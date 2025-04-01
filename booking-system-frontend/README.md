# Booking System Frontend

## Overview
This is the frontend application for the Booking System, built with Vue.js and Pinia for state management. It provides a user interface for managing bookings, user profiles, and service provider availability.

## Features
- User authentication (login/register)
- Profile management
- Booking creation and management
- Service provider availability management
  - Generate time slots with customizable parameters
  - Bulk slot generation with date range selection
  - Daily time slot configuration
  - Flexible slot duration options (15, 30, 45, 60 minutes)
  - Day of week selection
  - Individual and bulk slot deletion with confirmation dialogs
- Integration with Digital Samba for video conferencing

## Project Setup

```bash
# Install dependencies
npm install

# Start development server
npm run dev

# Build for production
npm run build

# Lint and fix files
npm run lint
```

## Structure
- `/src/components` - Reusable Vue components
- `/src/views` - Page components
  - `AvailabilityView.vue` - Manage service provider availability
  - `BookingView.vue` - Handle booking creation and management
  - `ProfileView.vue` - User profile management
- `/src/stores` - Pinia stores for state management
- `/src/services` - API service calls
- `/src/router` - Vue Router configuration
- `/src/assets` - Static assets like images and global CSS

## UI Components
The application uses Vuetify for UI components, including:
- Custom styled buttons with consistent spacing and typography
- Confirmation dialogs for destructive actions
- Responsive grid layouts
- Form controls with validation
- Loading states and error handling

## API Integration
This frontend connects to the booking-system-backend PHP API, handling:
- Availability slot generation and management
- User authentication
- Profile updates
- Booking operations

## Authentication
JWT token-based authentication is implemented with tokens stored in localStorage.

## Recent Updates
- Added confirmation dialogs for slot deletion
- Improved button styling and consistency
- Enhanced error handling and user feedback
- Added bulk slot generation with flexible parameters
- Improved date and time formatting
