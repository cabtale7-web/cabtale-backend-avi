# Scheduled Ride API Documentation

## Overview
This document contains the API endpoints and payloads for implementing the **scheduled ride booking** feature (book a cab 2 hours before travel).

---

## Base URL
```
https://your-domain.com/api
```

---

## Authentication
All endpoints require Bearer token authentication:
```
Authorization: Bearer {access_token}
```

---

## 1. Create Ride Request (Scheduled or Immediate)

### Endpoint
```http
POST /api/customer/trip/store
```

### Headers
```json
{
  "Authorization": "Bearer {access_token}",
  "Content-Type": "application/json",
  "zoneId": "zone-uuid-here"
}
```

### Request Payload (Immediate Ride)
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
  "vehicle_category_id": "category-uuid-here",
  "type": "ride_request",
  "note": "Please come to gate 2",
  "intermediate_coordinates": "[[28.6500, 77.2200]]",
  "intermediate_addresses": "[\"India Gate, Delhi\"]"
}
```

### Request Payload (Scheduled Ride - NEW)
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
  "vehicle_category_id": "category-uuid-here",
  "type": "ride_request",
  "note": "Please come to gate 2",
  "scheduled_at": "2025-07-20 14:00:00"
}
```

### Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `pickup_coordinates` | String (JSON Array) | Yes | Pickup location `[latitude, longitude]` |
| `destination_coordinates` | String (JSON Array) | Yes | Destination location `[latitude, longitude]` |
| `customer_coordinates` | String (JSON Array) | Yes | Customer's current location `[latitude, longitude]` |
| `pickup_address` | String | Yes | Human-readable pickup address |
| `destination_address` | String | Yes | Human-readable destination address |
| `customer_request_coordinates` | String (JSON Array) | Yes | Same as customer_coordinates |
| `estimated_time` | String | Yes | Estimated trip time in minutes |
| `estimated_distance` | String | Yes | Estimated distance in kilometers |
| `estimated_fare` | String | Yes | Estimated fare amount |
| `vehicle_category_id` | String (UUID) | Yes | Vehicle category ID |
| `type` | String | Yes | `ride_request` or `parcel` |
| `note` | String | No | Additional instructions for driver |
| `intermediate_coordinates` | String (JSON Array) | No | Waypoints `[[lat1, lng1], [lat2, lng2]]` |
| `intermediate_addresses` | String (JSON Array) | No | Waypoint addresses |
| `scheduled_at` | String (DateTime) | No | **NEW** - Schedule time (must be at least 2 hours from now) |

### Success Response (201 Created)
```json
{
  "response_code": "trip_request_200",
  "message": "Trip request created successfully",
  "total_size": null,
  "limit": null,
  "offset": null,
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
    "current_status": "pending",
    "scheduled_at": "2025-07-20 14:00:00",
    "type": "ride_request",
    "created_at": "2025-01-15T10:30:00.000000Z"
  },
  "errors": []
}
```

### Error Response (403 Forbidden)
```json
{
  "response_code": "default_400",
  "message": "Validation failed",
  "errors": [
    {
      "error_code": "scheduled_at",
      "message": "The scheduled at must be a date after 2025-01-15 12:30:00"
    }
  ]
}
```

### Validation Rules for `scheduled_at`
- Must be a valid datetime format: `YYYY-MM-DD HH:MM:SS`
- Must be at least **2 hours** from the current time
- Example: If current time is `2025-01-15 10:00:00`, minimum `scheduled_at` is `2025-01-15 12:00:00`

---

## 2. Get Estimated Fare (Before Booking)

### Endpoint
```http
POST /api/customer/trip/estimated-fare
```

### Headers
```json
{
  "Authorization": "Bearer {access_token}",
  "Content-Type": "application/json",
  "zoneId": "zone-uuid-here"
}
```

