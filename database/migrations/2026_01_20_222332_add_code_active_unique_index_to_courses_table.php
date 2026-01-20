<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        // Use raw SQL for broad compatibility across MySQL + MariaDB in XAMPP environments.
        DB::statement(
            "ALTER TABLE `courses` " .
            "ADD COLUMN `code_active` VARCHAR(255) " .
            "GENERATED ALWAYS AS (IF(`deleted_at` IS NULL, `code`, NULL)) STORED"
        );

        Schema::table('courses', function (Blueprint $table) {
            $table->unique('code_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique(['code_active']);
        });

        DB::statement('ALTER TABLE `courses` DROP COLUMN `code_active`');

        Schema::table('courses', function (Blueprint $table) {
            $table->unique('code');
        });
    }
};
