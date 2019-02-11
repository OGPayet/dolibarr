<?php
/* Copyright (C) 2018       Open-DSI      <support@open-dsi.fr>
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
 *  \file		htdocs/requestmanager/tpl/childrenrequestblock.tpl.php
 *  \ingroup	requestmanager
 *  \brief		Template to show children request to request manager
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

?>

<!-- BEGIN PHP TEMPLATE -->

<?php

global $user;

$langs = $GLOBALS['langs'];
$childrenRequestBlock = $GLOBALS['childrenRequestBlock'];
$stopParentID = $GLOBALS['stopParentID'];

$langs->load("requestmanager@requestmanager");

$var=true;
foreach($childrenRequestBlock as $key => $request)
{
    $trclass=($var?'pair':'impair');
?>
    <tr class="<?php echo $trclass; ?>">
      <td><?php echo $request->getNomUrl(1, 'parent_path_stop_parent_'.$stopParentID); ?></td>
      <td><?php echo $request->ref_ext; ?></td>
      <td align="center"><?php echo $request->getLibType(); ?></td>
	<td align="center"><?php echo dol_print_date($request->date_creation,'day'); ?></td>
	<td align="right"><?php echo $request->getLibStatut(5); ?></td>
    </tr>
<?php
}
?>

<!-- END PHP TEMPLATE -->
