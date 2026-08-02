# GlamByMariga Enhancement Plan
## Aurora Platform → Luxury Beauty Salon System

**Project**: Transform Aurora Platform into GlamByMariga  
**Approach**: Enhance existing foundation with luxury features  
**Timeline**: 6-8 weeks  
**Status**: Planning Phase

---

## 🎯 VISION

Transform Aurora Platform into a **production-ready luxury beauty salon e-commerce system** with:
- 💎 Premium rose gold branding
- 💎 M-Pesa payment integration (Daraja)
- 💎 Advanced booking with calendar management
- 💎 Complete e-commerce shop
- 💎 Professional admin dashboard
- 💎 Responsive mobile experience

---

## 📋 PHASE BREAKDOWN

### **PHASE 1: BRANDING & DESIGN ENHANCEMENT** (1-2 weeks)
**Goal**: Transform Aurora into GlamByMariga visual identity

#### 1.1 Color Palette & Typography
```
Primary Colors:
- Rose Gold: #B76E79, #D4A5A5
- Accent Gold: #C9A961, #E5C29F
- White: #FFFFFF
- Black: #1A1A1A
- Soft Pink: #F5E6E6
- Cream: #F5F1E8

Typography:
- Headings: 'Playfair Display' (elegant serif)
- Body: 'Montserrat' (modern sans-serif)
- Accents: 'Great Vibes' (script - sparingly)
```

#### 1.2 CSS Enhancements
**Files to Create/Update**:
- `public/css/glambymariga-theme.css` (2KB)
- `public/css/luxury-components.css` (3KB)
- `public/css/animations.css` (2KB)

**Features**:
- ✅ Rose gold gradients
- ✅ Premium shadows
- ✅ Smooth fade animations
- ✅ Hover effects
- ✅ Luxury card designs
- ✅ Premium spacing system

#### 1.3 Frontend Pages Update
**Update existing pages**:
- `public/dashboard.html` → Add luxury hero
- Add missing pages:
  - `public/about.html` (About brand)
  - `public/testimonials.html` (Reviews)
  - `public/gallery.html` (Gallery)
  - `public/faq.html` (FAQ)
  - `public/privacy.html` (Privacy Policy)
  - `public/terms.html` (Terms)

#### 1.4 Hero Banner & Homepage
```html
<!-- Luxury hero banner with:
- Full-width video/image background
- Premium typography
- CTA buttons
- Smooth scrolling
- Rose gold accents
-->
```

**Time**: 1-2 weeks  
**Priority**: HIGH  
**Dependencies**: None

---

### **PHASE 2: M-PESA INTEGRATION** (1 week)
**Goal**: Implement Daraja API for seamless payments

#### 2.1 Daraja API Setup
**Configuration File**: `config/mpesa.php`
```php
<?php
// M-Pesa Daraja Configuration
return [
    'consumer_key' => $_ENV['MPESA_CONSUMER_KEY'],
    'consumer_secret' => $_ENV['MPESA_CONSUMER_SECRET'],
    'business_shortcode' => $_ENV['MPESA_SHORTCODE'],
    'passkey' => $_ENV['MPESA_PASSKEY'],
    'environment' => 'production', // or 'sandbox'
    'callback_url' => 'https://glambymariga.com/api/mpesa/callback',
];
?>
```

#### 2.2 Payment Module Structure
```
public/ajax/mpesa/
├── stk-push.php          (Initiate payment)
├── callback.php          (Handle response)
├── transaction-query.php (Check status)
└── retry-payment.php     (Retry failed)

includes/payment/
├── MpesaGateway.php      (Main class)
├── PaymentValidator.php  (Validation)
├── PaymentLogger.php     (Logging)
└── PaymentProcessor.php  (Processing)
```

#### 2.3 Database Additions
**New Tables**:
```sql
-- Payment logging
CREATE TABLE mpesa_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT,
    order_id INT,
    amount DECIMAL(10,2),
    phone_number VARCHAR(15),
    transaction_ref VARCHAR(50) UNIQUE,
    checkout_request_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed'),
    response_code VARCHAR(10),
    response_message TEXT,
    timestamp DATETIME,
    updated_at DATETIME
);

CREATE TABLE payment_retries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT,
    attempt_number INT,
    status VARCHAR(50),
    response_message TEXT,
    created_at DATETIME
);
```

#### 2.4 STK Push Workflow
```
1. Customer submits booking/order
2. System initiates STK Push
   - Phone number
   - Amount
   - Account reference
3. Customer receives M-Pesa prompt
4. Customer enters PIN
5. Callback URL receives response
6. System verifies transaction
7. Updates booking/order status
8. Sends confirmation email
```

