<?php
/* Copyright (C) 2014-2016		Charlie Benke	<charlie@patas-monkey.com>
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


print '<!-- BEGIN PHP TEMPLATE -->';

$langs = $GLOBALS['langs'];
$db = $GLOBALS['db'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

$langs->load("factory@factory");
print '</table>';

print '<br>';
print_titre($langs->trans('RelatedFactory'));

?>

<table class="noborder allwidth">
<tr class="liste_titre">
	<td align="left" width=25% ><?php echo $langs->trans("Ref"); ?></td>
	<td align="center" width=25% ><?php echo $langs->trans("Product"); ?></td>
	<td align="right" width=25% ><?php echo $langs->trans("Qty"); ?></td>
	<td align="right"><?php echo $langs->trans("Status"); ?></td>
</tr>

<?php
$var=true;
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
$productlink = new Product($db);

foreach ($linkedObjectBlock as $object) {
	//var_dump($object);
	$var=!$var;
	$productlink->fetch($object->fk_product);

	print '<tr'.$GLOBALS['bc'][$var].' >';
?>
	<td align="left"><a href="<?php echo $object->getNomUrl(1); ?></td>
	<td align="center"><?php echo $productlink->getNomUrl(1); ?></td>
	<td align="right"><?php echo $object->qty_planned; ?></td>
	<td align="right"><?php echo $object->getLibStatut(3); ?></td>

<?php
	print '</tr>'."\n";
}
print '</table>'."\n";

print '<!-- END PHP TEMPLATE -->'."\n";