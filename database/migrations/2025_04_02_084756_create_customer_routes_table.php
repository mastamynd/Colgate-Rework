<?php

use App\Models\Customer;
use App\Models\Route;
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
        Schema::create('customer_routes', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->foreignIdFor(Route::class)->constrained('routes')->cascadeOnDelete();
            $table->foreignIdFor(Customer::class)->constrained('customers')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_routes');
    }
};
