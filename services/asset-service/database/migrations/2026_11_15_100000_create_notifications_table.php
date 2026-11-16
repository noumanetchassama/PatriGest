<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            // ID de l'utilisateur PatriGest (Auth Service) — pas de FK inter-services
            $table->unsignedBigInteger('user_id')->index();
            // ex. soumission_approuvee, affectation_rejetee, transfert_approuve
            $table->string('type', 50)->index();
            $table->string('title');
            $table->text('message')->nullable();
            // Lien interne PatriGest (ex. /patrimoine/3) — nullable
            $table->string('link')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
