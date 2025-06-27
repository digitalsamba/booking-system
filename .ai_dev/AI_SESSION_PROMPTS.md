# AI Session Prompts - Copy & Paste Ready

## 🚀 Day 1: Payment Integration (June 6)

### Morning Session - Payment Backend
```
I need to implement Stripe payment integration for the booking system.

Context:
- Check .ai_dev/session-handover/2025-01-06-initial-setup.md
- See .ai_dev/tasks/AI_SPRINT_TASKS.md for today's goals
- We're on aggressive 40-day timeline to July 15

Today's target: Complete payment backend in 4 hours
- Create payment MongoDB schema
- Implement Stripe SDK integration following EmailService pattern
- Build PaymentController like BookingController
- Generate tests like email_service_test.php
- Include webhook handling for payment events

Let's implement the entire payment backend now.
```

### Afternoon Session - Payment Frontend
```
Continue payment integration - frontend components.

Already completed: Payment backend (from morning session)
Now implement: Frontend payment flow

Requirements:
- Payment method selection during booking
- Stripe Elements integration
- Payment confirmation screen
- Payment history in user profile
- Follow patterns from bookings.js store

Complete the frontend in this session with tests.
```

## 🚀 Day 2: Payment Completion (June 7)

```
Complete payment system Day 2/3.

Yesterday: Basic payment backend/frontend
Today: Polish and production features

Tasks:
1. Invoice generation (PDF)
2. Payment receipts via email
3. Refund functionality
4. Payment webhook reliability
5. Comprehensive error handling
6. End-to-end testing

Work through all tasks in this session.
```

## 🚀 Day 4: Widget Architecture (June 9)

```
Implement embeddable booking widget system.

Context: 
- Payment system complete (Days 1-3)
- Check latest handover in .ai_dev/session-handover/
- Target: Complete widget system in 3 days

Today: Widget architecture and security
1. Design secure cross-domain communication
2. Implement CORS properly
3. Create widget configuration API
4. Build widget generator service
5. Handle style isolation
6. Create parent-child messaging

Reference public booking view for UI patterns.
```

## 📋 Standard Session Templates

### Feature Implementation Session
```
Implement [FEATURE NAME] for booking system.

Context: .ai_dev/session-handover/[LATEST].md
Timeline: Day [X] of 40-day sprint
Goal: Complete [FEATURE] end-to-end

Requirements:
1. [Specific requirement]
2. [Another requirement]

Reference existing patterns:
- Backend: [Similar service/controller]
- Frontend: [Similar component/store]
- Tests: [Similar test file]

Deliver complete feature with tests this session.
```

### Bug Fix Session
```
Fix [ISSUE] in booking system.

Issue: [Description]
Location: [File/component]
Impact: [User impact]

Latest context: .ai_dev/session-handover/[LATEST].md

Fix the issue and add tests to prevent regression.
```

### Testing Session
```
Add comprehensive tests for [FEATURE].

Current coverage: [X]%
Target coverage: 90%

Focus areas:
1. Unit tests for [components]
2. Integration tests for [flows]
3. E2E tests for [critical paths]

Use existing test patterns from tests/ directory.
```

## 🎯 Quick Prompts by Feature

### Analytics Dashboard
```
Implement analytics dashboard showing booking statistics.
- Monthly/weekly/daily booking counts
- Revenue tracking
- Provider utilization rates
- Export to CSV/PDF
Follow dashboard patterns from other views.
```

### Digital Samba Integration
```
Complete Digital Samba virtual meeting integration.
- Auto-create rooms on booking
- Add meeting links to confirmations
- Handle meeting webhooks
- Show meeting status in UI
Reference DigitalSambaController for API structure.
```

### Email Configuration UI
```
Create email configuration interface for providers.
- UI for email settings (backend exists)
- Template customization
- Email preview
- Test email sending
Follow BrandingSettingsView pattern.
```

## 💡 Efficiency Boosters

### Parallel Implementation
```
In this session, implement [FEATURE] completely:
1. Backend: Model, Service, Controller, Routes
2. Frontend: Store, Components, Views
3. Tests: Unit and Integration
4. Docs: API and User Guide
Don't separate - do all in parallel.
```

### Pattern Matching
```
Implement [NEW FEATURE] following these patterns:
- Service: Like EmailService.php
- Controller: Like BookingController.php
- Model: Like BookingModel.php
- Frontend: Like bookings.js store
- Tests: Like email_service_test.php
Maintain consistency with existing code.
```

### Test-Driven
```
For [FEATURE], write tests first:
1. Define test cases
2. Implement feature to pass tests
3. Add edge cases
4. Ensure 90% coverage
Generate tests alongside implementation.
```

## 🔥 Power Prompts

### The Feature Blitz
```
FEATURE BLITZ: Payment System

Time: 4 hours
Goal: Working payments end-to-end

Hour 1: Database + Models
Hour 2: Services + Controllers  
Hour 3: Frontend components
Hour 4: Testing + Polish

No delays, no blockers - implement everything now.
Pattern match from existing code.
Generate tests for everything.
Make it production-ready.

GO!
```

### The Integration Sprint
```
INTEGRATION SPRINT: Connect all systems

Tasks:
1. Payment + Booking flow
2. Email notifications for payments
3. Digital Samba + Paid bookings
4. Analytics for revenue

Make everything work together seamlessly.
Test all integration points.
4 hours - complete it all.
```

Remember: Be aggressive with timelines. AI can deliver what traditionally takes weeks in just days. Always push for complete implementation, not partial progress.