### Request Payload
```json
{
  "pickup_coordinates": "[28.6139, 77.2090]",
  "destination_coordinates": "[28.7041, 77.1025]",
  "pickup_address": "Connaught Place, New Delhi",
  "destination_address": "Rajiv Chowk, Delhi",
  "type": "ride_request",
  "intermediate_coordinates": "[[28.6500, 77.2200]]"
}
```

### Success Response (200 OK)
```json
{
  "response_code": "default_200",
  "message": "Success",
  "data": [
    {
      "vehicle_category_id": "category-uuid-1",
      "vehicle_category_name": "Economy",
      "vehicle_type": "car",
      "estimated_fare": 250.00,
      "estimated_distance": 15.2,
      "estimated_time": "25.5 min",
      "base_fare": 50.00,
      "per_km_fare": 10.00
    },
    {
      "vehicle_category_id": "category-uuid-2",
      "vehicle_category_name": "Premium",
      "vehicle_type": "car",
      "estimated_fare": 350.00,
      "estimated_distance": 15.2,
      "estimated_time": "25.5 min",
      "base_fare": 80.00,
      "per_km_fare": 15.00
    }
  ]
}
```

---

## 3. Get Ride Details

### Endpoint
```http
GET /api/customer/trip/{trip_id}
```

### Headers
```json
{
  "Authorization": "Bearer {access_token}"
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
      "last_name": "Doe"
    },
    "driver": null,
    "vehicle_category": {
      "id": "category-uuid",
      "name": "Economy",
      "type": "car"
    },
    "estimated_fare": 250.00,
    "actual_fare": null,
    "paid_fare": 0.00,
    "current_status": "pending",
    "scheduled_at": "2025-07-20 14:00:00",
    "type": "ride_request",
    "pickup_coordinates": "[28.6139, 77.2090]",
    "pickup_address": "Connaught Place, New Delhi",
    "destination_coordinates": "[28.7041, 77.1025]",
    "destination_address": "Rajiv Chowk, Delhi",
    "created_at": "2025-01-15T10:30:00.000000Z"
  }
}
```

---

## 4. Get Customer Ride List

### Endpoint
```http
GET /api/customer/trip/list
```

### Headers
```json
{
  "Authorization": "Bearer {access_token}"
}
```

### Query Parameters
```
?limit=10&offset=1&status=pending&filter=today
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `limit` | Integer | Yes | Number of records per page |
| `offset` | Integer | Yes | Page number |
| `status` | String | No | Filter by status: `pending`, `accepted`, `ongoing`, `completed`, `cancelled` |
| `filter` | String | No | Date filter: `today`, `this_week`, `this_month`, `custom_date` |
| `start` | String | No | Start date for custom filter (YYYY-MM-DD) |
| `end` | String | No | End date for custom filter (YYYY-MM-DD) |

### Success Response (200 OK)
```json
{
  "response_code": "default_200",
  "message": "Success",
  "total_size": 25,
  "limit": 10,
  "offset": 1,
  "data": [
    {
      "id": "trip-uuid-1",
      "ref_id": "100001",
      "estimated_fare": 250.00,
      "current_status": "pending",
      "scheduled_at": "2025-07-20 14:00:00",
      "type": "ride_request",
      "created_at": "2025-01-15T10:30:00.000000Z"
    },
    {
      "id": "trip-uuid-2",
      "ref_id": "100002",
      "estimated_fare": 180.00,
      "current_status": "completed",
      "scheduled_at": null,
      "type": "ride_request",
      "created_at": "2025-01-14T15:20:00.000000Z"
    }
  ]
}
```

---

## 5. Cancel Scheduled Ride

### Endpoint
```http
POST /api/customer/trip/status-update/{trip_id}
```

### Headers
```json
{
  "Authorization": "Bearer {access_token}",
  "Content-Type": "application/json"
}
```

### Request Payload
```json
{
  "status": "cancelled",
  "trip_cancellation_reason": "Plans changed"
}
```

### Success Response (200 OK)
```json
{
  "response_code": "default_update_200",
  "message": "Trip cancelled successfully",
  "data": {
    "id": "trip-uuid-here",
    "current_status": "cancelled",
    "trip_cancellation_reason": "Plans changed"
  }
}
```

---

## Frontend Implementation Guide

### 1. Date/Time Picker Component
```javascript
// Minimum allowed time = Current time + 2 hours
const minScheduleTime = new Date();
minScheduleTime.setHours(minScheduleTime.getHours() + 2);

