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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users');
            $table->string('name');
            $table->string('mobile', 20);
            $table->string('type');
            $table->string('source');
            $table->timestamps();

            $table->index(['workspace_id', 'mobile']);
            $table->index(['workspace_id', 'agent_id']);
        });

        Schema::create('client_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('purpose');
            $table->json('types');
            $table->json('districts');
            $table->unsignedBigInteger('budget_min')->nullable();
            $table->unsignedBigInteger('budget_max')->nullable();
            $table->timestamps();
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->text('body');
            $table->boolean('is_shared')->default(false);
            $table->timestamps();
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('title');
            $table->string('channel')->default('call');
            $table->timestamp('due_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'due_at']);
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('mobile', 20);
            $table->text('message')->nullable();
            $table->string('source');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'created_at']);
        });

        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_id')->constrained('users');
            $table->string('stage')->default('new');
            $table->string('furthest_stage')->default('new');
            $table->unsignedBigInteger('value');
            $table->unsignedBigInteger('final_price')->nullable();
            $table->unsignedBigInteger('commission')->nullable();
            $table->string('lost_reason')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'stage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('client_requests');
        Schema::dropIfExists('clients');
    }
};
