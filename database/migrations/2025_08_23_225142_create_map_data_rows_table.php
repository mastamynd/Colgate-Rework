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
		Schema::create('map_data_rows', function (Blueprint $table) {
			$table->uuid('id')->primary();
			$table->foreignUuid('map_data_id')->constrained('map_data');
			$table->json('data');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('map_data_rows');
	}
};
