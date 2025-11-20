<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! config('settings.teams')) {
            return;
        }

        $tableName = config('settings.table');
        $teamForeignKey = config('settings.team_foreign_key');

        Schema::table($tableName, function (Blueprint $table) use ($teamForeignKey) {
            if (!Schema::hasColumn($tableName, $teamForeignKey)) {
                $table->unsignedBigInteger($teamForeignKey)->nullable()->after('id');
                $table->index($teamForeignKey, 'settings_team_id_index');
            }

            $table->dropUnique('settings_key_unique');

            $table->unique([
                'key',
                $teamForeignKey,
            ], 'settings_key_team_id_unique');
        });
    }
};
