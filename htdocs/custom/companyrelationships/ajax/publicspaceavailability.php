<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 *       \file       htdocs/companyrelationships/ajax/publicspaceavailability.php
 *       \brief      File to load Public Space Availability for the relationships
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");

$socid			= GETPOST('socid', 'int');
$relation_type  = GETPOST('relation_type', 'int');
$relation_socid = GETPOST('relation_socid', 'int');
$element        = GETPOST('element', 'alpha');

/*
 * View
 */

top_httphead();


$return = array(
    'error'      => 0,
    'principal'  => 0,
    'benefactor' => 0,
    'watcher'    => 0
);
if ($socid>0 && $relation_type>0 && $relation_socid>0 && !empty($element))
{
    dol_include_once('/custom/companyrelationships/class/companyrelationships.class.php');
    $companyRelationships = new CompanyRelationships($db);
    $publicSpaceAvailability = $companyRelationships->getPublicSpaceAvailabilityThirdparty($socid, $relation_type, $relation_socid, $element);
    if (is_array($publicSpaceAvailability)) {
        $return['principal']  = $publicSpaceAvailability['principal'];
        $return['benefactor'] = $publicSpaceAvailability['benefactor'];
        $return['watcher']    = $publicSpaceAvailability['watcher'];
    } else {
        $return['error'] = 1;
    }
}
echo json_encode($return);

$db->close();
