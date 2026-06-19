<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        DB::table('local_roles')->insert([
            ['name' => 'admin',  'display_name' => 'Administrator',  'description' => 'Akses penuh (M2M service-to-service)',    'created_at' => now(), 'updated_at' => now()],
            ['name' => 'bidder', 'display_name' => 'Peserta Lelang', 'description' => 'Pengguna yang mengikuti lelang via SSO', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('local_roles');
    }
};