<?php
echo json_encode([
    'propel' => [
        'paths' => [
            'schemaDir' => $schemaDir,
            'phpDir' => $phpDir,
        ],
    ]
], JSON_PRETTY_PRINT);
