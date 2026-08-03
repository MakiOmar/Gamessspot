<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'archived' to the reports status enum.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE reports MODIFY COLUMN status ENUM('has_problem', 'needs_return', 'solved', 'archived') NOT NULL");
    }

    /**
     * Revert enum to previous values (remove 'archived').
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE reports MODIFY COLUMN status ENUM('has_problem', 'needs_return', 'solved') NOT NULL");
    }
};
