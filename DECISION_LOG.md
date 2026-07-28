# DECISION_LOG.md

**Aurora Platform - Architectural Decision Records**

Version: 1.0.0  
Status: Active  
Last Updated: 2026-07-28

---

## ADR-001: Adopt Domain-Driven Design

**Date**: 2026-07-15  
**Status**: Accepted  
**Owner**: Architect

**Context**:
Aurora Platform handles complex business logic (appointments, payments, inventory, loyalty) requiring clear separation between business rules and technical implementation.

**Decision**:
Adopt Domain-Driven Design (DDD) with layered architecture consisting of:
- Presentation Layer (REST API, WebUI)
- Application Layer (Controllers, Services, Validators)
- Domain Layer (Entities, Value Objects, Repositories)
- Infrastructure Layer (Database, Integrations)

**Rationale**:
- Complex business domain benefits from domain modeling
- Clear separation of concerns enables maintainability
- Domain layer can be tested independently
- Business logic isolated from framework changes
- Enables future microservices decomposition

**Alternatives Considered**:
1. Traditional MVC - lacks domain abstraction
2. Microservices - premature at this stage
3. CQRS - too complex for initial release

**Consequences**:
- ✓ Business logic testable without database
- ✓ Clear understanding of domain concepts
- ✓ Easy to maintain and extend
- ✗ More code initially (more abstraction layers)
- ✗ Steeper learning curve for new developers

**Related Decisions**: ADR-004 (Repository Pattern), ADR-005 (Service Layer)

---

## ADR-002: MySQL as Primary Database

**Date**: 2026-07-15  
**Status**: Accepted  
**Owner**: Database Architect

**Context**:
Need to select database for Aurora Platform. Options include MySQL, PostgreSQL, and MongoDB.

**Decision**:
Use MySQL 8.0+ with InnoDB storage engine as primary database.

**Rationale**:
- Widely supported and mature technology
- Good performance for relational data
- Excellent ACID compliance (InnoDB)
- Team familiarity with MySQL
- Strong support for business logic via constraints
- Cost-effective (open source)
- Suitable for Kenya-based hosting (abundant support)

**Alternatives Considered**:
1. PostgreSQL - more powerful, less team familiarity
2. MongoDB - NoSQL not suitable for relational business data
3. Cloud databases (RDS) - evaluated for Phase 2

**Consequences**:
- ✓ Proven, stable platform
- ✓ Good performance with proper indexing
- ✓ ACID guarantees for financial transactions
- ✗ Requires careful schema design
- ✗ Vertical scaling limits (addressed in Phase 2)

**Related Decisions**: ADR-010 (Backup Strategy)

---

## ADR-003: JWT for Authentication

**Date**: 2026-07-15  
**Status**: Accepted  
**Owner**: Security Architect

**Context**:
Need stateless authentication for horizontally scalable REST API.

**Decision**:
Implement JWT (JSON Web Token) authentication with HS256 algorithm.

**Rationale**:
- Stateless authentication enables horizontal scaling
- No server-side session storage needed (Redis only for optional rate limiting)
- Widely supported in modern frameworks
- Compact and efficient for mobile/API use
- Compatible with microservices architecture (Phase 2)

**Token Specification**:
- Algorithm: HS256 (HMAC with SHA-256)
- Expiration: 24 hours
- Claims: userId, roles, permissions, exp, iat
- Refresh: No refresh token (re-login required)

**Alternatives Considered**:
1. Session-based cookies - not suitable for API/mobile
2. OAuth 2.0 - unnecessary complexity for internal auth
3. Refresh tokens - session management complexity

**Consequences**:
- ✓ Stateless architecture
- ✓ Scales horizontally
- ✓ Mobile-friendly
- ✗ Cannot revoke token before expiration (24h max impact)
- ✗ Larger token size than sessions

**Mitigation**:
- 24-hour expiration limits damage from token compromise
- Future Phase 2: JWT refresh tokens with rotation
- Audit logging of sensitive operations

**Related Decisions**: ADR-011 (RBAC Implementation)

---

## ADR-004: Repository Pattern

**Date**: 2026-07-15  
**Status**: Accepted  
**Owner**: Architect

**Context**:
Domain layer should not depend on database implementation details.

**Decision**:
Implement Repository Pattern with:
- Interfaces defined in domain layer
- Implementations in infrastructure layer
- One repository per aggregate root
- In-memory implementations for testing

**Rationale**:
- Domain logic decoupled from persistence technology
- Easy to test with mock/in-memory repositories
- Can switch databases without changing business logic
- Clear data access contracts
- Natural fit with DDD

**Alternatives Considered**:
1. ORM (Doctrine, Eloquent) - tight coupling to ORM
2. Active Record - couples domain and persistence
3. Direct SQL queries - no abstraction

