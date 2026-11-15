<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            // preventive | corrective
            $table->string('type', 20);
            $table->string('description');
            $table->date('scheduled_date')->nullable();
            $table->date('realized_date')->nullable();
            $table->decimal('cost', 14, 2)->nullable();
            // planifiee | en_cours | terminee | annulee
            $table->string('status', 20)->default('planifiee')->index();
            $table->string('provider')->nullable();
            $table->text('notes')->nullable();
            // statut du bien avant le passage en maintenance (pour restauration)
            $table->string('previous_asset_status', 20)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenances');
    }
};
