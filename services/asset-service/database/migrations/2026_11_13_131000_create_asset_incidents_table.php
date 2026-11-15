<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            // dommage | perte | accident | vol
            $table->string('type', 20);
            $table->text('description');
            $table->timestamp('occurred_at');
            // mineure | majeure | critique
            $table->string('severity', 20)->default('mineure');
            // ouvert | en_cours | resolu | clos
            $table->string('status', 20)->default('ouvert')->index();
            $table->decimal('estimated_cost', 14, 2)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_incidents');
    }
};
