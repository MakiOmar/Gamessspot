# Memcached Key/Value Mapping for Our Application

## Overview

Memcached is a simple **key/value store** that stores everything as strings. Laravel handles the complexity of converting our application data to/from Memcached format.

---

## 🔑 **Basic Memcached Concept**

```
┌──────────────────────────────────────────────────────────┐
│ Memcached Storage (In-Memory)                            │
├──────────────────────────────────────────────────────────┤
│ Key (String)              →  Value (Serialized Data)     │
│ ─────────────────────────────────────────────────────    │
│ "user_count"              →  "7"                         │
│ "app_name"                →  "GameSpot"                  │
│ "settings"                →  "{...serialized json...}"   │
└──────────────────────────────────────────────────────────┘
```

All data is stored as **strings** in memory. Complex data (arrays, objects) is **serialized**.

---

## 📊 **Our Application → Memcached Mapping**

### Example 1: Simple Count (Dashboard Stat)

**Our Code:**
```php
$totalUsers = CacheManager::getTotalUserCount();
// Returns: 7
```

**What Laravel Does:**
```php
// 1. Generate cache key
$key = 'users:total_user_count';

// 2. Check Memcached
$memcached->get('users:total_user_count');

// 3. If not found, execute query
$value = User::count(); // Returns: 7

// 4. Serialize and store in Memcached
$memcached->set('users:total_user_count', serialize(7), 600);
```

**In Memcached:**
```
Key: "users:total_user_count"
Value: "i:7;" (serialized PHP integer)
TTL: 600 seconds (10 minutes)
```

### Example 2: Complex Data (User Listing)

**Our Code:**
```php
$users = CacheManager::getUserListing('any', 1, function() {
    return User::with('storeProfile')->withCount('orders')->paginate(15);
});
// Returns: LengthAwarePaginator with user collection
```

**What Laravel Does:**
```php
// 1. Generate cache key
$key = 'users:list:role_any:page_1';

// 2. Check Memcached
$cachedValue = $memcached->get('users:list:role_any:page_1');

// 3. If not found, execute query
$users = User::with('storeProfile')->withCount('orders')->paginate(15);
// Returns complex object with:
// - Collection of User models
// - Pagination metadata
// - Related storeProfile data
// - Order counts

// 4. Serialize entire paginator object
$serialized = serialize($users);

// 5. Store in Memcached
$memcached->set('users:list:role_any:page_1', $serialized, 60);
```

**In Memcached:**
```
Key: "users:list:role_any:page_1"

Value: "O:29:"Illuminate\Pagination\LengthAwarePaginator":8:{
    s:8:"items";
    a:15:{
        i:0;O:15:"App\Models\User":30:{
            s:2:"id";i:1;
            s:4:"name";s:10:"John Doe";
            s:5:"email";s:15:"john@email.com";
            s:13:"storeProfile";O:22:"App\Models\StoreProfile":...
            ...
        }
        i:1;O:15:"App\Models\User":30:{...}
        ...
    }
    s:5:"total";i:7;
    s:8:"perPage";i:15;
    s:12:"currentPage";i:1;
    ...
}" (highly simplified - actual serialized data is much longer)

TTL: 60 seconds (1 minute)
```

### Example 3: Store-Specific Data (PS4 Games)

**Our Code:**
```php
// Manager from Store 1
$games = CacheManager::getGameListing('ps4', 1, function() {
    return $this->fetchGamesByPlatform(4);
}, 1); // storeProfileId = 1
```

**In Memcached (Store 1):**
```
Key: "games:list:platform_ps4:store_1:page_1"

Value: [Serialized collection with Store 1 prices]
{
    "Game 1": {
        "title": "God of War",
        "ps4_primary_price": 50.00,    ← Store 1 price
        "ps4_secondary_price": 45.00,
        "ps4_offline_stock": 5
    },
    "Game 2": {...}
}

TTL: 60 seconds
```

**In Memcached (Store 2) - SEPARATE KEY:**
```
Key: "games:list:platform_ps4:store_2:page_1"  ← Different key!

Value: [Serialized collection with Store 2 prices]
{
    "Game 1": {
        "title": "God of War",
        "ps4_primary_price": 55.00,    ← Store 2 price (different!)
        "ps4_secondary_price": 42.00,
        "ps4_offline_stock": 5
    },
    "Game 2": {...}
}

TTL: 60 seconds
```

