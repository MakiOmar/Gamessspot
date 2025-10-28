# Production Memcached Fix - Step by Step

## 🎯 Issue on Production

Your **production server** (`staging.gamesspoteg.com`) has:
- **197,479 keys** in Memcached (should be 50-200)
- **4M+ evictions** (memory pressure)
- **316 MB usage** (should be 10-30 MB)

---

## 📋 **Fix Procedure for Production Server**

### **Step 1: SSH to Production**

```bash
ssh your-username@staging.gamesspoteg.com
cd /home/u605441708/domains/gamesspoteg.com/public_html/staging.gamesspoteg.com
```

---

### **Step 2: Check Current Configuration**

```bash
# Check what's using Memcached
php artisan tinker
```

In tinker, run:
```php
echo 'Session Driver: ' . config('session.driver') . "\n";
echo 'Cache Driver: ' . config('cache.default') . "\n";
echo 'Cache Prefix: ' . config('cache.prefix') . "\n";
exit
```

**If Session Driver shows "memcached"** → That's your problem!

---

### **Step 3: Inspect What's in Memcached**

```bash
php artisan memcached:inspect --sample=500
```

This will show you what prefixes dominate. Look for:
- `PHPSESSID` → PHP sessions
- `laravel_cache_` → Laravel cache (your app)
- `wordpress_` → WordPress
- Other app names

**Expected output:**
```
Prefix        Count    Percentage    Examples
──────────────────────────────────────────────────
PHPSESSID     180,000  90%          PHPSESSID:abc123...
laravel       15,000   8%           laravel_cache_users:...
other         2,479    1%           ...
```

---

### **Step 4: Run Migrations on Production**

```bash
# Run migrations (creates sessions table)
php artisan migrate --force
```

**Expected output:**
```
Running migrations:
2025_10_28_123634_create_sessions_table ............ DONE
```

**Verify table was created:**
```bash
php artisan tinker
```
```php
DB::table('sessions')->count();
// Should return: 0
exit
```

---

### **Step 5: Update Production .env File**

```bash
# Edit production .env
nano .env

# Or use vi
vi .env
```

**Find and change these lines:**

```env
# FIND THIS:
SESSION_DRIVER=memcached

# CHANGE TO:
SESSION_DRIVER=database

# ALSO ADD/UPDATE:
CACHE_PREFIX=gamessspot_
```

**Save and exit:**
- nano: `Ctrl+X`, then `Y`, then `Enter`
- vi: Press `Esc`, type `:wq`, press `Enter`

---

### **Step 6: Clear ALL Caches on Production**

```bash
# Clear configuration cache (IMPORTANT!)
php artisan config:clear

# Clear application cache
php artisan cache:clear

# Clear compiled views
php artisan view:clear

# Clear route cache
php artisan route:clear

# Reoptimize
php artisan optimize
```

---

### **Step 7: Verify Config Changed**

```bash
php artisan tinker
```

```php
// Check again
config('session.driver');
// MUST show: "database" ✅

config('cache.default');
// Should show: "memcached" ✅

exit
```

**If still shows "memcached" for sessions:**
- The `.env` change didn't save
- Or you edited wrong `.env` file
- Or config cache didn't clear

**Try again:**
```bash
# Make SURE .env is updated
cat .env | grep SESSION_DRIVER
# Should output: SESSION_DRIVER=database

# Clear config again
php artisan config:clear

# Verify
php artisan tinker --execute="echo config('session.driver');"
# MUST output: database
```

---

### **Step 8: Flush Memcached**

**Only after confirming SESSION_DRIVER=database:**

```bash
# Method 1: Use our command
php artisan memcached:clear-sessions --force

# Method 2: Direct flush (if command doesn't work)
echo "flush_all" | nc ::1 11211
```

**Note:** Your Memcached uses IPv6 (`::1`), so use `::1` not `127.0.0.1`.

---

### **Step 9: Wait and Verify**

