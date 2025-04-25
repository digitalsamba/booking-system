# SambaConnect Technical Specifications

## Overview

This document provides high-level technical specifications for the SambaConnect booking system. Detailed implementation guidelines are available in the `.cursor/rules` directory as MDC files.

## Architecture

The SambaConnect booking system follows a client-server architecture:
- Frontend: Vue.js 3 with Composition API and Pinia for state management
- Backend: PHP with RESTful API endpoints
- Database: MongoDB
- Deployment: Windows environment with Docker for MongoDB

Refer to `docs\architecture.mermaid` for a detailed visualization of the system architecture.

## Technology Stack

### Frontend
- Vue.js 3.x with Composition API
- Pinia for state management
- Vue Router for navigation
- Vuetify for UI components
- Vite as build tool
- Axios for API communication

### Backend
- PHP 8.x
- MongoDB for data storage
- Composer for dependency management
- JWT for authentication

### Development Environment
- Windows OS
- PowerShell for scripting
- Docker for MongoDB
- Visual Studio Code with Cursor

## Design Patterns and Principles

- **Repository Pattern** for data access
- **Service Layer** for business logic
- **MVC-inspired** structure for backend
- **Composition API** for Vue components
- **REST API** for communication between frontend and backend
- **JWT** for stateless authentication

## Detailed Guidelines

For detailed technical guidelines, refer to the following files in `.cursor/rules/`:

- `general-guidelines.mdc` - Overall project guidelines
- `php-guidelines.mdc` - Backend development standards
- `vue-guidelines.mdc` - Frontend development standards
- `mongodb-guidelines.mdc` - Database operations
- `logging-guidelines.mdc` - Logging implementation
- `windows-environment.mdc` - Windows-specific development
- `payment-integration.mdc` - Payment processing implementation
- `widget-embedding.mdc` - Widget embedding implementation
- `branding-guidelines.mdc` - Branding feature implementation

## Key Non-Functional Requirements

1. **Security**
   - All user inputs must be validated and sanitized
   - Authentication required for all non-public endpoints
   - Secure handling of sensitive information

2. **Performance**
   - API response time < 500ms for standard operations
   - Frontend initial load < 2s
   - Optimize MongoDB queries for performance

3. **Scalability**
   - Design for horizontal scaling
   - Implement proper caching strategies
   - Optimize database access patterns

4. **Maintainability**
   - Follow consistent coding standards
   - Comprehensive documentation
   - Extensive logging for troubleshooting

5. **Compatibility**
   - Support modern browsers (Chrome, Firefox, Safari, Edge)
   - Responsive design for mobile and desktop
   - Cross-platform compatibility