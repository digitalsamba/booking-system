# Booking System Frontend

## Overview
This is the frontend application for the Booking System, built with Vue.js and Pinia for state management. It provides a user interface for managing bookings, user profiles, and service provider availability.

## Features
- User authentication (login/register)
- Profile management
- Booking creation and management
- Service provider availability management
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
- `/src/stores` - Pinia stores for state management
- `/src/services` - API service calls
- `/src/router` - Vue Router configuration
- `/src/assets` - Static assets like images and global CSS

## API Integration
This frontend connects to the booking-system-backend PHP API.

## Authentication
JWT token-based authentication is implemented with tokens stored in localStorage.