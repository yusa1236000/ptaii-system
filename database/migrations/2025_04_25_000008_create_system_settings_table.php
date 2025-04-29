<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSystemSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id('setting_id');
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->string('setting_group', 50)->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
        
        // Add default inventory settings
        DB::table('system_settings')->insert([
            [
                'setting_key' => 'inventory_enforce_stock_validation',
                'setting_value' => 'true',
                'setting_group' => 'inventory',
                'description' => 'Whether to enforce stock validation on deliveries',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'inventory_allow_negative_stock',
                'setting_value' => 'false',
                'setting_group' => 'inventory',
                'description' => 'Whether to allow stock to go negative',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_settings');
    }
}