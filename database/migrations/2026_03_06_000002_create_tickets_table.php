<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = Schema::getConnection();
        $isPostgres = $connection->getDriverName() === 'pgsql';

        if ($isPostgres) {
            DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
        }

        Schema::create('tickets', function (Blueprint $table) use ($isPostgres) {
            if ($isPostgres) {
                $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            } else {
                $table->id();
            }

            $table->string('ticket_number', 20)->unique();
            $table->string('title', 200);

            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');

            $table->enum('status', ['open', 'in_progress', 'pending', 'resolved', 'closed', 'deleted'])->default('open');

            $table->timestamp('closed_at')->nullable();

            if ($isPostgres) {
                $table->jsonb('details')->default(DB::raw("'{\"description\": \"\", \"priority\": \"medium\", \"department\": null, \"system\": null, \"source_ip\": null, \"resolution_time_minutes\": null, \"tags\": [], \"custom_fields\": {}}'::jsonb"));
            } else {
                $table->json('details')->nullable();
            }

            $table->integer('total_time_minutes')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->index('ticket_number');
            $table->index('requester_id');
            $table->index('assigned_to_id');
            $table->index('status');
            $table->index('created_at');
        });

        if ($isPostgres) {
            DB::statement("ALTER TABLE tickets ADD CONSTRAINT ticket_status_check CHECK (status IN ('open', 'in_progress', 'pending', 'resolved', 'closed', 'deleted'))");
            DB::statement('CREATE INDEX IF NOT EXISTS idx_tickets_details ON tickets USING gin(details);');
            DB::statement("CREATE INDEX IF NOT EXISTS idx_tickets_title ON tickets USING gin(to_tsvector('english', title));");

            DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION generate_ticket_number()
RETURNS TRIGGER AS $$
DECLARE
    year_prefix CHAR(4);
    sequence_number INTEGER;
BEGIN
    year_prefix := to_char(NEW.created_at, 'YYYY');

    SELECT COALESCE(MAX(SUBSTRING(ticket_number FROM 8)::INTEGER), 0) + 1
    INTO sequence_number
    FROM tickets
    WHERE ticket_number LIKE 'TK-' || year_prefix || '%';

    NEW.ticket_number := 'TK-' || year_prefix || LPAD(sequence_number::TEXT, 6, '0');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_generate_ticket_number
    BEFORE INSERT ON tickets
    FOR EACH ROW
    EXECUTE FUNCTION generate_ticket_number();

CREATE OR REPLACE FUNCTION update_ticket_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_ticket_timestamp
    BEFORE UPDATE ON tickets
    FOR EACH ROW
    EXECUTE FUNCTION update_ticket_timestamp();

SQL
            );
        }
    }

    public function down(): void
    {
        $connection = Schema::getConnection();
        $isPostgres = $connection->getDriverName() === 'pgsql';

        if ($isPostgres) {
            DB::unprepared('DROP TRIGGER IF EXISTS trigger_update_ticket_timestamp ON tickets;');
            DB::unprepared('DROP FUNCTION IF EXISTS update_ticket_timestamp();');
            DB::unprepared('DROP TRIGGER IF EXISTS trigger_generate_ticket_number ON tickets;');
            DB::unprepared('DROP FUNCTION IF EXISTS generate_ticket_number();');
        }

        Schema::dropIfExists('tickets');
    }
};
