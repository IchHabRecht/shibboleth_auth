<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "shibboleth_auth".
 *
 * Auto generated 10-04-2017 21:35
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Shibboleth Authentication',
	'description' => 'Shibboleth Single Sign On Authentication (BE & FE). The FE Users will be imported automatically into the configured storage pid.',
	'category' => 'services',
	'shy' => false,
	'version' => '3.0.0',
	'priority' => NULL,
	'loadOrder' => NULL,
	'module' => NULL,
	'state' => 'stable',
	'uploadfolder' => true,
	'createDirs' => '',
	'modify_tables' => NULL,
	'clearcacheonload' => true,
	'lockType' => NULL,
	'author' => 'Tamer Erdogan, Richard Rode',
	'author_email' => 'typo3@univie.ac.at',
	'author_company' => '',
	'CGLcompliance' => NULL,
	'CGLcompliance_note' => NULL,
	'constraints' =>
	array (
		'depends' =>
		array (
			'typo3' => '6.2.0-8.7.99',
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
