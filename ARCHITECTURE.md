# ARCHITECTURE.md

**Aurora Platform - System Architecture & Design**

Version: 1.0.0  
Status: Production Architecture  
Last Updated: 2026-07-28

---

## TABLE OF CONTENTS

1. Architecture Overview
2. Design Principles
3. Layered Architecture
4. Domain-Driven Design
5. Key Patterns
6. Technology Stack
7. System Topology
8. Data Flow Architecture
9. Deployment Architecture
10. Security Architecture
11. Scalability Architecture
12. Integration Architecture
13. Error Handling & Recovery
14. Monitoring & Observability

---

## 1. ARCHITECTURE OVERVIEW

Aurora Platform follows a **layered architecture with Domain-Driven Design (DDD)** principles, designed for maintainability, testability, scalability, and clear separation of concerns.

### Architecture Layers

```
┌─────────────────────────────────────────┐
│        PRESENTATION LAYER               │
│  (REST API, WebUI, Admin Dashboard)     │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│     APPLICATION LAYER                   │
│  (Controllers, Services, Validators)    │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│        DOMAIN LAYER                     │
│  (Entities, Value Objects, Events)      │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│    INFRASTRUCTURE LAYER                 │
│  (Database, Cache, Integrations)        │
└─────────────────────────────────────────┘
```

### Key Characteristics

| Aspect | Implementation |
|--------|-----------------|
| **Pattern** | Layered + DDD |
| **API Style** | REST with JSON |
| **Authentication** | JWT tokens |
| **Authorization** | Role-Based Access Control (RBAC) |
| **Data Persistence** | MySQL 8.0+ with InnoDB |
| **Caching** | Redis 7.0+ |
| **Containerization** | Docker + Docker Compose |
| **Code Language** | PHP 8.3+ |
| **Frontend** | HTML5/CSS3/JavaScript (ES2022+) |

---

## 2. DESIGN PRINCIPLES

### Core Principles

1. **Single Responsibility Principle**
   - Each class has one reason to change
   - Controllers handle HTTP, services handle business logic, repositories handle persistence

2. **Dependency Injection**
   - Constructor injection preferred
   - No hard dependencies on concrete implementations
   - Promotes testability and flexibility

3. **Domain-Driven Design**
   - Business logic lives in domain models
   - Entities with rich behavior, not anemic objects
   - Value objects for immutable concepts
   - Domain events for side-effect tracking

4. **Repository Pattern**
   - Abstract persistence details behind repository interfaces
   - Enable testing with in-memory implementations
   - Facilitate switching databases

5. **Service Layer**
   - Encapsulate business workflows
   - Coordinate between domain and infrastructure
   - Handle cross-cutting concerns (logging, validation)

6. **Clean Code**
   - Self-documenting code through clear naming
   - Small, focused methods
   - No god objects or god methods
   - Minimal comments (code should be clear)

### Non-Principles (Anti-Patterns Avoided)

- **Anemic Models**: Domain models are NOT just data containers; they contain business logic
- **Service Locators**: Services are injected, not looked up
- **Static Methods**: Minimized for testability
- **Global State**: Avoided to prevent hidden dependencies
- **Active Record**: Not used; repositories provide data access

---

## 3. LAYERED ARCHITECTURE

### Layer 1: Presentation Layer

**Responsibility**: Expose API endpoints and handle HTTP concerns

**Components**:
- REST API endpoints (via Controllers)
- WebUI (HTML/CSS/JavaScript)
- Admin Dashboard
- Request/Response formatting
- CORS headers

**Technology**:
- PHP 8.3 (backend controllers)
- HTML5/CSS3/JavaScript (frontend)
- Bootstrap 5 (styling)

**Key Files**:
```
src/Application/Controllers/
  - AuthController.php
  - AppointmentController.php
  - SaleController.php
  - CustomerController.php
  - StaffController.php
  - ReportController.php
  - AdminController.php

public/
  - index.html (SPA entry point)
  - js/app.js (main app logic)
  - css/main.css (styling)
```

