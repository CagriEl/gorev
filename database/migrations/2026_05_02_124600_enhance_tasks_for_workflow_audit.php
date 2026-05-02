<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->text('solution_note')->nullable()->after('description');
            $table->json('close_proof_photos')->nullable()->after('solution_note');
            $table->timestamp('field_completed_at')->nullable()->after('resolved_at');
            $table->decimal('field_completion_distance_m', 8, 2)->nullable()->after('field_completed_at');
            $table->decimal('field_completion_latitude', 10, 7)->nullable()->after('field_completion_distance_m');
            $table->decimal('field_completion_longitude', 10, 7)->nullable()->after('field_completion_latitude');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'solution_note',
                'close_proof_photos',
                'field_completed_at',
                'field_completion_distance_m',
                'field_completion_latitude',
                'field_completion_longitude',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
