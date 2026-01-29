CREATE TABLE IF NOT EXISTS users (
    id         TEXT PRIMARY KEY,
    user_group TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS categories (
    id     TEXT PRIMARY KEY,
    name   TEXT NOT NULL,
    shape  TEXT NOT NULL,
    width  INTEGER NOT NULL,
    height INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS types (
    id   TEXT PRIMARY KEY,
    name TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS nodes (
    id       TEXT PRIMARY KEY,
    label    TEXT NOT NULL,
    category TEXT NOT NULL,
    type     TEXT NOT NULL,
    data     TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category) REFERENCES categories(id),
    FOREIGN KEY (type) REFERENCES types(id)
);

CREATE TABLE IF NOT EXISTS edges (
    id         TEXT PRIMARY KEY,
    label      TEXT NOT NULL,
    source     TEXT NOT NULL,
    target     TEXT NOT NULL,
    data       TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (source) REFERENCES nodes(id) ON DELETE CASCADE,
    FOREIGN KEY (target) REFERENCES nodes(id) ON DELETE CASCADE
);

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

INSERT OR IGNORE INTO nodes VALUES ('n1', 'n1', 'application', 'service', '{}', current_timestamp, current_timestamp);
INSERT OR IGNORE INTO nodes VALUES ('n2', 'n2', 'application', 'service', '{}', current_timestamp, current_timestamp);
INSERT OR IGNORE INTO nodes VALUES ('n3', 'n3', 'application', 'service', '{}', current_timestamp, current_timestamp);
INSERT OR IGNORE INTO nodes VALUES ('n4', 'n4', 'application', 'service', '{}', current_timestamp, current_timestamp);
INSERT OR IGNORE INTO nodes VALUES ('n5', 'n5', 'application', 'service', '{}', current_timestamp, current_timestamp);

INSERT OR IGNORE INTO edges VALUES ('e1-2', 'connects_to', 'n1', 'n2', '{}', current_timestamp, current_timestamp);
INSERT OR IGNORE INTO edges VALUES ('e3-4', 'connects_to', 'n3', 'n4', '{}', current_timestamp, current_timestamp);
INSERT OR IGNORE INTO edges VALUES ('e2-5', 'connects_to', 'n2', 'n5', '{}', current_timestamp, current_timestamp);
INSERT OR IGNORE INTO edges VALUES ('e4-5', 'connects_to', 'n4', 'n5', '{}', current_timestamp, current_timestamp);

INSERT OR IGNORE INTO projects VALUES ('p1', 'Projeto 1', 'admin', '{}', current_timestamp, current_timestamp);

INSERT OR IGNORE INTO nodes_projects VALUES ('n1', 'p1', current_timestamp);
INSERT OR IGNORE INTO nodes_projects VALUES ('n3', 'p1', current_timestamp);
