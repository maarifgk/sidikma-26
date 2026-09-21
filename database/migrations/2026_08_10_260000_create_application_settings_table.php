<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('owner_name');
            $table->string('phone', 30)->nullable();
            $table->string('short_title');
            $table->string('application_name');
            $table->string('logo_path')->nullable();
            $table->string('copyright_text')->nullable();
            $table->string('version', 50)->nullable();
            $table->text('whatsapp_token')->nullable();
            $table->text('server_key')->nullable();
            $table->text('client_key')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        DB::table('application_settings')->insert([
            'owner_name' => "L.P. Ma'arif NU PCNU Gunungkidul",
            'phone' => '085122016369',
            'short_title' => 'SiDIKMa-GK',
            'application_name' => "Sistem Data dan Informasi Kelembagaan Ma'arif NU Gunungkidul",
            'copyright_text' => 'Copy Right © SiDIKMa-GK',
            'version' => '1.0.0',
            'address' => 'Jl. Tentara Pelajar, Trimulyo I, Kepek, Wonosari, Gunungkidul, Yogyakarta 55813',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('application_settings');
    }
};
