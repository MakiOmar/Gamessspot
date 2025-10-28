# Memcached Memory Management Guide

## Overview

Memcached is an **in-memory** cache system. This guide explains how memory is managed, cleared, and monitored to prevent memory issues.

---

## 🔄 **Automatic Memory Clearing**

### 1. **TTL-Based Expiration (Time To Live)**

**Every cache entry has an expiration time:**

```php
// Our code
Cache::put('users:list:role_any:page_1', $users, 60);  // 60 seconds
```

**In Memcached:**
```
Key: users:list:role_any:page_1
Value: [serialized data]
TTL: 60 seconds
Created: 12:00:00
Expires: 12:01:00  ← Automatically deleted!
```

**What Happens:**
```
12:00:00 - Key created in Memcached (uses memory)
12:00:30 - Key still in cache (30s remaining)
12:01:00 - TTL expires, Memcached AUTOMATICALLY deletes key
12:01:01 - Memory freed, available for new data ✅
```

**Our Application TTLs:**
```
Statistics (rare changes):  600s (10 minutes)
Listings (frequent):        60s (1 minute)
Temp data:                  300s (5 minutes)
```

### 2. **LRU Eviction (Least Recently Used)**

**When Memcached runs out of memory:**

```
┌──────────────────────────────────────────────────────┐
│ Memcached Memory: 64 MB (Full)                       │
├──────────────────────────────────────────────────────┤
│ Cache Key                    Last Accessed           │
│ ────────────────────────────────────────────         │
│ users:total_user_count       5 minutes ago          │
│ games:list:ps4:store_1:p1    2 minutes ago          │
│ accounts:list:page_1         30 seconds ago  ← Most recent
│ old_unused_key               2 hours ago     ← LRU (evict first!)
└──────────────────────────────────────────────────────┘

New data arrives (needs 5 MB)
↓
Memory full! (64 MB used)
↓
Memcached finds LEAST RECENTLY USED keys
↓
Evicts: old_unused_key (frees 5 MB)
↓
Stores new data
↓
Memory: 64 MB used (different data now)
```

**LRU Algorithm:**
1. Identifies keys that haven't been accessed recently
2. Deletes oldest/least-used keys first
3. Frees memory for new data
4. Happens automatically, no intervention needed

---

## 🛠️ **Manual Memory Clearing**

We've implemented **multiple ways** to clear Memcached memory:

### 1. **Clear All Application Cache**

```bash
# Method 1: Our custom command
php artisan cache:clear-app --all

# Method 2: Laravel built-in
php artisan cache:clear

# Method 3: Via route (admin only)
curl -X POST http://your-domain.com/cache/clear-key \
  -d "key=all"
```

### 2. **Clear Specific Categories**

```bash
# Clear only user caches
php artisan cache:clear-app --users

# Clear only game caches
php artisan cache:clear-app --games

# Clear only dashboard caches
php artisan cache:clear-app --dashboard

# Clear multiple categories
php artisan cache:clear-app --users --games --accounts
```

### 3. **Clear Specific Page Cache (Via UI)**

**On any listing page:**
- Click **"Clear Cache"** button
- Clears only that specific page's cache
- Example: Clears `users:list:role_any:page_1`

### 4. **Flush Entire Memcached Server**

```bash
# WARNING: Clears ALL data in Memcached (all apps!)
echo "flush_all" | nc 127.0.0.1 11211
```

**Or via PHP:**
```php
$memcached = new Memcached();
$memcached->addServer('127.0.0.1', 11211);
$memcached->flush(); // Clears EVERYTHING
```

---

## 📊 **Monitor Memcached Memory Usage**

### Method 1: Via Our Health Check Page

Visit: `/manager/health-check`

Shows:
- ✅ Memcached connection status
- ✅ Server availability
- ✅ Connection test results

### Method 2: Via Stats Endpoint

```bash
# Visit in browser or curl
curl http://your-domain.com/check-cache
```

### Method 3: Direct Memcached Stats

```bash
# Connect to Memcached
telnet 127.0.0.1 11211

# Get memory statistics
stats
```

**Output:**
```
STAT bytes 2456789              ← Current memory used (2.4 MB)
STAT limit_maxbytes 67108864    ← Max memory (64 MB)
STAT curr_items 15              ← Number of keys stored
STAT total_items 125            ← Total items ever stored
STAT evictions 0                ← Items evicted due to memory
STAT cmd_get 1250               ← Total get commands
STAT cmd_set 125                ← Total set commands
STAT get_hits 1125              ← Cache hits (90% hit rate!)
STAT get_misses 125             ← Cache misses
```

