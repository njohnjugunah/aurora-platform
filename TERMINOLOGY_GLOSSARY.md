# TERMINOLOGY_GLOSSARY.md

**Aurora Platform - Ubiquitous Language Reference**

Version: 1.0.0  
Status: Active  
Last Updated: 2026-07-28

---

## DOMAIN TERMS

### Appointment
**Definition**: A scheduled booking of a service with a specific staff member at a specific date/time  
**Context**: Business domain term for customer service reservations  
**Synonyms (Invalid)**: Booking (use "Appointment" for consistency)  
**Example**: "Create a hair coloring appointment for customer Alice on Friday at 10 AM with Kariuki"  
**Related**: Service, Staff, Availability, Conflict  
**Architecture Link**: ARCHITECTURE.md § 3.1, DATABASE_SCHEMA.md § 2.8

### Service
**Definition**: A beauty treatment offered by the salon (e.g., Hair Coloring, Hair Treatment)  
**Attributes**: Name, Description, Duration (minutes), Price (KES)  
**Context**: Business offering, not technical service  
**Synonyms (Invalid)**: Beauty Service (too verbose), Treatment (ambiguous)  
**Example**: "Hair Coloring service takes 90 minutes and costs 3,500 KES"  
**Related**: Appointment, Staff, Sale, LineItem  
**Note**: DO NOT use "Service" for application services - use "Business Service" if needed  
**Architecture Link**: DATABASE_SCHEMA.md § 2.6, API_REFERENCE.md § 6

### Sale
**Definition**: A completed transaction recording services and/or products sold to a customer  
**Attributes**: Date, Amount (before/after discount), Payment Method, Status (open/paid/refunded)  
**Context**: Business transaction, recorded in POS system  
**Synonyms (Invalid)**: Transaction (too generic), Invoice (different concept - invoice is output)  
**Example**: "Sale #123: Hair Coloring + Hair Oil = 4,730 KES (paid via M-Pesa)"  
**Related**: LineItem, Payment, Receipt, Refund, Customer  
**Architecture Link**: DATABASE_SCHEMA.md § 2.9, API_REFERENCE.md § 7

### LineItem
**Definition**: Individual service or product within a sale (row-level detail)  
**Attributes**: Item Type (service/product), Quantity, Unit Price, Subtotal  
**Context**: Accounting detail, enables itemized receipts  
**Synonyms (Invalid)**: Item (too generic), SaleItem (verbose)  
**Example**: "LineItem: 1x Hair Coloring @ 3,500 KES = 3,500 KES"  
**Related**: Sale, Service, Product  
**Architecture Link**: DATABASE_SCHEMA.md § 2.10

### Payment
**Definition**: Money received from customer, settling a sale (fully or partially)  
**Attributes**: Method (Cash/M-Pesa/Card), Amount, Reference, Status (pending/verified/failed)  
**Context**: Financial transaction, essential for auditing and reconciliation  
**Synonyms (Invalid)**: Receipt (different - receipt is the document issued to customer)  
**Example**: "Payment: 4,730 KES via M-Pesa, ref LHR12345678, verified"  
**Related**: Sale, Receipt, Refund  
**Note**: One sale can have multiple partial payments  
**Architecture Link**: DATABASE_SCHEMA.md § 2.11, API_REFERENCE.md § 7

### Receipt
**Definition**: Document issued to customer confirming transaction details and payment received  
**Context**: Customer-facing output, serves as proof of purchase  
**Synonyms (Invalid)**: Invoice (different - invoice is formal billing document)  
**Format**: Printed or emailed, shows itemized sale + payment method  
**Related**: Sale, Payment  
**Architecture Link**: API_REFERENCE.md § 7

### Refund
**Definition**: Money returned to customer for previously paid sale (fully or partially)  
**Reason Types**: Customer request, damaged goods, no-show, system error  
**Process**: Authorization → Reverse to original payment method → Update inventory → Reverse loyalty points  
**Related**: Payment, Sale, Inventory  
**Architecture Link**: DATABASE_SCHEMA.md § 2.11, DECISION_LOG.md § ADR-007

### Stock / Inventory
**Definition**: Quantity of physical products available for sale  
**Terminology**: 
- **Stock** = Physical quantity in salon
- **Inventory** = Overall stock management system (not a noun)  
**Synonyms**: 
- "On-hand" = Physical quantity (correct)
- "Available" = On-hand minus reserved (correct)
- "Reserved" = Promised but not yet delivered  
**Example**: "Hair Oil: 45 on-hand, 3 reserved, 42 available"  
**Related**: Product, StockMovement, Reorder Point  
**Architecture Link**: DATABASE_SCHEMA.md § 2.12-2.14

