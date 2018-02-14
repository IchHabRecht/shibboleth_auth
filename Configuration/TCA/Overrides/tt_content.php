<?php

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['shibboleth_auth_pi1'] = 'layout,select_key,pages';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['shibboleth_auth_pi1'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:shibboleth_auth/locallang_db.xml:tt_content.list_type_pi1',
        'shibboleth_auth_pi1',
        'EXT:shibboleth_auth/ext_icon.gif',
    ],
    'list_type',
    'shibboleth_auth'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'shibboleth_auth_pi1',
    'FILE:EXT:shibboleth_auth/pi1/flexform.xml'
);
