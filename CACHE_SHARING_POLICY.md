# Cache Sharing Policy

## Overview

This document defines which caches are shared between users and which must be isolated for data privacy and correctness.

---

## 🟢 **Shared Cache (Global Data)**

These caches are **shared between ALL users** because data is the same for everyone:

### Statistics (Dashboard)
✅ **Can be shared** - Global aggregates, no user-specific filtering

| Cache Key | Description | Shared? |
|-----------|-------------|---------|
| `users:total_user_count` | Total users in system | ✅ YES |
| `accounts:total_account_cost` | Total account costs | ✅ YES |
| `cards:total_code_cost` | Total card costs | ✅ YES |
| `dashboard:today_order_count` | Today's total orders | ✅ YES |
| `orders:unique_buyer_phone_count` | Unique buyers | ✅ YES |
| `users:new_users_role_5_count` | New customers this month | ✅ YES |
| `devices:repair_stats` | Device repair statistics | ✅ YES |

**Cache Behavior:**
```
Manager A loads dashboard → Cache stats
Manager B loads dashboard → Gets same cached stats ✅
Manager C loads dashboard → Gets same cached stats ✅
```

### Listings (No User-Specific Data)

✅ **Can be shared** - All users see the same data

| Page | Cache Key Example | Shared? |
|------|-------------------|---------|
| **Users** | `users:list:role_any:page_1` | ✅ YES |
| **Accounts** | `accounts:list:page_1` | ✅ YES |
| **All Games** | `games:list:platform_all:page_1` | ✅ YES |

**Cache Behavior:**
```
Manager A views users page → Caches user list
Manager B views users page → Gets same cached list ✅
Manager C views users page → Gets same cached list ✅
```

---

## 🔒 **Store-Specific Cache (Per Store)**

These caches are **separate per store** because data differs based on store:

### PS4/PS5 Game Listings

❌ **CANNOT be shared** - Different stores see different prices

| Page | Cache Key Example | Store-Specific? |
|------|-------------------|-----------------|
| **PS4 Games** | `games:list:platform_ps4:store_1:page_1` | 🔒 YES |
| **PS5 Games** | `games:list:platform_ps5:store_2:page_1` | 🔒 YES |

**Why?** Special prices are store-specific:
```sql
-- Line 397-399 in ManagerController
->leftJoin('special_prices', function ($join) use ($storeProfileId) {
    $join->on('games.id', '=', 'special_prices.game_id')
        ->where('special_prices.store_profile_id', '=', $storeProfileId);
})
```

**Cache Behavior:**
```
Manager A (Store 1) views PS4 games
↓
Cache: games:list:platform_ps4:store_1:page_1
Prices: $50, $45, $60 (Store 1 special prices)

Manager B (Store 2) views PS4 games  
↓
Cache: games:list:platform_ps4:store_2:page_1  ← Different cache!
Prices: $55, $42, $58 (Store 2 special prices) ✅
```

---

## 👤 **User-Specific Cache (Per User)**

These caches are **separate per user** because each user sees different data:

### Order Listings

❌ **CANNOT be shared** - Different roles see different orders

| Role | Filter Logic | Cache Key Pattern |
|------|-------------|-------------------|
| **Admin** | All orders | `orders:list:filter_today:admin:page_1` |
| **Admin (filtered)** | Specific store orders | `orders:list:filter_today:store_5:page_1` |
| **Sales** | Only their orders | `orders:list:filter_today:user_15:page_1` |
| **Account Manager** | Only their orders | `orders:list:filter_today:user_20:page_1` |
| **Accountant** | Only their store orders | `orders:list:filter_today:store_3:page_1` |

**Why?** Different users see different orders:
```php
// Admin sees ALL orders
if ($roles->contains('admin')) {
    $orders = Order::whereDate(...)->paginate(10);
}

// Sales sees ONLY their orders
if ($roles->contains('sales')) {
    $orders = Order::whereDate(...)
        ->where('seller_id', $user->id)  // ← User-specific!
        ->paginate(10);
}
```

