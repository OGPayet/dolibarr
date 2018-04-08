<?php
/* Copyright (C) 2014 Maxime MANGIN <maxime@tuxserv.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
?>

<!-- BEGIN PHP TEMPLATE -->

<?php


global $user;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

$langs->load("contracts");
$langs->load("contratabonnement@contratabonnement");

$var=true;
foreach($linkedObjectBlock as $key => $objectlink)
{
    $objectlink->fetch_lines();
	$var=!$var;
?>
<tr <?php echo $bc[$var]; ?> >
    <td><?php echo $langs->trans("Subscription"); ?></td>
    <td><?php $url = $objectlink->getNomUrl(1);
        if (file_exists(DOL_DOCUMENT_ROOT ."/custom/contratabonnement")) { // Si on utilise le répertoire custom
            echo str_replace('contrat/card.php', 'custom/contratabonnement/fiche.php', $url);
        }
        else {
            echo str_replace('contrat/card.php', 'contratabonnement/fiche.php', $url);
        }
        ?></td>
    <td></td>
	<td align="center"><?php echo dol_print_date($objectlink->date_contrat,'day'); ?></td>
	<td align="right">&nbsp;</td>
	<td align="right"><?php echo $objectlink->getLibStatut(6); ?></td>
	<td align="right"><a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&dellinkid='.$key; ?>"><?php echo img_delete($langs->transnoentitiesnoconv("RemoveLink")); ?></a></td>

</tr>
<?php } ?>

<!-- END PHP TEMPLATE -->