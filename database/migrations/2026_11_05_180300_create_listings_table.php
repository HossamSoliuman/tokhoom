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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users');
            $table->string('purpose');
            $table->string('type');
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('price')->nullable();
            $table->unsignedInteger('area')->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->unsignedTinyInteger('floors')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('features')->nullable();
            $table->text('description')->nullable();
            $table->string('ad_license_number', 20)->nullable();
            $table->date('ad_license_expires_on')->nullable();
            $table->string('brokerage_contract_number')->nullable();
            $table->string('tour_url')->nullable();
            $table->string('video_url')->nullable();
            $table->string('short_code', 12)->unique();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('price_changed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'agent_id']);
        });

        Schema::create('listing_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['listing_id', 'position']);
        });

        Schema::create('site_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('source')->nullable();
            $table->timestamp('occurred_at');

            $table->index(['workspace_id', 'type', 'occurred_at']);
            $table->index(['listing_id', 'type', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_events');
        Schema::dropIfExists('listing_photos');
        Schema::dropIfExists('listings');
    }
};
