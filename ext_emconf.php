<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "shibboleth_auth".
 *
 * Auto generated 10-04-2017 21:33
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Shibboleth Authentication',
	'description' => 'Shibboleth Single Sign On Authentication (BE & FE). The FE Users will be imported automatically into the configured storage pid.',
	'category' => 'services',
	'shy' => 0,
	'version' => '2.5.5',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Tamer Erdogan, Richard Rode',
	'author_email' => 'typo3@univie.ac.at',
	'author_company' => '',
	'CGLcompliance' => NULL,
	'CGLcompliance_note' => NULL,
	'constraints' => 
	array (
		'depends' => 
		array (
			'typo3' => '4.0.0-6.1.1',
			'' => '',
		),
		'conflicts' => 
		array (
		),
		'suggests' => 
		array (
		),
	),
);

?>