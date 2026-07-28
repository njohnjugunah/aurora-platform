# Module Registry

**Aurora Platform - Complete Module Inventory**

Version: 1.0.0  
Last Updated: 2026-07-28  
Status: Phase 1 - Controllers Complete

---

## DOMAIN LAYER MODULES

### Models (6 modules)
- `App\Domain\Models\User` - System user with authentication
- `App\Domain\Models\Customer` - Beauty client profile
- `App\Domain\Models\Appointment` - Service booking
- `App\Domain\Models\Service` - Beauty service offering
- `App\Domain\Models\Staff` - Staff member profile
- `App\Domain\Models\Sale` - Transaction record

### Repositories (9 modules)
- `App\Domain\Repositories\UserRepository` - User data access interface
- `App\Domain\Repositories\CustomerRepository` - Customer data access interface
- `App\Domain\Repositories\AppointmentRepository` - Appointment data access interface
- `App\Domain\Repositories\ServiceRepository` - Service data access interface
- `App\Domain\Repositories\StaffRepository` - Staff data access interface
- `App\Domain\Repositories\SaleRepository` - Sale data access interface
- `App\Domain\Repositories\PaymentRepository` - Payment data access interface
- `App\Domain\Repositories\StockRepository` - Stock data access interface
- `App\Domain\Repositories\LoyaltyRepository` - Loyalty data access interface

---

## APPLICATION LAYER MODULES

### Controllers (10 modules) ✓ COMPLETE
- `App\Application\Controllers\AppointmentController` - Appointment CRUD + cancel
- `App\Application\Controllers\CustomerController` - Customer CRUD
- `App\Application\Controllers\ServiceController` - Service CRUD
- `App\Application\Controllers\StaffController` - Staff read + performance
- `App\Application\Controllers\SaleController` - Sale CRUD + payment + refund
- `App\Application\Controllers\PaymentController` - Payment operations
- `App\Application\Controllers\UserController` - User management
- `App\Application\Controllers\InventoryController` - Inventory management
- `App\Application\Controllers\LoyaltyController` - Loyalty programs
- `App\Application\Controllers\AuthController` - Authentication

### Services (8 modules) ⚙️ 65% COMPLETE
- `App\Application\Services\AuthenticationService` - User login (90%)
- `App\Application\Services\JWTService` - Token generation (100%)
- `App\Application\Services\BookingService` - Appointment logic (85%)
- `App\Application\Services\AvailabilityService` - Availability checking (80%)
- `App\Application\Services\PaymentService` - Payment processing (70%)
- `App\Application\Services\InventoryService` - Stock management (75%)
- `App\Application\Services\LoyaltyService` - Loyalty management (80%)
- `App\Application\Services\NotificationService` - Notifications (70%)

### Validators (5 modules) ✓ COMPLETE
- `App\Application\Validators\LoginValidator` - Login validation
- `App\Application\Validators\AppointmentValidator` - Appointment validation
- `App\Application\Validators\PaymentValidator` - Payment validation
- `App\Application\Validators\CustomerValidator` - Customer validation
- `App\Application\Validators\InventoryValidator` - Inventory validation

### Exceptions (5 modules) ✓ COMPLETE
- `App\Application\Exceptions\ValidationException` - Input validation errors
- `App\Application\Exceptions\InvalidBookingException` - Booking logic errors
- `App\Application\Exceptions\AppointmentConflictException` - Schedule conflicts
- `App\Application\Exceptions\PaymentException` - Payment errors (scaffolded)
- `App\Application\Exceptions\InsufficientStockException` - Stock errors (scaffolded)

---

## INFRASTRUCTURE LAYER MODULES

### Persistence (9 modules) ✓ COMPLETE
- `App\Infrastructure\Persistence\MySQLUserRepository` - MySQL user access
- `App\Infrastructure\Persistence\MySQLCustomerRepository` - MySQL customer access
- `App\Infrastructure\Persistence\MySQLAppointmentRepository` - MySQL appointment access
- `App\Infrastructure\Persistence\MySQLServiceRepository` - MySQL service access
- `App\Infrastructure\Persistence\MySQLStaffRepository` - MySQL staff access
- `App\Infrastructure\Persistence\MySQLSaleRepository` - MySQL sale access
- `App\Infrastructure\Persistence\MySQLPaymentRepository` - MySQL payment access
- `App\Infrastructure\Persistence\MySQLStockRepository` - MySQL stock access
- `App\Infrastructure\Persistence\MySQLLoyaltyRepository` - MySQL loyalty access

### Integrations (3 modules) ⏳ PENDING
- `App\Infrastructure\Integrations\MpesaGateway` - M-Pesa payment gateway (30%)
- `App\Infrastructure\Integrations\TwilioGateway` - SMS notifications (0%)
- `App\Infrastructure\Integrations\EmailGateway` - Email notifications (0%)

---

## DEPENDENCY SUMMARY

### Total Modules: 51

| Category | Count | Status |
|----------|-------|--------|
| Models | 6 | ✓ Complete |
| Repositories (Interfaces) | 9 | ✓ Complete |
| Repositories (Implementations) | 9 | ✓ Complete |
| Controllers | 10 | ✓ Complete |
| Services | 8 | ⚙️ 65% |
| Validators | 5 | ✓ Complete |
| Exceptions | 5 | ✓ Complete |
| Integrations | 3 | ⏳ Pending |

### Dependency Graph

```
Controllers (10)
  ↓ depends on
Services (8)
  ↓ depends on
Repositories (9) + Models (6)
  ↓ depends on
Database (16 tables)
  + Integrations (3)
```

---

## MODULE DEPENDENCIES

### Appointment Flow
- AppointmentController → BookingService → AppointmentRepository → appointments table
- BookingService → AvailabilityService → Staff availability checking
- AppointmentValidator → ValidationException

### Sale/Payment Flow
- SaleController → PaymentService → PaymentRepository → payments table
- SaleController → InventoryService → StockRepository → stock tables
- SaleController → LoyaltyService → LoyaltyRepository → loyalty_points table
- PaymentService → MpesaGateway (pending integration)

### Customer/Loyalty Flow
- CustomerController → CustomerRepository → customers table
- LoyaltyController → LoyaltyService → LoyaltyRepository → loyalty_points table

### User/Admin Flow
- UserController → UserRepository → users table
- AuthController → AuthenticationService → JWTService → users table

---

## INTERFACE CONTRACTS

All controllers follow consistent interface:

```php
public function list(array $query): array
public function get(int $id): array
public function create(array $request): array
public function update(int $id, array $request): array
public function delete(int $id): array
public function [action](int $id, array $request): array
```

All repositories implement base interface methods:
- findById(int $id): ?array
- findAll(int $page, int $limit): array
- create(array $data): array
- update(array $data): array
- delete(int $id): bool
- findFiltered(array $filters, string $sort, string $order, int $page, int $limit): array

---

## EXTENSION POINTS

### For Sprint 2
- [ ] MpesaGateway full implementation
- [ ] TwilioGateway SMS integration
- [ ] EmailGateway integration
- [ ] AuthenticationService 2FA completion
- [ ] NotificationService full integration
- [ ] ReportController implementation

### For Future Phases
- [ ] Analytics module
- [ ] Reporting module
- [ ] Dashboard module
- [ ] Admin portal modules
- [ ] Mobile API endpoints

---

**Generated By**: Aurora AI-SDLC Framework  
**Last Updated**: 2026-07-28 14:30 UTC
