<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_counters', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique(); // IMM | MOB | VEH
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });

        // Initialisation des compteurs
        DB::table('reference_counters')->insert([
            ['type' => 'IMM', 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'MOB', 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'VEH', 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_counters');
    }
};
