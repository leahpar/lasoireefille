<?php

$_SERVER['SERVER_ADDR'] = $argv[1];
require 'config.php';

$db = $config['db'];
$pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'] . ";charset=" . $db['charset'], $db['user'], $db['pass']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$sql = "UPDATE people SET attendance = 0";
$pdo->query($sql);

die('ok');
