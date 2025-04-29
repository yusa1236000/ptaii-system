<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddYieldFieldsToBomLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bom_lines', function (Blueprint $table) {
            $table->boolean('is_yield_based')->default(false)->after('is_critical');
            $table->float('yield_ratio')->nullable()->after('is_yield_based');
            $table->float('shrinkage_factor')->nullable()->after('yield_ratio');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bom_lines', function (Blueprint $table) {
            $table->dropColumn(['is_yield_based', 'yield_ratio', 'shrinkage_factor']);
        });
    }
}