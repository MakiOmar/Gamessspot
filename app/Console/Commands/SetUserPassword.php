<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:set-password {user_id=1 : The ID of the user} {password=123456789 : The password to set}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set password for a user by ID';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $password = $this->argument('password');

        // Find the user
        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return Command::FAILURE;
        }

        // Update the password
        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password updated successfully for user ID {$userId} ({$user->name}).");
        $this->info("New password: {$password}");

        return Command::SUCCESS;
    }
}
