<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classifier_training_samples', function (Blueprint $table): void {
            $table->id();
            $table->text('sikayet_metni');
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('mudurluk_slug', 64);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classifier_training_samples');
    }
};
