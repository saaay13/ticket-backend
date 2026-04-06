<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('history', function (Blueprint $table) {
            $table->id();

            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->uuid('ticket_id');
                $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            } else {
                $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            }

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action', 50);
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->jsonb('changes');
                $table->jsonb('metadata')->nullable();
            } else {
                $table->json('changes');
                $table->json('metadata')->nullable();
            }
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('history', function (Blueprint $table) {
            $table->index('ticket_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history');
    }
};