**Consequences**:
- ✓ Testable domain logic
- ✓ Database-agnostic business logic
- ✓ Clear data access contracts
- ✗ More code (one interface + multiple implementations)
- ✗ Potential performance overhead (mitigated by proper indexing)

**Related Decisions**: ADR-001 (DDD), ADR-002 (MySQL)

---

## ADR-005: Service Layer for Business Logic

**Date**: 2026-07-15  
**Status**: Accepted  
**Owner**: Architect

**Context**:
Where should business logic live - entities, controllers, or separate layer?

**Decision**:
Create Application Service Layer for:
- Use case orchestration (coordinate domain models)
- Transaction management
- Cross-cutting concerns (logging, validation)
- Event publishing

**Service Examples**:
- BookingService: Orchestrates appointment creation
- PaymentService: Coordinates payment processing
- InventoryService: Manages stock levels

**Rationale**:
- Services coordinate multiple domain objects
- Keeps controllers thin (HTTP concerns only)
- Reusable business logic across endpoints
- Transaction boundaries clearly defined
- Testable without HTTP or database

**Alternatives Considered**:
1. Fat controllers - mixing concerns
2. Fat models - violating single responsibility
3. Anemic services - no business logic

**Consequences**:
- ✓ Clear use case implementations
- ✓ Business logic testable in isolation
- ✓ Reusable across endpoints
- ✓ Transaction management clear
- ✗ Additional layer of abstraction

**Related Decisions**: ADR-001 (DDD), ADR-004 (Repository)

---

## ADR-006: M-Pesa Integration Architecture

**Date**: 2026-07-20  
**Status**: Accepted  
**Owner**: Integration Architect

**Context**:
Aurora Platform must integrate with M-Pesa for payment processing. How to structure the integration?

**Decision**:
Create MpesaGateway class in infrastructure layer that:
- Adapts Daraja API to domain payment concepts
- Handles authentication, retries, and error handling
- Supports STK push, transaction query, and refunds
- Implements circuit breaker pattern for resilience

**Implementation**:
```php
MpesaGateway {
  initiateStkPush(phone, amount, reference)
  queryTransactionStatus(checkoutRequestId)
  processRefund(transactionId, amount)
  verifyCallback(payload) // Webhook handler
}
```

**Rationale**:
- Adapter pattern encapsulates external API changes
- Circuit breaker handles Daraja API outages
- Retry logic handles transient failures
- Clear contract between domain and external systems
- Easy to mock for testing
- Future: Switch to different payment provider

**Alternatives Considered**:
1. Direct Daraja API calls in service - tight coupling
2. Third-party payment library - loss of control
3. In-process payment - not reliable

**Consequences**:
- ✓ Resilient to M-Pesa API changes
- ✓ Clear error handling
- ✓ Testable without real M-Pesa
- ✗ Requires understanding of Daraja API
- ✗ Callback handling complexity

**Related Decisions**: ADR-004 (Repository), ADR-005 (Services)

**Risks**:
- M-Pesa API changes require gateway updates
- Daraja API outages impact system (mitigated by circuit breaker)
- Callback verification essential for security

---

## ADR-007: Soft Deletes Strategy

**Date**: 2026-07-18  
**Status**: Accepted  
**Owner**: Database Architect

**Context**:
Business data must never be permanently deleted for compliance and audit requirements.

**Decision**:
Implement soft deletes via `deleted_at` timestamp on business entities:
- User, Customer, Appointment, Staff, Service, Product, Sales
- Queries filter deleted records by default
- Audit trail tables (audit_logs, stock_movements) never support delete

**Implementation**:
```sql
-- Soft delete
UPDATE users SET deleted_at = NOW() WHERE id = 1;

-- Restore
UPDATE users SET deleted_at = NULL WHERE id = 1;

-- Query excluding deleted (default)
SELECT * FROM users WHERE deleted_at IS NULL;
```

**Rationale**:
- Compliance requirement: data retention for audits
- Preserves relationships and history
- Can restore accidentally deleted data
- Audit trail remains intact
- Better than permanent delete

**Alternatives Considered**:
1. Permanent delete - loses history
2. Separate archive table - data duplication
3. No delete capability - poor UX

**Consequences**:
- ✓ Audit trail preserved
- ✓ Can restore data
- ✓ Compliance-friendly
- ✗ "Deleted" data takes disk space
- ✗ Queries must filter deleted_at

**Mitigation**:
- Archive and delete after 7 years (configurable)
- Use archive table for long-term storage
- Database maintenance: periodic purge of truly old data

**Related Decisions**: ADR-009 (Audit Logging)

---

## ADR-008: Docker for Containerization

**Date**: 2026-07-18  
**Status**: Accepted  
**Owner**: DevOps Architect

