<?php

$files = glob(dirname(__DIR__) . "/www/templates/*.html");

$content = "\$DATA_TEMPLATES = [\n";

foreach ($files as $file) {
    $data = base64_encode(file_get_contents($file));
    $eTag = md5($data);
    $filename = basename($file, '.html');
    $content .= "    \"{$filename}\" => [\n";
    $content .= "        \"data\" => '{$data}',\n";
    $content .= "        \"etag\" => '{$eTag}',\n";
    $content .= "    ],\n";
}

$content .= "];\n";

file_put_contents(dirname(__DIR__) . "/compiled/compiled_templates.php", $content);

echo "HTML template list generated successfully.\n";