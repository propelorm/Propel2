<?php echo "<?php\n"; ?>
return [
    'propel' => [
        'database' => [
            'connections' => [
                'default' => [
                    'adapter' => '<?php echo $rdbms ?>',
                    'dsn' => '<?php echo $dsn ?>',
                    'user' => '<?php echo $user ?>',
                    'password' => '<?php echo $password ?>',
                    'settings' => [
                        'charset' => '<?php echo $charset ?>'
                    ]
                ]
            ]
        ]
    ]
];
