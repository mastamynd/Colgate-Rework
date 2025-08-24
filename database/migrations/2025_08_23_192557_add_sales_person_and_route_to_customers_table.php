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
        Schema::table('customers', function (Blueprint $table) {
            $table->uuid('sales_person_id')->nullable()->after('address');
            $table->uuid('route_id')->nullable()->after('sales_person_id');
            
            $table->foreign('sales_person_id')->references('id')->on('sales_people')->onDelete('set null');
            $table->foreign('route_id')->references('id')->on('routes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['sales_person_id']);
            $table->dropForeign(['route_id']);
            $table->dropColumn(['sales_person_id', 'route_id']);
        });
    }
};
