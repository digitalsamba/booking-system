# Booking System Codebase Guide

## Build & Test Commands
- Install dependencies: `cd booking-system-backend && composer install`
- Syntax check: `php booking-system-backend/syntax_check.php`
- Run development server: `cd booking-system-backend && php -S localhost:8000 router.php`
- Run API test: `php booking-system-backend/api_test.php`
- Run MongoDB test: `php booking-system-backend/mongodb_integration_test.php`
- Check single file syntax: `php -l booking-system-backend/path/to/file.php`

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

Check existing controller/model implementations before adding new code.