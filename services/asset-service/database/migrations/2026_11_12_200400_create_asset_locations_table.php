<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            // Localisation administrative
            $table->string('region')->nullable();
            $table->string('prefecture')->nullable();
            $table->string('commune')->nullable();
            $table->string('adresse')->nullable();
            $table->string('site')->nullable(); // site ou bâtiment
            // Localisation GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->boolean('is_current')->default(true);
            $table->unsignedBigInteger('created_by')->nullable(); // user id (Auth Service)
            $table->timestamps();

            $table->index(['asset_id', 'is_current']);
            $table->index(['region', 'prefecture']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_locations');
    }
};
