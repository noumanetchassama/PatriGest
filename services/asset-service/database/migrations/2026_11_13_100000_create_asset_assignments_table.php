<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            // IDs du Auth Service (pas de FK inter-bases)
            $table->unsignedBigInteger('custodian_user_id');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            // en_attente | approuvee | rejetee | annulee
            $table->string('status', 20)->default('en_attente')->index();
            $table->text('motif')->nullable();
            $table->text('decision_motif')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'status']);
            $table->index('custodian_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
