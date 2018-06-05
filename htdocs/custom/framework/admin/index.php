<?php
/* Copyright (C) 2014		 Oscim       <support@oscim.fr>
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


/**
 * 	\file		admin/about.php
* 	\ingroup	cheque
 * 	\brief		This file is about page
 */
// Dolibarr environment
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


// Libraries
// core
	dol_include_once('/core/lib/admin.lib.php');

// framework Required
dol_include_once('/framework/class/PageConstruct.class.php');
dol_include_once('/framework/core/lib/PHP_Markdown_1.0.1o/markdown.php');
dol_include_once('/framework/core/lib/framework.lib.php');




// Access control
if (! $user->admin ) {
	accessforbidden();
}



$Page = new PageConfigSubModule($db, 'framework');
$Page->ReceiveContext(  GETPOST('page') );

/*
 * View
 */

$Page->DisplayPage();
