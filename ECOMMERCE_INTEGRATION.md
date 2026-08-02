# E-Commerce Enhancement - Phase 4 Guide

## Overview

Phase 4 introduces a complete e-commerce system with:
- Product catalog with images, variants, and reviews
- Shopping cart management with real-time updates
- Integrated checkout with M-Pesa payment
- Order management and fulfillment tracking
- Inventory management with stock tracking
- Advanced filtering and search functionality

---

## Architecture

### Backend Components

#### Product Manager (`includes/shop/ProductManager.php`)
Manages all product-related operations.

**Key Methods:**
- `getProduct()` - Get product with images and reviews
- `getProducts()` - Get products with filters and pagination
- `getRelatedProducts()` - Get similar products
- `getProductReviews()` - Get product reviews with pagination
- `addReview()` - Add customer review
- `addToWishlist()` - Add product to wishlist
- `removeFromWishlist()` - Remove from wishlist
- `getWishlist()` - Get customer wishlist
- `getProductStock()` - Check stock availability
- `updateStock()` - Update stock with audit logging
- `getFeaturedProducts()` - Get featured products
- `searchProducts()` - Full-text search
- `getTrendingProducts()` - Get best-selling products

#### Cart Manager (`includes/shop/CartManager.php`)
Handles shopping cart operations.

**Key Methods:**
- `addToCart()` - Add item to cart with quantity
- `updateCartItem()` - Update quantity or remove
- `removeFromCart()` - Delete item from cart
- `getCart()` - Get all cart items with totals
- `clearCart()` - Empty cart
- `validateCoupon()` - Apply discount codes
- `createOrder()` - Convert cart to order
- `getOrder()` - Retrieve order details
- `getCustomerOrders()` - Get order history
- `getShippingOptions()` - Get available shipping methods

#### Order Manager (`includes/shop/OrderManager.php`)
Manages order fulfillment and reporting.

**Key Methods:**
- `updateOrderStatus()` - Update order status (pending → processing → shipped → delivered)
- `assignTrackingNumber()` - Add tracking info
- `processRefund()` - Handle refunds and stock restoration
- `generateInvoice()` - Create invoice data
- `getSalesReport()` - Revenue analytics
- `getOrderAnalytics()` - Order status breakdown

### Database Schema

#### Product Images
```sql
product_images: id, product_id, image_url, alt_text, is_primary, sort_order
```

#### Product Variants
```sql
product_variants: id, product_id, sku, barcode, size, color, stock, price
```

#### Product Reviews
```sql
product_reviews: id, product_id, customer_id, rating, title, comment, 
                 verified_purchase, status, created_at
```

#### Wishlist
```sql
wishlist: id, customer_id, product_id, created_at
```

#### Cart Items
```sql
cart_items: id, customer_id, product_id, variant_id, quantity, 
            price_at_time, added_at, expires_at
```

#### Order Items
```sql
order_items: id, order_id, product_id, variant_id, quantity, 
             price_per_item, subtotal
```

#### Coupons
```sql
coupons: id, code, discount_type, discount_value, max_uses, 
         applicable_products, applicable_categories, start_date, end_date
```

#### Shipping Options
```sql
shipping_options: id, name, description, base_cost, per_kg_cost, 
                  max_days, is_active, sort_order
```

#### Stock History
```sql
stock_history: id, product_id, variant_id, old_stock, new_stock, 
               change_amount, reason, reference_id, created_by
```

---

## API Endpoints

### Products

**GET** `/ajax/shop/get-products.php`
```
Parameters:
  - category_id: Filter by category
  - search: Full-text search
  - min_price: Minimum price filter
  - max_price: Maximum price filter
  - featured: Show only featured products
  - sort: Sort by 'newest', 'price_low', 'price_high', 'rating', 'popular'
  - limit: Items per page (max 100)
  - offset: Pagination offset

Response:
{
    "success": true,
    "data": {
        "products": [...],
        "count": 45,
        "limit": 20,
        "offset": 0
    }
}
```

