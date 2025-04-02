# Booking System Backend API

## Overview

This backend API provides a complete solution for managing bookings, users, availability, and integrating with Digital Samba for video conferencing. It's built with PHP and uses MongoDB as the database backend.

## Features
- User authentication with JWT
- Profile management
- Booking creation and management
- Service provider availability management
  - Generate time slots with customizable parameters
  - Bulk slot generation with date range selection
  - Daily time slot configuration
  - Flexible slot duration options
  - Day of week selection
  - Individual and bulk slot deletion
- Integration with Digital Samba for video conferencing
- CORS support for frontend integration
- Rate limiting and security measures
- Comprehensive error handling
- Input validation and sanitization

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Development](#development)
- [Testing](#testing)
- [Architecture](#architecture)
- [Authentication](#authentication)
- [API Endpoints](#api-endpoints)
  - [Authentication Endpoints](#authentication-endpoints)
  - [Booking Endpoints](#booking-endpoints)
  - [Digital Samba Integration](#digital-samba-integration)
  - [Availability Endpoints](#availability-endpoints)
  - [Service Endpoints](#service-endpoints)
  - [User Endpoints](#user-endpoints)
- [Code Style Guidelines](#code-style-guidelines)
- [Error Handling](#error-handling)

## Installation

1. Clone the repository
2. Install dependencies:
   ```
   cd booking-system-backend
   composer install
   ```
3. Set up MongoDB:
   - Install MongoDB locally
   - OR run MongoDB using Docker:
     ```
     docker run -d -p 27017:27017 --name mongodb mongo
     ```
   - **Note for VirtualBox VM users**: If running in a VM with limited CPU features, consider using an older MongoDB version compatible with your environment
4. Configure MongoDB connection in `config/database.php`
5. Set up JWT secret key in `config/auth.php`
6. Configure Digital Samba API keys in `config/digitalsamba.php`
7. Set up CORS configuration in `config/cors.php` if needed

## Configuration

The application uses configuration files located in the `config/` directory:

- `database.php` - MongoDB connection settings
- `auth.php` - JWT token settings
- `digitalsamba.php` - Digital Samba integration settings
- `cors.php` - CORS policy configuration
- `rate_limit.php` - Rate limiting settings

## Development

Start the development server:

```
cd booking-system-backend
php -S 0.0.0.0:8000 router.php
```

Note: Using `0.0.0.0` instead of `localhost` allows access from other devices on the network or from within a virtual machine.

## Testing

The application provides several testing utilities:

- Syntax check: `php booking-system-backend/syntax_check.php`
- Check single file syntax: `php -l booking-system-backend/path/to/file.php`
- API test: `php booking-system-backend/api_test.php`
- MongoDB test: `php booking-system-backend/mongodb_integration_test.php`
- Booking customer test: `php booking-system-backend/test_booking_customer.php`
- Digital Samba integration test: `php booking-system-backend/ds_test.php`
- Simple test interface: Open `/public/test-provider-api-simple.html` in a browser
- Full booking test interface: Open `/public/test-provider-booking.html` in a browser

### Testing Notes

1. **Directory Structure**: The application uses PSR-4 autoloading with the namespace `App\` mapping to the `src/` directory. Class namespaces must match directory structure capitalization (e.g., `App\Controllers\AuthController` must be in `src/Controllers/AuthController.php`).

2. **MongoDB Testing**: The `mongodb_integration_test.php` script is useful for verifying MongoDB connectivity. For VirtualBox VMs without AVX support, use MongoDB 4.4 or earlier.

3. **Test Order**:
   - First run `php booking-system-backend/mongodb_integration_test.php` to verify database connectivity
   - Then run `php booking-system-backend/api_test.php` to test basic API functionality
   - Finally use the web interfaces for interactive testing

4. **Troubleshooting**:
   - Most "class not found" errors are related to namespace/directory capitalization mismatches
   - MongoDB connection issues may require changing the MongoDB version or connection string in `config/database.php`
   - CORS issues may require updating the allowed origins in `config/cors.php`

## Architecture

The application follows an MVC architecture:

- **Controllers**: Handle API requests and responses
- **Models**: Interact with MongoDB database
- **Utils**: Provide common functionality (Response, JwtAuth, etc.)
- **Router**: Routes API requests to appropriate controllers
- **Middleware**: Handle authentication, CORS, and rate limiting

## Authentication

The API uses JWT token authentication:

1. User logs in with email/password
2. Server validates credentials and returns JWT token
3. Client includes token in Authorization header for subsequent requests
4. Server validates token and authorizes user

## Data Model

### User
- **username**: String - Unique username for login
- **email**: String - User's email address
- **password**: String - Hashed password
- **display_name**: String - Name displayed to others (e.g., in meeting links)
- **role**: String - User role (user, admin)
- **developer_key**: String - Digital Samba API developer key
- **team_id**: String - Digital Samba team identifier

### Booking
- **provider_id**: ObjectId - ID of the service provider
- **slot_id**: ObjectId - ID of the availability slot
- **customer**: Object - Contains customer details (name, email, phone)
- **start_time**: DateTime - Booking start time
- **end_time**: DateTime - Booking end time
- **status**: String - Booking status (confirmed, completed, cancelled)
- **notes**: String - Optional booking notes
- **provider_link**: String - Digital Samba meeting link for provider
- **ds_room_id**: String - Digital Samba room identifier
- **customer**: Object
  - **name**: String - Customer name
  - **email**: String - Customer email
  - **phone**: String - Customer phone (optional)
  - **customer_link**: String - Digital Samba meeting link for customer

## API Endpoints

### Authentication Endpoints

- `POST /auth/register` - Register a new user
- `POST /auth/login` - Authenticate and get JWT token
- `GET /auth/profile` - Get user profile information
- `POST /auth/profile` - Update user profile information

### Booking Endpoints

- `GET /bookings` - List all bookings
- `GET /bookings/:id` - Get booking details
- `POST /bookings` - Create a new booking
- `PUT /bookings/:id` - Update a booking
- `DELETE /bookings/:id` - Cancel a booking

### Digital Samba Integration

- `POST /meetings/create` - Create a new virtual meeting
- `GET /meetings/:id` - Get meeting details
- `GET /booking/:id/meeting-links` - Get meeting links for a booking
- `POST /booking/:id/meeting-links` - Generate meeting links for a booking

For detailed documentation on the Digital Samba integration, see [docs/digital-samba-integration.md](docs/digital-samba-integration.md).

### Availability Endpoints

- `GET /availability` - List available time slots
- `POST /availability` - Create availability slots
- `POST /availability/generate` - Generate availability slots based on a template
- `GET /availability/:id` - Get availability slot details
- `PUT /availability/:id` - Update availability
- `DELETE /availability/deleteSlot?id={slot_id}` - Remove availability slot (Note: Uses query parameter format)

> **⚠️ Important Note on API Implementation**: While the API follows REST naming conventions for most endpoints, 
> the delete availability endpoint specifically requires using the query parameter format 
> (`/availability/deleteSlot?id={slot_id}`) rather than the path parameter format.

#### Generate Availability Slots

The `/availability/generate` endpoint automatically creates multiple availability slots based on a template:

Request body format:
```json
{
  "start_date": "2025-04-01",
  "end_date": "2025-04-30",
  "slot_duration": 30,
  "daily_start_time": "09:00",
  "daily_end_time": "17:00",
  "days_of_week": [1, 2, 3, 4, 5]
}
```

### Service Endpoints

- `GET /services` - List all services
- `GET /services/:id` - Get service details
- `POST /services` - Create a new service
- `PUT /services/:id` - Update a service
- `DELETE /services/:id` - Delete a service

### User Endpoints

- `GET /users` - List all users (admin only)
- `GET /users/:id` - Get user details
- `PUT /users/:id` - Update user profile
- `DELETE /users/:id` - Delete user (admin only)

## Code Style Guidelines

- **Namespaces**: Use `App\` namespace with proper PSR-4 autoloading
- **Error Handling**: Use try/catch with error logging; return JSON errors with proper HTTP status codes
- **Database**: Use MongoDB through models; format ObjectIds and UTCDates consistently 
- **Documentation**: Use PHPDoc for classes and methods
- **Naming**: PascalCase for classes, camelCase for methods/variables
- **Authentication**: JWT token authentication through Authorization header
- **Response Format**: Use Response utility class for consistent JSON responses
- **Controllers**: Extend BaseController; use getJsonData() to parse requests 
- **Models**: Extend BaseModel; use formatDocument() to normalize responses

## Error Handling

The API returns consistent error responses in the following format:

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable error message",
    "details": {}
  }
}
```

Common error codes:
- `VALIDATION_ERROR` - Invalid input data
- `NOT_FOUND` - Resource not found
- `UNAUTHORIZED` - Authentication required
- `FORBIDDEN` - Insufficient permissions
- `INTERNAL_ERROR` - Server error

## Recent Updates
- Added duplicate slot checking in availability generation
- Improved date handling and timezone management
- Enhanced error logging and debugging
- Added bulk slot deletion endpoint
- Improved MongoDB query optimization
- Added validation for slot generation parameters
- Enhanced error messages and response formatting
- Added CORS support for frontend integration
- Implemented rate limiting
- Added input validation and sanitization
- Improved security measures
- Enhanced Digital Samba integration error handling

## Development
- PHP 8.1 or higher
- MongoDB 4.4 or higher
- Composer for dependency management
- JWT for authentication

## Testing
```bash
# Run syntax check
php syntax_check.php

# Test MongoDB connection
php mongodb_check.php

# Test API endpoints
php api_test.php
```

## Security
- JWT token-based authentication
- Input validation and sanitization
- MongoDB injection prevention
- CORS configuration
- Rate limiting
- Secure password hashing
- XSS prevention
- CSRF protection