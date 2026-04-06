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
        Schema::create('rigjobs', function (Blueprint $table) {
            $table->id();
             // Company relation
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Basic Info
            $table->string('title');
            $table->string('location');
            $table->string('job_type'); // full-time, part-time
                $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();
            $table->string('experience_level')->nullable();

            // Salary
            $table->integer('salary_min')->nullable();
            $table->integer('salary_max')->nullable();

            // Description
            $table->text('description');

            // JSON fields
            $table->text('requirements')->nullable();
            $table->json('skills')->nullable();
            $table->json('benefits')->nullable();

            // Status
            $table->enum('status', ['draft', 'published','closed'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rigjobs');
    }
};
