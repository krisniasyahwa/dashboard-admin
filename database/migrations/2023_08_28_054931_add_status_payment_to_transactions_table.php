<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusPaymentToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Add a new column 'status_payment' to the 'transactions' table
            // The default value is set to 'UNPAID'
            $table->string('status_payment')->default('UNPAID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop the 'status_payment' column when rolling back the migration
            $table->dropColumn('status_payment');
        });
    }
}