### Loyalty Points
**Definition**: Reward currency earned by customers for purchases (1 point per 1 KES spent)  
**Attributes**: Customer ID, Total Points, Tier (Bronze/Silver/Gold/Platinum)  
**Redemption**: 100 points = 10 KES discount  
**Tier Benefits**: Automatic discount applied at checkout (0%, 5%, 10%, 20% by tier)  
**Related**: Customer, Sale, Tier, Discount  
**Architecture Link**: DATABASE_SCHEMA.md § 2.15

### Tier
**Definition**: Customer loyalty level determining automatic discount (Bronze/Silver/Gold/Platinum)  
**Thresholds**: 
- Bronze: 0+ points (0% discount)
- Silver: 5,000+ points (5% discount)
- Gold: 10,000+ points (10% discount)
- Platinum: 25,000+ points (20% discount)  
**Progression**: Automatic on points accumulation, never manual  
**Related**: LoyaltyPoints, Customer, Discount  
**Note**: "Tier" refers to loyalty level, not database tier  

### Commission
**Definition**: Staff member compensation as percentage of services they performed (0-100%)  
**Calculation**: Commission Rate × Service Revenue  
**Example**: Staff with 15% commission performing 87,000 KES revenue = 13,050 KES commission  
**Related**: Staff, Performance, Revenue  
**Architecture Link**: DATABASE_SCHEMA.md § 2.7, ARCHITECTURE.md § 3.1

### Customer
**Definition**: Individual who purchases services and/or products from salon  
**Types**: 
- **New**: No purchase history
- **Repeat**: 2+ visits
- **VIP**: Top 5% by lifetime value  
**Attributes**: Name, Phone, Email, Visit Count, Lifetime Value, Loyalty Tier  
**Related**: Appointment, Sale, LoyaltyPoints, Feedback  
**Architecture Link**: DATABASE_SCHEMA.md § 2.5

### Staff / Staff Member
**Definition**: Employee who performs services (stylist, receptionist, manager)  
**Attributes**: Name, Phone, Position (Stylist/Receptionist/Manager), Commission Rate, Status  
**Related**: Appointment, User, Performance, Commission  
**Note**: Staff member ≠ User (different tables, related by user_id)  
**Architecture Link**: DATABASE_SCHEMA.md § 2.7

### User
**Definition**: System account for staff/admin with authentication and permissions  
**Attributes**: Email, Password (hashed), Name, Role, Status (active/inactive/locked)  
**Related**: Staff, Roles, Permissions  
**Note**: Every staff member has a user account, but not every user is staff (admins, managers)  
**Architecture Link**: DATABASE_SCHEMA.md § 2.1

---

## TECHNICAL TERMS

### Controller
**Definition**: HTTP request handler that delegates to services  
**Location**: `src/Application/Controllers/`  
**Responsibility**: Parse request, call service, format response  
**Note**: NOT a business service, only HTTP concerns  
**Example**: AuthController receives login request, calls AuthenticationService  
**Related**: Service, Handler  
**Architecture Link**: ARCHITECTURE.md § 2 "Application Layer"

### Service
**Definition**: Business logic orchestrator that coordinates domain models  
**Location**: `src/Application/Services/`  
**Responsibility**: Business workflow, validation, transaction management  
**Example**: BookingService creates appointment, checks conflicts, publishes event  
**Note**: DO NOT confuse with business "Service" (beauty treatment) or HTTP service  
**Related**: Domain Model, Repository, Event  
**Architecture Link**: ARCHITECTURE.md § 5 "Service Layer"

### Repository
**Definition**: Data access abstraction hiding persistence implementation  
**Interface**: `src/Domain/Repositories/XRepository.php`  
**Implementation**: `src/Infrastructure/Persistence/MySQLXRepository.php`  
**Responsibility**: Query, insert, update database records  
**Related**: Entity, Domain Model, Persistence  
**Architecture Link**: ARCHITECTURE.md § 4 "Repository Pattern", DECISION_LOG.md § ADR-004

### Entity
**Definition**: Object with identity, mutable, contains business logic  
**Example**: Appointment, Customer, Sale  
**Contrasts**: Value Object (immutable, compared by value)  
**Related**: Domain Model, Aggregate  
**Architecture Link**: ARCHITECTURE.md § 3.1 "Entities"

### Value Object
**Definition**: Immutable object compared by value, embedded in entities  
**Examples**: Money (amount + currency), TimeRange (start + end), PhoneNumber (validated)  
**Characteristics**: No setters, returns new instance on changes  
**Related**: Entity, Domain Model  
**Status (Phase 1)**: Not yet implemented  
**Architecture Link**: ARCHITECTURE.md § 3.2 "Value Objects", DECISION_LOG.md § ADR-015

### Domain Event
**Definition**: Something that happened in the business domain, triggers side effects  
**Examples**: AppointmentScheduled, PaymentProcessed, StockDeducted  
**Handler Pattern**: Event → Multiple handlers → Side effects (notifications, integrations)  
**Related**: Service, Repository, Audit  
**Status (Phase 1)**: Framework ready, not integrated  
**Architecture Link**: ARCHITECTURE.md § 5 "Domain Events"

