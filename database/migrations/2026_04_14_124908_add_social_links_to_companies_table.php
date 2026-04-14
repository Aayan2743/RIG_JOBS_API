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
        Schema::table('companies', function (Blueprint $table) {
              $table->string('slug')->unique()->nullable();
                $table->string('linkedin')->nullable();
                $table->string('twitter')->nullable();
                $table->string('youtube')->nullable();
                $table->string('facebook')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['slug', 'linkedin', 'twitter', 'youtube', 'facebook']);
        });
    }
};
