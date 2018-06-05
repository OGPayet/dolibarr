<?php
/* Copyright (C)  SuperAdmin
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
 * \file    core/tpl/mytemplate.tpl.php
 * \ingroup warehousechild
 * \brief   Example template.
 *
 * Put detailed description here.
 */
global $langs;
// Protection to avoid direct call of template
if (empty($objects) || ! is_array($objects))
{
	print "Error, template page can't be called as URL";
	exit;
}
foreach ($objects as $obj) {
print '<tr>';


print '<td>';
print $langs->trans(ucfirst($objecttype));
print '</td>';

print '<td>';
print $obj->getNomUrl(1,'stock');
print '</td>';


print '<td align="right">';
print price($obj->price);
print '</td>';

print '</tr>';
}