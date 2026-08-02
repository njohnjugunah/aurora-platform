# FullCalendar Integration - Phase 3 Guide

## Overview

Phase 3 introduces advanced appointment scheduling with FullCalendar.js, enabling:
- Customer-facing booking calendar
- Admin staff calendar management
- Real-time slot availability
- Automatic slot locking during payment
- Responsive design for all devices

---

## Architecture

### Backend Components

#### SlotManager (`includes/booking/SlotManager.php`)
Manages appointment slot availability, locking, and confirmation.

**Key Methods:**
- `getAvailableSlots()` - Get slots for service/date
- `getAvailableDates()` - Get dates with availability
- `lockSlot()` - Lock slot during payment (10 min)
- `unlockSlot()` - Release slot on payment failure
- `confirmBooking()` - Confirm after payment
- `cleanupExpiredLocks()` - Release expired locks

#### API Endpoints

**GET** `/ajax/bookings/get-slots.php`
```
Parameters:
- service_id (required)
- date (required, YYYY-MM-DD)
- staff_id (optional)

Response:
{
    "success": true,
    "data": {
        "service_id": 1,
        "date": "2024-08-05",
        "slots": [
            {"date": "2024-08-05", "time": "08:00", "end_time": "09:00"},
            {"date": "2024-08-05", "time": "09:30", "end_time": "10:30"}
        ],
        "total_available": 12
    }
}
```

**POST** `/ajax/bookings/create-booking.php`
```json
{
    "service_id": 1,
    "date": "2024-08-05",
    "time": "09:00",
    "customer_id": null,
    "customer_name": "Jane Doe",
    "customer_email": "jane@example.com",
    "customer_phone": "254712345678",
    "staff_id": null,
    "notes": "First time"
}

Response:
{
    "success": true,
    "booking": {
        "id": 123,
        "service_id": 1,
        "lock_token": "LOCK_abc123_1722872400",
        "expires_at": "2024-08-05 14:40:00"
    }
}
```

### Database Schema

#### Business Hours
```sql
day_of_week: 0-6 (0=Sunday, 6=Saturday)
opening_time: TIME
closing_time: TIME
is_open: BOOLEAN
```

#### Holidays
```sql
holiday_date: DATE
holiday_name: VARCHAR
is_all_day: BOOLEAN
```

#### Break Times
```sql
day_of_week: 0-6
start_time: TIME
end_time: TIME
break_type: 'lunch', 'break', etc.
```

#### Service Availability
```sql
service_id: INT
duration_minutes: INT
max_concurrent: INT
requires_confirmation: BOOLEAN
buffer_before_minutes: INT
buffer_after_minutes: INT
```

#### Appointment Slots
```sql
service_id: INT
staff_id: INT (optional)
slot_date: DATE
start_time: TIME
end_time: TIME
is_available: BOOLEAN
booked_by: INT (customer ID)
booking_id: INT (booking ID)
locked_until: DATETIME
lock_token: VARCHAR
```

---

## Customer Booking Flow

### 1. Visit `/book-appointment.html`

**Form Fields:**
- Service selection (dropdown)
- Date selection (date picker)
- Time selection (available slots)
- Customer name, email, phone
- Special requests (optional)

### 2. Service Selection

- User selects service
- Price and duration loaded
- Summary updated

### 3. Date Selection

- User picks date
- System fetches available slots
- Slots displayed as buttons

### 4. Slot Selection

- User clicks time slot
- Selected slot highlighted
- Booking summary shown
- Submit button enabled

### 5. Form Submission

- Booking created with 10-min lock
- Slot locked for payment processing
- Automatically triggers M-Pesa payment
- On payment success: booking confirmed
- On payment failure: slot unlocked

---

## Admin Calendar Management

### Dashboard (`admin/calendar.html`)

**Features:**
- Week/day/month views
- Drag-to-reschedule
- Color-coded by status:
  - Yellow: Pending confirmation
  - Green: Confirmed
  - Red: Cancelled
- Real-time statistics
- Filter by status
- Booking details panel

**Views:**
- **Day View**: Hourly slots for detailed scheduling
- **Week View**: Full week overview with time slots
- **Month View**: Calendar month with event indicators

---

## Slot Management

### Availability Calculation

```
Available Slots = Business Hours 
                 - Break Times 
                 - Existing Bookings
                 - Buffer Times
```

### Slot Locking

1. When booking created:
   - Slot locked for 10 minutes
   - Lock token generated
   - Time saved in `locked_until`

2. During payment:
   - Slot remains locked
   - No other customers can book
   - Payment timer runs in parallel

3. On payment success:
   - Lock converted to booking
   - `booking_id` set
   - Confirmation sent

4. On payment failure:
   - Lock automatically released
   - Slot becomes available
   - Cleanup runs every 5 minutes

### Concurrent Bookings

Handled by:
- `max_concurrent` setting per service
- Transaction-level isolation
- Unique constraint on slot+date+time

---

## Configuration