**Context**:
Need consistent environments across development, staging, and production.

**Decision**:
Use Docker + Docker Compose for:
- Multi-stage builds (optimized production images)
- Local development via docker-compose.yml
- Production deployment via Docker containers
- Kubernetes ready for Phase 2 scaling

**Stack**:
```yaml
services:
  nginx: # Reverse proxy, static files
  php: # Application runtime
  mysql: # Database
  redis: # Cache and sessions
```

**Rationale**:
- Consistency: Same environment dev → prod
- Isolation: Services don't interfere
- Scalability: Containers scale horizontally
- Reproducibility: No "it works on my machine"
- Industry standard for cloud deployments

**Alternatives Considered**:
1. Virtual machines - overhead, less portable
2. Bare metal - manual setup, inconsistent
3. Serverless - not suitable for persistent data

**Consequences**:
- ✓ Consistent environments
- ✓ Easy onboarding for new developers
- ✓ Ready for cloud deployment
- ✗ Docker learning curve
- ✗ Resource overhead vs bare metal
- ✗ Networking complexity in development

**Related Decisions**: ADR-013 (CI/CD Pipeline)

---

## ADR-009: Comprehensive Audit Logging

**Date**: 2026-07-18  
**Status**: Accepted  
**Owner**: Security Architect

**Context**:
Business, compliance, and security require tracking all system changes.

**Decision**:
Implement audit_logs table capturing:
- All CREATE, UPDATE, DELETE operations
- User who made the change
- Exact values before/after change
- Timestamp and IP address
- Request context (user agent, request ID)

**Rationale**:
- Compliance requirement (audit trail)
- Security: Detect unauthorized access
- Troubleshooting: Understand what changed
- Non-repudiation: Who did what when
- Regulatory: Evidence of controls

**Alternatives Considered**:
1. No audit trail - non-compliant
2. Application logs only - not queryable
3. Database triggers - complex maintenance

**Consequences**:
- ✓ Full audit trail for compliance
- ✓ Queryable, structured data
- ✓ Security event detection
- ✗ Audit table grows large
- ✗ Some performance overhead

**Mitigation**:
- Archive audit logs after 1 year
- Index on common queries (user, resource_type, created_at)
- Batch insert for bulk operations

**Related Decisions**: ADR-007 (Soft Deletes)

---

## ADR-010: Daily Automated Backups

**Date**: 2026-07-18  
**Status**: Accepted  
**Owner**: DevOps Architect

**Context**:
Data loss would be catastrophic. Need reliable backup and recovery.

**Decision**:
Implement automated backup strategy:
- Full daily backup at 2 AM (off-peak)
- Hourly incremental backups
- 30-day retention for daily backups
- 12-month retention for monthly archives
- Automated testing of restores monthly

**Rationale**:
- Daily backup captures recent data
- Automated = reliable, consistent
- Off-peak = minimal performance impact
- Monthly restore test = proven recovery
- Compliance requirement

**Alternatives Considered**:
1. On-demand backups - unreliable (often skipped)
2. Real-time replication - expensive
3. No backups - catastrophic risk

**Consequences**:
- ✓ Disaster recovery capability
- ✓ Compliance met
- ✓ Peace of mind
- ✗ Storage costs
- ✗ Recovery time 1-2 hours typical

**Recovery SLA**: 2 hours RPO (Recovery Point Objective), 4 hours RTO (Recovery Time Objective)

**Related Decisions**: ADR-013 (Monitoring), ADR-014 (Disaster Recovery)

---

## ADR-011: Role-Based Access Control (RBAC)

**Date**: 2026-07-18  
**Status**: Accepted  
**Owner**: Security Architect

**Context**:
Different users need different system capabilities. How to manage permissions?

**Decision**:
Implement RBAC with hierarchy:
- Users → Roles → Permissions
- Predefined roles: Owner, Manager, Staff, Receptionist
- Granular permissions per resource and action
- Permission checks via middleware on all endpoints

**Rationale**:
- Flexible: Can define custom roles
- Scalable: Add permissions without code changes
- Auditable: Track who has what access
- Industry standard

**Alternatives Considered**:
1. ABAC (Attribute-based) - too complex for MVP
2. Hard-coded permissions - inflexible
3. No access control - security risk

**Consequences**:
- ✓ Fine-grained access control
- ✓ Easy to audit permissions
- ✓ Flexible role definitions
- ✗ Permission setup overhead
- ✗ Potential for misconfiguration

**Role Definitions**:
- Owner: All permissions (*)
- Manager: Read all, manage staff/settings, process refunds
- Staff: View own schedule, update customer notes
- Receptionist: Create appointments, process payments, view customers

**Related Decisions**: ADR-003 (JWT Authentication)

