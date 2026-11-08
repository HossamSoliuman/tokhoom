<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Listings a platform admin took off the public site, for example after a
     * complaint about the ad. The office cannot put them back on its own.
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->timestamp('removed_at')->nullable()->after('price_changed_at');
            $table->string('removal_reason')->nullable()->after('removed_at');
            $table->index('ad_license_number');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['ad_license_number']);
            $table->dropColumn(['removed_at', 'removal_reason']);
        });
    }
};
