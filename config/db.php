<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=127.0.0.1;dbname=db_admin',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8',
    'tablePrefix' => 'gzh_',
    'attributes' => [PDO::ATTR_PERSISTENT => false], //长连接
];
