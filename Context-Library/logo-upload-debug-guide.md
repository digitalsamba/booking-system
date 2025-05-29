# Logo Upload Debug Guide

## Overview
The brand logo display is now working perfectly, but the logo upload functionality needs debugging. This guide provides the investigation path for the next session.

## Current Working State ✅
- Brand logos display correctly on booking pages
- Backend API serving branding settings properly
- Database contains branding records with logoUrl field
- Manual database updates work correctly

## Upload System Components to Debug

### 1. Backend Upload Endpoint
**File**: `/src/Controllers/BrandingController.php`
**Method**: `uploadLogo()`
**Route**: `POST /api/branding/logo`

```php
public function uploadLogo(): void
{
    // Check authentication
    // Validate $_FILES['logoFile'] 
    // Call BrandingService::handleLogoUpload()
    // Return JSON response
}
```

### 2. Upload Service Logic
**File**: `/src/Services/BrandingService.php` 
**Method**: `handleLogoUpload()`

Expected functionality:
- File validation (type, size)
- File storage to uploads directory
- Database update with logoUrl
- Return success/error response

### 3. Frontend Upload Interface
**Access**: http://localhost:3002/branding (requires authentication)
**Components**: File upload form, preview, submit handling

### 4. File Storage System
**Directory**: `/public/uploads/logos/`
**Permissions**: Write access required
**URL Generation**: Convert file path to accessible URL

## Debug Checklist

### Authentication Access
- [ ] Login to branding management interface
- [ ] Test with conal1 user or test@example.com/password123
- [ ] Verify branding page loads correctly

### Upload Form Testing
- [ ] Locate file upload input
- [ ] Test file selection (PNG, JPG, SVG)
- [ ] Check form submission behavior
- [ ] Monitor network requests in dev tools

### Backend Debugging
- [ ] Test upload endpoint directly
- [ ] Check $_FILES array handling
- [ ] Verify file move operations
- [ ] Test database update queries
- [ ] Check error logging

### File System Issues
- [ ] Verify uploads directory exists and is writable
- [ ] Test file path generation
- [ ] Check URL accessibility from frontend
- [ ] Validate MIME type handling

### Error Scenarios
- [ ] File too large
- [ ] Invalid file type
- [ ] Upload directory permissions
- [ ] Database update failures
- [ ] CORS issues

## Test Files for Upload
Prepare test images:
- Small PNG (< 1MB)
- JPEG file
- SVG file (if supported)
- Large file (for size limit testing)

## Expected Flow
1. User uploads file via frontend form
2. Frontend sends multipart/form-data to backend
3. Backend validates and stores file
4. Database updated with new logoUrl
5. Frontend refreshes to show new logo
6. Booking page immediately shows updated logo

## Success Metrics
- File successfully uploaded to uploads directory
- Database logoUrl field updated
- New logo displays on booking page
- Error handling works for invalid files

---
*Ready for comprehensive logo upload debugging*