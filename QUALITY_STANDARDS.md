# QUALITY_STANDARDS.md

**Aurora Platform - Code Quality & Testing Standards**

Version: 1.0.0  
Status: Active  
Last Updated: 2026-07-28

---

## 1. CODE QUALITY STANDARDS

### PHP Code Standards

**Coding Standard**: PSR-12 (Extended PSR-2)

```bash
# Validate compliance
vendor/bin/phpcs --standard=PSR12 src/

# Auto-fix violations
vendor/bin/phpcbf --standard=PSR12 src/
```

**Requirements**:
- ✓ 4-space indentation (no tabs)
- ✓ Line length max 120 characters
- ✓ Declare strict types in all files: `declare(strict_types=1);`
- ✓ Use type hints for all parameters and return types
- ✓ PHPDoc blocks for all public methods
- ✓ No trailing whitespace
- ✓ Single blank line between methods

**Example**:
```php
<?php
declare(strict_types=1);

namespace App\Application\Services;

class BookingService {
    /**
     * Book an appointment for a customer.
     *
     * @param int $customerId Customer ID
     * @param int $serviceId Service ID
     * @param int $staffId Staff ID
     * @param string $startTime ISO-8601 datetime
     * @return int Appointment ID
     * @throws AppointmentConflictException
     */
    public function bookAppointment(
        int $customerId,
        int $serviceId,
        int $staffId,
        string $startTime
    ): int {
        // Implementation
    }
}
```

### Frontend Code Standards

**JavaScript**: Airbnb Style Guide

```bash
npm run lint:js
npm run lint:js:fix
```

**CSS**: BEM (Block Element Modifier)

```css
/* Good */
.appointment-card {}
.appointment-card__header {}
.appointment-card__header--confirmed {}

/* Avoid */
.appointmentCard {}
.appointment .header {}
.confirmed-appointment-header {}
```

### Code Review Requirements

**All code requires**:
- [ ] 1 approval from peer reviewer
- [ ] PHPCS compliance 100% (PHP)
- [ ] ESLint passing (JavaScript)
- [ ] No security issues (SonarQube)
- [ ] Test coverage for new code 80%+
- [ ] No commented-out code
- [ ] No console.log/var_dump left in code

---

## 2. TESTING STANDARDS

### Unit Testing

**Framework**: PHPUnit 10.0+

**Coverage Targets**:
- All service classes: 80%+ coverage
- All validator classes: 100% coverage
- Domain models: 70%+ coverage
- Controllers: 60%+ coverage

**Test File Naming**:
```
src/Application/Services/BookingService.php
→ tests/Unit/Services/BookingServiceTest.php
```

**Test Example**:
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Application\Services\BookingService;
use App\Domain\Repositories\AppointmentRepository;
use PHPUnit\Framework\TestCase;

class BookingServiceTest extends TestCase {
    private BookingService $service;
    private AppointmentRepository $repository;

    protected function setUp(): void {
        $this->repository = $this->createMock(AppointmentRepository::class);
        $this->service = new BookingService($this->repository);
    }

    public function testBookAppointmentSuccessfully(): void {
        $customerId = 1;
        $serviceId = 2;
        $staffId = 3;
        $startTime = '2026-08-01T10:00:00Z';

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->willReturn(42);

        $appointmentId = $this->service->bookAppointment(
            $customerId,
            $serviceId,
            $staffId,
            $startTime
        );

        $this->assertEquals(42, $appointmentId);
    }

    public function testBookAppointmentThrowsExceptionForConflict(): void {
        $this->expectException(AppointmentConflictException::class);

        // Setup conflict scenario
        $this->service->bookAppointment(...);
    }
}
```

**Run Tests**:
```bash
# Run all tests
vendor/bin/phpunit

# Run specific test class
vendor/bin/phpunit tests/Unit/Services/BookingServiceTest.php

# Generate coverage report
vendor/bin/phpunit --coverage-html=coverage/

# Check coverage
vendor/bin/phpunit --coverage-text
```

### Integration Testing

**Purpose**: Test API endpoints with real database

**Test Structure**:
```php
class AppointmentIntegrationTest extends TestCase {
    use RefreshDatabase;  // Refresh DB between tests

