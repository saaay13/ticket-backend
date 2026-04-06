<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->jsonb('sla_config')->nullable();
            } else {
                $table->json('sla_config')->nullable();
            }
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }





    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
