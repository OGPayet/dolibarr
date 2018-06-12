<?php
/*
 * Copyright (C) 2014 		Florian HENRY 		<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2017 	Charlene BENKE		<charlie@patas-monkey.com>
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
 * \file factory/info.php
 * \ingroup factory
 * \brief info of factory
 */
$res = @include("../main.inc.php"); // For root directory
if (! $res)
	$res = @include("../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

dol_include_once('/factory/class/factory.class.php');
dol_include_once('/factory/core/lib/factory.lib.php');

require_once(DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php');

// Security check
if (! $user->rights->factory->lire)
	accessforbidden();

$id = GETPOST('id', 'int');

/*
 * View
 */

llxHeader('', $langs->trans("Factory"));

$object = new Factory($db);
$object->info($id);

$head = factory_prepare_head($object, $user);

dol_fiche_head($head, 'infos', $langs->trans("Factory"), 0, 'factory@factory');

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';
print '</div>';

llxFooter();
$db->close();