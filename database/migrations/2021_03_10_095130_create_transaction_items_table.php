<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //THis is snippet to create table transaction_items
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            
            $table->bigInteger('users_id');
            $table->bigInteger('products_id');
            $table->bigInteger('transactions_id');
            $table->bigInteger('quantity');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_items');
    }
}
