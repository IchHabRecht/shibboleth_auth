The following .htaccess rules must be added to htaccess file in typo3 root:
	AuthType Shibboleth
	ShibRequireSession Off
	Require Shibboleth
	
And if you have RealUrl Extension, the following must also be added to htaccess:
	RewriteRule ^(Shib.*)/ - [L]

This must be the first rewrite rule in htaccess.
