<?php
// database/migrations/2023_01_01_000002_create_unit_of_measures_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id('uom_id');
            $table->string('name', 50);
            $table->string('symbol', 10);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('unit_of_measures');
    }
};