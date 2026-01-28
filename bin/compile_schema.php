<?php

$schemaFile = dirname(__DIR__) . '/sql/schema.sql';

$content = "\$SQL_SCHEMA = \"\n";
$content .= file_get_contents($schemaFile);
$content .= "\";\n";

file_put_contents(dirname(__DIR__) . "/compiled/compiled_schema.php", $content);

echo "Schema file compiled successfully.\n";
