<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
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
        // Create the admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'phone' => '12345678910',
            'password' => Hash::make('12345678'), // Replace with a strong password
        ]);

        // Find or create the admin role (assuming 'admin' role exists in the roles table)
        $adminRole = Role::where('name', 'admin')->firstOrFail();

        // Attach the admin role to the user
        $admin->roles()->attach($adminRole);
    }

    public function down()
    {
        Schema::dropIfExists('roles');
    }
};
