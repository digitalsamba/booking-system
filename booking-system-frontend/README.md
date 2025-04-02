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
- Public booking interface for clients
- Responsive design for all devices
- Dark/Light theme support
- Real-time availability updates

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
  - `AppLogo.vue` - Application logo component
  - `AppHeader.vue` - Main navigation header
  - `AppFooter.vue` - Footer component
  - `BookingForm.vue` - Booking creation form
  - `AvailabilityCalendar.vue` - Calendar view for availability
- `/src/views` - Page components
  - `LoginView.vue` - User authentication
  - `HomeView.vue` - Dashboard and overview
  - `AvailabilityView.vue` - Manage service provider availability
  - `BookingsView.vue` - Handle booking management
  - `ProfileView.vue` - User profile management
  - `PublicBookingView.vue` - Public booking interface
- `/src/stores` - Pinia stores for state management
  - `auth.js` - Authentication state
  - `bookings.js` - Booking management
  - `availability.js` - Availability management
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
- Custom theme with brand colors
- Responsive navigation drawer

## API Integration
This frontend connects to the booking-system-backend PHP API, handling:
- Availability slot generation and management
- User authentication
- Profile updates
- Booking operations
- Digital Samba meeting integration

## Authentication
JWT token-based authentication is implemented with tokens stored in localStorage.

## Recent Updates
- Added confirmation dialogs for slot deletion
- Improved button styling and consistency
- Enhanced error handling and user feedback
- Added bulk slot generation with flexible parameters
- Improved date and time formatting
- Added public booking interface
- Enhanced logo display across all pages
- Improved responsive design for mobile devices
- Added loading states and error handling
- Implemented proper form validation
- Added dark/light theme support

## Development Notes
- The application uses Vite for development and building
- Environment variables are managed through `.env` files
- API endpoints are configured in `src/services/api.js`
- Theme customization is handled in `src/plugins/vuetify.js`
- Global styles are defined in `src/assets/styles/`
