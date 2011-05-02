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
		
		if ($this->login['status']=='login' && $this->login['uname'])	{
			$user = $this->fetchUserRecord($this->login['uname']);
			// @todo:
			// if (TYPO3_MODE == 'BE') do not import users
			// if (TYPO3_MODE == 'FE') import users.
			if(!is_array($user)) {
				// Failed login attempt (no username found)
				$this->writelog(255,3,3,2,
					"Login-attempt from %s (%s), username '%s' not found!!",
					Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']));	// Logout written to log
				t3lib_div::sysLog(sprintf( "Login-attempt from %s (%s), username '%s' not found!", $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname'] ), 'Core', 0);
			} else {
				if ($this->writeDevLog) 	t3lib_div::devLog('User found: '.t3lib_div::arrayToLogString($user, array($this->db_user['userid_column'],$this->db_user['username_column'])), 'tx_sv_auth');
			}
		}
		return $user;
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
					array($this->info['REMOTE_ADDR'], $this->info['REMOTE_HOST'], $this->login['uname']));
				t3lib_div::sysLog(sprintf("Login-attempt from %s (%s), username '%s', password not accepted!", $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']), 'Core', 0 );
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