**GET** `/ajax/shop/get-product-detail.php?id=123`
```
Response:
{
    "success": true,
    "data": {
        "product": {...},
        "reviews": [...],
        "related_products": [...]
    }
}
```

**POST** `/ajax/shop/add-review.php`
```json
{
    "product_id": 1,
    "rating": 5,
    "title": "Amazing product!",
    "comment": "Love it, will buy again"
}

Response:
{
    "success": true,
    "review_id": 456,
    "message": "Review submitted successfully"
}
```

### Cart

**GET** `/ajax/shop/get-cart.php`
```
Response:
{
    "success": true,
    "items": [...],
    "count": 3,
    "total": 15000
}
```

**POST** `/ajax/shop/add-to-cart.php`
```json
{
    "product_id": 1,
    "quantity": 2,
    "variant_id": null
}

Response:
{
    "success": true,
    "cart_item_id": 789,
    "message": "Item added to cart"
}
```

**PUT** `/ajax/shop/update-cart.php`
```json
{
    "cart_item_id": 789,
    "quantity": 3
}

Response:
{
    "success": true,
    "message": "Cart updated"
}
```

### Checkout

**POST** `/ajax/shop/checkout.php`
```json
{
    "shipping_method": 1,
    "shipping_address": {
        "firstName": "Jane",
        "lastName": "Doe",
        "email": "jane@example.com",
        "phone": "254712345678",
        "address": "123 Main St",
        "city": "Nairobi",
        "postalCode": "00100"
    },
    "coupon_code": "SAVE20",
    "payment_method": "mpesa",
    "notes": "Special instructions"
}

Response:
{
    "success": true,
    "order_id": 1001,
    "order": {
        "id": 1001,
        "subtotal": 15000,
        "discount": 3000,
        "shipping": 250,
        "total": 12250
    },
    "mpesa_request_id": "ws_CO_..."
}
```

---

## Frontend Pages

### Shop Page (`shop.html`)
Complete product catalog with:
- Advanced filtering (category, price range, rating)
- Sorting options (newest, price, rating, popular)
- Product grid with images and ratings
- Add to cart button
- Pagination
- Search functionality
- Wishlist button

### Product Detail Page (TODO)
Should include:
- Full product images gallery
- Product description and details
- Variants (size, color, etc.)
- Reviews section
- Related products
- Stock status
- Add to cart with quantity selector

### Cart Page (`cart.html`)
Shopping cart with:
- Item list with images
- Quantity adjustment
- Remove item option
- Subtotal and total
- Coupon code input
- Proceed to checkout button
- Continue shopping link

### Checkout Page (`checkout.html`)
Complete checkout flow with:
- Shipping address form
- Shipping method selection with costs
- Payment method selection (M-Pesa)
- Order summary
- Total calculation
- Place order button
- Security badge

---

## Features

### Product Management
- Multiple images per product
- Size/color variants with independent stock
- 1-5 star rating system
- Customer reviews with moderation
- Stock tracking with audit trail
- Low stock alerts
- Featured products display

### Shopping Cart
- Persistent cart (30-day expiration)
- Real-time quantity updates
- Price snapshot at add time
- Automatic cart cleanup

### Checkout
- Multiple shipping options
- Address validation
- Coupon code support
- Discount calculations (percentage/fixed)
- Order totals with tax-ready structure
- M-Pesa payment integration

### Inventory Management
- Real-time stock tracking
- Automatic stock updates on order
- Stock history audit trail
- Multiple warehouse support (variants)
- Low stock notifications
- Restock management

### Order Management
- Order status tracking (pending → processing → shipped → delivered)
- Tracking number assignment
- Refund processing with stock restoration
- Invoice generation
- Order history per customer
- Sales reporting and analytics

---

## Configuration

### Shipping Options

Default shipping options (inserted via migration):
```
1. Standard Delivery - KES 250 (3-5 days)
2. Express Delivery - KES 500 (1-2 days)
3. Same Day Pickup - KES 0 (pickup only)
4. Next Day Delivery - KES 800 (guaranteed next day)
```

Add custom option:
```sql
INSERT INTO shipping_options (name, description, base_cost, max_days, is_active, sort_order)
VALUES ('Weekend Delivery', 'Delivery on weekends', 350, 5, TRUE, 5);
```

