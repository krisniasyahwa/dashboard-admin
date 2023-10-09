<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RenameImageFieldToImagePathToProductCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_categories', function (Blueprint $table) {
            DB::statement('ALTER TABLE product_categories CHANGE image image_path VARCHAR(255)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // To reverse the migration, you can create another statement to change it back
        Schema::table('product_categories', function (Blueprint $table) {
            DB::statement('ALTER TABLE product_categories CHANGE image_path image VARCHAR(255)');
        });
    }
}