#### 2.5 Security Features
- ✅ Request signature validation
- ✅ Callback verification
- ✅ Transaction replay protection
- ✅ Amount verification
- ✅ Audit logging
- ✅ Encrypted API keys

**Time**: 1 week  
**Priority**: CRITICAL  
**Dependencies**: Phase 1

---

### **PHASE 3: FULLCALENDAR INTEGRATION** (1 week)
**Goal**: Advanced appointment management

#### 3.1 FullCalendar Setup
**Installation**:
```bash
npm install --save @fullcalendar/react @fullcalendar/daygrid @fullcalendar/timegrid
# OR CDN (if avoiding npm)
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
```

#### 3.2 Calendar Features
**Admin Calendar**:
- ✅ Day/Week/Month views
- ✅ Time slot visualization
- ✅ Booking details popup
- ✅ Drag-to-reschedule
- ✅ Color-coded by status
- ✅ Service color coding

**Customer Calendar**:
- ✅ View available slots
- ✅ Book appointment
- ✅ View existing bookings
- ✅ Request reschedule

#### 3.3 Business Logic
```php
// Booking Rules
$businessHours = [
    'monday_to_saturday' => ['08:00' => '20:00'],
    'sunday' => ['10:00' => '17:00'],
    'holidays' => [], // Admin configurable
    'breaks' => []     // Admin configurable
];

// Slot Generation
function getAvailableSlots($date, $serviceId, $staffId) {
    // Get service duration
    // Get existing bookings
    // Generate 30-min slots
    // Return available only
}

// Slot Locking
function lockSlot($date, $time, $duration = 600) {
    // Lock for 10 minutes during payment
    // Auto-release if payment fails
}
```

#### 3.4 Database Updates
```sql
ALTER TABLE bookings ADD COLUMN 
    slot_locked_until DATETIME;

ALTER TABLE bookings ADD COLUMN
    lock_reason VARCHAR(100);

ALTER TABLE settings ADD COLUMN
    business_hours JSON;

CREATE TABLE holidays (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE,
    reason VARCHAR(255),
    created_by INT
);

CREATE TABLE break_times (
    id INT PRIMARY KEY AUTO_INCREMENT,
    day_of_week INT,
    start_time TIME,
    end_time TIME
);
```

**Time**: 1 week  
**Priority**: HIGH  
**Dependencies**: Phase 2

---

### **PHASE 4: E-COMMERCE ENHANCEMENT** (1-2 weeks)
**Goal**: Complete shop functionality

#### 4.1 Product Features
**Expand existing shop**:
- ✅ Multiple product images (gallery)
- ✅ SKU/Barcode
- ✅ Product weight
- ✅ Related products
- ✅ Stock management
- ✅ Discount system
- ✅ Featured products
- ✅ Product reviews

#### 4.2 Database Enhancements
```sql
-- Product images
CREATE TABLE product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT,
    image_url VARCHAR(255),
    alt_text VARCHAR(255),
    is_primary BOOLEAN,
    sort_order INT,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Product variants
CREATE TABLE product_variants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT,
    sku VARCHAR(100) UNIQUE,
    barcode VARCHAR(100),
    size VARCHAR(50),
    color VARCHAR(50),
    stock INT,
    price DECIMAL(10,2),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Reviews
CREATE TABLE product_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT,
    customer_id INT,
    rating INT (1-5),
    comment TEXT,
    verified_purchase BOOLEAN,
    created_at DATETIME,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Wishlist
CREATE TABLE wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    product_id INT,
    created_at DATETIME,
    UNIQUE(customer_id, product_id)
);
```

#### 4.3 Shop Pages
- ✅ Product detail (enhanced)
- ✅ Product review system
- ✅ Related products
- ✅ Wishlist
- ✅ Advanced filters
- ✅ Search results
- ✅ Category browse

#### 4.4 Checkout Enhancement
- ✅ Shipping options
- ✅ Pickup option
- ✅ Multiple payment methods
- ✅ Order confirmation
- ✅ Invoice generation
- ✅ Receipt email

**Time**: 1-2 weeks  
**Priority**: HIGH  
**Dependencies**: Phase 2

---

### **PHASE 5: ADMIN DASHBOARD ENHANCEMENT** (1-2 weeks)
**Goal**: Professional, responsive admin panel