---

## 🔄 **Complete Flow: Application → Memcached**

### Storing Data

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Your Code                                                │
├─────────────────────────────────────────────────────────────┤
│ $users = CacheManager::getUserListing('any', 1, $callback) │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. CacheManager                                             │
├─────────────────────────────────────────────────────────────┤
│ - Generates key: "users:list:role_any:page_1"              │
│ - Registers key in registry                                 │
│ - Calls Cache::remember()                                   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Laravel Cache Facade                                     │
├─────────────────────────────────────────────────────────────┤
│ - Adds app prefix (if configured)                          │
│ - Serializes the PHP object/array                          │
│ - Calls Memcached driver                                    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. PHP Memcached Extension                                  │
├─────────────────────────────────────────────────────────────┤
│ $memcached->set(                                            │
│     'users:list:role_any:page_1',  // Key (string)         │
│     serialize($paginatorObject),    // Value (string)       │
│     60                              // TTL (seconds)         │
│ );                                                          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. Memcached Server (RAM)                                   │
├─────────────────────────────────────────────────────────────┤
│ [Key: "users:list:role_any:page_1"]                        │
│ [Value: Binary serialized data ~50KB]                      │
│ [Expires: 60 seconds from now]                              │
│ [Stored in RAM at: 127.0.0.1:11211]                        │
└─────────────────────────────────────────────────────────────┘
```

### Retrieving Data

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Your Code (Next Request)                                 │
├─────────────────────────────────────────────────────────────┤
│ $users = CacheManager::getUserListing('any', 1, $callback) │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. CacheManager                                             │
├─────────────────────────────────────────────────────────────┤
│ - Generates same key: "users:list:role_any:page_1"         │
│ - Calls Cache::remember()                                   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Laravel Cache Facade                                     │
├─────────────────────────────────────────────────────────────┤
│ - Checks if key exists in Memcached                        │
│ - Key found! Cache HIT ✅                                   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. PHP Memcached Extension                                  │
├─────────────────────────────────────────────────────────────┤
│ $serializedData = $memcached->get(                         │
│     'users:list:role_any:page_1'                           │
│ );                                                          │
│ // Returns: Serialized string                              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. Laravel Unserialize                                      │
├─────────────────────────────────────────────────────────────┤
│ $users = unserialize($serializedData);                     │
│ // Converts back to LengthAwarePaginator object            │
│ // With all User models, relationships, counts             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. Return to Your Code                                      │
├─────────────────────────────────────────────────────────────┤
│ $users // LengthAwarePaginator ready to use!               │
└─────────────────────────────────────────────────────────────┘
```

---

## 💾 **Actual Memcached Storage**

Let me show you what's **actually in Memcached**:

### View All Keys in Memcached

```bash
# Connect to Memcached and list keys
telnet 127.0.0.1 11211
stats items
stats cachedump 1 0
quit
```

### What You'll See

```
STAT items:1:number 15
STAT items:1:age 245

KEY users:list:role_any:page_1 [50240 bytes; 45 s]
KEY games:list:platform_ps4:store_1:page_1 [125680 bytes; 30 s]
KEY games:list:platform_ps5:store_1:page_1 [118450 bytes; 55 s]
KEY accounts:list:page_1 [35890 bytes; 20 s]
KEY users:total_user_count [45 bytes; 350 s]
KEY dashboard:today_order_count [48 bytes; 120 s]
...
```

### Each Entry Contains

```
┌──────────────────────────────────────────────────────┐
│ Key: "users:list:role_any:page_1"                    │
├──────────────────────────────────────────────────────┤
│ Value: [Binary Serialized PHP Object]               │
│ - Size: ~50KB                                        │
│ - Contains:                                          │
│   * 15 User objects                                  │
│   * Each with storeProfile relationship             │
│   * Each with orders_count attribute                │
│   * Pagination metadata (total, per_page, etc.)     │
│ - TTL: 60 seconds                                    │
│ - Expires: 2025-10-28 12:46:30                       │
└──────────────────────────────────────────────────────┘
```

---

## 🔄 **Serialization Process**

### What Gets Stored in Memcached

**1. Simple Values:**
```php
// Our code
CacheManager::remember('users:total_user_count', 600, fn() => 7);

// Memcached stores
Key: "users:total_user_count"
Value: "i:7;" (PHP serialized integer)
Bytes: ~10 bytes
```

