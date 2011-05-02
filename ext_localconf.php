<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);

$subTypes = array();

if ($config['enableBE']) {
	$subTypes[] = 'getUserBE';
	$subTypes[] = 'authUserBE';
	
	// If this is set Auth Services will come into play everytime a page is requested without a valid user session. If you want to implement a single sign on scenario you will need to set this.
	$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['BE_fetchUserIfNoSession']=1;
	
	if (TYPO3_MODE == 'BE') {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'][] = 'EXT:'.$_EXTKEY.'/hooks/class.tx_shibbolethauth_userauth.php:tx_shibbolethauth_userauth->logoutBE';
	}
}

if ($config['enableFE']) {
	$subTypes[] = 'getUserFE';
	$subTypes[] = 'authUserFE';
	
	// If this is set Auth Services will come into play everytime a page is requested without a valid user session. If you want to implement a single sign on scenario you will need to set this.
	//$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_fetchUserIfNoSession']=1;
}

t3lib_extMgm::addService($_EXTKEY, 'auth',  'tx_shibbolethauth_sv1',
	array(
		'title' => 'Shibboleth-Authentication',
		'description' => 'Authentication service for Shibboleth (BE & FE)',

		'subtype' => implode(',', $subTypes),

		'available' => TRUE,
		'priority' => 100,
		'quality' => 50,

		'os' => '',
		'exec' => '',

		'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_shibbolethauth_sv1.php',
		'className' => 'tx_shibbolethauth_sv1',
	)
);
?>