### Method 4: Via Our Cache Stats Command

```bash
php artisan cache:stats
```

---

## ⚠️ **Preventing Memory Issues**

### 1. **Configure Adequate Memory**

**Check current Memcached memory allocation:**
```bash
# Windows (check Memcached service)
sc qc memcached

# Linux
ps aux | grep memcached
```

**Increase Memcached memory if needed:**

**Windows:**
```bash
# Stop Memcached
net stop memcached

# Reinstall with more memory
memcached.exe -d uninstall
memcached.exe -d install -m 128  # 128 MB

# Start Memcached
net start memcached
```

**Linux:**
```bash
# Edit Memcached config
sudo nano /etc/memcached.conf

# Change memory limit
-m 128  # 128 MB (default is usually 64 MB)

# Restart Memcached
sudo service memcached restart
```

### 2. **Use Appropriate TTL**

```php
// Short TTL for frequently changing data
CacheManager::TTL_SHORT = 60;      // 1 minute - auto-clears fast

// Long TTL for stable data
CacheManager::TTL_LONG = 600;      // 10 minutes

// Very long for configuration
CacheManager::TTL_VERY_LONG = 3600; // 1 hour
```

**Benefits:**
- ✅ Memory freed automatically as TTL expires
- ✅ No manual intervention needed
- ✅ Fresh data for users

### 3. **Limit Cache Size**

```php
// Don't cache huge datasets
if ($resultCount > 1000) {
    // Don't cache, too large
    return $query->paginate(100);
} else {
    // Cache it
    return CacheManager::remember($key, $ttl, $callback);
}
```

### 4. **Use Pattern-Based Invalidation**

```php
// Instead of letting old caches pile up
// Clear them proactively when data changes

// GameObserver automatically clears ALL game caches
CacheManager::forgetByPattern('games:*');
// Frees memory immediately!
```

---

## 🔧 **Memory Clearing Strategies**

### Strategy 1: Automatic (TTL) - **Recommended**

```
Data cached → Expires after TTL → Memory freed automatically
```

**Pros:**
- ✅ No manual intervention
- ✅ Predictable memory usage
- ✅ Fresh data

**Example:**
```php
// Cache for 1 minute
CacheManager::remember('key', 60, $callback);

After 60 seconds:
→ Memcached automatically deletes
→ Memory freed
→ No action needed!
```

### Strategy 2: Observer-Based Invalidation - **Implemented**

```
Data changes → Observer fires → Cache cleared → Memory freed
```

**Pros:**
- ✅ Immediate invalidation
- ✅ Always fresh data
- ✅ Frees memory when no longer valid

**Example:**
```php
// User created
User::create($data);

Immediately:
→ UserObserver fires
→ CacheManager::invalidateUsers()
→ All user caches deleted from Memcached
→ Memory freed! ✅
```

### Strategy 3: Scheduled Clearing - **You Can Add**

```bash
# Add to cron/scheduler
# Clear all caches daily at 3 AM
0 3 * * * php artisan cache:clear-app --all
```

**In Laravel (`app/Console/Kernel.php`):**
```php
protected function schedule(Schedule $schedule)
{
    // Clear cache daily at 3 AM
    $schedule->command('cache:clear-app --all')
             ->dailyAt('03:00');
    
    // Or clear specific caches every hour
    $schedule->command('cache:clear-app --games')
             ->hourly();
}
```

### Strategy 4: Manual Clearing - **Already Implemented**

**Via Command:**
```bash
php artisan cache:clear-app --all
```

**Via UI:**
- Click "Clear Cache" button on any page

**Via Endpoint:**
```bash
curl -X POST http://your-domain.com/cache/clear-key \
  -d "key=users:list:role_any:page_1"
```

---

## 🎯 **Memory Usage Monitoring**

### Add Memory Usage to Health Check

Let me add Memcached memory stats to your health check:

