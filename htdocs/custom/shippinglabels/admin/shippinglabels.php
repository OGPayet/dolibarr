<?php
/* Copyright (C) 2015 	   Jean Heimburger      <jean@tiaris.info>
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
 *
 */

/**
 * \file		accountingex/admin/about.php
 * \ingroup		Accounting Expert
 * \brief		Setup page to configure accounting expert module
 */

// Dolibarr environment
$res = @include ("../main.inc.php");
if (! $res && file_exists("../main.inc.php"))
	$res = @include ("../main.inc.php");
if (! $res && file_exists("../../main.inc.php"))
	$res = @include ("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php"))
	$res = @include ("../../../main.inc.php");
if (! $res)
	die("Include of main fails");

// Class
dol_include_once("/core/lib/admin.lib.php");
dol_include_once("/shippinglabels/lib/shippinglabels.lib.php");
require_once DOL_DOCUMENT_ROOT."/contact/class/contact.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

$langs->load("admin");
$langs->load('main');
$langs->load("shippinglabels@shippinglabels");

// Security check
if ($user->societe_id > 0)
	accessforbidden();

$langs->load("admin");
$langs->load("shippinglabels@shippinglabels");

$user->getrights('shippinglabels');

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
$pShipperaddress = GETPOST("Shipperaddress", "alpha");
$pShipperzip = GETPOST("Shipperzip", "alpha");
$pShippertown = GETPOST("Shippertown", "alpha");
$pShipperstate = GETPOST("Shipperstate", "alpha");

$tmparray=getCountry(GETPOST('Shippercountrycode','int'),'all',$db,$langs,0);
if (! empty($tmparray['id']))
{
	$mysoc->country_id   =$tmparray['id'];
	$pShippercountrycode =$tmparray['code'];
	$mysoc->country_label=$tmparray['label'];

	//$s=$mysoc->country_id.':'.$mysoc->country_code.':'.$mysoc->country_label;
	//dolibarr_set_const($db, "MAIN_INFO_SOCIETE_COUNTRY", $s,'chaine',0,'',$conf->entity);
}

//$pShippercountrycode = GETPOST("Shippercountrycode", "alpha");
$pShipperCivility = GETPOST("ShipperCivility", "alpha");
$pShipperattention = GETPOST("Shipperattention", "alpha");
$pShipperattention2 = GETPOST("Shipperattention2", "alpha");
$pShipperphone = GETPOST("Shipperphone", "alpha");
$pShipperphonext = GETPOST("Shipperphonext", "alpha");
$pShipperdesc = GETPOST("Shipperdesc", "alpha");
$pShippermail = GETPOST("Shippermail", "alpha");
// Image or PDF files generated
//$pShipperformat = GETPOST("Shipperformat", "alpha");

if (GETPOST("save", "alpha")<>"")
{
	$db->begin();

	$i=0;

	$i+=dolibarr_set_const($db,'ACCESS',$pAccess,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'USERID',$pUserid,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'PWD',$pPwd,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SHIPNUMBER',$pShipnumber,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'ENDPOINT',$pEndpoint,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'TAXNUMBER',$pTaxnumber,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SHIPPERNAME',$pShipname,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SHIPCHARGE',$pShipcharge,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SERVICECODE',$pServicecode,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'PACKAGINGCODE',$pPackagingcode,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SHIPPERADDRESS',$pShipperaddress,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SHIPPERZIP',$pShipperzip,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SHIPPERTOWN',$pShippertown,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SHIPPERSTATE',$pShipperstate,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SHIPPERCOUNTRYCODE',$pShippercountrycode,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SHIPPERCIVILITY',$pShipperCivility,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SHIPPERATTENTION',$pShipperattention,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SHIPPERATTENTION2',$pShipperattention2,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SHIPPERPHONE',$pShipperphone,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SHIPPERPHONEXT',$pShipperphonext,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SHIPPERDESC',$pShipperdesc,'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'SHIPPERMAIL',$pShippermail,'chaine',0,'',$conf->entity);
	//$i+=dolibarr_set_const($db,'SHIPPERFORMAT',$pShipperformat,'chaine',0,'',$conf->entity);


	if ($i >= 22)
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


$Shipname = dolibarr_get_const($db,"SHIPPERNAME",$conf->entity);
$Shipperaddress = dolibarr_get_const($db,"SHIPPERADDRESS",$conf->entity);
$Shipperzip = dolibarr_get_const($db,"SHIPPERZIP",$conf->entity);
$Shippertown = dolibarr_get_const($db,"SHIPPERTOWN",$conf->entity);
$Shipperstate = dolibarr_get_const($db,"SHIPPERSTATE",$conf->entity);
$Shippercountrycode = dolibarr_get_const($db,"SHIPPERCOUNTRYCODE",$conf->entity);
$ShipperCivility = dolibarr_get_const($db,"SHIPPERCIVILITY",$conf->entity);
$Shipperattention = dolibarr_get_const($db,"SHIPPERATTENTION",$conf->entity);
$Shipperattention2 = dolibarr_get_const($db,"SHIPPERATTENTION2",$conf->entity);
$Shipperphone = dolibarr_get_const($db,"SHIPPERPHONE",$conf->entity);
$Shipperphonext = dolibarr_get_const($db,"SHIPPERPHONEXT",$conf->entity);
$Shipperdesc = dolibarr_get_const($db,"SHIPPERDESC",$conf->entity);
$Shippermail = dolibarr_get_const($db,"SHIPPERMAIL",$conf->entity);
// Image or PDF files generated
//$Shipperformat = dolibarr_get_const($db,"SHIPPERFORMAT",$conf->entity);

/*
 * View
 */

/***************************************************
 * PAGE
 *
 * Put here all code to build page
 ****************************************************/
llxHeader('', 'Shipping Label','','');

$head = prepare_head_admin($user);

dol_fiche_head($head, 'Admin', $langs->trans("ShippinglabelsTitle"), 0, '');

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("GeneralSetup"),$linkback,'setup');

