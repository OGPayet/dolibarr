<?php
/* Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012 Jean Heimburger	<jean@tiaris.info>
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
 *      \file       logistique/admin/logistique.php
 *      \ingroup    logistique
 *      \brief      Module config for logistique
 *		\version    $Id: logistique.php,v 1.00 2012/07/21 22:53:34 eldy Exp $
 *		\author		Jean Heimburger
 *		\remarks
 */

$res=@include("../../../main.inc.php");					// For "custom" directory
if (! $res) $res=@include("../../main.inc.php");		// For root directory
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
dol_include_once("/shippinglabels/lib/shippinglabels.lib.php");

$langs->load("admin");
$langs->load("shippinglabels@shippinglabels");

if (!$user->admin)
accessforbidden();


// Protection if external user
if ($user->societe_id > 0)
{
	accessforbidden();
}

$user->getrights('shippinglabels');
//if (!$user->rights->shippinglabels->lire) accessforbidden();

/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/
// Get all datas
$pEndpoint = GETPOST("Endpoint","alpha");
$pDepot = GETPOST("depotCode","alpha");
$pClient = GETPOST("clientCode","alpha");
$pContact = GETPOST("contactID","alpha");
$pCountryCode = GETPOST("countryCode","alpha");
$pPRContact = GETPOST("prcontactID", "alpha");
$pFormatlabel =  GETPOST('Formatlabel', 'alpha');
$pApilogin =  GETPOST('Apilogin', 'alpha');
$pApipwd =  GETPOST('Apipwd', 'alpha');

if (GETPOST("save", "alpha")<>"")
{
	$db->begin();

	$i=0;

	$i+=dolibarr_set_const($db,'LOGISTIK_GLS_ENDPOINT',$pEndpoint,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_GLS_DEPOT',$pDepot,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_GLS_CLIENT',$pClient,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_GLS_CONTACTID',$pContact,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_GLS_COUNTRYCODE',$pCountryCode,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_GLS_PRCONTACTID',$pPRContact,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_GLS_FORMATLABEL',$pFormatlabel,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_GLS_APILOGIN',$pApilogin,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_GLS_APIPWD',$pApipwd,'chaine',0,'',$conf->entity);

	if ($i >= 9)
	{
		$db->commit();
		$mesg = '<font class="ok">'.$langs->trans("SetupSaved")."</font>";
	}
	else
	{
		$db->rollback();
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

/******************
 * DATAS
 *******************/

$Endpoint = dolibarr_get_const($db,"LOGISTIK_GLS_ENDPOINT",$conf->entity);

$Depot = dolibarr_get_const($db,"LOGISTIK_GLS_DEPOT",$conf->entity);
$Client = dolibarr_get_const($db,"LOGISTIK_GLS_CLIENT",$conf->entity);
$ContactID = dolibarr_get_const($db,"LOGISTIK_GLS_CONTACTID",$conf->entity);
$CountryCode = dolibarr_get_const($db,"LOGISTIK_GLS_COUNTRYCODE",$conf->entity);
$PRContactID = dolibarr_get_const($db,"LOGISTIK_GLS_PRCONTACTID",$conf->entity);  // contact pour PR
$Formatlabel = dolibarr_get_const($db,"LOGISTIK_GLS_FORMATLABEL",$conf->entity);
$apilogin = dolibarr_get_const($db,"LOGISTIK_GLS_APILOGIN",$conf->entity);
$apipwd = dolibarr_get_const($db,"LOGISTIK_GLS_APIPWD",$conf->entity);

/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/

llxHeader('', 'Shipping Label','','');

$head = prepare_head_admin($user);
dol_fiche_head($head, 'AdminGLS', $langs->trans("ShippinglabelsTitle"), 0, 'order');

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("GLSSetup"),$linkback,'setup');

$var=true;
print '<form name="logistikconfig" action="'.$_SERVER["PHP_SELF"].'" method="post">';

print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="40%">'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Examples").'</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParGLSEndpoint").'</td>';
print '<td><input type="text" class="flat" name="Endpoint" value="'. ($Endpoint) . '" size="60"></td>';
print '<td>'.$langs->trans("ParGLSDefEndpoint");
print '</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParGLSDepot").'</td>';
print '<td><input type="text" class="flat" name="depotCode" value="'. ($Depot) . '" size="60"></td>';
print '<td>'.$langs->trans("ParGLSDefDepot");
print '</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParGLSClient").'</td>';
print '<td><input type="text" class="flat" name="clientCode" value="'. ($Client) . '" size="60"></td>';
print '<td>'.$langs->trans("ParGLSDefClient");
print '</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParGLSContactId").'</td>';
print '<td><input type="text" class="flat" name="contactID" value="'. ($ContactID) . '" size="60"></td>';
print '<td>'.$langs->trans("ParGLSDefContactId");
print '</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParGLSPRContactId").'</td>';
print '<td><input type="text" class="flat" name="prcontactID" value="'. ($PRContactID) . '" size="60"></td>';
print '<td>'.$langs->trans("ParGLSDefPRContactId");
print '</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParGLSCountryCode").'</td>';
print '<td><input type="text" class="flat" name="countryCode" value="'. ($CountryCode) . '" size="60"></td>';
print '<td>'.$langs->trans("ParGLSDefCountryCode");
print '</td>';
print '</tr>';

// format étiquette
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParFormatLabel").'</td>';
//print '<td><input type="text" class="flat" name="Formatlabel" value="'. ($formatlabel) . '" size="10"></td>';
print '<td><select name="Formatlabel">';
print '<option value="" '.(($Formatlabel == '')?"selected":"").'>Standard</option>';
print '<option value="a4" '.(($Formatlabel == 'a4')?"selected":"").'>A4</option>';
print '<option value="a5" '.(($Formatlabel == 'a5')?"selected":"").'>A5</option>';
print '<option value="a6" '.(($Formatlabel == 'a6')?"selected":"").'>A6</option>';
print '</td>';
print '<td>'.$langs->trans("ParChoose");
print '</td>';
print '</tr>';

// connexion API
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParGLSApiLogin").'</td>';
print '<td><input type="text" class="flat" name="Apilogin" value="'. ($apilogin) . '" size="15">&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" class="flat" name="Apipwd" value="'. ($apipwd) . '" size="15"></td>';
print '<td>'.$langs->trans("ParGLSDefApiLogin");
print '</td>';
print '</tr>';


print '</table>';

print '<br><center>';
print '<input type="submit" name="save" class="button" value="'.$langs->trans("Save").'">';
print '</center>';
print "</form>\n";

print '<br>';

clearstatcache();
if ($mesg) print '<br>'.$mesg.'<br>';
print '<br>';

// End of page
$db->close();
llxFooter();

?>