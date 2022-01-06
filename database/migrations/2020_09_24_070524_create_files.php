<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename ('files','files_old');
        Schema::create ('files', function (Blueprint $table) {
            $table->bigIncrements ('id');
            $table->bigInteger ('dominio');
            $table->bigInteger ('idAuthor');
            $table->bigInteger ('idTarget');
            $table->char ('idMedia',255);
            $table->char ('typeTarget')->default ('class');
            $table->char ('title');
            $table->longText ('text');
            $table->timestamps ();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
        Schema::rename('files_old','files');
    }
}
