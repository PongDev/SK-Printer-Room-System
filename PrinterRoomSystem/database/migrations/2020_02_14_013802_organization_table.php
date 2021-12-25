<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OrganizationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('Organization', function (Blueprint $table) {
            $table->charset='utf8';
            $table->collation='utf8_bin';
            $table->increments('id');
            $table->string('Name',128)->unique();
            $table->timestamp('Create_Time')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('Organization');
    }
}
