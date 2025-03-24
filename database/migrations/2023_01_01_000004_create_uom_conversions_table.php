<?php
// database/migrations/2023_01_01_000004_create_uom_conversions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id('conversion_id');
            $table->unsignedBigInteger('from_uom_id');
            $table->unsignedBigInteger('to_uom_id');
            $table->float('conversion_factor');
            $table->timestamps();
            
            $table->foreign('from_uom_id')->references('uom_id')->on('unit_of_measures');
            $table->foreign('to_uom_id')->references('uom_id')->on('unit_of_measures');
        });
    }

    public function down()
    {
        Schema::dropIfExists('uom_conversions');
    }
};