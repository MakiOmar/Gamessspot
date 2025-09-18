# Production Deployment Guide - Account Import/Export Feature

## ⚠️ CRITICAL DEPLOYMENT CONSIDERATIONS

### **Potential Issues with Live Database:**

#### 1. **Foreign Key Constraint Changes** (HIGH RISK)
**Migration**: `2024_12_22_191843_modify_reports_and_role_user_foreign_keys.php`

**What it does:**
- Changes `reports.seller_id` from `NOT NULL` to `NULLABLE`
- Changes `role_user.user_id` from `NOT NULL` to `NULLABLE`
- Changes foreign key behavior from `CASCADE` to `SET NULL`

**⚠️ RISKS:**
- **Data Loss Risk**: If users are deleted, related reports and role assignments will have NULL values instead of being deleted
- **Application Logic**: Your application might not handle NULL values in these fields
- **Referential Integrity**: Changes the relationship behavior between tables

#### 2. **Missing Account Fields** (MEDIUM RISK)
**Migrations**: 
- `2024_10_06_110235_add_cost_and_password_to_accounts_table.php`
- `2024_10_06_122426_add_login_code_to_accounts_table.php`
- `2024_10_06_125729_add_birthdate_to_accounts_table.php`

**What it does:**
- Adds `cost`, `password`, `login_code`, `birthdate` fields to accounts table
- These fields are required for import functionality

**⚠️ RISKS:**
- **Existing Data**: Existing accounts will have NULL values for these new fields
- **Application Crashes**: If your application expects these fields to exist
- **Import Failures**: Import will fail if these fields are missing

## 🛡️ SAFE DEPLOYMENT STRATEGY

### **Phase 1: Pre-Deployment Assessment**

#### 1. **Database Backup**
```bash
# Create full database backup
mysqldump -u username -p database_name > backup_before_import_export_$(date +%Y%m%d_%H%M%S).sql

# Or use Laravel backup package
php artisan backup:run
```

#### 2. **Check Existing Data**
```sql
-- Check if accounts table has the required fields
DESCRIBE accounts;

-- Check existing accounts count
SELECT COUNT(*) FROM accounts;

-- Check if any accounts have NULL values in critical fields
SELECT COUNT(*) FROM accounts WHERE game_id IS NULL;
```

#### 3. **Test on Staging Environment**
- Deploy to staging first
- Test all existing functionality
- Verify import/export works correctly
- Check for any application errors

### **Phase 2: Safe Migration Strategy**

#### **Option A: Gradual Migration (RECOMMENDED)**

1. **Add Missing Fields First** (Safe)
```bash
# Run only the account field migrations
php artisan migrate --path=database/migrations/2024_10_06_110235_add_cost_and_password_to_accounts_table.php
php artisan migrate --path=database/migrations/2024_10_06_122426_add_login_code_to_accounts_table.php
php artisan migrate --path=database/migrations/2024_10_06_125729_add_birthdate_to_accounts_table.php
```

2. **Update Existing Data** (Critical)
```sql
-- Set default values for existing accounts
UPDATE accounts SET 
    cost = 0.00,
    password = 'TEMP_PASSWORD_' + id,
    login_code = 'TEMP_CODE_' + id,
    birthdate = '1990-01-01'
WHERE cost IS NULL OR password IS NULL OR login_code IS NULL OR birthdate IS NULL;
```

3. **Deploy Application Code** (Safe)
```bash
# Deploy the import/export functionality
git checkout feature/account-import-export
```

4. **Test Import/Export** (Safe)
- Test with existing data
- Verify no data corruption
- Check application functionality

5. **Foreign Key Migration** (RISKY - Do Last)
```bash
# Only run this after everything else works
php artisan migrate --path=database/migrations/2024_12_22_191843_modify_reports_and_role_user_foreign_keys.php
```

#### **Option B: Create New Migration for Production**

Create a safer migration that handles existing data:

