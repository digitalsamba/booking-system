# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Backend (PHP + MongoDB)
```bash
# Start MongoDB container
docker-compose up -d mongodb

# Check MongoDB connection
php booking-system-backend/mongodb_check.php

# Test email configuration
php booking-system-backend/email_test.php

# Check PHP syntax
find booking-system-backend -name "*.php" -exec php -l {} \;

# Run API locally (from backend directory)
cd booking-system-backend && php -S localhost:8080 -t public router.php
```

### Frontend (Vue.js + Vite)
```bash
# Install dependencies
cd booking-system-frontend && npm install

# Start development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview
```

### Git Workflow
```bash
# Create feature branch from develop
git checkout -b feature-name

# Conventional commits
git commit -m "feat: add payment integration"
git commit -m "fix: resolve routing issue"
git commit -m "docs: update API documentation"
```

## Architecture Overview

### Backend Structure (MVC Pattern)
- **Controllers**: Handle HTTP requests, located in `src/Controllers/`
  - All inherit from `BaseController` for common functionality
  - Use FastRoute for routing (see `config/routes.php`)
  - Return JSON responses via `Response::json()`

- **Models**: Data layer with MongoDB integration in `src/Models/`
  - Extend `BaseModel` for common database operations
  - Each model maps to a MongoDB collection
  - Handle data validation and business logic

- **Services**: Business logic and external integrations in `src/Services/`
  - `BrandingService`: Logo uploads and branding management
  - Email providers in `Utils/Email/Providers/`

- **Configuration**: Environment-based config in `config/`
  - Uses `.env` file for sensitive data
  - Database connections in `Utils/Database.php`

### Frontend Structure (Vue 3 + Pinia)
- **Views**: Page components in `src/views/`
  - Each view corresponds to a route
  - Use Composition API

- **Stores**: State management with Pinia in `src/stores/`
  - `auth.js`: JWT token and user state
  - `bookings.js`: Booking data management

- **Services**: API communication in `src/services/`
  - `api.js`: Axios instance with interceptors
  - Service modules for each API domain

### Key Integration Points
- **JWT Authentication**: All protected routes require Bearer token
- **File Uploads**: Logos stored in `public/uploads/branding/{providerId}/`
- **Email System**: Multi-provider support (SendGrid, SES, SMTP)
- **Digital Samba**: Virtual meeting integration (partially implemented)

## Current Development Status

### Active Features
- **Branding System** (90% complete): Custom logos, colors, fonts per provider
- **Booking Flow**: Complete CRUD operations with email notifications
- **Availability Management**: Flexible time slot configuration

### Pending Implementation
- **Payment Integration**: Stripe integration planned
- **Embeddable Widgets**: Cross-domain booking widgets
- **Analytics Dashboard**: Booking statistics and reporting

## Common Development Tasks

### Adding a New API Endpoint
1. Add route in `config/routes.php`
2. Create/update controller method
3. Add model methods if needed
4. Update frontend API service
5. Test with auth token if protected

### Debugging MongoDB Queries
```php
// Enable query logging in model
$this->db->getDatabase()->command(['profile' => 2]);

// Check MongoDB logs
docker logs booking-system-mongodb
```

### Testing Email Templates
```bash
# Direct template test
php booking-system-backend/template_email_test.php

# Full email service test
php booking-system-backend/email_service_test.php
```

## Known Issues & Workarounds

1. **FastRoute Parameter Mismatch**: Some controllers expect different parameter names than routes provide. Check controller method signatures.

2. **Static File Serving**: Development server needs explicit handling for uploaded files. Production will use web server.

3. **CORS in Development**: Frontend runs on :5173, backend on :8080. CORS headers added in index.php.

## Project Management

The `.ai_dev/` directory contains:
- `project-plan/`: Roadmaps and analysis documents
- `session-handover/`: AI session continuity docs
- `tasks/`: Current task tracking
- `progress/`: Implementation status

Refer to `.ai_dev/project-plan/COMPLETION_ROADMAP.md` for the development timeline through August 2025.