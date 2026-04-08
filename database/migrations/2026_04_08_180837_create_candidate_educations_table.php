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
        Schema::create('candidate_educations', function (Blueprint $table) {
            $table->id();
                $table->unsignedBigInteger('candidate_id');

    $table->unsignedBigInteger('education_id');
    $table->unsignedBigInteger('course_id');
    $table->unsignedBigInteger('specialization_id')->nullable();

    $table->string('institution');
    $table->year('start_year')->nullable();
    $table->year('end_year')->nullable();
    $table->string('course_type');
    $table->string('grading_system')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_educations');
    }
};
