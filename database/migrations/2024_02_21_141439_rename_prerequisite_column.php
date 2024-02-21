<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prerequisites',function(Blueprint $table){
            //$table->renameColumn('course_id','course_code');
            //DB::statement("ALTER TABLE `prerequisites` DROP INDEX `prerequisites_course_id_foreign`"); 
            //DB::statement("ALTER TABLE prerequisites add constraint foreign key course_code VARCHAR(255) NOT NULL");
            $table->dropForeign('prerequisites_course_id_foreign');
            $table->dropColumn('course_id');
            /* $table->foreignId('course_code')->constrained(
                table: 'courses', indexName: 'prerequisites_course_code'
            ); */
            //Schema::create('')
            $table->string('prerequisite_code');
            $table->foreign('prerequisite_code')->references('code')->on('courses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
