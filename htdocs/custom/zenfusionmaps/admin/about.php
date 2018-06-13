<?php
/*
 * ZenFusion Maps - A Google Maps module for Dolibarr
 * Copyright (C) 2013-2017  Raphaël Doursenaud  <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Cédric Salvador     <csalvador@gpcsolutions.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file admin/about.php
 * \ingroup zenfusionmaps
 * \brief Module about page
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}

require_once '../core/modules/modZenFusionMaps.class.php';
require_once '../lib/admin.lib.php';

global $db, $langs, $user;

$langs->load("zenfusionmaps@zenfusionmaps");
$langs->load("admin");
$langs->load("help");

// only readable by admin
if (! $user->admin) {
    accessforbidden();
}

$module = new modZenFusionMaps($db);

/*
 * View
 */

// Little folder on the html page
llxHeader();
/// Navigation in the modules
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">';
$linkback .= $langs->trans("BackToModuleList") . '</a>';
// Folder icon title
print load_fiche_titre("ZenFusion", $linkback, 'setup');

$head = zfPrepareHead();

dol_fiche_head(
    $head,
    'about',
    $langs->trans("Module105005Name"),
    0,
    'maps@zenfusionmaps'
);

echo '<h3>', $langs->trans("Module105005Name"), '</h3>',
     '<em>', $langs->trans("Version"), ' ',
 $langs->trans($module->version), '</em><br>',
     '<em>&copy;2013-2017 GPC.solutions<br><em>',
     '<a target="_blank" href="http://www.zenfusion.net/">',
 '<img src="../img/logo_zf.png" alt="Logo ZenFusion"></a>',

     '<h3>', $langs->trans("Publisher"), '</h3>',
     '<a target="_blank" href="http://www.gpcsolutions.fr">',
 '<img src="../img/logo_gpc.png" alt="GPC.solutions"></a>',
     '<address>Technopole Hélioparc<br>',
 '2 avenue du Président Pierre Angot<br>',
 '64053 PAU CEDEX 9<br>',
 'FRANCE<br>',
 '+33 (0)5 35 53 97 12</address>',
 '<a href="mailto:contact@gpcsolutions.fr">contact@gpcsolutions.fr</a>',

     '<h3>', $langs->trans("License"), '</h3>',
     '<a target="_blank" href="http://www.gnu.org/licenses/gpl-3.0.html">',
 '<img src="../img/gplv3.png" alt="GPL v.3"></a>',

     '<h3>', $langs->trans("Credits"), '</h3>',

     '<h4>', $langs->trans("Development"), '</h4>',

     '<ul>',
     '<li>Raphaël Doursenaud, ', $langs->trans('ProjectManager'), '</li>',
     '<li>Cédric Salvador, ', $langs->trans('SoftwareEngineer'), '</li>',
     '</ul>',

     '<h4>' . $langs->trans("Ressources") . '</h4>',

     '<ul>',
 '<li>Google Maps logo<br>',
 '&copy; <a target="_blank" href="http://google.com">Google</a><br>',
 'Google Maps is a trademark of Google Inc. Use of this trademark is subject to Google Permissions.',
 '</li>',
 '<li>GPLv3 logo<br>',
 '&copy;2007, 2008 ',
 '<a target="_blank" href="http://fsf.org">Free Software Foundation</a>',
 '</li>',
 '<li>ZenFusion logo<br>',
 '&copy;2011 GPC.solutions<br>',
 'Trademark Pending',
 '</li>',
 '<li>GPC.solutions logo<br>',
 '&copy;2010-2014 GPC.solutions',
 '</li>',
 '</ul>';

dol_fiche_end();
llxFooter();
