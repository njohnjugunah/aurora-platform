# API_REFERENCE.md

**Aurora Platform - REST API Reference**

Version: 1.0.0  
Status: Complete Specification  
Last Updated: 2026-07-28  
Base URL: `https://api.glambymariga.local`

---

## TABLE OF CONTENTS

1. API Overview
2. Authentication
3. Error Handling
4. Pagination
5. Authentication Endpoints
6. Appointment Endpoints
7. Sale/POS Endpoints
8. Inventory Endpoints
9. Customer Endpoints
10. Staff Endpoints
11. Admin Endpoints
12. Report Endpoints
13. Webhooks (Future)
14. Rate Limiting

---

## 1. API OVERVIEW

### Base URL
```
Development:  http://localhost:8080/api
Production:   https://api.glambymariga.local
```

### API Version
- Current: v1
- Format: REST/JSON
- Protocol: HTTPS (production only)

### Response Format
All responses are JSON with consistent structure:

**Success Response**:
```json
{
  "data": { /* response payload */ },
  "meta": {
    "timestamp": "2026-07-28T10:30:00Z",
    "version": "1.0.0"
  }
}
```

**Error Response**:
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Request validation failed",
    "details": [
      {
        "field": "email",
        "message": "Invalid email format"
      }
    ]
  },
  "meta": {
    "timestamp": "2026-07-28T10:30:00Z",
    "version": "1.0.0"
  }
}
```

### Common Headers

**Request Headers**:
```
Content-Type: application/json
Authorization: Bearer {JWT_TOKEN}
X-Request-ID: {unique-request-id} (optional)
```

**Response Headers**:
```
Content-Type: application/json
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 99
X-RateLimit-Reset: 1690531200
```

---

## 2. AUTHENTICATION

### JWT Token Structure

**Token Format**:
```
Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.
  eyJ1c2VySWQiOjEsInJvbGVzIjpbIm93bmVyIl0sImV4cCI6MTY5MDUzMTIwMH0.
  signature_here
```

**Token Payload**:
```json
{
  "userId": 1,
  "email": "owner@glambymariga.local",
  "roles": ["owner"],
  "permissions": ["*"],
  "exp": 1690531200,
  "iat": 1690444800
}
```

### Token Lifecycle

- **Expiration**: 24 hours
- **Refresh**: Not supported (login again required)
- **Revocation**: Not supported (24-hour window for revocation)
- **Storage**: LocalStorage (frontend only, never backend cookie)

### Bearer Token Usage

```
GET /api/appointments
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## 3. ERROR HANDLING

### HTTP Status Codes

| Code | Meaning | When Used |
|------|---------|-----------|
| **200** | OK | Request successful |
| **201** | Created | Resource created successfully |
| **204** | No Content | Successful but no content returned |
| **400** | Bad Request | Invalid request format |
| **401** | Unauthorized | Missing/invalid authentication |
| **403** | Forbidden | Authenticated but insufficient permission |
| **404** | Not Found | Resource does not exist |
| **409** | Conflict | Resource already exists (duplicate) |
| **422** | Unprocessable Entity | Business logic validation failed |
| **429** | Too Many Requests | Rate limit exceeded |
| **500** | Internal Server Error | Unexpected server error |
| **503** | Service Unavailable | Database or critical service down |

### Error Codes

| Code | Meaning | HTTP Status |
|------|---------|-------------|
| `VALIDATION_ERROR` | Request validation failed | 400 |
| `UNAUTHORIZED` | Authentication required | 401 |
| `FORBIDDEN` | Permission denied | 403 |
| `NOT_FOUND` | Resource not found | 404 |
| `DUPLICATE_RESOURCE` | Resource already exists | 409 |
| `BUSINESS_RULE_VIOLATION` | Business rule failed | 422 |
| `RATE_LIMITED` | Too many requests | 429 |
| `SERVER_ERROR` | Unexpected error | 500 |
| `SERVICE_UNAVAILABLE` | Database/service down | 503 |

---

## 4. PAGINATION

### Query Parameters

```
GET /api/appointments?page=1&limit=50&sort=date&order=desc
```

| Parameter | Type | Default | Max | Description |
|-----------|------|---------|-----|-------------|
| **page** | integer | 1 | - | Page number (1-indexed) |
| **limit** | integer | 50 | 100 | Items per page |
| **sort** | string | id | - | Field to sort by |
| **order** | enum | asc | - | Sort order (asc/desc) |

