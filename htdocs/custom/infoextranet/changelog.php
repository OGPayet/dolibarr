<?php
/* Copyright (C) 2017-2018	Eric GROULT			    <eric@code42.fr>
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

$res = 0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");         // to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");

require_once 'lib/parsedown-master/Parsedown.php';
require_once 'lib/output.lib.php';

llxHeader();

$head = generateDocumentationHeader();

dol_fiche_head($head, 'changelog', "InfoExtranet", 0, 'infoextranet@infoextranet');

$Parsedown = new Parsedown();

$buffer = file_get_contents('./CHANGELOG.md');

print load_fiche_titre('Changelog','','title_setup');
print '<div class="tabBar forceListStyle">';
echo $Parsedown->text($buffer);
echo '</div></div>';

llxFooter();
