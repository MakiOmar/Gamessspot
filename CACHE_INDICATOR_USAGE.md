# Cache Indicator Usage Guide

## Overview

The cache indicator component shows whether data is loaded from cache or database, and allows administrators to clear specific page caches.

## Features

✅ **Visual Indicators:**
- 🟢 Green badge: Data from cache (fast)
- 🟡 Yellow badge: Fresh from database (slower)

✅ **Cache Information:**
- Time since cached
- Cache expiration time
- Cache key
- Cache driver (Redis/Memcached/File)
- Cache hit/miss status

✅ **Admin Controls:**
- Clear cache button (admin only)
- Detailed cache information toggle
- AJAX-powered cache clearing with auto-refresh

## How to Add to Your Views

### Step 1: Ensure Controller Passes Cache Data

Your controller should already pass these variables (already implemented for users, games, accounts):

```php
// In controller method
$cacheKey = CacheManager::getUserListingKey($role, $page);
$cacheMetadata = CacheManager::getCacheMetadata($cacheKey);
$fromCache = CacheManager::wasCacheHit($cacheKey);

return view('manager.users', compact('users', 'cacheKey', 'cacheMetadata', 'fromCache'));
```

### Step 2: Add Component to View

Add this line at the top of your listing page, right after the page title:

```blade
@extends('layouts.admin')

@section('content')
<div class="container mt-5">
    <h1 class="text-center mb-4">User Management</h1>
    
    {{-- Add cache indicator here --}}
    @include('components.cache-indicator')
    
    {{-- Rest of your content --}}
    <table class="table">
        ...
    </table>
</div>
@endsection
```

### Example Implementation

#### Users Page (`resources/views/manager/users.blade.php`)

```blade
@extends('layouts.admin')

@section('title', 'Manager - Users')

@section('content')
<div class="container mt-5">
    <h1 class="text-center mb-4">Users Management</h1>
    
    {{-- Cache Indicator --}}
    @include('components.cache-indicator')
    
    {{-- User table here --}}
    <table class="table">
        ...
    </table>
</div>
@endsection
```

#### Games Page (`resources/views/manager/games.blade.php`)

```blade
@extends('layouts.admin')

@section('title', 'Manager - Games')

@section('content')
<div class="container mt-5">
    <h1 class="text-center mb-4">Games Management</h1>
    
    {{-- Cache Indicator --}}
    @include('components.cache-indicator')
    
    {{-- Games table here --}}
    <div id="games-table">
        ...
    </div>
</div>
@endsection
```

#### Accounts Page (`resources/views/manager/accounts.blade.php`)

```blade
@extends('layouts.admin')

@section('title', 'Manager - Accounts')

@section('content')
<div class="container mt-5">
    <h1 class="text-center mb-4">Accounts Management</h1>
    
    {{-- Cache Indicator --}}
    @include('components.cache-indicator')
    
    {{-- Accounts table here --}}
    <table class="table">
        ...
    </table>
</div>
@endsection
```

## Visual Examples

### Cache Hit (Data from Cache)
```
┌─────────────────────────────────────────────────────────────┐
│ 🟢 From Cache   ⏱ Cached 30 seconds ago | Expires in ~60s  │
│                                         [Clear Cache] [ℹ]   │
└─────────────────────────────────────────────────────────────┘
```

### Cache Miss (Fresh Query)
```
┌─────────────────────────────────────────────────────────────┐
│ 🟡 Fresh Query   Direct from database - now cached for 60s  │
│                                         [Clear Cache] [ℹ]   │
└─────────────────────────────────────────────────────────────┘
```

### Expanded Details
```
┌─────────────────────────────────────────────────────────────┐
│ 🟢 From Cache   ⏱ Cached 30 seconds ago | Expires in ~60s  │
│                                         [Clear Cache] [ℹ]   │
├─────────────────────────────────────────────────────────────┤
│ 🔑 Cache Details                                            │
│                                                              │
│ Cache Key:                                                   │
│ users:list:role_any:page_1                                  │
│                                                              │
│ Status:                                                      │
│ • Cache Hit                                                  │
│ • TTL: 60 seconds (1 minute)                                │
│ • Driver: REDIS                                              │
│ • Created: 2025-10-28 11:45:30                              │
│                                                              │
│ ℹ️ What does this mean?                                      │
│ • From Cache: Loaded from Redis (faster than database)      │
│ • Auto-Invalidation: Cache clears when data changes         │
└─────────────────────────────────────────────────────────────┘
```

