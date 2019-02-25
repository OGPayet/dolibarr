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
 *       \file       htdocs/core/ajax/countinterventions.php
 *       \brief      File to load count intervention
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

$contract_id    = GETPOST('contratid','int');
$ei_type        = GETPOST('ei_type','int');

/*
 * View
 */

top_httphead();

if ($contract_id > 0) {
    // Show count of interventions
    require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
    $contract = new Contrat($db);
    $contract->fetch($contract_id);
    $contract_list = array($contract->id => $contract);

    dol_include_once('/extendedintervention/class/extendedinterventionquota.class.php');
    $extendedinterventionquota = new ExtendedInterventionQuota($db);

    print $extendedinterventionquota->showBlockCountInterventionOfContract($contract_list, $ei_type);

    // Wrapper to show tooltips (html or onclick popup)
    if (! empty($conf->use_javascript_ajax) && empty($conf->dol_no_mouse_hover))
    {
        print "\n<!-- JS CODE TO ENABLE tipTip on all object with class classfortooltip -->\n";
        print '<script type="text/javascript">
            jQuery(document).ready(function () {
              jQuery(".classfortooltip").tipTip({maxWidth: "'.dol_size(($conf->browser->layout == 'phone' ? 400 : 700),'width').'px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});
              jQuery(".classfortooltiponclicktext").dialog({ width: 500, autoOpen: false });
              jQuery(".classfortooltiponclick").click(function () {
                console.log("We click on tooltip for element with dolid="+$(this).attr(\'dolid\'));
                if ($(this).attr(\'dolid\'))
                {
                  obj=$("#idfortooltiponclick_"+$(this).attr(\'dolid\'));
                  obj.dialog("open");
                }
              });
            });
          </script>' . "\n";
    }
}