#### 5.1 Dashboard Home
**Key Metrics**:
- ✅ Today's sales ($)
- ✅ Today's appointments (#)
- ✅ Today's orders (#)
- ✅ Total revenue (MTD)
- ✅ Monthly revenue chart
- ✅ Top products
- ✅ Top services
- ✅ Recent bookings
- ✅ Recent orders

#### 5.2 Admin Modules
```
Admin Dashboard/
├── Dashboard (Home)
├── Services
│   ├── Create/Edit
│   ├── Categories
│   └── Manage
├── Products
│   ├── Create/Edit
│   ├── Inventory
│   ├── Categories
│   └── Images
├── Bookings
│   ├── Calendar view
│   ├── List view
│   ├── Details/Edit
│   └── Reschedule
├── Orders
│   ├── List
│   ├── Details
│   ├── Status update
│   └── Invoice
├── Customers
│   ├── List
│   ├── Details
│   ├── Contact history
│   └── Loyalty points
├── Payments
│   ├── Transaction list
│   ├── M-Pesa logs
│   ├── Failed retries
│   └── Reports
├── Reports
│   ├── Sales report
│   ├── Booking report
│   ├── Revenue report
│   ├── Inventory report
│   └── Export (PDF/Excel)
├── Gallery
│   ├── Upload
│   ├── Manage
│   └── Sort
├── Reviews
│   ├── Moderate
│   ├── Delete
│   └── Feature
├── Coupons
│   ├── Create
│   ├── Edit
│   ├── Deactivate
│   └── Tracking
├── Settings
│   ├── Business info
│   ├── Hours/Holidays
│   ├── Break times
│   ├── Payment config
│   ├── Email settings
│   └── System settings
├── Staff
│   ├── Manage users
│   ├── Roles
│   ├── Permissions
│   └── Schedules
└── Logs
    ├── Audit trail
    ├── Activity log
    ├── Error logs
    └── Payment logs
```

#### 5.3 Charts & Analytics
- ✅ Chart.js integration
- ✅ Sales trends
- ✅ Revenue by service
- ✅ Revenue by product
- ✅ Customer acquisition
- ✅ Appointment completion rate

#### 5.4 Reports Module
```php
// Exportable reports
- Sales Report (PDF/Excel/CSV)
  - Date range filter
  - Service breakdown
  - Product breakdown
  - Payment method split
  
- Booking Report (PDF/Excel/CSV)
  - Appointments booked
  - Cancelled rate
  - No-show rate
  - Revenue per booking
  
- Revenue Report (PDF/Excel/CSV)
  - Daily/Weekly/Monthly
  - By service
  - By staff
  - By payment method
  
- Inventory Report (PDF/Excel/CSV)
  - Stock levels
  - Low stock alerts
  - Top sellers
  - Slow movers
```

**Time**: 1-2 weeks  
**Priority**: HIGH  
**Dependencies**: Phase 1-4

---

### **PHASE 6: ADVANCED FEATURES** (1 week)
**Goal**: Polish and premium features

#### 6.1 Customer Portal Enhancements
- ✅ Upcoming bookings widget
- ✅ Past bookings history
- ✅ Order history with filters
- ✅ Wishlist management
- ✅ Multiple addresses
- ✅ Invoice downloads
- ✅ Loyalty points display
- ✅ Referral system (bonus)

#### 6.2 Testimonials & Reviews
- ✅ Customer testimonials section
- ✅ Featured testimonials on home
- ✅ Star ratings
- ✅ Photo testimonials
- ✅ Admin moderation
- ✅ Verification badge

#### 6.3 Gallery System
- ✅ Before/after photos
- ✅ Service-specific galleries
- ✅ Lightbox viewer
- ✅ Social sharing
- ✅ Admin bulk upload

#### 6.4 Instagram Feed Placeholder
```php
// Integration options:
- Embed Instagram feed (API)
- Manual curated feed
- Latest posts display
- Link to Instagram profile
```

#### 6.5 Newsletter System
- ✅ Subscription management
- ✅ Email templates
- ✅ Bulk send (admin)
- ✅ Unsubscribe link
- ✅ Subscriber list

#### 6.6 Email Notifications
- ✅ Booking confirmation
- ✅ Booking reminder (24h before)
- ✅ Payment confirmation
- ✅ Order confirmation
- ✅ Shipping updates
- ✅ Review request
- ✅ Newsletter

**Time**: 1 week  
**Priority**: MEDIUM  
**Dependencies**: Phase 1-5

---

### **PHASE 7: DUMMY DATA & DOCUMENTATION** (1 week)
**Goal**: Production-ready package

