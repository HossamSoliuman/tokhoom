<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The custom domain's certificate: when it runs out, and the last attempt
     * to issue or renew it with how many failed in a row and why.
     */
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->timestamp('domain_certificate_expires_at')->nullable()->after('domain_checked_at');
            $table->timestamp('domain_certificate_attempted_at')->nullable()->after('domain_certificate_expires_at');
            $table->unsignedTinyInteger('domain_certificate_failures')->default(0)->after('domain_certificate_attempted_at');
            $table->string('domain_certificate_error')->nullable()->after('domain_certificate_failures');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn([
                'domain_certificate_expires_at',
                'domain_certificate_attempted_at',
                'domain_certificate_failures',
                'domain_certificate_error',
            ]);
        });
    }
};
