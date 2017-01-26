<?php
require_once __DIR__ . "/src/Badowski/Ldap.php";
require_once __DIR__ . "/config.php";

echo "Testowanie LDAP-a" . PHP_EOL;

use Badowski\Ldap\Ldap;

$ldap = new Ldap($config['ldap']);
// $usersList = $ldap->getUsersList();	
// $userCount = $ldap->getUsersCount();

// $usersList = $ldap->getUsersInGroupCount("groupname");
$usersList = $ldap->getUsersInGroup("groupname");
var_dump($usersList);

// $is = $ldap->isUserInLdap('kowalski');
// var_dump($is);

// $user = $ldap->getUserBySam('kowalski');
// var_dump($user);

echo PHP_EOL;