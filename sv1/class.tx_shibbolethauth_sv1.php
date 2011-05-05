<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Tamer Erdoğan <tamer.erdogan@univie.ac.at>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * Service "Shibboleth-Authentication" for the "tx_shibbolethauth" extension.
 *
 * @author	Tamer Erdoğan <tamer.erdogan@univie.ac.at>
 * @package	TYPO3
 * @subpackage	tx_shibbolethauth
 */
class tx_shibbolethauth_sv1 extends tx_sv_authbase {
	public $prefixId = 'tx_shibbolethauth_sv1';		// Same as class name
	public $scriptRelPath = 'sv1/class.tx_shibbolethauth_sv1.php';	// Path to this script relative to the extension dir.
	public $extKey = 'shibboleth_auth';	// The extension key.
	public $pObj;
	
	protected $conf;
	
	private $remoteUser;
	
	/**
	 * Inits some variables
	 *
	 * @return	void
	 */
	function init() {
		$this->conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
		if (empty($this->conf['remoteUser'])) $this->conf['remoteUser'] = 'REMOTE_USER';
		$this->remoteUser = $_SERVER[$this->conf['remoteUser']];
		
		return parent::init();
	}
	
	/**
	 * Initialize authentication service
	 *
	 * @param	string		Subtype of the service which is used to call the service.
	 * @param	array		Submitted login form data
	 * @param	array		Information array. Holds submitted form data etc.
	 * @param	object		Parent object
	 * @return	void
	 */
	function initAuth($mode, $loginData, $authInfo, $pObj) {
		if (empty($this->remoteUser)) {
			$target = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
			if (TYPO3_MODE == 'FE') {
				if(stristr($target, '?') === FALSE) $target .= '?';
				else $target .= '&';
				$target .= 'logintype=login&pid='.$this->conf['storagePid'];
			}
			$redirectUrl = $this->conf['loginHandler'] . '?target=' . urlencode($target);
			$redirectUrl = t3lib_div::sanitizeLocalUrl($redirectUrl);
			t3lib_utility_Http::redirect($redirectUrl);
		} else if ($_SERVER['AUTH_TYPE'] == 'shibboleth') {
			$loginData['uname'] = $this->remoteUser;
			//$loginData['uident'] = $_SERVER['Shib_Session_ID'];
			$loginData['status'] = 'login';
			parent::initAuth($mode, $loginData, $authInfo, $pObj);
		}
	}
	
