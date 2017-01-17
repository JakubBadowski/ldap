<?php

namespace Badowski\Ldap;

class Ldap
{
	const ERROR_MSG_CONNECT = "Nie można się połączyć do usługi LDAP";
	const ERROR_MSG_LOGIN = "Logowanie do usługi LDAP nie powiodło się!";

	// Konfiguracja połączenia z usługą LDAP
	private $host;
	private $port = 389;
	private $login;
	private $pass;
	private $base_dn;
	private $ldapconn;
	// private $_sr;

	public function __construct($configArray) // TODO: dodaj konfigurację - przez dependency injection
	{
		$this->setConfig($configArray);
		$this->connnect();
	}

	private function setConfig($cArray)
	{
		$this->host = $cArray['host'];

		if (isset($cArray['port'])) {
			$this->port = $cArray['port'];
		}

		$this->login = $cArray['login'];
		$this->pass = $cArray['pass'];
		$this->base_dn = $cArray['base_dn'];
	}

	private function connnect()
	{ 
		try {
			$this->ldapconn = @ldap_connect($this->host, $this->port) or die(self::ERROR_MSG_CONNECT);
			$ldapbind = @ldap_bind($this->ldapconn, $this->login, $this->pass) or die(self::ERROR_MSG_LOGIN);
			// var_dump($ldapbind);
		} catch (Exceptiom $e) {
			//
		}
	}


	public function getUsersList()
	{
		$filter = '(&(objectClass=user)(objectCategory=person)(sn=*))';
		// Pobieranie listy użytkowników z LDAP do selecta
		$this->search($filter);
		// $this->_sort( 'sn' );
		
		$data = $this->getArrayData();
		// $count = count($data);

		if ( $data['count'] > 0 ) return $this->_prepareDateForAllUser( $data );
		else return null;
	}

	public function getUsersCount()
	{
		$filter = '(&(objectClass=user)(objectCategory=person)(sn=*))';
		// Pobieranie listy użytkowników z LDAP do selecta
		$this->search($filter);
		// ldap_count_entries();
		$count = $this->getCount();
		return $count;
	}

	public function isUserInLdap($sam)
	{
		// Zabezpieczenie przed LDAP injection 
		$this->_search('(&(objectClass=user)(objectCategory=person)(samaccountname='.$sam.'))');
		$data = $this->_getData();
		
		if ( $data['count'] === 1 ) return true;
		else return false;
	}
}