**Guidelines**:
- Controllers should be thin (< 30 lines typically)
- All validation delegated to validators
- All business logic delegated to services
- Return appropriate HTTP status codes

---

### Layer 2: Application Layer

**Responsibility**: Orchestrate domain models to fulfill business use cases

**Components**:

#### 2.1 Services
- **AuthenticationService**: User login, token generation, session management
- **BookingService**: Appointment creation with conflict checking
- **PaymentService**: Payment processing and reconciliation
- **InventoryService**: Stock tracking and deductions
- **LoyaltyService**: Points calculation and tier management
- **NotificationService**: SMS and email notifications
- **AvailabilityService**: Staff availability and scheduling

**Characteristics**:
- Stateless (no instance variables)
- Single public method per use case
- Coordinate domain models
- Handle transactions
- Emit domain events

#### 2.2 Validators
- **LoginValidator**: Email format, password requirements
- **AppointmentValidator**: Date/time validation, staff availability
- **PaymentValidator**: Amount validation, payment method availability
- **CustomerValidator**: Required fields, duplicate detection

**Characteristics**:
- Pure validation logic
- Return validation result object
- No side effects
- Reusable across endpoints

#### 2.3 Controllers
- Map HTTP requests to service methods
- Parse and validate request data
- Call appropriate service
- Transform response to HTTP format

**Key Files**:
```
src/Application/Services/
  - AuthenticationService.php
  - BookingService.php
  - PaymentService.php
  - InventoryService.php
  - LoyaltyService.php
  - NotificationService.php
  - AvailabilityService.php

src/Application/Validators/
  - LoginValidator.php
  - AppointmentValidator.php
  - PaymentValidator.php
  - CustomerValidator.php

src/Application/Controllers/
  - (See Presentation Layer)
```

**Guidelines**:
- Services contain business logic, not controllers
- Validators are separate from services
- Services should be testable with mocks
- No SQL in services

---

### Layer 3: Domain Layer

**Responsibility**: Model business domain with rich behavior and clear intent

**Components**:

#### 3.1 Entities
- **User**: System user with roles and permissions
- **Customer**: Beauty salon customer with preferences and loyalty data
- **Appointment**: Booking event with service, staff, date/time
- **Service**: Beauty service offered (haircut, coloring, etc.)
- **Staff**: Staff member with schedule and performance metrics
- **Sale**: Customer transaction with line items and payments
- **Payment**: Payment record for a sale
- **Product**: Inventory item for sale
- **Stock**: Stock level and movements for a product
- **LoyaltyPoints**: Customer loyalty points ledger
- **Role**: User role for authorization
- **Permission**: Granular permission for authorization
- **AuditLog**: Record of system changes

**Entity Characteristics**:
- Have identity (unique ID)
- Mutable (state can change)
- Contain business logic (not just data)
- Enforce business rules
- Work together in aggregates

**Example (Appointment Entity)**:
```php
class Appointment {
  - Properties: id, customerId, staffId, serviceId, startTime, duration
  - Behavior: canBeCancelled(), calculateEndTime(), conflicts(other)
  - Rules: Minimum 1-hour lead time, cannot overlap other appointments
}
```

#### 3.2 Value Objects
- **Money**: Amount and currency (immutable)
- **TimeRange**: Start and end time (immutable)
- **PhoneNumber**: Formatted phone number (immutable)
- **EmailAddress**: Validated email (immutable)
- **UserId**, **CustomerId**, etc.: Type-safe IDs

**Value Object Characteristics**:
- Immutable (no setters)
- Compared by value, not identity
- No separate database table
- Embedded in entities
- Domain-specific type safety

#### 3.3 Aggregates
- **CustomerAggregate**: Customer + LoyaltyPoints (related entities)
- **OrderAggregate**: Sale + LineItems + Payments (related entities)
- **StaffAggregate**: Staff + Performance + Schedule (related entities)

**Aggregate Characteristics**:
- Root entity with sub-entities
- Accessed only through root
- Consistency boundary
- Transaction boundary

