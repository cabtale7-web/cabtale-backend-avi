# GCP VM Runbook: One VM, Separate Frontend + Backend Domains

This runbook deploys:

- Frontend (React SPA): `cabtale.com` (+ `www.cabtale.com`)
- Backend (Laravel API + admin): `backend.cabtale.com`
- Same VM instance, separate Nginx sites
- Optional: access backend admin/API from frontend domain via `/admin` and `/api`

---

## 0) Set variables (run first on VM)

```bash
export FRONTEND_DOMAIN="cabtale.com"
export BACKEND_DOMAIN="backend.cabtale.com"

# Backend repo (Laravel)
export BACKEND_REPO="https://github.com/teammarktaleworld-crypto/cabtale_main.git"

# Frontend repo:
# If same repo, keep FRONTEND_REPO same as BACKEND_REPO.
export FRONTEND_REPO="$BACKEND_REPO"

# React app folder inside FRONTEND_REPO
# Example: frontend, web, client, or . (if package.json is repo root)
export FRONTEND_SUBDIR="frontend"
```

If your React app is not in `frontend`, update `FRONTEND_SUBDIR` before step 3.

---

## 1) One-time VM setup (Ubuntu)

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx git curl unzip rsync mysql-server certbot python3-certbot-nginx software-properties-common

# PHP 8.2 + extensions needed by Laravel + Redis
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-curl php8.2-mysql php8.2-xml php8.2-mbstring php8.2-zip php8.2-bcmath php8.2-intl php8.2-gd php8.2-redis

# Composer
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
rm composer-setup.php
composer --version

# Node.js 20 (for React build)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

---

## 2) Deploy backend (Laravel) to `backend.cabtale.com`

### 2.1 Get code

```bash
sudo mkdir -p /var/www
sudo chown -R $USER:$USER /var/www

if [ ! -d /var/www/cabtale-backend-src/.git ]; then
  cd /var/www
  git clone "$BACKEND_REPO" cabtale-backend-src
else
  cd /var/www/cabtale-backend-src
  git pull
fi

cd /var/www/cabtale-backend-src/public_html
```

### 2.2 Install PHP dependencies

```bash
composer install --no-dev --optimize-autoloader
[ -f .env ] || cp .env.example .env
php artisan key:generate --force
```

### 2.3 Database setup

```bash
export DB_NAME="cabtale_db"
export DB_USER="cabtale_user"
export DB_PASS="CHANGE_THIS_STRONG_PASSWORD"

sudo mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
sudo mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"
```

### 2.4 Configure `.env` correctly for this server

Edit:

```bash
nano /var/www/cabtale-backend-src/public_html/.env
```

Use this structure (replace secrets with your real values):

```env
APP_NAME=cabtale
APP_ENV=production
APP_MODE=live
APP_DEBUG=false
APP_URL=https://backend.cabtale.com

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cabtale_db
DB_USERNAME=cabtale_user
DB_PASSWORD=CHANGE_THIS_STRONG_PASSWORD

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_CLIENT=phpredis
REDIS_HOST=10.143.145.245
REDIS_PORT=6379
REDIS_PASSWORD=null

FILESYSTEM_DRIVER=local
GOOGLE_CLOUD_PROJECT_ID=cabtale
GOOGLE_CLOUD_STORAGE_BUCKET=cabtale-storage
GOOGLE_APPLICATION_CREDENTIALS=/var/www/cabtale-backend-src/public_html/storage/keys/cabtale-gcs.json

MAIL_MAILER=resend
MAIL_FROM_ADDRESS=teammarktaleworld@gmail.com
MAIL_FROM_NAME="Cabtale Backend"
```

Important:

- Keep only one `BROADCAST_DRIVER` (do not define both `reverb` and `pusher`).
- If using Reverb, use host without `https://`:
  - `REVERB_HOST=backend.cabtale.com`
  - `REVERB_SCHEME=https`
  - `REVERB_PORT=443`
- Your old `GOOGLE_APPLICATION_CREDENTIALS=C:/var/www/...` path is Windows style and wrong on Ubuntu.

### 2.5 Migrate/cache/permissions

```bash
cd /var/www/cabtale-backend-src/public_html
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link || true

sudo chown -R www-data:www-data /var/www/cabtale-backend-src
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 3) Deploy frontend (React) to `cabtale.com`

### 3.1 Get code

```bash
if [ ! -d /var/www/cabtale-frontend-src/.git ]; then
  cd /var/www
  git clone "$FRONTEND_REPO" cabtale-frontend-src
else
  cd /var/www/cabtale-frontend-src
  git pull
fi

cd /var/www/cabtale-frontend-src/${FRONTEND_SUBDIR}
```

If folder is wrong, find React folder:

```bash
cd /var/www/cabtale-frontend-src
find . -maxdepth 3 -name package.json
```

### 3.2 Set API base URL to backend HTTPS

Use whichever env key your React app reads:

```bash
cat > .env.production <<EOF
VITE_API_BASE_URL=https://${BACKEND_DOMAIN}/api
REACT_APP_API_BASE_URL=https://${BACKEND_DOMAIN}/api
NEXT_PUBLIC_API_BASE_URL=https://${BACKEND_DOMAIN}/api
EOF
```

### 3.3 Build and publish static web

```bash
npm ci
npm run build

sudo mkdir -p /var/www/cabtale-frontend

