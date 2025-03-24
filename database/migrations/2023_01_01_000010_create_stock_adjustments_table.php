<?php
// database/migrations/2023_01_01_000010_create_stock_adjustments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id('adjustment_id');
            $table->date('adjustment_date');
            $table->text('adjustment_reason')->nullable();
            $table->string('status', 50);
            $table->string('reference_document', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_adjustments');
    }
};