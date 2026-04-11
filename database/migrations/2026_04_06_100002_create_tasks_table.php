<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code')->unique();
            $table->string('title');
            $table->string('location_description')->nullable();
            $table->foreignId('directorate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('assignee_name')->nullable();
            $table->string('status', 32);
            $table->string('priority', 32);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedSmallInteger('resolve_duration_minutes')->nullable();
            $table->unsignedSmallInteger('photo_count')->default(0);
            $table->timestamps();

            $table->index(['directorate_id', 'status']);
            $table->index(['activity_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