```php
// In ManagerController::healthCheck()
if ($healthData['memcached']['status'] === 'working') {
    try {
        $memcached = new \Memcached();
        $memcached->addServer(
            config('cache.stores.memcached.servers.0.host'),
            config('cache.stores.memcached.servers.0.port')
        );
        
        $stats = $memcached->getStats();
        $serverKey = config('cache.stores.memcached.servers.0.host') 
                   . ':' 
                   . config('cache.stores.memcached.servers.0.port');
        
        if (isset($stats[$serverKey])) {
            $serverStats = $stats[$serverKey];
            
            $healthData['memcached']['memory'] = [
                'used' => $this->formatBytes($serverStats['bytes']),
                'max' => $this->formatBytes($serverStats['limit_maxbytes']),
                'usage_percent' => round(($serverStats['bytes'] / $serverStats['limit_maxbytes']) * 100, 2),
                'items' => $serverStats['curr_items'],
                'evictions' => $serverStats['evictions'],
                'hit_rate' => round(($serverStats['get_hits'] / ($serverStats['get_hits'] + $serverStats['get_misses'])) * 100, 2)
            ];
        }
    } catch (\Exception $e) {
        // Stats retrieval failed
    }
}
```

This will show:
- Memory used: 2.4 MB / 64 MB (3.75%)
- Cache items: 15 keys
- Evictions: 0 (good!)
- Hit rate: 90% (excellent!)

---

## ⚠️ **Warning Signs**

### When to Clear Memory Manually

**1. High Memory Usage**
```
Memory: 60 MB / 64 MB (94%) ⚠️
→ Action: Clear old caches
→ Command: php artisan cache:clear-app --all
```

**2. High Eviction Count**
```
Evictions: 1500 ⚠️
→ Meaning: Memcached had to delete keys to make room
→ Action: Increase Memcached memory OR reduce TTL
```

**3. Low Hit Rate**
```
Hit Rate: 35% ⚠️
→ Meaning: Most requests miss cache (cache not effective)
→ Action: Increase TTL OR investigate cache invalidation
```

**4. After Deployment**
```
Just deployed new code
→ Old cached objects might cause errors
→ Action: php artisan cache:clear-app --all
```

---

## 🚨 **Emergency Memory Clearing**

### If Memcached Runs Out of Memory

**Scenario:** All 64 MB used, application slow

**Immediate Actions:**

```bash
# 1. Clear all application cache
php artisan cache:clear-app --all

# 2. If that doesn't work, flush Memcached entirely
echo "flush_all" | nc 127.0.0.1 11211

# 3. Restart Memcached service (last resort)
# Windows:
net stop memcached
net start memcached

# Linux:
sudo service memcached restart
```

---

## 📊 **Memory Management Tools**

### Tool 1: Cache Clear Command (Already Implemented)

```bash
# Clear everything
php artisan cache:clear-app --all

# Clear specific categories
php artisan cache:clear-app --users --games --accounts
```

### Tool 2: Laravel's Built-in

```bash
# Clear all Laravel cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear view cache
php artisan view:clear

# Clear everything
php artisan optimize:clear
```

### Tool 3: Direct Memcached Commands

```bash
# View memory stats
echo "stats" | nc 127.0.0.1 11211 | grep -E "bytes|maxbytes|evictions|items"

# Flush all data
echo "flush_all" | nc 127.0.0.1 11211

# Get specific key size
echo "stats items" | nc 127.0.0.1 11211
```

---

## 🎯 **Best Practices for Memory Management**

### 1. **Set Appropriate TTLs**

```php
// Our current TTLs (in CacheManager.php)
const TTL_SHORT = 60;       // 1 minute  ← Listings (clears fast)
const TTL_MEDIUM = 300;     // 5 minutes ← Semi-static
const TTL_LONG = 600;       // 10 minutes ← Statistics
const TTL_VERY_LONG = 3600; // 1 hour     ← Configuration
```

**Memory Impact:**
```
Short TTL (60s):
- Memory freed every minute
- Low memory usage
- Always fresh data ✅

Long TTL (3600s):
- Memory held for 1 hour
- Higher memory usage
- Older data ⚠️
```

### 2. **Don't Cache Huge Datasets**

```php
// ❌ BAD - Cache entire table
Cache::put('all_users', User::all(), 600);  // Could be 100,000 users!

// ✅ GOOD - Cache paginated chunks
CacheManager::getUserListing('any', 1, fn() => User::paginate(15));
```

### 3. **Use Compression for Large Objects**

```php
// For very large cache values
$data = gzcompress(serialize($largeData));
Cache::put('key', $data, 600);

// Retrieve
$retrieved = unserialize(gzuncompress(Cache::get('key')));
```

### 4. **Monitor Memory Usage**

```bash
# Check Memcached stats regularly
watch -n 5 'echo "stats" | nc 127.0.0.1 11211 | grep bytes'
```

