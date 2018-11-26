<?php
/* Copyright (C) 2015-2018	  Charlie BENKE	 <charlie@patas-monkey.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file	   htdocs/myfield/admin/setup.php
 *  \ingroup	myfield
 *  \brief	  Page d'administration-configuration du module myfield
 */

$res=0;
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists("../../../main.inc.php"))
	$res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once("/myfield/core/lib/myfield.lib.php");

require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");


$langs->load("admin");
$langs->load("other");
$langs->load("myfield@myfield");

// Security check
if (! $user->admin || $user->design) accessforbidden();

$action = GETPOST('action', 'alpha');


/*
 * Actions
 */

if ($action == 'setcontextview') {
	// save the setting
	dolibarr_set_const(
					$db, "MYFIELD_CONTEXT_VIEW", GETPOST('value', 'int'),
					'chaine', 0, '', $conf->entity
	);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}
if ($action == 'setadminallright') {
	// save the setting
	dolibarr_set_const(
					$db, "MYFIELD_ADMIN_ALL_RIGHT", GETPOST('value', 'int'),
					'chaine', 0, '', $conf->entity
	);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}
if ($action == 'setenablesmallbutton') {
	// save the setting
	dolibarr_set_const(
					$db, "MYFIELD_ENABLE_SMALL_BUTTON", GETPOST('value', 'int'),
					'chaine', 0, '', $conf->entity
	);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}
if ($action == 'updatecolor') {
	$val=(implode(',', (colorStringToArray(GETPOST('MYFIELD_INPUT_BACKGROUND'),array()))));
	if ($val == '')
		dolibarr_del_const($db, 'MYFIELD_INPUT_BACKGROUND', $conf->entity);
	else
		dolibarr_set_const($db, 'MYFIELD_INPUT_BACKGROUND', $val, 'chaine', 0, '', $conf->entity);
}

$form = new Form($db);

/*
 * View
 */

$page_name = $langs->trans("MyFieldSetup") . ' - ' . $langs->trans("myfieldGeneralSetting");
llxHeader('', $page_name);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($page_name, $linkback, 'title_setup');

$head = myfield_admin_prepare_head();

dol_fiche_head($head, 'setup', $langs->trans("myfield"), 0, "myfield@myfield");
dol_htmloutput_mesg($mesg);

$formother = new FormOther($db);


print '<table class="noborder" >';
print '<tr class="liste_titre">';
print '<td width="200px">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td nowrap >'.$langs->trans("Value").'</td>';
print '</tr>'."\n";
print '<tr >';
print '<td align=left>'.$langs->trans("EnableContextView").'</td>';
print '<td align=left>'.$langs->trans("InfoEnableContextView").'</td>';
print '<td align=left >';
if ($conf->global->MYFIELD_CONTEXT_VIEW =="1") {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setcontextview&amp;value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setcontextview&amp;value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td></tr>';

print '<tr >';
print '<td align=left>'.$langs->trans("AdminAllAccessRight").'</td>';
print '<td align=left>'.$langs->trans("InfoAdminAllAccessRight").'</td>';
print '<td align=left >';
if ($conf->global->MYFIELD_ADMIN_ALL_RIGHT =="1") {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setadminallright&amp;value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setadminallright&amp;value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td></tr>';


print '<tr >';
print '<td align=left>'.$langs->trans("ChangeInputBackground").'</td>';
print '<td align=left>'.$langs->trans("InfoChangeInputBackground").'</td>';
print '<td align=left >';
	print '<form enctype="multipart/form-data" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="updatecolor">';

	print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->MYFIELD_INPUT_BACKGROUND, array()), ''), 'MYFIELD_INPUT_BACKGROUND','formcolor',1).' ';
	print '<input class="button" type=submit name="'.$langs->trans("Save").'">';
	print '</form>';
print '</td></tr>';


print '<tr >';
print '<td align=left>'.$langs->trans("EnableSmallButton").'</td>';
print '<td align=left>'.$langs->trans("InfoEnableSmallButton").'</td>';
print '<td align=left >';
if ($conf->global->MYFIELD_ENABLE_SMALL_BUTTON =="1") {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setenablesmallbutton&amp;value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setenablesmallbutton&amp;value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td></tr>';

print '</table>';

/*
 *  Infos pour le support
 */
print '<br>';
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
print '<tr><td colspan="2">'.$langs->trans("SupportModuleInformationDesc").'</td></tr>'."\n";
print "</table>\n";

// Show messages
dol_htmloutput_mesg($object->mesg, '', 'ok');

// Footer
llxFooter();
$db->close();