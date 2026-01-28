CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    user_group TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS categories (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    shape TEXT NOT NULL,
    width INTEGER NOT NULL,
    height INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS types (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS nodes (
    id TEXT PRIMARY KEY,
    label TEXT NOT NULL,
    category TEXT NOT NULL,
    type TEXT NOT NULL,
    user_created BOOLEAN NOT NULL DEFAULT 0,
    data TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category) REFERENCES categories(id),
    FOREIGN KEY (type) REFERENCES types(id)
);

CREATE TABLE IF NOT EXISTS edges (
    id TEXT PRIMARY KEY,
    label TEXT NOT NULL,
    source TEXT NOT NULL,
    target TEXT NOT NULL,
    data TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (source) REFERENCES nodes(id) ON DELETE CASCADE,
    FOREIGN KEY (target) REFERENCES nodes(id) ON DELETE CASCADE
);

-- CREATE UNIQUE INDEX IF NOT EXISTS idx_edges_source_target ON edges (source, target);

CREATE TABLE IF NOT EXISTS status (
    node_id    TEXT PRIMARY KEY NOT NULL,
    status     TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (node_id) REFERENCES nodes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS projects (
    id         TEXT PRIMARY KEY NOT NULL,
    name       TEXT NOT NULL,
    author     TEXT NOT NULL,
    data       TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS nodes_projects (
    node_id    TEXT NOT NULL,
    project_id TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (node_id, project_id),
    FOREIGN KEY (node_id) REFERENCES nodes(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS audit (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_type TEXT NOT NULL,
    entity_id TEXT NOT NULL,
    action TEXT NOT NULL,
    old_data TEXT,
    new_data TEXT,
    user_id TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT OR IGNORE INTO users VALUES('admin', 'admin');

INSERT OR IGNORE INTO categories VALUES
('business',       'Negócios',       'round-rectangle', 80, 80),
('application',    'Aplicação',      'ellipse',         60, 60),
('infrastructure', 'Infraestrutura', 'round-hexagon',   60, 53);

INSERT OR IGNORE INTO types VALUES
('business',      'Negócios'),
('business_case', 'Caso de Uso'),
('service',       'Serviço'),
('server',        'Servidor'),
('database',      'Banco de Dados');

insert into nodes values ('n1', 'n1', 'application', 'service', 1, '{}', current_timestamp, current_timestamp);
insert into nodes values ('n2', 'n2', 'application', 'service', 1, '{}', current_timestamp, current_timestamp);
insert into nodes values ('n3', 'n3', 'application', 'service', 1, '{}', current_timestamp, current_timestamp);
insert into nodes values ('n4', 'n4', 'application', 'service', 1, '{}', current_timestamp, current_timestamp);
insert into nodes values ('n5', 'n5', 'application', 'service', 1, '{}', current_timestamp, current_timestamp);

insert into edges values ('e1-2', 'connects_to', 'n1', 'n2', '{}', current_timestamp, current_timestamp);
insert into edges values ('e3-4', 'connects_to', 'n3', 'n4', '{}', current_timestamp, current_timestamp);
insert into edges values ('e2-5', 'connects_to', 'n2', 'n5', '{}', current_timestamp, current_timestamp);
insert into edges values ('e4-5', 'connects_to', 'n4', 'n5', '{}', current_timestamp, current_timestamp);

insert into projects values ('p1', 'Projeto 1', 'admin', '', current_timestamp, current_timestamp);

insert into nodes_projects values ('n1', 'p1', current_timestamp);
insert into nodes_projects values ('n3', 'p1', current_timestamp);

.headers on
.mode column

WITH RECURSIVE descendants AS (
    SELECT     e.id,
               e.label,
               e.source as source_id,
               e.target as target_id,
               e.data,
               0 as depth
    FROM       edges e
    INNER JOIN nodes_projects np
    ON         e.source = np.node_id
    WHERE      np.project_id = 'p1'
    
    UNION ALL
    
    SELECT      e.id,
                e.label,
                e.source as source_id,
                e.target as target_id,
                e.data,
                d.depth + 1
    FROM        descendants d
    INNER JOIN  edges e ON d.target_id = e.source
    WHERE       d.depth < 100
)
SELECT DISTINCT d.id as edge_id,
                d.label as edge_label,
                d.data as edge_data,
                d.source_id,
                s.label as source_label,
                s.category as source_category,
                s.type as source_type,
                s.user_created as source_user_created,
                s.data as source_data,
                d.target_id,
                t.label as target_label,
                t.category as target_category,
                t.type as target_type,
                t.user_created as target_user_created,
                t.data as target_data,
                min(d.depth) as depth
FROM            descendants d
INNER JOIN      nodes s
ON              d.source_id = s.id
INNER JOIN      nodes t
ON              d.target_id = t.id
GROUP BY        d.id,
                d.label,
                d.data,
                d.source_id,
                s.label,
                s.category,
                s.type,
                s.user_created,
                s.data,
                d.target_id,
                t.label,
                t.category,
                t.type,
                t.user_created,
                t.data
ORDER BY        depth,
                d.id;

