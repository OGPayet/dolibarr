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
$pAccess = GETPOST("Access","alpha");
$pUserid = GETPOST("Userid","alpha");
$pPwd = GETPOST("Pwd","alpha");
$pShipnumber = GETPOST("Shipnumber","alpha");
$pEndpoint = GETPOST("Endpoint","alpha");
$pTaxnumber = GETPOST("Taxnumber","alpha");
$pShipname = GETPOST("Shipname","alpha");
$pShipcharge = GETPOST("Shipcharge", "alpha");
$pServicecode = GETPOST("Servicecode", "alpha");
$pPackagingcode = GETPOST("Packagingcode", "alpha");
// Image or PDF files generated
$pShipperformat = GETPOST("Shipperformat", "alpha");

if (GETPOST("save", "alpha")<>"")
{
	$db->begin();

	$i=0;

	$i+=dolibarr_set_const($db,'LOGISTIK_UPS_ACCESS',$pAccess,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_UPS_USERID',$pUserid,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_UPS_PWD',$pPwd,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_UPS_SHIPNUMBER',$pShipnumber,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_UPS_ENDPOINT',$pEndpoint,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_UPS_TAXNUMBER',$pTaxnumber,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_UPS_SHIPPERNAME',$pShipname,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_UPS_SHIPCHARGE',$pShipcharge,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_UPS_SERVICECODE',$pServicecode,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_UPS_PACKAGINGCODE',$pPackagingcode,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'LOGISTIK_UPS_SHIPPERFORMAT',$pShipperformat,'chaine',0,'',$conf->entity);

	if ($i >= 11)
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

$Access = dolibarr_get_const($db,"LOGISTIK_UPS_ACCESS",$conf->entity);
$Userid = dolibarr_get_const($db,"LOGISTIK_UPS_USERID",$conf->entity);
$Pwd = dolibarr_get_const($db,"LOGISTIK_UPS_PWD",$conf->entity);
$Shipnumber = dolibarr_get_const($db,"LOGISTIK_UPS_SHIPNUMBER",$conf->entity);
$Endpoint = dolibarr_get_const($db,"LOGISTIK_UPS_ENDPOINT",$conf->entity);
$Taxnumber = dolibarr_get_const($db,"LOGISTIK_UPS_TAXNUMBER",$conf->entity);
$Shipname = dolibarr_get_const($db,"LOGISTIK_UPS_SHIPPERNAME",$conf->entity);
$Shipcharge = dolibarr_get_const($db,"LOGISTIK_UPS_SHIPCHARGE",$conf->entity);
$Servicecode = dolibarr_get_const($db,"LOGISTIK_UPS_SERVICECODE",$conf->entity);
$Packagingcode = dolibarr_get_const($db,"LOGISTIK_UPS_PACKAGINGCODE",$conf->entity);
// Image or PDF files generated
$Shipperformat = dolibarr_get_const($db,"LOGISTIK_UPS_SHIPPERFORMAT",$conf->entity);


/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/

llxHeader('', 'Shipping Label','','');

$head = prepare_head_admin($user);
dol_fiche_head($head, 'AdminUPS', $langs->trans("ShippinglabelsTitle"), 0, 'order');

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("UPSSetup"),$linkback,'setup');

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
print '<td>'.$langs->trans("ParUPSAccess").'</td>';
print '<td><input type="text" class="flat" name="Access" value="'. ($Access) . '" size="10"></td>';
print '<td>'.$langs->trans("ParUPSDefAccess");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParUPSUserId").'</td>';
print '<td><input type="text" class="flat" name="Userid" value="'. ($Userid) . '" size="10"></td>';
print '<td>'.$langs->trans("ParUPSDefUserId");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParUPSPwd").'</td>';
print '<td><input type="text" class="flat" name="Pwd" value="'. ($Pwd) . '" size="10"></td>';
print '<td>'.$langs->trans("ParUPSDefPwd");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParUPSShipnumber").'</td>';
print '<td><input type="text" class="flat" name="Shipnumber" value="'. ($Shipnumber) . '" size="10"></td>';
print '<td>'.$langs->trans("ParUPSDefShipnumber");
print '</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParUPSEndpoint").'</td>';
print '<td><input type="text" class="flat" name="Endpoint" value="'. ($Endpoint) . '" size="60"></td>';
print '<td>'.$langs->trans("ParUPSDefEndpoint");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParUPSTaxnumber").'</td>';
print '<td><input type="text" class="flat" name="Taxnumber" value="'. ($Taxnumber) . '" size="20"></td>';
print '<td>'.$langs->trans("ParUPSDefTaxnumber");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParUPSShipcharge").'</td>';
print '<td><input type="text" class="flat" name="Shipcharge" value="'. ($Shipcharge) . '" size="10"></td>';
print '<td>'.$langs->trans("ParUPSDefShipcharge");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParUPSServicecode").'</td>';
print '<td><input type="text" class="flat" name="Servicecode" value="'. ($Servicecode) . '" size="10"></td>';
print '<td>'.$langs->trans("ParUPSDefServicecode");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParUPSPackagingcode").'</td>';
print '<td><input type="text" class="flat" name="Packagingcode" value="'. ($Packagingcode) . '" size="10"></td>';
print '<td>'.$langs->trans("ParUPSDefPackagingcode");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Shipformat").'</td>';
print '<td>';
if ($Shipperformat=="")
{
	$Shipperformat="GIF";
}
print '<input type="radio" class="flat" name="Shipperformat" value="GIF" '. ($Shipperformat=='GIF'?'checked':'') . '>&nbsp;&nbsp;GIF&nbsp;&nbsp;';
print '<input type="radio" class="flat" name="Shipperformat" value="PDF" '. ($Shipperformat=='PDF'?'checked':'') . '>&nbsp;&nbsp;PDF&nbsp;&nbsp;';
print '</td>';
print '<td>'.$langs->trans("DefShipformat");
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