#### 7.1 Dummy Data Generation
```sql
-- 40+ Products (wigs, lashes, accessories)
-- 15 Categories
-- 30+ Customers
-- 60+ Orders
-- 150+ Bookings
-- 100+ Payments
-- 20+ Testimonials
-- 20+ Gallery images
-- Admin & Staff accounts
-- Holidays & Break times
```

#### 7.2 Test Accounts
```
Admin Account:
- Email: admin@glambymariga.com
- Password: Admin@123
- Permissions: All

Staff Account:
- Email: staff@glambymariga.com
- Password: Staff@123
- Permissions: Bookings, Services, Reports

Customer Account:
- Email: customer@example.com
- Password: Customer@123
- Has bookings & orders
```

#### 7.3 Documentation
**Files to Create**:
- `README.md` (Installation guide)
- `INSTALLATION.md` (Step-by-step setup)
- `API_DOCUMENTATION.md` (API endpoints)
- `DATABASE_SCHEMA.md` (Database structure)
- `MPESA_SETUP.md` (Daraja configuration)
- `USER_GUIDE.md` (Admin guide)
- `TROUBLESHOOTING.md` (Common issues)

#### 7.4 Deployment Package
- ✅ Complete source code
- ✅ Database dump (.sql)
- ✅ Assets (CSS, JS, images)
- ✅ Configuration template
- ✅ Environment variables template
- ✅ cPanel deployment guide

**Time**: 1 week  
**Priority**: HIGH  
**Dependencies**: All phases

---

## 📁 UPDATED FILE STRUCTURE

```
glambymariga/
├── config/
│   ├── database.php
│   ├── app.php
│   ├── mpesa.php          [NEW]
│   └── email.php
├── public/
│   ├── css/
│   │   ├── bootstrap.css
│   │   ├── glambymariga-theme.css    [NEW]
│   │   ├── luxury-components.css     [NEW]
│   │   └── animations.css            [NEW]
│   ├── js/
│   │   ├── luxury.js                 [NEW]
│   │   ├── calendar.js               [NEW]
│   │   └── mpesa-payment.js          [NEW]
│   ├── images/
│   │   ├── hero/                     [NEW]
│   │   ├── products/
│   │   ├── gallery/                  [NEW]
│   │   └── testimonials/             [NEW]
│   ├── uploads/
│   │   ├── products/
│   │   ├── gallery/
│   │   └── avatars/
│   ├── index.html                    [UPDATED]
│   ├── about.html                    [NEW]
│   ├── gallery.html                  [NEW]
│   ├── testimonials.html             [NEW]
│   ├── faq.html                      [NEW]
│   ├── privacy.html                  [NEW]
│   └── terms.html                    [NEW]
├── admin/
│   ├── dashboard.html                [UPDATED]
│   ├── bookings/
│   │   ├── calendar.html             [NEW]
│   │   ├── list.html
│   │   └── details.html
│   ├── products/
│   │   ├── list.html
│   │   ├── form.html                 [UPDATED]
│   │   └── images.html               [NEW]
│   ├── reports/                      [NEW]
│   │   ├── sales.html
│   │   ├── bookings.html
│   │   ├── revenue.html
│   │   └── inventory.html
│   └── settings/                     [UPDATED]
├── customer/
│   ├── dashboard.html                [UPDATED]
│   ├── bookings.html                 [UPDATED]
│   ├── orders.html                   [UPDATED]
│   ├── wishlist.html
│   └── profile.html                  [UPDATED]
├── includes/
│   ├── payment/                      [NEW]
│   │   ├── MpesaGateway.php
│   │   ├── PaymentValidator.php
│   │   ├── PaymentLogger.php
│   │   └── PaymentProcessor.php
│   ├── booking/                      [NEW]
│   │   ├── BookingManager.php
│   │   ├── SlotManager.php
│   │   └── NotificationManager.php
│   ├── email/                        [UPDATED]
│   ├── auth/
│   └── database.php
├── public/ajax/
│   ├── mpesa/                        [NEW]
│   │   ├── stk-push.php
│   │   ├── callback.php
│   │   ├── transaction-query.php
│   │   └── retry-payment.php
│   ├── bookings/                     [UPDATED]
│   │   ├── get-slots.php
│   │   └── create-booking.php
│   ├── products/
│   ├── cart/
│   └── reviews/                      [NEW]
├── database/
│   ├── glambymariga.sql              [UPDATED]
│   └── dummy_data.sql                [NEW]
├── logs/
│   ├── error.log
│   ├── audit.log
│   └── payment.log                   [NEW]
├── vendor/
│   └── autoload.php
├── README.md                         [UPDATED]
├── INSTALLATION.md                   [NEW]
├── API_DOCUMENTATION.md              [NEW]
├── MPESA_SETUP.md                    [NEW]
└── .env.example                      [UPDATED]
```

