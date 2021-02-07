<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020 Alexis LAURIER <contact@alexislaurier.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    digitalsignaturemanager/admin/setup.php
 * \ingroup digitalsignaturemanager
 * \brief   DigitalSignatureManager setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/digitalsignaturemanager.lib.php';

// Translations
$langs->loadLangs(array("admin", "digitalsignaturemanager@digitalsignaturemanager"));

// Access control
if (!checkPermissionForAdminPages()) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$value = GETPOST('value', 'alpha');

$error = 0;

/*
 * Actions
 */

$arrayOfParametersForProductionSettings = array(
	'DIGITALSIGNATUREMANAGER_UNIVERSIGNPRODUCTIONURL' => array('name' => $langs->trans('DigitalSignatureUniversignApiUrl'), 'tooltip' => $langs->trans('DigitalSignatureUniversignApiUrlToolTip')),
	'DIGITALSIGNATUREMANAGER_UNIVERSIGNPRODUCTIONUSERNAME' => array('name' => $langs->trans('DigitalSignatureUniversignApiUsername')),
	'DIGITALSIGNATUREMANAGER_UNIVERSIGNPRODUCTIONPASSWORD' => array('name' => $langs->trans('DigitalSignatureUniversignApiPassword')),
);
$arrayOfParametersForTestSettings = array(
	'DIGITALSIGNATUREMANAGER_UNIVERSIGNTESTURL' => array('name' => $langs->trans('DigitalSignatureUniversignApiUrl'), 'tooltip' => $langs->trans('DigitalSignatureUniversignTestApiUrlToolTip')),
	'DIGITALSIGNATUREMANAGER_UNIVERSIGNTESTUSERNAME' => array('name' => $langs->trans('DigitalSignatureUniversignApiUsername')),
	'DIGITALSIGNATUREMANAGER_UNIVERSIGNTESTPASSWORD' => array('name' => $langs->trans('DigitalSignatureUniversignApiPassword')),
);
$arrayOfParametersForTestMode = array(
	'DIGITALSIGNATUREMANAGER_TESTMODE' => array('name' => $langs->trans('DigitalSignatureTestMode'), 'tooltip' => $langs->trans('DigitalSignatureTestModeToolTip')),
);;
$arrayOfParametersForAutomaticEventManagment = array(
	'DIGITALSIGNATUREMANAGER_REQUESTEVENT_CREATION' => array('name' => $langs->trans('DigitalSignatureManagerRequestCreateEvent')),
	'DIGITALSIGNATUREMANAGER_REQUESTEVENT_INPROGRESS' => array('name' => $langs->trans('DigitalSignatureManagerRequestInProgressEvent')),
	'DIGITALSIGNATUREMANAGER_REQUESTEVENT_CANCELEDBYOPSY' => array('name' => $langs->trans('DigitalSignatureManagerRequestCanceledByOpsyEvent')),
	'DIGITALSIGNATUREMANAGER_REQUESTEVENT_CANCELEDBYSIGNERS' => array('name' => $langs->trans('DigitalSignatureManagerRequestCanceledBySignersEvent')),
	'DIGITALSIGNATUREMANAGER_REQUESTEVENT_SUCCESS' => array('name' => $langs->trans('DigitalSignatureManagerRequestSuccessEvent')),
	'DIGITALSIGNATUREMANAGER_REQUESTEVENT_FAILED' => array('name' => $langs->trans('DigitalSignatureManagerRequestFailedEvent')),
	'DIGITALSIGNATUREMANAGER_REQUESTEVENT_EXPIRED' => array('name' => $langs->trans('DigitalSignatureManagerRequestExpiredEvent')),
	'DIGITALSIGNATUREMANAGER_REQUESTEVENT_DELETEDINPROVIDER' => array('name' => $langs->trans('DigitalSignatureManagerRequestDeletedInProviderEvent')),
	'DIGITALSIGNATUREMANAGER_REQUESTEVENT_DELETE' => array('name' => $langs->trans('DigitalSignatureManagerRequestDeleteEvent')),
	'DIGITALSIGNATUREMANAGER_PEOPLEEVENT_CREATE' => array('name' => $langs->trans('DigitalSignatureManagerPeopleCreateEvent')),
	'DIGITALSIGNATUREMANAGER_PEOPLEEVENT_WAITINGTOSIGN' => array('name' => $langs->trans('DigitalSignatureManagerPeopleWaitingToSignEvent')),
	'DIGITALSIGNATUREMANAGER_PEOPLEEVENT_SHOULDSIGN' => array('name' => $langs->trans('DigitalSignatureManagerPeopleShouldSignEvent')),
	'DIGITALSIGNATUREMANAGER_PEOPLEEVENT_REFUSED' => array('name' => $langs->trans('DigitalSignatureManagerPeopleRefusedEvent')),
	'DIGITALSIGNATUREMANAGER_PEOPLEEVENT_ACCESSED' => array('name' => $langs->trans('DigitalSignatureManagerPeopleAccessedEvent')),
	'DIGITALSIGNATUREMANAGER_PEOPLEEVENT_CODESENT' => array('name' => $langs->trans('DigitalSignatureManagerPeopleCodeSentEvent')),
	'DIGITALSIGNATUREMANAGER_PEOPLEEVENT_PENDINGDDOCS' => array('name' => $langs->trans('DigitalSignatureManagerPeoplePendingDocsEvent')),
	'DIGITALSIGNATUREMANAGER_PEOPLEEVENT_PENDINGVALIDATION' => array('name' => $langs->trans('DigitalSignatureManagerPeoplePendingDocsValidationEvent')),
	'DIGITALSIGNATUREMANAGER_PEOPLEEVENT_SUCCESS' => array('name' => $langs->trans('DigitalSignatureManagerPeopleSuccessEvent')),
	'DIGITALSIGNATUREMANAGER_PEOPLEEVENT_FAILED' => array('name' => $langs->trans('DigitalSignatureManagerPeopleFailedEvent')),
	'DIGITALSIGNATUREMANAGER_PEOPLEEVENT_PROCESSSTOPPEDBEFORE' => array('name' => $langs->trans('DigitalSignatureManagerPeopleProcessStoppedBeforeEvent')),
	'DIGITALSIGNATUREMANAGER_PEOPLEEVENT_DELETE' => array('name' => $langs->trans('DigitalSignatureManagerPeopleDeleteEvent')),

);