    public function testCreateAppointmentViaAPI(): void {
        $response = $this->post('/api/appointments', [
            'customerId' => 1,
            'serviceId' => 2,
            'staffId' => 3,
            'startTime' => '2026-08-01T10:00:00Z',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['id', 'status']]);
        $this->assertDatabaseHas('appointments', [
            'customer_id' => 1,
            'status' => 'pending',
        ]);
    }
}
```

### End-to-End Testing

**Future Implementation**: Selenium or Cypress

**Planned Scenarios**:
- User login and authentication
- Book appointment workflow
- Process payment with M-Pesa
- Generate and print receipt
- View customer history

---

## 3. PERFORMANCE STANDARDS

### API Response Time

| Endpoint | Target | Max |
|----------|--------|-----|
| **Login** | <200ms | 500ms |
| **List (pagination)** | <300ms | 1000ms |
| **Get single** | <100ms | 300ms |
| **Create** | <200ms | 500ms |
| **Update** | <200ms | 500ms |
| **Delete** | <100ms | 300ms |
| **Report** | <5000ms | 10000ms |

**Performance Testing**:
```bash
# Apache Bench (simple)
ab -n 1000 -c 50 http://localhost:8080/api/appointments

# Load testing
locust -f locustfile.py --headless -u 100 -r 10

# Query time analysis
EXPLAIN SELECT * FROM appointments WHERE staff_id = 3 AND start_time = '2026-08-01 10:00:00';
```

### Database Performance

**Query Time Targets**:
- Simple queries (<1000 rows): <50ms
- Complex queries (JOINs): <200ms
- Aggregation queries: <500ms

**Indexing Requirements**:
```sql
-- Check slow queries
SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;

-- Create indexes for slow queries
CREATE INDEX idx_appointments_staff_time ON appointments(staff_id, start_time);
```

### Frontend Performance

**Page Load Targets**:
- HTML: <500ms
- CSS: <200ms
- JavaScript: <500ms
- Total page: <2 seconds

**Lighthouse Audit Targets**:
- Performance: 80+
- Accessibility: 90+
- Best Practices: 90+
- SEO: 90+

---

## 4. SECURITY STANDARDS

### OWASP Top 10 Prevention

| Vulnerability | Prevention | Testing |
|---------------|-----------|---------|
| **Injection** | Prepared statements | SQL injection test |
| **Broken Auth** | JWT tokens, RBAC | Auth bypass test |
| **XSS** | Output encoding | DOM XSS test |
| **CSRF** | Token verification | CSRF token test |
| **Insecure Deserial** | No eval() | Code review |
| **Broken Access** | Permission checks | Privilege escalation test |
| **Crypto Failures** | TLS 1.2+, AES-256 | SSL/TLS scan |
| **Data Exposure** | Encryption at rest | Encryption audit |
| **API Misconfig** | Rate limiting | DDoS test |
| **Logging Failures** | Audit trail | Log verification |

### Security Checklist

**Before Production**:
- [ ] No hardcoded secrets (credentials in env vars only)
- [ ] HTTPS/TLS configured
- [ ] Password hashing: bcrypt 12+ rounds
- [ ] SQL: All queries use prepared statements
- [ ] XSS: All output HTML-encoded
- [ ] CSRF: Token validation on state changes
- [ ] CORS: Configured for specific origins
- [ ] Rate limiting: Implemented on APIs
- [ ] Audit logging: All critical actions logged
- [ ] Secrets scanning: No credentials in git history

**Security Testing**:
```bash
# Scan for secrets in git history
git log -p | grep -i password
git log -p | grep -i secret

# Check for hardcoded credentials
grep -r "password" src/ --include="*.php"
grep -r "API_KEY" src/ --include="*.php"

# Verify prepared statements
grep -r "query(" src/ --include="*.php" | grep -v "?"
```

---

## 5. DOCUMENTATION STANDARDS

### Code Comments

**Rule**: Comment the WHY, not the WHAT

```php
// ✓ Good - explains business logic
// Minimum 1-hour lead time ensures staff has preparation time
if ($now->addHours(1) > $requestedTime) {
    throw new InvalidBookingException('Minimum 1-hour lead time required');
}

