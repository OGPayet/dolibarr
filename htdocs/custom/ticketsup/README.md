Ticket Module for Dolibarr ERP/CRM
=========

This is a module for Dolibarr ERP/CRM to manage incident/support ticket.

Licence
-------
GPLv3 or (at your option) any later version.

See COPYING for more information.

SUPPORT
-------

See documentation here : https://doc.aternatik.net


INSTALL
-------

To install this module, Dolibarr (v >= 3.4) have to be already installed and configured on your server.

- In your Dolibarr installation directory: edit the htdocs/conf/conf.php file
- Find the following lines:

	\#$=dolibarr_main_url_root_alt ...

	\#$=dolibarr_main_document_root_alt ...

	or

	//$=dolibarr_main_url_root_alt ...

	//$=dolibarr_main_document_root_alt ...

- Delete the first "#" (or "//") of these lines and assign a value consistent with your Dolibarr installation

	$dolibarr_main_url_root = ...

	and

	$dolibarr_main_document_root = ...

for example on UNIX systems:

	$dolibarr_main_url_root = 'http://localhost/Dolibarr/htdocs';

	$dolibarr_main_document_root = '/var/www/Dolibarr/htdocs';

	$dolibarr_main_url_root_alt = 'http://localhost/Dolibarr/htdocs/custom';

	$dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';

for example on a Windows system:

	$dolibarr_main_url_root = 'http://localhost/Dolibarr/htdocs';

	$dolibarr_main_document_root = 'C:/My Web Sites/Dolibarr/htdocs';

	$dolibarr_main_url_root_alt = 'http://localhost/Dolibarr/htdocs/custom';

	$dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';

For more information about the conf.php file take a look at the conf.php.example file.

- Extract the module's files in the $dolibarr_main_document_root_alt directory.
(You may have to create the custom directory first if it doesn't exist yet.)

for example on UNIX systems: /var/www/Dolibarr/htdocs/custom

for example on a Windows system: C:/My Web Sites/Dolibarr/htdocs/custom

- From your browser: log in as a dolibarr administrator and left-click on the "configuration" menu then on the "module" submenu .
- On the screen that appears, you should see the new module (check all tabs, it can be in other than first one)
- Check the security rights (users->permissions) to make sure that rights are correctly set for users and groups


UPGRADING
-------------

When installing newest version of the module, you must disable and activate it (in setup area) to execute possible database changes.


Translating
-------------

If you want to help to translate this module please use Transifex platform : https://www.transifex.com/projects/p/dolibarr_tickets/

Other Licences
--------------
Uses Michel Fortin's PHP Markdown Licensed under BSD to display this README.
