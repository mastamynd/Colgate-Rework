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
			Schema::create('customers', function (Blueprint $table) {
				$table->uuid("id")->primary();
				$table->string("name");
				$table->string("phone")->nullable();
				$table->string("email")->nullable();
				$table->string("address")->nullable();
				$table->decimal("latitude", 10, 8)->nullable();
				$table->decimal("longitude", 11, 8)->nullable();
				$table->string("address")->nullable();
				$table->double("average_ims", 10, 6)->nullable();
				$table->timestamps();
			});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
