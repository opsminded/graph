<?php

declare(strict_types=1);

class TestDatabase extends TestAbstractTest
{
    private ?PDO $pdo;
    private ?LoggerInterface $logger;
    private ?Database $database;

    public function up(): void
    {
        $this->pdo = Database::createConnection('sqlite::memory:');
        $this->logger = new Logger(1);
        $this->database = new Database($this->pdo, $this->logger);
    }

    public function down(): void
    {
        $this->pdo = null;
        $this->logger = null;
        $this->database = null;
    }

    public function testGetUser(): void
    {
        $user = $this->database->getUser('maria');
        if ($user !== null) {
            throw new Exception('should return null');
        }

        $user = $this->database->getUser('admin');
        if ($user->id !== 'admin' || $user->group !== 'admin') {
            print_r($user);
            throw new Exception('admin expected');
        }
    }

    public function testGetUsers(): void
    {
        $users = $this->database->getUsers();
        if (count($users) !== 1) {
            throw new Exception('should have one user');
        }
        if ($users[0]->id !== 'admin' || $users[0]->group !== 'admin') {
            throw new Exception('admin expected');
        }
    }

    public function testInsertUser(): void
    {
        $this->database->insertUser(new UserDTO('maria', 'contributor'));
        $stmt = $this->pdo->prepare('select * from users where id = :id');
        $stmt->execute([':id' => 'maria']);
        $user = $stmt->fetch();

        if ($user['id'] !== 'maria' || $user['user_group'] !== 'contributor') {
            throw new Exception('maria expected');
        }
        try {
            $this->database->insertUser(new UserDTO('maria', 'contributor'));
        } catch(Exception $e) {
            if ($e->getMessage() !== "Database Error: Failed to insert user - user already exists. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: users.id") {
                throw new Exception('unique constraint expected');
            }
            return;
        }
        throw new Exception('error expected');
    }

    public function testBatchInsertUsers(): void
    {
        $users = [
            ['id' => 'joao', 'group' => 'admin'],
            ['id' => 'ana', 'group' => 'contributor'],
            ['id' => 'carlos', 'group' => 'viewer'],
            ['id' => 'joao', 'group' => 'admin'],
        ];

        try {
            $this->database->batchInsertUsers($users);
        } catch(Exception $e) {
            if ($e->getMessage() !== "Database Error: Failed to insert user in batch: user already exists: joao. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: users.id") {
                throw new Exception('unique constraint expected');
            }
        }

        $users = [
            ['id' => 'j', 'group' => 'admin'],
            ['id' => 'a', 'group' => 'contributor'],
            ['id' => 'c', 'group' => 'viewer'],
        ];

        $this->database->batchInsertUsers($users);

        foreach ($users as $u) {
            $stmt = $this->pdo->prepare('select * from users where id = :id');
            $stmt->execute([':id' => $u['id']]);
            $user = $stmt->fetch();

            if ($user['id'] !== $u['id'] || $user['user_group'] !== $u['group']) {
                throw new Exception($u['id'] . ' expected');
            }
        }
    }

    public function testUpdateUser(): void
    {
        $stmt = $this->pdo->prepare('insert into users (id, user_group) values (:id, :user_group)');
        $stmt->execute([':id' => 'maria', ':user_group' => 'contributor']);
        
        $this->database->updateUser(new UserDTO('maria', 'admin'));
        
        $stmt = $this->pdo->prepare('select * from users where id = :id');
        $stmt->execute([':id' => 'maria']);
        $user = $stmt->fetch();
        
        if ($user['id'] !== 'maria' || $user['user_group'] !== 'admin') {
            throw new Exception('expected maria admin');
        }
        if ($this->database->updateUser(new UserDTO('joao', 'contributor'))) {
            throw new Exception('expected joao not found');
        }
    }

    public function testDeleteUser(): void
    {
        $stmt = $this->pdo->prepare('insert into users (id, user_group) values (:id, :user_group)');
        $stmt->execute([':id' => 'maria', ':user_group' => 'contributor']);
        
        if (!$this->database->deleteUser('maria')) {
            throw new Exception('expected maria deleted');
        }

        $stmt = $this->pdo->prepare('select * from users where id = :id');
        $stmt->execute([':id' => 'maria']);
        $user = $stmt->fetch();

        if ($user !== false) {
            throw new Exception('expected maria not found');
        }

        if ($this->database->deleteUser('joao')) {
            throw new Exception('expected joao not found');
        }
    }

