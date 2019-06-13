<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/requestmanager/admin/dictionaries.php
 *		\ingroup    requestmanager
 *		\brief      Page dictionaries of requestmanager module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/requestmanager/lib/requestmanager.lib.php');
dol_include_once('/requestmanager/lib/opendsi_common.lib.php');

$langs->load("admin");
$langs->load("requestmanager@requestmanager");
$langs->load("opendsi@requestmanager");

$action      = GETPOST('action', 'alpha');
$confirm     = GETPOST('confirm', 'alpha');
$id          = GETPOST('id', 'int');
$rowid       = GETPOST('rowid', 'int');
$prevrowid   = GETPOST('prevrowid', 'int');
$module      = GETPOST('module', 'alpha');
$name        = GETPOST('name', 'alpha');

$canRead = $user->rights->advancedictionaries->read || $user->admin;
$canCreate = $user->rights->advancedictionaries->create || $user->admin;
$canUpdate = $user->rights->advancedictionaries->create || $user->admin;
$canDelete = $user->rights->advancedictionaries->delete || $user->admin;
$canDisable = $user->rights->advancedictionaries->disable || $user->admin;

include dol_buildpath('/advancedictionaries/core/actions_dictionaries.inc.php');

/**
 * View
 */

$wikihelp='EN:Request_Manager_En|FR:Request_Manager_Fr|ES:Request_Manager_Es';
llxHeader('', $langs->trans("RequestManagerSetup"), $wikihelp);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("RequestManagerSetup"),$linkback,'title_setup');
print "<br>\n";


$head=requestmanager_admin_prepare_head();

dol_fiche_head($head, 'dictionaries', $langs->trans("Module163018Name"), 0, 'opendsi@requestmanager');

$moduleFilter = ''; // array or string to set the dictionaries of witch modules to show in dictionaries list
$familyFilter = 'requestmanager'; // array or string to set the dictionaries of witch family to show in dictionaries list

include dol_buildpath('/advancedictionaries/core/tpl/dictionaries.tpl.php');

dol_fiche_end();

llxFooter();

$db->close();
