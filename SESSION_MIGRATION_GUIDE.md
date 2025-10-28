# Session Migration Guide: Memcached → Database

## 🎯 Problem

Your Memcached has:
- **280,457 keys** (mostly PHP sessions!)
- **4,034,205 evictions** (constant memory pressure)
- **449 MB memory usage** (43% of 1GB)

**Cause:** PHP sessions are stored in Memcached, creating thousands of keys.

**Solution:** Move sessions to database, keep Memcached for app cache only.

---

## ✅ **Migration Steps**

### Step 1: Verify Sessions Table Created

The migration file has been created:
```
database/migrations/2025_10_28_123634_create_sessions_table.php
```

This creates a `sessions` table with:
- `id` (session ID)
- `user_id` (nullable, for authenticated users)
- `ip_address` (for tracking)
- `user_agent` (browser info)
- `payload` (session data)
- `last_activity` (timestamp)

### Step 2: Run Migration

```bash
# Run the migration to create sessions table
php artisan migrate
```

**Expected output:**
```
Running migrations:
2025_10_28_123634_create_sessions_table ...................... DONE
```

### Step 3: Update Environment Configuration

**Edit your `.env` file:**

```env
# BEFORE (problematic)
SESSION_DRIVER=memcached  ← Remove this
CACHE_DRIVER=memcached

# AFTER (recommended)
SESSION_DRIVER=database   ← Use database for sessions
CACHE_DRIVER=memcached    ← Keep Memcached for app cache only
CACHE_PREFIX=gamessspot_  ← Isolate your app cache
```

### Step 4: Clear Configuration Cache

```bash
# Clear Laravel config cache to apply changes
php artisan config:clear

# Clear route cache (if exists)
php artisan route:clear

# Clear view cache
php artisan view:clear
```

### Step 5: Flush Memcached (Clean Slate)

```bash
# This removes all old session keys from Memcached
php artisan memcached:clear-sessions --force
```

**You'll see:**
```
Before flush:
  Items: 280,457
  Memory: 449.27 MB

⚠️  This will flush ALL data from Memcached (including sessions). Continue? (yes/no)
> yes

✅ Memcached flushed successfully!

After flush:
  Items: 0
  Memory: 0 B
  Freed: 280,457 keys

⚠️  Note: This cleared ALL data including sessions.
   Users will need to log in again.
```

### Step 6: Verify New Configuration

```bash
# Test that sessions are now in database
php artisan tinker
```

```php
>>> config('session.driver')
=> "database"  ✅

>>> config('cache.default')
=> "memcached"  ✅

>>> exit
```

### Step 7: Test Application

**1. Log in to your application**
   - Your session will be stored in database

**2. Check database:**
```sql
SELECT COUNT(*) FROM sessions;
-- Should show 1 session (yours)
```

**3. Check Memcached:**
```bash
# Visit health check page
http://your-domain.com/manager/health-check
```

**Should show:**
```
Memcached:
- Items: 10-50 keys (app cache only!)
- Memory: 5-15 MB
- Evictions: 0
- Hit Rate: 85%+
```

---

## 🔄 **Alternative: Use Redis for Sessions**

If you prefer Redis over database:

### Option A: Redis for Sessions (Faster than database)

```env
SESSION_DRIVER=redis
CACHE_DRIVER=memcached
```

**Benefits:**
- ✅ Faster than database
- ✅ Automatic session cleanup
- ✅ Better concurrency handling
- ✅ No database writes for sessions

**Requirements:**
- Redis must be installed and running
- Check with: `curl http://your-domain.com/check-redis`

### Option B: Separate Redis Databases

```env
# Use different Redis databases
SESSION_DRIVER=redis
CACHE_DRIVER=redis

# In config/database.php
'redis' => [
    'client' => 'phpredis',
    'default' => [
        'database' => 0,  // Cache
    ],
    'session' => [
        'database' => 1,  // Sessions (separate!)
    ],
],

# In config/session.php
'connection' => 'session',
```

---

## 📊 **Expected Results**

### Before Migration (Problematic)

```
┌────────────────────────────────────────┐
│ Memcached Status                       │
├────────────────────────────────────────┤
│ Items:     280,457 keys     🚨         │
│ Memory:    449 MB / 1024 MB            │
│ Evictions: 4,034,205        🚨         │
│ Hit Rate:  80.27%                      │
│                                         │
│ Contents:                               │
│ - PHP Sessions:     ~280,000 keys      │
│ - App Cache:        ~400 keys          │
│ - Other apps:       ~57 keys           │
└────────────────────────────────────────┘
```

### After Migration (Healthy)

**Memcached:**
```
┌────────────────────────────────────────┐
│ Memcached Status                       │
├────────────────────────────────────────┤
│ Items:     50-200 keys      ✅         │
│ Memory:    10-30 MB / 1024 MB          │
│ Evictions: 0-5              ✅         │
│ Hit Rate:  90%+             ✅         │
│                                         │
│ Contents:                               │
│ - App Cache ONLY:  50-200 keys         │
└────────────────────────────────────────┘
```