```bash
# Wait 2-3 minutes for old sessions to expire

# Then visit health check page
# https://staging.gamesspoteg.com/manager/health-check
```

**Expected result:**
```
Memcached:
- Items: 50-200         ← Down from 197K!
- Memory: 10-30 MB      ← Down from 316 MB!
- Evictions: 0-10       ← Down from 4M!
- Hit Rate: 90%+        ✅
```

---

## 🔍 **Troubleshooting**

### Problem: "Items still high after flush"

**Possible causes:**
1. **Config not applied** - `.env` change didn't take effect
2. **Other apps** - Multiple sites using same Memcached
3. **Sessions still going to Memcached** - Config cache issue

**Debug:**
```bash
# Check what driver is ACTUALLY being used
php artisan tinker
>>> config('session.driver')
>>> exit

# If still "memcached", the config cache is stuck
# Force clear everything:
rm -rf bootstrap/cache/*.php
php artisan config:clear
php artisan cache:clear

# Then check again
php artisan tinker
>>> config('session.driver')
>>> exit
```

### Problem: "Can't connect to Memcached after changes"

You changed the port but didn't start Memcached on that port.

**Fix:**
```bash
# Revert config/cache.php to use port 11211
# Or start Memcached on port 11212
```

### Problem: "Other websites creating keys"

If `memcached:inspect` shows other app names:

**Solution A: Use cache prefix**
```env
CACHE_PREFIX=gamessspot_unique_
```

**Solution B: Use dedicated Memcached instance**
- Run Memcached on different port (11212)
- Only your app connects to it

---

## 📊 **Expected Timeline**

```
Now:       197,479 keys, 316 MB
Step 1-2:  Diagnose issue (2 min)
Step 3-4:  Run migration (1 min)
Step 5-6:  Update .env, clear cache (2 min)
Step 7:    Verify config (1 min)
Step 8:    Flush Memcached (1 min)
Step 9:    Wait for stabilization (3 min)
───────────────────────────────────────
Total:     10 minutes
Result:    50-200 keys, 10-30 MB ✅
```

---

## 🎯 **Critical Commands Summary**

**Run these IN ORDER on your production server:**

```bash
# 1. Diagnose
php artisan memcached:inspect --sample=200

# 2. Migrate
php artisan migrate --force

# 3. Edit .env (change SESSION_DRIVER to database)
nano .env

# 4. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan optimize

# 5. Verify
php artisan tinker --execute="echo config('session.driver');"
# MUST output: database

# 6. Flush Memcached
php artisan memcached:clear-sessions --force

# 7. Check health page after 5 minutes
# Visit: https://staging.gamesspoteg.com/manager/health-check
```

---

## ⚠️ **Important Notes**

1. **All users will be logged out** when you flush Memcached
2. **New sessions go to database** after config change
3. **App cache stays in Memcached** (fast performance)
4. **Do this during low-traffic time** (if possible)

---

## ✅ **Success Criteria**

After following all steps, you should see:

**Memcached Health Check:**
```
✅ WORKING
Memory: 10-30 MB / 1024 MB (1-3%)
Items: 50-200 keys
Evictions: 0-5
Hit Rate: 90%+
```

**Database Sessions Table:**
```sql
SELECT COUNT(*) FROM sessions;
-- Should show: Active user count (e.g., 50-100)
```

**Application:**
- ✅ Users can log in
- ✅ Sessions persist
- ✅ Cache works fast
- ✅ No memory issues

---

## 🚨 **If You Still See High Keys After All Steps**

Then it's **other applications** sharing your Memcached. You need to:

**Option 1: Add unique cache prefix**
```env
CACHE_PREFIX=gamessspot_production_v2_
```

**Option 2: Use different Memcached port** (requires setup)

**Option 3: Contact hosting provider** to get dedicated Memcached instance

---

**First, revert the port back to 11211 so the inspect command works!**

Then run the steps above to properly diagnose and fix.

