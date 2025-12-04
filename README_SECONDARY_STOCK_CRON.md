# Secondary Stock Synchronization Cron Job

## Overview

This cron job automatically synchronizes secondary stock values for accounts. If either `ps4_secondary_stock` or `ps5_secondary_stock` is 0, it will set the other to 0 as well. This ensures consistency across both stock types.

**Important:** This cron job **excludes "PS5 Only" accounts** (accounts where all PS4 stocks are 0). The normalization only applies to regular accounts that support both PS4 and PS5 platforms.

## How It Works

- The command processes accounts in batches (default: 100 accounts per run)
- It tracks the last processed account ID using Laravel's cache system
- When all accounts are processed, it resets and starts from the beginning
- All changes are logged with before/after values for auditing
- The command is scheduled to run hourly via Laravel's task scheduler
- **PS5 Only accounts are automatically excluded** from synchronization

### PS5 Only Accounts

"PS5 Only" accounts are identified by having all three PS4 stock fields set to 0:
- `ps4_primary_stock = 0`
- `ps4_secondary_stock = 0`
- `ps4_offline_stock = 0`

These accounts are designed to only work with PS5 consoles and should not have their PS5 secondary stock synchronized with PS4 secondary stock. The cron job automatically skips these accounts during processing.

## Command Details

**Command Name:** `accounts:sync-secondary-stock`

**Options:**
- `--limit`: Number of accounts to process per run (default: 100)

**Schedule:** Runs every hour (configured in `app/Console/Kernel.php`)

## Setup Instructions

### Step 1: Verify the Command

First, verify that the command is available:

```bash
php artisan list | grep accounts:sync-secondary-stock
```

Or test it manually:

```bash
php artisan accounts:sync-secondary-stock
```

### Step 2: Configure Cron Job on Hostinger

1. Log in to your Hostinger control panel
2. Navigate to **Cron Jobs** section
3. Create a new cron job with the following settings:

**Cron Command:**
```
* * * * * /opt/alt/php82/usr/bin/lsphp /home/u605441708/domains/gamesspoteg.com/public_html/staging.gamesspoteg.com/artisan schedule:run >> /dev/null 2>&1
```

**Important Notes:**
- Replace `/home/u605441708/domains/gamesspoteg.com/public_html/staging.gamesspoteg.com/` with your actual project path
- The `* * * * *` schedule means "run every minute" - this is correct because Laravel's scheduler will handle the actual timing
- The `>> /dev/null 2>&1` redirects output to prevent email notifications (optional but recommended)
- Adjust the PHP version path (`/opt/alt/php82/usr/bin/lsphp`) if needed for your server

