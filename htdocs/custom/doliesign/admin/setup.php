<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018 		Netlogic			<info@netlogic.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    doliesign/admin/setup.php
 * \ingroup doliesign
 * \brief   DoliEsign setup page.
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/doliesign.lib.php';
//require_once "../class/myclass.class.php";

// Translations
if (DoliEsign::checkDolVersion('6.0')) {
	$langs->loadLangs(array("admin", "doliesign@doliesign"));
} else {
	$langs->load("admin");
	$langs->load("doliesign@doliesign");
}


// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');

$arrayofparameters=array(
	'DOLIESIGN_LOGIN'=>array('css'=>'minwidth500','type'=>'text'),
	'DOLIESIGN_PASSWORD'=>array('css'=>'minwidth500','type'=>'password'),
	'DOLIESIGN_API_KEY'=>array('css'=>'minwidth500','type'=>'text'),
	'DOLIESIGN_AUTHENTICATION_MODE'=>array('css'=>'minwidth500','type'=>'text')
);


/*
 * Actions
 */

if ($action == 'update' && is_array($arrayofparameters))
{
	$db->begin();

	$ok=True;
	foreach($arrayofparameters as $key => $val)
	{
		if ($val['type'] != 'yesno' && $val['type'] != 'fieldset') {
			$result=dolibarr_set_const($db,$key,GETPOST($key, 'alpha'),'chaine',0,'',$conf->entity);
			if ($result < 0)
			{
				$ok=False;
				break;
			}
		}
	}

	if (! $error)
	{
		$db->commit();
		if (empty($nomessageinupdate)) setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		$db->rollback();
		if (empty($nomessageinupdate)) setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
	}
}

if ($action == 'setYesNo')
{
	$db->begin();

	// Process common param fields
	if (is_array($_GET))
	{
		foreach($_GET as $key => $val)
		{
			if (preg_match('/^param(\w*)$/', $key, $reg))
			{
				$param=GETPOST("param".$reg[1],'alpha');
				$value=GETPOST("value".$reg[1],'int');
				if ($param)
				{
					$res = dolibarr_set_const($db,$param,$value,'yesno',0,'',$conf->entity);
					if (! $res > 0) $error++;
					if (! $error && $param == 'EMAILTIMESTAMPING_INCLUDE_FILES') {
						$res=dolibarr_set_const($db,'MAIN_DISABLE_PDF_AUTOUPDATE',$value,'chaine',0,'',$conf->entity);
						if (! $res > 0) $error++;
					}
				}
			}
		}
	}

	if (! $error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
		if (empty($nomessageinsetmoduleoptions)) setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
	}
}


/*
 * View
 */

$page_name = "DoliEsignSetup";
llxHeader('', $langs->trans($page_name), 'EN:Module_DoliEsign_EN|FR:Module_DoliEsign_FR|ES:Module_DoliEsign_EN');

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_doliesign@doliesign');

// Configuration header
$head = doliesignAdminPrepareHead();
dol_fiche_head($head, 'settings', '', -1, "doliesign@doliesign");

// Setup page goes here
echo $langs->trans("DoliEsignSetupPage");


print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

foreach($arrayofparameters as $key => $val)
{
	if ($val['type'] == 'fieldset') {
		print '<tr class="liste_titre"><td>';
	} else {
		print '<tr class="oddeven"><td>';
	}

	if ($langs->trans($key.'Tooltip') != $key.'Tooltip') {
		print $form->textwithpicto($langs->trans($key),$langs->trans($key.'Tooltip'));
	} else {
		print $langs->trans($key);
	}

	if ($val['type'] == 'yesno') {
		if ($conf->global->$key == "1")
		{
			print '<td align="left"><a href="'.$_SERVER['PHP_SELF'].'?action=setYesNo&param'.$key.'='.$key.'&value'.$key.'=0">';
			print img_picto($langs->trans("Activated"),'switch_on');
			print '</td></tr>';
		}
		else
		{
			print '<td align="left"><a href="'.$_SERVER['PHP_SELF'].'?action=setYesNo&param'.$key.'='.$key.'&value'.$key.'=1">';
			print img_picto($langs->trans("Disabled"),'switch_off');
			print '</a></td></tr>';
		}
	} else if ($val['type'] == 'fieldset') {
		print '</td><td></td></tr>';
	} else if (! empty($val['type'])) {
		print '</td><td><input type="'.$val['type'].'" name="'.$key.'"  class="flat '.(empty($val['css'])?'minwidth200':$val['css']).'" value="' . $conf->global->$key . '"></td></tr>';
	} else {
		print '</td><td><input name="'.$key.'"  class="flat '.(empty($val['css'])?'minwidth200':$val['css']).'" value="' . $conf->global->$key . '"></td></tr>';
	}
}

print '</table>';

print '<br><div class="center">';
print '<input class="butAction" type="submit" value="'.$langs->trans("Modify").'">';
print '</div>';

print '</form>';
print '<br>';

// Page end
dol_fiche_end();

llxFooter();
$db->close();
