<?php
/* Copyright (C) 2012-2016	Charlie BENKE	 <charlie@patas-monkey.com>
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
 *	  \file	   htdocs/admin/equipement.php
 *		\ingroup	equipement
 *		\brief	  Page to setup equipement module
 */

$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res)
	$res=@include("../../../main.inc.php");		// For "custom" directory


require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load("admin");
$langs->load("errors");
$langs->load("equipement@equipement");

if (! $user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');


/*
 * Actions
 */

if ($action == 'updateMask') {
	$maskconst=GETPOST('maskconst', 'alpha');
	$maskvalue=GETPOST('maskvalue', 'alpha');
	if ($maskconst) $res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);

	if (! $res > 0) $error++;

	if (! $error)
		$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
	else
		$mesg = "<font class='error'>".$langs->trans("Error")."</font>";
}

if ($action == 'specimen') {
	$modele=GETPOST('module', 'alpha');

	$equipement = new Equipement($db);
	$equipement->initAsSpecimen();

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file=dol_buildpath($reldir."core/modules/equipement/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$filefound=1;
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($filefound) {
		require_once($file);

		$module = new $classname($db);

		if ($module->write_file($equipement, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=equipement&file=SPECIMEN.pdf");
			return;
		} else {
			$mesg='<font class="error">'.$module->error.'</font>';
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		$mesg='<font class="error">'.$langs->trans("ErrorModuleNotFound").'</font>';
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
}

// define constants for models generator that need parameters
if ($action == 'setdefaultother') {
	$res = dolibarr_set_const($db, "EQUIPEMENT_SEPARATORLIST", GETPOST("separatorlist"), 'chaine', 0, '', $conf->entity);
	if (! $res > 0) $error++;

	$res = dolibarr_set_const($db, "EQUIPEMENT_SHOWADDITIONNALINFO", GETPOST("showadditionnalinfo"), 'chaine', 0, '', $conf->entity);
	if (! $res > 0) $error++;

	$res = dolibarr_set_const($db, "EQUIPEMENT_BEGINKEYSERIALLIST", GETPOST("beginkeyseriallist"), 'chaine', 0, '', $conf->entity);
	if (! $res > 0) $error++;

	if (! $error)
		$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
	else
		$mesg = "<font class='error'>".$langs->trans("Error")."</font>";
}
if ($action == 'setdefaulserial') {
	$res = dolibarr_set_const($db, "EQUIPEMENT_DEFAULTSERIALMODE", GETPOST("serialMethod"), 'chaine', 0, '', $conf->entity);
	if (! $res > 0) $error++;

	if (! $error)
		$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
	else
		$mesg = "<font class='error'>".$langs->trans("Error")."</font>";
}



// define constants for models generator that need parameters
if ($action == 'setModuleOptions') {
	$post_size=count($_POST);
	for ($i=0;$i < $post_size;$i++) {
		if (array_key_exists('param'.$i, $_POST)) {
			$param=GETPOST("param".$i, 'alpha');
			$value=GETPOST("value".$i, 'alpha');
			if ($param)
				$res = dolibarr_set_const($db, $param, $value, 'chaine', 0, '', $conf->entity);
		}
	}
	if (! $res > 0) $error++;

	if (! $error)
		$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
	else
		$mesg = "<font class='error'>".$langs->trans("Error")."</font>";
}

if ($action == 'set') {
	$label = GETPOST('label', 'alpha');
	$scandir = GETPOST('scandir', 'alpha');

	$type='equipement';
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
	$sql.= " VALUES ('".$db->escape($value)."', '".$type."', ".$conf->entity.", ";
	$sql.= ($label?"'".$db->escape($label)."'":'null').", ";
	$sql.= (! empty($scandir)?"'".$db->escape($scandir)."'":"null");
	$sql.= ")";
	$retsql =$db->query($sql);
}

if ($action == 'del') {
	$type='equipement';
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql.= " WHERE nom = '".$db->escape($value)."'";
	$sql.= " AND type = '".$type."'";
	$sql.= " AND entity = ".$conf->entity;

	if ($db->query($sql))
		if ($conf->global->EQUIPEMENT_ADDON_PDF == "$value")
			dolibarr_del_const($db, 'EQUIPEMENT_ADDON_PDF', $conf->entity);

}

if ($action == 'setdoc') {
	$label = GETPOST('label', 'alpha');
	$scandir = GETPOST('scandir', 'alpha');

	$db->begin();

	if (dolibarr_set_const($db, "EQUIPEMENT_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity))
		$conf->global->EQUIPEMENT_ADDON_PDF = $value;

	// On active le modele
	$type='equipement';

	$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql_del.= " WHERE nom = '".$db->escape($value)."'";
	$sql_del.= " AND type = '".$type."'";
	$sql_del.= " AND entity = ".$conf->entity;
	dol_syslog("equipement.php ".$sql_del);
	$result1=$db->query($sql_del);

	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
	$sql.= " VALUES ('".$value."', '".$type."', ".$conf->entity.", ";
	$sql.= ($label?"'".$db->escape($label)."'":'null').", ";
	$sql.= (! empty($scandir)?"'".$scandir."'":"null");
	$sql.= ")";
	dol_syslog("equipement.php ".$sql);
	$result2=$db->query($sql);
	if ($result1 && $result2)
		$db->commit();
	else
		dol_syslog("equipement.php ".$db->lasterror(), LOG_ERR);
		$db->rollback();
}
if ($action == 'setmod') {
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "EQUIPEMENT_ADDON", $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'showaddinfo') {
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated
	dolibarr_set_const($db, "EQUIPEMENT_SHOWADDITIONNALINFO", $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'chkboxstockmvton') {
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated
	dolibarr_set_const($db, "EQUIPEMENT_CHKBOXSTOCKMVTON", $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'usedluodate') {
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated
	dolibarr_set_const($db, "EQUIPEMENT_USEDLUODATE", $value, 'chaine', 0, '', $conf->entity);
}

/*
 * View
 */

$page_name = $langs->trans('EquipementSetup')." - ".$langs->trans('GeneralSetup');;
$tab = $langs->trans("Equipement");


$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);

$help_url='EN:Equipement_Configuration|FR:Configuration_module_equipement|ES:ConfiguracionEquipement';
llxHeader("", $page_name, $help_url);

$form=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($page_name, $linkback, 'title_setup');


$head = equipement_admin_prepare_head();
dol_fiche_head($head, 'general', $tab, 0, 'equipement@equipement');

/*
 *  Numbering module
 */

print_titre($langs->trans("EquipementsNumberingModule"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td nowrap>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	$dir = dol_buildpath($reldir."core/modules/equipement/");
	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			$var=true;

			while (($file = readdir($handle))!==false) {
				if (! is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
					$filebis = $file;
					$classname = preg_replace('/\.php$/', '', $file);
					// For compatibility
					if (! is_file($dir.$filebis)) {
						$filebis = $file."/".$file.".modules.php";
						$classname = "mod_equipement_".$file;
					}
					if (! class_exists($classname)
						&& is_readable($dir.$filebis)
						&& (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname))
						&& substr($filebis, dol_strlen($filebis)-3, 3) == 'php') {
						// Chargement de la classe de numerotation
						require_once($dir.$filebis);

						$module = new $classname($db);

						// Show modules according to features level
						if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

						if ($module->isEnabled()) {
							$var = !$var;
							print '<tr '.$bc[$var].'><td width="100">';
							echo preg_replace('/mod_equipement_/', '', preg_replace('/\.php$/', '', $file));
							print "</td><td>\n";
							print $module->info();

							print '</td>';

							// Show example of numbering module
							print '<td nowrap="nowrap">';
							$tmp=$module->getExample();
							if (preg_match('/^Error/', $tmp)) {
								$langs->load("errors");
								print '<div class="error">'.$langs->trans($tmp).'</div>';
							} elseif ($tmp=='NotConfigured')
								print $langs->trans($tmp);
							else print $tmp;
							print '</td>'."\n";

							print '<td align="center">';
							//print "> ".$conf->global->EQUIPEMENT_ADDON." - ".$file;
							if ($conf->global->EQUIPEMENT_ADDON == $file || $conf->global->EQUIPEMENT_ADDON.'.php' == $file)
								print img_picto($langs->trans("Activated"), 'switch_on');
							else {
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&';
								print 'value='.preg_replace('/\.php$/', '', $file);
								print '&scandir='.$module->scandir.'&label='.urlencode($module->name).'"';
								print ' alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
							}
							print '</td>';

							$equipement=new Equipement($db);
							$equipement->initAsSpecimen();

							// Example for standard invoice
							$htmltooltip='';
							$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
							$nextval=$module->getNextValue($mysoc, $equipement);
							if ("$nextval" != $langs->trans("NotAvailable")) {
								$htmltooltip.=$langs->trans("NextValueForEquipement").': ';
								if ($nextval)
									$htmltooltip.=$nextval.'<br>';
								else
									$htmltooltip.=$langs->trans($module->error).'<br>';
							}

							print '<td align="center">';
							print $form->textwithpicto('', $htmltooltip, 1, 0);
							// If module is the one used, we show existing errors
							if ($conf->global->EQUIPEMENT_ADDON.'.php' == $file)
								if (! empty($module->error))
									dol_htmloutput_mesg($module->error, '', 'error', 1);
							print '</td>';
							print "</tr>\n";
						}
					}
				}
			}
			closedir($handle);
		}
	}
}

print '</table>';


/*
 *  Document templates generators
 */
print '<br>';
print_titre($langs->trans("EquipementsPDFModules"));

// Load array def with activated templates
$type='equipement';
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$type."'";
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
} else
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
		$dir = dol_buildpath($reldir."core/modules/equipement".$valdir);
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
									print "<td align='center'>\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&value='.$name;
									print '&scandir='.$module->scandir.'&label='.urlencode($module->name).'">';
									print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
									print "</td>";
								}

								// Defaut
								print "<td align='center'>";
								if ($conf->global->EQUIPEMENT_ADDON_PDF == "$name")
									print img_picto($langs->trans("Default"), 'on');
								else
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&value='.$name;
									print '&scandir='.$module->scandir.'&label='.urlencode($module->name).'"';
									print ' alt="'.$langs->trans("Default").'">';
									print img_picto($langs->trans("Disabled"), 'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip =	''.$langs->trans("Name").': '.$module->name;
								$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
								if ($module->type == 'pdf') {
									$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								print '<td align="center">';
								print $form->textwithpicto('', $htmltooltip,1,0);
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


print '<br>';
print_titre($langs->trans("FournishOptions"));
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setdefaultother">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>';
print '<td align="right">'.$langs->trans("Value").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td width=400px>'.$langs->trans("SeparatorList").'</td>';
print '<td align=right >';
$separatorlist=$conf->global->EQUIPEMENT_SEPARATORLIST;
//if ($separatorlist=="__N__")
print '<input type=text name=separatorlist value="'.$separatorlist.'">';
print '</td>';
print '<td>__N__ : CRLF<br>__B__ : TABS </td></tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td width=400px>'.$langs->trans("BeginKeySerialList").'</td>';
print '<td align=right >';
print '<input type=text name=beginkeyseriallist value="'.$conf->global->EQUIPEMENT_BEGINKEYSERIALLIST.'">';
print '</td>';
print '<td></td></tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td colspan=2></td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';
print "</form>\n";
print "</table>\n";
print '<br>';

print_titre($langs->trans("OtherOptions"));
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>';
print '<td align="right">'.$langs->trans("Value").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("DefaultSerialMode").'</td>';
print '<td align=right >';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="action" value="setdefaulserial">';
$arraySerialMethod=array(
				'1'=>$langs->trans("InternalSerial"),
				'2'=>$langs->trans("ExternalSerial"),
				'3'=>$langs->trans("SeriesMode")
);
print $form->selectarray("serialMethod", $arraySerialMethod, $conf->global->EQUIPEMENT_DEFAULTSERIALMODE);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td>';
print '</tr>'."\n";
$var=!$var;

print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowAdditionnalInfoSelling").'</td>';
print '<td align=right >';
if ($conf->global->EQUIPEMENT_SHOWADDITIONNALINFO =="1") {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=showaddinfo&amp;value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=showaddinfo&amp;value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td>';
print '</tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("CheckboxStockMovementDefaultChecked").'</td>';
print '<td align=right >';
if ($conf->global->EQUIPEMENT_CHKBOXSTOCKMVTON =="1") {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=chkboxstockmvton&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=chkboxstockmvton&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td>';
print '</tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("UseDLUODate").'</td>';
print '<td align=right >';
if ($conf->global->EQUIPEMENT_USEDLUODATE =="1") {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=usedluodate&amp;value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=usedluodate&amp;value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td>';
print '</tr>'."\n";

print "</table>\n";
print '<br>';


if ($action == 'chkboxstockmvton') {
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated
	dolibarr_set_const($db, "EQUIPEMENT_CHKBOXSTOCKMVTON", $value, 'chaine', 0, '', $conf->entity);
}


// Module barcode
//if ($conf->barcode->enabled)
//{
//	require_once(DOL_DOCUMENT_ROOT."/core/class/html.formbarcode.class.php");
//	$formbarcode = new FormBarCode($db);
//
//	$var=!$var;
//	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
//	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
//	print '<input type="hidden" name="action" value="setdefaultbarcodetype">';
//	print '<tr '.$bc[$var].'>';
//	print '<td>'.$langs->trans("SetDefaultBarcodeTypeEquipements").'</td>';
//	print '<td width="60" align="right">';
//	print $formbarcode->select_barcode_type($conf->global->EQUIPEMENT_DEFAULT_BARCODE_TYPE,"coder_id",1);
//	print '</td><td align="right">';
//	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
//	print '</td>';
//	print '</tr>';
//	print '</form>';
//}
//print '</table>';
//


/*
 *  Repertoire
 */
print '<br>';
print_titre($langs->trans("PathToDocuments"));

print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td>'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";
print '<tr '.$bc[false].'>'."\n";
print '<td width="140">'.$langs->trans("PathDirectory").'</td>'."\n";
print '<td>'.$conf->equipement->dir_output.'</td>'."\n";
print '</tr>'."\n";
print "</table>\n";


function checkvalue($checkValue, $label='') {
	global $conf, $langs;
	$temp= '<td align=left ><a href="'.$_SERVER["PHP_SELF"].'?action='.$checkValue;
	if ($conf->global->$checkValue =="1")
		$temp.= '&value=0">'.img_picto($langs->trans("Activated"), 'switch_on').'</a>';
	else
		$temp.= '&value=1">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
	$temp.=$label;
	$temp.='</td>';
	return $temp;
}

/*
 *  Infos pour le support
 */
print '<br>';
libxml_use_internal_errors(true);
$sxe = simplexml_load_string(nl2br(file_get_contents('../changelog.xml')));
if ($sxe === false)  {
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
print '<tr '.$bc[true].'><td >'.$langs->trans("DatabaseVersion").'</td><td>'.$db::LABEL." ".$db->getVersion().'</td></tr>'."\n";
print '<tr '.$bc[false].'><td >'.$langs->trans("WebServerVersion").'</td><td>'.$_SERVER["SERVER_SOFTWARE"].'</td></tr>'."\n";
print '<tr>'."\n";
print '<td colspan="2">'.$langs->trans("SupportModuleInformationDesc").'</td></tr>'."\n";
print "</table>\n";

// Show messages
dol_htmloutput_mesg($object->mesg, '', 'ok');

// Footer
llxFooter();
$db->close();