#### 3.4 Domain Events
- **AppointmentScheduled**: Event raised when appointment created
- **AppointmentCancelled**: Event raised when appointment cancelled
- **PaymentProcessed**: Event raised when payment confirmed
- **StockDeducted**: Event raised when inventory deducted

**Domain Event Characteristics**:
- Represent something that happened
- Named in past tense
- Can trigger side effects (notifications, integrations)
- Store in audit log for compliance

#### 3.5 Repository Interfaces
- Define data access contracts
- No implementation details
- Enable testing with in-memory implementations

**Key Files**:
```
src/Domain/Models/
  - User.php
  - Customer.php
  - Appointment.php
  - Service.php
  - Staff.php
  - Sale.php

src/Domain/ValueObjects/
  - Money.php
  - TimeRange.php
  - PhoneNumber.php
  - EmailAddress.php

src/Domain/Repositories/
  - UserRepository (interface)
  - CustomerRepository (interface)
  - AppointmentRepository (interface)
  - ServiceRepository (interface)
  - StaffRepository (interface)
  - SaleRepository (interface)
  - PaymentRepository (interface)
  - StockRepository (interface)
  - LoyaltyRepository (interface)

src/Domain/Events/
  - AppointmentScheduled.php
  - AppointmentCancelled.php
  - PaymentProcessed.php
  - StockDeducted.php
```

**Guidelines**:
- Model the business domain accurately
- Enforce business rules in entities
- Use value objects for domain concepts
- Avoid database thinking in domain models
- Focus on "what" not "how"

---

### Layer 4: Infrastructure Layer

**Responsibility**: Implement technical details (databases, external services, caching)

**Components**:

#### 4.1 Repository Implementations
- **MySQLUserRepository**: Implements UserRepository
- **MySQLCustomerRepository**: Implements CustomerRepository
- (... one for each repository interface)

**Characteristics**:
- Hide SQL complexity
- Implement repository interfaces
- Handle connection pooling
- Support transaction management

#### 4.2 External Integrations
- **MpesaGateway**: M-Pesa payment processing (Daraja API)
- **TwilioGateway**: SMS delivery
- **EmailGateway**: Email sending
- **RedisCache**: Caching layer

**Characteristics**:
- Adapt external APIs to domain concepts
- Handle failures gracefully
- Implement retry logic
- Provide fallbacks

#### 4.3 Database Access
- **Database Connection Manager**: Connection pooling, lifecycle
- **Query Builder**: DML/DDL operations (prepared statements)
- **Migration Runner**: Schema versioning

**Characteristics**:
- All SQL prepared statements (prevent injection)
- Connection pooling for performance
- Transaction management
- Logging for debugging

**Key Files**:
```
src/Infrastructure/Persistence/
  - MySQLUserRepository.php
  - MySQLCustomerRepository.php
  - MySQLAppointmentRepository.php
  - (... etc for all repositories)

src/Infrastructure/Integrations/
  - MpesaGateway.php
  - TwilioGateway.php
  - EmailGateway.php

src/Infrastructure/Database/
  - Connection.php
  - QueryBuilder.php
  - Migrator.php

migrations/
  - 001_create_base_schema.sql
  - 002_add_indexes.sql
  - (... etc for future migrations)
```

**Guidelines**:
- Keep infrastructure concerns separate from business logic
- Implement repository interfaces exactly as specified
- Use dependency injection for external services
- Log all external service calls
- Implement circuit breaker for unreliable services

---

## 4. DOMAIN-DRIVEN DESIGN

### Bounded Contexts

Aurora Platform consists of these logical bounded contexts:

| Context | Core Entities | Services | Purpose |
|---------|---------------|----------|---------|
| **Identity** | User, Role, Permission | AuthenticationService | Access control |
| **Appointments** | Appointment, Service, Staff | BookingService, AvailabilityService | Schedule management |
| **Sales** | Sale, LineItem, Payment | PaymentService | Transaction processing |
| **Inventory** | Product, Stock, Movement | InventoryService | Stock management |
| **Customers** | Customer, LoyaltyPoints | LoyaltyService | CRM and retention |
| **Notifications** | - | NotificationService | Communication |
| **Reporting** | - | ReportService | Business intelligence |

