<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user history notes plus the manage-user-history capability (granted to admin by default).
 */
return new class extends Migration
{
    private const ABILITY = 'manage-user-history';

    public function up(): void
    {
        Schema::create('user_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->timestamps();
            $table->softDeletes();

            $table->index(array('user_id', 'created_at'));
        });

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $capabilities = $admin->capabilities ?? array();
            if (! in_array(self::ABILITY, $capabilities, true)) {
                $admin->capabilities = array_values(array_merge($capabilities, array(self::ABILITY)));
                $admin->save();
            }
        }
    }

    public function down(): void
    {
        Role::query()->each(function (Role $role) {
            $capabilities = $role->capabilities ?? array();
            if (in_array(self::ABILITY, $capabilities, true)) {
                $role->capabilities = array_values(array_diff($capabilities, array(self::ABILITY)));
                $role->save();
            }
        });

        Schema::dropIfExists('user_notes');
    }
};
