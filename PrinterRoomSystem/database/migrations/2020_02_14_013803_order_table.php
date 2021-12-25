<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('Order', function (Blueprint $table) {
            $table->charset='utf8';
            $table->collation='utf8_bin';
            $table->increments('id');
            $table->integer('MemberID');
            $table->integer('OrganizationID');
            $table->integer('BrownPaper');
            $table->integer('WhitePaper');
            $table->integer('ColorPaper');
            $table->integer('WorkType');
            $table->json('Detail');
            $table->timestamp('Time')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('Order');
    }
}
