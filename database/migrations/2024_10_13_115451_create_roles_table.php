<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // Role name, e.g., 'admin', 'editor', etc.
                $table->text('capabilities')->nullable(); // Store capabilities as a serialized or JSON array
                $table->timestamps();
            });
            // Insert default roles after table creation
            DB::table('roles')->insert([
                ['name' => 'admin' ],
                ['name' => 'sales' ],
                ['name' => 'accountatnt'],
            ]);
        }

        // Admin user attachment runs after role_user pivot exists (later migration).
    }

    public function down()
    {
        Schema::dropIfExists('roles');
    }
};
