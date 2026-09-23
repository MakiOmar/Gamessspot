<?php

use App\Models\Game;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('games', 'is_featured')) {
            Schema::table('games', function (Blueprint $table) {
                $table->boolean('is_featured')->default(false)->after('product_type');
                $table->index('is_featured');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('games', 'is_featured')) {
            Schema::table('games', function (Blueprint $table) {
                $table->dropIndex(['is_featured']);
                $table->dropColumn('is_featured');
            });
        }
    }
};
