# Complete Scheduled Ride API Documentation
## Customer & Driver Integration Guide

---

## Table of Contents
1. [Overview](#overview)
2. [Customer APIs](#customer-apis)
3. [Driver APIs](#driver-apis)
4. [Push Notifications](#push-notifications)
5. [Frontend Implementation](#frontend-implementation)
6. [Testing Guide](#testing-guide)

---

## Overview

### What is Scheduled Ride?
Customers can book a cab at least **2 hours in advance**. The system will automatically notify nearby drivers at the scheduled time.

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

## 1. Book Ride (Immediate or Scheduled)

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

### Request - Immediate Ride
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
  "note": "Please come to gate 2"
}
```

### Request - Scheduled Ride ⭐ NEW
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

### Validation Rules
- `scheduled_at` must be at least **2 hours** from current time
- Format: `YYYY-MM-DD HH:MM:SS`
- Example: If now is `10:00 AM`, minimum is `12:00 PM`

---

## 2. Get Customer Ride List

### Endpoint
```http
GET /api/customer/trip/list?limit=10&offset=1&status=pending
```

### Response
```json
{
  "response_code": "default_200",
  "total_size": 25,
  "data": [
    {
      "id": "trip-uuid-1",
      "ref_id": "100001",
      "estimated_fare": 250.00,
      "current_status": "pending",
      "scheduled_at": "2025-07-20 14:00:00",
      "type": "ride_request",
      "created_at": "2025-01-15T10:30:00Z"
    }
  ]
}
```

---

## 3. Cancel Scheduled Ride

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

### Response
```json
{
  "response_code": "default_update_200",
  "message": "Trip cancelled successfully"
}
```

---

# Driver APIs

## 4. Get Pending Rides (Including Scheduled)

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
  "total_size": 5,
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
      "estimated_distance": 15.2,
      "current_status": "pending",
      "scheduled_at": "2025-07-20 14:00:00",
      "pickup_address": "Connaught Place, New Delhi",
      "destination_address": "Rajiv Chowk, Delhi",
      "note": "Please come to gate 2"
    }
  ]
}
```

**Note:** Scheduled rides appear in this list only at their scheduled time.

---

## 5. Accept Ride ✅

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

### What Happens
1. Trip assigned to driver
2. Driver status → `on_trip`
3. Customer gets notification: "Driver assigned"
4. OTP generated
5. Other drivers can't see this trip anymore

### Error - Already Assigned (403)
```json
{
  "response_code": "trip_request_driver_403",
  "message": "Trip already assigned to another driver"
}
```

---

## 6. Reject Ride ❌

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

### Response (200 OK)
```json
{
  "response_code": "default_update_200",
  "message": "Request processed successfully"
}
```

### What Happens
1. Driver added to rejected list
2. Trip removed from driver's pending list
3. Trip still available for other drivers
4. No notification to customer

---

## 7. Match OTP (Start Trip)

### Endpoint
```http
POST /api/driver/trip/match-otp
```

### Request
```json
{
  "trip_request_id": "trip-uuid-here",
  "otp": "1234"
}
```

### Response (200 OK)
```json
{
  "response_code": "default_store_200",
  "message": "OTP matched successfully"
}
```

### Error - Wrong OTP (403)
```json
{
  "response_code": "otp_mismatch_404",
  "message": "OTP does not match"
}
```

---

## 8. Complete/Cancel Trip

### Endpoint
```http
POST /api/driver/trip/status-update
```

### Request - Complete
```json
{
  "trip_request_id": "trip-uuid-here",
  "status": "completed"
}
```

### Request - Cancel
```json
{
  "trip_request_id": "trip-uuid-here",
  "status": "cancelled",
  "trip_cancellation_reason": "Customer didn't arrive"
}
```

### Response
```json
{
  "response_code": "default_update_200",
  "message": "Trip status updated successfully"
}
```

---

# Push Notifications

## Customer Notifications

### 1. Driver Assigned
```json
{
  "title": "Driver Assigned",
  "description": "Your driver is on the way",
  "ride_request_id": "trip-uuid",
  "action": "driver_assigned"
}
```

### 2. Trip Started
```json
{
  "title": "Trip Started",
  "description": "Your trip has started",
  "ride_request_id": "trip-uuid",
  "action": "otp_matched"
}
```

### 3. Trip Completed
```json
{
  "title": "Trip Completed",
  "description": "You have reached your destination",
  "ride_request_id": "trip-uuid",
  "action": "ride_completed"
}
```

---

## Driver Notifications

### 1. New Scheduled Ride (At Scheduled Time)
```json
{
  "title": "Scheduled Ride Available",
  "description": "A scheduled ride is now available",
  "ride_request_id": "trip-uuid",
  "action": "new_ride_request_notification",
  "data": {
    "scheduled_at": "2025-07-20 14:00:00",
    "is_scheduled": true,
    "estimated_fare": 250.00
  }
}
```

### 2. Trip Cancelled by Customer
```json
{
  "title": "Trip Cancelled",
  "description": "Customer has cancelled the trip",
  "ride_request_id": "trip-uuid",
  "action": "ride_cancelled"
}
```

---

# Frontend Implementation

## Customer App - Book Scheduled Ride

```javascript
// Date/Time Picker - Minimum 2 hours from now
const minScheduleTime = new Date();
minScheduleTime.setHours(minScheduleTime.getHours() + 2);

// Format for API
const formatDateTime = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  const seconds = '00';
  
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
};

// Book Ride
const bookRide = async (isScheduled, scheduledDateTime) => {
  const payload = {
    pickup_coordinates: JSON.stringify([28.6139, 77.2090]),
    destination_coordinates: JSON.stringify([28.7041, 77.1025]),
    customer_coordinates: JSON.stringify([28.6139, 77.2090]),
    pickup_address: "Connaught Place, New Delhi",
    destination_address: "Rajiv Chowk, Delhi",
    customer_request_coordinates: JSON.stringify([28.6139, 77.2090]),
    estimated_time: "25.5",
    estimated_distance: "15.2",
    estimated_fare: "250.00",
    vehicle_category_id: "category-uuid",
    type: "ride_request"
  };

  // Add scheduled_at only if scheduling
  if (isScheduled && scheduledDateTime) {
    payload.scheduled_at = formatDateTime(scheduledDateTime);
  }

  const response = await fetch('https://your-domain.com/api/customer/trip/store', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${customerToken}`,
      'Content-Type': 'application/json',
      'zoneId': 'zone-uuid'
    },
    body: JSON.stringify(payload)
  });

  const data = await response.json();
  
  if (response.ok) {
    if (isScheduled) {
      showMessage('Ride scheduled successfully!');
    } else {
      showMessage('Finding nearby drivers...');
    }
  } else {
    showError(data.message);
  }
};
```

---

## Driver App - Accept/Reject Ride

```javascript
// Accept Ride
const acceptRide = async (tripId) => {
  const response = await fetch('https://your-domain.com/api/driver/trip/request-action', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${driverToken}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      trip_request_id: tripId,
      action: 'accepted'
    })
  });

  const data = await response.json();
  
  if (response.ok) {
    console.log('Ride accepted');
    navigateToTripDetails(tripId);
  } else {
    if (data.response_code === 'trip_request_driver_403') {
      showError('This ride was already accepted by another driver');
    } else {
      showError(data.message);
    }
  }
};

