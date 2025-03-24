<?php
// database/migrations/2023_01_01_000006_create_warehouse_zones_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('warehouse_zones', function (Blueprint $table) {
            $table->id('zone_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('name', 100);
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses');
        });
    }

    public function down()
    {
        Schema::dropIfExists('warehouse_zones');
    }
};