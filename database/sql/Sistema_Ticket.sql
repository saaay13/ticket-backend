-- =====================================================
-- SISTEMA DE TICKETS IT - VERSIÓN COMPACTA
-- DISEÑO PARA POSTGRESQL (NO RELACIONAL CON JSONB)
-- =====================================================

-- Extensión para UUIDs
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- =====================================================
-- TABLE 1: DEPARTMENTS
-- =====================================================
CREATE TABLE departments (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    active BOOLEAN DEFAULT TRUE
);

INSERT INTO departments (name) VALUES
    ('Systems'), ('Network'), ('Support'), ('Development');

-- =====================================================
-- TABLE 2: USERS
-- =====================================================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    department_id INTEGER REFERENCES departments(id),
    role VARCHAR(20) DEFAULT 'user', -- 'admin', 'agent', 'user'
    active BOOLEAN DEFAULT TRUE,
    password_hash VARCHAR(255) NOT NULL,
    metadata JSONB DEFAULT '{}'::jsonb, -- phone, avatar, preferences, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_metadata ON users USING gin(metadata);

-- =====================================================
-- TABLE 3: CATEGORIES (with SLA defaults)
-- =====================================================
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    sla_config JSONB NOT NULL DEFAULT '{"response_hours": 4, "resolution_hours": 24, "default_priority": "medium"}'::jsonb,
    active BOOLEAN DEFAULT TRUE
);

INSERT INTO categories (name, sla_config) VALUES
    ('Hardware', '{"response_hours": 8, "resolution_hours": 48, "default_priority": "low"}'),
    ('Software', '{"response_hours": 4, "resolution_hours": 24, "default_priority": "medium"}'),
    ('Network', '{"response_hours": 2, "resolution_hours": 12, "default_priority": "high"}'),
    ('Access', '{"response_hours": 2, "resolution_hours": 8, "default_priority": "high"}');

-- =====================================================
-- TABLE 4: TICKETS (core - JSONB for flexible details)
-- =====================================================
CREATE TABLE tickets (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    ticket_number VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,

    -- Foreign keys
    requester_id INTEGER NOT NULL REFERENCES users(id),
    assigned_to_id INTEGER REFERENCES users(id),
    category_id INTEGER NOT NULL REFERENCES categories(id),

    -- Status
    status VARCHAR(20) NOT NULL DEFAULT 'open',

    -- Key dates
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP,

    -- Flexible details
    details JSONB NOT NULL DEFAULT '{
        "description": "",
        "priority": "medium",
        "department": null,
        "system": null,
        "source_ip": null,
        "resolution_time_minutes": null,
        "tags": [],
        "custom_fields": {}
    }'::jsonb,

    -- Metrics
    total_time_minutes INTEGER,

    CONSTRAINT status_valid CHECK (status IN ('open', 'in_progress', 'pending', 'resolved', 'closed'))
);

-- Indexes for quick lookup
CREATE INDEX idx_tickets_number ON tickets(ticket_number);
CREATE INDEX idx_tickets_requester ON tickets(requester_id);
CREATE INDEX idx_tickets_assigned ON tickets(assigned_to_id);
CREATE INDEX idx_tickets_status ON tickets(status);
CREATE INDEX idx_tickets_created_at ON tickets(created_at);
CREATE INDEX idx_tickets_details ON tickets USING gin(details);

-- Full text search index
CREATE INDEX idx_tickets_title ON tickets USING gin(to_tsvector('english', title));

-- =====================================================
-- TABLE 5: COMMENTS (with metadata JSONB)
-- =====================================================
CREATE TABLE comments (
    id BIGSERIAL PRIMARY KEY,
    ticket_id UUID NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id),
    content TEXT NOT NULL,
    metadata JSONB DEFAULT '{
        "is_internal": false,
        "worked_time_minutes": null,
        "source_ip": null,
        "attachments": []
    }'::jsonb,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_comments_ticket ON comments(ticket_id);
CREATE INDEX idx_comments_metadata ON comments USING gin(metadata);

-- =====================================================
-- TABLE 6: HISTORY (with JSONB for flexibility)
-- =====================================================
CREATE TABLE history (
    id BIGSERIAL PRIMARY KEY,
    ticket_id UUID NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id),
    action VARCHAR(50) NOT NULL, -- 'created', 'assigned', 'status_change', etc.
    changes JSONB NOT NULL, -- before/after changes
    metadata JSONB DEFAULT '{"note": null, "source_ip": null}'::jsonb,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_history_ticket ON history(ticket_id);
