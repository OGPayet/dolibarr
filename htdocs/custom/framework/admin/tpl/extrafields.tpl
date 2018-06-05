<?php
/* Copyright (C) 2016		 Oscss-Shop       <support@oscss-shop.fr>
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

global $langs, $conf, $html, $user, $result, $currentmod, $submodisprev, $master, $subarray, $action;

$type2label = subExtrafields::$type2label;
$tmptype2label = subExtrafields::$tmptype2label;
$extrafields = subExtrafields::$extrafields;
$form = subExtrafields::$form;

	$action=GETPOST('action', 'alpha');
	$attrname=GETPOST('attrname', 'alpha');
	$code=GETPOST('code', 'alpha');
	$elementtype=PageConfigSubModule::$descriptor->code; //Must be the $table_element of the class that manage
// 		var_dump(($elementtype===$code));
	$elementtype=((!empty($code)&&$elementtype!=$code)?$code : $elementtype);

// 	var_dump($elementtype);

// 	print_r($extrafields);






require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_view.tpl.php';

// var_dump($action);


// Buttons
if ($action != 'create' && $action != 'edit')
{
	print '<div class="tabsAction">';
	print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"]."&action=create\">".$langs->trans("NewAttribute").'</a></div>';
	print "</div>";
}


/* ************************************************************************** */
/*                                                                            */
/* Creation of an optional field											  */
/*                                                                            */
/* ************************************************************************** */

if ($action == 'create')
{
	print "<br>";
	print load_fiche_titre($langs->trans('NewAttribute'));
    require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_add.tpl.php';
}

/* ************************************************************************** */
/*                                                                            */
/* Edition of an optional field                                               */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'edit' && ! empty($attrname))
{
	print "<br>";
	print load_fiche_titre($langs->trans("FieldEdition", $attrname));
    require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_edit.tpl.php';
}
