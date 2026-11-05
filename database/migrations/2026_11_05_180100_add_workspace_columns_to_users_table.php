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
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 20)->nullable()->after('email');
            $table->string('gender', 10)->nullable()->after('mobile');
            $table->boolean('is_platform_admin')->default(false)->after('password');
            $table->foreignId('current_workspace_id')->nullable()->after('is_platform_admin')
                ->constrained('workspaces')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_workspace_id');
            $table->dropColumn(['mobile', 'gender', 'is_platform_admin']);
        });
    }
};
