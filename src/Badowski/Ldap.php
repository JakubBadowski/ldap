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

	// public function checkCredentials( $login, $pass )
	// {
	// 	$ldapconn = ldap_connect('10.2.100.51') or die($php_errormsg);
	// 	// $ldapconn = ldap_bind($ds, $login.'@inig', $pass) or die($php_errormsg);

	// 	if ($ldapconn) 
	// 	{
	// 	    // binding to ldap server
	// 	    $ldapbind = ldap_bind($ldapconn, $login.'@inig', $pass);

	// 	    // verify binding
	// 	    if ($ldapbind) {
	// 	        return true;
	// 	    } else {
	// 	        return false;
	// 	    }
	// 	} else return false;
	// }

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









	public function getKierownik( $department )
	{
		// Zwraca kierownika zakładu/ działu
		// wtedy gdy: należy do danego zakładu oraz do grupy "kierownicy"
		$this->_search('(&(objectClass=user)(objectCategory=person)(department='.$department.')(memberOf=CN=kierownicy,CN=Users,DC=inig,DC=local))');
		$this->_sort();
		$data = $this->_getData();

		if ( $data['count'] === 1 ) return $data;
		else return null;
	}

	public function getUsersInDepartment( $department )
	{
		$this->_search('(&(objectClass=user)(objectCategory=person)(department='. $department .'))');
		$this->_sort('sn');
		$data = $this->_getData();

		return $data;
	}
	// end refaktoryzacja

	public function getOneUser( $sam )
	{
		// Funkcja zwraca obiekt User, jeśli taki istnieje, lub null w przeciwnym wypadku
		$this->_search('(&(objectClass=user)(objectCategory=person)(samaccountname='.$sam.'))');
		$this->_sort();
		$data = $this->_getData();

		if ( $data['count'] === 1 ) return $this->_prepareDateForOneUser( $data );
		else return null;
	}

	/**
	*	Funkcja sprawdza czy dany użytkownik istnieje
	*/
	public function isUserInLdap($sam)
	{
		// Zabezpieczenie przed LDAP injection 
		$sam = ldap_escape($sam);

		$this->search('(&(objectClass=user)(objectCategory=person)(samaccountname='.$sam.'))');
		$this->setArrayData();
		
		if ( $this->arrayData['count'] === 1 ) return true;
		else return false;
	}

	public function getUserCommonName( $sam )
	{
		// Funkcja zwraca obiekt User, jeśli taki istnieje, lub null w przeciwnym wypadku
		$this->_search('(&(objectClass=user)(objectCategory=person)(samaccountname='.$sam.'))');
		$this->_sort();
		$data = $this->_getData();

		if ( $data['count'] === 1 ) return $data[0]['cn'][0] ;
		else return null;
	}

	public function getUserDepartment( $sam )
	{
		// Funkcja zwraca obiekt User, jeśli taki istnieje, lub null w przeciwnym wypadku
		$this->_search('(&(objectClass=user)(objectCategory=person)(samaccountname='.$sam.'))');
		$this->_sort();
		$data = $this->_getData();

		if ( $data['count'] === 1 ) return isset($data[0]['department'][0]) ? $data[0]['department'][0] : 'brak' ;
		else return null;
	}






	private function _prepareSpecialType( $departments )
	{
		if ( in_array('DZ', $departments) ) {

			return 5;

		} elseif ( in_array('SP', $departments ) ) {

			return 6;
		}
		else return null;
	}






	private function _getUserListOfDepartment( $dep )
	{
		// Pobieranie listy użytkowników z LDAP do selecta
		// Przypadek ktoś należy do kilku zakładów
		$this->_search('(&(objectClass=user)(objectCategory=person)(department='. $dep .'))');
		$this->_sort();
		$data = $this->_getData();

		if ( $data['count'] > 0 ) return $this->_prepareDateForAllUser( $data );
		else return [];
	}

	private function _getSubordList($accountType, $departments, $sam )
	{
		if ( count($departments) > 1 ) {

			// Przypadek Warszawy
			if ( $sam === 'huszal' ) {
				$data1 = $this->_getSubordListForKZ( 'WN', 'huszal' );
				$data2 = $this->_getSubordListForKZ('DLW', 'huszal' );

				$data = array_merge($data1, $data2);

				return $data;
			}

		} else {

			$department = $departments[0];

			// Jeśli dyrektor Pionu
			if ( $accountType === 4 ) {
				return $this->_getSubordListForD( );
			}
			// Jeśli dyrektor Pionu
			elseif ( $accountType === 3 ) {
				return $this->_getSubordListForDP( $department );
			}
			// Jeśli kierownik
			elseif ( $accountType === 2 )
			{
				return $this->_getSubordListForKZ( $department, $sam );
			}
			else return null;

			// return $subord;
		}
	}

	private function _getSubordListForD(  )
	{
		$subord = []; // Podlegający pod dyrektora pionu

		// Symbol zakładu dyrektora jest symbolem pionu
		// $pion = $department;

		$sql = "SELECT tab2.symbol FROM piony AS tab1, piony AS tab2 WHERE tab1.symbol='D' AND tab1.id=tab2.ref";
		$piony = $this->_app['db']->fetchAll($sql, []);

		foreach ($piony as $pion) {

			// TODO: to jest tymczas
			$subord = array_merge($subord, $this->_getSubordListForDP( $pion['symbol'] ));
		}

		return $subord;
	}

	// Pobieranie danych o podwładnych dla dyrektora pionu
	private function _getSubordListForDP( $department )
	{
		// $subords = [];
		$subord = []; // Podlegający pod dyrektora pionu

		// Symbol zakładu dyrektora jest symbolem pionu
		$piony = [ $department ];

		foreach ( $piony as $pion ) {

			// Wyciąga symbole zakładów z danego pionu
			$sql = "SELECT tab2.symbol FROM piony AS tab1, piony AS tab2 WHERE tab1.symbol='$pion' AND tab1.id=tab2.ref";
			$zaklady = $this->_app['db']->fetchAll($sql, []);

			// Wyciąga pracowników wszystkich zakładów podległych pod dany pion
			foreach ($zaklady as $item) {

				$i = new \stdClass();
				$i->symbol = $item['symbol'];
				$i->users = $this->_getUserListOfDepartment( $item['symbol'] );

				$subord[] = $i;
			}

			$p = new \stdClass();
			$p->symbol = $pion;
			$p->zaklady = $subord;

			$subords[] = $p;
		}

		return $subords;
	}

	private function _getSubordListForKZ( $department, $sam )
	{
		//Wyłączenie kierownika z listy pracowników zakładu
		$this->_search('(&(objectClass=user)(objectCategory=person)(department='.$department.')(!(samaccountname='.$sam.')))');
		$this->_sort();
		$data = $this->_getData();

		$zaklad = new \stdClass();
		$zaklad->symbol = $department;
		$zaklad->users = $this->_prepareDateForAllUser( $data );

		$pion = new \stdClass();
		$pion->symbol = 'DS';
		$pion->zaklady = [ $zaklad ];

		$piony[] = $pion;

		return $piony;
	}


	private function _prepareDepartments( $departmentStr )
	{
		// Zwraca tablicę jednostek organizacyjnych
		$departments = explode('/', $departmentStr);

		return $departments;
	}

	private function _prepareAccountType( $sam, $departments )
	{
		// Sprawdza czy w $department jest DOW/WN - wtedy osoba należy do więcej niż jednego zakładu

		// var_dump($departments);
		// exit;
		// if ( count($departments) > 1 ) {
		foreach ($departments as $department) {
			return $this->_prepareAccountTypeForOne( $sam, $department );
		}
		// }

	}

	private function _prepareAccountTypeForOne( $sam, $department )
	{
		// TODO: wybrać to z bazy danych
		// Czy Dyrektor?
		if ( $department === 'D' ) return 4; // Typ konta poziom drugi (Dyrektr Instytutu)
		elseif ( in_array($department, ['DS', 'DG', 'DT', 'DOK', 'DOW', 'DE', 'DX'] ) ) return 3; // Dyraktor pionu/ oddziału (w Warszawie)

		// Czy kierownik?
		$this->_search('(&(objectClass=user)(objectCategory=person)(samaccountname='.$sam.')(memberOf=CN=kierownicy,CN=Users,DC=inig,DC=local))');
		$data = $this->_getData();

		if ( $data['count'] === 1 ) return 2; // Typ konta poziom drugi (kierownik)
		else return 1; // Typ konta poziom 1 (pracownik)

		// Jeśli nalży do grupy DZ
		$type = 'Test type';

		return $type;
	}

	private function _prepareAccountTypeName( $id )
	{
		$sql = "SELECT name FROM typ_konta WHERE id = ". (int) $id;
		$name = $this->_app['db']->fetchColumn($sql, []);

		return $name;
	}

	private function _prepareImages( $name )
	{
		// Obrazki
		$image = "http://szukaj.inig.pl/public/images/people/$name.jpg";

		// TODO:

		// $size = \getimagesize( $image );


		// var_dump($image);
		// exit;


		// if ( !is_array( $size ) )
		// {
		// 	// $image = "http://szukaj.inig.pl/public/images/people/$name";
		// 	$image = "http://szukaj.inig.pl/public/images/noimg.jpg";
		// }
		// else {
		// 	# none
		// }

		return $image;
	}

}