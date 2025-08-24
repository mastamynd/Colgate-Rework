<?php

use App\Models\Route;
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
        Schema::create('sales_person_routes', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->foreignIdFor(Route::class)->constrained('routes')->cascadeOnDelete();
            $table->foreignIdFor(SalesPerson::class)->constrained('sales_people')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_person_routes');
    }
};