    public function testGetCategory(): void
    {
        $category = $this->database->getCategory('nonexistent');
        if ($category !== null) {
            throw new Exception('should return null');
        }
        $category = $this->database->getCategory('business');
        if ($category->id !== 'business' || $category->name !== 'Negócios') {
            throw new Exception('business expected');
        }
    }


    public function testGetCategories(): void
    {
        $categories = $this->database->getCategories();
        $originalCount = count($categories);
        
        if ($originalCount === 0) {
            throw new Exception('should have categories');
        }

        $this->pdo->exec('insert into categories (id, name, shape, width, height) values ("cat1", "Category 1", "box", 80, 80)');
        $this->pdo->exec('insert into categories (id, name, shape, width, height) values ("cat2", "Category 2", "box", 80, 80)');

        $categories = $this->database->getCategories();
        if (count($categories) !== $originalCount + 2) {
            throw new Exception('should be two categories');
        }

        foreach ($categories as $key => $cat) {
            if ($cat->id !== 'cat1' && $cat->id !== 'cat2') {
                unset($categories[$key]);
            }
        }

        $categories = array_values($categories);


        if ($categories[0]->id !== 'cat1' || $categories[0]->name !== 'Category 1') {
            throw new Exception('error on category cat1');
        }

        if ($categories[1]->id !== 'cat2' || $categories[1]->name !== 'Category 2') {
            throw new Exception('error on category cat2');
        }
    }

    public function testInsertCategory(): void
    {
        $this->database->insertCategory(new CategoryDTO('cat1', 'Category 1', 'box', 100, 50));
        $stmt = $this->pdo->prepare('select * from categories where id = :id');
        $stmt->execute([':id' => 'cat1']);
        $category = $stmt->fetch();
        if ($category['id'] !== 'cat1' || $category['name'] !== 'Category 1' || $category['shape'] !== 'box' || $category['width'] !== 100 || $category['height'] !== 50) {
            throw new Exception('error on insert category cat1');
        }

        try {
            $this->database->insertCategory(new CategoryDTO('cat1', 'Category 1', 'box', 100, 50));
        } catch(DatabaseException $e) {
            if ($e->getMessage() !== "Database Error: Failed to insert category - category already exists: cat1. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: categories.id") {
                throw new Exception('unique constraint expected');
            }
            return;
        }
        throw new Exception('error expected');
    }

    public function testUpdateCategory(): void
    {
        $this->pdo->exec('insert into categories (id, name, shape, width, height) values ("cat1", "Category 1", "box", 100, 50)');
        $this->database->updateCategory(new CategoryDTO('cat1', 'Updated Category 1', 'circle', 150, 75));

        $stmt = $this->pdo->prepare('select * from categories where id = :id');
        $stmt->execute([':id' => 'cat1']);
        $category = $stmt->fetch();

        if ($category['id'] !== 'cat1' || $category['name'] !== 'Updated Category 1' || $category['shape'] !== 'circle' || $category['width'] !== 150 || $category['height'] !== 75) {
            throw new Exception('error on update category cat1');
        }

        $result = $this->database->updateCategory(new CategoryDTO('nonexistent', 'Name', 'box', 100, 50));
        if ($result) {
            throw new Exception('error on update nonexistent category');
        }

    }

    public function testDeleteCategory(): void
    {
        $this->pdo->exec('insert into categories (id, name, shape, width, height) values ("cat1", "Category 1", "box", 100, 50)');
        $this->database->deleteCategory('cat1');

        $stmt = $this->pdo->prepare('select * from categories where id = :id');
        $stmt->execute([':id' => 'cat1']);
        $category = $stmt->fetch();

        if ($category !== false) {
            throw new Exception('category cat1 should be deleted');
        }

        $result = $this->database->deleteCategory('nonexistent');
        if ($result) {
            throw new Exception('deleting nonexistent category should return false');
        }
    }

    public function testGetType(): void
    {
        $type = $this->database->getType('service');
        if ($type->id !== 'service' || $type->name !== 'Serviço') {
            throw new Exception('service expected');
        }

        $type = $this->database->getType('nonexistent');
        if ($type !== null) {
            throw new Exception('should return null');
        }
    }

    public function testGetTypes(): void
    {
        $types = $this->database->getTypes();
        $originalCount = count($types);
        
        if ($originalCount === 0) {
            throw new Exception('should have types');
        }

        $this->pdo->exec('insert into types (id, name) values ("type1", "Type 1")');
        $this->pdo->exec('insert into types (id, name) values ("type2", "Type 2")');

        $types = $this->database->getTypes();
        if (count($types) !== $originalCount + 2) {
            throw new Exception('should be two types');
        }

        foreach ($types as $key => $type) {
            if ($type->id !== 'type1' && $type->id !== 'type2') {
                unset($types[$key]);
            }
        }

        $types = array_values($types);

        if ($types[0]->id !== 'type1' || $types[0]->name !== 'Type 1') {
            throw new Exception('error on type type1');
        }

        if ($types[1]->id !== 'type2' || $types[1]->name !== 'Type 2') {
            throw new Exception('error on type type2');
        }
    }

