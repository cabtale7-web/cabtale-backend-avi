# Driver API Documentation - Scheduled Rides

## Overview
This document contains the driver-side API endpoints for handling scheduled ride requests, including accepting and rejecting rides.

---

## Base URL
```
https://your-domain.com/api
```

---

## Authentication
All endpoints require Bearer token authentication:
```
Authorization: Bearer {driver_access_token}
```

---

## 1. Get Pending Ride Requests (Including Scheduled)

### Endpoint
```http
GET /api/driver/trip/pending-list
```

### Headers
```json
{
  "Authorization": "Bearer {driver_access_token}",
  "zoneId": "zone-uuid-here"
}
```

### Query Parameters
```
?limit=10&offset=1
```

### Success Response (200 OK)
```json
{
  "response_code": "default_200",
  "message": "Success",
  "total_size": 5,
  "limit": 10,
  "offset": 1,
  "data": [
    {
      "id": "trip-uuid-1",
      "ref_id": "100001",
      "customer": {
        "id": "customer-uuid",
        "first_name": "John",
        "last_name": "Doe",
        "phone": "+919876543210",
        "profile_image": "https://...",
        "avg_rating": 4.5
      },
      "estimated_fare": 250.00,
      "estimated_distance": 15.2,
      "estimated_time": "25.5 min",
      "current_status": "pending",
      "scheduled_at": "2025-07-20 14:00:00",
      "type": "ride_request",
      "pickup_coordinates": "[28.6139, 77.2090]",
      "pickup_address": "Connaught Place, New Delhi",
      "destination_coordinates": "[28.7041, 77.1025]",
      "destination_address": "Rajiv Chowk, Delhi",
      "note": "Please come to gate 2",
      "created_at": "2025-01-15T10:30:00.000000Z"
    },
    {
      "id": "trip-uuid-2",
      "ref_id": "100002",
      "customer": {
        "id": "customer-uuid-2",
        "first_name": "Jane",
        "last_name": "Smith",
        "phone": "+919876543211"
      },
      "estimated_fare": 180.00,
      "current_status": "pending",
      "scheduled_at": null,
      "type": "ride_request",
      "created_at": "2025-01-15T11:00:00.000000Z"
    }
  ],
  "errors": []
}
```

### Notes
- `scheduled_at` will be `null` for immediate rides
- `scheduled_at` will have datetime for scheduled rides
- Scheduled rides appear in the list when their scheduled time arrives
- Drivers within the search radius receive notifications at the scheduled time

---

## 2. Get Ride Details (Before Accepting)

### Endpoint
```http
GET /api/driver/trip/{trip_id}?type=overview
```

### Headers
```json
{
  "Authorization": "Bearer {driver_access_token}"
}
```

### Success Response (200 OK)
```json
{
  "response_code": "default_200",
  "message": "Success",
  "data": {
    "id": "trip-uuid-here",
    "ref_id": "100001",
    "customer": {
      "id": "customer-uuid",
      "first_name": "John",
      "last_name": "Doe",
      "phone": "+919876543210",
      "profile_image": "https://...",
      "avg_rating": 4.5
    },
    "estimated_fare": 250.00,
    "estimated_distance": 15.2,
    "estimated_time": "25.5 min",
    "current_status": "pending",
    "scheduled_at": "2025-07-20 14:00:00",
    "type": "ride_request",
    "pickup_coordinates": "[28.6139, 77.2090]",
    "pickup_address": "Connaught Place, New Delhi",
    "destination_coordinates": "[28.7041, 77.1025]",
    "destination_address": "Rajiv Chowk, Delhi",
    "intermediate_coordinates": "[[28.6500, 77.2200]]",
    "intermediate_addresses": "[\"India Gate, Delhi\"]",
    "note": "Please come to gate 2",
    "created_at": "2025-01-15T10:30:00.000000Z"
  }
}
```

---

## 3. Accept Ride Request (ACCEPT)

### Endpoint
```http
POST /api/driver/trip/request-action
```

### Headers
```json
{
  "Authorization": "Bearer {driver_access_token}",
  "Content-Type": "application/json"
}
```

### Request Payload
```json
{
  "trip_request_id": "trip-uuid-here",
  "action": "accepted"
}
```

### Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `trip_request_id` | String (UUID) | Yes | Trip request ID to accept |
| `action` | String | Yes | Must be `"accepted"` |

### Success Response (200 OK)
```json
{
  "response_code": "default_update_200",
  "message": "Trip accepted successfully",
  "data": null,
  "errors": []
}
```

### What Happens After Accept
1. Trip status changes to `accepted`
2. Driver's availability status changes to `on_trip`
3. Customer receives notification: "Driver assigned"
4. OTP is generated for trip verification
5. Other drivers can no longer see this trip
6. Driver can now see customer details and navigate to pickup