$var=true;
print '<form name="config" action="'.$_SERVER["PHP_SELF"].'" method="post">';

print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';

// Shipper infos
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("ShipperInfos").'</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Shipname").'</td>';
print '<td><input type="text" class="flat" name="Shipname" value="'. ($Shipname) . '" size="30"></td>';
print '<td>'.$langs->trans("DefShipname");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Shipaddress").'</td>';
print '<td><input type="text" class="flat" name="Shipperaddress" value="'. ($Shipperaddress) . '" size="80"></td>';
print '<td>'.$langs->trans("DefShipaddress");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Shipzip").'</td>';
print '<td><input type="text" class="flat" name="Shipperzip" value="'. ($Shipperzip) . '" size="10"></td>';
print '<td>'.$langs->trans("DefShipzip");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Shiptown").'</td>';
print '<td><input type="text" class="flat" name="Shippertown" value="'. ($Shippertown) . '" size="50"></td>';
print '<td>'.$langs->trans("DefShiptown");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Shipstate").'</td>';
print '<td><input type="text" class="flat" name="Shipperstate" value="'. ($Shipperstate) . '" size="10"></td>';
print '<td>'.$langs->trans("DefShipstate");
print '</td>';
print '</tr>';
$var=!$var;

print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Shipcountry").'</td>';
print '<td class="maxwidthonsmartphone">';
print $form->select_country($Shippercountrycode,'Shippercountrycode');
print '</td>';
//print '<td><input type="text" class="flat" name="Shippercountrycode" value="'. ($Shippercountrycode) . '" size="10"></td>';
print '<td>'.$langs->trans("DefShipcountry");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Shipphone").'</td>';
print '<td><input type="text" class="flat" name="Shipperphone" value="'. ($Shipperphone) . '" size="10"></td>';
print '<td>'.$langs->trans("DefShipphone");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Shipphonext").'</td>';
print '<td><input type="text" class="flat" name="Shipperphonext" value="'. ($Shipperphonext) . '" size="10"></td>';
print '<td>'.$langs->trans("DefShipphonext");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShipCivility").'</td>';
print '<td><input type="text" class="flat" name="ShipperCivility" value="'. ($ShipperCivility) . '" size="10"></td>';
print '<td>'.$langs->trans("DefShipCivility");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Shipattention").'</td>';
print '<td><input type="text" class="flat" name="Shipperattention" value="'. ($Shipperattention) . '" size="10"></td>';
print '<td>'.$langs->trans("DefShipattention");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Shipattention2").'</td>';
print '<td><input type="text" class="flat" name="Shipperattention2" value="'. ($Shipperattention2) . '" size="10"></td>';
print '<td>'.$langs->trans("DefShipattention2");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Shipdesc").'</td>';
print '<td><input type="text" class="flat" name="Shipperdesc" value="'. ($Shipperdesc) . '" size="20"></td>';
print '<td>'.$langs->trans("DefShipdesc");
print '</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("Shipmail").'</td>';
print '<td><input type="text" class="flat" name="Shippermail" value="'. ($Shippermail) . '" size="20"></td>';
print '<td>'.$langs->trans("DefShipmail");
print '</td>';
// $var=!$var;
// print '<tr '.$bc[$var].'>';
// print '<td>'.$langs->trans("Shipformat").'</td>';
// print '<td>';
// if ($Shipperformat=="")
// {
	// $Shipperformat="JPG";
// }
// print '<input type="radio" class="flat" name="Shipperformat" value="JPG" '. ($Shipperformat=='JPG'?'checked':'') . '>&nbsp;&nbsp;JPG&nbsp;&nbsp;';
// print '<input type="radio" class="flat" name="Shipperformat" value="PDF" '. ($Shipperformat=='PDF'?'checked':'') . '>&nbsp;&nbsp;PDF&nbsp;&nbsp;';
// print '</td>';
// print '<td>'.$langs->trans("DefShipformat");
// print '</td>';
// print '</tr>';

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
