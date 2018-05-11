<?php
/* Copyright (C) 2015-2017	  Charlene BENKE	 <charlie@patas-monkey.com>
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
 *  \file	   htdocs/portofolio/admin/setup.php
 *  \ingroup	portofolio
 *  \brief	  Page d'administration-configuration du module portofolio
 */

$res=0;
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists("../../../main.inc.php"))
	$res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once("/portofolio/core/lib/portofolio.lib.php");

require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");

$langs->load("admin");
$langs->load("other");
$langs->load("portofolio@portofolio");

// Security check
if (! $user->admin || $user->design) accessforbidden();

$action = GETPOST('action', 'alpha');


/*
 * Actions
 */

if ($action == 'setcontextview') {
	// save the setting
	dolibarr_set_const($db, "PORTOFOLIO_ENABLE_CLONE", GETPOST('value', 'int'), 'chaine', 0, '', $conf->entity);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}

if ($action == 'setscoring') {
	// save the setting
	dolibarr_set_const($db, "PORTOFOLIO_ENABLE_SCORING ", GETPOST('value', 'int'), 'chaine', 0, '', $conf->entity);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}

if ($action == 'setsalesmantrigger') {
	// save the setting
	dolibarr_set_const($db, "PORTOFOLIO_ADDSALESMAN_TRIGGER", GETPOST('value', 'int'), 'chaine', 0, '', $conf->entity);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}

if ($action == 'setccmailtosalesman') {
	// save the setting
	dolibarr_set_const($db, "PORTOFOLIO_CCMAIL_TO_SALESMAN", GETPOST('value', 'int'), 'chaine', 0, '', $conf->entity);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}


$form = new Form($db);

/*
 * View
 */

$pageName = $langs->trans("PortofolioSetup") . " - " .$langs->trans("portofolioGeneralSetting");
llxHeader('', $pageName);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($pageName, $linkback, 'title_setup');

$head = portofolio_admin_prepare_head();

dol_fiche_head($head, 'setup', $langs->trans("Portofolio"), 0, "portofolio@portofolio");

dol_htmloutput_mesg($mesg);

print '<table class="noborder" >';
print '<tr class="liste_titre">';
print '<td width="200px">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td nowrap >'.$langs->trans("Value").'</td>';
print '</tr>'."\n";
print '<tr >';
print '<td align=left>'.$langs->trans("EnableScoring").'</td>';
print '<td align=left>'.$langs->trans("InfoEnableScoring").'</td>';
print '<td align=left >';
if ($conf->global->PORTOFOLIO_ENABLE_SCORING =="1") {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setscoring&amp;value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setscoring&amp;value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td></tr>'."\n";

print '<tr class="liste_titre">';
print '<td width="200px">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td nowrap >'.$langs->trans("Value").'</td>';
print '</tr>'."\n";
print '<tr >';
print '<td align=left>'.$langs->trans("EnableSocieteClone").'</td>';
print '<td align=left>'.$langs->trans("InfoEnableSocieteClone").'</td>';
print '<td align=left >';
if ($conf->global->PORTOFOLIO_ENABLE_CLONE =="1") {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setcontextview&amp;value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setcontextview&amp;value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td></tr>'."\n";

print '<tr >';
print '<td align=left>'.$langs->trans("CCMailtoSalesman").'</td>';
print '<td align=left>'.$langs->trans("InfoCCMailtoSalesman").'</td>';
print '<td align=left >';
if ($conf->global->PORTOFOLIO_CCMAIL_TO_SALESMAN =="1") {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setccmailtosalesman&amp;value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setccmailtosalesman&amp;value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td></tr>'."\n";

print '<tr >';
print '<td align=left>'.$langs->trans("EnableSalesmanActionTrigger").'</td>';
print '<td align=left>'.$langs->trans("InfoEnableSalesmanActionTrigger").'</td>';
print '<td align=left >';
if ($conf->global->PORTOFOLIO_ADDSALESMAN_TRIGGER =="1") {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setsalesmantrigger&amp;value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setsalesmantrigger&amp;value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td></tr>'."\n";
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
print '<tr>'."\n";
print '<td colspan="2">'.$langs->trans("SupportModuleInformationDesc").'</td></tr>'."\n";
print "</table>\n";

// Show messages
dol_htmloutput_mesg($object->mesg, '', 'ok');

// Footer
llxFooter();
$db->close();