    public function testInsertType(): void
    {
        $this->database->insertType(new TypeDTO('type1', 'Type 1'));
        $stmt = $this->pdo->prepare('select * from types where id = :id');
        $stmt->execute([':id' => 'type1']);
        $type = $stmt->fetch();
        if ($type['id'] !== 'type1' || $type['name'] !== 'Type 1') {
            throw new Exception('error on insert type type1');
        }

        try {
            $this->database->insertType(new TypeDTO('type1', 'Type 1'));
        } catch(DatabaseException $e) {
            if ($e->getMessage() !== "Database Error: Failed to insert type. Type already exists: type1. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: types.id") {
                throw new Exception('unique constraint expected');
            }
            return;
        }
    }

    public function testUpdateType(): void
    {
        $this->pdo->exec('insert into types (id, name) values ("type1", "Type 1")');
        $this->database->updateType(new TypeDTO('type1', 'Updated Type 1'));

        $stmt = $this->pdo->prepare('select * from types where id = :id');
        $stmt->execute([':id' => 'type1']);
        $type = $stmt->fetch();

        if ($type['id'] !== 'type1' || $type['name'] !== 'Updated Type 1') {
            throw new Exception('error on update type type1');
        }

        $result = $this->database->updateType(new TypeDTO('nonexistent', 'Name'));
        if ($result) {
            throw new Exception('error on update nonexistent type');
        }
    }

    public function testDeleteType(): void
    {
        $this->pdo->exec('insert into types (id, name) values ("type1", "Type 1")');
        $this->database->deleteType('type1');

        $stmt = $this->pdo->prepare('select * from types where id = :id');
        $stmt->execute([':id' => 'type1']);
        $type = $stmt->fetch();

        if ($type !== false) {
            throw new Exception('type type1 should be deleted');
        }

        $result = $this->database->deleteType('nonexistent');
        if ($result) {
            throw new Exception('deleting nonexistent type should return false');
        }
    }

    public function testGetNode(): void {
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');
        $stmt->execute([
            ':id' => 'node1',
            ':label' => 'Node 01',
            ':category' => 'business',
            ':type' => 'service',
            ':data' => json_encode(['running_on' => 'SRV01OP'])
        ]);

        $node = $this->database->getNode('node1');
        
        if ($node->id !== 'node1' || $node->label !== 'Node 01' || $node->category !== 'business' || $node->type !== 'service') {
            throw new Exception('error on getNode');
        }

        if ($node->data['running_on'] !== 'SRV01OP') {
            throw new Exception('error on getNode');
        }

        if (!is_null($this->database->getNode('node2'))) {
            throw new Exception('null expected');
        }
    }

    public function testGetNodes(): void {
        $this->pdo->exec('insert into categories (id, name, shape, width, height) values ("cat1", "Category 1", "box", 100, 50)');
        $this->pdo->exec('insert into categories (id, name, shape, width, height) values ("cat2", "Category 2", "box", 100, 50)');
        $this->pdo->exec('insert into types (id, name) values ("app", "Application")');
        $this->pdo->exec('insert into types (id, name) values ("db", "Database")');
        
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');

        $stmt->execute([
            ':id' => 'node1',
            ':label' => 'Node 01',
            ':category' => 'cat1',
            ':type' => 'app',
            ':data' => json_encode(['running_on' => 'SRV01OP'])
        ]);
        usleep(500);
        $stmt->execute([
            ':id' => 'node2',
            ':label' => 'Node 02',
            ':category' => 'cat2',
            ':type' => 'db',
            ':data' => json_encode(['running_on' => 'SRV011P'])
        ]);

        $nodes = $this->database->getNodes();

        if (count($nodes) !== 2) {
            throw new Exception('error on testGetNodes');
        }

        if ($nodes[0]->id !== 'node1') {
            throw new Exception('error on getNode');
        }

        if ($nodes[0]->data['running_on'] !== 'SRV01OP') {
            throw new Exception('error on getNode');
        }

        if ($nodes[1]->id !== 'node2') {
            throw new Exception('error on getNode');
        }

        if ($nodes[1]->data['running_on'] !== 'SRV011P') {
            throw new Exception('error on getNode');
        }
    }