---

## 🔍 **Memory Monitoring Dashboard**

Let me add memory monitoring to the health check page:

### Enhanced Memcached Stats

I'll add this to `ManagerController::healthCheck()`:

```php
if ($healthData['memcached']['status'] === 'working') {
    $stats = $memcached->getStats();
    $serverKey = $healthData['memcached']['host'] . ':' . $healthData['memcached']['port'];
    
    if (isset($stats[$serverKey])) {
        $s = $stats[$serverKey];
        
        $healthData['memcached']['memory'] = [
            'used_bytes' => $s['bytes'],
            'used_formatted' => $this->formatBytes($s['bytes']),
            'max_bytes' => $s['limit_maxbytes'],
            'max_formatted' => $this->formatBytes($s['limit_maxbytes']),
            'usage_percent' => round(($s['bytes'] / $s['limit_maxbytes']) * 100, 2),
            'free_bytes' => $s['limit_maxbytes'] - $s['bytes'],
            'free_formatted' => $this->formatBytes($s['limit_maxbytes'] - $s['bytes']),
        ];
        
        $healthData['memcached']['performance'] = [
            'curr_items' => $s['curr_items'],
            'total_items' => $s['total_items'],
            'evictions' => $s['evictions'],
            'get_hits' => $s['get_hits'],
            'get_misses' => $s['get_misses'],
            'hit_rate' => $s['get_hits'] + $s['get_misses'] > 0 
                ? round(($s['get_hits'] / ($s['get_hits'] + $s['get_misses'])) * 100, 2) 
                : 0,
        ];
    }
}
```

This will show:
```
Memcached Memory:
- Used: 2.4 MB / 64 MB (3.75%)
- Free: 61.6 MB
- Items: 15 keys
- Evictions: 0 (no memory pressure!)
- Hit Rate: 90% (excellent!)
```

---

## 🎮 **Automatic Memory Management**

### Our Observers Clear Memory Automatically

```
User created/updated/deleted
↓
UserObserver fires
↓
CacheManager::invalidateUsers()
↓
Deletes all user cache keys from Memcached
↓
Memory freed! ✅
```

**Example:**
```
Before: 15 keys, 2.5 MB used
Create new user
→ UserObserver clears 3 user cache keys
After: 12 keys, 2.3 MB used (200 KB freed)
```

---

## 📈 **Memory Usage Estimation**

### Typical Application

```
┌──────────────────────────────────────────────────┐
│ Cache Type          | Keys | Memory               │
├──────────────────────────────────────────────────┤
│ Dashboard stats     | 7    | ~1 KB                │
│ User listings       | 5    | ~200 KB              │
│ Game listings       | 10   | ~1 MB                │
│ Account listings    | 5    | ~150 KB              │
│ Store-specific (×3) | 15   | ~1.5 MB              │
├──────────────────────────────────────────────────┤
│ Total               | 42   | ~2.85 MB / 64 MB     │
│                     |      | (4.5% usage) ✅      │
└──────────────────────────────────────────────────┘
```

### High-Traffic Application

```
┌──────────────────────────────────────────────────┐
│ Cache Type          | Keys | Memory               │
├──────────────────────────────────────────────────┤
│ Dashboard stats     | 7    | ~1 KB                │
│ User listings       | 20   | ~800 KB              │
│ Game listings       | 50   | ~5 MB                │
│ Account listings    | 15   | ~450 KB              │
│ Store-specific (×10)| 100  | ~10 MB               │
├──────────────────────────────────────────────────┤
│ Total               | 192  | ~16.25 MB / 64 MB    │
│                     |      | (25% usage) ✅       │
└──────────────────────────────────────────────────┘
```

**Conclusion:** Even with heavy usage, you'll use < 30% of Memcached memory!

---

## 🔄 **Memory Lifecycle**

### Example: User Listing Cache

