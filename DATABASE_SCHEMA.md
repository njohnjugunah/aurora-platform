# DATABASE_SCHEMA.md

**Aurora Platform - Database Schema Reference**

Version: 1.0.0  
Status: Current Schema  
Last Updated: 2026-07-28  
Database: MySQL 8.0+

---

## TABLE OF CONTENTS

1. Schema Overview
2. Core Tables
3. Relationship Diagrams
4. Indexes & Performance
5. Constraints & Rules
6. Soft Deletes
7. Audit Trail
8. Migration Strategy
9. Backup & Recovery
10. Query Optimization

---

## 1. SCHEMA OVERVIEW

### Database Statistics

| Metric | Value |
|--------|-------|
| **Engine** | InnoDB |
| **Charset** | utf8mb4 |
| **Collation** | utf8mb4_unicode_ci |
| **Total Tables** | 16 |
| **Total Columns** | 120+ |
| **Est. Size (1 year data)** | 500MB |

### Design Principles

1. **Normalization**: 3NF (Third Normal Form)
2. **Soft Deletes**: All business entities support soft delete via `deleted_at`
3. **Audit Trail**: All changes logged via `audit_logs` table
4. **Timestamps**: All tables have `created_at` and `updated_at`
5. **Types**: Strong typing with ENUM where appropriate
6. **Constraints**: Foreign keys with CASCADE behavior
7. **Indexing**: Strategic indexes for query performance

---

## 2. CORE TABLES

### 2.1 users

**Purpose**: System users and authentication