### Error Responses

#### Driver Already on Trip (403)
```json
{
  "response_code": "driver_unavailable_403",
  "message": "Driver is unavailable",
  "errors": []
}
```

#### Trip Already Accepted by Another Driver (403)
```json
{
  "response_code": "trip_request_driver_403",
  "message": "Trip already assigned to another driver",
  "errors": []
}
```

#### Trip Cancelled (403)
```json
{
  "response_code": "driver_request_accept_timeout_408",
  "message": "Trip request has been cancelled",
  "errors": []
}
```

---

## 4. Reject Ride Request (REJECT)

### Endpoint
```http
POST /api/driver/trip/request-action
```

### Headers
```json
{
  "Authorization": "Bearer {driver_access_token}",
  "Content-Type": "application/json"
}
```

### Request Payload
```json
{
  "trip_request_id": "trip-uuid-here",
  "action": "rejected"
}
```

### Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `trip_request_id` | String (UUID) | Yes | Trip request ID to reject |
| `action` | String | Yes | Must be `"rejected"` |

### Success Response (200 OK)
```json
{
  "response_code": "default_update_200",
  "message": "Request processed successfully",
  "data": null,
  "errors": []
}
```

### What Happens After Reject
1. Driver is added to rejected drivers list for this trip
2. This trip will no longer appear in driver's pending list
3. Trip remains available for other drivers
4. No notification sent to customer

---

## 5. Ignore Trip Notification (Silent Dismiss)

### Endpoint
```http
POST /api/driver/trip/ignore-notification
```

### Headers
```json
{
  "Authorization": "Bearer {driver_access_token}",
  "Content-Type": "application/json"
}
```

### Request Payload
```json
{
  "trip_request_id": "trip-uuid-here"
}
```

### Success Response (200 OK)
```json
{
  "response_code": "default_200",
  "message": "Success",
  "data": null,
  "errors": []
}
```

### Notes
- Use this when driver dismisses the notification without accepting/rejecting
- Trip will no longer appear in driver's pending list
- Trip remains available for other drivers

---

## 6. Get Active/Ongoing Ride

### Endpoint
```http
GET /api/driver/trip/resume-status
```

### Headers
```json
{
  "Authorization": "Bearer {driver_access_token}"
}
```

### Success Response (200 OK)
```json
{
  "response_code": "default_200",
  "message": "Success",
  "data": {
    "id": "trip-uuid-here",
    "ref_id": "100001",
    "customer": {
      "id": "customer-uuid",
      "first_name": "John",
      "last_name": "Doe",
      "phone": "+919876543210"
    },
    "estimated_fare": 250.00,
    "actual_fare": null,
    "current_status": "accepted",
    "scheduled_at": "2025-07-20 14:00:00",
    "otp": "1234",
    "pickup_address": "Connaught Place, New Delhi",
    "destination_address": "Rajiv Chowk, Delhi",
    "created_at": "2025-01-15T10:30:00.000000Z"
  }
}
```

### Error Response (404)
```json
{
  "response_code": "default_404",
  "message": "No active ride found",
  "errors": []
}
```

---

## 7. Match OTP (Start Trip)

### Endpoint
```http
POST /api/driver/trip/match-otp
```

### Headers
```json
{
  "Authorization": "Bearer {driver_access_token}",
  "Content-Type": "application/json"
}
```

### Request Payload
```json
{
  "trip_request_id": "trip-uuid-here",
  "otp": "1234"
}
```

### Success Response (200 OK)
```json
{
  "response_code": "default_store_200",
  "message": "OTP matched successfully",
  "data": null,
  "errors": []
}
```

### Error Response - OTP Mismatch (403)
```json
{
  "response_code": "otp_mismatch_404",
  "message": "OTP does not match",
  "errors": []
}
```

### What Happens After OTP Match
1. Trip status changes to `ongoing`
2. Customer receives notification: "Trip started"
3. Timer starts for trip duration
4. Driver can now navigate to destination

---

## 8. Update Trip Status (Complete/Cancel)

### Endpoint
```http
POST /api/driver/trip/status-update
```

### Headers
```json
{
  "Authorization": "Bearer {driver_access_token}",
  "Content-Type": "application/json"
}
```

### Request Payload (Complete Trip)
```json
{
  "trip_request_id": "trip-uuid-here",
  "status": "completed"
}
```

### Request Payload (Cancel Trip)
```json
{
  "trip_request_id": "trip-uuid-here",
  "status": "cancelled",
  "trip_cancellation_reason": "Customer didn't arrive at pickup point"
}
```

