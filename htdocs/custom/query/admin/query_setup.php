<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
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
 * 	\file		admin/query.php
 * 	\ingroup	query
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
require('../config.php');

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/query.lib.php';

// Translations
$langs->load("query@query");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		if($code == 'QUERY_HOME_SELECTOR' && GETPOST($code) == 0)
		{
			dolibarr_del_const($db, 'QUERY_DASHBOARD_OPEN_IN_NEW_TAB', 0);
		}

		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */
$page_name = "querySetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = queryAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104778Name"),
    0,
    "query@query"
);

// Setup page goes here
$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";


// Example with a yes / no select
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("set_QUERY_GRAPH_PIEHOLE").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_QUERY_GRAPH_PIEHOLE">';
print $form->selectarray('QUERY_GRAPH_PIEHOLE', array('0'=>'0%',  '0.1'=>'10%',  '0.3'=>'30%',  '0.5'=>'50%',  '0.7'=>'70%',  '0.9'=>'90%'), $conf->global->QUERY_GRAPH_PIEHOLE );
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("set_QUERY_GRAPH_LINESTYLE").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_QUERY_GRAPH_LINESTYLE">';
print $form->selectarray('QUERY_GRAPH_LINESTYLE', array('function'=>$langs->trans('Souple'), 'none'=>$langs->trans('Dur')), $conf->global->QUERY_GRAPH_LINESTYLE );
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("set_QUERY_SHOW_PDF_TRANSFORM").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_QUERY_SHOW_PDF_TRANSFORM">';
print $form->selectyesno("QUERY_SHOW_PDF_TRANSFORM",$conf->global->QUERY_SHOW_PDF_TRANSFORM,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("set_ABRICOT_WKHTMLTOPDF_CMD").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300" style="white-space:nowrap;">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_ABRICOT_WKHTMLTOPDF_CMD">';
$formCore=new TFormCore;
print $formCore->texte('', 'ABRICOT_WKHTMLTOPDF_CMD', empty($conf->global->ABRICOT_WKHTMLTOPDF_CMD) ? '' : $conf->global->ABRICOT_WKHTMLTOPDF_CMD, 80,255,' placeholder="wkhtmltopdf" ');
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("set_ABRICOT_CONVERTPDF_CMD").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300" style="white-space:nowrap;">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_ABRICOT_CONVERTPDF_CMD">';
$formCore=new TFormCore;
print $formCore->texte('', 'ABRICOT_CONVERTPDF_CMD', empty($conf->global->ABRICOT_CONVERTPDF_CMD) ? '' : $conf->global->ABRICOT_CONVERTPDF_CMD, 80, 255,' placeholder="libreoffice --invisible --norestore --headless --convert-to pdf --outdir " ');
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("set_ABRICOT_NB_MAX_RESULT_SQL").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300" style="white-space:nowrap;">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_ABRICOT_NB_MAX_RESULT_SQL">';
$formCore=new TFormCore;
print $formCore->texte('', 'ABRICOT_NB_MAX_RESULT_SQL', empty($conf->global->ABRICOT_NB_MAX_RESULT_SQL) ? 2000 : $conf->global->ABRICOT_NB_MAX_RESULT_SQL, 10, 50);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("set_QUERY_SET_TIME_LIMIT").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300" style="white-space:nowrap;">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_QUERY_SET_TIME_LIMIT">';
$formCore=new TFormCore;
print $formCore->texte('', 'QUERY_SET_TIME_LIMIT', !isset($conf->global->QUERY_SET_TIME_LIMIT) ? ini_get('max_execution_time') : $conf->global->QUERY_SET_TIME_LIMIT, 10, 50);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("set_QUERY_HOME_SELECTOR").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_QUERY_HOME_SELECTOR">';
print $form->selectyesno("QUERY_HOME_SELECTOR",$conf->global->QUERY_HOME_SELECTOR,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

if(! empty($conf->global->QUERY_HOME_SELECTOR))
{
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("set_QUERY_DASHBOARD_OPEN_IN_NEW_TAB").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_QUERY_DASHBOARD_OPEN_IN_NEW_TAB">';
	print $form->selectyesno("QUERY_DASHBOARD_OPEN_IN_NEW_TAB",$conf->global->QUERY_DASHBOARD_OPEN_IN_NEW_TAB,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';
}

print '</table>';

llxFooter();

$db->close();