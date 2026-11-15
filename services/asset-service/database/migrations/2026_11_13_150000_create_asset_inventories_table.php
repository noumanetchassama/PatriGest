<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            // en_cours | clos
            $table->string('status', 20)->default('en_cours')->index();
            $table->unsignedBigInteger('started_by')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            // en_attente | trouve | absent | non_concerne
            $table->string('status', 20)->default('en_attente')->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['asset_inventory_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_inventory_items');
        Schema::dropIfExists('asset_inventories');
    }
};
