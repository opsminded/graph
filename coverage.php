<?php
// generate-minimal-report.php

$coverageData = json_decode(file_get_contents('coverage.json'), true);

echo "<html><head><meta charset='UTF-8'><style>
body { font-family: monospace; font-size: 12px; background: #222; color: #ddd; margin: 0; }
.covered { background: #1a4d1a; }
.not-covered { background: #4d1a1a; }
.not-executable { background: #2a2a2a; color: #666; }
.line { padding: 2px 10px; white-space: pre; border-left: 3px solid transparent; }
.covered { border-left-color: #0f0; }
.not-covered { border-left-color: #f00; }
.num { color: #666; width: 50px; display: inline-block; text-align: right; margin-right: 10px; }
h2 { background: #333; padding: 10px; margin: 20px 0 0 0; position: sticky; top: 0; }
</style></head><body>";

foreach ($coverageData as $file => $lines) {
    if (strpos($file, 'vendor/') !== false || !file_exists($file)) continue;
    
    $content = file($file);
    $covered = count(array_filter($lines, fn($c) => $c > 0));
    $total = count($lines);
    $pct = $total > 0 ? round(($covered/$total)*100, 2) : 0;
    
    echo "<h2>" . htmlspecialchars($file) . " - {$pct}% ({$covered}/{$total})</h2>";
    
    foreach ($content as $num => $line) {
        $num++;
        $class = isset($lines[$num]) 
            ? ($lines[$num] > 0 ? 'covered' : 'not-covered') 
            : 'not-executable';
        
        echo "<div class='line {$class}'><span class='num'>{$num}</span>" 
            . htmlspecialchars($line) . "</div>";
    }
}

echo "</body></html>";