    public function testInsertNode(): void {
        $this->database->insertNode(new NodeDTO('node1', 'Node 01', 'business', 'service', false, ['running_on' => 'SRV01OP']));
        

        $stmt = $this->pdo->prepare('select * from nodes where id = :id');
        $stmt->execute([':id' => 'node1']);
        $dbNode = $stmt->fetch();

        if ($dbNode['id'] !== 'node1' || $dbNode['label'] !== 'Node 01' || $dbNode['category'] !== 'business' || $dbNode['type'] !== 'service') {
            throw new Exception('error on testInsertNode');
        }
        if ($dbNode['data'] !== "{\"running_on\":\"SRV01OP\"}") {
            throw new Exception('error on testInsertNode');
        }

        $this->database->insertNode(new NodeDTO('user_created', 'User Created', 'application', 'database', true, ['created_by' => 'admin']));
        
        $stmt->execute([':id' => 'user_created']);
        $dbNode = $stmt->fetch();

        if ($dbNode['id'] !== 'user_created' || $dbNode['label'] !== 'User Created' || $dbNode['category'] !== 'application' || $dbNode['type'] !== 'database' || $dbNode['user_created'] !== 1) {
            print_r($dbNode);
            throw new Exception('error on testInsertNode');
        }

        try {
            $this->database->insertNode(new NodeDTO('node1', 'Node 01', 'business', 'service', false, ['running_on' => 'SRV01OP']));
        } catch(Exception $e) {
            if ($e->getMessage() !== "Database Error: Failed to insert node. Node already exists: node1. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: nodes.id") {
                throw new Exception('unique constraint expected');
            }
            return;
        }
        throw new Exception('error on testInsertNode');
    }

    public function testBatchInsertNodes(): void {
        $nodes = [
            new NodeDTO('node1', 'Node 01', 'business', 'service', false, ['running_on' => 'SRV01OP']),
            new NodeDTO('node2', 'Node 02', 'application', 'database', false, ['running_on' => 'SRV011P']),
            new NodeDTO('node3', 'Node 03', 'application', 'service', false, ['running_on' => 'SRV012P']),
            new NodeDTO('node1', 'Node 01', 'business', 'service', false, ['running_on' => 'SRV01OP']),
        ];

        try {
            $this->database->batchInsertNodes($nodes);
        } catch (Exception $e) {
            if ($e->getMessage() !== "Database Error: Failed to batch insert nodes. Node already exists: node1. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: nodes.id") {
                throw new Exception('unique constraint expected');
            }
        }

        $nodes = [
            new NodeDTO('node4', 'Node 04', 'business', 'service', false, ['running_on' => 'SRV01OP']),
            new NodeDTO('node5', 'Node 05', 'application', 'database', false, ['running_on' => 'SRV011P']),
            new NodeDTO('node6', 'Node 06', 'application', 'service', false, ['running_on' => 'SRV012P']),
        ];

        $this->database->batchInsertNodes($nodes);

        $stmt = $this->pdo->prepare('select * from nodes where id = :id');
        
        foreach ($nodes as $n) {
            $stmt->execute([':id' => $n->id]);
            $node = $stmt->fetch();

            if ($node['id'] !== $n->id || $node['label'] !== $n->label || $node['category'] !== $n->category || $node['type'] !== $n->type) {
                throw new Exception('error on batchInsertNodes');
            }
            if (json_decode($node['data'], true)['running_on'] !== $n->data['running_on']) {
                throw new Exception('error on batchInsertNodes');
            }
        }
    }

    public function testUpdateNode(): void {
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node1", "Node 01", "business", "service", \'{"running_on":"SRV011P"}\')');
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node2", "Node 02", "application", "database", \'{"running_on":"SRV012P"}\')');
        
        $this->database->updateNode(new NodeDTO('node1', 'Novo Label', 'application', 'database', false, ['other' => 'diff']));

        $stmt = $this->pdo->prepare('select * from nodes where id = :id');
        $stmt->execute([':id' => 'node1']);
        $node = $stmt->fetch();

        if ($node['id'] !== 'node1' || $node['label'] !== 'Novo Label' || $node['category'] !== 'application' || $node['type'] !== 'database') {
            throw new Exception('error on testUpdateNode 0');
        }
        if (json_decode($node['data'], true)['other'] !== 'diff') {
            throw new Exception('error on testUpdateNode 1');
        }

        if ($this->database->updateNode(new NodeDTO('node3', 'Novo Label', 'application', 'database', false, ['other' => 'diff']))) {
            throw new Exception('error on testUpdateNode 2');
        }
    }

