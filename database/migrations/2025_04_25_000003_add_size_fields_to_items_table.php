<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSizeFieldsToItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            
            // Add size related fields
            $table->decimal('length', 15, 4)->nullable()->after('sale_price');
            $table->decimal('width', 15, 4)->nullable()->after('length');
            $table->decimal('thickness', 15, 4)->nullable()->after('width');
            $table->decimal('weight', 15, 4)->nullable()->after('thickness');

            // Document upload field
            $table->string('document_path')->nullable()->after('weight');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'length',
                'width',
                'thickness',
                'weight',
                'document_path',
            ]);
        });
    }
}
