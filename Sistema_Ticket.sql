-- =====================================================
-- SISTEMA DE TICKETS IT - VERSIÓN COMPACTA
-- DISEÑO PARA POSTGRESQL (NO RELACIONAL CON JSONB)
-- =====================================================

-- Extensión para UUIDs
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- =====================================================
-- TABLA 1: DEPARTAMENTOS
-- =====================================================
CREATE TABLE departamentos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    activo BOOLEAN DEFAULT TRUE
);

INSERT INTO departamentos (nombre) VALUES
    ('Sistemas'), ('Redes'), ('Soporte'), ('Desarrollo');

-- =====================================================
-- TABLA 2: USUARIOS
-- =====================================================
CREATE TABLE usuarios (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    email VARCHAR(255) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    departamento_id INTEGER REFERENCES departamentos(id),
    rol VARCHAR(20) DEFAULT 'usuario', -- 'admin', 'agente', 'usuario'
    activo BOOLEAN DEFAULT TRUE,
    password_hash VARCHAR(255) NOT NULL,
    metadata JSONB DEFAULT '{}'::jsonb, -- Teléfono, avatar, preferencias, etc.
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices
CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_usuarios_rol ON usuarios(rol);
CREATE INDEX idx_usuarios_metadata ON usuarios USING gin(metadata);

-- =====================================================
-- TABLA 3: CATEGORIAS (con SLA incluido)
-- =====================================================
CREATE TABLE categorias (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    sla_config JSONB NOT NULL DEFAULT '{"respuesta_horas": 4, "solucion_horas": 24, "prioridad_default": "media"}'::jsonb,
    activo BOOLEAN DEFAULT TRUE
);

INSERT INTO categorias (nombre, sla_config) VALUES
    ('Hardware', '{"respuesta_horas": 8, "solucion_horas": 48, "prioridad_default": "baja"}'),
    ('Software', '{"respuesta_horas": 4, "solucion_horas": 24, "prioridad_default": "media"}'),
    ('Red', '{"respuesta_horas": 2, "solucion_horas": 12, "prioridad_default": "alta"}'),
    ('Accesos', '{"respuesta_horas": 2, "solucion_horas": 8, "prioridad_default": "alta"}');

-- =====================================================
-- TABLA 4: TICKETS (PRINCIPAL - con JSONB para flexibilidad)
-- =====================================================
CREATE TABLE tickets (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    numero_ticket VARCHAR(20) NOT NULL UNIQUE,
    titulo VARCHAR(200) NOT NULL,
    
    -- Relaciones clave
    solicitante_id UUID NOT NULL REFERENCES usuarios(id),
    asignado_a_id UUID REFERENCES usuarios(id),
    categoria_id INTEGER NOT NULL REFERENCES categorias(id),
    
    -- Estado (simplificado)
    estado VARCHAR(20) NOT NULL DEFAULT 'abierto',
    
    -- Fechas clave
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre TIMESTAMP,
    
    -- Campo flexible para todo lo demás
    detalles JSONB NOT NULL DEFAULT '{
        "descripcion": "",
        "prioridad": "media",
        "departamento": null,
        "sistema_afectado": null,
        "ip_origen": null,
        "tiempo_resolucion_minutos": null,
        "etiquetas": [],
        "campos_personalizados": {}
    }'::jsonb,
    
    -- Métricas
    tiempo_total_minutos INTEGER,
    
    CONSTRAINT estado_valido CHECK (estado IN ('abierto', 'en_progreso', 'pendiente', 'resuelto', 'cerrado'))
);

-- Índices para búsquedas rápidas
CREATE INDEX idx_tickets_numero ON tickets(numero_ticket);
CREATE INDEX idx_tickets_solicitante ON tickets(solicitante_id);
CREATE INDEX idx_tickets_asignado ON tickets(asignado_a_id);
CREATE INDEX idx_tickets_estado ON tickets(estado);
CREATE INDEX idx_tickets_fecha ON tickets(fecha_creacion);
CREATE INDEX idx_tickets_detalles ON tickets USING gin(detalles);

