<?php

$coverageData = json_decode(file_get_contents('coverage.json'), true);

echo "<html><head><meta charset='UTF-8'><style>
body { font-family: monospace; font-size: 14px; margin: 0; }
.covered { background: #0f0; }
.not-executable { background: #fff652ff; }
.line { padding: 2px 10px; white-space: pre; border-left: 3px solid transparent; }
.covered { border-left-color: #0f0; }
.not-covered { border-left-color: #f00; background-color: rgba(253, 18, 18, 0.52);}
.num { color: #666; width: 50px; display: inline-block; text-align: right; margin-right: 10px; }
h2 {background: #fff; padding: 10px; margin: 20px 0 0 0; position: sticky; top: 0; }
</style></head><body>";

foreach ($coverageData as $file => $lines) {
    if (strpos($file, 'vendor/') !== false || !file_exists($file)) continue;
    
    if (str_contains($file, '/tests/')) {
        continue;
    }

    $content = file($file);
    $covered = count(array_filter($lines, fn($c) => $c > 0));
    $total = count($lines);
    $pct = $total > 0 ? round(($covered/$total)*100, 2) : 0;
    
    echo "<h2>" . htmlspecialchars($file) . " - {$pct}% ({$covered}/{$total})</h2>";
    
    foreach ($content as $num => $line) {
        $num++;
        if (trim($line) == '') {
        //     $class = 'covered';
        // } elseif(trim($line) == '{' || trim($line) == '}') {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'function')) {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'SELECT')) {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'INSERT')) {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'UPDATE')) {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'VALUES')) {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'SET')) {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'WHERE')) {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'FROM')) {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'LEFT JOIN')) {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'ON')) {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'ORDER BY')) {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'LIMIT')) {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'CREATE')) {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'TEXT')) {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'DATETIME')) {
        //     $class = 'covered';
        // } elseif (str_contains($line, 'INTEGER')) {
        //     $class = 'covered';
        // } elseif(trim($line) == ');') {
        //     $class = 'covered';
        // } elseif(trim($line) == ')");') {
        //     $class = 'covered';
        // } elseif(str_starts_with(trim($line), "'")) {
        //     $class = 'covered';
        // } elseif(str_starts_with(trim($line), "<?php")) {
        //     $class = 'covered';
        // } elseif(str_starts_with(trim($line), "declare")) {
        //     $class = 'covered';
        // } elseif(str_starts_with(trim($line), "$") && (str_ends_with(trim($line), ",") || str_ends_with(trim($line), "s"))) {
        //     $class = 'covered';
        // } elseif(str_starts_with(trim($line), "string $")) {
        //     $class = 'covered';
        // } elseif(str_starts_with(trim($line), "?array $")) {
        //     $class = 'covered';
        // } elseif(trim($line) == '];') {
        //     $class = 'covered';
        // } elseif(str_starts_with($line, 'class')) {
        //     $class = 'covered';
        // } elseif(str_starts_with(trim($line), 'public')) {
        //     $class = 'covered';
        // } elseif(str_starts_with(trim($line), 'private')) {
        //     $class = 'covered';
        // } elseif(str_starts_with($line, 'interface')) {
        //     $class = 'covered';
        // } elseif(str_starts_with($line, 'final class')) {
        //     $class = 'covered';
        // } elseif(str_starts_with($line, '#')) {
        //     $class = 'covered';
        // } elseif(str_starts_with(trim($line), ']);')) {
        //     $class = 'covered';
        // } elseif(str_starts_with(trim($line), '//')) {
        //     $class = 'covered';
        // } elseif(str_starts_with(trim($line), 'try')) {
        //     $class = 'covered';
        //     } elseif(str_starts_with(trim($line), 'return [')) {
        //     $class = 'covered';
        } else {
            $class = isset($lines[$num]) ? ($lines[$num] > 0 ? 'covered' : 'not-covered') : 'not-executable';
        }

        echo "<div class='line {$class}'><span class='num'>{$num}</span>" 
            . htmlspecialchars($line) . "</div>";
    }
}

echo "</body></html>";