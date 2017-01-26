<?php

namespace Badowski\Ldap;

class Ldap
{
	const ERROR_MSG_CONNECT = "Nie można się połączyć do usługi LDAP";
	const ERROR_MSG_LOGIN = "Logowanie do usługi LDAP nie powiodło się!";
	const SHOW_KEYS = ['samaccountname', 'cn', 'givenname', 'sn', 'description', 'title', 'mail', 'telephonenumber', 'homedirectory', 'department', 
						'physicaldeliveryofficename'];

	// Konfiguracja połączenia z usługą LDAP
	private $host;
	private $port = 389;
	private $login;
	private $pass;
	private $base_dn;
	private $ldapconn;
	private $sr;
	private $data;
	private $arrayData;

	public function __construct($configArray)
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

		// var_dump($this->ldapconn);
		// var_dump($ldapbind);
	}

	/**
	* @param string samacountname użytkownika
	* @return stdClass obiekt jednego użytkownika lub null
	*/
	public function getUserBySam( $sam )
	{
		$sam = ldap_escape($sam);
		$this->search('(&(objectClass=user)(objectCategory=person)(samaccountname='.$sam.'))');
		
		if ($this->setData()) return $this->prepareData();
		else return null;
	}

	/**
	* @param string nazwa grupy 
	* @return array tablica obiektów - Użytkowników
	*/
	public function getUsersInGroup($groupName)
	{
		$groupName = ldap_escape($groupName);

		$filter = '(&(objectClass=user)(objectCategory=person)(memberOf=CN='.$groupName.','.$this->base_dn.'))';
		$this->search($filter);
		$this->setArrayData();

		return $this->prepareDataArray();
	}

	/**
	*	Funkcja zwraca liczbę użytkowników w danej grupie
	*/
	public function getUsersInGroupCount($groupName)
	{
		$groupName = ldap_escape($groupName);

		$filter = '(&(objectClass=user)(objectCategory=person)(memberOf=CN='.$groupName.','.$this->base_dn.'))';
		$this->search($filter);
		$count = $this->getCount();

		return $count;
	}

	public function isUserInGroup()
	{
		
	}

	private function search( $filter )
	{
		$this->sr = ldap_search($this->ldapconn, $this->base_dn, $filter);
	}

	private function sort( $po='cn' )
	{
		ldap_sort($this->_ds, $this->_sr, $po);
	}

	private function setData()
	{
		$data = ldap_get_entries($this->ldapconn, $this->sr);
		
		if ($data['count']) {
			$this->data = $data[0];
			return true; 
		} else {
			return false;
		}
	}

	private function setArrayData()
	{
		$this->arrayData = ldap_get_entries($this->ldapconn, $this->sr);
	}

	private function getCount()
	{
		return ldap_count_entries ($this->ldapconn, $this->sr);
	}

	private function prepareData()
	{
		$uAccount = new \stdClass;

		foreach (self::SHOW_KEYS as $key) {
			$uAccount->{$key} = isset($this->data[$key]) ? $this->data[$key][0] : null;
		}

		return $uAccount;
	}

	private function prepareDataArray()
	{
		$new = [];

		for ($i = 0; $i < $this->arrayData['count']; $i++)
		{			
			$this->data = $this->arrayData[$i];
			$new[] = $this->prepareData();
		}

		return $new;
	}

	// public function getUsersList()
	// {
	// 	$filter = '(&(objectClass=user)(objectCategory=person)(sn=*))';
	// 	// Pobieranie listy użytkowników z LDAP do selecta
	// 	$this->search($filter);
	// 	// $this->_sort( 'sn' );
	// 	// $sr = ldap_search($this->ldapconn, 'cn=Users,dc=inig,dc=local', $filtr);
		
	// 	$data = $this->setArrayData();

	// 	if ( $data['count'] > 0 ) return $this->_prepareDateForAllUser( $data );
	// 	else return null;
	// }



	// public function getUsersCount()
	// {
	// 	$filter = '(&(objectClass=user)(objectCategory=person)(sn=*)(memberOf=CN=_INIG_ALL,'.$this->base_dn.'))';
	// 	// Pobieranie listy użytkowników z LDAP do selecta
	// 	$this->search($filter);
	// 	$count = $this->getCount();

	// 	return $count;
	// }

}