<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMediaDominio extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table ('media', function (Blueprint $table) {
            $table->bigInteger ('dominio')->after ('idUser')->nullable ();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table ('media', function (Blueprint $table) {
            $table->dropColumn ('dominio');
        });
    }
}
