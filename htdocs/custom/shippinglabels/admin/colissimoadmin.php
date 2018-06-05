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
$pUserid = GETPOST("Userid","alpha");
$pPwd = GETPOST("Pwd","alpha");
$pEndpoint = GETPOST("Endpoint","alpha");
$pTaxnumber = GETPOST("Taxnumber","alpha");
$pShipname = GETPOST("Shipname","alpha");
$pShipcharge = GETPOST("Shipcharge", "alpha");
$pServicecode = GETPOST("Servicecode", "alpha");
$pPackagingcode = GETPOST("Packagingcode", "alpha");
$pShipperaddress = GETPOST("Shipperaddress", "alpha");
$pShipperzip = GETPOST("Shipperzip", "alpha");
$pShippertown = GETPOST("Shippertown", "alpha");
$pShipperstate = GETPOST("Shipperstate", "alpha");
$pShippercountrycode = GETPOST("Shippercountrycode", "alpha");
$pShipperCivility = GETPOST("ShipperCivility", "alpha");
$pShipperattention = GETPOST("Shipperattention", "alpha");
$pShipperphone = GETPOST("Shipperphone", "alpha");
$pShipperphonext = GETPOST("Shipperphonext", "alpha");
$pShipperdesc = GETPOST("Shipperdesc", "alpha");
$pShippermail = GETPOST("Shippermail", "alpha");

$pFormatlabel=  GETPOST('Formatlabel', 'alpha');

if (GETPOST("save", "alpha")<>"")
{
	$db->begin();

	$i=0;

	$i+=dolibarr_set_const($db,'SOCO_USERID',$pUserid,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SOCO_PWD',$pPwd,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SOCO_ENDPOINT',$pEndpoint,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SOCO_FORMATLABEL',$pFormatlabel,'chaine',0,'',$conf->entity);

	if ($i >= 4)
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

$Userid = dolibarr_get_const($db,"SOCO_USERID",$conf->entity);
$Pwd = dolibarr_get_const($db,"SOCO_PWD",$conf->entity);
$Endpoint = dolibarr_get_const($db,"SOCO_ENDPOINT",$conf->entity);
$Formatlabel = dolibarr_get_const($db,"SOCO_FORMATLABEL",$conf->entity);

/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/

llxHeader('', 'Shipping label	','','');

$head = prepare_head_admin($user);
dol_fiche_head($head, 'AdminColissimo', $langs->trans("ShippinglabelsTitle"), 0, 'order');

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("SOCOSetup"),$linkback,'setup');

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
print '<td>'.$langs->trans("ParSOCOUserId").'</td>';
print '<td><input type="text" class="flat" name="Userid" value="'. ($Userid) . '" size="10"></td>';
print '<td>'.$langs->trans("ParSOCODefUserId");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParSOCOPwd").'</td>';
print '<td><input type="text" class="flat" name="Pwd" value="'. ($Pwd) . '" size="10"></td>';
print '<td>'.$langs->trans("ParSOCODefPwd");
print '</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParSOCOEndpoint").'</td>';
print '<td><input type="text" class="flat" name="Endpoint" value="'. ($Endpoint) . '" size="60"></td>';
print '<td>'.$langs->trans("ParSOCODefEndpoint");
print '</td>';
print '</tr>';

// format étiquette
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParFormatLabel").'</td>';
//print '<td><input type="text" class="flat" name="Formatlabel" value="'. ($formatlabel) . '" size="10"></td>';
print '<td><select name="Formatlabel">';
print '<option value="a4" '.(($Formatlabel == 'a4')?"selected":"").'>A4</option>';
print '<option value="10x15" '.(($Formatlabel == '10x15')?"selected":"").'>10x15</option>';
print '</td>';
print '<td>'.$langs->trans("ParChoose");
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