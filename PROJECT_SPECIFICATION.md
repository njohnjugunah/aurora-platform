# PROJECT_SPECIFICATION.md

**Aurora Platform - Complete Project Specification**

Version: 1.0.0  
Status: Active Development  
Last Updated: 2026-07-28

---

## TABLE OF CONTENTS

1. Executive Summary
2. Business Objectives  
3. Business Vision
4. Business Scope
5. Stakeholders
6. Functional Requirements
7. Non-Functional Requirements
8. Business Modules
9. Business Rules
10. Primary Actors
11. Use Cases
12. User Stories & Acceptance Criteria
13. External Integrations
14. Technology Stack
15. Deployment Targets
16. Future Enhancements
17. Traceability Matrix

---

## 1. EXECUTIVE SUMMARY

Aurora Platform is a production-ready SaaS application for GlamByMariga Beauty Studio that consolidates appointment management, point-of-sale operations, inventory tracking, customer relationship management, staff performance monitoring, and business analytics into a unified, secure, scalable platform.

**Client**: GlamByMariga Beauty Studio  
**Location**: Nairobi, Kenya  
**Primary Use**: Enterprise Salon Operations  
**Target Users**: 3-5 concurrent staff + owner/manager  
**Annual Revenue Impact**: Projected 30-40% improvement in operational efficiency  

---

## 2. BUSINESS OBJECTIVES

### Primary Objectives

1. **Eliminate Manual Processes**
   - Replace paper-based appointment logs
   - Eliminate manual sales recording
   - Automate inventory tracking
   - Remove manual report generation

2. **Increase Revenue**
   - Enable upselling through appointment notes
   - Reduce no-shows via automated reminders
   - Facilitate impulse purchases via POS
   - Enable loyalty program to increase repeat business

3. **Improve Customer Experience**
   - Enable online self-service booking
   - Provide real-time appointment confirmation
   - Enable customer history visibility
   - Personalized recommendations based on visit history

4. **Enable Data-Driven Decisions**
   - Provide real-time revenue dashboard
   - Track staff performance metrics
   - Identify top customers by value
   - Monitor inventory trends

---

## 3. BUSINESS VISION

Aurora Platform shall become the operational nerve center of GlamByMariga Beauty Studio, where:

- Every customer interaction is tracked and analyzed
- Every transaction is recorded and verified
- Every staff member's performance is measurable
- Every business decision is data-informed
- The business operates with minimal manual overhead
- Growth is limited only by physical capacity, not administrative burden

---

## 4. BUSINESS SCOPE

### In Scope

**Operations Management**
- Appointment scheduling and management
- Staff availability and assignment
- Service catalog with pricing

**Point of Sale**
- Transaction recording
- Multi-payment method support
- Receipt and invoice generation
- Refund processing
- Discount management

**Inventory Management**
- Product tracking
- Stock level monitoring
- Reorder point alerts
- Purchase order generation
- Stock movement history

**Customer Management**
- Customer profiles and history
- Loyalty program management
- Communication preferences
- Lifetime value tracking

**Staff Management**
- Staff member profiles
- Performance tracking
- Commission calculation
- Schedule management

**Reporting & Analytics**
- Daily revenue reports
- Staff performance metrics
- Customer analytics
- Inventory reports
- Custom report builder

**Administration**
- User account management
- Role and permission management
- System configuration
- Audit logging
- Settings management

**Integration**
- M-Pesa payment processing
- SMS notification delivery
- Email notification delivery

### Out of Scope (Future Phases)

- Mobile app (web responsive only)
- Video consultations
- AI-powered recommendations
- Social media integration
- Advanced inventory forecasting
- Supply chain integration

---

## 5. STAKEHOLDERS

| Stakeholder | Role | Interests | Constraints |
|-------------|------|-----------|-------------|
| Salon Owner/Manager | System Owner | Revenue, Efficiency, Insights | Limited technical knowledge |
| Receptionists (2-3) | Daily Users | Simple booking, easy payments | No technical training |
| Hair Stylists (3-5) | Service Staff | Schedule visibility, client history | Mobile access needed |
| Customers | End Users | Online booking, status visibility | No complex features |
| Finance Team | Periodic User | Payment reconciliation, reporting | Monthly review cycle |

---

## 6. FUNCTIONAL REQUIREMENTS