CREATE INDEX idx_history_created_at ON history(created_at);

-- =====================================================
-- TABLE 7: NOTIFICATIONS
-- =====================================================
CREATE TABLE notifications (
    id BIGSERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id),
    ticket_id UUID REFERENCES tickets(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL, -- 'assignment', 'comment', 'due'
    title VARCHAR(200) NOT NULL,
    content TEXT,
    read BOOLEAN DEFAULT FALSE,
    metadata JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_user ON notifications(user_id, read);
CREATE INDEX idx_notifications_ticket ON notifications(ticket_id);

-- =====================================================
-- TABLE 8: TEMPLATES (optional)
-- =====================================================
CREATE TABLE templates (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    type VARCHAR(50) NOT NULL, -- 'reply', 'internal_comment'
    content TEXT NOT NULL,
    metadata JSONB DEFAULT '{
        "category_id": null,
        "is_public": true,
        "variables": []
    }'::jsonb,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- FUNCIONES Y TRIGGERS
-- =====================================================

-- Function to generate ticket number
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

-- Function to update updated_at timestamp
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

-- Function to record history automatically
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
        -- Record status changes
        IF OLD.status IS DISTINCT FROM NEW.status THEN
            INSERT INTO history (ticket_id, user_id, action, changes)
            VALUES (
                NEW.id,
                COALESCE(NEW.assigned_to_id, NEW.requester_id),
                'status_change',
                jsonb_build_object('before', OLD.status, 'after', NEW.status)
            );
            
            -- If ticket is closed, calculate total time
            IF NEW.status = 'closed' AND OLD.status != 'closed' THEN
                NEW.closed_at = CURRENT_TIMESTAMP;
                NEW.total_time_minutes = EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - NEW.created_at))/60;
            END IF;
        END IF;
        
        -- Record assignment changes
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

-- =====================================================
-- USEFUL VIEWS
-- =====================================================

-- Active tickets view
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

-- Quick dashboard metrics view
CREATE VIEW v_dashboard_metrics AS
SELECT
    COUNT(*) FILTER (WHERE status = 'open') AS tickets_open,
    COUNT(*) FILTER (WHERE status = 'in_progress') AS tickets_in_progress,
    COUNT(*) FILTER (WHERE status = 'pending') AS tickets_pending,
    COUNT(*) FILTER (WHERE created_at >= CURRENT_DATE) AS tickets_today,
    AVG(total_time_minutes) FILTER (WHERE status = 'closed') AS avg_resolution_time
FROM tickets;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Users
INSERT INTO users (email, first_name, last_name, password_hash, role, department_id) VALUES
    ('admin@empresa.com', 'Admin', 'Sistema', '$2a$10$N9qo8uLOickgx2ZMRZoMy.MrV.DQiK8p3p7zQbZzZx9q8q8q8q8q8', 'admin', 1),
    ('agente@empresa.com', 'Ana', 'García', '$2a$10$N9qo8uLOickgx2ZMRZoMy.MrV.DQiK8p3p7zQbZzZx9q8q8q8q8q8', 'agente', 1),
    ('usuario@empresa.com', 'Carlos', 'López', '$2a$10$N9qo8uLOickgx2ZMRZoMy.MrV.DQiK8p3p7zQbZzZx9q8q8q8q8q8', 'usuario', 3);

-- Tickets de prueba
INSERT INTO tickets (titulo, solicitante_id, categoria_id, detalles) VALUES
    ('No puedo acceder al correo', 
     (SELECT id FROM usuarios WHERE email = 'usuario@empresa.com'),
     (SELECT id FROM categorias WHERE nombre = 'Accesos'),
     '{
        "descripcion": "Error al intentar acceder a Outlook",
        "prioridad": "alta",
        "etiquetas": ["correo", "autenticacion"]
     }'::jsonb);

-- =====================================================
-- COMENTARIOS
-- =====================================================
COMMENT ON TABLE tickets IS 'Tabla principal con campo JSONB para datos flexibles';
COMMENT ON COLUMN tickets.detalles IS 'Almacena descripción, prioridad, etiquetas y campos personalizados';