### Ubiquitous Language

The team uses consistent terminology across code, documentation, and conversation:

| Term | Definition |
|------|-----------|
| **Appointment** | A booking of a service with a specific staff member at a specific date/time |
| **Service** | A beauty service offered (haircut, coloring, treatment, etc.) |
| **Staff** | A team member who can perform services |
| **Sale** | A transaction representing services and/or products sold to a customer |
| **LineItem** | A single service or product within a sale |
| **Payment** | A method of settling a sale (cash, M-Pesa, card, etc.) |
| **Stock** | The inventory quantity of a product available for sale |
| **Loyalty Points** | Reward currency earned by customers for purchases |
| **Tier** | Customer loyalty tier (Bronze, Silver, Gold, Platinum) |
| **Commission** | Compensation for staff based on services performed |
| **Audit Log** | Immutable record of system changes for compliance |

---

## 5. KEY PATTERNS

### Pattern 1: Repository Pattern

**Problem**: Business logic shouldn't depend on database implementation

**Solution**: Define repository interfaces in domain layer, implement in infrastructure layer

```
Domain Layer:
  interface AppointmentRepository {
    findById(id): Appointment
    save(appointment): void
    delete(id): void
  }

Infrastructure Layer:
  class MySQLAppointmentRepository implements AppointmentRepository {
    // Implementation using SQL
  }

Application Layer:
  class BookingService {
    __construct(AppointmentRepository $repository)
    bookAppointment(): void {
      $appointment = new Appointment(...)
      $this->repository->save($appointment)
    }
  }
```

**Benefits**:
- Decouple business logic from persistence
- Easy to test with in-memory repositories
- Can switch databases without affecting domain logic

---

### Pattern 2: Service Layer

**Problem**: Business logic shouldn't live in controllers or entities alone

**Solution**: Create services that orchestrate domain models

```
Controller:
  AppointmentController->create() {
    $validator->validate($request)
    $service->bookAppointment($request)
  }

Service:
  BookingService->bookAppointment(request) {
    $customer = $this->customerRepository->find(request.customerId)
    $appointment = Appointment::create(...)
    $this->checkAvailability($appointment)
    $this->appointmentRepository->save($appointment)
    $this->notificationService->sendConfirmation($appointment)
  }

Domain:
  Appointment::create(...) -> validate business rules
```

**Benefits**:
- Clear separation of concerns
- Testable in isolation
- Reusable across multiple controllers

---

### Pattern 3: Value Objects

**Problem**: Domain concepts like Money, Time, PhoneNumber are not primitive types

**Solution**: Create immutable value objects

```
Domain:
  class Money {
    private float $amount
    private string $currency
    
    public function __construct(float $amount, string $currency) {
      // Validate
      $this->amount = $amount
      $this->currency = $currency
    }
    
    // No setters - immutable
    public function add(Money $other): Money {
      return new Money($this->amount + $other->amount, ...)
    }
  }

Usage:
  $price = new Money(5000, 'KES')
  $tax = new Money(800, 'KES')
  $total = $price->add($tax) // Returns new Money object
```

**Benefits**:
- Type safety
- Business rule enforcement
- Clear intent in code

---

### Pattern 4: Dependency Injection

**Problem**: Components shouldn't create their dependencies (tight coupling)

**Solution**: Inject dependencies through constructor

```
// Good: Injected
class PaymentService {
  public function __construct(
    private PaymentRepository $repository,
    private MpesaGateway $gateway,
    private NotificationService $notifications
  ) {}
}

$service = new PaymentService(
  new MySQLPaymentRepository(),
  new MpesaGateway(),
  new NotificationService()
)

// Bad: Hard-coded (tight coupling)
class PaymentService {
  public function __construct() {
    $this->repository = new MySQLPaymentRepository() // Hard-coded!
  }
}
```

**Benefits**:
- Easy to test (inject mocks)
- Flexible (can swap implementations)
- Clear dependencies

---

### Pattern 5: RBAC (Role-Based Access Control)