### Pagination Response

```json
{
  "data": [ /* array of resources */ ],
  "pagination": {
    "page": 1,
    "limit": 50,
    "total": 234,
    "pages": 5,
    "hasMore": true
  },
  "meta": {
    "timestamp": "2026-07-28T10:30:00Z"
  }
}
```

---

## 5. AUTHENTICATION ENDPOINTS

### POST /api/login

**Description**: Authenticate user and receive JWT token

**Request**:
```json
{
  "email": "receptionist@glambymariga.local",
  "password": "SecurePassword123!"
}
```

**Response** (200 OK):
```json
{
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": 2,
      "email": "receptionist@glambymariga.local",
      "name": "Jane Smith",
      "roles": ["receptionist"],
      "permissions": [
        "appointments.create",
        "appointments.read",
        "sales.create",
        "sales.read",
        "customers.read"
      ]
    },
    "expiresIn": 86400
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Error** (401 Unauthorized):
```json
{
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Invalid email or password"
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Business Rules**:
- Password must be at least 8 characters
- Account must be active
- Max 5 failed login attempts (15-minute lockout)

---

### POST /api/logout

**Description**: Invalidate user session (client-side action mainly)

**Request**:
```
Authorization: Bearer {token}
```

**Response** (204 No Content):
```
[no body]
```

**Notes**: 
- Token is invalidated on server (optional audit trail)
- Frontend removes token from localStorage

---

## 6. APPOINTMENT ENDPOINTS

### GET /api/appointments

**Description**: List all appointments with filters

**Query Parameters**:
```
GET /api/appointments?page=1&limit=50&date=2026-08-01&staff_id=3&status=confirmed
```

| Parameter | Type | Description |
|-----------|------|-------------|
| **date** | date | Filter by appointment date (YYYY-MM-DD) |
| **staff_id** | integer | Filter by staff member |
| **customer_id** | integer | Filter by customer |
| **status** | enum | Filter by status: pending, confirmed, completed, cancelled |

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "customerId": 5,
      "customer": {
        "id": 5,
        "name": "Alice Okonkwo",
        "phone": "+254712345678"
      },
      "serviceId": 2,
      "service": {
        "id": 2,
        "name": "Hair Coloring",
        "duration": 90,
        "price": 3500
      },
      "staffId": 3,
      "staff": {
        "id": 3,
        "name": "Kariuki Mutua",
        "phone": "+254722334455"
      },
      "startTime": "2026-08-01T10:00:00Z",
      "endTime": "2026-08-01T11:30:00Z",
      "status": "confirmed",
      "notes": "Customer prefers brown color",
      "createdAt": "2026-07-28T09:15:00Z",
      "updatedAt": "2026-07-28T09:15:00Z"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 50,
    "total": 156,
    "pages": 4,
    "hasMore": true
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Permissions Required**:
- `appointments.read`

---

### POST /api/appointments

**Description**: Create new appointment

**Request**:
```json
{
  "customerId": 5,
  "serviceId": 2,
  "staffId": 3,
  "startTime": "2026-08-01T10:00:00Z",
  "notes": "Customer prefers brown color"
}
```

**Response** (201 Created):
```json
{
  "data": {
    "id": 157,
    "customerId": 5,
    "serviceId": 2,
    "staffId": 3,
    "startTime": "2026-08-01T10:00:00Z",
    "endTime": "2026-08-01T11:30:00Z",
    "status": "pending",
    "notes": "Customer prefers brown color",
    "createdAt": "2026-07-28T10:30:00Z"
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Validation**:
- ✓ customerId must exist and be active
- ✓ serviceId must exist
- ✓ staffId must exist and be active
- ✓ startTime must be at least 1 hour in future
- ✓ startTime must not be before current date
- ✓ startTime must not conflict with other appointments for same staff
- ✓ startTime must be within business hours (configurable)

**Permissions Required**:
- `appointments.create`

---

### GET /api/appointments/:id

**Description**: Retrieve single appointment

**Response** (200 OK):
```json
{
  "data": { /* appointment object */ },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Permissions Required**:
- `appointments.read`

---

### PUT /api/appointments/:id

**Description**: Update appointment details

**Request**:
```json
{
  "serviceId": 3,
  "staffId": 4,
  "startTime": "2026-08-01T14:00:00Z",
  "notes": "Updated notes"
}
```

**Validations**:
- Cannot update if status is completed or cancelled
- Same conflict checks as creation
- 1-hour lead time still required

**Permissions Required**:
- `appointments.update`

---

### POST /api/appointments/:id/cancel

**Description**: Cancel appointment

**Request**:
```json
{
  "reason": "Customer requested cancellation"
}
```

**Response** (200 OK):
```json
{
  "data": {
    "id": 157,
    "status": "cancelled",
    "cancelledAt": "2026-07-28T10:30:00Z",
    "cancelReason": "Customer requested cancellation"
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Side Effects**:
- Send cancellation SMS/email to customer
- Refund any prepaid amount
- Update staff schedule

**Permissions Required**:
- `appointments.cancel`

---

## 7. SALE/POS ENDPOINTS

### GET /api/sales

**Description**: List sales transactions

**Query Parameters**:
```
GET /api/sales?date=2026-08-01&staff_id=3&status=paid&min_amount=1000&max_amount=10000
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 45,
      "customerId": 5,
      "staffId": 3,
      "date": "2026-07-28T15:30:00Z",
      "status": "paid",
      "items": [
        {
          "type": "service",
          "id": 2,
          "name": "Hair Coloring",
          "quantity": 1,
          "unitPrice": 3500,
          "discount": 0,
          "subtotal": 3500
        },
        {
          "type": "product",
          "id": 12,
          "name": "Hair Oil",
          "quantity": 1,
          "unitPrice": 800,
          "discount": 0,
          "subtotal": 800
        }
      ],
      "subtotal": 4300,
      "tax": 430,
      "total": 4730,
      "discount": {
        "type": "percentage",
        "value": 10
      },
      "payments": [
        {
          "method": "mpesa",
          "amount": 4730,
          "reference": "LHR12345678",
          "status": "verified"
        }
      ],
      "notes": "Loyalty tier: Silver",
      "createdAt": "2026-07-28T15:30:00Z"
    }
  ],
  "pagination": { /* ... */ },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Permissions Required**:
