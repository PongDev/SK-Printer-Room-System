<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('User', function (Blueprint $table) {
            $table->charset='utf8';
            $table->collation='utf8_bin';
            $table->increments('id');
            $table->string('Username',128)->unique();
            $table->string('Password',128);
            $table->integer('Rank');
            $table->string('DisplayName',128);
            $table->json('OrganizationIDList');
            $table->timestamp('Create_Time')->useCurrent();
            $table->timestamp('Last_Login')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('User');
    }
}
