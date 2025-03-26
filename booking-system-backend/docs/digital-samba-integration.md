# Digital Samba Integration Documentation

This document provides information about the Digital Samba video conferencing integration with the booking system.

## Overview

The Digital Samba integration allows for creating virtual meeting rooms and generating access tokens for meeting participants. This integration is used to provide video conferencing capabilities for bookings within the platform.

## Configuration

### Required Files

- `config/digitalsamba.php`: Contains API base URL and default room settings
- `src/Controllers/DigitalSambaController.php`: Handles API communication and room/token management

### API Credentials

Each provider (user) requires the following credentials to use Digital Samba:

1. **Developer Key**: API key for authenticating with Digital Samba API
2. **Team ID**: The provider's team identifier within Digital Samba

These credentials must be stored in the user's profile.

## Features

### Room Creation

- Rooms are created for each booking
- Room properties include:
  - Privacy settings (public/private)
  - Language settings
  - Roles (moderator, attendee)
  - Default settings from configuration

### Token Generation

- Separate tokens are generated for each meeting participant
- Providers receive moderator role tokens
- Customers receive attendee role tokens
- Tokens include participant display names

### Meeting Links

- Customer and provider meeting links are stored in the booking record
- Links contain embedded access tokens for direct meeting access
- Room IDs are stored for future token generation

## API Response Handling

The Digital Samba API returns meeting links in the `link` property (rather than `url`). Our implementation handles both formats:

```php
// Extract meeting URLs from tokens - Digital Samba API may return 'link' instead of 'url'
$providerLink = $providerToken['link'] ?? $providerToken['url'] ?? null;
$customerLink = $customerToken['link'] ?? $customerToken['url'] ?? null;
```

## Testing

A test script (`ds_test.php`) is provided to verify the Digital Samba integration:

1. Creates a test user with Digital Samba credentials (if needed)
2. Creates a test booking
3. Tests meeting link generation
4. Tests direct API access to Digital Samba

Run the test with:
```bash
php booking-system-backend/ds_test.php
```

## API Endpoints

The integration uses the following Digital Samba API endpoints:

- `POST /api/v1/rooms`: Create a new meeting room
- `POST /api/v1/rooms/{roomId}/token`: Generate a participant token for a room

## Error Handling

The integration includes comprehensive error handling:

- API request failures are logged
- User-friendly error messages are returned
- Room creation and token generation failures are handled gracefully

## Troubleshooting

Common issues:

1. **Missing credentials**: Ensure provider has both developer_key and team_id in their profile
2. **API errors**: Check error logs for detailed API response information
3. **Invalid token**: Ensure token is generated with a valid room ID and team ID
4. **Room creation failure**: Verify the team ID is valid and API credentials are correct