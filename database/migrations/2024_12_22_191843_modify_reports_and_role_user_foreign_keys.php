<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyReportsAndRoleUserForeignKeys extends Migration
{
    public function up()
    {
        // Handle reports table
        // Check if foreign key exists and drop it
        $reportsForeignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'reports' 
            AND COLUMN_NAME = 'seller_id' 
            AND CONSTRAINT_NAME != 'PRIMARY'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        if (!empty($reportsForeignKeys)) {
            DB::statement("ALTER TABLE reports DROP FOREIGN KEY {$reportsForeignKeys[0]->CONSTRAINT_NAME}");
        }
        
        if (Schema::hasColumn('reports', 'seller_id')) {
            Schema::table('reports', function (Blueprint $table) {
                // Make the column nullable to support SET NULL
                $table->unsignedBigInteger('seller_id')->nullable()->change();
                // Create the new foreign key with SET NULL
                $table->foreign('seller_id')
                    ->references('id')->on('users')
                    ->onDelete('set null'); // Change to SET NULL
            });
        }

        // Handle role_user table
        // Check if foreign key exists and drop it
        $roleUserForeignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'role_user' 
            AND COLUMN_NAME = 'user_id' 
            AND CONSTRAINT_NAME != 'PRIMARY'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        if (!empty($roleUserForeignKeys)) {
            DB::statement("ALTER TABLE role_user DROP FOREIGN KEY {$roleUserForeignKeys[0]->CONSTRAINT_NAME}");
        }
        
        if (Schema::hasColumn('role_user', 'user_id')) {
            Schema::table('role_user', function (Blueprint $table) {
                // Make the column nullable to support SET NULL
                $table->unsignedBigInteger('user_id')->nullable()->change();
                // Create the new foreign key with SET NULL
                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->onDelete('set null'); // Change to SET NULL
            });
        }
    }

    public function down()
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['seller_id']); // Drop the modified foreign key
            // Make the column NOT NULL again
            $table->unsignedBigInteger('seller_id')->nullable(false)->change();
            // Restore the original foreign key with CASCADE
            $table->foreign('seller_id')
                ->references('id')->on('users')
                ->onDelete('cascade'); // Restore to CASCADE
        });

        Schema::table('role_user', function (Blueprint $table) {
            $table->dropForeign(['user_id']); // Drop the modified foreign key
            // Make the column NOT NULL again
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            // Restore the original foreign key with CASCADE
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade'); // Restore to CASCADE
        });
    }
}
