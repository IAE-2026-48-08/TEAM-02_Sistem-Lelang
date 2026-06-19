<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_users', function (Blueprint $table) {
            $table->id();
            $table->string('sso_subject')->unique();
            $table->string('email')->unique();
            $table->string('full_name')->nullable();
            $table->string('nim')->nullable();
            $table->string('token_type');
            $table->json('sso_payload')->nullable();
            $table->foreignId('local_role_id')
                  ->constrained('local_roles')
                  ->restrictOnDelete();
            $table->text('last_jwt_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_users');
    }
};