---

## ADR-012: Layered Testing Pyramid

**Date**: 2026-07-20  
**Status**: Accepted  
**Owner**: QA Lead

**Context**:
Need balanced approach to testing for speed and confidence.

**Decision**:
Testing pyramid:
- **Unit Tests** (70%): Domain logic, services, validators
- **Integration Tests** (20%): API endpoints with real database
- **E2E Tests** (10%): Critical user workflows

**Rationale**:
- Unit tests: Fast feedback, isolate issues
- Integration tests: Verify API contracts
- E2E tests: Verify user experience
- Balanced: Speed + Confidence

**Alternatives Considered**:
1. All E2E tests - slow, brittle
2. Only unit tests - miss integration issues
3. Manual testing only - slow, unreliable

**Consequences**:
- ✓ Fast CI/CD pipeline (unit tests)
- ✓ Good coverage of API contracts
- ✓ Confidence in critical workflows
- ✗ Some code paths not covered

**Coverage Targets**:
- Services: 85%+
- Validators: 100%
- Controllers: 60%+
- Overall: 75%+

**Related Decisions**: ADR-013 (CI/CD Pipeline)

---

## ADR-013: GitHub Actions for CI/CD

**Date**: 2026-07-20  
**Status**: Accepted  
**Owner**: DevOps Architect

**Context**:
Need automated testing and deployment pipeline.

**Decision**:
Use GitHub Actions for:
- Build on every push
- Test on every PR
- Deploy to staging on merge to develop
- Deploy to production on merge to main

**Pipeline Stages**:
1. Build (Docker image)
2. Lint (PHPCS, ESLint)
3. Unit Tests (PHPUnit)
4. Integration Tests (API + Database)
5. Security Scan
6. Performance Test
7. Deploy (staging/production)

**Rationale**:
- Native GitHub integration
- No additional infrastructure needed
- Free for public/private repos
- Familiar to most developers
- Easy to extend

**Alternatives Considered**:
1. Jenkins - requires management overhead
2. CircleCI - third-party SaaS
3. GitLab CI - switching platforms

**Consequences**:
- ✓ Automated quality gates
- ✓ Catches bugs before deployment
- ✓ Enforces code standards
- ✗ GitHub vendor lock-in
- ✗ Slow first run (Docker build)

**Related Decisions**: ADR-012 (Testing Pyramid), ADR-008 (Docker)

---

## ADR-014: Disaster Recovery Plan

**Date**: 2026-07-22  
**Status**: Accepted  
**Owner**: Business Continuity Lead

**Context**:
Business-critical system requires disaster recovery planning.

**Decision**:
Implement disaster recovery with:
- **RPO** (Recovery Point Objective): 1 hour
- **RTO** (Recovery Time Objective): 4 hours
- Daily backups with 30-day retention
- Monthly restore testing
- Documented runbooks for each failure scenario
- 24/7 on-call rotation post-launch

**Failure Scenarios**:
1. Database corruption: Restore from daily backup
2. Data center failure: Fail over to secondary (Phase 2)
3. Application bug: Deploy previous version
4. Security breach: Activate incident response
5. Extended outage: Notify customers, provide updates

**Rationale**:
- Planned recovery = faster response
- Tested procedures = proven effectiveness
- Clear responsibilities = accountability
- Customer communication plan = trust

**Alternatives Considered**:
1. No DR plan - unprepared for disaster
2. Real-time replication - expensive for MVP
3. Manual recovery - slow, error-prone

**Consequences**:
- ✓ Prepared for disasters
- ✓ Faster recovery
- ✓ Reduced data loss
- ✗ Ongoing management effort
- ✗ Testing overhead

**Related Decisions**: ADR-010 (Backups), ADR-013 (Monitoring)

---

## ADR-015: Open-Closed Architecture

**Date**: 2026-07-22  
**Status**: Accepted  
**Owner**: Architect

**Context**:
Future phases will add new features. System should be extensible.

**Decision**:
Design system to be:
- **Open for Extension**: Add new services, repositories, integrations
- **Closed for Modification**: Existing code doesn't change
- Plugin architecture for future integrations
- Event-driven for side effects

**Rationale**:
- Minimize regression risk when adding features
- Reduce impact of new requirements
- Enable third-party extensions (Phase 4)
- Supports microservices transition

**Consequences**:
- ✓ Extensible architecture
- ✓ Lower risk of regressions
- ✓ Ready for microservices
- ✗ More abstraction layers
- ✗ Potential performance overhead

**Related Decisions**: ADR-001 (DDD), ADR-005 (Services), ADR-009 (Events)

---

**END OF DECISION_LOG.md**

**Document Status**: Active  
**Review Cycle**: Quarterly  
**Next Review**: 2026-10-28
