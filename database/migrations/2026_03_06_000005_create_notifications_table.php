<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->uuid('ticket_id')->nullable();
                $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            } else {
                $table->foreignId('ticket_id')->nullable()->constrained('tickets')->onDelete('cascade');
            }

            $table->string('type', 50);
            $table->string('title', 200);
            $table->text('content')->nullable();
            $table->boolean('read')->default(false);
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->jsonb('metadata')->nullable();
            } else {
                $table->json('metadata')->nullable();
            }
            $table->timestamps();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
