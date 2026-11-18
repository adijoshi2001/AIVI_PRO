<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->unsignedTinyInteger('ats_score')->nullable()->after('skills');
            $table->json('ats_analysis')->nullable()->after('ats_score');
            $table->json('weak_topics')->nullable()->after('improvements');
            $table->json('career_roadmap')->nullable()->after('weak_topics');
            $table->timestamp('report_emailed_at')->nullable()->after('completed_at');
        });

        Schema::table('interview_questions', function (Blueprint $table) {
            $table->string('topic', 100)->nullable()->after('order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropColumn([
                'ats_score',
                'ats_analysis',
                'weak_topics',
                'career_roadmap',
                'report_emailed_at',
            ]);
        });

        Schema::table('interview_questions', function (Blueprint $table) {
            $table->dropColumn('topic');
        });
    }
};
