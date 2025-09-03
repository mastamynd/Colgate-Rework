<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('map_data', function (Blueprint $table) {
            $table->string('data_table')->nullable()->after('data');
            $table->json('data_columns')->nullable()->after('data_table');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('map_data', function (Blueprint $table) {
            $table->dropColumn(['data_table', 'data_columns']);
        });
    }
};
