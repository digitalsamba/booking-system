# Developer Notes - API Implementation Details

## Router Behavior

The booking system uses a simple router that works differently from standard RESTful frameworks:

1. The router parses URLs in the format `/controller/action` or `/controller/action/param`
2. It maps this to `ControllerClass->action(param)`
3. Query parameters are NOT automatically passed to controller methods

## API Request Patterns

### Standard Routes
- `GET /controller/action` maps to `ControllerClass->action()`
- `GET /controller/action/:param` maps to `ControllerClass->action($param)`

### Parameter Handling
- **URL Path Parameters**: Passed directly to the method as arguments
- **Query Parameters**: Must be explicitly accessed via `$_GET['param_name']` inside the method
- **Request Body**: Access via `$this->getJsonData()` in controller methods

## Availability Management

The availability management endpoints demonstrate these patterns:

- `GET /availability` - Lists all slots (AvailabilityController->index())
- `GET /availability/:id` - Gets a single slot (AvailabilityController->getSlot($id))
- `DELETE /availability/deleteSlot?id={slotId}` - Deletes a slot (AvailabilityController->deleteSlot())
  - Note: This endpoint gets the ID from the query parameter, not from the URL path

## Security Considerations

- Always check user authentication with `$this->getUserId()` before performing operations
- For operations that modify data, verify the user has permissions to perform the action
- Many model methods require the userId parameter to enforce access control