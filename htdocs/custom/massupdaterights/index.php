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
 *   	\file       htdocs/custom/massupdaterights/index.php
 *		\ingroup    massupdaterights
 *		\brief
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
//include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
dol_include_once('/massupdaterights/lib/massupdaterights.lib.php');

// Load traductions files requiredby by page
$langs->load("massupdaterights@massupdaterights");
$langs->load("exports");
$langs->load("other");

// Get parameters
$action		= GETPOST('action','alpha');

$caneditgroup = $user->admin || ($user->rights->massupdaterights->manage && (empty($conf->global->MAIN_USE_ADVANCED_PERMS)?$user->rights->user->user->creer:$user->rights->user->group_advance->write));
// Protection if not right
if (!$caneditgroup) {
	accessforbidden();
}



/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

// Action to import
if ($action == 'import_groups' && $caneditgroup) {
    $nom_fic = dol_print_date(dol_now(),'%Y%m%d%H%M%S').'-'.$_FILES["fichier"]["name"];
    $newfic = DOL_DATA_ROOT.'/'.$nom_fic; // dans le rep de travail
    $temp_fic = $_FILES["fichier"]["tmp_name"];
    if (!empty($temp_fic)) {
      if (move_uploaded_file($temp_fic, $newfic)) {
          if (massupdaterights_import($newfic) > 0) {
              setEventMessage($langs->trans("MassUpdateRightsImported"));
          }
      } else {
          setEventMessage($langs->trans("ErrorFailedToSaveFile"), "errors");
      }
    } else {
        setEventMessage($langs->trans("MassUpdateRightsErrNoFile"), "errors");
    }
}

// Action to export
elseif ($action == 'export_groups' && $caneditgroup) {
    massupdaterights_export();
}



/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('',$langs->trans("MassUpdateRightsTitle"),'');

$form=new Form($db);


print load_fiche_titre($langs->trans("MassUpdateRightsTitle"));

dol_fiche_head();

if ($caneditgroup) {
    // Export
    print '<form name="userfile" action="' . $_SERVER["PHP_SELF"] . '" METHOD="POST">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="max_file_size" value="' . $conf->maxfilesize . '">';
    print '<input type="hidden" name="action" value="export_groups">';

    print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

    $var = true;
    print '<tr class="liste_titre"><td>' . $langs->trans("MassUpdateRightsExportCSV") . '</td></tr>';

    $var = false;
    print '<tr ' . $bc[$var] . '><td>';
    print '<input type="submit" class="button" value="' . $langs->trans('Generate') . '" name="export">';
    print "</td></tr>\n";

    print '</table></form>';

    print '<br />';

    // Import
    print '<form name="userfile" action="' . $_SERVER["PHP_SELF"] . '" enctype="multipart/form-data" METHOD="POST">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="max_file_size" value="' . $conf->maxfilesize . '">';
    print '<input type="hidden" name="action" value="import_groups">';

    print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

    $var = true;
    print '<tr class="liste_titre"><td>' . $langs->trans("MassUpdateRightsImportCSV") . '</td></tr>';

    // Input file name box
    $var = false;
    print '<tr ' . $bc[$var] . '><td>';
    print '<input type="file"   name="fichier" size="20" maxlength="80"> &nbsp; &nbsp; ';
    print '<input type="submit" class="button" value="' . $langs->trans("AddFile") . '" name="import">';
    print "</td></tr>\n";

    print '</table></form>';
}

dol_fiche_end();


// End of page
llxFooter();
$db->close();