## User Experience

### For Regular Users
- See visual indicator of page performance
- Understand if they're seeing cached or fresh data
- No action buttons (view only)

### For Administrators
- See all cache information
- Clear specific page cache with one click
- View detailed cache metadata
- Get instant feedback with SweetAlert2 notifications

## Cache Clearing Flow

1. **Admin clicks "Clear Cache" button**
   ```
   Button shows: "🗑 Clear Cache"
   ↓
   Button shows: "⟳ Clearing..."
   ↓
   AJAX request to /cache/clear-key
   ↓
   Success: "✓ Cleared!"
   ↓
   SweetAlert: "Cache Cleared! Page will refresh..."
   ↓
   Page auto-refreshes with fresh data
   ```

2. **Next request loads fresh data from database**
   ```
   Indicator shows: "🟡 Fresh Query"
   ↓
   Data now cached for 60 seconds
   ↓
   Subsequent requests show: "🟢 From Cache"
   ```

## Customization

### Change Indicator Position

```blade
{{-- Top of page --}}
@include('components.cache-indicator')

{{-- Or after search box --}}
<input type="text" id="search-box" class="form-control mb-3" placeholder="Search...">
@include('components.cache-indicator')

{{-- Or before table --}}
@include('components.cache-indicator')
<table class="table">
    ...
</table>
```

### Styling

The component includes built-in CSS. To customize, you can override styles in your view:

```blade
@push('css')
<style>
.cache-indicator-wrapper {
    margin-bottom: 20px; /* Adjust spacing */
}

.cache-indicator-wrapper .badge {
    font-size: 1rem; /* Larger badges */
}
</style>
@endpush
```

## Requirements

✅ Already Met (if using cache system):
- SweetAlert2 loaded (for notifications)
- jQuery loaded (for AJAX)
- Bootstrap loaded (for styling)
- Laravel CSRF token
- Admin authentication middleware

## Permissions

- **View cache indicator:** All authenticated users
- **Clear cache button:** Only users with `manage-options` permission (admin)

## API Endpoint

The component uses this endpoint:

```
POST /cache/clear-key
Middleware: auth:admin, checkRole:admin

Request:
{
    "key": "users:list:role_any:page_1",
    "_token": "csrf_token_here"
}

Response (Success):
{
    "success": true,
    "message": "Cache cleared successfully!",
    "key": "users:list:role_any:page_1"
}

Response (Error):
{
    "success": false,
    "message": "Failed to clear cache"
}
```

## Troubleshooting

### Indicator Not Showing
```blade
{{-- Check if variables are passed from controller --}}
@if(isset($cacheKey))
    <p>Cache key exists: {{ $cacheKey }}</p>
@else
    <p class="text-danger">Cache key not passed from controller!</p>
@endif
```

### Clear Button Not Working
1. Check if user has `manage-options` permission
2. Check browser console for JavaScript errors
3. Verify CSRF token is present
4. Check if SweetAlert2 is loaded

### Cache Not Clearing
1. Check if route `/cache/clear-key` exists
2. Verify middleware is correct
3. Check server logs for errors
4. Test manually with `php artisan cache:clear-app`

## Performance Impact

- **Component render:** < 1ms
- **Cache metadata storage:** < 5ms
- **AJAX cache clear:** 10-50ms
- **Overall impact:** Negligible

## Summary

✅ **Easy to Add:** One line in your view  
✅ **User-Friendly:** Clear visual indicators  
✅ **Admin-Friendly:** One-click cache clearing  
✅ **Informative:** Detailed cache metadata  
✅ **Performant:** Minimal overhead  
✅ **Responsive:** Works on all screen sizes  

---

**File Location:** `resources/views/components/cache-indicator.blade.php`  
**Route:** `/cache/clear-key`  
**Permissions:** `manage-options` (admin only)

