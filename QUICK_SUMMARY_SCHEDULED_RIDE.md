# Scheduled Ride Feature - Quick Summary (Hindi + English)

## 🎯 Feature Overview / फीचर का सारांश

### English
Customer can book a cab **2 hours in advance**. When the scheduled time arrives, nearby drivers get notified. When a driver accepts, they receive a **30-minute reminder** before pickup time.

### Hindi
Customer cab को **2 घंटे पहले** book कर सकता है। जब scheduled time आता है, तो nearby drivers को notification मिलती है। जब driver accept करता है, तो उसे pickup time से **30 मिनट पहले reminder** मिलती है।

---

## 📱 Complete Flow / पूरा फ्लो

```
1. Customer books ride
   ↓
2. System waits until scheduled time
   ↓
3. Drivers get notification at scheduled time
   ↓
4. Driver accepts ride
   ↓
5. 🆕 Driver gets 30-min reminder automatically
   ↓
6. Driver picks up customer
   ↓
7. Trip completes
```

---

## 🔧 Backend Changes / बैकएंड में बदलाव

### Files Created / नई फाइलें
1. ✅ `SendScheduledTripReminderJob.php` - 30-min reminder job
2. ✅ `ScheduledTripReminderNotificationSeeder.php` - Notification message
3. ✅ Migration for `scheduled_at` column

### Files Updated / अपडेट की गई फाइलें
1. ✅ `TripRequest.php` - Uncommented `scheduled_at`
2. ✅ `RideRequestCreate.php` - Added validation
3. ✅ `TripRequestService.php` - Added reminder dispatch logic

---

## 📡 API Endpoints

### Customer Side

#### 1. Book Scheduled Ride
```http
POST /api/customer/trip/store
```

**Payload:**
```json
{
  "pickup_coordinates": "[28.6139, 77.2090]",
  "destination_coordinates": "[28.7041, 77.1025]",
  "customer_coordinates": "[28.6139, 77.2090]",
  "pickup_address": "Connaught Place, New Delhi",
  "destination_address": "Rajiv Chowk, Delhi",
  "customer_request_coordinates": "[28.6139, 77.2090]",
  "estimated_time": "25.5",
  "estimated_distance": "15.2",
  "estimated_fare": "250.00",
  "vehicle_category_id": "category-uuid",
  "type": "ride_request",
  "scheduled_at": "2025-07-20 14:00:00"
}
```

**Important:**
- `scheduled_at` must be at least 2 hours from now
- Format: `YYYY-MM-DD HH:MM:SS`

---

### Driver Side

#### 2. Get Pending Rides
```http
GET /api/driver/trip/pending-list?limit=10&offset=1
```

**Response:**
```json
{
  "data": [
    {
      "id": "trip-uuid",
      "ref_id": "100001",
      "scheduled_at": "2025-07-20 14:00:00",
      "estimated_fare": 250.00,
      "pickup_address": "Connaught Place"
    }
  ]
}
```

#### 3. Accept Ride ✅
```http
POST /api/driver/trip/request-action
```

**Payload:**
```json
{
  "trip_request_id": "trip-uuid",
  "action": "accepted"
}
```

**What happens:**
- Trip assigned to driver
- 30-min reminder scheduled automatically
- Customer notified

#### 4. Reject Ride ❌
```http
POST /api/driver/trip/request-action
```

**Payload:**
```json
{
  "trip_request_id": "trip-uuid",
  "action": "rejected"
}
```

---

## 🔔 Push Notifications

### 1. New Scheduled Ride (At Scheduled Time)
```json
{
  "title": "Scheduled Ride Available",
  "description": "A scheduled ride is now available",
  "action": "new_ride_request_notification",
  "ride_request_id": "trip-uuid"
}
```

### 2. 🆕 30-Minute Reminder (NEW!)
```json
{
  "title": "Upcoming Scheduled Trip",
  "description": "Your scheduled trip #100001 starts in 30 minutes. Please be ready!",
  "action": "scheduled_trip_reminder",
  "ride_request_id": "trip-uuid"
}
```

### 3. Driver Assigned (To Customer)
```json
{
  "title": "Driver Assigned",
  "description": "Your driver is confirmed",
  "action": "driver_assigned",
  "ride_request_id": "trip-uuid"
}
```

---

## 💻 Frontend Implementation

### Customer App - Date Picker

```javascript
// Minimum time = Current time + 2 hours
const minScheduleTime = new Date();
minScheduleTime.setHours(minScheduleTime.getHours() + 2);

// Format for API
const formatDateTime = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  
  return `${year}-${month}-${day} ${hours}:${minutes}:00`;
};
```

