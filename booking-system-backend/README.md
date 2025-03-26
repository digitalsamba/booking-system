# Booking System Backend API

## Overview

This backend API provides a complete solution for managing bookings, users, availability, and integrating with Digital Samba for video conferencing. It's built with PHP and uses MongoDB as the database backend.

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

## Configuration

The application uses configuration files located in the `config/` directory:

- `database.php` - MongoDB connection settings
- `auth.php` - JWT token settings
- `digitalsamba.php` - Digital Samba integration settings
- `cors.php` - CORS policy configuration

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

## Architecture

The application follows an MVC architecture:

- **Controllers**: Handle API requests and responses
- **Models**: Interact with MongoDB database
- **Utils**: Provide common functionality (Response, JwtAuth, etc.)
- **Router**: Routes API requests to appropriate controllers

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
- `PUT /availability/:id` - Update availability
- `DELETE /availability/:id` - Remove availability

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