**Problem**: Different users need different permissions

**Solution**: Assign permissions to roles, roles to users

```
Roles:
  - Owner (full access)
  - Manager (view all, manage staff/settings)
  - Staff (view own schedule, update clients)
  - Receptionist (create appointments, process payments)

Permissions:
  - appointments.create
  - appointments.read
  - appointments.update
  - appointments.delete
  - payments.process
  - staff.manage
  - settings.view
  - reports.view

Example:
  Owner role has all permissions
  Receptionist role has: appointments.create, payments.process, customers.read
```

**Implementation**:
- RBAC middleware checks permission before action
- Permission stored in database table
- Roles assigned to users in database

---

### Pattern 6: Domain Events

**Problem**: Side effects (notifications, integrations) should be triggered by business events

**Solution**: Emit domain events, handle asynchronously

```
Domain:
  Appointment::create() {
    $this->appointmentCreated = new AppointmentScheduled($this)
  }

Application:
  BookingService->bookAppointment() {
    $appointment = Appointment::create(...)
    $this->repository->save($appointment)
    
    // Emit event for side effects
    $this->eventBus->emit($appointment->getDomainEvents())
  }

Event Handlers:
  SendAppointmentConfirmationHandler->handle(AppointmentScheduled) {
    $notification->sendConfirmation($appointment)
  }
  
  UpdateStaffScheduleHandler->handle(AppointmentScheduled) {
    $staff->addAppointment($appointment)
  }
```

**Benefits**:
- Decouple core logic from side effects
- Enable async processing
- Create audit trail of business events

---

## 6. TECHNOLOGY STACK

### Backend

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| **Language** | PHP | 8.3+ | Production language |
| **Web Server** | Nginx | Latest | Reverse proxy, static files |
| **Application** | Custom Framework | 1.0 | HTTP routing, dependency injection |
| **Database** | MySQL | 8.0+ | Persistent data storage |
| **Cache** | Redis | 7.0+ | Session storage, query results |
| **Task Queue** | Async (future) | - | Background jobs |

### Frontend

| Component | Technology | Version | Purpose |
|-----------|-----------|---------|---------|
| **Markup** | HTML5 | ES2022+ | Page structure |
| **Styling** | Bootstrap 5 | 5.3+ | CSS framework |
| **JavaScript** | ES2022+ | Latest | Interactivity, API calls |
| **API Client** | Fetch API | Native | HTTP requests |
| **State** | localStorage | Native | Client-side persistence |
| **UI Components** | Custom | - | Bootstrap-based components |

### DevOps

| Component | Technology | Version | Purpose |
|-----------|-----------|---------|---------|
| **Containerization** | Docker | Latest | Consistent environments |
| **Orchestration** | Docker Compose | Latest | Multi-container orchestration |
| **CI/CD** | GitHub Actions | Native | Automated testing/deployment |
| **Monitoring** | (future) | - | Application monitoring |
| **Logging** | (future) | - | Centralized logging |

### Testing

| Component | Technology | Version | Purpose |
|-----------|-----------|---------|---------|
| **Unit Testing** | PHPUnit | 10.0+ | Component testing |
| **Mocking** | PHPUnit Mocks | - | Dependency mocking |
| **HTTP Testing** | cURL/Postman | - | API testing |

---

## 7. SYSTEM TOPOLOGY

### Local Development

```
Developer Machine
├── Docker Daemon
│   ├── PHP 8.3 FPM Container
│   │   ├── Aurora Application Code
│   │   ├── Composer Dependencies
│   │   └── PHP Modules
│   ├── MySQL 8.0 Container
│   │   ├── Database Schema
│   │   ├── Data Volume
│   │   └── Backup Volume
│   ├── Redis 7.0 Container
│   │   └── Session/Cache Storage
│   └── Nginx Container
│       ├── Reverse Proxy (80 → 8000)
│       ├── Static File Serving
│       └── SSL/TLS (future)
├── Local IDE/Editor
│   └── Application Code
└── Browser
    └── http://localhost:8080
```

### Production Deployment