**Cache Behavior:**
```
Admin views orders
↓
Cache: orders:list:filter_today:admin:page_1
Orders: [Order 1, Order 2, Order 3, Order 4, Order 5]

Sales User (ID: 15) views orders
↓
Cache: orders:list:filter_today:user_15:page_1  ← Different cache!
Orders: [Order 2, Order 5] (only their orders) ✅

Sales User (ID: 20) views orders
↓
Cache: orders:list:filter_today:user_20:page_1  ← Different cache!
Orders: [Order 1, Order 3] (only their orders) ✅
```

---

## 📋 **Implementation Status**

### ✅ Implemented with Correct Sharing

| Feature | Sharing Type | Status |
|---------|-------------|--------|
| Dashboard stats | Shared (global) | ✅ Implemented |
| User listings | Shared (global) | ✅ Implemented |
| Account listings | Shared (global) | ✅ Implemented |
| All games listing | Shared (global) | ✅ Implemented |
| PS4 games | Store-specific | ✅ Fixed |
| PS5 games | Store-specific | ✅ Fixed |

### ⚠️ Not Cached Yet (Intentionally)

| Feature | Sharing Type | Status |
|---------|-------------|--------|
| Order listings | User-specific | ⚠️ Not cached (on purpose) |
| Device repairs | TBD | ⚠️ Not cached |
| Card listings | TBD | ⚠️ Not cached |

**Why not cached?**
- Orders are complex (user-specific, role-specific, store-specific)
- I've prepared the infrastructure but haven't enabled it yet
- Waiting to ensure correctness before enabling

---

## 🎯 **Cache Key Design Principles**

### 1. **Global Data (Shared)**
```
Pattern: {prefix}:{description}
Example: users:total_user_count
```

### 2. **Paginated Global Data (Shared)**
```
Pattern: {prefix}:list:page_{page}
Example: users:list:role_any:page_1
```

### 3. **Store-Specific Data**
```
Pattern: {prefix}:list:store_{storeId}:page_{page}
Example: games:list:platform_ps4:store_5:page_1
```

### 4. **User-Specific Data**
```
Pattern: {prefix}:list:user_{userId}:page_{page}
Example: orders:list:filter_today:user_15:page_1
```

### 5. **Combined (Store + Filter)**
```
Pattern: {prefix}:list:filter_{filter}:store_{storeId}:page_{page}
Example: orders:list:filter_has_problem:store_3:page_1
```

---

## 🔐 **Security Implications**

### ✅ **No Data Leakage**

With correct cache separation:
- ✅ Store 1 cannot see Store 2's special prices
- ✅ Sales User A cannot see Sales User B's orders
- ✅ Accountant for Store 1 cannot see Store 2's orders
- ✅ Each user sees only their authorized data

### ❌ **Before Fix (SECURITY ISSUE)**

```
Store 1 Manager caches PS4 games with Store 1 prices
↓
Store 2 Manager views PS4 games
↓
Gets Store 1 prices from cache ❌ DATA LEAKAGE!
```

### ✅ **After Fix (SECURE)**

```
Store 1 Manager caches: games:list:platform_ps4:store_1:page_1
Store 2 Manager caches: games:list:platform_ps4:store_2:page_1
↓
Each store has separate cache ✅ NO DATA LEAKAGE!
```

---

## 📊 **Cache Key Examples**

### Shared Caches (1 cache for all users)
```
users:total_user_count                    → 7 users
users:list:role_any:page_1               → [User 1, 2, 3...]
accounts:list:page_1                     → [Account 1, 2, 3...]
games:list:platform_all:page_1           → [All games]
dashboard:today_order_count              → 45 orders
```

