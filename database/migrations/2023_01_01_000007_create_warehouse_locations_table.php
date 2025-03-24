<?php
// database/migrations/2023_01_01_000007_create_warehouse_locations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id('location_id');
            $table->unsignedBigInteger('zone_id');
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('zone_id')->references('zone_id')->on('warehouse_zones');
        });
    }

    public function down()
    {
        Schema::dropIfExists('warehouse_locations');
    }
};