### Driver App - Accept/Reject

```javascript
// Accept Ride
const acceptRide = async (tripId) => {
  const response = await fetch('/api/driver/trip/request-action', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      trip_request_id: tripId,
      action: 'accepted'
    })
  });
  
  if (response.ok) {
    console.log('Ride accepted - 30-min reminder will be sent automatically');
  }
};

// Reject Ride
const rejectRide = async (tripId) => {
  await fetch('/api/driver/trip/request-action', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      trip_request_id: tripId,
      action: 'rejected'
    })
  });
};
```

### Handle 30-Min Reminder Notification

```javascript
const handlePushNotification = (notification) => {
  if (notification.action === 'scheduled_trip_reminder') {
    // Show prominent alert
    Alert.alert(
      '⏰ Upcoming Trip',
      'Your scheduled trip starts in 30 minutes!',
      [
        { text: 'View Details', onPress: () => navigateToTrip(notification.ride_request_id) },
        { text: 'OK' }
      ]
    );
    
    // Play sound
    playReminderSound();
    
    // Show in-app notification
    showInAppNotification({
      title: notification.title,
      message: notification.description
    });
  }
};
```

---

## 🧪 Testing Checklist

### Customer Testing
- [ ] Book ride 2 hours ahead - Success
- [ ] Book ride 1 hour ahead - Error (minimum 2 hours)
- [ ] View scheduled ride in "My Rides"
- [ ] Cancel scheduled ride
- [ ] Receive "Driver Assigned" notification

### Driver Testing
- [ ] Receive notification at scheduled time
- [ ] See scheduled ride in pending list
- [ ] Accept scheduled ride
- [ ] **🆕 Receive 30-min reminder notification**
- [ ] Reject scheduled ride
- [ ] Try accepting already assigned ride (should fail)

---

## ⚙️ Backend Setup Commands

```bash
# 1. Run migration
php artisan migrate

# 2. Run seeder (add notification message)
php artisan db:seed --class=ScheduledTripReminderNotificationSeeder

# 3. Start queue worker (IMPORTANT!)
php artisan queue:work --queue=high,default --tries=3

# 4. Check if notification exists
php artisan tinker
>>> \Modules\BusinessManagement\Entities\FirebasePushNotification::where('name', 'scheduled_trip_reminder')->first();
```

---

## 🐛 Common Issues

### Issue 1: Reminder not received
**Solution:** Check if queue worker is running
```bash
php artisan queue:work
```

### Issue 2: Currency showing $ instead of ₹
**Solution:** 
- Go to Admin Panel → Business Settings
- Set Currency Symbol to `₹`
- Clear cache

### Issue 3: Scheduled ride not triggering
**Solution:** Ensure queue worker is running continuously

---

## 📊 Timeline Example

```
10:00 AM → Customer books ride for 2:00 PM
           ✅ Ride created

2:00 PM  → Drivers notified
           📢 "Scheduled ride available"

2:05 PM  → Driver accepts
           ✅ Trip assigned
           🔔 30-min reminder scheduled

1:30 PM  → 🆕 Driver gets reminder
           📱 "Trip starts in 30 minutes"

2:00 PM  → Driver picks up customer
           🚗 Trip starts
```

---

## 📞 Support

**For Integration Issues:**
- API not working: Check endpoint, headers, payload
- Notification not received: Check FCM token, queue worker
- Validation error: Check `scheduled_at` format and time

**Contact:**
- Backend Team: backend@company.com
- Include: Endpoint, Request, Response, Error message

---

## ✅ Summary

### What's Working Now:
1. ✅ Customer can book 2 hours ahead
2. ✅ Drivers notified at scheduled time
3. ✅ Driver can accept/reject
4. ✅ **30-minute reminder sent automatically**
5. ✅ OTP verification
6. ✅ Trip completion

### What Frontend Needs to Do:
1. Add date/time picker (min 2 hours)
2. Add `scheduled_at` field to booking API
3. Show "Scheduled" badge in ride list
4. Add Accept/Reject buttons for drivers
5. Handle 30-min reminder notification
6. Show countdown timer for scheduled rides

---

## 📄 Complete Documentation

For detailed API docs, see:
- `COMPLETE_SCHEDULED_RIDE_WITH_REMINDER_API.md`

This file has:
- All endpoints with examples
- Complete request/response payloads
- Frontend code samples
- Testing guide
- Troubleshooting

---

**Ready to integrate! 🚀**
