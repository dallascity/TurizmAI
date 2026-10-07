<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rezervasyonu oluşturan sohbet. Boş ise rezervasyon yapay zekâ dışında (örn. seed) oluşturulmuştur.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('chat_session_id')->nullable()->after('unit_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chat_session_id');
        });
    }
};