// ✗ Bad - restates the code
// Add 1 hour to current time
$minTime = $now->addHours(1);
```

### API Documentation

**Format**: OpenAPI 3.0 / Swagger

**Requirement**: All endpoints documented with:
- Purpose and description
- Request parameters and body
- Response examples (200, 400, 401, 403, 404, 500)
- Permission requirements
- Rate limit info

### Database Documentation

**Requirement**: Database dictionary maintained for:
- Table purpose
- Column descriptions
- Relationships and constraints
- Sample queries
- Performance notes

---

## 6. RELEASE QUALITY GATES

### Pre-Release Checklist

**Code Quality**:
- [ ] PHPCS compliance 100%
- [ ] Unit test coverage ≥80%
- [ ] Integration tests passing
- [ ] No code duplicates >5%
- [ ] No critical static analysis issues
- [ ] No TODO/FIXME comments

**Functionality**:
- [ ] All acceptance criteria met
- [ ] Manual testing completed
- [ ] No regressions in existing features
- [ ] Error handling complete
- [ ] Performance targets met

**Security**:
- [ ] Security audit passed
- [ ] No SQL injection vulnerabilities
- [ ] No XSS vulnerabilities
- [ ] No hardcoded credentials
- [ ] Encryption validated

**Operations**:
- [ ] Database migrations tested
- [ ] Backup procedures tested
- [ ] Rollback tested
- [ ] Monitoring configured
- [ ] Documentation updated

### Release Approval

**Approval Required From**:
1. Lead Developer (code quality)
2. QA Lead (testing)
3. DevOps Lead (operations)
4. Product Owner (functionality)
5. Security Lead (security)

**Approval Process**:
```
Code → Review → Test → Security → DevOps → Approval → Deploy
```

---

## 7. STATIC ANALYSIS

**Tools**:
- PHPStan (static analysis)
- SonarQube (code quality)
- PHPCS (style)
- PHPMutate (mutation testing)

**Run All**:
```bash
#!/bin/bash
echo "Running static analysis..."

# PHPCS
vendor/bin/phpcs --standard=PSR12 src/

# PHPStan
vendor/bin/phpstan analyse src/

# SonarQube (if configured)
sonar-scanner \
  -Dsonar.projectKey=aurora-platform \
  -Dsonar.sources=src \
  -Dsonar.host.url=http://sonarqube:9000

echo "✓ Analysis complete"
```

---

## 8. TESTING COVERAGE TARGETS

| Component | Target | Phase 1 |
|-----------|--------|---------|
| **Domain Models** | 70% | 60% |
| **Services** | 85% | 80% |
| **Controllers** | 60% | 50% |
| **Repositories** | 70% | 60% |
| **Validators** | 100% | 95% |
| **Overall** | 75% | 60% |

**Generate Coverage Report**:
```bash
vendor/bin/phpunit --coverage-html=build/coverage --coverage-clover=build/clover.xml
open build/coverage/index.html
```

---

## 9. CI/CD VALIDATION

**GitHub Actions Pipeline** validates:

1. **Build** (2 min)
   - Docker image builds successfully
   - No build errors

2. **Lint** (1 min)
   - PHPCS compliance
   - ESLint compliance

3. **Unit Tests** (5 min)
   - All tests pass
   - Coverage ≥60%

4. **Integration Tests** (5 min)
   - API endpoints work
   - Database integration works

5. **Security Scan** (3 min)
   - No known vulnerabilities
   - OWASP Top 10 checks

6. **Performance** (3 min)
   - API response times acceptable
   - No memory leaks

**Failure Response**:
- PR blocked if any check fails
- Automatic email to developer
- Cannot merge without fixes

---

## 10. QUALITY METRICS DASHBOARD

**Weekly Review**:
```
Test Coverage:    [=====>     ] 72% (Target: 75%)
Code Quality:     [========>  ] 85/100
Performance:      [===>       ] 350ms avg (Target: 300ms)
Security Issues:  [✓] 0 critical
Deployment Rate:  [✓] 5 per week
Incident Rate:    [✓] 0 critical this week
```

---

**END OF QUALITY_STANDARDS.md**

**Version**: 1.0.0  
**Last Updated**: 2026-07-28  
**Owner**: QA Lead