### F1: Appointment Management

**F1.1 Appointment Booking**
- Staff can create appointments via backend interface
- Customers can book appointments via customer portal (future)
- Prevent double-booking
- Enforce minimum lead time (1 hour)
- Support recurring appointments (future)

**F1.2 Appointment Scheduling**
- View appointments by date/staff/customer
- Filter and search appointments
- Modify appointment details
- Confirm pending appointments
- Mark appointments as completed

**F1.3 Appointment Cancellation**
- Cancel appointments with reason tracking
- Send cancellation notifications
- Track no-shows
- Automatic refund processing for prepaid appointments

**F1.4 Appointment Reminders**
- Send SMS reminder 24 hours before
- Send email reminder 24 hours before
- Send reminder 1 hour before (optional)

### F2: Point of Sale (POS)

**F2.1 Transaction Creation**
- Add services to transaction
- Add products to transaction
- Apply discounts (fixed/percentage)
- Calculate taxes
- Display total amount

**F2.2 Payment Processing**
- Support multiple payment methods:
  - Cash
  - M-Pesa
  - Card (future)
  - Bank transfer (future)
  - Loyalty points
- Process M-Pesa through Daraja API
- Verify M-Pesa payment receipt
- Handle payment failures

**F2.3 Receipt & Invoice**
- Generate receipt for cash transactions
- Generate invoice for customer
- Email receipt/invoice
- Print receipt
- Track invoice payment status

**F2.4 Refunds**
- Process full refunds
- Process partial refunds
- Verify refund authorization
- Update inventory on refund
- Return loyalty points on refund

### F3: Inventory Management

**F3.1 Product Catalog**
- Maintain product database
- Track cost price and selling price
- Categorize products
- Mark products as active/inactive
- Set reorder points

**F3.2 Stock Tracking**
- Record stock on hand
- Track reserved stock
- Calculate available stock
- Deduct stock on sale
- Record stock adjustments

**F3.3 Alerts**
- Alert when stock falls below reorder point
- Track products with zero stock
- Generate purchase order suggestions

**F3.4 Stock Movements**
- Record all stock changes
- Track reasons (sale, purchase, adjustment, return)
- Maintain movement history
- Generate stock reports

### F4: Customer Management

**F4.1 Customer Profiles**
- Create customer records
- Track contact information
- Record preferences
- Maintain visit history
- Monitor communication preferences

**F4.2 Loyalty Program**
- Award loyalty points for purchases
- Track points balance
- Implement tier progression (Bronze, Silver, Gold, Platinum)
- Enable point redemption
- Calculate tier-based discounts

**F4.3 Customer Analytics**
- Track lifetime value
- Calculate visit frequency
- Track preferred services
- Identify VIP customers
- Generate customer segments

### F5: Staff Management

**F5.1 Staff Profiles**
- Create staff member records
- Track roles (Stylist, Manager, Receptionist)
- Maintain contact information
- Track start date and availability

**F5.2 Performance Tracking**
- Track appointments completed
- Monitor no-show rate
- Calculate revenue generated
- Calculate commission earned
- Track customer ratings

**F5.3 Commission Calculation**
- Define commission rates by role
- Calculate commission from sales
- Generate commission reports
- Track commission payments

### F6: Reporting & Analytics

**F6.1 Dashboard**
- Daily revenue total
- Transaction count
- New customers today
- Pending appointments
- Top services today

**F6.2 Reports**
- Daily revenue breakdown
- Weekly performance summary
- Monthly financial report
- Staff performance rankings
- Top customers by spend
- Top products by quantity
- Top products by revenue
- Inventory valuation report

**F6.3 Export**
- Export to PDF
- Export to Excel
- Email reports
- Schedule automatic reports

### F7: Administration

**F7.1 User Management**
- Create user accounts
- Assign roles and permissions
- Disable/enable users
- Reset passwords
- Track login history

**F7.2 Role & Permission Management**
- Define custom roles
- Assign permissions to roles
- Modify role permissions
- Track permission changes

**F7.3 System Settings**
- Configure business hours
- Set tax rates
- Define reorder points
- Configure notification settings
- Manage service categories

**F7.4 Audit Logging**
- Log all system changes
- Track user actions
- Record payment confirmations
- Maintain audit trail
- Generate audit reports

