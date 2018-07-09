<?php
/* Copyright (C) 2014-2017		Charlie BENKE	<charlie@patas-monkey.com>
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
 *  \file	   htdocs/transporteur/admin/setup.php
 *  \ingroup	transporteur
 *  \brief	  Page d'administration-configuration du module transporteur
 */

$res=0;
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists("../../../main.inc.php"))
	$res=@include("../../../main.inc.php");				// For "custom" directory

dol_include_once("/transporteur/core/lib/transporteur.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");

$langs->load("admin");
$langs->load("other");
$langs->load("transporteur@transporteur");

// Security check
if (! $user->admin || $user->design) accessforbidden();

$action = GETPOST('action', 'alpha');

$form = new Form($db);
/*
 * Actions
 */

// juste besoin de saisir le service associé au transport

if ($action == 'setvalue' ) {
	dolibarr_set_const($db, "TRANSPORTEUR_SERVICE", GETPOST('transportservice'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "TRANSPORTEUR_FRANCO", GETPOST('transportfranco'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "TRANSPORTEUR_FRANCO_TTC", GETPOST('transportfrancottc'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "TRANSPORTEUR_FRANCO_TEXTE", GETPOST('transportfrancotexte'), 'chaine', 0, '', $conf->entity);

	$mesg = "<font class='ok'>".$langs->trans("ImportSettingSaved")."</font>";
}

$transportservice=$conf->global->TRANSPORTEUR_SERVICE;
$transportfranco=$conf->global->TRANSPORTEUR_FRANCO;
$transportfrancottc=$conf->global->TRANSPORTEUR_FRANCO_TTC;
$transportfrancotexte=$conf->global->TRANSPORTEUR_FRANCO_TEXTE;
if ($transportfrancotexte == "")
	$transportfrancotexte="Franco de port pour %s KG";

/*
 * View
 */

$page_name = $langs->trans("TransPorteurSetup") . " - " . $langs->trans("transporteurGeneralSetting");
llxHeader('', $page_name);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($page_name, $linkback, 'title_setup');



$head = transporteur_admin_prepare_head();

dol_fiche_head($head, 'setup', $langs->trans("transPorteur"), 0, "transporteur@transporteur");

print_titre($langs->trans("TransPorteurSettingValue"));
print '<br>';
print '<form method="post" action="setup.php">';
print '<input type="hidden" name="action" value="setvalue">';
print '<table class="noborder" >';
print '<tr class="liste_titre">';
print '<td></td><td  align=left>'.$langs->trans("Description").'</td>';
print '<td align=center>'.$langs->trans("Value").'</td>';
print '</tr>'."\n";
print '<tr >';
print '<td width=20%  align=left>'.$langs->trans("ServiceAssociatedToTransport").'</td>';
print '<td align=left>'.$langs->trans("InfoServiceAssociatedToTransport").'</td>';

print '<td  align=right>';
$form->select_produits(
				$transportservice, 'transportservice', 1,
				$conf->product->limit_size, "", 1, 2, '',
				1, array(), 0
);
print '</td></tr>'."\n";

print '<tr >';
print '<td width=20%  align=left>'.$langs->trans("TransporteurFranco").'</td>';
print '<td align=left>'.$langs->trans("InfoTransporteurFranco").'</td>';
print '<td  align=right>';
print '<input type="text" name="transportfranco" size="6" value="'.$transportfranco.'"> &nbsp;';
print $form->selectPriceBaseType($transportfrancottc, "transportfrancottc");
print '</td></tr>'."\n";

print '<tr >';
print '<td width=20%  align=left>'.$langs->trans("TransporteurFrancoText").'</td>';
print '<td align=left>'.$langs->trans("InfoTransporteurFrancoText").'</td>';
print '<td  align=right>';
print '<input type="text" name="transportfrancotexte" size="40" value="'.$transportfrancotexte.'"> &nbsp;';
print '</td></tr>'."\n";


print '<tr ><td colspan=2></td><td align=right>';
// Boutons d'action
//print '<div class="tabsAction">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
//print '</div>';
print '</td></tr>'."\n";
print '</table>';
print '</form>';
// Show errors
print "<br>";

dol_htmloutput_errors($object->error, $object->errors);


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
}
else
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