```
Production Server(s)
├── Docker Runtime
│   ├── PHP 8.3 FPM Containers (2+ replicas)
│   ├── MySQL 8.0 (Primary + Replica)
│   ├── Redis 7.0 (with persistence)
│   └── Nginx Container (load balancer)
├── Volume Storage
│   ├── Database files
│   ├── Session storage
│   ├── Backup storage
│   └── Log storage
├── Monitoring
│   ├── Container health checks
│   ├── Application metrics
│   ├── Database monitoring
│   └── Error tracking
└── Network
    ├── SSL/TLS termination
    ├── CDN integration (future)
    └── Firewall rules
```

---

## 8. DATA FLOW ARCHITECTURE

### User Authentication Flow

```
1. Browser → POST /api/login
                 [email, password]
                 ↓
2. Server → Validate Credentials
            Check password hash
            ↓
3. Server → Generate JWT Token
            [userId, roles, permissions, exp]
            ↓
4. Response → {token, user, permissions}
                 ↓
5. Browser → Store token in localStorage
                 ↓
6. Subsequent → Add Authorization header
   Requests      Authorization: Bearer {token}
                 ↓
7. Server → Validate Token Signature
            Check expiration
            Extract claims
            ↓
8. Allow/Reject based on permissions
```

### Appointment Booking Flow

```
1. Receptionist → POST /api/appointments
                  [customerId, serviceId, staffId, datetime]
                  ↓
2. Controller → Validate Request
                 Check required fields
                 ↓
3. Validator → Business Rule Validation
                Check date/time format
                Verify staff availability
                Check minimum lead time
                ↓
4. Service → Create Appointment Entity
              Check for conflicts
              Calculate duration
              ↓
5. Repository → Save to Database
                 INSERT INTO appointments
                 ↓
6. Event → Emit AppointmentScheduled event
            ↓
7. Handlers → Send SMS confirmation
              Send email reminder
              Update staff schedule
              ↓
8. Response → {appointmentId, status, confirmation}
                 ↓
9. Receptionist → Appointment confirmed on screen
```

### Payment Processing Flow

```
1. Receptionist → POST /api/payments
                  [saleId, amount, method]
                  ↓
2. Service → Validate Amount
              Verify sale status
              ↓
3. PaymentGateway → Route to Payment Method
                    ├─ Cash: Record immediately
                    ├─ M-Pesa: Initiate STK push
                    └─ Card: Send to processor
                    ↓
4. For M-Pesa:
   ├─ Customer enters PIN on phone
   ├─ Daraja API callback with result
   ├─ Verify transaction
   └─ Confirm payment
   ↓
5. Repository → Record Payment
                UPDATE sales SET status = 'paid'
                UPDATE line_items -> deduct stock
                INSERT INTO payments
                ↓
6. Event → Emit PaymentProcessed event
            ↓
7. Handlers → Award loyalty points
              Send receipt
              Update inventory
              Generate invoice
              ↓
8. Response → {paymentId, status, receipt}
                 ↓
9. Receptionist → Print receipt
```

---

## 9. DEPLOYMENT ARCHITECTURE

### CI/CD Pipeline

```
Code Push to GitHub
        ↓
Trigger GitHub Actions
        ↓
Build Stage:
  ├─ Checkout code
  ├─ Build Docker image
  ├─ Run linting (PHPCS)
  └─ Publish to registry
        ↓
Test Stage:
  ├─ Run unit tests (PHPUnit)
  ├─ Run integration tests
  ├─ Check code coverage (80%+)
  └─ Security scan
        ↓
Integration Test Stage:
  ├─ Spin up test containers
  ├─ Run E2E tests
  ├─ Load test
  └─ Cleanup
        ↓
Deploy Stage (on main branch only):
  ├─ Pull image from registry
  ├─ Backup current database
  ├─ Run migrations
  ├─ Update containers
  ├─ Health checks
  └─ Rollback on failure
        ↓
Monitoring:
  ├─ Container health
  ├─ Application metrics
  ├─ Error tracking
  └─ Alerts
```

### Release Channels

