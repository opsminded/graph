# Opsminded Graph

A small PHP graph library with basic graph structures and algorithms.

## Quick start

Install with Composer:

```bash
composer require opsminded/graph
```

Example usage:

```php
use Opsminded\Graph\UndirectedGraph;
use Opsminded\Graph\Node;
use Opsminded\Graph\Edge;
use Opsminded\Graph\Algorithms\BFS;

$g = new UndirectedGraph();
$a = new Node('a');
$b = new Node('b');
$g->addEdge(new Edge($a, $b));

$visited = [];
BFS::traverse($g, 'a', function($n) use (&$visited) {
    $visited[] = $n->getId();
});

print_r($visited);
```

See `tests/` for a simple example test.
# graph
PHP Graph library
