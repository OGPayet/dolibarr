<?php
/* Copyright (C) 2014	Maxime MANGIN	<maxime@tuxserv.fr>
 * Copyright (C) 2012	Regis Houssin	<regis@dolibarr.fr>
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
 *  \file       /contratabonnement/admin/contratabonnement_conf.php
 *  \ingroup    contract
 *  \brief      Page d'administration/configuration du module contrat d'abonnement
 */

$res=@include("../../main.inc.php");					// For root directory
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

$langs->load("admin");
$langs->load("products");
$langs->load("contratabonnement@contratabonnement");

// Security check
if (!$user->admin) {accessforbidden();}


if ($_POST["action"] == 'nbJoursAvant' && $_POST["value"]>0) {
	dolibarr_set_const($db, "SUBSCRIPTION_BOX_DAYS_BEFORE", $_POST["value"],'chaine',0,'',$conf->entity);
}
else if ($_POST["action"] == 'nbJoursApres' && $_POST["value"]>0) {
	dolibarr_set_const($db, "SUBSCRIPTION_BOX_DAYS_AFTER", $_POST["value"],'chaine',0,'',$conf->entity);
}
else if ($_POST["action"] == 'facturationCivile' && $_POST["value"]>=0) {
	dolibarr_set_const($db, "SUBSCRIPTION_USE_CIVIL_BILLING", $_POST["value"],'chaine',0,'',$conf->entity);
}
else if ($_POST["action"] == 'nbPeriodReconduction' && $_POST["value"]>=0) {
	dolibarr_set_const($db, "SUBSCRIPTION_RECOND_PERIOD", $_POST["value"],'chaine',0,'',$conf->entity);
}
else if ($_POST["action"] == 'objectMailMass' && $_POST["value"]) {
	dolibarr_set_const($db, "SUBSCRIPTION_MASS_MAIL_OBJECT", $_POST["value"],'chaine',0,'',$conf->entity);
}
else if ($_POST["action"] == 'contentMailMass' && $_POST["value"]) {
	dolibarr_set_const($db, "SUBSCRIPTION_MASS_MAIL_CONTENT", $_POST["value"],'chaine',0,'',$conf->entity);
}

/*
 * Affiche page
 */

llxHeader('',$langs->trans("ProductSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("SubscriptionContractSetup"),$linkback,'setup');

$html = new Form($db);
$var = true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "  <td>".$langs->trans("ParametersOfSubscribBox")."</td>\n";
print "  <td align=\"right\" width=\"60\">".$langs->trans("Value")."</td>\n";
print "  <td width=\"80\">&nbsp;</td></tr>\n";

/*
 * Formulaire parametres divers
 */


$var=!$var;

//Nombre de jours avant
print "<form method=\"post\" action=\"contratabonnement_conf.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"nbJoursAvant\">";
print "<tr ".$bc[$var].">";
print '<td>'.$langs->trans("NumberOfDaysBefore").'</td>';
print "<td align=\"right\"><input size=\"3\" type=\"text\" class=\"flat\" name=\"value\" value=\"".$conf->global->SUBSCRIPTION_BOX_DAYS_BEFORE."\"></td>";
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</form>';
//Nombre de jours après
print "<form method=\"post\" action=\"contratabonnement_conf.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"nbJoursApres\">";
print "<tr ".$bc[$var].">";
print '<td>'.$langs->trans("NumberOfDaysAfter").'</td>';
print "<td align=\"right\"><input size=\"3\" type=\"text\" class=\"flat\" name=\"value\" value=\"".$conf->global->SUBSCRIPTION_BOX_DAYS_AFTER."\"></td>";
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</form>';

//Facturation sur les périodes civiles
if(file_exists('contratabonnement_conf_noncivile.php')){
	include('contratabonnement_conf_noncivile.php');
}

//Tacites reconduction
if(file_exists('contratabonnement_conf_reconduction.php')){
	include('contratabonnement_conf_reconduction.php');
}

// Envoi en masse des factures
if(file_exists('contratabonnement_conf_masse_envoi.php')){
	include('contratabonnement_conf_masse_envoi.php');
}

print '</table>';

print '<br/>'.$langs->trans("editOdtSubscriptions");
$db->close();

llxFooter('$Date: 2012/07/10 15:00:00');
?>