// Format for API: YYYY-MM-DD HH:MM:SS
const formatDateTime = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  const seconds = String(date.getSeconds()).padStart(2, '0');
  
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
};
```

### 2. API Call Example (React/JavaScript)
```javascript
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
    vehicle_category_id: "category-uuid-here",
    type: "ride_request",
    note: "Please come to gate 2"
  };

  // Add scheduled_at only if scheduling
  if (isScheduled && scheduledDateTime) {
    payload.scheduled_at = formatDateTime(scheduledDateTime);
  }

  try {
    const response = await fetch('https://your-domain.com/api/customer/trip/store', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${accessToken}`,
        'Content-Type': 'application/json',
        'zoneId': 'zone-uuid-here'
      },
      body: JSON.stringify(payload)
    });

    const data = await response.json();
    
    if (response.ok) {
      console.log('Ride booked successfully:', data);
      // Show success message
    } else {
      console.error('Booking failed:', data.errors);
      // Show error message
    }
  } catch (error) {
    console.error('Network error:', error);
  }
};
```

### 3. UI Flow
1. **Ride Booking Screen**
   - Show toggle: "Book Now" vs "Schedule for Later"
   - If "Schedule for Later" is selected:
     - Show date/time picker
     - Validate minimum 2 hours from now
     - Display selected time clearly

2. **Confirmation Screen**
   - For scheduled rides, show: "Your ride is scheduled for [Date Time]"
   - For immediate rides, show: "Finding nearby drivers..."

3. **My Rides Screen**
   - Show badge for scheduled rides: "Scheduled"
   - Display scheduled time prominently
   - Allow cancellation before scheduled time

---

## Status Flow

### Immediate Ride
```
pending → accepted → ongoing → completed/cancelled
```

### Scheduled Ride
```
pending (scheduled) → [waits until scheduled_at] → pending → accepted → ongoing → completed/cancelled
```

---

## Important Notes

1. **Queue Worker**: Backend must have queue worker running:
   ```bash
   php artisan queue:work --queue=high,default
   ```

2. **Timezone**: All datetime values are in UTC. Frontend should convert to user's local timezone for display.

3. **Currency Symbol**: The API returns amounts as numbers. Currency symbol (₹) should be added by frontend based on business settings.

4. **Validation**: Always validate `scheduled_at` is at least 2 hours from current time before sending to API.

5. **Driver Assignment**: For scheduled rides, drivers are notified at the scheduled time, not when the booking is created.

---

## Error Codes Reference

| Code | Message | Description |
|------|---------|-------------|
| `trip_request_200` | Success | Trip created successfully |
| `incomplete_ride_403` | Incomplete ride exists | User has an active/incomplete ride |
| `zone_404` | Zone not found | Invalid or missing zone ID |
| `default_400` | Validation failed | Check errors array for details |
| `trip_request_404` | Trip not found | Invalid trip ID |

---

## Testing Checklist

- [ ] Book immediate ride (without `scheduled_at`)
- [ ] Book scheduled ride 2 hours from now
- [ ] Try booking scheduled ride less than 2 hours (should fail)
- [ ] View scheduled ride in ride list
- [ ] Cancel scheduled ride before scheduled time
- [ ] Verify driver gets notification at scheduled time
- [ ] Check timezone handling for different regions

---

## Support

For any issues or questions, contact the backend team with:
- API endpoint used
- Request payload
- Response received
- Error message (if any)
