<?php echo json_encode([
    'propel' => [
        'database' => [
            'connections' => [
                'default' => [
                    'adapter' => $rdbms,
                    'dsn' => $dsn,
                    'user' => $user,
                    'password' => '',
                    'settings' => [
                        'charset' => $charset
                    ]
                ]
            ]
        ]
    ]
], JSON_PRETTY_PRINT);