-- Índice para búsqueda de texto
CREATE INDEX idx_tickets_titulo ON tickets USING gin(to_tsvector('spanish', titulo));

-- =====================================================
-- TABLA 5: COMENTARIOS (con metadata JSONB)
-- =====================================================
CREATE TABLE comentarios (
    id BIGSERIAL PRIMARY KEY,
    ticket_id UUID NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    usuario_id UUID NOT NULL REFERENCES usuarios(id),
    contenido TEXT NOT NULL,
    metadata JSONB DEFAULT '{
        "es_interno": false,
        "tiempo_trabajado": null,
        "ip_origen": null,
        "adjuntos": []
    }'::jsonb,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_comentarios_ticket ON comentarios(ticket_id);
CREATE INDEX idx_comentarios_metadata ON comentarios USING gin(metadata);

-- =====================================================
-- TABLA 6: HISTORIAL (con JSONB para flexibilidad)
-- =====================================================
CREATE TABLE historial (
    id BIGSERIAL PRIMARY KEY,
    ticket_id UUID NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    usuario_id UUID NOT NULL REFERENCES usuarios(id),
    accion VARCHAR(50) NOT NULL, -- 'creado', 'asignado', 'cambio_estado', etc.
    cambios JSONB NOT NULL, -- Antes/después de los cambios
    metadata JSONB DEFAULT '{"comentario": null, "ip_origen": null}'::jsonb,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_historial_ticket ON historial(ticket_id);
CREATE INDEX idx_historial_fecha ON historial(fecha);

-- =====================================================
-- TABLA 7: NOTIFICACIONES
-- =====================================================
CREATE TABLE notificaciones (
    id BIGSERIAL PRIMARY KEY,
    usuario_id UUID NOT NULL REFERENCES usuarios(id),
    ticket_id UUID REFERENCES tickets(id) ON DELETE CASCADE,
    tipo VARCHAR(50) NOT NULL, -- 'asignacion', 'comentario', 'vencimiento'
    titulo VARCHAR(200) NOT NULL,
    contenido TEXT,
    leida BOOLEAN DEFAULT FALSE,
    metadata JSONB DEFAULT '{}'::jsonb,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notificaciones_usuario ON notificaciones(usuario_id, leida);
CREATE INDEX idx_notificaciones_ticket ON notificaciones(ticket_id);

-- =====================================================
-- TABLA 8: PLANTILLAS (opcional)
-- =====================================================
CREATE TABLE plantillas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    tipo VARCHAR(50) NOT NULL, -- 'respuesta', 'comentario_interno'
    contenido TEXT NOT NULL,
    metadata JSONB DEFAULT '{
        "categoria_id": null,
        "es_publica": true,
        "variables": []
    }'::jsonb,
    creado_por UUID REFERENCES usuarios(id),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- FUNCIONES Y TRIGGERS
-- =====================================================

-- Función para generar número de ticket
CREATE OR REPLACE FUNCTION generar_numero_ticket()
RETURNS TRIGGER AS $$
DECLARE
    anio CHAR(4);
    secuencia INTEGER;
BEGIN
    anio := to_char(NEW.fecha_creacion, 'YYYY');
    
    SELECT COALESCE(MAX(SUBSTRING(numero_ticket FROM 8)::INTEGER), 0) + 1
    INTO secuencia
    FROM tickets
    WHERE numero_ticket LIKE 'TK-' || anio || '%';
    
    NEW.numero_ticket := 'TK-' || anio || LPAD(secuencia::TEXT, 6, '0');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_generar_numero_ticket
    BEFORE INSERT ON tickets
    FOR EACH ROW
    EXECUTE FUNCTION generar_numero_ticket();

-- Función para actualizar fecha_modificacion
CREATE OR REPLACE FUNCTION actualizar_fecha_modificacion()
RETURNS TRIGGER AS $$
BEGIN
    NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_actualizar_tickets
    BEFORE UPDATE ON tickets
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_fecha_modificacion();

-- Función para registrar historial automático
CREATE OR REPLACE FUNCTION registrar_historial()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO historial (ticket_id, usuario_id, accion, cambios)
        VALUES (
            NEW.id,
            NEW.solicitante_id,
            'creado',
            jsonb_build_object('titulo', NEW.titulo, 'detalles', NEW.detalles)
        );
    ELSIF TG_OP = 'UPDATE' THEN
        -- Registrar cambios en estado
        IF OLD.estado IS DISTINCT FROM NEW.estado THEN
            INSERT INTO historial (ticket_id, usuario_id, accion, cambios)
            VALUES (
                NEW.id,
                COALESCE(NEW.asignado_a_id, NEW.solicitante_id),
                'cambio_estado',
                jsonb_build_object('anterior', OLD.estado, 'nuevo', NEW.estado)
            );
            
            -- Si se cierra el ticket, calcular tiempo total
            IF NEW.estado = 'cerrado' AND OLD.estado != 'cerrado' THEN
                NEW.fecha_cierre = CURRENT_TIMESTAMP;
                NEW.tiempo_total_minutos = EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - NEW.fecha_creacion))/60;
            END IF;
        END IF;
        
        -- Registrar cambios en asignación
        IF OLD.asignado_a_id IS DISTINCT FROM NEW.asignado_a_id THEN
            INSERT INTO historial (ticket_id, usuario_id, accion, cambios)
            VALUES (
                NEW.id,
                NEW.asignado_a_id,
                'asignado',
                jsonb_build_object('anterior', OLD.asignado_a_id, 'nuevo', NEW.asignado_a_id)
            );
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_registrar_historial
    AFTER INSERT OR UPDATE ON tickets
    FOR EACH ROW
    EXECUTE FUNCTION registrar_historial();

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista de tickets activos
CREATE VIEW v_tickets_activos AS
SELECT 
    t.id,
    t.numero_ticket,
    t.titulo,
    t.estado,
    t.detalles->>'prioridad' as prioridad,
    u_sol.email as solicitante_email,
    u_sol.nombre || ' ' || u_sol.apellido as solicitante_nombre,
    u_asig.nombre || ' ' || u_asig.apellido as asignado_nombre,
    c.nombre as categoria,
    t.fecha_creacion,
    t.detalles->>'etiquetas' as etiquetas,
    EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - t.fecha_creacion))/3600 as horas_abierto,
    (SELECT COUNT(*) FROM comentarios WHERE ticket_id = t.id) as total_comentarios
FROM tickets t
JOIN usuarios u_sol ON t.solicitante_id = u_sol.id
LEFT JOIN usuarios u_asig ON t.asignado_a_id = u_asig.id
JOIN categorias c ON t.categoria_id = c.id
WHERE t.estado NOT IN ('cerrado', 'resuelto');

-- Vista de métricas rápidas
CREATE VIEW v_metricas_dashboard AS
SELECT
    COUNT(*) FILTER (WHERE estado = 'abierto') as tickets_abiertos,
    COUNT(*) FILTER (WHERE estado = 'en_progreso') as tickets_en_progreso,
    COUNT(*) FILTER (WHERE estado = 'pendiente') as tickets_pendientes,
    COUNT(*) FILTER (WHERE fecha_creacion >= CURRENT_DATE) as tickets_hoy,
    AVG(tiempo_total_minutos) FILTER (WHERE estado = 'cerrado') as tiempo_promedio_resolucion
FROM tickets;

-- =====================================================
-- DATOS DE PRUEBA
-- =====================================================

-- Usuarios
INSERT INTO usuarios (email, nombre, apellido, password_hash, rol, departamento_id) VALUES
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