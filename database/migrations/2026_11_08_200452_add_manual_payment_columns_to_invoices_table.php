<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payments a platform admin records by hand, such as a bank transfer.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('recorded_by')->nullable()->after('last_attempted_at')->constrained('users')->nullOnDelete();
            $table->string('payment_note')->nullable()->after('recorded_by');
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['paid_at']);
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropColumn('payment_note');
        });
    }
};
