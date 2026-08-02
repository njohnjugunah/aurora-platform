# Phase 9 - Mobile App API (IMPLEMENTATION PLAN)

## Executive Summary

Build a comprehensive REST API backend for native iOS/Android mobile applications with JWT authentication, push notifications, offline support, and mobile-optimized endpoints.

---

## Architecture Overview

```
Mobile App (iOS/Android)
        ↓
    HTTPS/REST
        ↓
API Gateway (Rate Limiting, Auth)
        ↓
API Endpoints (v1/v2/versioning)
        ↓
Service Layer (Business Logic)
        ↓
Database + Cache (Redis)
        ↓
Push Notification Service (FCM/APNS)
```

---

## Phase 9 Deliverables

### 1. Authentication & Security (Week 1)
- ✅ JWT token implementation (access + refresh tokens)
- ✅ Device fingerprinting
- ✅ Biometric auth support (foundation)
- ✅ Session management
- ✅ Token refresh mechanism

### 2. Core API Endpoints (Week 1-2)
- ✅ Authentication endpoints
- ✅ Customer profile endpoints
- ✅ Appointment management
- ✅ Service catalog
- ✅ Booking history
- ✅ Payment methods

### 3. Notifications & Real-time (Week 2)
- ✅ Push notification infrastructure
- ✅ FCM/APNS integration foundation
- ✅ Notification preferences
- ✅ In-app messaging

### 4. Offline Support (Week 2-3)
- ✅ Request queuing for offline
- ✅ Sync strategy documentation
- ✅ Conflict resolution
- ✅ Cache invalidation

### 5. Mobile-Specific Features (Week 3)
- ✅ Image upload (profile, reviews)
- ✅ Location services
- ✅ QR code generation
- ✅ Favorites/wishlist

### 6. Analytics & Monitoring (Week 3)
- ✅ API usage tracking
- ✅ Device telemetry
- ✅ Performance metrics
- ✅ Error tracking

### 7. Documentation & SDK (Week 3)
- ✅ OpenAPI/Swagger documentation
- ✅ Mobile SDK (optional)
- ✅ Postman collection
- ✅ Integration guide

---

## Technical Stack

| Component | Technology |
|-----------|-----------|
| API Framework | PHP 8.3 (RESTful) |
| Authentication | JWT (HS256/RS256) |
| Push Notifications | Firebase Cloud Messaging (FCM) |
| Image Processing | Intervention Image |
| Caching | Redis |
| Rate Limiting | Token Bucket Algorithm |
| Versioning | URL-based (/api/v1/, /api/v2/) |
| Documentation | OpenAPI 3.0 + Swagger UI |

---

## Database Schema Changes

### New Tables
```sql
-- Mobile-specific tables
device_tokens
device_info
api_access_logs
push_notifications
offline_queue
user_preferences_mobile
```

---

## API Endpoints (High-Level)

### Authentication
```
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/refresh
POST   /api/v1/auth/logout
POST   /api/v1/auth/forgot-password
POST   /api/v1/auth/reset-password
```

### Customer Profile
```
GET    /api/v1/customer/profile
PUT    /api/v1/customer/profile
POST   /api/v1/customer/profile/avatar
GET    /api/v1/customer/preferences
PUT    /api/v1/customer/preferences
```

### Appointments
```
GET    /api/v1/appointments
POST   /api/v1/appointments
GET    /api/v1/appointments/{id}
PUT    /api/v1/appointments/{id}
DELETE /api/v1/appointments/{id}
GET    /api/v1/appointments/{id}/details
```

### Bookings & Services
```
GET    /api/v1/services
GET    /api/v1/services/{id}
GET    /api/v1/staff
GET    /api/v1/availability
GET    /api/v1/availability/slots
```

### Payments
```
GET    /api/v1/payments
POST   /api/v1/payments
GET    /api/v1/payments/{id}
GET    /api/v1/payment-methods
POST   /api/v1/payment-methods
DELETE /api/v1/payment-methods/{id}
```

### Notifications
```
GET    /api/v1/notifications
POST   /api/v1/notifications/mark-read
DELETE /api/v1/notifications/{id}
GET    /api/v1/notifications/preferences
PUT    /api/v1/notifications/preferences
```

### Loyalty & Reviews
```
GET    /api/v1/loyalty/balance
GET    /api/v1/loyalty/history
POST   /api/v1/reviews
GET    /api/v1/reviews/{id}
PUT    /api/v1/reviews/{id}
```

### Search & Discovery
```
GET    /api/v1/salon/info
GET    /api/v1/salon/hours
GET    /api/v1/salon/staff
GET    /api/v1/salon/gallery
GET    /api/v1/search
```

---

