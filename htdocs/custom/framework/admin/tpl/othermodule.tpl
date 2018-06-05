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

global $langs, $conf, $html, $list;


print '<br>';

print "<p>Retrouvez la liste de l'ensemble de nos modules </p>";

// Dependencies
// 		$this->depends = array();
print '<ul>';

foreach ($list as $row) {
    print '<li style="margin-bottom:20px;"><h2>';
    if(isset($row->url))
        print '<a href="'.$row->url.'" target="_blank">';
    list($titre,$soustitre) = explode(':',$row->titre);
    print "$titre<small>$soustitre</small>";
    if(isset($row->url))
        print '</a>';
    else print ' (Prochainement)';
    print '</h2></li>';
}

print '</ul>';
