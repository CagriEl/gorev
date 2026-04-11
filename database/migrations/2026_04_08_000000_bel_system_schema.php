<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('manager_name')->nullable();
            $table->string('manager_phone')->nullable();
            $table->string('foreman_name')->nullable();
            $table->string('foreman_phone')->nullable();
            $table->unsignedInteger('staff_count')->nullable();
            $table->timestamps();
        });

        if (Schema::hasColumn('users', 'directorate_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('directorate_id');
            });
        }

        Schema::dropIfExists('tasks');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('directorate_user');
        Schema::dropIfExists('directorates');

        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('staff')->after('password');
            $table->foreignId('department_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_code')->unique();
            $table->string('title');
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('priority', 32);
            $table->string('status', 32);
            $table->text('description')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedSmallInteger('resolve_minutes')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('mudur')->after('password');
        });

        Schema::dropIfExists('departments');

        Schema::create('directorates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('manager_name')->nullable();
            $table->string('manager_phone', 32)->nullable();
            $table->string('foreman_name')->nullable();
            $table->string('foreman_phone', 32)->nullable();
            $table->unsignedInteger('active_staff_count')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('directorate_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->string('family_name');
            $table->string('category');
            $table->text('scope')->nullable();
            $table->string('measure_unit')->nullable();
            $table->text('kpi_sla')->nullable();
            $table->string('data_source')->nullable();
            $table->timestamps();
        });

        Schema::create('directorate_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('directorate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['directorate_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('directorate_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });

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
        });
    }
};