```
Main Branch (Production)
  ├─ Deploys to production
  └─ Version: 1.0.0, 1.0.1, 1.1.0, etc.

Develop Branch (Staging)
  ├─ Deploys to staging
  └─ Pre-release testing

Feature Branches
  ├─ Local testing only
  └─ PR required to merge to develop
```

---

## 10. SECURITY ARCHITECTURE

### Authentication

- **Method**: JWT (JSON Web Token)
- **Algorithm**: HS256 (HMAC with SHA-256)
- **Token Contents**: userId, roles, permissions, exp
- **Expiration**: 24 hours (configurable)
- **Refresh**: New token on login required (no refresh tokens)
- **Storage**: LocalStorage (frontend), memory (secure)
- **Transport**: HTTPS only

### Authorization

- **Pattern**: RBAC (Role-Based Access Control)
- **Levels**: User → Roles → Permissions
- **Enforcement**: Middleware on all API endpoints
- **Exceptions**: Public endpoints (login, health)

### Data Protection

- **Passwords**: Bcrypt hashing (12 rounds minimum)
- **Sensitive Data**: AES-256 encryption at rest
- **Data in Transit**: TLS 1.2+ (HTTPS)
- **Database**: Read replicas for scaling, backups encrypted

### API Security

- **SQL Injection**: Prepared statements everywhere
- **XSS**: HTML escaping on output
- **CSRF**: Token-based protection on state-changing requests
- **Rate Limiting**: 100 requests/minute per user
- **Input Validation**: All user input validated before use

### Infrastructure Security

- **Firewall**: Restrict database access to application servers
- **Secrets**: Environment variables for sensitive config
- **Logging**: Audit trail of all sensitive operations
- **Monitoring**: Alert on suspicious activity

---

## 11. SCALABILITY ARCHITECTURE

### Horizontal Scaling

```
Load Balancer (Nginx)
        ↓
    ┌───┴───┬───────┐
    ↓       ↓       ↓
  App 1  App 2   App 3  (PHP-FPM containers)
    ↓       ↓       ↓
    └───┬───┴───────┘
        ↓
    Database (MySQL Primary)
    +
    Database (MySQL Replica)
```

**Strategy**:
- Multiple PHP-FPM containers behind load balancer
- Stateless application (session in Redis)
- Database master-slave replication
- Session storage in Redis (shared)

### Database Scaling

```
Write Operations → MySQL Primary
Read Operations  → MySQL Replica(s)
Cache Layer      → Redis
                   (Reduces DB queries)
```

**Strategy**:
- Read replicas for heavy queries (reporting)
- Query optimization and indexing
- Redis caching for frequently accessed data
- Connection pooling (max 100 connections)

### Performance Optimization

| Strategy | Implementation |
|----------|-----------------|
| **Caching** | Redis for sessions, query results, static data |
| **Query Optimization** | Indexes on foreign keys, search fields |
| **Lazy Loading** | Load related objects only when needed |
| **Pagination** | Limit results to 50 per page |
| **Compression** | GZIP compression for API responses |
| **CDN** | Future: Static assets served from CDN |

---

## 12. INTEGRATION ARCHITECTURE

### M-Pesa Integration

```
Aurora System
    ↓
Daraja API Gateway (Safaricom)
    ├─ Authentication (OAuth 2.0)
    ├─ STK Push (Prompt user for payment)
    ├─ Query Status (Check payment result)
    └─ Reverse/Refund (Reverse payment)
    ↓
M-Pesa Network
    ↓
Customer Phone
    ├─ Display USSD prompt
    ├─ Collect PIN
    └─ Execute transaction
    ↓
Callback to Aurora
    ├─ Verify signature
    ├─ Record payment
    └─ Update inventory
```

**Security**:
- HTTPS only for all requests
- OAuth 2.0 authentication
- Signature verification on callbacks
- Timeout handling (retry logic)

### SMS/Email Integration

```
Aurora System
    ↓
Notification Service
    ├─ Queue notification
    ├─ Log to database
    └─ Send via gateway
    ↓
Twilio Gateway (SMS)
↓
Telecom Provider
↓
Customer Phone

Email Service
↓
SMTP Server
↓
Customer Email
```