**Database (New):**
```
┌────────────────────────────────────────┐
│ sessions Table                         │
├────────────────────────────────────────┤
│ Rows:      50-200 active sessions      │
│ Size:      ~500 KB                     │
│ Indexed:   Yes (fast lookups)          │
│ Cleaned:   Automatically by Laravel    │
└────────────────────────────────────────┘
```

---

## 🛠️ **Complete Migration Procedure**

### **Execute These Commands in Order:**

```bash
# 1. Create sessions table
php artisan migrate

# 2. Check if table was created
php artisan tinker
>>> DB::table('sessions')->count()
=> 0  ✅ Table exists!
>>> exit

# 3. Update .env file (use text editor)
# Change: SESSION_DRIVER=database

# 4. Clear all Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 5. Flush Memcached (removes old sessions)
php artisan memcached:clear-sessions --force

# 6. Test the application
# - Log in to your application
# - Session will be created in database

# 7. Verify sessions are in database
php artisan tinker
>>> DB::table('sessions')->count()
=> 1  ✅ Your session is now in database!
>>> exit

# 8. Check Memcached health
# Visit: http://your-domain.com/manager/health-check
# Should show: Items: 50-200 (not 280,000!)
```

---

## 📝 **Post-Migration Checklist**

### ✅ Verification Steps

- [ ] **Migration ran successfully**
  ```bash
  php artisan migrate:status
  # Should show: 2025_10_28_123634_create_sessions_table [Ran]
  ```

- [ ] **Sessions table exists**
  ```bash
  php artisan tinker
  >>> Schema::hasTable('sessions')
  => true ✅
  ```

- [ ] **`.env` updated to `SESSION_DRIVER=database`**
  ```bash
  php artisan tinker
  >>> config('session.driver')
  => "database" ✅
  ```

- [ ] **Memcached flushed (old sessions removed)**
  ```bash
  # Check health page
  # Items should be < 500 now
  ```

- [ ] **Application works (can log in)**
  - Visit your application
  - Log in
  - Session stored in database ✅

- [ ] **Sessions created in database**
  ```sql
  SELECT COUNT(*) FROM sessions;
  -- Should show active sessions
  ```

- [ ] **Memcached healthy again**
  ```
  Visit: /manager/health-check
  
  Expected:
  - Items: 50-200
  - Memory: 10-30 MB
  - Evictions: Near 0
  - Hit Rate: 85%+
  ```

---

## 🎯 **Session Cleanup**

### Automatic Cleanup (Laravel Built-in)

Laravel automatically removes old sessions. Configure in `config/session.php`:

```php
'lottery' => [2, 100],  // 2% chance per request to run cleanup
```

**Or set up scheduled cleanup:**

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Clean up old sessions daily
    $schedule->command('session:clear-expired')->daily();
}
```

### Manual Cleanup

```bash
# Remove sessions older than 2 hours (7200 seconds)
DB::table('sessions')
  ->where('last_activity', '<', now()->subHours(2)->timestamp)
  ->delete();
```

---

## 🔧 **Troubleshooting**

### Issue: "Migration already ran"

```bash
# Check migration status
php artisan migrate:status

# If already ran, that's fine! Just update .env
```

### Issue: "Can't log in after migration"

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear

# Check session driver
php artisan tinker
>>> config('session.driver')
=> "database"  # Should be database

# If not, edit .env and run config:clear again
```

### Issue: "Memcached still has many keys"

```bash
# Flush Memcached completely
php artisan memcached:clear-sessions --force

# Or manually
echo "flush_all" | nc 127.0.0.1 11211
```

---

## 📊 **Performance Comparison**

### Memcached Sessions (Current - Problematic)

```
Pros:
- Very fast (in-memory)

Cons:
- 280,000+ keys polluting cache
- 4M+ evictions (memory pressure)
- Conflicts with app cache
- Lost on Memcached restart
```

### Database Sessions (Recommended)

```
Pros:
- Clean Memcached (app cache only)
- Persistent (survives restarts)
- Easy to manage (SQL queries)
- Automatic cleanup

Cons:
- Slightly slower (but still fast with indexes)
- Uses database storage
```

**Speed comparison:**
- Memcached session: 1-2ms
- Database session: 3-5ms
- Difference: Negligible for session operations

---

## ✅ **Summary**

**What to do NOW:**

1. ✅ **Migration created** - `2025_10_28_123634_create_sessions_table.php`

2. **Run migration:**
   ```bash
   php artisan migrate
   ```

3. **Update `.env`:**
   ```env
   SESSION_DRIVER=database
   ```

4. **Clear configs:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

5. **Flush Memcached:**
   ```bash
   php artisan memcached:clear-sessions --force
   ```

6. **Verify on health page:**
   - Visit `/manager/health-check`
   - Should show: Items: 50-200 (not 280,000!)

**Your Memcached will be healthy again!** 🚀

---

**Time to complete:** 5 minutes  
**Downside:** Users will need to log in again (one-time)  
**Benefit:** Clean, fast, stable caching system ✅