---

## 7. NON-FUNCTIONAL REQUIREMENTS

### NFR1: Performance

- Page load time < 2 seconds
- API response time < 500ms
- Support 50 concurrent users
- Database queries optimized with proper indexing
- Caching strategy for frequently accessed data

### NFR2: Reliability

- 99.5% uptime SLA
- Automated daily backups
- Point-in-time recovery capability
- Automatic failover for database
- Health check monitoring

### NFR3: Security

- TLS 1.2+ encryption for all data in transit
- AES-256 encryption for sensitive data at rest
- Bcrypt password hashing (minimum 12 rounds)
- JWT tokens for API authentication
- RBAC for authorization
- CSRF protection
- SQL injection prevention via prepared statements
- XSS protection via output encoding
- Rate limiting on sensitive endpoints
- Audit logging for all changes
- GDPR compliance for customer data

### NFR4: Scalability

- Horizontal scaling via containerization
- Database replication for read scaling
- Caching layer for performance
- API versioning for backward compatibility
- Support for 100+ concurrent users (future)

### NFR5: Maintainability

- Code coverage minimum 80%
- Automated testing for all features
- Clean code principles followed
- Comprehensive documentation
- Infrastructure as Code

### NFR6: Usability

- Intuitive UI requiring minimal training
- Responsive design for mobile/tablet/desktop
- Accessibility (WCAG 2.1 AA minimum)
- Dark mode support
- Clear error messages

---

## 8. BUSINESS MODULES

| Module | Purpose | Key Entities | Business Value |
|--------|---------|--------------|-----------------|
| **Appointments** | Manage salon schedules | Appointment, Service, Staff | No-show reduction, staff optimization |
| **POS** | Process transactions | Sale, Payment, LineItem, Invoice | Revenue tracking, upselling |
| **Inventory** | Manage product stock | Product, Stock, Movement | Cost control, availability |
| **Customers** | Manage customer relationships | Customer, LoyaltyPoints | Revenue growth, retention |
| **Staff** | Track performance | StaffMember, Performance, Commission | Labor cost visibility, incentives |
| **Reporting** | Business insights | Reports, Analytics, Dashboard | Data-driven decisions |
| **Administration** | System management | User, Role, Permission, AuditLog | Security, compliance, governance |

---

## 9. BUSINESS RULES

### Appointment Rules

1. Appointments must have minimum 1-hour lead time
2. Appointments cannot be booked more than 30 days in advance
3. Staff member must be active to be assigned appointments
4. Maximum appointment duration is limited by service duration + buffer
5. Duplicate bookings for same staff member at same time prohibited
6. Appointment cancellation within 24 hours should trigger notification

### Payment Rules

1. Payment amount must match transaction total
2. Partial payments track remaining balance
3. M-Pesa payments verified before marking complete
4. Refunds can only be processed by authorized users
5. Refunds must deduct loyalty points proportionally
6. Prepaid appointments release payment on completion

### Inventory Rules

1. Stock cannot go below zero (sales rejected if insufficient)
2. Reorder quantity = max(stock) - current stock at reorder point
3. Expiration date tracking (if applicable)
4. Cost price must be less than selling price
5. Stock movements create audit trail

### Loyalty Rules

1. 1 point awarded per KES 1 spent
2. Tier progression on point threshold:
   - Bronze: 0+ points (0% discount)
   - Silver: 5,000+ points (5% discount)
   - Gold: 10,000+ points (10% discount)
   - Platinum: 25,000+ points (20% discount)
3. Points don't expire (within loyalty program terms)
4. 100 points = KES 10 redemption value

### Staff Rules

1. Commission percentage: 0-100%
2. Commission calculated as percentage of service revenue
3. Staff performance tracked independently
4. No-show rate tracked (completion %)
5. Salary and commission tracked separately

### Security Rules

1. All user actions logged with timestamp and user ID
2. Sensitive data encrypted at rest
3. API requests limited to 100 per minute per user
4. Admin actions require confirmation
5. Deleted records soft-deleted, preserving history

---

## 10. PRIMARY ACTORS