// Reject Ride
const rejectRide = async (tripId) => {
  const response = await fetch('https://your-domain.com/api/driver/trip/request-action', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${driverToken}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      trip_request_id: tripId,
      action: 'rejected'
    })
  });

  if (response.ok) {
    console.log('Ride rejected');
    removeTripFromList(tripId);
  }
};

// Display Ride Card with Scheduled Badge
const renderTripCard = (trip) => {
  const isScheduled = trip.scheduled_at !== null;
  
  return (
    <div className="trip-card">
      {isScheduled && (
        <span className="badge scheduled">
          📅 Scheduled: {formatDateTime(trip.scheduled_at)}
        </span>
      )}
      <div className="customer-info">
        <p>{trip.customer.first_name} {trip.customer.last_name}</p>
        <p>⭐ {trip.customer.avg_rating}</p>
      </div>
      <div className="trip-info">
        <p>📍 {trip.pickup_address}</p>
        <p>📍 {trip.destination_address}</p>
        <p>💰 ₹{trip.estimated_fare}</p>
      </div>
      <div className="actions">
        <button onClick={() => acceptRide(trip.id)} className="btn-accept">
          ✅ Accept
        </button>
        <button onClick={() => rejectRide(trip.id)} className="btn-reject">
          ❌ Reject
        </button>
      </div>
    </div>
  );
};
```

---

## UI/UX Recommendations

### Customer App

**Booking Screen:**
```
┌─────────────────────────────┐
│  Book Your Ride             │
├─────────────────────────────┤
│  📍 Pickup Location         │
│  📍 Drop Location           │
│                             │
│  ⏰ When do you need it?    │
│  ○ Now                      │
│  ○ Schedule for Later       │
│                             │
│  [If Schedule selected:]    │
│  📅 Date: [Picker]          │
│  🕐 Time: [Picker]          │
│  ⚠️ Min 2 hours from now    │
│                             │
│  [Book Ride Button]         │
└─────────────────────────────┘
```

**My Rides Screen:**
```
┌─────────────────────────────┐
│  My Rides                   │
├─────────────────────────────┤
│  📅 SCHEDULED               │
│  Trip #100001               │
│  📍 CP → Rajiv Chowk        │
│  🕐 Jul 20, 2:00 PM         │
│  💰 ₹250                    │
│  [Cancel] [View Details]    │
├─────────────────────────────┤
│  ✅ COMPLETED               │
│  Trip #100000               │
│  📍 Home → Office           │
│  💰 ₹180                    │
│  [Rate Driver]              │
└─────────────────────────────┘
```

### Driver App

**Pending Rides Screen:**
```
┌─────────────────────────────┐
│  Available Rides            │
├─────────────────────────────┤
│  📅 SCHEDULED RIDE          │
│  🕐 Starts at 2:00 PM       │
│  👤 John Doe ⭐ 4.5         │
│  📍 CP → Rajiv Chowk        │
│  📏 15.2 km | 💰 ₹250       │
│  📝 "Come to gate 2"        │
│  [✅ Accept] [❌ Reject]    │
├─────────────────────────────┤
│  🚗 IMMEDIATE RIDE          │
│  👤 Jane Smith ⭐ 4.8       │
│  📍 Airport → Hotel         │
│  📏 8.5 km | 💰 ₹180        │
│  [✅ Accept] [❌ Reject]    │
└─────────────────────────────┘
```

---

# Testing Guide

## Customer Testing

### Test Case 1: Book Immediate Ride
- [ ] Open booking screen
- [ ] Select "Book Now"
- [ ] Enter pickup and destination
- [ ] Click "Book Ride"
- [ ] Verify: Ride created without `scheduled_at`
- [ ] Verify: "Finding drivers..." message shown

### Test Case 2: Book Scheduled Ride (Valid)
- [ ] Open booking screen
- [ ] Select "Schedule for Later"
- [ ] Pick date/time 3 hours from now
- [ ] Click "Book Ride"
- [ ] Verify: Ride created with `scheduled_at`
- [ ] Verify: "Ride scheduled successfully" message
- [ ] Check ride list: Shows "Scheduled" badge

### Test Case 3: Book Scheduled Ride (Invalid - Less than 2 hours)
- [ ] Select "Schedule for Later"
- [ ] Pick time 1 hour from now
- [ ] Click "Book Ride"
- [ ] Verify: Error message shown
- [ ] Verify: "Must be at least 2 hours from now"

### Test Case 4: Cancel Scheduled Ride
- [ ] Go to "My Rides"
- [ ] Find scheduled ride
- [ ] Click "Cancel"
- [ ] Enter reason
- [ ] Verify: Ride status → "cancelled"

---

## Driver Testing

### Test Case 5: Receive Scheduled Ride Notification
- [ ] Customer books ride for 2 hours later
- [ ] Driver is online
- [ ] Wait until scheduled time
- [ ] Verify: Driver receives push notification
- [ ] Verify: Ride appears in pending list
- [ ] Verify: Shows "Scheduled" badge

### Test Case 6: Accept Scheduled Ride
- [ ] See scheduled ride in pending list
- [ ] Click "Accept"
- [ ] Verify: Success message
- [ ] Verify: Navigate to trip details
- [ ] Verify: Customer receives "Driver assigned" notification
- [ ] Verify: OTP is shown

### Test Case 7: Reject Scheduled Ride
- [ ] See scheduled ride in pending list
- [ ] Click "Reject"
- [ ] Verify: Ride removed from list
- [ ] Verify: No notification to customer
- [ ] Verify: Ride still available for other drivers

### Test Case 8: Try Accepting Already Assigned Ride
- [ ] Driver A accepts ride
- [ ] Driver B tries to accept same ride
- [ ] Verify: Driver B gets error
- [ ] Verify: "Already assigned" message

### Test Case 9: Complete Scheduled Ride
- [ ] Accept scheduled ride
- [ ] Navigate to pickup
- [ ] Match OTP
- [ ] Navigate to destination
- [ ] Click "Complete"
- [ ] Verify: Trip status → "completed"
- [ ] Verify: Fare calculated

---

## Backend Requirements Checklist

- [x] `scheduled_at` column added to `trip_requests` table
- [x] Validation: minimum 2 hours from now
- [x] `ProcessScheduledTripJob` dispatched with delay
- [x] Queue worker running: `php artisan queue:work`
- [x] Drivers notified at scheduled time
- [x] Accept/Reject endpoints working
- [x] OTP generation on accept
- [x] Push notifications configured

---

## Common Issues & Solutions

### Issue 1: Scheduled rides not triggering
**Solution:** Ensure queue worker is running:
```bash
php artisan queue:work --queue=high,default
```

### Issue 2: Validation error "must be after..."
**Solution:** Ensure `scheduled_at` is at least 2 hours from current time in UTC

### Issue 3: Driver not receiving notification
**Solution:** 
- Check driver is online
- Check driver is within search radius
- Check FCM token is valid
- Check queue worker is running

### Issue 4: Currency showing $ instead of ₹
**Solution:** Update business settings:
- Admin Panel → Business Settings → Currency Symbol → Set to `₹`
- Clear cache: `Session::forget('currency_symbol')`

---

## API Error Codes

| Code | Message | Action |
|------|---------|--------|
| `trip_request_200` | Success | Ride created |
| `default_400` | Validation failed | Check errors array |
| `incomplete_ride_403` | Incomplete ride exists | Complete current ride first |
| `zone_404` | Zone not found | Check zone ID |
| `driver_unavailable_403` | Driver unavailable | Driver offline or on trip |
| `trip_request_driver_403` | Already assigned | Another driver accepted |
| `otp_mismatch_404` | OTP mismatch | Wrong OTP entered |

---

## Support & Contact

For integration support:
- Backend Team: backend@yourcompany.com
- API Issues: Include endpoint, payload, response
- Bug Reports: Include steps to reproduce

---

## Changelog

### Version 1.0 (Current)
- ✅ Scheduled ride booking (2 hours minimum)
- ✅ Driver accept/reject functionality
- ✅ Push notifications at scheduled time
- ✅ OTP verification
- ✅ Trip completion flow

### Upcoming Features
- 🔜 Recurring scheduled rides
- 🔜 Favorite drivers for scheduled rides
- 🔜 Price estimation for future dates