**Alternative Cron Command (if the above path doesn't work):**
```
* * * * * cd /home/u605441708/domains/gamesspoteg.com/public_html/staging.gamesspoteg.com && /opt/alt/php82/usr/bin/lsphp artisan schedule:run >> /dev/null 2>&1
```

### Step 3: Verify Cron Job is Running

After setting up the cron job, you can verify it's working by:

1. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Look for log entries:**
   - Search for "Account secondary stock sync - BEFORE"
   - Search for "Account secondary stock sync - AFTER"

3. **Check cache:**
   The command stores the last processed ID in cache with key: `sync_secondary_stock_last_id`

## Manual Testing

### Run the Command Manually

```bash
php artisan accounts:sync-secondary-stock
```

### Run with Custom Limit

```bash
php artisan accounts:sync-secondary-stock --limit=50
```

### Expected Output

```
Starting secondary stock synchronization (limit: 100, excluding PS5 Only accounts)...
Secondary stock synchronization completed. Updated X account(s). Last processed ID: Y
```

**Note:** The number of updated accounts excludes any PS5 Only accounts that were skipped during processing.

## Logging

All changes are logged to `storage/logs/laravel.log` with the following information:

**Before Update:**
- Account ID
- Email
- Game ID
- PS4 Secondary Stock (before)
- PS5 Secondary Stock (before)

**After Update:**
- Account ID
- Email
- Game ID
- PS4 Secondary Stock (after)
- PS5 Secondary Stock (after)
- Changes made

### View Logs

```bash
# View recent logs
tail -n 100 storage/logs/laravel.log

# Search for sync entries
grep "Account secondary stock sync" storage/logs/laravel.log

# View logs in real-time
tail -f storage/logs/laravel.log | grep "Account secondary stock sync"
```

## Configuration

### Change Processing Limit

Edit the schedule in `app/Console/Kernel.php`:

```php
$schedule->command('accounts:sync-secondary-stock --limit=200')->hourly();
```

### Change Schedule Frequency

Edit `app/Console/Kernel.php` to change how often it runs:

```php
// Run every 30 minutes
$schedule->command('accounts:sync-secondary-stock')->everyThirtyMinutes();

// Run every 2 hours
$schedule->command('accounts:sync-secondary-stock')->everyTwoHours();

// Run daily at specific time
$schedule->command('accounts:sync-secondary-stock')->dailyAt('02:00');
```

## Troubleshooting

### Cron Job Not Running

1. **Check cron job is active:**
   - Verify in Hostinger control panel that the cron job is enabled
   - Check the cron job path is correct

2. **Test Laravel scheduler manually:**
   ```bash
   php artisan schedule:run
   ```

3. **Check file permissions:**
   ```bash
   chmod +x artisan
   ```

### Command Not Found

If you get "Command not found" error:

1. Clear Laravel cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

2. Re-register commands:
   ```bash
   php artisan optimize:clear
   ```

### No Accounts Being Processed

- Check if there are accounts with `ps4_secondary_stock = 0` or `ps5_secondary_stock = 0`
- Verify the cache key `sync_secondary_stock_last_id` - you can clear it:
  ```bash
  php artisan cache:forget sync_secondary_stock_last_id
  ```

### Logs Not Appearing

- Check `storage/logs` directory has write permissions:
  ```bash
  chmod -R 775 storage/logs
  ```
- Verify logging is enabled in `.env`:
  ```
  LOG_CHANNEL=stack
  LOG_LEVEL=debug
  ```

## Cache Management

The command uses Laravel cache to track progress. The cache key is:
- **Key:** `sync_secondary_stock_last_id`
- **TTL:** 7 days
- **Purpose:** Stores the last processed account ID to resume from where it left off

### Clear Cache (Reset Processing)

If you need to reset and start from the beginning:

```bash
php artisan cache:forget sync_secondary_stock_last_id
```

Or clear all cache:

```bash
php artisan cache:clear
```

## Monitoring

### Check Last Run Status

You can check the cache to see the last processed ID:

```bash
php artisan tinker
```

Then in tinker:
```php
Cache::get('sync_secondary_stock_last_id', 0);
```

### Database Query to Check Progress

```sql
-- Check total accounts and accounts that need synchronization (excluding PS5 Only)
SELECT COUNT(*) as total_accounts,
       SUM(CASE WHEN ps4_secondary_stock = 0 OR ps5_secondary_stock = 0 THEN 1 ELSE 0 END) as accounts_needing_sync,
       SUM(CASE WHEN ps4_primary_stock = 0 AND ps4_secondary_stock = 0 AND ps4_offline_stock = 0 THEN 1 ELSE 0 END) as ps5_only_accounts
FROM accounts;
```

```sql
-- View PS5 Only accounts specifically
SELECT id, mail, game_id, 
       ps4_primary_stock, ps4_secondary_stock, ps4_offline_stock,
       ps5_primary_stock, ps5_secondary_stock, ps5_offline_stock
FROM accounts
WHERE ps4_primary_stock = 0 
  AND ps4_secondary_stock = 0 
  AND ps4_offline_stock = 0;
```

```sql
-- View accounts that will be processed by the cron (need sync and NOT PS5 Only)
SELECT id, mail, game_id,
       ps4_secondary_stock, ps5_secondary_stock
FROM accounts
WHERE (ps4_secondary_stock = 0 OR ps5_secondary_stock = 0)
  AND NOT (ps4_primary_stock = 0 AND ps4_secondary_stock = 0 AND ps4_offline_stock = 0)
LIMIT 10;
```

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify cron job is running in Hostinger control panel
3. Test the command manually: `php artisan accounts:sync-secondary-stock`

