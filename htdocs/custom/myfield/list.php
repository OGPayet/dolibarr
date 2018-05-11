<?php
/* Copyright (C) 2015-2016		Charlie BENKE	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/myfield/list.php
 *	\ingroup	myfield
 *	\brief	  Page liste des champs personnalisées
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

dol_include_once('/myfield/class/myfield.class.php');
dol_include_once('/myfield/core/lib/myfield.lib.php');


$langs->load('myfield@myfield');

if (!$user->rights->myfield->lire) accessforbidden();

llxHeader("", "", $langs->trans("Myfield"));

print_fiche_titre($langs->trans("MyfieldList"), "", "myfield@myfield");
print '<br>';

$typefield = (GETPOST("typefield")?GETPOST("typefield"):-1);

print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';

$LT = new Myfield($db);
$lists = $LT->get_all_myfield(GETPOST("contextname"), $typefield);
if ($lists != -1) {
	print '<table id="listtable" class="noborder" width="100%">';
	print '<thead>';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("myFieldLabel").'</th>';
	print '<th>'.$langs->trans("TypeOfMyField").'</th>';
	print '<th>'.$langs->trans("context").'</th>';
	print '<th width=100px>'.$langs->trans("author").'</th>';
	print '<th width=150px>'.$langs->trans("activeMode").'</th>';
	print '<th width=100px>'.$langs->trans("Compulsory").'</th>';
	print '<th width=100px>'.$langs->trans("Color").'</th>';
	print '<th width=100px >'.$langs->trans("Replacement").'</th>';
	print '<th width=22px ></th>';
	print '</tr>';

	print '<tr class="liste_titre">';
	print '<td></td>';
	print '<td align="left">'.SelectMyFieldType($typefield, 1).'</td>';
	print '<td align="left">';
	print '<input type=text name="contextname" value="'.GETPOST("contextname").'">';
	print '</td>';
	print '<td colspan=5 ></td>';
	print '<td  align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="';
	print img_picto($langs->trans("Search"), 'search.png', '', '', 1).'"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print "</tr>\n";

	print '</thead>';
	print '<tbody>';
	foreach ($lists as $list) {
		print "<tr >\n";
		print "\t<td><a href='card.php?rowid=".$list['rowid']."'>";
		print img_picto("", "object_myfield@myfield").($list['label']? $list['label']: "object_Myfield ".$list['rowid'] );
		print "</a></td>\n";
		print "\t<td align='left'>".ShowMyFieldType($list['typefield'])."</td>\n";
		print "\t<td align='left'>".$list['context']."</td>\n";
		print "\t<td align='left'>".$list['author']."</td>\n";
		print "\t<td align='center'>".ShowActiveMode($list['active'])."</td>\n";
		print "\t<td align='center'>".yn($list['compulsory'])."</td>\n";
		print "\t<td align='left'>".$list['color']."</td>\n";
		print "\t<td align='left' >".$list['replacement']."</td>\n";
		print "\t<td align='left' ></td>\n";
		print "</tr>\n";
	}
	print '</tbody>';
	print "</table>";
}
else
	dol_print_error();


print '</form>'."\n";
/*
 * Boutons actions
 */
print '<br>';
print '<div class="tabsAction">';
if ($user->rights->myfield->export)
	print '<a class="butAction" href="card.php?action=export">'.$langs->trans('ExportField').'</a>';
print "</div>";

llxFooter();
$db->close();

// pour empecher le trigger de myfield de s'activer
function llxFooter()
{
}

if (!empty($conf->global->MAIN_USE_JQUERY_DATATABLES)) {
	print "\n";
	print '<script type="text/javascript">'."\n";
	print 'jQuery(document).ready(function() {'."\n";
	print 'jQuery("#listtable").dataTable( {'."\n";

	print '"oColVis": {"buttonText": "'.$langs->trans('showhidecols').'" },'."\n";
	print '"bPaginate": true,'."\n";
	print '"bFilter": false,'."\n";
	print '"sPaginationType": "full_numbers",'."\n";
	print '"bJQueryUI": false,'."\n";
	print '"oLanguage": {"sUrl": "'.$langs->trans('mfdatatabledict').'" },'."\n";
	print '"iDisplayLength": '.$conf->global->MAIN_SIZE_LISTE_LIMIT.','."\n";
	print '"aLengthMenu": [[10, 25, 50, 100, 500, -1], [10, 25, 100, 500, "All"]],'."\n";
	print '"bSort": true,'."\n";
	print '} );'."\n";
	print '});'."\n";
	print '</script>'."\n";
}
