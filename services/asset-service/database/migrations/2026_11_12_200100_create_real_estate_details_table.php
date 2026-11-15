<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_estate_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->unique()->constrained('assets')->cascadeOnDelete();
            $table->string('bien_type')->nullable(); // terrain | batiment | bureau | logement | infrastructure
            $table->decimal('surface_terrain', 12, 2)->nullable(); // m²
            $table->decimal('surface_batie', 12, 2)->nullable(); // m²
            $table->unsignedSmallInteger('nb_pieces')->nullable();
            $table->unsignedSmallInteger('annee_construction')->nullable();
            $table->string('reference_cadastrale')->nullable();
            $table->string('occupant_type')->nullable(); // administration | direction | service | location
            $table->string('titre_possession')->nullable(); // titre foncier, bail, etc.
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_details');
    }
};
