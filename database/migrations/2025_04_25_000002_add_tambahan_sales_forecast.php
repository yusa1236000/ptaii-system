<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTambahanSalesForecast extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('SalesForecast', function (Blueprint $table) {
            $table->string('forecast_source', 50)->default('System')->after('variance');
            $table->decimal('confidence_level', 5, 2)->default(0.7)->after('forecast_source');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('SalesForecast', function (Blueprint $table) {
            $table->dropColumn('forecast_source');
            $table->dropColumn('confidence_level');
        });
    }
}