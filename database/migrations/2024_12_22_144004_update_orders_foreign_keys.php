<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateOrdersForeignKeys extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('orders', 'seller_id')) {
            $this->dropForeignIfExists('orders', 'orders_seller_id_foreign', 'seller_id');

            Schema::table('orders', function (Blueprint $table) {
                // Make the column nullable first (required for SET NULL foreign key)
                $table->unsignedBigInteger('seller_id')->nullable()->change();
            });

            Schema::table('orders', function (Blueprint $table) {
                // Then add the foreign key with SET NULL
                $table->foreign('seller_id')
                    ->references('id')->on('users')
                    ->onDelete('set null');
            });
        }

        if (Schema::hasColumn('orders', 'account_id')) {
            $this->dropForeignIfExists('orders', 'orders_account_id_foreign', 'account_id');

            Schema::table('orders', function (Blueprint $table) {
                // Make the column nullable first (required for SET NULL foreign key)
                $table->unsignedBigInteger('account_id')->nullable()->change();
            });

            Schema::table('orders', function (Blueprint $table) {
                // Then add the foreign key with SET NULL
                $table->foreign('account_id')
                    ->references('id')->on('accounts')
                    ->onDelete('set null');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('orders', 'seller_id')) {
            $this->dropForeignIfExists('orders', 'orders_seller_id_foreign', 'seller_id');

            Schema::table('orders', function (Blueprint $table) {
                // Make the column NOT NULL again
                $table->unsignedBigInteger('seller_id')->nullable(false)->change();
            });

            Schema::table('orders', function (Blueprint $table) {
                // Restore the original foreign key without onDelete('set null')
                $table->foreign('seller_id')
                    ->references('id')->on('users');
            });
        }

        if (Schema::hasColumn('orders', 'account_id')) {
            $this->dropForeignIfExists('orders', 'orders_account_id_foreign', 'account_id');

            Schema::table('orders', function (Blueprint $table) {
                // Make the column NOT NULL again
                $table->unsignedBigInteger('account_id')->nullable(false)->change();
            });

            Schema::table('orders', function (Blueprint $table) {
                // Restore the original foreign key without onDelete('set null')
                $table->foreign('account_id')
                    ->references('id')->on('accounts');
            });
        }
    }

    protected function hasForeignKey(string $table, string $foreignKey): bool
    {
        $result = DB::selectOne("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = ? 
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table, $foreignKey]);

        return $result !== null;
    }

    protected function dropForeignIfExists(string $table, string $foreignKey, string $column): void
    {
        if ($this->hasForeignKey($table, $foreignKey)) {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropForeign([$column]);
            });
        }
    }
}