    public function testDeleteNode(): void {
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node2", "Node 02", "application", "database", \'{"running_on":"SRV011P"}\')');

        // Test deleting the node
        if (!$this->database->deleteNode('node2')) {
            throw new Exception('error on testDeleteNode - delete should succeed');
        }

        // Verify node was deleted
        $stmt = $this->pdo->prepare('select * from nodes where id = :id');
        $stmt->execute([':id' => 'node2']);
        $node = $stmt->fetch();
        if ($node !== false) {
            throw new Exception('error on testDeleteNode - node should be deleted');
        }

        // Test deleting non-existent node
        if ($this->database->deleteNode('node4')) {
            throw new Exception('error on testDeleteNode - should return false for non-existent node');
        }
    }

    public function testGetEdge(): void {
        $edge = $this->database->getEdge('node1', 'node2');
        if ($edge !== null) {
            throw new Exception('error on testGetEdge');
        }

        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node1", "Node 01", "application", "service", \'{}\')');
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node2", "Node 02", "business", "database", \'{}\')');
        $this->pdo->exec('insert into edges (id, source, target, label, data) values ("edge1", "node1", "node2", "label", \'{"a":"b"}\')');
        
        $edge = $this->database->getEdge('edge1');
        
        if ($edge->id !== 'edge1' || $edge->source !== 'node1' || $edge->target !== 'node2') {
            throw new Exception('error on testGetEdge');
        }
        if ($edge->data['a'] !== 'b') {
            throw new Exception('error on testGetEdge');
        }
    }