$arrayOfParametersOfMiscellaneous = array(
	//	'DIGITALSIGNATUREMANAGER_CHECKBOX_ADDNUMBEROFPAGE'=>array('name'=>$langs->trans('DigitalSignatureManagerCheckBoxAddNumberOfPage'))
);

$arrayofparameters = array_merge($arrayOfParametersForProductionSettings, $arrayOfParametersForTestSettings, $arrayOfParametersForTestMode, $arrayOfParametersForAutomaticEventManagment, $arrayOfParametersOfMiscellaneous);

if ((float) DOL_VERSION >= 6) {
	include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';
}

if ($action == 'updateMask') {
	$maskconstorder = GETPOST('maskconstorder', 'alpha');
	$maskorder = GETPOST('maskorder', 'alpha');

	if ($maskconstorder) $res = dolibarr_set_const($db, $maskconstorder, $maskorder, 'chaine', 0, '', $conf->entity);

	if (!$res > 0) $error++;


	$maskconstbom = GETPOST('maskconstBom', 'alpha');
	$maskDigitalSignatureRequest = GETPOST('maskDigitalSignatureRequest', 'alpha');

	if ($maskconstbom) $res = dolibarr_set_const($db, $maskconstbom, $maskDigitalSignatureRequest, 'chaine', 0, '', $conf->entity);

	if (!$res > 0) $error++;

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');
	$tmpobjectkey = GETPOST('object');

	$tmpobject = new $tmpobjectkey($db);
	$tmpobject->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$filefound = 0;
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir . "core/modules/digitalsignaturemanager/doc/pdf_" . $modele . "_" . strtolower($tmpobjectkey) . ".modules.php", 0);
		if (file_exists($file)) {
			$filefound = 1;
			$classname = "pdf_" . $modele;
			break;
		}
	}

	if ($filefound) {
		require_once $file;

		$module = new $classname($db);

		if ($module->write_file($tmpobject, $langs) > 0) {
			header("Location: " . DOL_URL_ROOT . "/document.php?modulepart=" . strtolower($tmpobjectkey) . "&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, null, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
}

// Activate a model
elseif ($action == 'set') {
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$tmpobjectkey = GETPOST('object');

	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$constforval = strtoupper($tmpobjectkey) . '_ADDON_PDF';
		if ($conf->global->$constforval == "$value") dolibarr_del_const($db, $constforval, $conf->entity);
	}
}

// Set default model
elseif ($action == 'setdoc') {
	$tmpobjectkey = GETPOST('object');
	$constforval = strtoupper($tmpobjectkey) . '_ADDON_PDF';
	if (dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity)) {
		// The constant that was read before the new set
		// We therefore requires a variable to have a coherent view
		$conf->global->$constforval = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'setmod') {
	// TODO Check if numbering module chosen can be activated
	// by calling method canBeActivated
	$tmpobjectkey = GETPOST('object');
	$constforval = 'DIGITALSIGNATUREMANAGER_' . strtoupper($tmpobjectkey) . "_ADDON";
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}



/*
 * View
 */

$form = new Form($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$page_name = "DigitalSignatureManagerSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_digitalsignaturemanager@digitalsignaturemanager');

// Configuration header
$head = digitalsignaturemanagerAdminPrepareHead();
dol_fiche_head($head, 'settings', '', -1, "digitalsignaturemanager@digitalsignaturemanager");

// Setup page goes here
echo '<span class="opacitymedium">' . $langs->trans("DigitalSignatureManagerSetupPage") . '</span><br><br>';

$moduledir = 'digitalsignaturemanager';
$myTmpObjects = array();
$myTmpObjects['DigitalSignatureRequest'] = array('includerefgeneration' => 1, 'includedocgeneration' => 0);


foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
	if ($myTmpObjectArray['includerefgeneration']) {
		/*
		 * Orders Numbering model
		 */
		$setupnotempty++;

		print load_fiche_titre($langs->trans("DigitalSignatureManagerNumberingModules", $myTmpObjectKey), '', '');

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td>' . $langs->trans("Name") . '</td>';
		print '<td>' . $langs->trans("Description") . '</td>';
		print '<td class="nowrap">' . $langs->trans("Example") . '</td>';
		print '<td class="center" width="60">' . $langs->trans("Status") . '</td>';
		print '<td class="center" width="16">' . $langs->trans("ShortInfo") . '</td>';
		print '</tr>' . "\n";

		clearstatcache();

		foreach ($dirmodels as $reldir) {
			$dir = dol_buildpath($reldir . "core/modules/" . $moduledir);

			if (is_dir($dir)) {
				$handle = opendir($dir);
				if (is_resource($handle)) {
					while (($file = readdir($handle)) !== false) {
						if (strpos($file, 'mod_' . strtolower($myTmpObjectKey) . '_') === 0 && substr($file, dol_strlen($file) - 3, 3) == 'php') {
							$file = substr($file, 0, dol_strlen($file) - 4);

							require_once $dir . '/' . $file . '.php';

							$module = new $file($db);

							// Show modules according to features level
							if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

							if ($module->isEnabled()) {
								dol_include_once('/' . $moduledir . '/class/' . strtolower($myTmpObjectKey) . '.class.php');

								print '<tr class="oddeven"><td>' . $module->name . "</td><td>\n";
								print $module->info();
								print '</td>';

								// Show example of numbering model
								print '<td class="nowrap">';
								$tmp = $module->getExample();
								if (preg_match('/^Error/', $tmp)) print '<div class="error">' . $langs->trans($tmp) . '</div>';
								elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
								else print $tmp;
								print '</td>' . "\n";

								print '<td class="center">';
								$constforvar = 'DIGITALSIGNATUREMANAGER_' . strtoupper($myTmpObjectKey) . '_ADDON';
								if ($conf->global->$constforvar == $file) {
									print img_picto($langs->trans("Activated"), 'switch_on');
								} else {
									print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setmod&object=' . strtolower($myTmpObjectKey) . '&value=' . $file . '">';
									print img_picto($langs->trans("Disabled"), 'switch_off');
									print '</a>';
								}
								print '</td>';

								$mytmpinstance = new $myTmpObjectKey($db);
								$mytmpinstance->initAsSpecimen();

								// Info
								$htmltooltip = '';
								$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';

								$nextval = $module->getNextValue($mytmpinstance);
								if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
									$htmltooltip .= '' . $langs->trans("NextValue") . ': ';
									if ($nextval) {
										if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
											$nextval = $langs->trans($nextval);
										$htmltooltip .= $nextval . '<br>';
									} else {
										$htmltooltip .= $langs->trans($module->error) . '<br>';
									}
								}

								print '<td class="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								print "</tr>\n";
							}
						}
					}
					closedir($handle);
				}
			}
		}
		print "</table><br>\n";
	}

	if ($myTmpObjectArray['includedocgeneration']) {
		/*
		 * Document templates generators
		 */
		$setupnotempty++;
		$type = strtolower($myTmpObjectKey);

		print load_fiche_titre($langs->trans("DocumentModules", $myTmpObjectKey), '', '');

		// Load array def with activated templates
		$def = array();
		$sql = "SELECT nom";
		$sql .= " FROM " . MAIN_DB_PREFIX . "document_model";
		$sql .= " WHERE type = '" . $type . "'";
		$sql .= " AND entity = " . $conf->entity;
		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num_rows = $db->num_rows($resql);
			while ($i < $num_rows) {
				$array = $db->fetch_array($resql);
				array_push($def, $array[0]);
				$i++;
			}
		} else {
			dol_print_error($db);
		}

		print "<table class=\"noborder\" width=\"100%\">\n";
		print "<tr class=\"liste_titre\">\n";
		print '<td>' . $langs->trans("Name") . '</td>';
		print '<td>' . $langs->trans("Description") . '</td>';
		print '<td class="center" width="60">' . $langs->trans("Status") . "</td>\n";
		print '<td class="center" width="60">' . $langs->trans("Default") . "</td>\n";
		print '<td class="center" width="38">' . $langs->trans("ShortInfo") . '</td>';
		print '<td class="center" width="38">' . $langs->trans("Preview") . '</td>';
		print "</tr>\n";

		clearstatcache();

		foreach ($dirmodels as $reldir) {
			foreach (array('', '/doc') as $valdir) {
				$realpath = $reldir . "core/modules/" . $moduledir . $valdir;
				$dir = dol_buildpath($realpath);

				if (is_dir($dir)) {
					$handle = opendir($dir);
					if (is_resource($handle)) {
						while (($file = readdir($handle)) !== false) {
							$filelist[] = $file;
						}
						closedir($handle);
						arsort($filelist);

						foreach ($filelist as $file) {
							if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
								if (file_exists($dir . '/' . $file)) {
									$name = substr($file, 4, dol_strlen($file) - 16);
									$classname = substr($file, 0, dol_strlen($file) - 12);

									require_once $dir . '/' . $file;
									$module = new $classname($db);

									$modulequalified = 1;
									if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified = 0;
									if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified = 0;

									if ($modulequalified) {
										print '<tr class="oddeven"><td width="100">';
										print(empty($module->name) ? $name : $module->name);
										print "</td><td>\n";
										if (method_exists($module, 'info')) print $module->info($langs);
										else print $module->description;
										print '</td>';

										// Active
										if (in_array($name, $def)) {
											print '<td class="center">' . "\n";
											print '<a href="' . $_SERVER["PHP_SELF"] . '?action=del&value=' . $name . '">';
											print img_picto($langs->trans("Enabled"), 'switch_on');
											print '</a>';
											print '</td>';
										} else {
											print '<td class="center">' . "\n";
											print '<a href="' . $_SERVER["PHP_SELF"] . '?action=set&value=' . $name . '&amp;scan_dir=' . $module->scandir . '&amp;label=' . urlencode($module->name) . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
											print "</td>";
										}

										// Default
										print '<td class="center">';
										$constforvar = 'DIGITALSIGNATUREMANAGER_' . strtoupper($myTmpObjectKey) . '_ADDON';
										if ($conf->global->$constforvar == $name) {
											print img_picto($langs->trans("Default"), 'on');
										} else {
											print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setdoc&value=' . $name . '&amp;scan_dir=' . $module->scandir . '&amp;label=' . urlencode($module->name) . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
										}
										print '</td>';

										// Info
										$htmltooltip = '' . $langs->trans("Name") . ': ' . $module->name;
										$htmltooltip .= '<br>' . $langs->trans("Type") . ': ' . ($module->type ? $module->type : $langs->trans("Unknown"));
										if ($module->type == 'pdf') {
											$htmltooltip .= '<br>' . $langs->trans("Width") . '/' . $langs->trans("Height") . ': ' . $module->page_largeur . '/' . $module->page_hauteur;
										}
										$htmltooltip .= '<br>' . $langs->trans("Path") . ': ' . preg_replace('/^\//', '', $realpath) . '/' . $file;

										$htmltooltip .= '<br><br><u>' . $langs->trans("FeaturesSupported") . ':</u>';
										$htmltooltip .= '<br>' . $langs->trans("Logo") . ': ' . yn($module->option_logo, 1, 1);
										$htmltooltip .= '<br>' . $langs->trans("MultiLanguage") . ': ' . yn($module->option_multilang, 1, 1);

										print '<td class="center">';
										print $form->textwithpicto('', $htmltooltip, 1, 0);
										print '</td>';

										// Preview
										print '<td class="center">';
										if ($module->type == 'pdf') {
											print '<a href="' . $_SERVER["PHP_SELF"] . '?action=specimen&module=' . $name . '&object=' . $myTmpObjectKey . '">' . img_object($langs->trans("Preview"), 'generic') . '</a>';
										} else {
											print img_object($langs->trans("PreviewNotAvailable"), 'generic');
										}
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
	}
}


print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

// Universign Production Api Informations
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefield" style="min-width:520px;">' . $langs->trans("DigitalSignatureParameterProductionUniversignSettings") . '</td>';
print '<td class="minwidth300">' . $langs->trans("Value") . '</td>';
print '</tr>';

foreach ($arrayOfParametersForProductionSettings as $key => $parameter) {
	print '<tr class="oddeven">';
	print '<td>';
	print $form->textwithpicto($parameter['name'], $parameter['tooltip']);
	print '</td>';
	print '<td>';
	print '<input name="' . $key . '"  class="flat minwidth300" value="' . $conf->global->$key . '">';
	print '</td>';
}

print '</table>';

// Universign Test Api Informations

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefield" style="min-width:520px;">' . $langs->trans("DigitalSignatureParameterTestUniversignSettings") . '</td>';
print '<td class="minwidth300">' . $langs->trans("Value") . '</td>';
print '</tr>';

foreach ($arrayOfParametersForTestSettings as $key => $parameter) {
	print '<tr class="oddeven">';
	print '<td>';
	print $form->textwithpicto($parameter['name'], $parameter['tooltip']);
	print '</td>';
	print '<td>';
	print '<input name="' . $key . '"  class="flat minwidth300" value="' . $conf->global->$key . '">';
	print '</td>';
}

print '</table>';

//Test Mode
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefield" style="min-width:500px;">' . $langs->trans("DigitalSignatureManagerTestMode") . '</td>';
print '<td class="minwidth300">' . $langs->trans("Value") . '</td>';
print '</tr>';
foreach ($arrayOfParametersForTestMode as $key => $parameter) {
	print '<tr class="oddeven">';
	print '<td>';
	print $form->textwithpicto($parameter['name'], $parameter['tooltip']);
	print '</td>';
	print '<td>';
	print '<input size="64" type="hidden" name="' . $key . '" value="0">';
	print '<input size="64" type="checkbox" name="' . $key . '" value="1" ';
	print !empty($conf->global->$key) ? 'checked="checked"' : '';
	print '>';
	print '</td>';
}

print '</table>';

//Miscellaneous
// print '<table class="noborder centpercent">';
// print '<tr class="liste_titre">';
// print '<td class="titlefield" style="min-width:500px;">'.$langs->trans("DigitalSignatureManagerMiscellaneous").'</td>';
// print '<td class="minwidth300">'.$langs->trans("Value").'</td>';
// print '</tr>';
// foreach($arrayOfParametersOfMiscellaneous as $key=>$parameter) {
// 	print '<tr class="oddeven">';
// 	print '<td>';
// 	print $form->textwithpicto($parameter['name'], $parameter['tooltip']);
// 	print '</td>';
// 	print '<td>';
// 	print '<input size="64" type="hidden" name="' . $key . '" value="0">';
// 	print '<input size="64" type="checkbox" name="' . $key . '" value="1" ';
// 	print !empty($conf->global->$key) ? 'checked="checked"' : '';
// 	print '>';
// 	print '</td>';
// }

// print '</table>';

//Automatic Event Managment
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefield" style="min-width:500px;">' . $langs->trans("DigitalSignatureManagerAutomaticEventManagment") . '</td>';
print '<td class="minwidth300">' . $langs->trans("Value") . '</td>';
print '</tr>';
foreach ($arrayOfParametersForAutomaticEventManagment as $key => $parameter) {
	print '<tr class="oddeven">';
	print '<td>';
	print $form->textwithpicto($parameter['name'], $parameter['tooltip']);
	print '</td>';
	print '<td>';
	print '<input size="64" type="hidden" name="' . $key . '" value="0">';
	print '<input size="64" type="checkbox" name="' . $key . '" value="1" ';
	print !empty($conf->global->$key) ? 'checked="checked"' : '';
	print '>';
	print '</td>';
	print '</td>';
}

print '</table>';

print '<br><div class="center">';
print '<input class="button" type="submit" value="' . $langs->trans("Save") . '">';
print '</div>';
print '<br>';

print '</form>';
print '<br>';

// Page end
dol_fiche_end();

llxFooter();
$db->close();