### Business Hours

Default (inserted via migration):
```
Monday-Saturday: 8:00 AM - 8:00 PM
Sunday: 10:00 AM - 5:00 PM (optional)
```

Update via admin panel:
```sql
UPDATE business_hours 
SET opening_time = '09:00', closing_time = '21:00'
WHERE day_of_week = 1;
```

### Break Times

Default: 1 PM - 2 PM lunch break all days

Add custom break:
```sql
INSERT INTO break_times (day_of_week, start_time, end_time, break_type)
VALUES (5, '15:00', '15:30', 'tea-break');
```

### Service Duration

Set duration in minutes:
```sql
UPDATE service_availability 
SET duration_minutes = 120
WHERE service_id = 1;
```

### Holidays

Add holiday:
```sql
INSERT INTO holidays (holiday_date, holiday_name, is_all_day)
VALUES ('2024-12-25', 'Christmas', TRUE);
```

---

## Frontend Integration

### FullCalendar Setup

```html
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.10/index.global.min.js"></script>

<script>
const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'timeGridWeek',
    plugins: ['dayGrid', 'timeGrid', 'interaction'],
    events: '/ajax/bookings/get-calendar-events.php',
    businessHours: {
        daysOfWeek: [1, 2, 3, 4, 5, 6],
        startTime: '08:00',
        endTime: '20:00'
    }
});
calendar.render();
</script>
```

### Booking Form Example

```javascript
// Get available slots
async function loadSlots(serviceId, date) {
    const response = await fetch(
        `/ajax/bookings/get-slots.php?service_id=${serviceId}&date=${date}`
    );
    const result = await response.json();
    return result.data.slots;
}

// Create booking
async function createBooking(data) {
    const response = await fetch('/ajax/bookings/create-booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return await response.json();
}
```

---

## Testing

### Test Scenarios

1. **Happy Path**
   - Select service, date, time
   - Complete booking and payment
   - Verify booking confirmation

2. **Slot Locking**
   - Create 2 bookings simultaneously
   - Verify only one succeeds
   - Other gets "slot unavailable"

3. **Payment Cancellation**
   - Create booking
   - Cancel payment
   - Verify slot becomes available

4. **Slot Expiry**
   - Create booking
   - Wait 10+ minutes
   - Verify slot auto-releases

5. **Business Hours**
   - Try booking before 8 AM
   - Verify no slots available
   - Try booking after 8 PM
   - Verify no slots available

6. **Holidays**
   - Add holiday
   - Try booking on holiday
   - Verify no slots available

7. **Break Times**
   - Add break 1-2 PM
   - Try booking 1:30 PM
   - Verify no slots in break time

---

## Performance Optimization

### Database Indexing

```sql
CREATE INDEX idx_service_date ON appointment_slots(service_id, slot_date);
CREATE INDEX idx_availability ON appointment_slots(is_available, slot_date);
CREATE INDEX idx_locked ON appointment_slots(locked_until);
```

### Caching

Recommended caching strategy:
- Cache business hours (low change frequency)
- Cache service duration (low change frequency)
- DO NOT cache slot availability (changes frequently)

### Query Optimization

Avoid:
- Generating all slots upfront
- Loading all bookings for month
- N+1 queries on related data

Use:
- On-demand slot generation
- Date-range queries only
- JOIN operations for related data

---

## Admin Features (Phase 3+)

### Planned Features

- **Drag-to-Reschedule**: Drag booking to new slot
- **Bulk Actions**: Confirm/cancel multiple bookings
- **Staff Assignment**: Assign stylist to booking
- **Payment Integration**: View/refund payments
- **Email Notifications**: Send reminders
- **Calendar Sync**: Google Calendar integration
- **Reports**: Revenue, utilization, no-shows

### Staff Management

Each staff member can have:
- Custom schedule
- Service specialization
- Maximum concurrent bookings
- Days off

---

## Troubleshooting

### Issue: Slots showing as available but not in calendar

**Solution:** 
- Verify business hours are set
- Check holiday table for date
- Verify service duration configured
- Check `appointment_slots` table

### Issue: Booking locks expiring too quickly

**Solution:**
- Increase `$this->lockDurationMinutes` in SlotManager
- Adjust M-Pesa payment timeout
- Monitor lock cleanup job

### Issue: Concurrent booking race condition

**Solution:**
- Use database transactions
- Add UNIQUE constraint on slot
- Implement optimistic locking
- Check max_concurrent setting

---

## Deployment Checklist

- [ ] Run database migrations
- [ ] Configure business hours
- [ ] Set service durations
- [ ] Add holidays/breaks
- [ ] Test customer booking flow
- [ ] Test admin calendar
- [ ] Set up lock cleanup cron job
- [ ] Configure email notifications
- [ ] Test M-Pesa payment integration
- [ ] Monitor for race conditions

---

**Phase 3 Implementation Status:** COMPLETE  
**Next Phase:** E-Commerce Enhancement (Phase 4)

