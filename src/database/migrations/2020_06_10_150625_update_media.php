<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMedia extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('media', function($table) {
            $table->char('thumbs')->after('type');
            $table->char('med')->after('type');
            $table->char('lit')->after('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('media', function($table) {
            $table->dropColumn('thumbs');
            $table->dropColumn('med');
            $table->dropColumn('lit');
        });
    }
}
