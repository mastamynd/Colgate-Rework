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
            // Drop existing string columns
            $table->dropColumn('county');
            $table->dropColumn('constituency');
            $table->dropColumn('ward');
            
            // Add foreign key columns
            $table->unsignedBigInteger('county_id')->nullable()->after('address');
            $table->unsignedBigInteger('constituency_id')->nullable()->after('county_id');
            $table->unsignedBigInteger('ward_id')->nullable()->after('constituency_id');
            
            // Add foreign key constraints
            $table->foreign('county_id')->references('id')->on('boundaries')->onDelete('set null');
            $table->foreign('constituency_id')->references('id')->on('boundaries')->onDelete('set null');
            $table->foreign('ward_id')->references('id')->on('boundaries')->onDelete('set null');
            
            // Add indexes for performance
            $table->index(['county_id']);
            $table->index(['constituency_id']);
            $table->index(['ward_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Drop foreign key constraints and indexes
            $table->dropForeign(['county_id']);
            $table->dropForeign(['constituency_id']);
            $table->dropForeign(['ward_id']);
            $table->dropIndex(['county_id']);
            $table->dropIndex(['constituency_id']);
            $table->dropIndex(['ward_id']);
            
            // Drop ID columns
            $table->dropColumn(['county_id', 'constituency_id', 'ward_id']);
            
            // Restore string columns
            $table->string('country')->nullable()->after('address');
            $table->string('constituency')->nullable()->after('country');
            $table->string('ward')->nullable()->after('constituency');
        });
    }
};
