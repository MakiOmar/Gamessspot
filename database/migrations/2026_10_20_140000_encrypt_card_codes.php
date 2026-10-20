<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gift card codes are stored encrypted (APP_KEY); `code_hash` keeps them unique and searchable.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cards', 'code_hash')) {
            Schema::table('cards', function (Blueprint $table) {
                $table->string('code_hash', 64)->nullable()->after('code');
            });
        }

        Schema::table('cards', function (Blueprint $table) {
            $table->dropUnique('cards_code_unique');
        });
        Schema::table('cards', function (Blueprint $table) {
            $table->text('code')->change();
        });

        DB::table('cards')->whereNull('code_hash')->orderBy('id')->chunkById(500, function ($cards) {
            foreach ($cards as $card) {
                $plain = trim((string) $card->code);
                DB::table('cards')->where('id', $card->id)->update([
                    'code' => Crypt::encryptString($plain),
                    'code_hash' => hash_hmac('sha256', $plain, (string) config('app.key')),
                ]);
            }
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->unique('code_hash');
        });
    }

    public function down(): void
    {
        DB::table('cards')->whereNotNull('code_hash')->orderBy('id')->chunkById(500, function ($cards) {
            foreach ($cards as $card) {
                DB::table('cards')->where('id', $card->id)->update([
                    'code' => Crypt::decryptString($card->code),
                ]);
            }
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->dropUnique(['code_hash']);
            $table->dropColumn('code_hash');
        });
        Schema::table('cards', function (Blueprint $table) {
            $table->string('code')->change();
            $table->unique('code');
        });
    }
};
