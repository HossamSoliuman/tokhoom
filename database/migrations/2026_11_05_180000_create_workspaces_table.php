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
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('kind');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city');
            $table->string('cr_number')->nullable();
            $table->string('fal_number');
            $table->date('license_expires_on');

            $table->string('approval_status')->default('pending')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->text('rejection_message')->nullable();
            $table->timestamp('suspended_at')->nullable();

            $table->string('brand_color', 7)->default('#145A55');
            $table->text('about')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('phone')->nullable();
            $table->string('x_handle')->nullable();
            $table->string('instagram')->nullable();
            $table->string('snapchat')->nullable();
            $table->string('tiktok')->nullable();

            $table->string('plan')->default('office');
            $table->unsignedSmallInteger('seats')->default(5);
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_expiry', 7)->nullable();

            $table->string('custom_domain')->nullable()->unique();
            $table->string('domain_status')->nullable();
            $table->timestamp('domain_checked_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
