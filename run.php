<?php
require_once __DIR__ . "/src/Badowski/Ldap.php";
require_once __DIR__ . "/config.php";

echo "Testowanie LDAP-a" . PHP_EOL;

use Badowski\Ldap\Ldap;

$ldap = new Ldap($config['ldap']);
// $usersList = $ldap->getUsersList();	
// $userCount = $ldap->getUsersCount();

// $usersList = $ldap->getUsersInGroupCount("_INIG_ALL");
$usersList = $ldap->getUsersInGroup("admini");
var_dump($usersList);

// $is = $ldap->isUserInLdap('baran');
// var_dump($is);

// $user = $ldap->getUserBySam('sikorak');
// var_dump($user);

// $c = $ldap->getUsersInGroupCount("skarbnicy");
// print_r($c);

echo PHP_EOL;
// var_dump($userCount);