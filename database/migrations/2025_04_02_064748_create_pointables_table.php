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
        Schema::create('pointables', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("pointable_id");
            $table->string("pointable_type");
            $table->geometry("location", "point");
            $table->timestamps();

            $table->index(["pointable_id", "pointable_type"]);
            
            // Only create spatial index if not using SQLite
            if (config('database.default') !== 'sqlite') {
                $table->spatialIndex('location');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pointables');
    }
};
