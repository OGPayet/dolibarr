<?php
/* Copyright (C) 2014-2017	 Charlie Benke <charlie@patas-monkey.com>
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
 *   	\file	   htdocs/mylist/admin/admin.php
 *		\ingroup	customlink
 *		\brief	  Page to setup the module admin
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php"))
	$res=@include("../../../main.inc.php");	// For "custom" directory


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/mylist/core/lib/mylist.lib.php');

$langs->load("admin");
$langs->load("mylist@mylist");

if (! $user->admin) accessforbidden();

$type = array('yesno', 'texte', 'chaine');

$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scandir', 'alpha');
$typedoc='mylist';

/*
 * Actions
 */

if ($action == 'setvalue') {
	// save the setting
	dolibarr_set_const($db, "MYLIST_NB_ROWS", GETPOST('nbrows', 'int'), 'chaine', 0, '', $conf->entity);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}
if ($action == 'setvaluecrlf') {
	// save the setting
	dolibarr_set_const($db, "MYLIST_CRLF_REPLACE", GETPOST('crlfreplace', 'chaine'), 'chaine', 0, '', $conf->entity);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}
$crlfreplace = $conf->global->MYLIST_CRLF_REPLACE;

if ($action == 'setexportcsv') {
	// save the setting
	dolibarr_set_const($db, "MYLIST_CSV_EXPORT", GETPOST('value', 'int'), 'chaine', 0, '', $conf->entity);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}

