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
        Schema::create('areables', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("areable_id");
            $table->string("areable_type");
            $table->geometry("area", "geometry");
            $table->timestamps();

            $table->index(["areable_id", "areable_type"]);
            
            // Only create spatial index if not using SQLite
            if (config('database.default') !== 'sqlite') {
                $table->spatialIndex('area');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('areables');
    }
};
