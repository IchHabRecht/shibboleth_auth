The following .htaccess rules must be added to htaccess file in typo3 root:
	AuthType Shibboleth
	Require Shibboleth
	
And if you have RealUrl Extension and if Shibboleth_Login_Handler and Shibboleth_Logout_Handler are at the same domain as typo3 installation, the following must also be added to htaccess:
	RewriteRule ^("Shibboleth_Login_Handler".*)/ - [L]
	RewriteRule ^("Shibboleth_Logout_Handler".*)/ - [L]
It must be the first RewriteRule in htaccess.
