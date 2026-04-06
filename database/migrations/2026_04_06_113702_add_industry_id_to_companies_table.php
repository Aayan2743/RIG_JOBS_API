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

            // ✅ Add industry_id column with FK
            $table->foreignId('industry_id')
                  ->nullable()
                  ->after('website') // adjust if needed
                  ->constrained('industries')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
              $table->dropForeign(['industry_id']);
            $table->dropColumn('industry_id');
        });
    }
};
