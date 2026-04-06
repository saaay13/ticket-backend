<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('id');
            }

            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }

            if (! Schema::hasColumn('users', 'department_id')) {
                $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null')->after('last_name');
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('user')->after('department_id');
            }

            if (! Schema::hasColumn('users', 'active')) {
                $table->boolean('active')->default(true)->after('role');
            }

            if (! Schema::hasColumn('users', 'metadata')) {
                if (Schema::getConnection()->getDriverName() === 'pgsql') {
                    $table->jsonb('metadata')->nullable()->default(DB::raw("'{}'::jsonb"))->after('active');
                } else {
                    $table->json('metadata')->nullable()->default('{}')->after('active');
                }
            }

            if (! Schema::hasColumn('users', 'password_hash')) {
                $table->string('password_hash')->nullable()->after('password');
            }

            // Keep the default Laravel email/password structure.
            // Remove legacy fields from the previous project that are not needed.
            if (Schema::hasColumn('users', 'ci')) {
                $table->dropColumn('ci');
            }
            if (Schema::hasColumn('users', 'complement')) {
                $table->dropColumn('complement');
            }
            if (Schema::hasColumn('users', 'address')) {
                $table->dropColumn('address');
            }
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'metadata')) {
                $table->dropColumn('metadata');
            }
            if (Schema::hasColumn('users', 'active')) {
                $table->dropColumn('active');
            }
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }
            if (Schema::hasColumn('users', 'password_hash')) {
                $table->dropColumn('password_hash');
            }
            if (Schema::hasColumn('users', 'first_name')) {
                $table->dropColumn('first_name');
            }
            if (Schema::hasColumn('users', 'last_name')) {
                $table->dropColumn('last_name');
            }

            // If needed, restore old fields in a future migration.
        });
    }
};
