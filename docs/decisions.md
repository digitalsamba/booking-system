# Architectural Decisions

## Branding Feature - Backend Implementation (Initial)

**Date:** $(Get-Date -Format yyyy-MM-dd)

**Context:** Need to implement backend support for storing and retrieving service provider branding settings for booking forms.

**Decision:**

1.  **Database Schema (MongoDB):**
    *   A new collection `branding_settings` will be created.
    *   Each document will represent settings for one user, linked via a `userId` (ObjectId reference to the `users` collection).
    *   Schema includes fields: `userId`, `logoUrl` (string), `primaryColor` (string), `secondaryColor` (string), `backgroundColor` (string), `textColor` (string), `fontFamily` (string, optional), `customCss` (string, optional), `updatedAt` (ISODate).

2.  **Backend Structure (PHP):**
    *   A new service class `App\Services\BrandingService` created in `src/Services/` to handle database interactions (get, update/upsert).
    *   A new controller class `App\Controllers\BrandingController` created in `src/Controllers/` to handle API requests.
    *   Assumed dependency injection or service location provides MongoDB client, Logger, and AuthService instances.

3.  **API Endpoints:**
    *   `GET /api/branding`: Retrieves branding settings for the authenticated user. Handled by `BrandingController::getSettings`.
    *   `PUT /api/branding`: Updates or creates branding settings for the authenticated user. Handled by `BrandingController::updateSettings`.
    *   Routes added to `router.php` using `preg_match` and manual controller instantiation, consistent with existing routing patterns.
    *   Authentication handled by checking user ID via `AuthService` in the controller.

**Rationale:**

*   Schema provides necessary fields based on `branding-guidelines`.
*   Separation of concerns between Controller (HTTP layer) and Service (business logic/DB interaction).
*   Follows existing project structure (`src/Controllers`, `src/Services`).
*   Routing approach matches the existing `router.php` implementation.
*   Uses `upsert` for `updateBrandingSettings` to simplify creation/update logic.

**Future Considerations:**

*   Implement secure logo file upload handling (separate endpoint/service method).
*   Implement robust input validation and sanitization (colors, URLs, CSS).
*   Consider creating a `BrandingSettings` entity/model class for better data handling.
*   Refactor routing if project adopts a dedicated routing library.
*   Verify and adapt dependency instantiation in `router.php` based on the actual DI setup in `bootstrap.php`.

## Routing Refactor with FastRoute

**Date:** $(Get-Date -Format yyyy-MM-dd)

**Context:** The existing `router.php` script used a mix of prefix-based routing and individual `preg_match` blocks, leading to complexity and errors when handling proxied requests (where the `/api` prefix was stripped before reaching PHP).

**Decision:**

1.  **Introduce FastRoute:** Installed `nikic/fast-route` (v1.3.0) via Composer.
2.  **Centralized Route Definitions:** Created `config/routes.php` to define all application routes (API, public, test) using FastRoute syntax, mapping HTTP methods and URI patterns to `[Controller::class, 'methodName']` handlers.
3.  **Refactor `router.php`:** Replaced the old logic with a new implementation that uses the FastRoute dispatcher (`FastRoute\simpleDispatcher`):
    *   Loads route definitions from `config/routes.php`.
    *   Gets the current HTTP method and URI path (assuming `/api` prefix is stripped by proxy/webserver).
    *   Dispatches the route using FastRoute.
    *   Handles `FOUND`, `NOT_FOUND`, and `METHOD_NOT_ALLOWED` statuses.
    *   For `FOUND` routes, instantiates the specified controller and calls the specified method, passing named route parameters (like `{id}`) directly to the method using PHP 8 spread operator (`...$vars`).
    *   Uses `App\Utils\Response::json` for standardized JSON responses.
4.  **Adjust Controllers:** Modified controller methods (`BookingController::view`, `BookingController::cancel`, `DigitalSambaController::getMeetingLinks`, etc.) to accept route parameters (e.g., `$id`) directly as arguments instead of relying on internal methods like `getIdParam()`.

**Rationale:**

*   Provides a single, standardized, and maintainable way to define and manage routes.
*   Eliminates complex and error-prone conditional logic from `router.php`.
*   Simplifies the handling of URIs, especially regarding the `/api` prefix managed by the frontend proxy.
*   Leverages a well-established library for efficient routing.
*   Improves handling of route parameters.

**Future Considerations:**

*   Implement Dependency Injection (DI) container (like PHP-DI) for controller instantiation instead of `new $controllerClass()` to manage dependencies more effectively.
*   Refine error handling and logging within the router/dispatcher.
*   Verify the dispatching logic added for various controllers (POST/PUT/DELETE methods) in the API route block of the old `router.php` is correctly represented in `config/routes.php` and that the corresponding controller methods exist and function as expected.
*   Clarify the intended routing mechanism for `/public/*` routes vs `/api/*` routes if they are handled differently by the webserver/proxy setup.

--- 