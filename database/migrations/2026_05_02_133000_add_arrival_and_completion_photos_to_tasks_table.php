<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->json('arrival_photos')->nullable()->after('close_proof_photos');
            $table->json('completion_photos')->nullable()->after('arrival_photos');
        });

        // Geriye dönük uyumluluk: mevcut kanıt fotoğraflarını görev sonrası alanına taşı.
        DB::table('tasks')
            ->whereNotNull('close_proof_photos')
            ->whereNull('completion_photos')
            ->update([
                'completion_photos' => DB::raw('close_proof_photos'),
            ]);
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['arrival_photos', 'completion_photos']);
        });
    }
};
