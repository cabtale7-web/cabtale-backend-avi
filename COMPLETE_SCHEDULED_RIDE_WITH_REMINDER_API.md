# Complete Scheduled Ride API - With 30-Min Reminder
## Customer & Driver Integration Guide (Updated)

---

## 🆕 What's New: 30-Minute Reminder

When a driver **accepts** a scheduled ride, they will receive a **reminder notification 30 minutes before** the scheduled pickup time.

---

## Table of Contents
1. [Overview](#overview)
2. [Customer APIs](#customer-apis)
3. [Driver APIs](#driver-apis)
4. [Push Notifications](#push-notifications)
5. [30-Minute Reminder Flow](#30-minute-reminder-flow)
6. [Frontend Implementation](#frontend-implementation)
7. [Testing Guide](#testing-guide)

---

## Overview

### How Scheduled Rides Work

1. **Customer books** ride for future time (minimum 2 hours ahead)
2. **System waits** until scheduled time
3. **Drivers notified** at scheduled time
4. **Driver accepts** the ride
5. **🆕 30-min reminder** sent to driver automatically
6. **Driver picks up** customer at scheduled time
7. **Trip completes** normally

### Base URL
```
https://your-domain.com/api
```

### Authentication
```
Authorization: Bearer {access_token}
```

---

# Customer APIs

## 1. Book Scheduled Ride

### Endpoint
```http
POST /api/customer/trip/store
```

### Headers
```json
{
  "Authorization": "Bearer {customer_token}",
  "Content-Type": "application/json",
  "zoneId": "zone-uuid-here"
}
```

### Request Payload
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
  "note": "Please come to gate 2",
  "scheduled_at": "2025-07-20 14:00:00"
}
```

### Response (201 Created)
```json
{
  "response_code": "trip_request_200",
  "message": "Trip request created successfully",
  "data": {
    "id": "trip-uuid",
    "ref_id": "100001",
    "estimated_fare": 250.00,
    "current_status": "pending",
    "scheduled_at": "2025-07-20 14:00:00",
    "type": "ride_request"
  }
}
```

---

## 2. Cancel Scheduled Ride

### Endpoint
```http
POST /api/customer/trip/status-update/{trip_id}
```

### Request
```json
{
  "status": "cancelled",
  "trip_cancellation_reason": "Plans changed"
}
```

---

# Driver APIs

## 3. Get Pending Rides

### Endpoint
```http
GET /api/driver/trip/pending-list?limit=10&offset=1
```

### Headers
```json
{
  "Authorization": "Bearer {driver_token}",
  "zoneId": "zone-uuid-here"
}
```

### Response
```json
{
  "response_code": "default_200",
  "data": [
    {
      "id": "trip-uuid-1",
      "ref_id": "100001",
      "customer": {
        "first_name": "John",
        "last_name": "Doe",
        "phone": "+919876543210",
        "avg_rating": 4.5
      },
      "estimated_fare": 250.00,
      "scheduled_at": "2025-07-20 14:00:00",
      "pickup_address": "Connaught Place, New Delhi",
      "destination_address": "Rajiv Chowk, Delhi"
    }
  ]
}
```

---

## 4. Accept Ride ✅

### Endpoint
```http
POST /api/driver/trip/request-action
```

### Request
```json
{
  "trip_request_id": "trip-uuid-here",
  "action": "accepted"
}
```

### Response (200 OK)
```json
{
  "response_code": "default_update_200",
  "message": "Trip accepted successfully"
}
```

### 🆕 What Happens After Accept (Scheduled Ride)
1. Trip assigned to driver
2. Driver status → `on_trip`
3. Customer gets notification: "Driver assigned"
4. OTP generated
5. **🔔 30-minute reminder scheduled automatically**
6. Other drivers can't see this trip

---

## 5. Reject Ride ❌

### Endpoint
```http
POST /api/driver/trip/request-action
```

### Request
```json
{
  "trip_request_id": "trip-uuid-here",
  "action": "rejected"
}
```

---

# Push Notifications

## 🆕 Driver Notifications (Updated)

### 1. Scheduled Ride Available (At Scheduled Time)
```json
{
  "title": "Scheduled Ride Available",
  "description": "A scheduled ride is now available",
  "ride_request_id": "trip-uuid",
  "action": "new_ride_request_notification",
  "data": {
    "scheduled_at": "2025-07-20 14:00:00",
    "estimated_fare": 250.00,
    "pickup_address": "Connaught Place, New Delhi"
  }
}
```

### 2. 🆕 30-Minute Reminder (NEW!)
**Sent automatically 30 minutes before scheduled pickup time**

```json
{
  "title": "Upcoming Scheduled Trip",
  "description": "Your scheduled trip #100001 starts in 30 minutes. Please be ready!",
  "ride_request_id": "trip-uuid",
  "type": "ride_request",
  "action": "scheduled_trip_reminder",
  "data": {
    "trip_ref_id": "100001",
    "scheduled_at": "2025-07-20 14:00:00",
    "pickup_address": "Connaught Place, New Delhi",
    "customer_name": "John Doe",
    "estimated_fare": 250.00
  }
}
```

### 3. Trip Cancelled by Customer
```json
{
  "title": "Trip Cancelled",
  "description": "Customer has cancelled the trip",
  "ride_request_id": "trip-uuid",
  "action": "ride_cancelled"
}
```

---

## Customer Notifications

### 1. Driver Assigned
```json
{
  "title": "Driver Assigned",
  "description": "Your driver is confirmed for scheduled ride",
  "ride_request_id": "trip-uuid",
  "action": "driver_assigned"
}
```

---

# 30-Minute Reminder Flow

## Complete Timeline Example

**Customer books ride at 10:00 AM for 2:00 PM pickup**

```
10:00 AM  → Customer books scheduled ride
            ✅ Ride created with scheduled_at = 2:00 PM
            
2:00 PM   → System notifies nearby drivers
            📢 "Scheduled ride available"
            
2:05 PM   → Driver accepts the ride
            ✅ Trip assigned to driver
            🔔 30-min reminder scheduled for 1:30 PM
            
1:30 PM   → 🆕 Driver receives reminder
            📱 "Your trip starts in 30 minutes"
            
2:00 PM   → Driver arrives at pickup
            🔢 Matches OTP
            🚗 Trip starts
```

## Backend Logic

```php
// When driver accepts scheduled trip
if ($trip->scheduled_at) {
    $scheduledTime = Carbon::parse($trip->scheduled_at);
    $reminderTime = $scheduledTime->copy()->subMinutes(30);
    
    // Only schedule if reminder time is in future
    if ($reminderTime->isFuture()) {
        SendScheduledTripReminderJob::dispatch($trip->id)
            ->delay($reminderTime);
    }
}
```

### Edge Cases Handled

1. **Driver accepts 20 minutes before scheduled time**
   - No reminder sent (already too close)

2. **Driver accepts 1 hour before scheduled time**
   - Reminder sent at 30 minutes before

3. **Customer cancels after driver accepts**
   - Reminder job still in queue but checks trip status
   - No notification sent if trip is cancelled

4. **Driver cancels after accepting**
   - Reminder job checks if driver is still assigned
   - No notification sent if driver changed

---

# Frontend Implementation

## Driver App - Handle 30-Min Reminder

### 1. Push Notification Handler

```javascript
// Handle incoming push notifications
const handlePushNotification = (notification) => {
  const { action, ride_request_id, data } = notification;
  
  switch (action) {
    case 'new_ride_request_notification':
      // New scheduled ride available
      showNotification({
        title: 'Scheduled Ride Available',
        message: `Pickup: ${data.pickup_address}`,
        tripId: ride_request_id
      });
      fetchPendingRides();
      break;
      
    case 'scheduled_trip_reminder':
      // 🆕 30-minute reminder
      showReminderNotification({
        title: notification.title,
        message: notification.description,
        tripId: ride_request_id,
        priority: 'high',
        sound: 'reminder_sound.mp3'
      });
      
      // Show in-app alert
      showInAppAlert({
        title: '⏰ Upcoming Trip',
        message: `Trip #${data.trip_ref_id} starts in 30 minutes`,
        buttons: [
          {
            text: 'View Details',
            action: () => navigateToTripDetails(ride_request_id)
          },
          {
            text: 'OK',
            action: () => dismissAlert()
          }
        ]
      });
      break;
      
    case 'ride_cancelled':
      removeTripFromList(ride_request_id);
      showToast('Trip was cancelled by customer');
      break;
  }
};
```

### 2. Reminder Notification UI

```javascript
// Show prominent reminder notification
const showReminderNotification = ({ title, message, tripId, priority, sound }) => {
  // For Android
  if (Platform.OS === 'android') {
    PushNotification.localNotification({
      channelId: 'scheduled-trips',
      title: title,
      message: message,
      priority: priority,
      soundName: sound,
      vibrate: true,
      vibration: 1000,
      playSound: true,
      actions: ['View', 'Dismiss'],
      data: { tripId: tripId }
    });
  }
  
  // For iOS
  if (Platform.OS === 'ios') {
    PushNotificationIOS.addNotificationRequest({
      id: `reminder-${tripId}`,
      title: title,
      body: message,
      sound: sound,
      badge: 1,
      userInfo: { tripId: tripId }
    });
  }
};
```

### 3. In-App Reminder Alert

```javascript
// Show in-app modal when app is open
const showInAppAlert = ({ title, message, buttons }) => {
  return (
    <Modal visible={true} transparent={true}>
      <View style={styles.alertContainer}>
        <View style={styles.alertBox}>
          <Text style={styles.alertIcon}>⏰</Text>
          <Text style={styles.alertTitle}>{title}</Text>
          <Text style={styles.alertMessage}>{message}</Text>
          
          <View style={styles.buttonContainer}>
            {buttons.map((button, index) => (
              <TouchableOpacity
                key={index}
                style={[
                  styles.button,
                  index === 0 ? styles.primaryButton : styles.secondaryButton
                ]}
                onPress={button.action}
              >
                <Text style={styles.buttonText}>{button.text}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  alertContainer: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  alertBox: {
    backgroundColor: 'white',
    borderRadius: 16,
    padding: 24,
    width: '85%',
    alignItems: 'center',
  },
  alertIcon: {
    fontSize: 48,
    marginBottom: 16,
  },
  alertTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 8,
    textAlign: 'center',
  },
  alertMessage: {
    fontSize: 16,
    color: '#666',
    marginBottom: 24,
    textAlign: 'center',
  },
  buttonContainer: {
    flexDirection: 'row',
    gap: 12,
  },
  button: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: 'center',
  },
  primaryButton: {
    backgroundColor: '#4CAF50',
  },
  secondaryButton: {
    backgroundColor: '#E0E0E0',
  },
  buttonText: {
    fontSize: 16,
    fontWeight: '600',
  },
});
```

### 4. Trip Details Screen - Show Countdown

```javascript
const TripDetailsScreen = ({ trip }) => {
  const [timeRemaining, setTimeRemaining] = useState('');
  
  useEffect(() => {
    if (!trip.scheduled_at) return;
    
    const interval = setInterval(() => {
      const now = new Date();
      const scheduled = new Date(trip.scheduled_at);
      const diff = scheduled - now;
      
      if (diff <= 0) {
        setTimeRemaining('Starting now!');
        clearInterval(interval);
        return;
      }
      
      const hours = Math.floor(diff / (1000 * 60 * 60));
      const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
      
      if (hours > 0) {
        setTimeRemaining(`Starts in ${hours}h ${minutes}m`);
      } else if (minutes > 30) {
        setTimeRemaining(`Starts in ${minutes} minutes`);
      } else if (minutes > 0) {
        setTimeRemaining(`⚠️ Starts in ${minutes} minutes!`);
      } else {
        setTimeRemaining('⚠️ Starting very soon!');
      }
    }, 1000);
    
    return () => clearInterval(interval);
  }, [trip.scheduled_at]);
  
  return (
    <View style={styles.container}>
      {trip.scheduled_at && (
        <View style={styles.countdownBanner}>
          <Text style={styles.countdownText}>⏰ {timeRemaining}</Text>
        </View>
      )}
      
      <View style={styles.tripDetails}>
        <Text>Trip #{trip.ref_id}</Text>
        <Text>Customer: {trip.customer.first_name}</Text>
        <Text>Pickup: {trip.pickup_address}</Text>
        <Text>Fare: ₹{trip.estimated_fare}</Text>
      </View>
    </View>
  );
};
```

---

# Testing Guide

## Test Case 1: 30-Minute Reminder (Normal Flow)

**Setup:**
- Current time: 10:00 AM
- Schedule ride for: 11:00 AM (1 hour ahead)

**Steps:**
1. Customer books ride for 11:00 AM
2. Wait until 11:00 AM
3. Driver receives notification
4. Driver accepts at 11:02 AM
5. **Wait until 10:30 AM (30 min before 11:00 AM)**
6. ✅ Driver should receive reminder notification

**Expected Result:**
```
Notification Title: "Upcoming Scheduled Trip"
Notification Body: "Your scheduled trip #100001 starts in 30 minutes. Please be ready!"
```

---

## Test Case 2: Accept Close to Scheduled Time

**Setup:**
- Current time: 10:00 AM
- Schedule ride for: 10:25 AM (25 minutes ahead)

**Steps:**
1. Customer books ride for 10:25 AM
2. Wait until 10:25 AM
3. Driver receives notification
4. Driver accepts at 10:27 AM (only 23 min before scheduled time)

**Expected Result:**
- ❌ No 30-minute reminder sent (already too close)
- Driver proceeds directly to pickup

---

## Test Case 3: Customer Cancels After Driver Accepts

**Setup:**
- Driver accepted scheduled ride
- 30-min reminder is scheduled

**Steps:**
1. Driver accepts ride at 10:00 AM for 11:00 AM pickup
2. Customer cancels at 10:15 AM
3. Wait until 10:30 AM (reminder time)

**Expected Result:**
- ❌ No reminder sent (trip is cancelled)
- Reminder job checks trip status and exits

---

## Test Case 4: Driver Cancels After Accepting

**Setup:**
- Driver accepted scheduled ride
- 30-min reminder is scheduled

**Steps:**
1. Driver A accepts ride
2. Driver A cancels
3. Driver B accepts the ride
4. Wait for reminder time

**Expected Result:**
- ❌ No reminder sent to Driver A
- ✅ New reminder scheduled for Driver B

---

# Backend Setup

## 1. Run Migration
```bash
php artisan migrate
```

## 2. Run Seeder (Add Notification Message)
```bash
php artisan db:seed --class=ScheduledTripReminderNotificationSeeder
```

## 3. Start Queue Worker
```bash
php artisan queue:work --queue=high,default --tries=3
```

## 4. Verify Notification Message

Check in database:
```sql
SELECT * FROM firebase_push_notifications 
WHERE name = 'scheduled_trip_reminder';
```

Should return:
```
name: scheduled_trip_reminder
title: Upcoming Scheduled Trip
value: Your scheduled trip #{tripId} starts in 30 minutes. Please be ready!
status: 1
```

---

# API Summary

## Customer Endpoints
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/customer/trip/store` | POST | Book scheduled ride |
| `/api/customer/trip/list` | GET | View all rides |
| `/api/customer/trip/status-update/{id}` | POST | Cancel ride |

## Driver Endpoints
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/driver/trip/pending-list` | GET | Get available rides |
| `/api/driver/trip/request-action` | POST | Accept/Reject ride |
| `/api/driver/trip/match-otp` | POST | Start trip |
| `/api/driver/trip/status-update` | POST | Complete/Cancel trip |

## Push Notification Actions
| Action | When | Recipient |
|--------|------|-----------|
| `new_ride_request_notification` | At scheduled time | Nearby drivers |
| `scheduled_trip_reminder` | 🆕 30 min before | Assigned driver |
| `driver_assigned` | Driver accepts | Customer |
| `ride_cancelled` | Trip cancelled | Driver/Customer |

---

# Troubleshooting

## Issue: Reminder not received

**Check:**
1. Queue worker running?
   ```bash
   php artisan queue:work
   ```

2. Job in queue?
   ```sql
   SELECT * FROM jobs WHERE payload LIKE '%SendScheduledTripReminderJob%';
   ```

3. Driver FCM token valid?
   ```sql
   SELECT fcm_token FROM users WHERE id = 'driver-id';
   ```

4. Notification message exists?
   ```sql
   SELECT * FROM firebase_push_notifications 
   WHERE name = 'scheduled_trip_reminder';
   ```

---

## Issue: Multiple reminders sent

**Cause:** Driver accepted, cancelled, then another driver accepted

**Solution:** Job checks if driver is still assigned before sending

---

## Issue: Reminder sent at wrong time

**Check timezone:**
```php
// In config/app.php
'timezone' => 'Asia/Kolkata',  // Set your timezone
```

---

# Summary

## What Changed

✅ **Added:** 30-minute reminder notification for drivers  
✅ **Added:** `SendScheduledTripReminderJob` job  
✅ **Added:** Notification message seeder  
✅ **Updated:** `handleRequestActionPushNotification` method  
✅ **Updated:** API documentation  

## Files Modified/Created

1. ✅ `app/Jobs/SendScheduledTripReminderJob.php` (NEW)
2. ✅ `database/seeders/ScheduledTripReminderNotificationSeeder.php` (NEW)
3. ✅ `Modules/TripManagement/Service/TripRequestService.php` (UPDATED)
4. ✅ `COMPLETE_SCHEDULED_RIDE_WITH_REMINDER_API.md` (NEW)

## Next Steps

1. Run migration: `php artisan migrate`
2. Run seeder: `php artisan db:seed --class=ScheduledTripReminderNotificationSeeder`
3. Start queue: `php artisan queue:work`
4. Test with frontend team
5. Deploy to production

---

**Questions? Contact backend team with:**
- Endpoint used
- Request/Response
- Expected vs Actual behavior
- Logs from queue worker
