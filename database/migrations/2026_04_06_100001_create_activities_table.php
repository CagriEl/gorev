<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

            $table->index(['directorate_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