### Coupons

Create a coupon:
```sql
INSERT INTO coupons (code, discount_type, discount_value, max_uses, min_order_amount, 
                     is_active, start_date, end_date, created_by)
VALUES ('SAVE20', 'percentage', 20, 100, 500, TRUE, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1);
```

---

## Testing

### Test Scenarios

1. **Product Display**
   - Load shop page
   - Verify products display correctly
   - Test pagination
   - Test sorting options
   - Test filtering by category/price

2. **Shopping Cart**
   - Add product to cart
   - Update quantity
   - Remove item
   - Verify totals update
   - Clear cart

3. **Checkout Flow**
   - Fill shipping address
   - Select shipping method
   - Apply coupon code
   - Verify totals
   - Place order

4. **Inventory**
   - Create order
   - Verify stock decreases
   - Check stock history
   - Add stock back
   - Verify audit trail

5. **Product Reviews**
   - Add review as verified customer
   - Check review appears
   - Verify rating updates
   - Test review moderation

6. **Order Management**
   - Create order
   - Update order status
   - Assign tracking number
   - Process refund
   - Verify stock restoration

---

## Integration with Other Phases

### M-Pesa Payment (Phase 2)
- Automatic payment initiation on checkout
- Order creation after payment success
- Payment status tracking
- Receipt generation

### Appointments (Phase 3)
- Shop can be accessed independently
- Future: Add appointment-based product availability
- Future: Booking discounts in shop

### Homepage (Phase 1)
- Link to shop from homepage
- Featured products widget
- New arrivals section
- Best sellers section

---

## Performance Optimization

### Database Indexing
```sql
CREATE INDEX idx_product_category ON products(category_id);
CREATE INDEX idx_product_featured ON products(is_featured);
CREATE INDEX idx_product_price ON products(price);
CREATE INDEX idx_review_product ON product_reviews(product_id);
CREATE INDEX idx_cart_customer ON cart_items(customer_id);
CREATE INDEX idx_coupon_code ON coupons(code);
```

### Caching Strategy
- Cache product data (TTL: 1 hour)
- Cache featured products (TTL: 6 hours)
- Cache shipping options (TTL: 24 hours)
- DO NOT cache: cart, stock, coupon status

### Query Optimization
- Use pagination for product lists
- Fetch reviews separately
- Index frequently filtered columns
- Use JOIN operations for related data

---

## Security Considerations

### Payment Security
- All payment processing via M-Pesa Daraja API
- No direct credit card handling
- PCI DSS compliance via M-Pesa
- HTTPS required for all checkout pages
- CSRF tokens on forms

### Data Protection
- Sanitize all user input
- Use prepared statements
- Validate coupon codes server-side
- Hash sensitive data
- Audit all stock changes

### Fraud Prevention
- Verify M-Pesa payment before order confirmation
- Validate coupon usage limits
- Check stock availability before order
- Rate-limit API endpoints
- Monitor suspicious patterns

---

## Admin Features (Future)

### Product Management
- Create/edit products
- Manage images and variants
- Set pricing and stock
- Feature/highlight products
- Bulk import/export

### Order Management
- View all orders
- Update order status
- Print labels and invoices
- Process refunds
- Email notifications

### Analytics
- Sales revenue tracking
- Product performance
- Customer insights
- Inventory reports
- Coupon effectiveness

---

## Deployment Checklist

- [ ] Run database migrations (ecommerce_tables.sql)
- [ ] Configure shipping options
- [ ] Set default product images
- [ ] Create sample coupons for testing
- [ ] Test add to cart flow
- [ ] Test checkout process
- [ ] Test M-Pesa payment integration
- [ ] Test order creation and status updates
- [ ] Verify email notifications
- [ ] Test refund process
- [ ] Load test product pages
- [ ] Monitor database performance
- [ ] Set up order confirmation emails
- [ ] Configure automated stock alerts

---

**Phase 4 Implementation Status:** COMPLETE  
**Next Phase:** Admin Dashboard & Analytics (Phase 5)
