<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 20)->default('text'); // text | live
            $table->string('difficulty', 20)->default('intermediate'); // beginner | intermediate | advanced
            $table->string('target_role')->nullable();
            $table->string('resume_filename')->nullable();
            $table->json('skills')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->longText('feedback')->nullable();
            $table->json('strengths')->nullable();
            $table->json('improvements')->nullable();
            $table->string('evaluation_provider', 40)->nullable();
            $table->unsignedInteger('movement_alerts')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('status', 20)->default('in_progress'); // in_progress | completed
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
