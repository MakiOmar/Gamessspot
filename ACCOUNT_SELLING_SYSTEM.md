# Account Selling System

## Overview

This document explains how the account selling system works in the GamesSspot platform, including what accounts are available for selling and the rules that govern the sales process.

## Table of Contents

1. [Core Concepts](#core-concepts)
2. [Account Structure](#account-structure)
3. [Game Configuration](#game-configuration)
4. [Selling Process](#selling-process)
5. [Availability Rules](#availability-rules)
6. [Store Profile Restrictions](#store-profile-restrictions)
7. [Stock Management](#stock-management)
8. [Order Creation Flow](#order-creation-flow)

---

## Core Concepts

The system manages PlayStation game accounts that can be sold in different configurations:

- **Platforms**: PS4 and PS5
- **Account Types**: 
  - **Primary**: Full access with game sharing capabilities
  - **Secondary**: Limited access
  - **Offline**: Can play games without internet connection

Each account contains multiple games and has individual stock counters for each type and platform combination.

---

## Account Structure

### Database Fields

Each `Account` record contains:

**Basic Information:**
- `id` - Unique identifier
- `mail` - Account email address
- `password` - Account password
- `game_id` - Associated game
- `region` - Account region (e.g., US, UK, etc.)
- `cost` - Purchase cost of the account
- `birthdate` - Account birthdate
- `login_code` - Two-factor authentication code

**Stock Fields (Inventory Counters):**
- `ps4_primary_stock` - Available PS4 primary sales
- `ps4_secondary_stock` - Available PS4 secondary sales
- `ps4_offline_stock` - Available PS4 offline sales
- `ps5_primary_stock` - Available PS5 primary sales
- `ps5_secondary_stock` - Available PS5 secondary sales
- `ps5_offline_stock` - Available PS5 offline sales

### Default Stock Values

When creating a new account, default stock values are:

```php
ps4_primary_stock   = 1
ps4_secondary_stock = 1
ps4_offline_stock   = 2  // Can be sold twice
ps5_primary_stock   = 1
ps5_secondary_stock = 1
ps5_offline_stock   = 1
```

**PS5 Only Accounts:**
If an account is marked as "PS5 Only":
```php
ps4_primary_stock   = 0
ps4_secondary_stock = 0
ps4_offline_stock   = 0
ps5_offline_stock   = 2
```

---

## Game Configuration

### Game Status Flags

Each `Game` record has status flags that control whether a specific type can be sold:

**PS4 Status Flags:**
- `ps4_primary_status` (boolean) - Enable/disable PS4 primary sales
- `ps4_secondary_status` (boolean) - Enable/disable PS4 secondary sales
- `ps4_offline_status` (boolean) - Enable/disable PS4 offline sales

**PS5 Status Flags:**
- `ps5_primary_status` (boolean) - Enable/disable PS5 primary sales
- `ps5_secondary_status` (boolean) - Enable/disable PS5 secondary sales
- `ps5_offline_status` (boolean) - Enable/disable PS5 offline sales

### Price Configuration

Each game also stores prices for each type:

```php
ps4_primary_price
ps4_secondary_price
ps4_offline_price
ps5_primary_price
ps5_secondary_price
ps5_offline_price
```

---

## Selling Process

### Account Selection Algorithm

When an order is placed, the system selects an account using the following criteria:

1. **Game Match**: Account must contain the requested game
2. **Stock Availability**: The specific stock type must be > 0
3. **Game Status**: The game's status flag for that type must be `true`
4. **Special Conditions**: Platform-specific rules (see below)
5. **Order**: **Oldest account first (FIFO)** - `ORDER BY accounts.created_at ASC`

### Query Example

```php
$accountQuery = Account::where('game_id', $game_id)
    ->join('games', 'accounts.game_id', '=', 'games.id')
    ->where("games.{$sold_item_status}", true)
    ->orderBy('accounts.created_at', 'asc')
    ->where($sold_item, '>', 0);
```

---

## Availability Rules

### What CAN Be Sold

An account type is available for sale when **ALL** of the following conditions are met:

1. ✅ **Stock Available**: The specific stock counter is greater than 0
2. ✅ **Status Enabled**: The game's status flag for that type is `true`
3. ✅ **Not Blocked**: Store is not blocked from selling this game (via SpecialPrice)
4. ✅ **Platform Rules**: Meets platform-specific requirements (see below)

### What CANNOT Be Sold

An account type is **NOT** available for sale when:

1. ❌ **No Stock**: Stock counter = 0
2. ❌ **Status Disabled**: Game's status flag is `false`
3. ❌ **Store Blocked**: Store has `is_available = false` in SpecialPrice for this game
4. ❌ **Violates Platform Rules**: Doesn't meet platform-specific requirements

---

## Platform-Specific Rules

### PS4 Platform Rules

#### PS4 Primary Accounts
**Special Restriction**: PS4 primary accounts can only be sold if the account has **zero offline stock**.

```php
if ($platform === '4' && $type === 'primary') {
    $accountQuery->where('ps4_offline_stock', 0)
                 ->where('ps4_primary_stock', '>', 0);
}
```

**Reason**: This prevents selling an account as both offline and primary, which could cause conflicts.

#### PS4 Secondary Accounts
No special restrictions - only requires stock > 0 and status enabled.

#### PS4 Offline Accounts
No special restrictions - can have up to 2 offline sales per account.

### PS5 Platform Rules

#### PS5 Primary/Secondary/Offline Accounts
**No Special Restrictions**: PS5 accounts do not have the offline stock restriction that PS4 has.

```php
if ($platform === '5') {
    // No offline stock check required
    $accountQuery->where($sold_item, '>', 0);
}
```

---

## Store Profile Restrictions

### Special Prices and Restrictions

The `special_prices` table allows administrators to:

1. **Set Custom Prices**: Override default game prices for specific stores
2. **Block Games**: Prevent specific stores from selling certain games

### How Blocking Works

```php
public function isBlockedForGame($gameId)
{
    return $this->specialPrices()
        ->where('game_id', $gameId)
        ->where('store_profile_id', $this->id)
        ->where('is_available', false)
        ->exists();
}
```

If a `SpecialPrice` record exists with `is_available = false`, that store **cannot** sell that game, regardless of stock or status.

### UI Indicator

When a store is blocked from selling a game, the game card displays:

```
⚠️ You are not allowed to sell this game due to restrictions on your store profile.
```

---

## Stock Management

### Decrementing Stock

When an order is successfully created:

```php
$account->decrement($sold_item, 1);
```

This reduces the specific stock counter by 1. For example:
- Selling PS4 Primary → `ps4_primary_stock` decreased by 1
- Selling PS5 Offline → `ps5_offline_stock` decreased by 1

### Stock Exhaustion

When stock reaches 0:
- The account is still in the database
- It just won't be selected for that specific type anymore
- Other types on the same account can still be sold (if they have stock)

### Example

```
Initial Account State:
- ps4_primary_stock = 1
- ps4_offline_stock = 0
- ps5_primary_stock = 1

After selling PS4 Primary:
- ps4_primary_stock = 0  ← Decreased
- ps4_offline_stock = 0
- ps5_primary_stock = 1  ← Still available for sale
```

---

## Order Creation Flow

### Step-by-Step Process

1. **Validation**
   - Validate request data (game_id, platform, type, buyer info, price)
   - Check order amount against system settings

2. **Customer Management**
   - Check if customer exists by phone number
   - Create new customer if needed with default 'customer' role

3. **Account Selection**
   - Build query with game, platform, type, and status filters
   - Apply platform-specific rules
   - Select oldest available account (FIFO)
   - Return error if no account found

4. **Transaction**
   - Begin database transaction
   - Decrement account stock
   - Create order record
   - Commit transaction

5. **Response**
   - Return account credentials to seller
   - Include order ID
   - Show warning if recent conflicting order exists (PS4 only)

### Transaction Safety

All stock changes and order creation happen within a database transaction:

```php
DB::beginTransaction();
try {
    $account->decrement($sold_item, 1);
    $order = Order::create($order_data);
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    return error_response();
}
```

This ensures that stock is never decremented without a corresponding order.

---

## Order Record Structure

Each `Order` contains:

```php
seller_id          // User who made the sale (null for API orders)
store_profile_id   // Store where sale was made
account_id         // Account that was sold
buyer_phone        // Customer phone number
buyer_name         // Customer name
price              // Sale price
notes              // Additional notes
sold_item          // Which stock was decremented (e.g., "ps4_primary_stock")
pos_order_id       // Point of sale system reference (optional)
card_id            // If selling a gift card instead
```

---

## Special Cases and Warnings

### PS4 Offline Conflict Detection

When selling PS4 offline or primary accounts, the system checks for recent conflicting orders:

**Scenario**: 
- Account has 1 offline stock remaining
- Within last 11 minutes, another seller sold from the same account

**System Response**:
```
Warning: [Seller Name] has sold [type] from this account. 
Please contact them on [phone number]
```

This helps prevent conflicts when multiple sellers work simultaneously.

### No Available Accounts

If no account matches the criteria:

```json
{
    "message": "No available account matches the specified criteria.",
    "status": 422
}
```

**Common Reasons**:
1. All accounts for this game have 0 stock for the requested type
2. Game status is disabled for this type
3. PS4 primary requested but all accounts have offline stock > 0
4. Store is blocked from selling this game

---

## API Endpoints

### Create Order (API)

**Endpoint**: `POST /api/orders`

**Required Parameters**:
```php
store_profile_id  // Store making the sale
game_id           // Game being sold
buyer_phone       // Customer phone
buyer_name        // Customer name
buyer_email       // Customer email
price             // Sale price
type              // 'primary' or 'secondary'
platform          // '4' or '5'
```

**Success Response**:
```json
{
    "message": "Order created successfully!",
    "account_email": "account@email.com",
    "account_password": "password123",
    "order_id": 123
}
```

### Create Order (Web)

**Endpoint**: `POST /orders/store`

Includes additional types: `'offline'`, `'primary'`, `'secondary'`

---

## Summary Table

| Type | PS4 Rules | PS5 Rules | Default Stock | Max Sales |
|------|-----------|-----------|---------------|-----------|
| **Primary** | Must have offline = 0 | No restrictions | 1 | 1 |
| **Secondary** | No restrictions | No restrictions | 1 | 1 |
| **Offline** | No restrictions | No restrictions | 2 | 2 |

### Availability Checklist

For an account to be sellable:

- [ ] Stock > 0 for that type
- [ ] Game status enabled for that type
- [ ] Store not blocked via SpecialPrice
- [ ] If PS4 Primary: offline stock must be 0
- [ ] Account must be oldest available (FIFO)

---

## Best Practices

### For Account Managers

1. **Set accurate stock levels** when creating accounts
2. **Mark PS5 Only accounts** appropriately to set PS4 stock to 0
3. **Monitor stock levels** regularly to prevent stockouts
4. **Import accounts** using the template to ensure consistency

### For Administrators

1. **Enable/disable game types** using status flags
2. **Set appropriate prices** for each type
3. **Use SpecialPrice records** to:
   - Block stores from problematic games
   - Set custom pricing for specific stores
4. **Monitor the sales log** for conflicts and issues

### For Sales Staff

1. **Check availability** before promising to customers
2. **Understand platform differences** (PS4 vs PS5 rules)
3. **Report stockouts** immediately to account managers
4. **Contact other sellers** if conflict warnings appear

---

## Troubleshooting

### "No available account matches the specified criteria"

**Check**:
1. Is there stock for this game/type combination?
2. Is the game status enabled for this type?
3. For PS4 Primary: Do accounts have offline stock > 0? (This blocks them)
4. Is your store blocked via SpecialPrice?

### Game shows in listing but can't be sold

**Possible Causes**:
1. Game status is disabled for that specific type
2. Store is blocked via SpecialPrice with `is_available = false`
3. For PS4 Primary: All accounts have offline stock > 0

### Stock shows available but sale fails

**Most Common**: Another seller just sold the last unit (race condition)
- The system uses transactions to prevent overselling
- Try again or choose another game

---

## Related Documentation

- [Account Import/Export Guide](ACCOUNT_IMPORT_EXPORT_README.md)
- [Laravel Settings Implementation](LARAVEL_SETTINGS_IMPLEMENTATION.md)
- [Production Deployment Guide](PRODUCTION_DEPLOYMENT_GUIDE.md)

---

## Technical References

### Models
- `App\Models\Account`
- `App\Models\Game`
- `App\Models\Order`
- `App\Models\StoresProfile`
- `App\Models\SpecialPrice`

### Controllers
- `App\Http\Controllers\OrderController`
- `App\Http\Controllers\AccountController`
- `App\Http\Controllers\ManagerController`
- `App\Http\Controllers\SpecialPriceController`

### Migrations
- `create_accounts_table.php`
- `create_games_table.php`
- `create_orders_table.php`
- `create_special_prices_table.php`

---

**Last Updated**: October 13, 2025

