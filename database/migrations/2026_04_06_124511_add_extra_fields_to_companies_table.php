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
              $table->string('tagline')->nullable()->after('company_name');
            $table->string('company_size')->nullable()->after('tagline');
            $table->string('headquarters')->nullable()->after('company_size');
            $table->string('company_email')->nullable()->after('headquarters');

            $table->text('compliance_certifications')->nullable()->after('company_email');
            $table->json('culture_values')->nullable()->after('compliance_certifications');
            $table->json('benefits_perks')->nullable()->after('culture_values');

            // Social links (JSON is best)
            $table->json('social_links')->nullable()->after('benefits_perks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
             $table->dropColumn([
                'tagline',
                'company_size',
                'headquarters',
                'company_email',
                'compliance_certifications',
                'culture_values',
                'benefits_perks',
                'social_links'
            ]);
        });
    }
};
