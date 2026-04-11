<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('directorates');
    }
};