    public function testGetEdges(): void {
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');
        $stmt->execute([':id' => 'node1', ':label' => 'Node 01', ':category' => 'business', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV01'])]);
        $stmt->execute([':id' => 'node2', ':label' => 'Node 02', ':category' => 'business', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV02'])]);
        $stmt->execute([':id' => 'node3', ':label' => 'Node 03', ':category' => 'business', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV03'])]);

        $stmt = $this->pdo->query('select * from nodes');
        $nodes = $stmt->fetchAll();
        
        $this->pdo->exec('insert into edges (id, source, target, label, data) values ("edge1", "node1", "node2", "label", \'{"a":"b"}\')');
        $this->pdo->exec('insert into edges (id, source, target, label, data) values ("edge2", "node2", "node3", "label", \'{"b":"c"}\')');
        
        $edges = $this->database->getEdges();
        if (count($edges) !== 2) {
            throw new Exception('error on testGetEdges');
        }

        if ($edges[0]['id'] !== 'edge1' || $edges[0]['source'] !== 'node1' || $edges[0]['target'] !== 'node2' || $edges[0]['data']['a'] !== 'b') {
            throw new Exception('error on testGetEdges');
        }

        if ($edges[1]['id'] !== 'edge2' || $edges[1]['source'] !== 'node2' || $edges[1]['target'] !== 'node3' || $edges[1]['data']['b'] !== 'c') {
            throw new Exception('error on testGetEdges');
        }
    }

    public function testInsertEdge(): void {
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');
        $stmt->execute([':id' => 'node1', ':label' => 'Node 01', ':category' => 'application', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV01OP'])]);
        $stmt->execute([':id' => 'node2', ':label' => 'Node 02', ':category' => 'business', ':type' => 'database', ':data' => json_encode(['running_on' => 'SRV011P'])]);

        $this->database->insertEdge(new EdgeDTO('edge1', 'node1', 'node2', 'label', ['a' => 'b']));

        $stmt = $this->pdo->query('select * from edges where id = :id');
        $stmt->execute([':id' => 'edge1']);
        $dbEdge = $stmt->fetch();

        if ($dbEdge['id'] !== 'edge1' || $dbEdge['source'] !== 'node1' || $dbEdge['target'] !== 'node2') {
            throw new Exception('error on testInsertEdge 1');
        }

        if (json_decode($dbEdge['data'], true)['a'] !== 'b') {
            throw new Exception('error on testInsertEdge 2');
        }

        try {
            $this->database->insertEdge(new EdgeDTO('edge1', 'node1', 'node2', 'label', ['a' => 'b']));
        } catch(Exception $e) {
            if ($e->getMessage() !== "Database Error: Failed to insert edge. Edge already exists: edge1. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: edges.source, edges.target") {
                throw new Exception('unique constraint expected');
            }
            return;
        }

        throw new Exception('error on testInsertEdge 3');
    }

    public function testBatchInsertEdges(): void
    {
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');
        $stmt->execute([':id' => 'node1', ':label' => 'Node 01', ':category' => 'business', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV01OP'])]);
        $stmt->execute([':id' => 'node2', ':label' => 'Node 02', ':category' => 'application', ':type' => 'database', ':data' => json_encode(['running_on' => 'SRV011P'])]);
        $stmt->execute([':id' => 'node3', ':label' => 'Node 03', ':category' => 'application', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV012P'])]);
        
        $edges = [
            ['id' => 'edge1', 'source' => 'node1', 'target' => 'node2', 'label' => 'label1', 'data' => ['a' => 'b']],
            ['id' => 'edge2', 'source' => 'node2', 'target' => 'node3', 'label' => 'label2', 'data' => ['b' => 'c']],
        ];

        $this->database->batchInsertEdges($edges);

        $stmt = $this->pdo->prepare('select * from edges where source = :source and target = :target');
        
        foreach ($edges as $e) {
            $stmt->execute([':source' => $e['source'], ':target' => $e['target']]);
            $edge = $stmt->fetch();

            if ($edge['id'] !== $e['id'] || $edge['source'] !== $e['source'] || $edge['target'] !== $e['target']) {
                throw new Exception('error on batchInsertEdges 1');
            }
            foreach ($e['data'] as $key => $value) {
                if (json_decode($edge['data'], true)[$key] !== $value) {
                    throw new Exception('error on batchInsertEdges 2');
                }
            }
        }

        $edge = ['id' => 'edge1', 'source' => 'node1', 'target' => 'node2', 'label' => 'label1', 'data' => ['a' => 'b']];

        try {
            $this->database->batchInsertEdges([$edge]);
        } catch (Exception $e) {
            if ($e->getMessage() !== "Database Error: Failed to batch insert edges. Edge already exists: edge1. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: edges.source, edges.target") {
                throw new Exception('unique constraint expected');
            }
        }
    }

    public function testUpdateEdge(): void
    {
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');
        $stmt->execute([':id' => 'node1', ':label' => 'Node 01', ':category' => 'business', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV01OP'])]);
        $stmt->execute([':id' => 'node2', ':label' => 'Node 02', ':category' => 'application', ':type' => 'database', ':data' => json_encode(['running_on' => 'SRV011P'])]);

        $stmt = $this->pdo->prepare('insert into edges (id, source, target, label, data) values (:id, :source, :target, :label, :data)');
        $stmt->execute([':id' => 'edge1', ':source' => 'node1', ':target' => 'node2', ':label' => 'label', ':data' => json_encode(['a' => 'b'])]);


        $this->database->updateEdge(new EdgeDTO('edge1', 'node1', 'node2', 'label', ['x' => 'y']));

        $stmt = $this->pdo->prepare('select * from edges where id = :id');
        $stmt->execute([':id' => 'edge1']);
        $edge = $stmt->fetch();
        $edge['data'] = json_decode($edge['data'], true);

        if ($edge['id'] !== 'edge1' || $edge['source'] !== 'node1' || $edge['target'] !== 'node2') {
            throw new Exception('error on testUpdateEdge');
        }

        if ($edge['data']['x'] !== 'y') {
            throw new Exception('error on testUpdateEdge');
        }

        if ($this->database->updateEdge(new EdgeDTO('edge3', 'node3', 'node4', 'label', ['x' => 'y']))) {
            throw new Exception('error on testUpdateEdge');
        }
    }

    public function testDeleteEdge(): void {
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');
        $stmt->execute([':id' => 'node1', ':label' => 'Node 01', ':category' => 'business', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV01OP'])]);
        $stmt->execute([':id' => 'node2', ':label' => 'Node 02', ':category' => 'application', ':type' => 'database', ':data' => json_encode(['running_on' => 'SRV011P'])]);
        $stmt->execute([':id' => 'node3', ':label' => 'Node 03', ':category' => 'application', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV012P'])]);

        $stmt = $this->pdo->prepare('insert into edges (id, source, target, label, data) values (:id, :source, :target, :label, :data)');
        $stmt->execute([':id' => 'edge1', ':source' => 'node1', ':target' => 'node2', ':label' => 'label', ':data' => json_encode(['a' => 'b'])]);
        $stmt->execute([':id' => 'edge2', ':source' => 'node2', ':target' => 'node3', ':label' => 'label', ':data' => json_encode(['b' => 'c'])]);

        $this->database->deleteEdge('edge1');
        $this->database->deleteEdge('edge2');

        $stmt = $this->pdo->prepare('select * from edges where id = :id');
        $stmt->execute([':id' => 'edge1']);
        $edge = $stmt->fetch();
        if ($edge !== false) {
            throw new Exception('error on testDeleteEdge');
        }

        $stmt->execute([':id' => 'edge2']);
        $edge = $stmt->fetch();
        if ($edge !== false) {
            throw new Exception('error on testDeleteEdge');
        }

        if ($this->database->deleteEdge('edge6')) {
            throw new Exception('error on testDeleteEdge');
        }
    }

    public function testGetStatus(): void {
        $s = $this->database->getStatus();

        if (count($s) !== 0) {
            throw new Exception('error on testGetStatus');
        }

        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');
        $stmt->execute([':id' => 'node2', ':label' => 'Node 02', ':category' => 'application', ':type' => 'database', ':data' => json_encode(['running_on' => 'SRV011P'])]);

        $s = $this->database->getStatus();

        if (count($s) !== 1) {
            throw new Exception('error on testGetStatus');
        }

        if ($s[0][Status::STATUS_KEYNAME_NODE_ID] !== 'node2' || $s[0][Status::STATUS_KEYNAME_STATUS] !== null) {
            throw new Exception('error on testGetStatus');
        }
    }

    public function testGetNodeStatus(): void {
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');
        $stmt->execute([':id' => 'node1', ':label' => 'Node 01', ':category' => 'business', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV01OP'])]);

        // Test node with no status
        $s = $this->database->getNodeStatus('node1');

        if ($s->node_id !== 'node1' || $s->status !== null) {
            throw new Exception('error on testGetStatus');
        }

        if (!is_null($this->database->getNodeStatus('node2'))) {
            throw new Exception('error on testGetStatus');
        }
    }

    public function testUpdateNodeStatus(): void {
        
        try {
            $this->database->updateNodeStatus(new NodeStatusDTO('node1', 'healthy'));
        } catch(Exception $e) {
            if ($e->getMessage() !== "Database Error: Failed to update node status: node not found for status update: node1. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN KEY constraint failed") {
                throw new Exception('error on testUpdateNodeStatus - node should not exist');
            }
        }

        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node1", "Node 01", "business", "service", \'{"running_on":"SRV01OP"}\')');
        $this->database->updateNodeStatus(new NodeStatusDTO('node1', 'healthy'));

        $stmt = $this->pdo->prepare('select * from status where node_id = :node_id');
        $stmt->execute([':node_id' => 'node1']);
        $s = $stmt->fetch();

        if ($s['node_id'] !== 'node1' || $s['status'] !== 'healthy') {
            throw new Exception('error on testUpdateNodeStatus');
        }

        try {
            $this->database->updateNodeStatus(new NodeStatusDTO('node2', 'unhealthy'));
        } catch(Exception $e) {
            return;
        }
        throw new Exception('error on testUpdateNodeStatus');
    }

    public function testBatchUpdateNodeStatus(): void {
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node1", "Node 01", "business", "service", \'{"running_on":"SRV01OP"}\')');
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node2", "Node 02", "application", "database", \'{"running_on":"SRV011P"}\')');

        $statuses = [
            new NodeStatusDTO('node1', 'healthy'),
            new NodeStatusDTO('node2', 'unhealthy'),
        ];

        $this->database->batchUpdateNodeStatus($statuses);

        $stmt = $this->pdo->prepare('select * from status where node_id = :node_id');
        $stmt->execute([':node_id' => 'node1']);
        $s1 = $stmt->fetch();
        if ($s1['node_id'] !== 'node1' || $s1['status'] !== 'healthy') {
            throw new Exception('error on testBatchUpdateNodeStatus');
        }

        $stmt = $this->pdo->prepare('select * from status where node_id = :node_id');
        $stmt->execute([':node_id' => 'node2']);
        $s2 = $stmt->fetch();
        if ($s2['node_id'] !== 'node2' || $s2['status'] !== 'unhealthy') {
            throw new Exception('error on testBatchUpdateNodeStatus');
        }
    }

    public function testGetProject(): void
    {
        $project = $this->database->getProject('initial');
        if ($project !== null) {
            throw new Exception('error on testGetProject');
        }

        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("a", "Node A", "business", "service", \'{}\')');
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("b", "Node B", "business", "service", \'{}\')');
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("c", "Node C", "business", "service", \'{}\')');
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("d", "Node D", "business", "service", \'{}\')');
        
        $this->pdo->exec('insert into edges (id, source, target, label, data) values ("a-b", "a", "b", "connects", \'{}\')');
        $this->pdo->exec('insert into edges (id, source, target, label, data) values ("c-d", "c", "d", "connects", \'{}\')');

        $this->pdo->exec('insert into projects (id, name, author, data) values ("initial", "Initial Project", "admin", \'{}\')');
        
        $this->pdo->exec('insert into nodes_projects (node_id, project_id) values ("a", "initial")');
        $this->pdo->exec('insert into nodes_projects (node_id, project_id) values ("c", "initial")');
        $project = $this->database->getProject('initial');
        print_r($project);
        exit();

        // TODO: verify project content
        //print_r($project);
    }

    public function testGetProjects(): void
    {
        // TODO: 
        $projects = $this->database->getProjects();
        if (count($projects) !== 0) {
            throw new Exception('error on testGetProjects');
        }

        $this->pdo->exec('insert into projects (id, name, author, data) values ("initial", "Initial Project", "admin", \'{"nodes":["a","b"]}\')');

        $projects = $this->database->getProjects();
        if (count($projects) !== 1) {
            throw new Exception('error on testGetProjects');
        }

        if ($projects[0]['id'] !== 'initial' || $projects[0]['name'] !== 'Initial Project' || $projects[0]['author'] !== 'admin') {
            throw new Exception('error on testGetProjects');
        }
    }

    public function testInsertProject(): void
    {
        $this->database->insertProject(new ProjectDTO('initial', 'Initial Project', 'admin', ['nodes' => ['a', 'b']]));

        $stmt = $this->pdo->prepare('select * from projects where id = :id');
        $stmt->execute([':id' => 'initial']);
        $project = $stmt->fetch();
        
        try {
            $this->database->insertProject(new ProjectDTO('initial', 'Initial Project', 'admin', ['nodes' => ['a', 'b']]));
        } catch(Exception $e) {
            return;
        }
        throw new Exception('error on testInsertProject');
    }

    public function testUpdateProject(): void
    {
        $this->pdo->exec('insert into projects (id, name, author, data) values ("initial", "Initial Project", "admin", \'{}\')');

        $this->database->updateProject(new ProjectDTO('initial', 'Updated Project', 'admin', ['nodes' => ['c', 'd']]));

        
        $stmt = $this->pdo->prepare('select * from projects where id = :id');
        $stmt->execute([':id' => 'initial']);
        $projects = $stmt->fetchAll();

        if (count($projects) !== 1) {
            throw new Exception('error on testUpdateProject 1');
        }

        if ($projects[0]['id'] !== 'initial' || $projects[0]['name'] !== 'Updated Project' || $projects[0]['author'] !== 'admin') {
            throw new Exception('error on testUpdateProject 2');
        }

        if ($this->database->updateProject(new ProjectDTO('nonexistent', 'Name', 'admin', ['nodes' => []]))) {
            throw new Exception('error on testUpdateProject 3');
        }
    }

    public function testDeleteProject(): void
    {
        $this->pdo->exec('insert into projects (id, name, author, data) values ("initial", "Initial Project", "admin", \'{}\')');

        if (! $this->database->deleteProject('initial')) {
            throw new Exception('error on testDeleteProject 1');
        }

        $projects = $this->database->getProjects();
        if (count($projects) !== 0) {
            throw new Exception('error on testDeleteProject 2');
        }

        if ($this->database->deleteProject('nonexistent')) {
            throw new Exception('error on testDeleteProject 3');
        }
    }

    public function testGetLogs(): void {
        $logs = $this->database->getLogs(2);
        if (count($logs) > 0) {
            throw new Exception('error on testGetLogs');
        }

        $this->database->insertLog(new LogDTO('node', 'node1', 'update', null, null, 'admin', '127.0.0.1', new DateTimeImmutable()));
        sleep(1);
        $this->database->insertLog(new LogDTO('node', 'node2', 'update', null, null, 'admin', '127.0.0.1', new DateTimeImmutable()));

        $logs = $this->database->getLogs(2);
        if (count($logs) !== 2) {
            throw new Exception('error on testGetLogs');
        }

        if ($logs[0]['entity_id'] !== 'node2') {
            throw new Exception('error on testGetLogs');
        }

        if ($logs[1]['entity_id'] !== 'node1') {
            throw new Exception('error on testGetLogs');
        }
    }

    public function testInsertAuditLog(): void {
        $this->database->insertLog(new LogDTO('node', 'node1', 'update', null, null, 'admin', '127.0.0.1', new DateTimeImmutable()));
        $logs = $this->database->getLogs(2);
        if (count($logs) !== 1) {
            throw new Exception('error on testInsertAuditLog');
        }

        if ($logs[0]['entity_id'] !== 'node1') {
            throw new Exception('error on testInsertAuditLog');
        }
    }
}