### Success Response (200 OK)
```json
{
  "response_code": "default_update_200",
  "message": "Trip status updated successfully",
  "data": {
    "id": "trip-uuid-here",
    "current_status": "completed",
    "actual_fare": 280.50,
    "paid_fare": 280.50
  }
}
```

---

## 9. Get Driver's Ride List

### Endpoint
```http
GET /api/driver/trip/list
```

### Headers
```json
{
  "Authorization": "Bearer {driver_access_token}"
}
```

### Query Parameters
```
?limit=10&offset=1&status=completed&filter=today
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `limit` | Integer | Yes | Number of records per page |
| `offset` | Integer | Yes | Page number |
| `status` | String | No | Filter: `pending`, `accepted`, `ongoing`, `completed`, `cancelled` |
| `filter` | String | No | Date filter: `today`, `this_week`, `this_month` |

### Success Response (200 OK)
```json
{
  "response_code": "default_200",
  "message": "Success",
  "total_size": 50,
  "limit": 10,
  "offset": 1,
  "data": [
    {
      "id": "trip-uuid-1",
      "ref_id": "100001",
      "customer": {
        "first_name": "John",
        "last_name": "Doe"
      },
      "estimated_fare": 250.00,
      "actual_fare": 280.50,
      "paid_fare": 280.50,
      "current_status": "completed",
      "scheduled_at": "2025-07-20 14:00:00",
      "type": "ride_request",
      "created_at": "2025-01-15T10:30:00.000000Z"
    }
  ]
}
```

---

## 10. Bid on Ride (If Bidding Enabled)

### Endpoint
```http
POST /api/driver/trip/bid
```

### Headers
```json
{
  "Authorization": "Bearer {driver_access_token}",
  "Content-Type": "application/json"
}
```

### Request Payload
```json
{
  "trip_request_id": "trip-uuid-here",
  "bid_fare": 220.00
}
```

### Success Response (200 OK)
```json
{
  "response_code": "bidding_action_200",
  "message": "Bid submitted successfully",
  "data": null,
  "errors": []
}
```

### Notes
- Only available if `bid_on_fare` is enabled in business settings
- Driver can bid lower than estimated fare
- Customer sees all bids and can choose

---

## Push Notifications for Drivers

### 1. New Ride Request (Immediate)
```json
{
  "title": "New Ride Request",
  "description": "You have a new ride request nearby",
  "ride_request_id": "trip-uuid-here",
  "type": "ride_request",
  "action": "new_ride_request_notification",
  "data": {
    "trip_id": "trip-uuid-here",
    "estimated_fare": 250.00,
    "pickup_address": "Connaught Place, New Delhi",
    "scheduled_at": null
  }
}
```

### 2. New Scheduled Ride (At Scheduled Time)
```json
{
  "title": "Scheduled Ride Request",
  "description": "A scheduled ride is now available",
  "ride_request_id": "trip-uuid-here",
  "type": "ride_request",
  "action": "new_ride_request_notification",
  "data": {
    "trip_id": "trip-uuid-here",
    "estimated_fare": 250.00,
    "pickup_address": "Connaught Place, New Delhi",
    "scheduled_at": "2025-07-20 14:00:00",
    "is_scheduled": true
  }
}
```

### 3. Trip Cancelled by Customer
```json
{
  "title": "Trip Cancelled",
  "description": "Customer has cancelled the trip",
  "ride_request_id": "trip-uuid-here",
  "type": "ride_request",
  "action": "ride_cancelled"
}
```

---

## Frontend Implementation Guide for Drivers

### 1. Handling Scheduled Rides in UI

```javascript
// Check if ride is scheduled
const isScheduledRide = (trip) => {
  return trip.scheduled_at !== null;
};

