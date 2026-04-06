<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->uuid('ticket_id');
                $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            } else {
                $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            }

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->jsonb('metadata')->nullable();
            } else {
                $table->json('metadata')->nullable();
            }
            $table->timestamps();
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