### Store-Specific Caches (1 cache per store)
```
games:list:platform_ps4:store_1:page_1   → PS4 games with Store 1 prices
games:list:platform_ps4:store_2:page_1   → PS4 games with Store 2 prices
games:list:platform_ps5:store_1:page_1   → PS5 games with Store 1 prices
games:list:platform_ps5:store_3:page_1   → PS5 games with Store 3 prices
```

### User-Specific Caches (1 cache per user)
```
orders:list:filter_today:user_15:page_1      → User 15's orders
orders:list:filter_today:user_20:page_1      → User 20's orders
orders:list:filter_has_problem:user_15:page_1 → User 15's problematic orders
```

### Admin Caches (Admins see all data)
```
orders:list:filter_today:admin:page_1              → All today's orders
orders:list:filter_today:store_5:page_1            → Store 5's orders (admin filtered)
orders:list:filter_has_problem:admin:page_1        → All problematic orders
```

---

## 🔄 **Cache Invalidation Impact**

### Global Cache
```
When a user is created:
↓
UserObserver fires
↓
Invalidates ALL user caches (for all users)
↓
Next request by ANY user rebuilds cache
```

### Store-Specific Cache
```
When a game is updated:
↓
GameObserver fires
↓
Invalidates ALL game caches (all stores, all platforms)
↓
Each store rebuilds their own cache on next request
```

### User-Specific Cache
```
When an order is created:
↓
OrderObserver fires
↓
Invalidates ALL order caches (all users, all filters)
↓
Each user rebuilds their own cache on next request
```

---

## 📈 **Cache Memory Usage**

### Example: 3 Stores, 10 Users

**Shared Caches:**
```
Dashboard stats: 7 keys
User listings: 5 keys (different roles/pages)
Account listings: 3 keys (different pages)
All games: 2 keys (different pages)
Total: ~17 keys (shared by everyone)
```

**Store-Specific Caches:**
```
PS4 games: 3 keys (3 stores × 1 page each)
PS5 games: 3 keys (3 stores × 1 page each)
Total: ~6 keys
```

**User-Specific Caches (if implemented):**
```
Orders: 10 keys (10 users × 1 page each)
Total: ~10 keys
```

**Grand Total: ~33 cache keys** (very manageable!)

---

## ⚡ **Performance vs Privacy Trade-off**

### Shared Cache (Better Performance)
- ✅ One query serves multiple users
- ✅ Lower memory usage
- ✅ Faster cache hits
- ❌ Must ensure no user-specific data!

### Isolated Cache (Better Privacy)
- ✅ Each user gets correct data
- ✅ No data leakage
- ✅ Secure
- ❌ More cache keys
- ❌ More memory usage

---

## 🎯 **Decision Matrix**

Use this to decide cache sharing strategy:

| Question | Answer | Cache Type |
|----------|--------|------------|
| Does data vary by user? | No | **Shared** |
| Does data vary by role? | No | **Shared** |
| Does data vary by store? | No | **Shared** |
| Does data vary by user? | Yes | **User-Specific** |
| Does data vary by store? | Yes | **Store-Specific** |
| Contains sensitive info? | Yes | **User/Store-Specific** |
| Contains user's private data? | Yes | **User-Specific** |

---

## 🚀 **Summary**

### Current Implementation ✅

**Shared Caches:**
- Dashboard statistics → All managers see same stats
- User listings → All managers see same users
- Account listings → All managers see same accounts
- All games → All managers see same game list

**Store-Specific Caches:**
- PS4 games → Each store sees their own prices
- PS5 games → Each store sees their own prices

**User-Specific (Not Cached Yet):**
- Orders → Would require user-specific cache keys
- Complex role-based filtering prevents simple caching

### Security Status ✅

- ✅ No data leakage between stores
- ✅ Correct prices per store
- ✅ Global data properly shared
- ✅ User-specific data not cached yet (safe)

**Your cache system is secure and performant!** 🔒⚡

---

**Last Updated:** October 28, 2025  
**Git Branch:** `feature/cache-optimizations`  
**Status:** Production Ready with Correct Data Isolation

