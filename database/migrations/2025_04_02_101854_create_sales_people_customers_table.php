<?php

use App\Models\Customer;
use App\Models\SalesPerson;
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
        Schema::create('sales_people_customers', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->foreignIdFor(SalesPerson::class)->constrained('sales_people')->cascadeOnDelete();
            $table->foreignIdFor(Customer::class)->constrained('customers')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_people_customers');
    }
};
