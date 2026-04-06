<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = Schema::getConnection();
        $isPostgres = $connection->getDriverName() === 'pgsql';

        if (! $isPostgres) {
            return;
        }

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION record_ticket_history()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO history (ticket_id, user_id, action, changes)
        VALUES (
            NEW.id,
            NEW.requester_id,
            'created',
            jsonb_build_object('title', NEW.title, 'details', NEW.details)
        );
    ELSIF TG_OP = 'UPDATE' THEN
        IF OLD.status IS DISTINCT FROM NEW.status THEN
            INSERT INTO history (ticket_id, user_id, action, changes)
            VALUES (
                NEW.id,
                COALESCE(NEW.assigned_to_id, NEW.requester_id),
                'status_change',
                jsonb_build_object('before', OLD.status, 'after', NEW.status)
            );

            IF NEW.status = 'closed' AND OLD.status != 'closed' THEN
                NEW.closed_at = CURRENT_TIMESTAMP;
                NEW.total_time_minutes = EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - NEW.created_at))/60;
            END IF;
        END IF;

        IF OLD.assigned_to_id IS DISTINCT FROM NEW.assigned_to_id THEN
            INSERT INTO history (ticket_id, user_id, action, changes)
            VALUES (
                NEW.id,
                NEW.assigned_to_id,
                'assigned',
                jsonb_build_object('before', OLD.assigned_to_id, 'after', NEW.assigned_to_id)
            );
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_record_ticket_history
    AFTER INSERT OR UPDATE ON tickets
    FOR EACH ROW
    EXECUTE FUNCTION record_ticket_history();

CREATE VIEW v_active_tickets AS
SELECT
    t.id,
    t.ticket_number,
    t.title,
    t.status,
    t.details->>'priority' AS priority,
    u_req.email AS requester_email,
    u_req.first_name || ' ' || u_req.last_name AS requester_name,
    u_assigned.first_name || ' ' || u_assigned.last_name AS assigned_name,
    c.name AS category,
    t.created_at,
    t.details->>'tags' AS tags,
    EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - t.created_at))/3600 AS hours_open,
    (SELECT COUNT(*) FROM comments WHERE ticket_id = t.id) AS total_comments
FROM tickets t
JOIN users u_req ON t.requester_id = u_req.id
LEFT JOIN users u_assigned ON t.assigned_to_id = u_assigned.id
JOIN categories c ON t.category_id = c.id
WHERE t.status NOT IN ('closed', 'resolved');

CREATE VIEW v_dashboard_metrics AS
SELECT
    COUNT(*) FILTER (WHERE status = 'open') AS tickets_open,
    COUNT(*) FILTER (WHERE status = 'in_progress') AS tickets_in_progress,
    COUNT(*) FILTER (WHERE status = 'pending') AS tickets_pending,
    COUNT(*) FILTER (WHERE created_at >= CURRENT_DATE) AS tickets_today,
    AVG(total_time_minutes) FILTER (WHERE status = 'closed') AS avg_resolution_time
FROM tickets;
SQL
        );
    }

    public function down(): void
    {
        $connection = Schema::getConnection();
        $isPostgres = $connection->getDriverName() === 'pgsql';

        if (! $isPostgres) {
            return;
        }

        DB::unprepared('DROP VIEW IF EXISTS v_active_tickets CASCADE;');
        DB::unprepared('DROP VIEW IF EXISTS v_dashboard_metrics CASCADE;');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_record_ticket_history ON tickets;');
        DB::unprepared('DROP FUNCTION IF EXISTS record_ticket_history();');
    }
};
