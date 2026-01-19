<?php

$files = glob(dirname(__DIR__) . "/www/images/*.png");

$content = "<?php\n";

$content .= "\$images = [\n";

foreach ($files as $file) {
    $base64 = base64_encode(file_get_contents($file));
    $eTag = md5($base64);
    $filename = basename($file, '.png');
    $content .= "    \"{$filename}\" => [\n";
    $content .= "        \"data\" => '{$base64}',\n";
    $content .= "        \"etag\" => '{$eTag}',\n";
    $content .= "    ],\n";
}

$content .= "];\n";

file_put_contents(dirname(__DIR__) . "/compiled/compiled_images.php", $content);

echo "PNG file list generated successfully.\n";