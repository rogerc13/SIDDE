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
        // Revert the previous workaround (generated column + unique on code_active)
        // and return to a standard UNIQUE(code) constraint.

        Schema::table('courses', function (Blueprint $table) {
            // If the previous migration ran, this index exists.
            $table->dropUnique(['code_active']);
        });

        // Drop the generated column using raw SQL for MySQL/MariaDB compatibility.
        DB::statement('ALTER TABLE `courses` DROP COLUMN `code_active`');

        Schema::table('courses', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Re-apply the workaround if we ever roll back this revert.
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        DB::statement(
            "ALTER TABLE `courses` " .
            "ADD COLUMN `code_active` VARCHAR(255) " .
            "GENERATED ALWAYS AS (IF(`deleted_at` IS NULL, `code`, NULL)) STORED"
        );

        Schema::table('courses', function (Blueprint $table) {
            $table->unique('code_active');
        });
    }
};