```sql
CREATE TABLE users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Authentication
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  
  -- Profile
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  
  -- Status
  status ENUM('active', 'inactive', 'locked') DEFAULT 'active',
  last_login_at TIMESTAMP NULL,
  failed_login_count INT DEFAULT 0 COMMENT 'Reset on successful login',
  locked_until TIMESTAMP NULL COMMENT 'Account lock timestamp',
  
  -- Role Assignment
  role_id BIGINT UNSIGNED NOT NULL,
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  
  FOREIGN KEY (role_id) REFERENCES roles(id),
  INDEX idx_email (email),
  INDEX idx_status (status),
  INDEX idx_role_id (role_id),
  INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Key Constraints**:
- Email must be unique (case-insensitive)
- Password must be hashed with bcrypt (12+ rounds)
- Account locked after 5 failed logins (15 minute lockout)
- Soft delete via `deleted_at`

---

### 2.2 roles

**Purpose**: User role definitions

```sql
CREATE TABLE roles (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL UNIQUE,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Predefined Roles**:
- owner (full system access)
- manager (manage staff, view reports, process refunds)
- staff (view own schedule, update customer notes)
- receptionist (book appointments, process payments)

---

### 2.3 permissions

**Purpose**: Granular permission definitions

```sql
CREATE TABLE permissions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL UNIQUE,
  description TEXT,
  resource VARCHAR(50) NOT NULL,
  action VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_resource (resource),
  INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Permission Examples**:
- appointments.create
- appointments.read
- appointments.update
- appointments.delete
- appointments.cancel
- sales.create
- sales.read
- customers.read
- staff.manage
- reports.view
- users.manage
- audit.view

---

### 2.4 role_permissions

**Purpose**: Map permissions to roles

```sql
CREATE TABLE role_permissions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  role_id BIGINT UNSIGNED NOT NULL,
  permission_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  UNIQUE KEY unique_role_permission (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### 2.5 customers

**Purpose**: Customer profiles and contact information

```sql
CREATE TABLE customers (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Personal Info
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(255),
  
  -- Preferences
  preferred_staff_id BIGINT UNSIGNED,
  communication_preference ENUM('sms', 'email', 'both') DEFAULT 'sms',
  
  -- Statistics
  total_visits INT DEFAULT 0,
  total_spent DECIMAL(10, 2) DEFAULT 0.00,
  avg_transaction_value DECIMAL(10, 2) DEFAULT 0.00,
  last_visit_at TIMESTAMP NULL,
  
  -- Status
  status ENUM('active', 'inactive', 'vip') DEFAULT 'active',
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  
  FOREIGN KEY (preferred_staff_id) REFERENCES staff_members(id),
  INDEX idx_phone (phone),
  INDEX idx_email (email),
  INDEX idx_status (status),
  INDEX idx_total_spent (total_spent),
  INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Business Rules**:
- Phone number must be unique (or allow duplicates for walk-ins)
- First/last name required
- Total visits and spent updated on transaction completion

---

### 2.6 services

**Purpose**: Beauty services catalog

```sql
CREATE TABLE services (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Service Info
  name VARCHAR(100) NOT NULL,
  description TEXT,
  category VARCHAR(50),
  
  -- Pricing & Duration
  price DECIMAL(10, 2) NOT NULL,
  duration_minutes INT NOT NULL COMMENT 'Service duration in minutes',
  
  -- Status
  status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  
  INDEX idx_category (category),
  INDEX idx_status (status),
  INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Example Data**:
- Hair Coloring (90 min, 3,500 KES)
- Hair Treatment (60 min, 2,500 KES)
- Weaving (120 min, 4,000 KES)
- Nail Art (45 min, 1,500 KES)

---

### 2.7 staff_members

**Purpose**: Staff member profiles and scheduling

```sql
CREATE TABLE staff_members (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Personal Info
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  
  -- Employment
  position VARCHAR(50) NOT NULL COMMENT 'Stylist, Receptionist, Manager, etc.',
  hire_date DATE NOT NULL,
  status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
  
  -- Commission
  commission_rate DECIMAL(5, 2) NOT NULL COMMENT 'Percentage (0-100)',
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  
  FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_status (status),
  INDEX idx_position (position),
  INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Business Rules**:
- One staff member per user
- Commission rate must be 0-100%
- Cannot delete active staff (set to inactive)

---

### 2.8 appointments

**Purpose**: Service appointments/bookings

```sql
CREATE TABLE appointments (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- References
  customer_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NOT NULL,
  staff_id BIGINT UNSIGNED NOT NULL,
  
  -- Timing
  start_time DATETIME NOT NULL COMMENT 'Appointment start',
  end_time DATETIME NOT NULL COMMENT 'Calculated from service duration',
  
  -- Status
  status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
  
  -- Notes
  notes TEXT,
  cancel_reason VARCHAR(255) NULL,
  
  -- Payment
  prepaid_amount DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Prepayment for appointment',
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (service_id) REFERENCES services(id),
  FOREIGN KEY (staff_id) REFERENCES staff_members(id),
  INDEX idx_customer_id (customer_id),
  INDEX idx_staff_id (staff_id),
  INDEX idx_start_time (start_time),
  INDEX idx_status (status),
  INDEX idx_deleted_at (deleted_at),
  UNIQUE KEY unique_staff_appointment (staff_id, start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Business Rules**:
- No double-booking for same staff at same time
- Cannot book without 1-hour lead time
- Cannot book in past
- Cannot cancel completed appointments

---

### 2.9 sales

**Purpose**: Customer transactions/sales

```sql
CREATE TABLE sales (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- References
  customer_id BIGINT UNSIGNED NULL COMMENT 'NULL for walk-in customers',
  staff_id BIGINT UNSIGNED COMMENT 'Staff who made the sale',
  appointment_id BIGINT UNSIGNED NULL,
  
  -- Amounts
  subtotal DECIMAL(10, 2) NOT NULL,
  discount_amount DECIMAL(10, 2) DEFAULT 0.00,
  tax_amount DECIMAL(10, 2) DEFAULT 0.00,
  total DECIMAL(10, 2) NOT NULL,
  
  -- Discount
  discount_type ENUM('fixed', 'percentage') NULL,
  discount_value DECIMAL(5, 2) NULL,
  
  -- Status
  status ENUM('open', 'paid', 'refunded') DEFAULT 'open',
  
  -- Notes
  notes TEXT,
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (staff_id) REFERENCES staff_members(id),
  FOREIGN KEY (appointment_id) REFERENCES appointments(id),
  INDEX idx_customer_id (customer_id),
  INDEX idx_staff_id (staff_id),
  INDEX idx_status (status),
  INDEX idx_created_at (created_at),
  INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Calculations**:
- `total = subtotal - discount_amount + tax_amount`
- `subtotal = SUM(line_items.subtotal)`

---

### 2.10 line_items

**Purpose**: Individual items within a sale

```sql
CREATE TABLE line_items (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- References
  sale_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NULL,
  product_id BIGINT UNSIGNED NULL,
  
  -- Item Details
  item_type ENUM('service', 'product') NOT NULL,
  item_name VARCHAR(100) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10, 2) NOT NULL,
  discount_amount DECIMAL(10, 2) DEFAULT 0.00,
  subtotal DECIMAL(10, 2) NOT NULL,
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id),
  FOREIGN KEY (product_id) REFERENCES products(id),
  INDEX idx_sale_id (sale_id),
  INDEX idx_type (item_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### 2.11 payments

**Purpose**: Payment records for sales

```sql
CREATE TABLE payments (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- References
  sale_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED,
  
  -- Payment Details
  method ENUM('cash', 'mpesa', 'card', 'bank_transfer') NOT NULL,
  amount DECIMAL(10, 2) NOT NULL,
  reference VARCHAR(100) COMMENT 'M-Pesa ref, card auth code, etc.',
  
  -- Status
  status ENUM('pending', 'verified', 'failed', 'refunded') DEFAULT 'pending',
  
  -- M-Pesa Specific
  mpesa_checkout_request_id VARCHAR(100) NULL,
  mpesa_merchant_request_id VARCHAR(100) NULL,
  mpesa_result_code INT NULL,
  mpesa_result_desc TEXT NULL,
  
  -- Refund
  refund_amount DECIMAL(10, 2) DEFAULT 0.00,
  refund_reason VARCHAR(255) NULL,
  refunded_at TIMESTAMP NULL,
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (sale_id) REFERENCES sales(id),
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  INDEX idx_sale_id (sale_id),
  INDEX idx_method (method),
  INDEX idx_status (status),
  INDEX idx_reference (reference),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### 2.12 products

**Purpose**: Retail product inventory

```sql
CREATE TABLE products (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Product Info
  name VARCHAR(100) NOT NULL,
  description TEXT,
  sku VARCHAR(50) UNIQUE,
  category VARCHAR(50),
  
  -- Pricing
  cost_price DECIMAL(10, 2) NOT NULL,
  selling_price DECIMAL(10, 2) NOT NULL,
  
  -- Inventory
  reorder_point INT DEFAULT 20 COMMENT 'Trigger alert when stock falls below',
  
  -- Status
  status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  
  INDEX idx_category (category),
  INDEX idx_status (status),
  INDEX idx_sku (sku),
  INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Business Rules**:
- `selling_price > cost_price` (enforced in application)
- Cost price and selling price must be positive
- Reorder point is configurable per product

---

### 2.13 stock

**Purpose**: Current inventory levels

```sql
CREATE TABLE stock (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- References
  product_id BIGINT UNSIGNED NOT NULL UNIQUE,
  
  -- Levels
  quantity_on_hand INT NOT NULL DEFAULT 0 COMMENT 'Total physical quantity',
  quantity_reserved INT NOT NULL DEFAULT 0 COMMENT 'Reserved for pending sales',
  quantity_available INT GENERATED ALWAYS AS (quantity_on_hand - quantity_reserved) STORED,
  
  -- History
  last_counted_at TIMESTAMP NULL,
  last_restock_at TIMESTAMP NULL,
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (product_id) REFERENCES products(id),
  INDEX idx_product_id (product_id),
  INDEX idx_quantity_available (quantity_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Business Rules**:
- `quantity_available >= 0` (never negative)
- `quantity_reserved <= quantity_on_hand`
- `quantity_available` is computed column

---

### 2.14 stock_movements

**Purpose**: Audit trail of inventory changes

```sql
CREATE TABLE stock_movements (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- References
  product_id BIGINT UNSIGNED NOT NULL,
  created_by_user_id BIGINT UNSIGNED NOT NULL,
  
  -- Movement Details
  type ENUM('sale', 'purchase', 'adjustment', 'return', 'damage') NOT NULL,
  quantity INT NOT NULL COMMENT 'Negative for deductions',
  reference VARCHAR(100) COMMENT 'Sale ID, PO ID, etc.',
  reason TEXT,
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (created_by_user_id) REFERENCES users(id),
  INDEX idx_product_id (product_id),
  INDEX idx_type (type),
  INDEX idx_created_at (created_at),
  INDEX idx_reference (reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Examples**:
```
Sale: quantity = -2, reference = SALE-46, type = sale
Purchase: quantity = 100, reference = PO-789, type = purchase
Adjustment: quantity = -5, reason = Inventory count correction, type = adjustment
Return: quantity = 1, reason = Damaged product, type = return
```

---

### 2.15 loyalty_points

**Purpose**: Customer loyalty program tracking

```sql
CREATE TABLE loyalty_points (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- References
  customer_id BIGINT UNSIGNED NOT NULL,
  
  -- Points
  total_points INT NOT NULL DEFAULT 0,
  tier ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
  
  -- Tier Thresholds
  -- Bronze: 0+ points (0% discount)
  -- Silver: 5000+ points (5% discount)
  -- Gold: 10000+ points (10% discount)
  -- Platinum: 25000+ points (20% discount)
  
  -- History
  points_earned INT DEFAULT 0,
  points_redeemed INT DEFAULT 0,
  last_earned_at TIMESTAMP NULL,
  last_redeemed_at TIMESTAMP NULL,
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  UNIQUE KEY unique_customer (customer_id),
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  INDEX idx_tier (tier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Calculation Logic**:
- 1 point earned per 1 KES spent
- 100 points = 10 KES redemption value
- Tier progression automatic on points accumulation

---

### 2.16 audit_logs

**Purpose**: Complete audit trail of system changes

```sql
CREATE TABLE audit_logs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- User & Action
  user_id BIGINT UNSIGNED,
  action VARCHAR(50) NOT NULL COMMENT 'create, read, update, delete, refund, etc.',
  
  -- Resource
  resource_type VARCHAR(50) NOT NULL COMMENT 'appointment, sale, product, user, etc.',
  resource_id BIGINT UNSIGNED,
  
  -- Changes
  old_values JSON COMMENT 'Previous values before change',
  new_values JSON COMMENT 'New values after change',
  
  -- Request Context
  ip_address VARCHAR(45),
  user_agent TEXT,
  request_id VARCHAR(100),
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_user_id (user_id),
  INDEX idx_resource_type (resource_type),
  INDEX idx_resource_id (resource_id),
  INDEX idx_action (action),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Examples**:
```json
{
  "action": "create",
  "resource_type": "appointment",
  "resource_id": 157,
  "new_values": {
    "customerId": 5,
    "serviceId": 2,
    "staffId": 3,
    "startTime": "2026-08-01T10:00:00Z"
  }
}

{
  "action": "update",
  "resource_type": "appointment",
  "resource_id": 157,
  "old_values": { "status": "pending" },
  "new_values": { "status": "confirmed" }
}
```

---

## 3. RELATIONSHIP DIAGRAMS

### Core Entity Relationships

```
users ─── role_id ──→ roles
  ↓
  └─ role_permissions ←── permissions

staff_members ←─ user_id ── users

customers ← customer_id ── appointments ─→ services
                ↓                              ↓
              sales ◄─── line_items ────► products
                ↓                            ↓
              payments                     stock
                                             ↓
                                      stock_movements

customers ─── customer_id ──→ loyalty_points

audit_logs → All tables (references)
```

---

## 4. INDEXES & PERFORMANCE

### High-Volume Queries (Indexed)

```sql
-- Appointment queries
SELECT * FROM appointments 
WHERE staff_id = ? AND start_time BETWEEN ? AND ?
-- Index: (staff_id, start_time)

-- Sales queries
SELECT * FROM sales 
WHERE created_at >= ? AND status = ?
-- Index: (created_at, status)

-- Customer queries
SELECT * FROM customers 
WHERE phone = ? OR email = ?
-- Index: (phone), (email)

-- Inventory queries
SELECT * FROM products 
WHERE category = ? AND status = 'active'
-- Index: (category, status)

-- Audit queries
SELECT * FROM audit_logs 
WHERE resource_type = ? AND created_at >= ?
-- Index: (resource_type, created_at)
```

### Recommended Indexes Summary

| Table | Column(s) | Type | Reason |
|-------|-----------|------|--------|
| appointments | (staff_id, start_time) | Composite | Conflict checking |
| appointments | (start_time) | Simple | Range queries |
| sales | (customer_id, created_at) | Composite | Customer history |
| sales | (created_at) | Simple | Daily reports |
| stock_movements | (product_id, created_at) | Composite | Movement history |
| audit_logs | (resource_type, created_at) | Composite | Audit trails |
| customers | (phone, email) | Simple | Duplicate detection |

---

## 5. CONSTRAINTS & RULES

### Foreign Key Constraints

| Constraint | Behavior | Reason |
|-----------|----------|--------|
| customers → (none) | - | Cascade not needed |
| appointments → customers | CASCADE | Delete customer, delete appointments |
| appointments → services | RESTRICT | Cannot delete active service |
| appointments → staff | RESTRICT | Cannot delete active staff |
| sales → customers | SET NULL | Customer deletion doesn't affect sales |
| sales → staff | RESTRICT | Cannot delete staff with sales |
| line_items → sales | CASCADE | Delete sale, delete items |
| payments → sales | RESTRICT | Cannot delete sale with payments |
| stock → products | CASCADE | Delete product, delete stock |
| stock_movements → products | RESTRICT | Audit trail must remain |

### Check Constraints

```sql
-- Prices must be positive
ALTER TABLE services ADD CONSTRAINT chk_service_price CHECK (price > 0);
ALTER TABLE products ADD CONSTRAINT chk_product_prices 
  CHECK (cost_price > 0 AND selling_price > cost_price);

-- Quantities
ALTER TABLE stock ADD CONSTRAINT chk_quantities 
  CHECK (quantity_on_hand >= 0 AND quantity_reserved >= 0);

-- Amounts
ALTER TABLE sales ADD CONSTRAINT chk_amounts 
  CHECK (total = (subtotal - discount_amount + tax_amount));
```

---

## 6. SOFT DELETES

### Implementation

All business entities support soft delete via `deleted_at` column:

```sql
-- Soft delete
UPDATE users SET deleted_at = NOW() WHERE id = 1;

-- Restore
UPDATE users SET deleted_at = NULL WHERE id = 1;

-- Query excluding deleted (standard)
SELECT * FROM users WHERE deleted_at IS NULL;

-- Query including deleted (for admin)
SELECT * FROM users; -- No filter

-- Permanent delete (rare, requires audit)
DELETE FROM users WHERE id = 1;
```

### Tables with Soft Delete

- users
- customers
- staff_members
- services
- products
- appointments
- sales

### Audit Trail Tables (Permanent)

These tables never support soft delete:
- audit_logs
- stock_movements
- role_permissions
- permissions
- roles
- line_items (cascade from sales)
- payments (cascade from sales)
- stock (cascade from products)

---

## 7. AUDIT TRAIL

### Audit Trail Coverage

**Automatic Audit on**:
- All `INSERT` operations
- All `UPDATE` operations
- `DELETE` operations (soft deletes only)
- Sensitive operations (refunds, permission changes)

**Audit Trail Contents**:
- Timestamp
- User ID
- Action (create, read, update, delete)
- Resource type and ID
- Old and new values (for updates)
- IP address and user agent

**Audit Log Retention**:
- Keep indefinitely for compliance
- Archive to separate table after 1 year (optional)

---

## 8. MIGRATION STRATEGY

### Migration File: 001_create_base_schema.sql

Current location: `/migrations/001_create_base_schema.sql`

**Contains**:
- All 16 tables
- All indexes
- All foreign keys
- All check constraints
- Seed data (roles, permissions)

### Future Migrations

**Pattern**:
```
migrations/
  001_create_base_schema.sql
  002_add_analytics_table.sql
  003_add_sms_notification_status.sql
  004_add_invoice_number_column.sql
```

**Migration Best Practices**:
1. Create new migration file for each change
2. Include both UP (apply) and DOWN (rollback) scripts
3. Test rollback before deployment
4. Keep migrations small and focused
5. Never modify previous migrations (immutable history)

### Running Migrations

```bash
# Apply all pending migrations
php migrate.php up

# Rollback last migration
php migrate.php down

# Rollback to specific version
php migrate.php rollback --target=001

# Current version status
php migrate.php status
```

---

## 9. BACKUP & RECOVERY

### Backup Strategy

**Frequency**: Daily at 2:00 AM (off-peak)

**Backup Types**:
1. **Full Backup**: Complete database (daily)
2. **Incremental**: Changes since last backup (hourly)
3. **Transaction Log**: All transactions (continuous)

**Retention**:
- Daily backups: 30 days
- Weekly backups: 12 weeks
- Monthly backups: 12 months

### Backup & Restore Commands

```bash
# Full backup
mysqldump -u root -p --all-databases > backup_full_$(date +%Y%m%d).sql

# Backup specific database
mysqldump -u root -p aurora > backup_aurora_$(date +%Y%m%d).sql

# Backup with binary log position (for point-in-time recovery)
mysqldump -u root -p --master-data aurora > backup_aurora_pitr.sql

# Restore from backup
mysql -u root -p < backup_aurora_20260728.sql

# Point-in-time recovery (restore to specific moment)
mysql -u root -p aurora < backup_aurora_base.sql
mysql -u root -p aurora < binlog_extract_start_point_end_point.sql
```

### Recovery Test Schedule

- Monthly: Full restore and verification
- Quarterly: Point-in-time recovery test
- Annually: Disaster recovery drill

---

## 10. QUERY OPTIMIZATION

### Slow Query Log

```sql
-- Enable slow query logging
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2; -- Log queries taking > 2 seconds

-- View slow queries
SHOW PROCESSLIST;
```

### Query Optimization Checklist

| Optimization | Status | Priority |
|--------------|--------|----------|
| Add indexes on WHERE clauses | ✓ | P0 |
| Add indexes on JOIN columns | ✓ | P0 |
| Add indexes on ORDER BY | ✓ | P1 |
| Denormalize high-traffic reads | ⚠️ | P2 |
| Archive old audit logs | ⏳ | P3 |

### Common Query Patterns

**Find Available Staff**:
```sql
SELECT s.* FROM staff_members s
WHERE s.status = 'active'
AND NOT EXISTS (
  SELECT 1 FROM appointments a
  WHERE a.staff_id = s.id
  AND a.start_time = ?
  AND a.status NOT IN ('cancelled')
);
```

**Daily Revenue Report**:
```sql
SELECT 
  DATE(s.created_at) as date,
  SUM(s.total) as revenue,
  COUNT(s.id) as transactions
FROM sales s
WHERE s.status = 'paid'
AND s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(s.created_at)
ORDER BY date DESC;
```

**Customer Lifetime Value**:
```sql
SELECT 
  c.id,
  c.name,
  COUNT(s.id) as visit_count,
  SUM(s.total) as total_spent,
  AVG(s.total) as avg_transaction
FROM customers c
LEFT JOIN sales s ON s.customer_id = c.id AND s.status = 'paid'
WHERE c.deleted_at IS NULL
GROUP BY c.id
ORDER BY total_spent DESC;
```

---

## DATABASE VALIDATION

### Data Quality Checks

```sql
-- Check for orphaned records
SELECT * FROM appointments WHERE customer_id NOT IN (SELECT id FROM customers);
SELECT * FROM appointments WHERE staff_id NOT IN (SELECT id FROM staff_members);

-- Check for inconsistent soft deletes
SELECT * FROM sales WHERE customer_id IN (
  SELECT id FROM customers WHERE deleted_at IS NOT NULL
);

-- Check for stock inconsistencies
SELECT p.id, p.name, s.quantity_on_hand, s.quantity_reserved
FROM products p
LEFT JOIN stock s ON s.product_id = p.id
WHERE s.quantity_on_hand < 0 OR s.quantity_reserved < 0;

-- Check payment totals against sales totals
SELECT s.id, s.total, SUM(p.amount) as paid
FROM sales s
LEFT JOIN payments p ON p.sale_id = s.id
WHERE s.status = 'paid'
GROUP BY s.id
HAVING s.total != SUM(p.amount);
```

---

**END OF DATABASE_SCHEMA.md**

**Schema Version**: 1.0.0  
**Last Modified**: 2026-07-28  
**Next Review**: After each migration deployment