| Actor | Description | Capabilities |
|-------|-------------|--------------|
| **Receptionist** | Front desk staff | Create appointments, process payments, view schedule |
| **Hair Stylist** | Service delivery staff | View appointments, update customer notes, clock in/out |
| **Manager** | Operations management | Create users, modify settings, view reports, process refunds |
| **Owner** | Business owner | Full system access, financial reports, strategic analytics |
| **Customer** | External customer | View appointment history, book appointments (future), manage profile (future) |

---

## 11. USE CASES

### UC1: Book Appointment (Receptionist)

**Actors**: Receptionist, Customer, System  
**Preconditions**: Receptionist logged in, customer information available  
**Steps**:
1. Receptionist selects "New Appointment"
2. Receptionist selects customer
3. Receptionist selects service
4. Receptionist selects staff member
5. Receptionist selects date and time
6. System checks availability
7. Receptionist confirms booking
8. System sends confirmation SMS/email
9. Appointment appears in staff schedule

**Postconditions**: Appointment created, notification sent, customer reminded

### UC2: Process Payment (Receptionist)

**Actors**: Receptionist, Customer, System, M-Pesa  
**Preconditions**: Sale created, amount due known  
**Steps**:
1. Receptionist selects payment method
2. For M-Pesa: Receptionist enters customer phone
3. System initiates M-Pesa STK push
4. Customer enters M-Pesa PIN
5. M-Pesa confirms payment
6. System records payment
7. System generates receipt
8. Invoice marked as paid

**Postconditions**: Payment recorded, inventory updated, receipt printed

### UC3: View Performance Report (Manager)

**Actors**: Manager, System  
**Preconditions**: Manager logged in, sufficient permissions  
**Steps**:
1. Manager navigates to Reports
2. Manager selects date range and report type
3. System generates report
4. Manager views dashboard/chart
5. Manager exports or prints report

**Postconditions**: Report displayed, data available for analysis

### UC4: Refund Transaction (Manager)

**Actors**: Manager, Customer, System, M-Pesa  
**Preconditions**: Original payment recorded, refund authorized  
**Steps**:
1. Manager locates original transaction
2. Manager enters refund reason
3. Manager confirms refund authorization
4. System initiates refund to original payment method
5. System updates inventory (adds back sold products)
6. System returns loyalty points
7. Audit log records refund

**Postconditions**: Payment reversed, inventory restored, notification sent

---

## 12. USER STORIES & ACCEPTANCE CRITERIA

### US1: Appointment Confirmation

**As a** receptionist  
**I want to** confirm pending appointments with one click  
**So that** customers know appointments are secured

**Acceptance Criteria**:
- ✓ Pending appointments listed on dashboard
- ✓ One-click confirmation available
- ✓ Confirmed appointments appear in calendar
- ✓ Staff receives notification

### US2: M-Pesa Payment

**As a** receptionist  
**I want to** process M-Pesa payments without manual verification  
**So that** transactions complete faster

**Acceptance Criteria**:
- ✓ Customer phone number collected
- ✓ STK push sent via Daraja API
- ✓ Payment verified automatically
- ✓ Receipt generated immediately

### US3: Low Stock Alert

**As a** manager  
**I want to** receive alerts when products hit reorder point  
**So that** stock-outs are prevented

**Acceptance Criteria**:
- ✓ Alert notification when stock < reorder point
- ✓ Purchase order template generated
- ✓ Supplier contact information accessible

### US4: Staff Performance Dashboard

**As an** owner  
**I want to** see staff performance metrics in one view  
**So that** I can identify top performers and coach underperformers

**Acceptance Criteria**:
- ✓ Revenue per staff member visible
- ✓ Appointments completed tracked
- ✓ No-show rate calculated
- ✓ Sortable by metric
- ✓ Exportable to Excel

### US5: Customer Loyalty Tier

**As a** receptionist  
**I want to** see customer loyalty tier at POS  
**So that** I can apply automatic tier discounts

**Acceptance Criteria**:
- ✓ Loyalty tier displayed in customer profile
- ✓ Discount automatically applied at checkout
- ✓ Points balance updated after payment
- ✓ Tier status visible on receipt

---

## 13. EXTERNAL INTEGRATIONS

### Integration 1: M-Pesa Daraja API

**Purpose**: Process mobile money payments  
**Integration Points**: 
- STK push (payment initiation)
- Transaction query (verification)
- Refund processing