// Activate a model
if ($action == 'set') {
	$ret = addDocumentModel($value, $typedoc, $label, $scandir);

	// si pas de model par défaut on prend celui que l'on vient d'activer
	if ($conf->global->MYLIST_ADDON_PDF == "")
		dolibarr_set_const($db, "MYLIST_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity);
}
if ($action == 'del') {
	$ret = delDocumentModel($value, $typedoc);
	if ($ret > 0)
		if ($conf->global->MYLIST_ADDON_PDF == "$value")
			dolibarr_del_const($db, 'MYLIST_ADDON_PDF', $conf->entity);
}
// Set default model
if ($action == 'setdoc') {
	if (dolibarr_set_const($db, "MYLIST_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent

		$conf->global->MYLIST_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $typedoc);
	if ($ret > 0)
		$ret = addDocumentModel($value, $typedoc, $label, $scandir);
}

// Get setting
$nbrows=$conf->global->MYLIST_NB_ROWS;
if ($nbrows == "") {
	$nbrows=25;
	dolibarr_set_const($db, "MYLIST_NB_ROWS", $nbrows, 'chaine', 0, '', $conf->entity);
}
$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);

/*
 * View
 */
$form=new Form($db);

$page_name = $langs->trans("MylistSetup") ." - " . $langs->trans("GeneralSetting");
$help_url='EN:Module_mylist|FR:Module_mylist|ES:M&oacute;dulo_mylist';
llxHeader('', $page_name, $help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($page_name, $linkback, 'title_setup');


$head = mylist_admin_prepare_head();

dol_fiche_head($head, 'admin', $langs->trans("myList"), 0, 'mylist.png@mylist');


dol_htmloutput_mesg($mesg);

// la sélection des status à suivre dans le process commercial
print '<br>';
print_titre($langs->trans("GeneralSetting"));


$var=true;
print '<table class="noborder" >';
print '<tr class="liste_titre">';
print '<td width="200px">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td nowrap >'.$langs->trans("Value").'</td>';
print '</tr>'."\n";

$var = !$var;
print '<tr '.$bc[$var].'>';
print '<td align=left>'.$langs->trans("NumberRowsInmyList").'</td>';
print '<td align=left>'.$langs->trans("InfoNumberRowsInmyList").'</td>';
print '<td  align=left>';
print '<form method="post" action="admin.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';
print '<input type=text value="'.$nbrows.'" name=nbrows>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td>';
print '</tr>'."\n";

$var = !$var;
print '<tr '.$bc[$var].'>';
print '<td align=left>'.$langs->trans("EnableExportcsv").'</td>';
print '<td align=left>'.$langs->trans("InfoEnableExportcsv").'</td>';
print '<td align=left >';
if ($conf->global->MYLIST_CSV_EXPORT =="1") {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setexportcsv&amp;value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setexportcsv&amp;value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td>';
print '</tr>';

$var = !$var;
print '<tr '.$bc[$var].'>';
print '<td align=left>'.$langs->trans("ReplaceCarriageReturn").'</td>';
print '<td align=left>'.$langs->trans("InfoReplaceCarriageReturn").'</td>';
print '<td  align=left>';
print '<form method="post" action="admin.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvaluecrlf">';
print '<input type=text value="'.$crlfreplace.'" name=crlfreplace>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td>';
print '</tr>'."\n";
print '</table>';
// Boutons d'action

print '<br>';

/*
 *  Document templates generators
 */
print '<br>';
print_titre($langs->trans("MylistPDFModules"));

// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$typedoc."'";
$sql.= " AND entity = ".$conf->entity;
$resql=$db->query($sql);
if ($resql) {
	$i = 0;
	$num_rows=$db->num_rows($resql);
	while ($i < $num_rows) {
		$array = $db->fetch_array($resql);
		array_push($def, $array[0]);
		$i++;
	}
}
else
	dol_print_error($db);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$var=true;
foreach ($dirmodels as $reldir) {
	foreach (array('', '/doc') as $valdir) {
		$dir = dol_buildpath($reldir."core/modules/mylist".$valdir);

		if (is_dir($dir)) {
			$handle=opendir($dir);
			if (is_resource($handle)) {
				while (($file = readdir($handle))!==false)
					$filelist[]=$file;
				closedir($handle);
				arsort($filelist);

				foreach ($filelist as $file) {
					if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
						if (file_exists($dir.'/'.$file)) {
							$name = substr($file, 4, dol_strlen($file) -16);
							$classname = substr($file, 0, dol_strlen($file) -12);

							require_once($dir.'/'.$file);
							$module = new $classname($db);

							$modulequalified=1;
							if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2)
								$modulequalified=0;
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
								$modulequalified=0;

							if ($modulequalified) {
								$var = !$var;
								print '<tr '.$bc[$var].'><td width="100">';
								print (empty($module->name)?$name:$module->name);
								print "</td><td>\n";
								if (method_exists($module, 'info'))
									print $module->info($langs);
								else
									print $module->description;
								print '</td>';

								// Active
								if (in_array($name, $def)) {
									print '<td align="center">'."\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&value='.$name.'">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print '</td>';
								} else {
									print "<td align=\"center\">\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&value='.$name;
									print '&scandir='.$module->scandir.'&label='.urlencode($module->name).'">';
									print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
									print "</td>";
								}

								// Defaut
								print "<td align=\"center\">";
								if ($conf->global->MYLIST_ADDON_PDF == "$name")
									print img_picto($langs->trans("Default"), 'on');
								else {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&value='.$name;
									print '&scandir='.$module->scandir.'&label='.urlencode($module->name);
									print '" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip =	''.$langs->trans("Name").': '.$module->name;
								$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
								if ($module->type == 'pdf') {
									$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height");
									$htmltooltip.=': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								print '<td align="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								// Preview
								print '<td align="center">';
								if ($module->type == 'pdf') {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">';
									print img_object($langs->trans("Preview"), 'bill').'</a>';
								} else
									print img_object($langs->trans("PreviewNotAvailable"), 'generic');

								print '</td>';
								print "</tr>\n";
							}
						}
					}
				}
			}
		}
	}
}
print '</table>';

/*
 *  Infos pour le support
 */
print '<br><br>';
libxml_use_internal_errors(true);
$sxe = simplexml_load_string(nl2br(file_get_contents('../changelog.xml')));
if ($sxe === false) {
	echo "Erreur lors du chargement du XML\n";
	foreach (libxml_get_errors() as $error)
		print $error->message;
	exit;
} else
	$tblversions=$sxe->Version;

$currentversion = $tblversions[count($tblversions)-1];

print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td width=20%>'.$langs->trans("SupportModuleInformation").'</td>'."\n";
print '<td>'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";
print '<tr '.$bc[false].'><td >'.$langs->trans("DolibarrVersion").'</td><td>'.DOL_VERSION.'</td></tr>'."\n";
print '<tr '.$bc[true].'><td >'.$langs->trans("ModuleVersion").'</td>';
print '<td>'.$currentversion->attributes()->Number." (".$currentversion->attributes()->MonthVersion.')</td></tr>'."\n";
print '<tr '.$bc[false].'><td >'.$langs->trans("PHPVersion").'</td><td>'.version_php().'</td></tr>'."\n";
print '<tr '.$bc[true].'><td >'.$langs->trans("DatabaseVersion").'</td>';
print '<td>'.$db::LABEL." ".$db->getVersion().'</td></tr>'."\n";
print '<tr '.$bc[false].'><td >'.$langs->trans("WebServerVersion").'</td>';
print '<td>'.$_SERVER["SERVER_SOFTWARE"].'</td></tr>'."\n";
print '<tr>'."\n";
print '<td colspan="2">'.$langs->trans("SupportModuleInformationDesc").'</td></tr>'."\n";
print "</table>\n";

dol_fiche_end();
llxFooter();
$db->close();