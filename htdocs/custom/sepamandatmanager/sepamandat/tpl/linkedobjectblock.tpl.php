<?php
/* Copyright (C) 2010-2011  Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2013       Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos García <marcosgdf@gmail.com>
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
 *  \file		htdocs/requestmanager/tpl/linkedobjectblock.tpl.php
 *  \ingroup	requestmanager
 *  \brief		Template to show objects linked to request manager
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
global $linkedObjectBlock;

$langs->load("digitalsignaturemanager@digitalsignaturemanager");

$var=true;
foreach($linkedObjectBlock as $key => $objectlink)
{
	$trclass=($var?'pair':'impair');
?>
    <tr class="<?php echo $trclass; ?>">
        <td><?php echo $langs->trans("SepaMandatLinkedObjectName"); ?></td>
        <td><?php echo $objectlink->getNomUrl(1); ?></td>
        <td colspan="3" align="center"></td>
        <td align="right"><?php echo $objectlink->getLibStatut(3); ?></td>
        <td align="right"><a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&dellinkid='.$key; ?>"><?php echo img_delete($langs->transnoentitiesnoconv("RemoveLink")); ?></a></td>
    </tr>
<?php
//$var = !$var;
}
?>

<!-- END PHP TEMPLATE -->