```
Time: 12:00:00
Event: Manager visits /manager/users
Action: Cache miss, query database
Result: Store in Memcached
Memory: +50 KB
Key: users:list:role_any:page_1
Status: ┌───────────────────────────┐
        │ Memory: 2.45 MB / 64 MB  │
        │ Items: 16 keys           │
        └───────────────────────────┘

Time: 12:00:30
Event: Another manager visits /manager/users
Action: Cache hit! Return from Memcached
Result: No new memory used
Memory: Same (2.45 MB)
Status: ┌───────────────────────────┐
        │ Memory: 2.45 MB / 64 MB  │
        │ Items: 16 keys           │
        │ Hits: +1 ✅              │
        └───────────────────────────┘

Time: 12:01:00
Event: TTL expires (60 seconds)
Action: Memcached auto-deletes key
Result: Memory freed
Memory: -50 KB
Status: ┌───────────────────────────┐
        │ Memory: 2.40 MB / 64 MB  │
        │ Items: 15 keys           │
        │ Freed: 50 KB ✅          │
        └───────────────────────────┘

Time: 12:01:05
Event: Manager creates new user
Action: UserObserver fires
Result: All user caches deleted
Memory: -200 KB (3 user cache keys)
Status: ┌───────────────────────────┐
        │ Memory: 2.20 MB / 64 MB  │
        │ Items: 12 keys           │
        │ Freed: 200 KB ✅         │
        └───────────────────────────┘
```

---

## 🚨 **Troubleshooting Memory Issues**

### Issue 1: "Memcached memory full"

**Symptoms:**
```
Memory: 64 MB / 64 MB (100%)
Evictions: 5000+ ⚠️
Performance: Degraded
```

**Solutions:**

**A. Increase Memcached memory:**
```bash
# Stop Memcached
net stop memcached

# Increase to 128 MB
memcached.exe -d install -m 128

# Start Memcached
net start memcached
```

**B. Reduce cache TTL:**
```php
// In CacheManager.php
const TTL_SHORT = 30;  // 30 seconds instead of 60
const TTL_LONG = 300;  // 5 minutes instead of 10
```

**C. Clear old caches:**
```bash
php artisan cache:clear-app --all
```

### Issue 2: "High eviction rate"

**Symptoms:**
```
Evictions: 500 per hour ⚠️
Hit rate: 60% (should be 80%+)
```

**Causes:**
- Too many cache keys
- TTL too long
- Memory too small
- Large cached objects

**Solutions:**
```bash
# 1. Check what's using memory
php artisan cache:stats

# 2. Clear unnecessary caches
php artisan cache:clear-app --games

# 3. Increase memory (recommended)
# Edit Memcached config to allocate more RAM
```

### Issue 3: "Cache not clearing"

**Symptoms:**
```
Created user but still seeing old count
```

**Debug:**
```bash
# 1. Check if observers are registered
tail -f storage/logs/laravel.log | grep "Observer"

# 2. Manually clear cache
php artisan cache:clear-app --users

# 3. Check Memcached connection
curl http://your-domain.com/test-memcached
```

---

## 📋 **Maintenance Checklist**

### Daily
- [ ] Check health check page for cache status
- [ ] Monitor hit rate (should be > 80%)

### Weekly  
- [ ] Review `php artisan cache:stats`
- [ ] Check eviction count (should be low)
- [ ] Verify memory usage (should be < 50%)

### Monthly
- [ ] Review cache strategy
- [ ] Adjust TTLs if needed
- [ ] Clear all caches: `php artisan cache:clear-app --all`

### After Deployment
- [ ] `php artisan cache:clear-app --all`
- [ ] `php artisan config:clear`
- [ ] `php artisan view:clear`
- [ ] Monitor for errors in logs

---

## 🎯 **Summary**

**Q: "If Memcached needs memory, how to make sure this memory is cleared if needed?"**

**A: Multiple layers of automatic and manual clearing:**

### Automatic (No Action Needed) ✅

1. **TTL Expiration** - Keys auto-delete after 60-600 seconds
2. **LRU Eviction** - Memcached auto-evicts least-used keys when full
3. **Observer Invalidation** - Cache auto-clears when data changes

### Manual (When Needed) ✅

1. **UI Button** - "Clear Cache" on every cached page
2. **Artisan Command** - `php artisan cache:clear-app --all`
3. **Laravel Command** - `php artisan cache:clear`
4. **Direct Flush** - `echo "flush_all" | nc 127.0.0.1 11211`

### Monitoring ✅

1. **Health Check Page** - `/manager/health-check` (will show memory stats)
2. **Cache Stats Command** - `php artisan cache:stats`
3. **Stats Endpoint** - `/cache-stats`
4. **Memcached Stats** - `telnet 127.0.0.1 11211` → `stats`

**Your memory is managed automatically and can be cleared anytime!** 🎯

---

**Recommendation:** With 64 MB Memcached, your application will use < 10 MB typically, so memory clearing is rarely needed. TTL and observers handle it automatically!

