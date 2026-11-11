<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the owner accepted the terms of use and privacy policy while
     * creating the workspace. Empty for workspaces created before signup asked.
     */
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn('terms_accepted_at');
        });
    }
};
