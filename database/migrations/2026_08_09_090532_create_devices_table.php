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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();        // Nama PC, misal LAB-A-PC01
            $table->boolean('is_flagged')->default(false);
            $table->json('flagged_activity')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('lokasi')->nullable();

            $table->float('cpu_percent')->default(0);
            $table->float('ram_percent')->default(0);

            $table->json('processes')->nullable();   // Daftar aplikasi aktif
            $table->json('windows')->nullable();      // Daftar window terbuka

            // Command yang akan dieksekusi oleh agent Python saat ping berikutnya
            $table->string('pending_action')->nullable();   // shutdown | restart | kill_process
            $table->string('pending_target')->nullable();   // nama proses / PID (untuk kill_process)

            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('screen_watch_until')->nullable();

            // Kapan terakhir kali agent berhasil mengupload screenshot (untuk cache-busting).
            $table->timestamp('screenshot_updated_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