---

## 🔧 CONFIGURATION REQUIREMENTS

### Environment Variables (.env)
```env
# Database
DB_HOST=localhost
DB_USER=glambymariga_user
DB_PASS=SecurePassword123
DB_NAME=glambymariga_db

# M-Pesa Daraja
MPESA_CONSUMER_KEY=your_key_here
MPESA_CONSUMER_SECRET=your_secret_here
MPESA_SHORTCODE=174379
MPESA_PASSKEY=your_passkey_here
MPESA_ENVIRONMENT=production

# Email
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USER=your_email@example.com
MAIL_PASS=your_password

# Site
SITE_NAME=GlamByMariga
SITE_URL=https://glambymariga.com
ADMIN_EMAIL=admin@glambymariga.com

# Optional
INSTAGRAM_ACCESS_TOKEN=your_token
GOOGLE_ANALYTICS_ID=your_id
```

---

## 📊 DATABASE SCHEMA ADDITIONS

**New Tables** (20+):
- `mpesa_transactions`
- `payment_retries`
- `product_images`
- `product_variants`
- `product_reviews`
- `wishlist`
- `testimonials`
- `gallery`
- `holidays`
- `break_times`
- `email_templates`
- `email_logs`
- `coupons`
- `newsletter_subscribers`
- `audit_logs`
- `payment_logs`

**Modified Tables**:
- `bookings` (add slot_locked_until)
- `settings` (add JSON business_hours)
- `products` (add more fields)

**Total Schema Size**: ~50 tables with indexes and views

---

## ✅ QUALITY ASSURANCE

### Security Checklist
- ✅ Prepared statements (all queries)
- ✅ CSRF tokens (all forms)
- ✅ Input validation/sanitization
- ✅ Output escaping (XSS prevention)
- ✅ Password hashing (bcrypt)
- ✅ Session security
- ✅ SSL/TLS ready
- ✅ Audit logging
- ✅ Rate limiting

### Performance
- ✅ Database indexing
- ✅ Query optimization
- ✅ Image optimization
- ✅ Caching strategy
- ✅ CSS/JS minification
- ✅ CDN ready

### Responsive Design
- ✅ Mobile-first approach
- ✅ Touch-friendly buttons (44px min)
- ✅ Breakpoints: 320px, 576px, 768px, 992px, 1200px
- ✅ Tested on: iPhone, iPad, Android, Desktop

---

## 📱 RESPONSIVE BREAKPOINTS

```css
/* Mobile First */
/* Extra Small (320px) */
@media (max-width: 575px) { }

/* Small (576px) */
@media (min-width: 576px) { }

/* Tablet (768px) */
@media (min-width: 768px) { }

/* Desktop (992px) */
@media (min-width: 992px) { }

/* Large (1200px) */
@media (min-width: 1200px) { }
```

---

## 🚀 DEPLOYMENT STEPS

### 1. Pre-Deployment
- [ ] Configure .env file
- [ ] Import database
- [ ] Set folder permissions
- [ ] Configure M-Pesa credentials
- [ ] Set up email service

### 2. cPanel Upload
- [ ] Upload project to public_html
- [ ] Create database
- [ ] Import SQL file
- [ ] Set file permissions (755 for folders, 644 for files)

### 3. Post-Deployment
- [ ] Test admin login
- [ ] Test customer booking
- [ ] Test payment flow (sandbox)
- [ ] Test responsive design
- [ ] Configure backups

---

## 📈 SUCCESS METRICS

### Phase Completion
- ✅ All 7 phases completed on time
- ✅ All code peer-reviewed
- ✅ All tests passing
- ✅ Documentation complete
- ✅ Deployment ready

### Performance
- ✅ Page load < 2 seconds
- ✅ Mobile score > 90
- ✅ Payment success rate > 95%
- ✅ Booking completion rate > 85%

### Quality
- ✅ Zero critical bugs
- ✅ Zero security issues
- ✅ All features working
- ✅ All edge cases handled

---

## 🎯 NEXT STEPS

1. ✅ **Approve this plan**
2. ✅ **Confirm M-Pesa credentials**
3. ✅ **Set timeline expectations**
4. ✅ **Assign team members**
5. ✅ **Start Phase 1 (Branding)**

---

**Questions before we begin?** 🚀

