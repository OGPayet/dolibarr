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
 *  \file       /retourproduits/admin/retourproduits_conf.php
 *  \ingroup    contract
 *  \brief      Page d'administration/configuration du module contrat d'abonnement
 */

$res=@include("../../main.inc.php");					// For root directory
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
dol_include_once('/retourproduits/lib/retourproduits.lib.php');

$langs->load("admin");
$langs->load("products");
$langs->load("retourproduits@retourproduits");

// Security check
if (!$user->admin) {accessforbidden();}
$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);

/*
 * Affiche page
 */

llxHeader('',$langs->trans("ProductSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("RetourProduitsSetUp"),$linkback,'setup');

$head = retourproduits_admin_prepare_head();
dol_fiche_head($head, 'general', $tab, 0, 'retourproduits@retourproduits');

$html = new Form($db);
$var = true;


$var=!$var;

/*
 *  Document templates generators
 */
print '<br>';
print_titre($langs->trans("BonRetourPDFModules"));

// Load array def with activated templates
$type='retourproduits';
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
		$dir = dol_buildpath($reldir."core/modules/retourproduits".$valdir);
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
								print "<td align=\"center\">";
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

$db->close();

llxFooter('$Date: 2012/07/10 15:00:00');
?>
