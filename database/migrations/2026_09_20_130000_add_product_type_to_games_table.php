<?php

use App\Models\Game;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('games', 'product_type')) {
            Schema::table('games', function (Blueprint $table) {
                $table->string('product_type', 32)->default(Game::TYPE_GAME)->after('code');
                $table->index('product_type');
            });
        }

        // One-time backfill for PS Plus-style titles
        DB::table('games')
            ->where(function ($query) {
                $query->where('title', 'like', '%ps plus%')
                    ->orWhere('title', 'like', '%playstation plus%')
                    ->orWhere('title', 'like', '%ps+%')
                    ->orWhere('title', 'like', '%ps +%');
            })
            ->update(['product_type' => Game::TYPE_SUBSCRIPTION]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('games', 'product_type')) {
            Schema::table('games', function (Blueprint $table) {
                $table->dropIndex(['product_type']);
                $table->dropColumn('product_type');
            });
        }
    }
};
