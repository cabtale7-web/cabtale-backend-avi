<?php
namespace App\Providers;

use Illuminate\Filesystem\FilesystemAdapter;
use Google\Cloud\Storage\StorageClient;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;
use League\Flysystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\MailManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Resend\Laravel\Transport\ResendTransportFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register Resend transport for mailer
        $this->app->extend(MailManager::class, function ($manager, $app) {
            $manager->extend('resend', function ($app) {
                return new \Illuminate\Mail\Mailer(
                    $app['view'],
                    $app['swift.mailer'] ?? $app['mailer.transport'],
                    $app['events']
                );
            });
            return $manager;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        /*
         |----------------------------------------------------------------------
         | Force HTTPS for production deployments when enabled via env
         |----------------------------------------------------------------------
         */
        $forceHttps = filter_var(config('app.force_https'), FILTER_VALIDATE_BOOLEAN);
        if ($forceHttps) {
            URL::forceScheme('https');
        }

        /*
         |----------------------------------------------------------------------
         | Pagination styling
         |----------------------------------------------------------------------
         */
        Paginator::useBootstrap();

        /*
         |----------------------------------------------------------------------
         | Register Google Cloud Storage adapter for Laravel Storage
         |----------------------------------------------------------------------
         */
        \Illuminate\Support\Facades\Storage::extend('gcs', function ($app, $config) {
            $storageClient = new StorageClient([
                'projectId' => $config['project_id'],
                'keyFilePath' => $config['key_file'],
            ]);

            $bucket = $storageClient->bucket($config['bucket']);
            $adapter = new GoogleCloudStorageAdapter($bucket);
            $flysystem = new Filesystem($adapter);

            return new FilesystemAdapter($flysystem, $adapter, $config);
        });
    }
}