**Data Flow**: Payment request → Daraja API → M-Pesa Gateway → Customer Phone → Callback verification

**Error Handling**: Automatic retry with exponential backoff, manual verification option

### Integration 2: SMS Gateway

**Purpose**: Send appointment reminders and confirmations  
**Integration Points**:
- Appointment reminders (24h before)
- Booking confirmations
- Payment confirmations
- Promotional messages

**Data Flow**: Transaction event → SMS service → Telecommunications network → Customer phone

**Error Handling**: Retry failed messages, log delivery status, escalate critical failures

### Integration 3: Email Service

**Purpose**: Send receipts, invoices, reports  
**Integration Points**:
- Payment receipts
- Invoice delivery
- Report distribution
- Account notifications

**Data Flow**: Transaction event → Email service → SMTP → Customer email

**Error Handling**: Queue failed emails, retry daily, provide manual resend option

---

## 14. TECHNOLOGY STACK

| Layer | Technology | Version |
|-------|-----------|---------|
| **Language** | PHP | 8.3+ |
| **Web Server** | Apache/Nginx | 2.4+/Latest |
| **Database** | MySQL | 8.0+ |
| **Cache** | Redis | 7.0+ |
| **Frontend** | HTML5/CSS3/JavaScript | ES2022+ |
| **Framework** | Bootstrap | 5.3+ |
| **API Architecture** | REST + JSON | v1 |
| **Authentication** | JWT | HS256 |
| **Testing** | PHPUnit | 10.0+ |
| **Containerization** | Docker | Latest |
| **Orchestration** | Docker Compose | Latest |

---

## 15. DEPLOYMENT TARGETS

### Environment 1: Development

- Local machine
- Docker Compose
- SQLite or local MySQL
- Mock external services

### Environment 2: Staging

- AWS EC2 or similar VPS
- Docker containers
- MySQL RDS
- Test M-Pesa integration
- Full feature parity with production

### Environment 3: Production

- AWS or Kenyan hosting provider
- Kubernetes or Docker Swarm
- MySQL with replication
- Live M-Pesa integration
- CDN for static assets
- SSL/TLS certificates
- Database backups every 6 hours
- Application monitoring and alerting

---

## 16. FUTURE ENHANCEMENTS

### Phase 2

- Customer portal for self-service booking
- Mobile app (iOS/Android)
- SMS-based booking
- Advanced reporting with date ranges
- Custom report builder

### Phase 3

- Email marketing integration
- Customer feedback/ratings
- Inventory forecasting
- Supplier management
- Multi-location support

### Phase 4

- AI-powered recommendations
- Video consultations
- Social media integration
- Advanced analytics
- Third-party integrations (accounting, HR)

---

## 17. TRACEABILITY MATRIX

| Requirement | Module | API | Frontend | Test | Status |
|-------------|--------|-----|----------|------|--------|
| F1.1 Appointment Booking | Appointments | ✓ | ⟳ | ✓ | In Progress |
| F1.2 Appointment Scheduling | Appointments | ✓ | ⟳ | ✓ | In Progress |
| F1.3 Appointment Cancellation | Appointments | ✓ | ⟳ | ✓ | In Progress |
| F1.4 Appointment Reminders | Appointments | ✓ | N/A | ✓ | In Progress |
| F2.1 Transaction Creation | POS | ✓ | ⟳ | ✓ | In Progress |
| F2.2 Payment Processing | POS | ✓ | ⟳ | ✓ | In Progress |
| F2.3 Receipt & Invoice | POS | ✓ | ⟳ | ✓ | In Progress |
| F2.4 Refunds | POS | ✓ | ⟳ | ✓ | In Progress |
| F3.1 Product Catalog | Inventory | ✓ | ⟳ | ⟳ | Planned |
| F3.2 Stock Tracking | Inventory | ✓ | ⟳ | ⟳ | Planned |
| F4.1 Customer Profiles | Customers | ✓ | ⟳ | ✓ | In Progress |
| F4.2 Loyalty Program | Customers | ✓ | ⟳ | ✓ | In Progress |
| F7.1 User Management | Admin | ✓ | ⟳ | ⟳ | In Progress |

Legend: ✓ = Complete | ⟳ = In Progress | ⟲ = Planned | N/A = Not Applicable

---

**END OF PROJECT_SPECIFICATION.md**