**2. Arrays:**
```php
// Our code
$stats = ['total' => 7, 'active' => 5];

// Memcached stores
Key: "dashboard:stats"
Value: "a:2:{s:5:"total";i:7;s:6:"active";i:5;}"
Bytes: ~50 bytes
```

**3. Eloquent Collections (User Listing):**
```php
// Our code
$users = User::with('storeProfile')->paginate(15);

// Laravel serializes
$serialized = serialize($users);

// Memcached stores
Key: "users:list:role_any:page_1"
Value: "O:29:"Illuminate\Pagination\LengthAwarePaginator":8:{
    s:5:"items";
    a:15:{
        i:0;O:15:"App\Models\User":30:{
            s:2:"id";i:1;
            s:4:"name";s:10:"John Doe";
            s:5:"email";s:17:"john@example.com";
            s:8:"is_active";i:1;
            s:13:"storeProfile";O:22:"App\Models\StoreProfile":15:{
                s:2:"id";i:5;
                s:4:"name";s:15:"Downtown Store";
                ...
            }
            s:12:"orders_count";i:25;
            ...
        }
        i:1;O:15:"App\Models\User":30:{...}
        ...more users...
    }
    s:5:"total";i:7;
    s:8:"perPage";i:15;
    s:12:"currentPage";i:1;
    s:4:"path";s:30:"http://example.com/manager/users";
    ...
}"
Bytes: ~50,000 bytes (50KB)
```

---

## 🗃️ **Real Examples from Your Application**

### 1. Dashboard Statistics in Memcached

```
┌────────────────────────────────────────────────────────────┐
│ Memcached Key/Value Pairs for Dashboard                   │
├────────────────────────────────────────────────────────────┤
│ users:total_user_count          → i:7;                    │ (10 bytes)
│ accounts:total_account_cost     → d:5000.50;              │ (15 bytes)
│ cards:total_code_cost           → d:12500.75;             │ (16 bytes)
│ dashboard:today_order_count     → i:45;                   │ (11 bytes)
│ orders:unique_buyer_phone_count → i:32;                   │ (11 bytes)
│ users:new_users_role_5_count    → i:8;                    │ (10 bytes)
│ devices:repair_stats            → a:4:{...};              │ (200 bytes)
├────────────────────────────────────────────────────────────┤
│ Total: 7 keys, ~270 bytes                                  │
└────────────────────────────────────────────────────────────┘
```

### 2. User Listings in Memcached

```
┌────────────────────────────────────────────────────────────┐
│ Memcached Key/Value Pairs for User Pages                  │
├────────────────────────────────────────────────────────────┤
│ users:list:role_any:page_1  → O:29:"...Paginator":{...}  │ (50KB)
│ users:list:role_any:page_2  → O:29:"...Paginator":{...}  │ (50KB)
│ users:list:role_2:page_1    → O:29:"...Paginator":{...}  │ (30KB)
│ users:list:role_1:page_1    → O:29:"...Paginator":{...}  │ (15KB)
├────────────────────────────────────────────────────────────┤
│ Total: 4 keys, ~145KB                                      │
└────────────────────────────────────────────────────────────┘
```

### 3. Game Listings in Memcached (Store-Specific)

```
┌────────────────────────────────────────────────────────────┐
│ Store 1 Cache                                              │
├────────────────────────────────────────────────────────────┤
│ games:list:platform_all:page_1      → O:...{...}          │ (80KB)
│ games:list:platform_ps4:store_1:page_1 → O:...{...}       │ (120KB)
│ games:list:platform_ps5:store_1:page_1 → O:...{...}       │ (115KB)
├────────────────────────────────────────────────────────────┤
│ Total for Store 1: 3 keys, ~315KB                          │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│ Store 2 Cache (SEPARATE)                                   │
├────────────────────────────────────────────────────────────┤
│ games:list:platform_ps4:store_2:page_1 → O:...{...}       │ (120KB)
│ games:list:platform_ps5:store_2:page_1 → O:...{...}       │ (115KB)
├────────────────────────────────────────────────────────────┤
│ Total for Store 2: 2 keys, ~235KB                          │
└────────────────────────────────────────────────────────────┘
```

---

## 📏 **Memory Usage Estimation**

### Typical Cache Size Per Type

