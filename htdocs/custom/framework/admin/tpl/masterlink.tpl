<?php

/* Copyright (C) 2014		 Support       <support@oscss-shop.fr>
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
 * or see http://www.gnu.org/
 */

global $langs, $conf, $html, $db, $action;

dol_include_once('/masterlink/class/masterlink.class.php');
dol_include_once('/masterlink/class/html.form.masterlink.class.php');


print '<br>';
print '<form method="post" action="">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

if(GETPOST("action") == 'edit' )
	print '<input type="hidden" name="action" value="setlistpath">';

clearstatcache();

print '<table class="noborder" width="100%">';
print '<caption>' . $langs->trans("MasterlinkMySetup") . '</caption>';



print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Origin") . '</td>';
print '<td>' . $langs->trans("Custom") . '</td>';
// print '<td>' . $langs->trans("active") . '</td>';
print "</tr>\n";

$ml = new masterlink($db);
// $form = new htmlformmasterlink($db);
// print_r( $ml->fetchall());


// foreach($ml->fetchall(1) as $k => $v )
// 	$active[$k] = $v->custom;
// ;
//
// print_r($res);
// exit;

foreach( $ml->fetchall() as $k=>$v ) {

// print_r($v);
	$var = !$var;

	if(GETPOST("action") == 'edit' ) {
		print '<tr ' . $bc[$var] . '>';
			print '<td width="20%"><input type="text" name="original['.$v->id.']" value="' . $v->original . '" /></td>';
// 			print '<td width="20%"><input type="text" name=""  value="'. $v->custom . '" /></td>';
			print '<td>';
// 				if($v->nbr > 1)
// 					print $form->select_customlink($v->original, $v->custom);
// 				else
					print '<input type="text" name="custom['.$v->id.']"  value="'. $v->custom . '" />';
			print '</td>';

// 			print '<td>';
// 				if($v->nbr > 1)
// 					$form->select_customlink($v->original);
// 				else
// 					print '<input type="text" name="" value="' . $v->active . '" />';
// 			print '</td>';
// 			print '<td>' . $v->active . '</td>';
		print "</tr>\n";
	}
	else{

		print '<tr ' . $bc[$var] . '>';
			print '<td width="20%">' . $v->original . '</td>';
			print '<td width="20%">' . $v->custom . '</td>';
// 			print '<td>' . $v->active . '</td>';
// 			print '<td>' . $v->active . '</td>';
		print "</tr>\n";
	}
}




if(GETPOST("action") == 'edit' ) {
	print '<tr ' . $bc[$var] . '><td colspan="2" align="right">';
	print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
	print "</td></tr>\n";
}
else {
	print '<tr ' . $bc[$var] . '><td colspan="2" align="right">';
	print '<a  class="button" href="'.dol_buildpath('/masterlink/admin/index.php',2).'?page=config&action=edit">edit</a>';
	print "</td></tr>\n";
}
print '</table>';
print '</form>';
print '<br>';






?>
