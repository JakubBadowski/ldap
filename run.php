<?php
require_once __DIR__ .  "/src/Badowski/Ldap.php";
require_once __DIR__ . "/config.php";

echo "Testowanie LDAP-a" . PHP_EOL;

use Badowski\Ldap\Ldap;

$ldap = new Ldap($config['ldap']);
// $usersList = $ldap->getUsersList();	
$userCount = $ldap->getUsersCount();

// print_r($usersList);
var_dump($userCount);