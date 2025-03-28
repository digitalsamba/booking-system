# Development Notes

This document contains important information about the development process, recent changes, and known issues that may be helpful for future development work.

## Digital Samba Integration

### Profile Management (March 2025)

The system now correctly stores and retrieves Digital Samba credentials in the user profile:

1. **Backend Changes:**
   - Added `team_id` and `developer_key` fields to the allowed list of profile fields in `UserModel::updateProfile()`
   - Updated profile API responses to include these fields in:
     - `AuthController::getProfile()`
     - `AuthController::updateProfile()`
     - `AuthController::login()`

2. **Frontend Changes:**
   - Updated the profile page to use the API for saving profile data
   - Added fallback to localStorage when API is unavailable
   - Implemented automatic merging of Digital Samba credentials between API and localStorage

### Key Notes for Developers

1. **Authentication Flow:**
   - The system uses JWT tokens for authentication
   - Tokens are stored in localStorage and sent with each request
   - The backend has been updated to handle various header formats for better compatibility

2. **Profile Data Structure:**
   - User profiles contain the following fields:
     - `id`: MongoDB ObjectId (string format)
     - `username`: Username for login (cannot be changed)
     - `email`: User's email address
     - `display_name`: Name shown to others in meeting links
     - `role`: User role (user, admin)
     - `team_id`: Digital Samba team identifier
     - `developer_key`: Digital Samba API developer key

3. **Known Issues:**
   - The frontend still uses localStorage as a fallback for API issues
   - Digital Samba authentication needs to be validated separately
   - Frontend does not currently refresh the token when it expires

4. **Future Improvements:**
   - Implement token refresh mechanism
   - Add validation for Digital Samba credentials
   - Create a testing endpoint for Digital Samba integration
   - Add UI feedback when Digital Samba credentials are invalid

## SambaConnect Branding (March 2025)

The application has been branded as "SambaConnect" with consistent styling across all components.

### Branding Elements

1. **Brand Name:**
   - Application name: SambaConnect
   - Provider: Digital Samba

2. **Color Palette:**
   - Primary color: `#f06859` (coral/salmon)
   - Secondary color: `#323e66` (navy blue)
   - CSS variables are defined in `src/assets/main.css`

3. **Typography:**
   - Primary font: Inter (sans-serif)
   - Font is imported from Google Fonts
   - Font weights used: 400 (regular), 500 (medium), 600 (semibold), 700 (bold)

4. **Logo:**
   - SVG format stored at `public/assets/logo.svg`
   - Used in LoginView, RegisterView, and other key entry points
   - Component available as `AppLogo.vue` for consistent display

### Implementation Details

1. **CSS Variables:**
   - Global variables defined in `src/assets/main.css`
   - Applied consistently across components via CSS inheritance

2. **Component Styling:**
   - Forms and buttons use the Inter font family
   - Headers use the brand colors (primary for highlights, secondary for main text)
   - Consistent border radius (8px) and shadow styling for cards and modals

3. **Login/Register Experience:**
   - Updated with brand logo and colors
   - Consistent styling between authentication views

### Future Branding Improvements

1. **Favicon Implementation:**
   - Add branded favicon based on the logo
   - Update manifest.json with app icons in various sizes

2. **Email Templates:**
   - Apply branding to email notifications
   - Create consistent header/footer for emails

3. **Print Styles:**
   - Add print-specific stylesheets for booking confirmations
   - Ensure logo displays properly in print context

## Troubleshooting Common Issues

1. **Authentication Problems:**
   - Check that the JWT token is being correctly stored and sent in the Authorization header
   - Verify that the token format is `Bearer <token>`
   - Look for CORS issues if requests are failing from the frontend

2. **Profile Updates Not Saving:**
   - Ensure the backend model includes all fields in the allowed list
   - Check that the controller is correctly returning all fields
   - Verify that the data is being properly formatted in both directions

3. **Digital Samba Integration Issues:**
   - Verify that both `team_id` and `developer_key` are set correctly
   - Check the Digital Samba API documentation for any changes to the API
   - Use the debug endpoints to verify credential validity

## API User Profile Endpoints

### GET /auth/profile
Returns the current user's profile information.

**Response:**
```json
{
  "id": "user_id_string",
  "username": "username",
  "email": "user@example.com",
  "role": "user",
  "display_name": "User Name",
  "team_id": "digital_samba_team_id",
  "developer_key": "digital_samba_developer_key",
  "created_at": "timestamp"
}
```

### POST /auth/profile
Updates the current user's profile information.

**Request:**
```json
{
  "email": "new_email@example.com",
  "display_name": "New Display Name",
  "team_id": "digital_samba_team_id",
  "developer_key": "digital_samba_developer_key"
}
```

**Response:**
```json
{
  "message": "Profile updated successfully",
  "user": {
    "id": "user_id_string",
    "username": "username",
    "email": "new_email@example.com",
    "role": "user",
    "display_name": "New Display Name",
    "team_id": "digital_samba_team_id",
    "developer_key": "digital_samba_developer_key",
    "updated_at": "timestamp"
  }
}
```