- `sales.read`

---

### POST /api/sales

**Description**: Create new sale transaction

**Request**:
```json
{
  "customerId": 5,
  "staffId": 3,
  "items": [
    {
      "type": "service",
      "serviceId": 2,
      "quantity": 1
    },
    {
      "type": "product",
      "productId": 12,
      "quantity": 1
    }
  ],
  "discountType": "percentage",
  "discountValue": 10,
  "notes": "Walk-in customer"
}
```

**Response** (201 Created):
```json
{
  "data": {
    "id": 46,
    "customerId": 5,
    "status": "open",
    "items": [ /* ... */ ],
    "subtotal": 4300,
    "tax": 430,
    "discount": 430,
    "total": 4730,
    "createdAt": "2026-07-28T10:30:00Z"
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Validations**:
- ✓ customerId may be null (walk-in customer)
- ✓ serviceId/productId must exist
- ✓ Quantity must be > 0
- ✓ Product quantity <= available stock
- ✓ Discount value must be valid

**Permissions Required**:
- `sales.create`

---

### POST /api/sales/:id/payments

**Description**: Process payment for sale

**Request**:
```json
{
  "method": "mpesa",
  "phone": "+254712345678",
  "amount": 4730
}
```

**Response** (200 OK):
```json
{
  "data": {
    "paymentId": 123,
    "saleId": 46,
    "method": "mpesa",
    "amount": 4730,
    "status": "stk_push_sent",
    "checkoutRequestId": "ws_CO_1690531200",
    "message": "Awaiting customer confirmation on their phone"
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**For M-Pesa**:
- Status starts as "stk_push_sent"
- Customer enters PIN on phone
- Daraja API returns callback
- Status updates to "verified" on success

**Alternative** (Cash Payment):
```json
{
  "method": "cash",
  "amount": 4730
}
```

**Response**:
```json
{
  "data": {
    "paymentId": 124,
    "status": "paid",
    "receiptNumber": "RCP-20260728-001",
    "receiptUrl": "/receipts/RCP-20260728-001.pdf"
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Side Effects**:
- Inventory deducted for products/services
- Loyalty points awarded to customer
- Receipt generated
- Audit log recorded

**Permissions Required**:
- `payments.process`

---

### POST /api/sales/:id/refund

**Description**: Refund completed sale

**Request**:
```json
{
  "reason": "Customer not satisfied"
}
```

**Response** (200 OK):
```json
{
  "data": {
    "refundId": 25,
    "saleId": 46,
    "amount": 4730,
    "status": "processed",
    "processedAt": "2026-07-28T10:30:00Z"
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Side Effects**:
- Refund sent to original payment method
- Inventory restocked
- Loyalty points reversed
- Audit log recorded

**Permissions Required**:
- `sales.refund`
- User role must be Manager or Owner

---

## 8. INVENTORY ENDPOINTS

### GET /api/products

**Description**: List all products

**Query Parameters**:
```
GET /api/products?category=hair_care&in_stock=true
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 12,
      "name": "Hair Oil",
      "category": "hair_care",
      "description": "Premium hair treatment oil",
      "costPrice": 400,
      "sellingPrice": 800,
      "reorderPoint": 20,
      "status": "active",
      "stock": {
        "available": 45,
        "reserved": 3,
        "onHand": 48
      }
    }
  ],
  "pagination": { /* ... */ },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Permissions Required**:
- `inventory.read`

---

### GET /api/stock/:productId

**Description**: Get current stock level and movements

**Response** (200 OK):
```json
{
  "data": {
    "productId": 12,
    "available": 45,
    "reserved": 3,
    "onHand": 48,
    "reorderPoint": 20,
    "status": "normal",
    "lastMovement": "2026-07-28T15:30:00Z",
    "movements": [
      {
        "id": 567,
        "type": "sale",
        "quantity": -2,
        "reference": "SALE-46",
        "timestamp": "2026-07-28T15:30:00Z"
      }
    ]
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Permissions Required**:
- `inventory.read`

---

### GET /api/stock/:productId/movements

**Description**: Get stock movement history

**Query Parameters**:
```
GET /api/stock/12/movements?type=sale&start_date=2026-07-01&end_date=2026-07-28
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 567,
      "type": "sale",
      "quantity": -2,
      "reference": "SALE-46",
      "reason": "Customer purchased",
      "timestamp": "2026-07-28T15:30:00Z",
      "createdBy": 3
    },
    {
      "id": 566,
      "type": "purchase",
      "quantity": 100,
      "reference": "PO-789",
      "reason": "Restock",
      "timestamp": "2026-07-27T10:00:00Z",
      "createdBy": 2
    }
  ],
  "pagination": { /* ... */ },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Permissions Required**:
- `inventory.read`

---

## 9. CUSTOMER ENDPOINTS

### GET /api/customers

**Description**: List all customers

**Query Parameters**:
```
GET /api/customers?loyalty_tier=gold&min_visits=5
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 5,
      "name": "Alice Okonkwo",
      "phone": "+254712345678",
      "email": "alice@example.com",
      "visits": 12,
      "totalSpent": 45000,
      "loyalty": {
        "points": 8500,
        "tier": "gold",
        "discount": 0.10,
        "nextTier": "platinum",
        "pointsToNextTier": 16500
      },
      "lastVisit": "2026-07-28T15:30:00Z",
      "status": "active"
    }
  ],
  "pagination": { /* ... */ },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Permissions Required**:
- `customers.read`

---

### POST /api/customers

**Description**: Create new customer

**Request**:
```json
{
  "name": "Bob Kipchoge",
  "phone": "+254722334455",
  "email": "bob@example.com",
  "preferredStaff": 3
}
```

**Response** (201 Created):
```json
{
  "data": {
    "id": 157,
    "name": "Bob Kipchoge",
    "phone": "+254722334455",
    "email": "bob@example.com",
    "visits": 0,
    "totalSpent": 0,
    "loyalty": {
      "points": 0,
      "tier": "bronze",
      "discount": 0.00
    },
    "createdAt": "2026-07-28T10:30:00Z"
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Validations**:
- ✓ Name required
- ✓ Phone must be valid format (optional)
- ✓ Email must be unique if provided

**Permissions Required**:
- `customers.create`

---

### GET /api/customers/:id/history

**Description**: Get customer visit and purchase history

**Response** (200 OK):
```json
{
  "data": {
    "customerId": 5,
    "visits": [
      {
        "date": "2026-07-28T15:30:00Z",
        "service": "Hair Coloring",
        "staff": "Kariuki Mutua",
        "amount": 4730,
        "notes": "Brown color"
      }
    ],
    "preferences": {
      "preferredStaff": [3, 1],
      "preferredServices": [2, 5],
      "averageSpend": 3750,
      "visitFrequency": "weekly"
    }
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Permissions Required**:
- `customers.read`

---

## 10. STAFF ENDPOINTS

### GET /api/staff

**Description**: List all staff members

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 3,
      "name": "Kariuki Mutua",
      "phone": "+254722334455",
      "email": "kariuki@glambymariga.local",
      "role": "stylist",
      "status": "active",
      "performance": {
        "appointmentsCompleted": 145,
        "rating": 4.8,
        "noShowRate": 0.02,
        "totalRevenue": 580000,
        "commission": 87000
      },
      "schedule": {
        "workDays": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
        "startTime": "09:00",
        "endTime": "18:00",
        "breakTime": {
          "start": "13:00",
          "end": "14:00"
        }
      }
    }
  ],
  "pagination": { /* ... */ },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Permissions Required**:
- `staff.read`

---

### GET /api/staff/:id/performance

**Description**: Get detailed staff performance metrics

**Query Parameters**:
```
GET /api/staff/3/performance?start_date=2026-07-01&end_date=2026-07-28
```

**Response** (200 OK):
```json
{
  "data": {
    "staffId": 3,
    "period": {
      "startDate": "2026-07-01",
      "endDate": "2026-07-28",
      "days": 28
    },
    "appointments": {
      "scheduled": 25,
      "completed": 24,
      "noShow": 1,
      "cancelled": 0,
      "completionRate": 0.96
    },
    "revenue": {
      "total": 87000,
      "average": 3625,
      "byService": [
        {
          "service": "Hair Coloring",
          "count": 12,
          "total": 42000
        }
      ]
    },
    "customer": {
      "totalCustomers": 18,
      "repeatCustomers": 12,
      "newCustomers": 6,
      "averageRating": 4.8
    },
    "commission": {
      "rate": 0.15,
      "amount": 13050
    }
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Permissions Required**:
- `staff.read` (own data)
- `staff.manage` (any staff data)

---

## 11. ADMIN ENDPOINTS

### GET /api/admin/users

**Description**: List all system users

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "email": "owner@glambymariga.local",
      "name": "Mariga Kamau",
      "role": "owner",
      "status": "active",
      "permissions": ["*"],
      "lastLogin": "2026-07-28T10:30:00Z",
      "createdAt": "2026-01-15T08:00:00Z"
    },
    {
      "id": 2,
      "email": "manager@glambymariga.local",
      "name": "Jane Smith",
      "role": "manager",
      "status": "active",
      "permissions": [
        "appointments.read",
        "appointments.update",
        "sales.read",
        "customers.read",
        "staff.read",
        "reports.view"
      ],
      "lastLogin": "2026-07-28T09:15:00Z",
      "createdAt": "2026-01-15T08:00:00Z"
    }
  ],
  "pagination": { /* ... */ },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Permissions Required**:
- `users.manage`

---

### POST /api/admin/users

**Description**: Create new system user

**Request**:
```json
{
  "email": "receptionist2@glambymariga.local",
  "name": "Elizabeth Adeyemi",
  "role": "receptionist",
  "password": "SecurePassword123!"
}
```

**Response** (201 Created):
```json
{
  "data": {
    "id": 8,
    "email": "receptionist2@glambymariga.local",
    "name": "Elizabeth Adeyemi",
    "role": "receptionist",
    "status": "active",
    "permissions": [
      "appointments.create",
      "appointments.read",
      "sales.create",
      "sales.read",
      "customers.read"
    ],
    "createdAt": "2026-07-28T10:30:00Z"
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Permissions Required**:
- `users.manage`

---

### GET /api/admin/audit-log

**Description**: Get system audit trail

**Query Parameters**:
```
GET /api/admin/audit-log?user_id=2&action=create&resource=appointment&start_date=2026-07-01&end_date=2026-07-28
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1234,
      "timestamp": "2026-07-28T15:30:00Z",
      "user": {
        "id": 2,
        "email": "receptionist@glambymariga.local"
      },
      "action": "create",
      "resource": "appointment",
      "resourceId": 157,
      "changes": {
        "before": {},
        "after": {
          "customerId": 5,
          "serviceId": 2,
          "startTime": "2026-08-01T10:00:00Z"
        }
      },
      "ipAddress": "192.168.1.100",
      "userAgent": "Mozilla/5.0 Chrome/118.0"
    }
  ],
  "pagination": { /* ... */ },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Permissions Required**:
- `audit.view`

---

## 12. REPORT ENDPOINTS

### GET /api/reports/dashboard

**Description**: Get dashboard metrics overview

**Response** (200 OK):
```json
{
  "data": {
    "period": {
      "date": "2026-07-28",
      "dayOfWeek": "Thursday"
    },
    "revenue": {
      "today": 28500,
      "thisWeek": 145000,
      "thisMonth": 580000
    },
    "transactions": {
      "today": 8,
      "thisWeek": 42,
      "thisMonth": 168
    },
    "appointments": {
      "today": {
        "scheduled": 6,
        "completed": 4,
        "pending": 1,
        "cancelled": 1
      },
      "nextSevenDays": 28
    },
    "customers": {
      "newToday": 2,
      "newThisMonth": 15,
      "repeat": {
        "today": 6,
        "thisMonth": 153
      }
    },
    "inventory": {
      "lowStockItems": 3,
      "outOfStock": 1,
      "totalValue": 125000
    },
    "staff": {
      "topPerformer": {
        "name": "Kariuki Mutua",
        "revenue": 28000,
        "appointments": 8
      }
    }
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Permissions Required**:
- `reports.view`

---

### GET /api/reports/revenue

**Description**: Get detailed revenue report

**Query Parameters**:
```
GET /api/reports/revenue?start_date=2026-07-01&end_date=2026-07-28&groupBy=service
```

**Response** (200 OK):
```json
{
  "data": {
    "period": {
      "startDate": "2026-07-01",
      "endDate": "2026-07-28",
      "days": 28
    },
    "summary": {
      "total": 580000,
      "average": 20714,
      "byPaymentMethod": {
        "cash": 290000,
        "mpesa": 290000
      }
    },
    "byService": [
      {
        "service": "Hair Coloring",
        "count": 45,
        "total": 157500,
        "average": 3500
      },
      {
        "service": "Hair Treatment",
        "count": 38,
        "total": 133000,
        "average": 3500
      }
    ]
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

**Permissions Required**:
- `reports.view`

---

### GET /api/reports/export

**Description**: Export report to Excel or PDF

**Query Parameters**:
```
GET /api/reports/export?type=revenue&format=excel&start_date=2026-07-01&end_date=2026-07-28
```

| Parameter | Values |
|-----------|--------|
| **type** | revenue, appointments, customers, staff, inventory |
| **format** | excel, pdf |

**Response** (200 OK):
```
File attachment: revenue_2026-07-01_to_2026-07-28.xlsx
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
```

**Permissions Required**:
- `reports.view`

---

## 13. WEBHOOKS (Future)

Webhooks will be implemented in Phase 2 for real-time notifications:

- `appointment.created`
- `appointment.cancelled`
- `payment.processed`
- `payment.failed`
- `stock.low`
- `customer.created`

---

## 14. RATE LIMITING

### Rate Limit Policy

**Limit**: 100 requests per minute per authenticated user

**Default**: 10 requests per minute for unauthenticated requests

**Response Headers**:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 99
X-RateLimit-Reset: 1690531260
```

**When Limited** (429 Too Many Requests):
```json
{
  "error": {
    "code": "RATE_LIMITED",
    "message": "Too many requests. Please try again after 60 seconds.",
    "retryAfter": 60
  },
  "meta": { "timestamp": "2026-07-28T10:30:00Z" }
}
```

---

## IMPLEMENTATION CHECKLIST

### Phase 1 (Sprint 1) - Required Endpoints

- [ ] POST /api/login
- [ ] POST /api/logout
- [ ] GET /api/appointments
- [ ] POST /api/appointments
- [ ] GET /api/appointments/:id
- [ ] PUT /api/appointments/:id
- [ ] POST /api/appointments/:id/cancel
- [ ] GET /api/sales
- [ ] POST /api/sales
- [ ] POST /api/sales/:id/payments
- [ ] POST /api/sales/:id/refund
- [ ] GET /api/products
- [ ] GET /api/stock/:productId
- [ ] GET /api/customers
- [ ] POST /api/customers
- [ ] GET /api/staff
- [ ] GET /api/staff/:id/performance
- [ ] GET /api/admin/users
- [ ] POST /api/admin/users
- [ ] GET /api/admin/audit-log
- [ ] GET /api/reports/dashboard
- [ ] GET /api/reports/revenue
- [ ] GET /api/reports/export

---

**END OF API_REFERENCE.md**

**Document History**:
- v1.0.0 (2026-07-28): Complete API specification for Phase 1

**Last Review**: 2026-07-28  
**Next Review**: After each sprint for endpoint additions