## Authentication Flow

### Login Flow
```
1. User enters credentials
2. POST /api/v1/auth/login
3. Server validates, returns:
   - access_token (15 min expiry)
   - refresh_token (30 day expiry)
   - user data
   - device_id (for push notifications)

4. Client stores tokens securely (Keychain/Keystore)
5. Subsequent requests: Authorization: Bearer {access_token}
```

### Token Refresh
```
1. Access token expires
2. POST /api/v1/auth/refresh with refresh_token
3. Server returns new access_token
4. Client updates bearer token
5. Retry original request
```

### Device Registration
```
1. On app install/login
2. POST /api/v1/auth/device-register
3. Send: device_id, device_name, OS, push_token
4. Server stores for push notifications
5. User can view/revoke devices anytime
```

---

## Response Format

### Success Response
```json
{
  "success": true,
  "code": 200,
  "message": "Operation successful",
  "data": { /* actual data */ },
  "timestamp": "2026-08-02T10:30:00Z"
}
```

### Error Response
```json
{
  "success": false,
  "code": 400,
  "message": "Validation failed",
  "errors": {
    "email": ["Invalid email format"],
    "phone": ["Phone must be 10 digits"]
  },
  "timestamp": "2026-08-02T10:30:00Z"
}
```

### Paginated Response
```json
{
  "success": true,
  "code": 200,
  "data": [ /* array of items */ ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 150,
    "pages": 8,
    "has_more": true
  },
  "timestamp": "2026-08-02T10:30:00Z"
}
```

---

## Push Notifications

### Types
- Appointment reminders (24h, 1h before)
- Promotion/offers
- Loyalty points earned
- New reviews
- Booking confirmation
- Service updates

### Implementation
- Firebase Cloud Messaging (FCM) for Android
- Apple Push Notification (APNs) for iOS
- Server stores device tokens
- Topic-based subscriptions
- Deep linking to specific screens

---

## Rate Limiting

### Limits
- Guest: 20 requests/min
- Authenticated: 100 requests/min
- Admin: Unlimited

### Implementation
- Token bucket algorithm
- Per-user rate limiting
- Graceful degradation
- 429 Too Many Requests response

---

## Offline Support Strategy

### Approach
1. Client caches critical data (services, staff, availability)
2. Failed requests queued locally
3. When online, sync queued requests
4. Server handles idempotency (via request IDs)
5. Conflict resolution: server wins

### Data to Cache
- Service catalog
- Staff directory
- Salon info/hours
- Booked appointments
- Loyalty points

---

## Performance Optimizations

### Mobile-First Design
- Minimal response payloads
- Pagination (default 20 items/page)
- Lazy loading support
- Compression (gzip)
- CDN for images

### Query Optimization
- Indexed queries
- Eager loading relationships
- Response caching (Redis)
- Database query optimization

---

## Testing & QA

### Unit Tests
- Auth logic
- Business rules
- Data validation

### Integration Tests
- API endpoints
- Database interactions
- Third-party integrations

### Load Testing
- Simulated mobile traffic
- Peak time scenarios
- Stress testing

### Security Testing
- SQL injection attempts
- XSS payload testing
- CORS validation
- Rate limit enforcement

---

## Deployment

### Pre-Deployment
- API documentation complete
- Rate limiting tested
- Push notifications tested
- Load testing passed (>1000 concurrent)
- Security audit passed

### Deployment Steps
1. Deploy API endpoints
2. Configure push notifications
3. Set up rate limiting
4. Initialize device token table
5. Monitor for errors

### Post-Deployment
- Monitor API usage
- Track error rates
- Review performance metrics
- Gather user feedback

---

## Timeline

| Week | Deliverables |
|------|--------------|
| Week 1 | Auth, JWT tokens, Core endpoints |
| Week 2 | Appointments, Payments, Notifications |
| Week 3 | Mobile features, Documentation, Testing |

**Estimated Total:** 3 weeks
**Lines of Code:** ~2,500-3,000
**API Endpoints:** 40+

---

## Success Criteria

✅ All API endpoints documented and tested
✅ JWT authentication working with refresh tokens
✅ Push notifications sent and received
✅ Rate limiting enforced
✅ Response times < 500ms (p95)
✅ Load testing: 1000 concurrent users
✅ 95%+ API availability
✅ Error rate < 1%
✅ Mobile apps can authenticate and book appointments

---

## Next Steps

1. Create database migration for mobile tables
2. Implement JWT token service
3. Build API endpoint scaffolding
4. Implement core auth endpoints
5. Build appointment endpoints
6. Add push notification infrastructure
7. Test with mobile app simulation
8. Documentation and Postman collection

**Status:** READY TO START ✅