**Features**:
- Asynchronous sending (queue-based)
- Retry logic for failures
- Delivery tracking
- Template system

---

## 13. ERROR HANDLING & RECOVERY

### Exception Hierarchy

```
Exception (PHP Built-in)
├── DomainException
│   ├── InvalidBookingException
│   ├── AppointmentConflictException
│   ├── InsufficientStockException
│   └── InvalidPaymentException
├── RuntimeException
│   ├── PaymentGatewayException
│   ├── NotificationException
│   └── DatabaseException
└── LogicException
    └── ValidationException
```

### Error Response Format

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Appointment date must be at least 1 hour in future",
    "details": [
      {
        "field": "appointmentDate",
        "message": "Minimum 1-hour lead time required"
      }
    ],
    "timestamp": "2026-07-28T10:30:00Z"
  }
}
```

### Recovery Strategies

| Scenario | Strategy |
|----------|----------|
| **Database unavailable** | Return 503, log error, alert ops |
| **Payment gateway timeout** | Retry 3 times with exponential backoff, escalate if still failing |
| **Duplicate data** | Unique constraint at DB level, return 409 Conflict |
| **Authentication failure** | Clear token, return 401 Unauthorized |
| **Permission denied** | Return 403 Forbidden, log access attempt |

---

## 14. MONITORING & OBSERVABILITY

### Logging Strategy

| Level | Purpose | Example |
|-------|---------|---------|
| **ERROR** | System failures | Database connection failure |
| **WARN** | Unusual conditions | Retry attempt after failure |
| **INFO** | Important business events | User login, payment processed |
| **DEBUG** | Development only | Service method entry/exit |

**Log Format**:
```
[2026-07-28T10:30:45Z] [ERROR] Payment processing failed
  Service: PaymentService
  Method: processPayment()
  Error: M-Pesa gateway timeout
  Context: saleId=123, amount=5000
  Stack: ...
```

### Metrics Tracked

| Metric | Purpose |
|--------|---------|
| **Request count** | Traffic volume |
| **Response time** | Performance health |
| **Error rate** | System stability |
| **Database query time** | Query performance |
| **Cache hit rate** | Caching effectiveness |
| **Concurrent users** | Load testing baseline |

### Alerts

| Alert | Threshold | Action |
|-------|-----------|--------|
| **High error rate** | >1% | Notify on-call |
| **Slow response time** | >2 seconds | Investigate |
| **High CPU** | >80% | Scale or optimize |
| **Low disk space** | <10% | Cleanup or expand |
| **Database down** | Yes | Immediate escalation |

---

## Architecture Decision Records

### ADR-001: DDD Pattern Selection

**Decision**: Use Domain-Driven Design with layered architecture  
**Rationale**: Complex business domain (appointments, payments, inventory) requires clear separation of concerns  
**Alternatives**: MVC, Microservices  
**Trade-off**: More initial setup, but highly maintainable long-term

### ADR-002: JWT for Authentication

**Decision**: Use JWT tokens instead of sessions  
**Rationale**: Stateless authentication enables horizontal scaling  
**Alternatives**: Session cookies  
**Trade-off**: Cannot revoke tokens (24-hour expiration mitigates)

### ADR-003: MySQL as Primary Database

**Decision**: Use MySQL 8.0+ with InnoDB  
**Rationale**: Reliable, mature, good performance, familiar to team  
**Alternatives**: PostgreSQL, MongoDB  
**Trade-off**: Requires careful indexing for performance

### ADR-004: Repository Pattern

**Decision**: Abstract persistence behind repository interfaces  
**Rationale**: Testability, flexibility to change databases  
**Alternatives**: Direct ORM usage, Active Record  
**Trade-off**: Slightly more code, but much more flexible

---

**END OF ARCHITECTURE.md**

**Revision History**:
- v1.0.0 (2026-07-28): Initial architecture documentation

**Next Review**: After Phase 1 completion (2026-08-07)
