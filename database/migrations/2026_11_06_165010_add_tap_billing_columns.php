<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Workspaces that already paid keep the next charge date they had: the start of next month.
     */
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->date('paid_until')->nullable()->after('trial_ends_at');
            $table->string('tap_customer_id')->nullable()->after('card_expiry');
            $table->string('tap_card_id')->nullable()->after('tap_customer_id');
            $table->string('tap_payment_agreement_id')->nullable()->after('tap_card_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('tap_charge_id')->nullable()->unique()->after('paid_at');
            $table->unsignedTinyInteger('payment_attempts')->default(0)->after('tap_charge_id');
            $table->timestamp('last_attempted_at')->nullable()->after('payment_attempts');
        });

        DB::table('workspaces')
            ->whereIn('id', DB::table('invoices')->whereNotNull('paid_at')->select('workspace_id'))
            ->update(['paid_until' => now()->addMonthNoOverflow()->startOfMonth()->toDateString()]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['tap_charge_id']);
            $table->dropColumn(['tap_charge_id', 'payment_attempts', 'last_attempted_at']);
        });

        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn(['paid_until', 'tap_customer_id', 'tap_card_id', 'tap_payment_agreement_id']);
        });
    }
};
