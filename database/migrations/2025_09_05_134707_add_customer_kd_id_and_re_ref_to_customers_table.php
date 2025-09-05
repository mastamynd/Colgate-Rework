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
			$this->down();
        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_kd_code')->nullable();
            $table->string('re_ref')->nullable();
            
            // Add foreign key constraint
            $table->foreign('customer_kd_code')->references('code')->on('customer_kds')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['customer_kd_code']);
            $table->dropColumn(['customer_kd_code', 're_ref']);
        });
    }
};
