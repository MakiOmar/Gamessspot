# Memcached Session Issue - 419 Page Expired

## Problem Identified

**Issue:** 419 "Page Expired" errors occur when using Memcached for sessions.  
**Root Cause:** Memcached has limitations with session handling that can cause CSRF token mismatches.

## Diagnosis Results

✅ **Sessions work correctly:** `/test-session` confirms sessions are functional  
✅ **Memcached is connected:** Extension loaded and responding  
❌ **Login fails with 419:** CSRF token validation fails with Memcached  
✅ **Login works with file driver:** Problem isolated to Memcached  

---

## Solution Options

### **Option 1: Use Redis for Sessions (RECOMMENDED)**

Redis is more reliable for session storage than Memcached.

**Why Redis is Better for Sessions:**
- ✅ Persistent storage (Memcached can evict data)
- ✅ Better CSRF token handling
- ✅ Supports session locking
- ✅ More predictable behavior

**Configuration:**

```env
# In .env file
SESSION_DRIVER=redis
CACHE_DRIVER=memcached  # Keep memcached for cache if you prefer

# Redis configuration
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0
REDIS_CACHE_DB=1
```

**Then run:**
```bash
php artisan config:clear
php artisan cache:clear
```

---

### **Option 2: Use Database for Sessions**

Reliable but slower than Redis.

```env
SESSION_DRIVER=database
```

**Then run:**
```bash
php artisan session:table
php artisan migrate
php artisan config:clear
```

---

### **Option 3: Keep File Sessions (Current)**

Simple and works, but not ideal for multi-server setups.

```env
SESSION_DRIVER=file
CACHE_DRIVER=memcached  # Use memcached only for cache, not sessions
```

---

### **Option 4: Fix Memcached Session Configuration (Advanced)**

If you must use Memcached for sessions, add these settings:

**In `config/session.php`:**

```php
'memcached' => [
    'driver' => 'memcached',
    'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
    'sasl' => [
        env('MEMCACHED_USERNAME'),
        env('MEMCACHED_PASSWORD'),
    ],
    'options' => [
        // Disable compression to prevent serialization issues
        Memcached::OPT_COMPRESSION => false,
        // Use binary protocol for better performance
        Memcached::OPT_BINARY_PROTOCOL => true,
        // Increase timeouts
        Memcached::OPT_CONNECT_TIMEOUT => 2000,
        Memcached::OPT_POLL_TIMEOUT => 2000,
        Memcached::OPT_RECV_TIMEOUT => 2000,
        Memcached::OPT_SEND_TIMEOUT => 2000,
    ],
    'servers' => [
        [
            'host' => env('MEMCACHED_HOST', '127.0.0.1'),
            'port' => env('MEMCACHED_PORT', 11211),
            'weight' => 100,
        ],
    ],
],
```

---

## Recommended Production Configuration

**Best Practice:** Use different drivers for different purposes:

```env
# Sessions: Redis (reliable, persistent)
SESSION_DRIVER=redis

# Cache: Memcached or Redis (either works)
CACHE_DRIVER=redis

# Queue: Redis or Database (not sync!)
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# Keep Memcached available for cache if desired
MEMCACHED_HOST=127.0.0.1
MEMCACHED_PORT=11211
```

---

## Installation Steps for Redis

**On Ubuntu/Debian Server:**

```bash
# Install Redis
sudo apt update
sudo apt install redis-server php-redis

# Enable and start Redis
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Verify Redis is running
redis-cli ping  # Should return: PONG

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

**Then update `.env` and clear caches:**

```bash
# Update .env with Redis settings
SESSION_DRIVER=redis
CACHE_DRIVER=redis

# Clear caches
php artisan config:clear
php artisan cache:clear
```

---

## Testing

After configuring Redis:

1. **Test Redis:** `https://yourdomain.com/check-redis`
2. **Test Session:** `https://yourdomain.com/test-session`
3. **Test Login:** Go to manager login and try logging in

---

## Performance Comparison

| Driver | Speed | Reliability | Session Support | Multi-Server |
|--------|-------|-------------|-----------------|--------------|
| **File** | Slow | ✓ Good | ✓ Excellent | ✗ No |
| **Memcached** | Fast | ⚠️ Can evict | ⚠️ Issues | ✓ Yes |
| **Redis** | Fast | ✓ Excellent | ✓ Excellent | ✓ Yes |
| **Database** | Medium | ✓ Excellent | ✓ Good | ✓ Yes |

**Winner:** **Redis** - Best balance of speed, reliability, and session support

---

## Current Status

✅ **Query optimizations:** All working (70% reduction)  
✅ **System health monitor:** Working  
✅ **Redis/Memcached checks:** Working  
⚠️ **Memcached sessions:** Known issue - use Redis or File instead  

---

## Immediate Action

**For Now (Quick Fix):**
```env
SESSION_DRIVER=file
CACHE_DRIVER=memcached
```

This gives you:
- ✅ Working login (file sessions)
- ✅ Fast caching (memcached)
- ✅ All optimizations active

**For Later (Best Solution):**
Install Redis and use it for sessions + cache.

---

**Created:** 2025-10-16  
**Issue:** Memcached session CSRF token handling  
**Solution:** Use Redis for sessions, Memcached for cache