	function getUser() {
		$user = false;
		
		if ($this->login['status']=='login' && $this->remoteUser)	{
			$user = $this->fetchUserRecord($this->remoteUser);
			
			if(!is_array($user) || empty($user)) {
				if ($this->authInfo['loginType'] == 'FE' && !empty($this->remoteUser) && $this->conf['enableAutoImport']) {
					$this->importFEUser();
				} else {
					// Failed login attempt (no username found)
					$this->writelog(255,3,3,2,
						"Login-attempt from %s (%s), username '%s' not found!!",
						Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->remoteUser));
					t3lib_div::sysLog(sprintf("Login-attempt from %s (%s), username '%s' not found!", $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->remoteUser), 'Core', 0);
				}
			} else {
				if ($this->authInfo['loginType'] == 'FE' && $this->conf['enableAutoImport']) {
					$this->updateFEUser();
				}
				if ($this->writeDevLog) t3lib_div::devLog('User found: '.t3lib_div::arrayToLogString($user, array($this->db_user['userid_column'],$this->db_user['username_column'])), 'tx_sv_auth');
			}
			if ($this->authInfo['loginType'] == 'FE') {
				// the fe_user was updated, it should be fetched again.
				$user = $this->fetchUserRecord($this->remoteUser);
			}
		}
		return $user;
	}
	
	/**
	 * @return	boolean
	 */
	protected function importFEUser() {
		$this->writelog(255,3,3,2,
			"Importing user %s (%s), username '%s' not found!!",
			Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->remoteUser));
		
		$user = array('crdate' => time(),
			'tstamp' => time(),
			'pid' => $this->conf['storagePid'],
			'username' => $this->remoteUser,
			'password' => t3lib_div::shortMD5(uniqid(rand(), true)),
			'email' => $_SERVER[$this->conf['mail']],
			'name' => $_SERVER[$this->conf['displayName']],
			'usergroup' => $this->getFEUserGroups(),
			);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->authInfo['db_user']['table'], $user);
	}
	
	/**
	 * @return	boolean
	 */
	protected function updateFEUser() {
		$this->writelog(255,3,3,2,
			"Updating user %s (%s), username '%s' not found!!",
			Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->remoteUser));
		
		$where = "username = '".$this->remoteUser."' AND pid = " . $this->conf['storagePid'];
		$user = array('tstamp' => time(),
			'username' => $this->remoteUser,
			'password' => t3lib_div::shortMD5(uniqid(rand(), true)),
			'email' => $_SERVER[$this->conf['mail']],
			'name' => $_SERVER[$this->conf['displayName']],
			'usergroup' => $this->getFEUserGroups(),
			);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->authInfo['db_user']['table'], $where, $user);
	}
	
	protected function getFEUserGroups() {
		$feGroups = array();
		if (!empty($_SERVER[$this->conf['eduPersonAffiliation']])) {
			$affiliation = explode(';', $_SERVER[$this->conf['eduPersonAffiliation']]);
			array_walk($affiliation, create_function('&$v,$k', '$v = preg_replace("/@.*/", "", $v);'));
			
			// insert the affiliations in fe_groups if they are not there.
			foreach ($affiliation as $title) {
				$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, title',
					$this->authInfo['db_groups']['table'],
					"deleted = 0 AND pid = ".$this->conf['storagePid'] . " AND title = '$title'");
				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
					$feGroups[] = $row['uid'];
				} else {
					$group = array('title' => $title, 'pid' => $this->conf['storagePid']);
					$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->authInfo['db_groups']['table'], $group);
					$feGroups[] = $GLOBALS['TYPO3_DB']->sql_insert_id();
				}
				if ($dbres) $GLOBALS['TYPO3_DB']->sql_free_result($dbres);
			}
		}
		
		// Hook for any additional fe_groups
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['getFEUserGroups'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['getFEUserGroups'] as $_classRef) {
				$_procObj =& t3lib_div::getUserObj($_classRef);
				$feGroups = $_procObj->getFEUserGroups($feGroups);
			}
		}
		
		return implode(',', $feGroups);
	}
	
	/**
	 * Authenticate a user (Check various conditions for the user that might invalidate its authentication, eg. password match, domain, IP, etc.)
	 *
	 * @param	array		Data of user.
	 * @return	boolean
	 */
	function authUser($user) {
		$OK = 100;
		
		if ($_SERVER['AUTH_TYPE'] == 'shibboleth' && !empty($this->remoteUser)) {
			$OK = 200;
			
			if ($user['lockToDomain'] && $user['lockToDomain']!=$this->authInfo['HTTP_HOST']) {
				// Lock domain didn't match, so error:
				if ($this->writeAttemptLog) {
					$this->writelog(255,3,3,1,
						"Login-attempt from %s (%s), username '%s', locked domain '%s' did not match '%s'!",
						array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->authInfo['db_user']['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST']));
					t3lib_div::sysLog(sprintf( "Login-attempt from %s (%s), username '%s', locked domain '%s' did not match '%s'!",
						$this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->authInfo['db_user']['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST']), 'Core', 0);
				}
				$OK = 0;
			}
		} else {
			// Failed login attempt (wrong password) - write that to the log!
			if ($this->writeAttemptLog) {
				$this->writelog(255,3,3,1,
					"Login-attempt from %s (%s), username '%s', password not accepted!",
					array($this->info['REMOTE_ADDR'], $this->info['REMOTE_HOST'], $this->remoteUser));
				t3lib_div::sysLog(sprintf("Login-attempt from %s (%s), username '%s', password not accepted!", $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->remoteUser), 'Core', 0 );
			}
			$OK = 0;
		}
		
		return $OK;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/shibboleth_auth/sv1/class.tx_shibbolethauth_sv1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/shibboleth_auth/sv1/class.tx_shibbolethauth_sv1.php']);
}

?>