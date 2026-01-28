<?php

if(count($argv) != 2) {
    print("filename missing\n");
    exit();
}

$filename = $argv[1];

$coverageData = json_decode(file_get_contents($filename), true);

echo "<html><head><meta charset='UTF-8'><style>
body { font-family: monospace; font-size: 14px; margin: 0; }
.covered { background: #0f0; }
.not-executable { background: #fff652ff; display: none;}
.not-executable { background: #fff652ff; display: block;}
.line { padding: 2px 10px; white-space: pre; border-left: 3px solid transparent; }
.covered { border-left-color: #0f0; }
.not-covered { border-left-color: #f00; background-color: rgba(253, 18, 18, 0.52);}
.num { color: #666; width: 50px; display: inline-block; text-align: right; margin-right: 10px; }
h2 {background: #fff; padding: 10px; margin: 20px 0 0 0; position: sticky; top: 0; }
</style></head><body>";

$names = [];
foreach($coverageData as $file => $d) {
    if (strpos($file, 'tests.php') !== false || !file_exists($file)) continue;
    if (strpos($file, 'TestAbstractTest.php') !== false || !file_exists($file)) continue;
    if (strpos($file, 'Interface.php') !== false || !file_exists($file)) continue;

    if (str_contains($file, '/tests/')) {
        continue;
    }

    $names[] = $file;
}
sort($names);

foreach ($names as $name) {
    $file = $name;
    $lines = $coverageData[$name];
    $content = file($file);

    // mark } as checked
    foreach ($content as $num => $line) {
        if(trim($line) == '{') {$lines[$num+1] = 1;}
        if(trim($line) == '}') {$lines[$num+1] = 1;}
        if(trim($line) == '') {$lines[$num+1] = 1;}
    }

    $covered = count(array_filter($lines, fn($c) => $c > 0));
    $total = count($lines);
    $pct = $total > 0 ? round(($covered/$total)*100, 2) : 0;
    
    echo "<h2>" . htmlspecialchars($file) . " - {$pct}% ({$covered}/{$total})</h2>";
    
    foreach ($content as $num => $line) {
        $num++;
        $class = isset($lines[$num]) ? ($lines[$num] > 0 ? 'covered' : 'not-covered') : 'not-executable';
        echo "<div class='line {$class}'><span class='num'>{$num}</span>" . htmlspecialchars($line) . "</div>";
    }
}

echo "</body></html>";