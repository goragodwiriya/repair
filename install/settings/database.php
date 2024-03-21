<?php
/* settings/database.php */

return array(
    'mysql' => array(
        'dbdriver' => 'mysql',
        'username' => 'root',
        'password' => '',
        'dbname' => 'repair',
        'prefix' => 'rp',
    ),
    'tables' => array(
        'user' => 'user',
        'category' => 'category',
        'language' => 'language',
        'repair' => 'repair',
        'inventory' => 'inventory',
    ),
);
