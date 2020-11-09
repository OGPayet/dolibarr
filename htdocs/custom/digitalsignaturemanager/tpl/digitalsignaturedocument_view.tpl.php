<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017		Juanjo Menent		<jmenent@2byte.es>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * Need to have following variables defined:
 * $object - digitalsignaturedocument
 * $conf
 * $langs
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $disableedit, $disablemove, $disableremove
 * $action
 * $num - number of lines
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}

// add html5 elements
$domData  = ' data-element="'.$object->element.'"';
$domData .= ' data-id="'.$object->id.'"';

$coldisplay = 0;
?>
<!-- BEGIN PHP TEMPLATE digitalsignaturedocument_view.tpl.php -->
<!-- Start Of row -->
<tr  id="digitalsignaturedocument-<?php print $object->id?>" class="drag drop oddeven" <?php print $domData; ?> >

<!-- Number Column -->
<?php if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
	<td class="linecolnum center"><?php $coldisplay++; ?><?php print ($i + 1); ?></td>
<?php } ?>
	<td class="linecoldescription minwidth300imp"><?php $coldisplay++; ?><div id="line_<?php print $line->id; ?>"></div>

</td>;
<?php

//Action button Column
if ($object->isEditable() && !empty($permtoedit) && $action != 'selectlines') {
	//edit button
	print '<td class="linecoledit center">';
	$coldisplay++;
	if($object->isEditable() && empty($disableedit)) {
		?>
		<a class="editfielda reposition" href="<?php print $_SERVER["PHP_SELF"].'?id='.$object->fk_digitalsignaturerequest.'&amp;action=editline&amp;lineid='.$object->id.'#line_'.$object->id; ?>">
		<?php print img_edit().'</a>';
	}
	print '</td>';

	//delete button
	print '<td class="linecoldelete center">';
	$coldisplay++;
	if (!empty($disableremove)) {
		print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->fk_digitalsignaturerequest.'&amp;action=ask_deleteline&amp;lineid='.$object->id.'">';
		print img_delete();
		print '</a>';
	}
	print '</td>';

	//order button
	if ($num > 1 && $conf->browser->layout != 'phone' &&  empty($disablemove)) {
		print '<td class="linecolmove tdlineupdown center">';
		$coldisplay++;
		if ($i > 0) { ?>
			<a class="lineupdown" href="<?php print $_SERVER["PHP_SELF"].'?id='.$object->fk_digitalsignaturerequest.'&amp;action=up&amp;rowid='.$object->id; ?>">
			<?php print img_up('default', 0, 'imgupforline'); ?>
			</a>
		<?php }
		if ($i < $num - 1) { ?>
			<a class="lineupdown" href="<?php print $_SERVER["PHP_SELF"].'?id='.$object->fk_digitalsignaturerequest.'&amp;action=down&amp;rowid='.$object->id; ?>">
			<?php print img_down('default', 0, 'imgdownforline'); ?>
			</a>
		<?php }
		print '</td>';
	} else {
		print '<td '.(($conf->browser->layout != 'phone' && empty($disablemove)) ? ' class="linecolmove tdlineupdown center"' : ' class="linecolmove center"').'></td>';
		$coldisplay++;
	}
} else {
	print '<td colspan="3"></td>';
	$coldisplay = $coldisplay + 3;
}

if ($action == 'selectlines') { ?>
	<td class="linecolcheck center"><input type="checkbox" class="linecheckbox" name="line_checkbox[<?php print $i + 1; ?>]" value="<?php print $object->id; ?>" ></td>
<?php }

print "</tr>\n";

//Line extrafield
if (!empty($extrafields))
{
	print $line->showOptionals($extrafields, 'view', array('style'=>'class="drag drop oddeven"', 'colspan'=>$coldisplay), '', '', 1);
}

print "<!-- END PHP TEMPLATE digitalsignaturedocument_view.tpl.php -->\n";
