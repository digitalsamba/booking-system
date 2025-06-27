# Session Context Guide

## Minimal Context for Quick Sessions

For quick bug fixes or small tasks, use this minimal prompt:
```
Working on booking-system. Check .ai_dev/session-handover/[latest].md
Task: [specific task]
Branch: [current branch]
```

## Standard Context for Development Sessions

For regular development work:
```
Project: DigitalSamba Booking System
Docs: .ai_dev/session-handover/[latest].md and .ai_dev/tasks/CURRENT_TASKS.md

Today's Goal: [specific feature/fix]
Time: [session duration]
Branch: [working branch]

Start by checking existing todos with TodoRead.
```

## Comprehensive Context for Complex Work

For major features or architectural changes:
```
Project: DigitalSamba Booking System (Deadline: Aug 31, 2025)

Context Files:
1. .ai_dev/session-handover/[latest].md - Previous session
2. .ai_dev/tasks/CURRENT_TASKS.md - Priority tasks  
3. .ai_dev/progress/STATUS_JUNE_2025.md - Overall status
4. CLAUDE.md - Architecture and commands

Current State:
- 60% complete, Payment & Widgets are blockers
- Working on: [specific feature]
- Branch: [branch name]
- Session goal: [concrete deliverable]

Please start with TodoRead, analyze the context, then create a session plan.
```

## Context Hierarchy

### Level 1: Always Include
- Latest session handover
- Current task/goal
- Working branch

### Level 2: For Development Work
- CURRENT_TASKS.md
- Specific technical context
- Time available

### Level 3: For Major Changes
- Progress status
- Architecture (CLAUDE.md)
- Risk factors
- Dependencies

## Smart Context Loading

Instead of loading everything, match context to task type:

### 🐛 Bug Fix
```
Bug in [component]. See .ai_dev/session-handover/[latest].md
Error: [error message]
File: [affected file]
```

### 🎨 Frontend Work
```
Frontend task: [description]
Context: .ai_dev/session-handover/[latest].md
Check booking-system-frontend/src/views/
```

### 💾 Backend API
```
API work: [endpoint/feature]
Context: .ai_dev/session-handover/[latest].md
Check booking-system-backend/src/Controllers/
```

### 💰 Payment Integration
```
Payment integration work. Critical priority!
Docs: .ai_dev/tasks/CURRENT_TASKS.md (Payment section)
Previous: .ai_dev/session-handover/[latest].md
```

## Session Continuity Tips

1. **Name handovers clearly**: `2025-06-07-payment-stripe-setup.md`
2. **Update CURRENT_TASKS.md**: Mark completed items
3. **Note blockers prominently**: In handover "Blockers" section
4. **Track decisions**: Add to .ai_dev/context/ for future reference

## Quick Reference Paths

Save these in your notes for copy-paste:
```
.ai_dev/session-handover/
.ai_dev/tasks/CURRENT_TASKS.md
.ai_dev/progress/STATUS_JUNE_2025.md
.ai_dev/project-plan/COMPLETION_ROADMAP.md
CLAUDE.md
```

## Progressive Disclosure Pattern

Start simple, add context as needed:

1. **Initial prompt**: Basic task + latest handover
2. **If assistant needs more**: Add CURRENT_TASKS.md
3. **If architectural questions**: Point to CLAUDE.md
4. **If timeline questions**: Show STATUS or ROADMAP

This prevents token waste while ensuring the assistant has what it needs.