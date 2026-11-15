<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // PG-IMM-0001, PG-MOB-0001, PG-VEH-0001
            $table->uuid('qr_token')->unique();
            $table->string('type'); // immobilier | mobilier | vehicule
            $table->string('designation');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // sous-catégorie métier
            $table->date('acquisition_date')->nullable();
            $table->string('origin')->nullable(); // origine / source d'acquisition
            $table->decimal('value', 15, 2)->nullable(); // montant en FCFA
            $table->string('value_type')->default('acquisition'); // acquisition | actuelle | estimee
            $table->string('status')->default('brouillon'); // brouillon | actif | maintenance | hors_service | reforme
            $table->string('etat')->nullable(); // excellent | bon | moyen | mauvais
            // Références vers Auth Service (pas de FK inter-bases)
            $table->unsignedBigInteger('direction_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('custodian_user_id')->nullable(); // détenteur actuel
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status']);
            $table->index(['category']);
            $table->index(['direction_id', 'service_id']);
            $table->index('designation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
