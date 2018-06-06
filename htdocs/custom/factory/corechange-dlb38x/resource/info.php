<?php
/* Copyright (C) 2005-2009  Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2009-2013  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2016		Charlie Benke		 <charlie@patas-monkey.com>

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
 *	\file	   htdocs/resource/info.php
 *	\ingroup	resource
 *	\brief	  Page d'affichage des infos d'une fiche d'intervention
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/resource.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/resource.lib.php';

$langs->load('companies');
$langs->load("resource");

$id = GETPOST('id','int');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'resource', $id, 'resource');

$object = new Resource($db);

if ($id > 0)
{
	$object->fetch($id);
}


/*
 *	View
 */

llxHeader('', $langs->trans("Resource"));

$societe = new Societe($db);
$societe->fetch($object->socid);

$head = resource_prepare_head($object);
dol_fiche_head($head, 'info', $langs->trans('ResourceSingular'), 0, 'resource@resource');

$object->info($object->id);

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

llxFooter();
$db->close();