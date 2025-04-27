<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveLocationReferenceAndTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Hapus foreign key constraints terlebih dahulu
        if (Schema::hasColumn('stock_transactions', 'location_id')) {
            Schema::table('stock_transactions', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        if (Schema::hasColumn('stock_adjustment_lines', 'location_id')) {
            Schema::table('stock_adjustment_lines', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        if (Schema::hasColumn('goods_receipt_lines', 'location_id')) {
            Schema::table('goods_receipt_lines', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        if (Schema::hasColumn('delivery_line', 'location_id') || Schema::hasColumn('DeliveryLine', 'location_id')) {
            $tableName = Schema::hasTable('delivery_line') ? 'delivery_line' : 'DeliveryLine';
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        if (Schema::hasColumn('cycle_countings', 'location_id')) {
            Schema::table('cycle_countings', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        if (Schema::hasColumn('production_consumption', 'location_id') || Schema::hasColumn('ProductionConsumption', 'location_id')) {
            $tableName = Schema::hasTable('production_consumption') ? 'production_consumption' : 'ProductionConsumption';
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        // Hapus tabel warehouse_locations dan warehouse_zones
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Tidak perlu membuat kembali tabel-tabel yang dihapus
        // Method ini tetap disediakan karena required oleh migration class
    }
}