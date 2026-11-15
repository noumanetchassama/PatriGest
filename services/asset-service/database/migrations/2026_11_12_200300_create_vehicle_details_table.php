<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->unique()->constrained('assets')->cascadeOnDelete();
            $table->string('immatriculation')->nullable();
            $table->string('marque')->nullable();
            $table->string('modele')->nullable();
            $table->unsignedSmallInteger('annee')->nullable();
            $table->string('carburant')->nullable(); // essence | diesel | electrique | hybride
            $table->unsignedBigInteger('kilometrage')->nullable();
            $table->string('numero_chassis')->nullable();
            $table->string('numero_moteur')->nullable();
            $table->date('assurance_fin')->nullable();
            $table->date('visite_technique_fin')->nullable();
            $table->date('prochaine_revue')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index('immatriculation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_details');
    }
};
