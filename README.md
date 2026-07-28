# Aurora Platform - Beauty Salon Management System

Production-ready SaaS platform for salon operations, built with PHP 8.3, Bootstrap 5, and MySQL 8.0.

## Quick Start

### Prerequisites
- Docker & Docker Compose
- Git

### Setup (5 minutes)

```bash
# Clone repository
git clone https://github.com/glambymriga/aurora.git
cd aurora

# Copy environment configuration
cp .env.example .env

# Start services
docker-compose up -d

# Install dependencies
docker-compose exec app composer install

# Run database migrations
docker-compose exec app php bin/migrate.php

# Access application
# Frontend: http://localhost
# API: http://localhost/api/v1
# PHPMyAdmin: http://localhost:8080
```

## Project Structure

```
aurora/
├── src/                          # Application code
│   ├── Application/              # Application layer
│   │   ├── Controllers/          # HTTP controllers
│   │   ├── Services/             # Business logic
│   │   ├── Repositories/         # Data access
│   │   ├── Validators/           # Input validation
│   │   └── Exceptions/           # Custom exceptions
│   ├── Domain/                   # Domain layer
│   │   ├── Models/               # Entity models
│   │   ├── Repositories/         # Repository interfaces
│   │   └── ValueObjects/         # Value objects
│   ├── Infrastructure/           # Infrastructure layer
│   │   ├── Database/             # Database implementation
│   │   ├── Http/                 # HTTP layer
│   │   ├── Integrations/         # External service integration
│   │   └── Logging/              # Logging implementation
│   └── Common/                   # Shared utilities
├── public/                       # Web root
│   ├── index.html                # SPA entry point
│   ├── api.php                   # API entry point
│   ├── css/                      # Stylesheets
│   └── js/                       # Frontend JavaScript
├── tests/                        # Test suites
│   ├── Unit/                     # Unit tests
│   └── Integration/              # Integration tests
├── config/                       # Configuration files
├── migrations/                   # Database migrations
├── docker/                       # Docker configuration
└── composer.json                 # PHP dependencies
```

## Features

### Appointment Management
- Book and confirm appointments
- Prevent double-booking
- Automated SMS/Email reminders
- Cancellation handling

### POS & Payments
- M-Pesa integration
- Multiple payment methods
- Invoice generation
- Refund processing

### Customer Management
- Customer profiles
- Loyalty points system
- Lifetime value tracking
- Communication preferences

### Inventory Management
- Product tracking
- Stock level alerts
- Purchase order management
- Stock movement history

### Staff Management
- Staff profiles
- Performance tracking
- Commission calculation
- Schedule management

### Reporting & Analytics
- Daily revenue reports
- Customer analytics
- Staff performance metrics
- Inventory reports

### Admin Panel
- User management
- Role & permission management
- Audit log viewer
- System configuration
- Business settings

## Technology Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| Language | PHP | 8.3+ |
| Database | MySQL | 8.0+ |
| Cache | Redis | 7.0+ |
| Frontend | Bootstrap | 5.3+ |
| API | REST/JSON | v1 |
| Authentication | JWT | HS256 |
| Testing | PHPUnit | 10.0+ |

## API Documentation

API documentation available at `/api/v1/docs`

### Authentication

```bash
# Login
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'

# Response
{
  "success": true,
  "data": {
    "token": "eyJhbGc...",
    "user": {...},
    "expires_in": 3600
  }
}
```

### API Endpoints

- `POST /auth/login` - User authentication
- `GET /appointments` - List appointments
- `POST /appointments` - Create appointment
- `PUT /appointments/{id}` - Update appointment
- `DELETE /appointments/{id}` - Cancel appointment
- `GET /customers` - List customers
- `POST /sales` - Create sale
- `POST /sales/{id}/payment` - Process payment
- `GET /reports/dashboard` - Dashboard metrics
- `GET /reports/revenue` - Revenue report

## Development

### Running Tests

```bash
# Unit tests
docker-compose exec app vendor/bin/phpunit tests/Unit

# Integration tests
docker-compose exec app vendor/bin/phpunit tests/Integration

# All tests
docker-compose exec app composer test
```

### Code Quality

```bash
# PHP CodeSniffer (PSR-12)
docker-compose exec app vendor/bin/phpcs src

# PHPStan (Level 9)
docker-compose exec app vendor/bin/phpstan analyse src
```

### Database Migrations

```bash
# Run migrations
docker-compose exec app php bin/migrate.php

# Seed sample data
docker-compose exec app php bin/seed.php
```

## Deployment

### Production Build

```bash
# Build Docker image
docker build -t aurora:latest .

# Tag for registry
docker tag aurora:latest registry.example.com/aurora:latest

# Push to registry
docker push registry.example.com/aurora:latest
```

### Environment Variables

See `.env.example` for all configuration options. Key variables:

```
APP_ENV=production
APP_DEBUG=false
DB_HOST=db.prod
DB_USERNAME=aurora_user
JWT_SECRET=your-secret-key
MPESA_CONSUMER_KEY=your-mpesa-key
```

## Support & Documentation

- **Developer Guide**: See [docs/DEVELOPER.md](docs/DEVELOPER.md)
- **API Documentation**: See [docs/API.md](docs/API.md)
- **Operations Manual**: See [docs/OPERATIONS.md](docs/OPERATIONS.md)
- **User Guide**: See [docs/USER_GUIDE.md](docs/USER_GUIDE.md)

## License

Proprietary - GlamByMariga Beauty Studio

## Contact

- **Email**: support@glambymriga.com
- **Phone**: +254-XXX-XXXX
- **Status Page**: https://status.aurora.glambymriga.com

---

**Aurora Platform v1.0.0** | Built with ❤️ for beauty salons
