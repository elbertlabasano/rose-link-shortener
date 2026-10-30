<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(false);
            $table->string('keycloak_issuer', 191)->nullable();
            $table->string('keycloak_subject', 191)->nullable();
            $table->unique(['keycloak_issuer', 'keycloak_subject']);
        });

        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160)->nullable();
            $table->string('code', 40)->unique();
            $table->text('url');
            $table->char('url_hash', 64);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->timestamp('last_clicked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'url_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['keycloak_issuer', 'keycloak_subject']);
            $table->dropColumn(['is_active', 'keycloak_issuer', 'keycloak_subject']);
        });
    }
};