# Vite output is usually dist/, CRA output is usually build/
if [ -d dist ]; then
  sudo rsync -av --delete dist/ /var/www/cabtale-frontend/
elif [ -d build ]; then
  sudo rsync -av --delete build/ /var/www/cabtale-frontend/
else
  echo "Build output folder not found (expected dist/ or build/)." && exit 1
fi

sudo chown -R www-data:www-data /var/www/cabtale-frontend
```

---

## 4) Nginx config (separate sites)

### 4.1 Backend site

```bash
sudo tee /etc/nginx/sites-available/cabtale-backend >/dev/null <<EOF
server {
    listen 80;
    server_name ${BACKEND_DOMAIN};

    root /var/www/cabtale-backend-src/public_html/public;
    index index.php index.html;

    client_max_body_size 64M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
```

### 4.2 Frontend site (`/admin` and `/api` passthrough enabled)

```bash
sudo tee /etc/nginx/sites-available/cabtale-frontend >/dev/null <<EOF
server {
    listen 80;
    server_name ${FRONTEND_DOMAIN} www.${FRONTEND_DOMAIN};

    root /var/www/cabtale-frontend;
    index index.html;

    # Optional: cabtale.com/admin -> backend admin
    location = /admin {
        return 301 /admin/;
    }
    location ^~ /admin/ {
        proxy_pass http://127.0.0.1;
        proxy_set_header Host ${BACKEND_DOMAIN};
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    # Optional: cabtale.com/api/* -> backend api
    location ^~ /api/ {
        proxy_pass http://127.0.0.1;
        proxy_set_header Host ${BACKEND_DOMAIN};
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location ~* \.(?:js|css|png|jpg|jpeg|gif|svg|ico|webp|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }
}
EOF
```

### 4.3 Enable and restart

```bash
sudo ln -sf /etc/nginx/sites-available/cabtale-backend /etc/nginx/sites-enabled/cabtale-backend
sudo ln -sf /etc/nginx/sites-available/cabtale-frontend /etc/nginx/sites-enabled/cabtale-frontend
sudo rm -f /etc/nginx/sites-enabled/default

sudo nginx -t
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

---

## 5) SSL certificates

```bash
sudo certbot --nginx -d ${BACKEND_DOMAIN}
sudo certbot --nginx -d ${FRONTEND_DOMAIN} -d www.${FRONTEND_DOMAIN}
sudo certbot renew --dry-run
```

---

## 6) DNS checklist

All A records must point to the same VM external IP:

- `backend.cabtale.com` -> VM IP
- `cabtale.com` -> VM IP
- `www.cabtale.com` -> VM IP

---

## 7) API behavior fix: why `/login` returns HTML

This is expected in your project.

- `/admin/auth/login` is web admin page (HTML)
- `/login` (if present) is web route behavior (HTML/redirect)
- API login is under `/api/...` and must be `POST`

Correct login endpoints:

- `POST /api/customer/auth/login`
- `POST /api/driver/auth/login`

Test correctly:

```bash
# This should return HTML (web/admin), not JSON:
curl -i https://${BACKEND_DOMAIN}/admin/auth/login

# This should return JSON (validation/auth response):
curl -i -X POST "https://${BACKEND_DOMAIN}/api/customer/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email_or_phone":"test@example.com","password":"wrong-pass"}'
```

Route check on server:

```bash
cd /var/www/cabtale-backend-src/public_html
php artisan route:list | grep -E "customer/auth/login|driver/auth/login|admin/auth/login"
```

---

## 8) Verify deployment

```bash
curl -I https://${BACKEND_DOMAIN}
curl -I https://${FRONTEND_DOMAIN}
curl -I https://${FRONTEND_DOMAIN}/admin/auth/login
curl -I https://${FRONTEND_DOMAIN}/api/customer/configuration
```

Open in browser:

- `https://cabtale.com`
- `https://cabtale.com/admin/auth/login` (proxied)
- `https://backend.cabtale.com/admin/auth/login` (direct)

---

## 9) Update commands (next deploy)

### Backend update

```bash
cd /var/www/cabtale-backend-src
git pull
cd /var/www/cabtale-backend-src/public_html

composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

### Frontend update

```bash
cd /var/www/cabtale-frontend-src
git pull
cd /var/www/cabtale-frontend-src/${FRONTEND_SUBDIR}

npm ci
npm run build

if [ -d dist ]; then
  sudo rsync -av --delete dist/ /var/www/cabtale-frontend/
elif [ -d build ]; then
  sudo rsync -av --delete build/ /var/www/cabtale-frontend/
fi

sudo systemctl reload nginx
```

---

## 10) If API/login still fails, collect logs

```bash
curl -i https://${BACKEND_DOMAIN}/api/customer/configuration
curl -i -X POST "https://${BACKEND_DOMAIN}/api/customer/auth/login" -H "Accept: application/json" -H "Content-Type: application/json" -d '{}'

sudo tail -n 150 /var/log/nginx/error.log
sudo tail -n 150 /var/log/nginx/access.log

cd /var/www/cabtale-backend-src/public_html
php artisan optimize:clear
php artisan config:cache
```

Paste the full outputs to Codex if you need debugging.

---

## Security note (important)

You shared real-looking secrets (DB password, mail key, pusher keys). Rotate them after deployment and never commit them to Git.