| Data Type | Size per Entry | Lifetime | Example |
|-----------|---------------|----------|---------|
| Simple count | 10-50 bytes | 600s | `users:total_user_count` |
| Array stats | 200-500 bytes | 300s | `devices:repair_stats` |
| User listing (15 users) | 40-60 KB | 60s | `users:list:role_any:page_1` |
| Game listing (100 games) | 80-120 KB | 60s | `games:list:platform_all:page_1` |
| Account listing (10 accounts) | 20-40 KB | 60s | `accounts:list:page_1` |

### Total Memory Usage (Estimated)

```
┌────────────────────────────────────────────┐
│ Typical Application Cache Size             │
├────────────────────────────────────────────┤
│ Dashboard stats: ~1 KB (7 keys)           │
│ User listings: ~200 KB (5 pages)          │
│ Game listings: ~1 MB (10 pages)           │
│ Account listings: ~150 KB (5 pages)       │
│ Store-specific: ~500 KB per store         │
├────────────────────────────────────────────┤
│ Total per store: ~2-3 MB                  │
│ 3 stores: ~6-9 MB total                   │
└────────────────────────────────────────────┘

Default Memcached Memory: 64 MB
Sufficient for: 7-10 stores easily!
```

---

## 🎯 **How Cache Keys Work**

### Our Cache Keys → Memcached Keys

```
Application Cache Key              Memcached Actual Key
─────────────────────────────────────────────────────────────
users:total_user_count          → users:total_user_count
users:list:role_any:page_1      → users:list:role_any:page_1
games:list:platform_ps4:store_1:page_1 
  → games:list:platform_ps4:store_1:page_1
```

**With Cache Prefix (if configured):**
```env
CACHE_PREFIX=myapp_
```

```
Application Key                 Memcached Actual Key
───────────────────────────────────────────────────────
users:total_user_count       → myapp_users:total_user_count
users:list:role_any:page_1   → myapp_users:list:role_any:page_1
```

---

## 🔍 **Viewing Memcached Data**

### Method 1: Telnet (Direct Access)

```bash
telnet 127.0.0.1 11211
```

```
# Get a value
get users:total_user_count
VALUE users:total_user_count 0 4
i:7;
END

# Get statistics
stats
STAT curr_items 15
STAT total_items 125
STAT bytes 2456789
STAT curr_connections 3
STAT cmd_get 1250
STAT cmd_set 125
STAT get_hits 1125    ← 90% hit ratio!
STAT get_misses 125
END

# Delete a key
delete users:total_user_count
DELETED
```

### Method 2: PHP Script

```php
// Connect to Memcached
$memcached = new Memcached();
$memcached->addServer('127.0.0.1', 11211);

// Get a value
$value = $memcached->get('users:total_user_count');
echo "Value: " . print_r(unserialize($value), true);

// Check stats
$stats = $memcached->getStats();
print_r($stats);

// Get all keys (requires getAllKeys() or getServerKey())
$allKeys = $memcached->getAllKeys();
print_r($allKeys);
```

### Method 3: Our Health Check Page

Visit: `/manager/health-check`

Shows:
- Memcached connection status
- Memory usage
- Cache statistics
- Hit/miss ratio

---

## ⚡ **Performance Characteristics**

### Read Performance

```
Database Query:
┌──────────────────────────────────────┐
│ 1. Connect to MySQL                  │ 5-10ms
│ 2. Parse SQL                         │ 1-2ms
│ 3. Execute query                     │ 50-100ms
│ 4. Fetch results                     │ 10-20ms
│ 5. Build Eloquent models            │ 20-30ms
│ 6. Load relationships               │ 30-50ms
├──────────────────────────────────────┤
│ Total: 116-212ms                     │
└──────────────────────────────────────┘

Memcached Read:
┌──────────────────────────────────────┐
│ 1. Connect to Memcached (pooled)    │ 0.5ms
│ 2. Get value by key                 │ 2-5ms
│ 3. Unserialize data                 │ 3-8ms
├──────────────────────────────────────┤
│ Total: 5.5-13.5ms                    │
└──────────────────────────────────────┘

Speed Improvement: 10-20x faster! ⚡
```

---

## 🎨 **Visual Representation**

### Memcached Memory Layout