### Aggregate
**Definition**: Cluster of related entities treated as single unit with one root  
**Example**: CustomerAggregate = Customer + LoyaltyPoints (accessed through Customer)  
**Boundary**: Transactions don't span multiple aggregates  
**Related**: Entity, Repository  
**Status (Phase 1)**: Designed, not formally implemented  
**Architecture Link**: ARCHITECTURE.md § 3.3 "Aggregates"

### JWT Token
**Definition**: JSON Web Token for stateless authentication  
**Format**: Header.Payload.Signature (e.g., `eyJhbGc...`)  
**Contains**: userId, roles, permissions, expiration  
**Expiration**: 24 hours  
**Storage**: Client-side localStorage (frontend only)  
**Related**: Authentication, Authorization, RBAC  
**Architecture Link**: ARCHITECTURE.md § 10 "Security", DECISION_LOG.md § ADR-003

### RBAC
**Definition**: Role-Based Access Control - permissions granted through roles  
**Hierarchy**: User → Roles → Permissions  
**Example**: Owner role has all permissions; Receptionist role has specific permissions  
**Related**: User, Role, Permission, JWT  
**Architecture Link**: ARCHITECTURE.md § 5 "RBAC", DECISION_LOG.md § ADR-011

---

## ANTI-PATTERNS (What NOT to Use)

| Term | Problem | Correct Term |
|------|---------|--------------|
| "Booking" for appointment | Ambiguous (appointment = booking) | Use "Appointment" |
| "Transaction" for sale | Too generic (also used for DB transactions) | Use "Sale" |
| "Invoice" for receipt | Invoice is formal billing, receipt is proof of payment | Use "Receipt" |
| "Service" for application logic | Overloads business "service" | Use "Business Service" if needed, or "Service Class" |
| "Store" as synonym for save | Vague, use database verb | Use "Save", "Insert", "Update", "Create" |
| "Component" without qualifier | Ambiguous (code component vs UI component) | Use "Module", "Widget", "Class", "Layer" |
| "Inventory" as countable noun | Inventory is the system, not the thing | Use "Stock" (noun) or "Inventory management" (system) |
| "Discount" vs "Commission" | Opposite directions (customer benefit vs staff benefit) | Be explicit which direction |

---

## ABBREVIATIONS (Approved)

| Abbreviation | Full Term | Context | Usage |
|--------------|-----------|---------|-------|
| **POS** | Point of Sale | Sales system | "POS transaction", "POS interface" |
| **SKU** | Stock Keeping Unit | Product identifier | "Product SKU", "Track by SKU" |
| **API** | Application Programming Interface | Technical | "REST API", "API endpoint" |
| **JWT** | JSON Web Token | Authentication | "JWT token", "JWT authentication" |
| **RBAC** | Role-Based Access Control | Authorization | "RBAC policy", "RBAC enforcement" |
| **HTTP** | Hypertext Transfer Protocol | Web | "HTTP method", "HTTP status" |
| **SMS** | Short Message Service | Communication | "SMS notification", "SMS reminder" |
| **KES** | Kenyan Shilling | Currency | "5,000 KES", "Amount in KES" |
| **SLA** | Service Level Agreement | Operations | "99.5% SLA", "SLA violation" |
| **RTO** | Recovery Time Objective | Disaster Recovery | "4-hour RTO", "RTO = Recovery Time Objective" |
| **RPO** | Recovery Point Objective | Disaster Recovery | "1-hour RPO", "RPO = Recovery Point Objective" |

**Do NOT abbreviate in formal documents unless introduced with expansion first.**

---

## CONTEXT-SPECIFIC MEANINGS

### "Service" Disambiguation

| Context | Meaning | Example |
|---------|---------|---------|
| **Business** | Beauty treatment offered | "Hair Coloring service" |
| **Architecture** | Component providing functionality | "PaymentService", "BookingService" |
| **Infrastructure** | External capability | "M-Pesa service", "SMS service" |
| **DevOps** | Running process | "Nginx service", "MySQL service" |

**Rule**: Qualify if ambiguous (e.g., "Business Service" vs "Application Service")

### "Component" Disambiguation

| Context | Meaning | Correct Term |
|---------|---------|--------------|
| **Code** | Reusable code module | "Module", "Class", "Component Class" |
| **UI** | Visual element | "Widget", "UI Component", "Form Component" |
| **Architecture** | Structural piece | "Layer", "Subsystem" |

**Rule**: Qualify unless context is clear (e.g., "React Component" is unambiguous)

---

## REVIEW & MAINTENANCE

**Document Owner**: Architecture Lead  
**Review Cycle**: Quarterly + as-needed when new terms arise  
**Approval**: Team consensus via PR review  
**Updates**: Add new terms before using in documents  

---

**END OF TERMINOLOGY_GLOSSARY.md**

**Purpose**: Ensure consistent language across all documentation  
**Authority**: Architecture Team  
**Cross-References**: All governance documents