```php
<?php
// Create: database/migrations/2024_12_22_200000_safe_production_account_import_export.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SafeProductionAccountImportExport extends Migration
{
    public function up()
    {
        // 1. Add missing fields with safe defaults
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'cost')) {
                $table->decimal('cost', 8, 2)->default(0.00);
            }
            if (!Schema::hasColumn('accounts', 'password')) {
                $table->string('password')->default('TEMP_PASSWORD');
            }
            if (!Schema::hasColumn('accounts', 'login_code')) {
                $table->longText('login_code')->nullable();
            }
            if (!Schema::hasColumn('accounts', 'birthdate')) {
                $table->date('birthdate')->nullable();
            }
        });

        // 2. Update existing records with safe defaults
        DB::statement("
            UPDATE accounts SET 
                cost = COALESCE(cost, 0.00),
                password = COALESCE(password, CONCAT('TEMP_PASSWORD_', id)),
                login_code = COALESCE(login_code, CONCAT('TEMP_CODE_', id)),
                birthdate = COALESCE(birthdate, '1990-01-01')
            WHERE cost IS NULL OR password IS NULL OR login_code IS NULL OR birthdate IS NULL
        ");

        // 3. Make fields NOT NULL after setting defaults
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('cost', 8, 2)->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }

    public function down()
    {
        // Safe rollback - don't drop columns, just make them nullable
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('cost', 8, 2)->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }
}
```

### **Phase 3: Post-Deployment Verification**

#### 1. **Data Integrity Checks**
```sql
-- Verify no NULL values in critical fields
SELECT COUNT(*) FROM accounts WHERE cost IS NULL OR password IS NULL;

-- Check foreign key relationships
SELECT COUNT(*) FROM reports WHERE seller_id IS NULL;
SELECT COUNT(*) FROM role_user WHERE user_id IS NULL;

-- Verify import/export data structure
SELECT mail, password, cost, birthdate, login_code FROM accounts LIMIT 5;
```

#### 2. **Application Testing**
- Test account creation (manual)
- Test account import (with sample data)
- Test account export
- Test existing functionality (orders, reports, etc.)

#### 3. **Performance Monitoring**
- Monitor database performance
- Check for any slow queries
- Monitor application logs for errors

## 🚨 ROLLBACK PLAN

### **If Issues Occur:**

#### 1. **Immediate Rollback**
```bash
# Rollback the problematic migration
php artisan migrate:rollback --step=1

# Or rollback to specific batch
php artisan migrate:rollback --batch=40
```

#### 2. **Database Restore**
```bash
# Restore from backup
mysql -u username -p database_name < backup_before_import_export_YYYYMMDD_HHMMSS.sql
```

#### 3. **Code Rollback**
```bash
# Switch back to master branch
git checkout master
git pull origin master
```

## 📋 DEPLOYMENT CHECKLIST

### **Pre-Deployment:**
- [ ] Full database backup created
- [ ] Staging environment tested
- [ ] All existing functionality verified
- [ ] Team notified of deployment
- [ ] Rollback plan prepared

### **During Deployment:**
- [ ] Deploy during low-traffic period
- [ ] Monitor application logs
- [ ] Test critical functionality immediately
- [ ] Have rollback ready

### **Post-Deployment:**
- [ ] Verify data integrity
- [ ] Test import/export functionality
- [ ] Monitor for 24 hours
- [ ] Update documentation

## 🔧 RECOMMENDED APPROACH

**For Production Deployment:**

1. **Use Option A (Gradual Migration)** - It's safer
2. **Deploy during maintenance window**
3. **Have database backup ready**
4. **Test thoroughly on staging first**
5. **Monitor closely after deployment**

**The foreign key migration is the most risky part** - consider if you really need to change from CASCADE to SET NULL, as this changes the application's behavior significantly.

## 💡 ALTERNATIVE: Skip Foreign Key Migration

If the foreign key changes aren't critical for the import/export functionality, you could:

1. Deploy only the account field migrations
2. Deploy the import/export code
3. Skip the foreign key migration entirely
4. Keep the existing CASCADE behavior

This would be the safest approach for production.