```
┌─────────────────────────────────────────────────────────┐
│ Memcached Server (RAM - 64 MB allocated)                │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ [users:total_user_count] → 7                (10 bytes)  │
│ [users:list:role_any:page_1] → [15 users]  (50 KB)     │
│ [users:list:role_2:page_1] → [8 users]     (30 KB)     │
│ [games:list:platform_all:page_1] → [...]   (80 KB)     │
│ [games:list:platform_ps4:store_1:page_1]   (120 KB)    │
│ [games:list:platform_ps4:store_2:page_1]   (120 KB)    │
│ [games:list:platform_ps5:store_1:page_1]   (115 KB)    │
│ [accounts:list:page_1] → [10 accounts]     (35 KB)     │
│ [dashboard:today_order_count] → 45         (11 bytes)  │
│ ...more keys...                                          │
│                                                          │
│ Used: ~2.5 MB / 64 MB (4% utilization)                  │
└─────────────────────────────────────────────────────────┘
```

---

## 🔧 **How Our CacheManager Fits**

```
Your Application Code
        ↓
CacheManager (Abstraction Layer)
    - Generates descriptive keys
    - Handles serialization automatically
    - Provides pattern-based invalidation
    - Tracks metadata
        ↓
Laravel Cache Facade (Framework Layer)
    - Routes to configured driver (Memcached/Redis/File)
    - Handles serialization/deserialization
    - Adds prefixes if configured
        ↓
Memcached Driver (Storage Layer)
    - Stores as key/value pairs
    - Manages TTL/expiration
    - Handles memory allocation
        ↓
Memcached Server (Physical Storage)
    - In-memory storage (RAM)
    - Distributed across nodes (if clustered)
    - Fast access (~1-5ms per operation)
```

---

## 💡 **Key Takeaways**

### 1. **Simple Storage, Complex Data**
- Memcached only knows strings (keys) and bytes (values)
- Laravel handles all the complexity (serialization)
- You work with PHP objects, Laravel converts to strings

### 2. **Efficient Key Design**
Our keys are descriptive and organized:
```
users:list:role_any:page_1              ✅ Clear, structured
games:list:platform_ps4:store_1:page_1  ✅ Includes all context
my_cache_key_123                        ❌ Unclear, hard to manage
```

### 3. **Automatic Serialization**
```php
// You store complex objects
$users = User::with('storeProfile')->get();
Cache::put('key', $users, 60);

// Memcached stores serialized string
Memcached: "key" → "O:29:Illuminate\Database\Eloquent\Collection:1:{...}"

// You get back complex objects
$users = Cache::get('key');
// Ready to use! Laravel deserialized it for you
```

### 4. **TTL Management**
```
Memcached handles expiration:
- Store: $memcached->set('key', 'value', 60)  // 60 seconds
- After 60s: Key automatically deleted from memory
- Next request: Cache miss, query runs, cache rebuilds
```

---

## 📊 **Monitoring Memcached**

### Check What's in Memcached

```bash
# Via our health check
curl http://your-domain.com/check-cache

# Via our cache stats
php artisan cache:stats

# Via Memcached directly
echo "stats" | nc 127.0.0.1 11211
```

### View Specific Key

```php
// In tinker
php artisan tinker

>>> $m = new Memcached();
>>> $m->addServer('127.0.0.1', 11211);
>>> $data = $m->get('users:list:role_any:page_1');
>>> print_r(unserialize($data));
```

---

## 🎯 **Summary**

**Your Question:** "If Memcached stores key/value pairs, how does this fit our application?"

**Answer:**

1. **Memcached stores everything as strings** (keys and serialized values)

2. **Laravel converts complex data automatically:**
   - PHP objects → Serialized strings (store)
   - Serialized strings → PHP objects (retrieve)

3. **Our cache keys are descriptive strings:**
   - `users:list:role_any:page_1`
   - `games:list:platform_ps4:store_1:page_1`

4. **Our cache values are serialized PHP objects:**
   - User collections with relationships
   - Paginator objects
   - Arrays with metadata

5. **The system works transparently:**
   - You write: `CacheManager::getUserListing(...)`
   - Memcached stores: Key → Serialized data
   - You get back: Fully hydrated PHP objects

**It's a perfect fit!** Memcached handles the simple storage, Laravel handles the complexity, and you work with clean, object-oriented PHP code! 🎉

---

**Memory Efficient:**
- Simple stats: ~10 bytes each
- Complex listings: 20-120 KB each
- Total usage: 2-10 MB typically
- Memcached default: 64 MB (plenty of room!)

**Lightning Fast:**
- Cache get: 2-5ms
- Database query: 100-200ms
- **40-100x faster!** ⚡

