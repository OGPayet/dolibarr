<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Florian Henry		  	<florian.henry@open-concept.pro>
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
 *	\file       htdocs/comm/propal/note.php
 *	\ingroup    propal
 *	\brief      Fiche d'information sur une proposition commerciale
 */


namespace CORE;
$res = 0;
// if (!$res && file_exists("../main.inc.php"))
//     $res = @include("../main.inc.php");
if (!$res && file_exists("../../main.inc.php"))
    $res = @include("../../main.inc.php");
if (!$res && file_exists("../../../main.inc.php"))
    $res = @include("../../../main.inc.php");
if (!$res && file_exists("../../../../main.inc.php"))
    $res = @include("../../../../main.inc.php");
if (!$res && file_exists("../../../dolibarr/htdocs/main.inc.php"))
    $res = @include("../../../dolibarr/htdocs/main.inc.php");     // Used on dev env only
if (!$res && file_exists("../../../../dolibarr/htdocs/main.inc.php"))
    $res = @include("../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (!$res && file_exists("../../../../../dolibarr/htdocs/main.inc.php"))
    $res = @include("../../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (!$res)
    die("Include of main fails");

/**
	@brief Call Specific Class provider for OScssShop Exts
		This class garanted process for dolibarr evolution
*/
dol_include_once('/framework/main.inc.php');
dol_include_once('/framework/core/lib/framework.lib.php');
dol_include_once('/framework/class/autotabs.class.php');

loadClass("Societe");
loadClass("User");
loadClass("Form", 'html.form');


use \Form;
use \Formother;
use \FormQualityReport;
use \UserGroup;
// use \User;
use \ExtraFields;
// use \Propal;

use \CORE\FRAMEWORK\User as User;
use \CORE\FRAMEWORK\Societe as Societe;

use \TabsPropal;


$form = new Form($db);

$root = GETPOST('tab', 'chaine');
$path = GETPOST('mod', 'chaine');
dol_include_once('/'.$path.'/core/tabs/'.$root.'.tabs.class.php');
// echo '/'.$path.'/core/tabs/'.$root.'.tabs.class.php';

$name = 'Tabs'.ucwords($root);


$TabsView = new $name($db);

$TabsView->CollectGetPost();

$TabsView->Process();

$TabsView->Display();


llxFooter();
$db->close();