// Display scheduled badge
const renderTripCard = (trip) => {
  return (
    <div className="trip-card">
      {isScheduledRide(trip) && (
        <span className="badge scheduled">
          Scheduled: {formatDateTime(trip.scheduled_at)}
        </span>
      )}
      <div className="trip-details">
        <p>Pickup: {trip.pickup_address}</p>
        <p>Fare: ₹{trip.estimated_fare}</p>
      </div>
      <div className="actions">
        <button onClick={() => acceptRide(trip.id)}>Accept</button>
        <button onClick={() => rejectRide(trip.id)}>Reject</button>
      </div>
    </div>
  );
};
```

### 2. Accept Ride API Call

```javascript
const acceptRide = async (tripId) => {
  try {
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
      console.log('Ride accepted:', data);
      // Navigate to trip details screen
      navigateToTripDetails(tripId);
    } else {
      console.error('Accept failed:', data.errors);
      showError(data.message);
    }
  } catch (error) {
    console.error('Network error:', error);
  }
};
```

### 3. Reject Ride API Call

```javascript
const rejectRide = async (tripId) => {
  try {
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

    const data = await response.json();
    
    if (response.ok) {
      console.log('Ride rejected:', data);
      // Remove from pending list
      removeTripFromList(tripId);
    } else {
      console.error('Reject failed:', data.errors);
    }
  } catch (error) {
    console.error('Network error:', error);
  }
};
```

### 4. Handle Push Notifications

```javascript
// When push notification received
const handlePushNotification = (notification) => {
  const { action, ride_request_id, data } = notification;
  
  if (action === 'new_ride_request_notification') {
    // Show notification popup
    if (data.is_scheduled) {
      showNotification({
        title: 'Scheduled Ride Available',
        message: `Pickup: ${data.pickup_address}`,
        tripId: ride_request_id
      });
    } else {
      showNotification({
        title: 'New Ride Request',
        message: `Pickup: ${data.pickup_address}`,
        tripId: ride_request_id
      });
    }
    
    // Refresh pending rides list
    fetchPendingRides();
  }
  
  if (action === 'ride_cancelled') {
    // Remove from list and show message
    removeTripFromList(ride_request_id);
    showToast('Trip was cancelled by customer');
  }
};
```

### 5. Polling for Pending Rides

```javascript
// Poll every 10 seconds when driver is online
const startPolling = () => {
  const pollInterval = setInterval(async () => {
    if (driverStatus === 'online') {
      await fetchPendingRides();
    }
  }, 10000); // 10 seconds
  
  return pollInterval;
};

const fetchPendingRides = async () => {
  try {
    const response = await fetch(
      'https://your-domain.com/api/driver/trip/pending-list?limit=20&offset=1',
      {
        headers: {
          'Authorization': `Bearer ${driverToken}`,
          'zoneId': currentZoneId
        }
      }
    );
    
    const data = await response.json();
    
    if (response.ok) {
      updatePendingRidesList(data.data);
    }
  } catch (error) {
    console.error('Failed to fetch pending rides:', error);
  }
};
```

---

## Driver Status Flow

### For Scheduled Rides
```
Driver Online → Receives notification at scheduled time → 
Sees ride in pending list → Accepts → On Trip → 
Matches OTP → Ongoing → Completes → Available
```

### For Immediate Rides
```
Driver Online → Receives notification immediately → 
Sees ride in pending list → Accepts → On Trip → 
Matches OTP → Ongoing → Completes → Available
```

---

## Important Notes for Drivers

1. **Scheduled Rides Timing**
   - Drivers receive notifications at the scheduled time, not when booking is created
   - Scheduled rides appear in pending list only at scheduled time
   - Multiple drivers can see the same scheduled ride

2. **Accept/Reject Behavior**
   - Only one driver can accept a ride
   - Once accepted, ride disappears from other drivers' lists
   - Rejected rides won't appear again for that driver
   - Driver must be `online` and `available` to accept

3. **Driver Availability Status**
   - `available` - Can accept new rides
   - `on_trip` - Currently on a ride, cannot accept new rides
   - `unavailable` - Offline, won't receive notifications
   - `on_bidding` - Submitted bid, waiting for customer decision

4. **OTP Verification**
   - OTP is generated when driver accepts
   - Customer shows OTP to driver at pickup
   - Driver enters OTP to start trip
   - Trip cannot start without OTP match

5. **Cancellation**
   - Driver can cancel before OTP match (status: `accepted`)
   - Driver can cancel after trip starts (status: `ongoing`)
   - Cancellation reasons are required
   - Cancellation may affect driver rating

---

## Error Codes Reference

| Code | Message | Description |
|------|---------|-------------|
| `default_200` | Success | Request successful |
| `default_update_200` | Updated successfully | Status updated |
| `driver_unavailable_403` | Driver unavailable | Driver is offline or on trip |
| `trip_request_404` | Trip not found | Invalid trip ID |
| `trip_request_driver_403` | Already assigned | Another driver accepted |
| `otp_mismatch_404` | OTP mismatch | Wrong OTP entered |
| `bidding_action_200` | Bid submitted | Bid placed successfully |

---

## Testing Checklist for Drivers

- [ ] Receive notification for scheduled ride at scheduled time
- [ ] See scheduled rides in pending list
- [ ] Accept scheduled ride
- [ ] Reject scheduled ride
- [ ] Accept immediate ride
- [ ] Reject immediate ride
- [ ] Try accepting already assigned ride (should fail)
- [ ] Try accepting while on another trip (should fail)
- [ ] Match OTP and start trip
- [ ] Complete trip
- [ ] Cancel trip with reason
- [ ] View ride history with scheduled rides

---

## Support

For any issues or questions, contact the backend team with:
- API endpoint used
- Request payload
- Response received
- Driver ID and trip ID
- Error message (if any)
