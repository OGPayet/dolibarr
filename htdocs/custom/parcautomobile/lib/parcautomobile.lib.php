<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
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
 *	\file		lib/parcautomobile.lib.php
 *	\ingroup	parcautomobile
 *	\brief		This file is an example module library
 *				Put some comments here
 */


function service_inclus($type="")
{
    global $langs, $conf, $db;
    $langs->load("parcautomobile@parcautomobile");

    $menu='<div class="tabs" data-role="controlgroup" data-type="horizontal">';
		$menu .='<div class="inline-block tabsElem tabsElemActive">';
			// <!-- id tab = services_inclus -->
			$menu .='<div id="services_inclus" class="tabactive tab inline-block" >Services inclus</div>';
		$menu .='</div>';

        if($type == "contrats"){
    		$menu .='<div class="inline-block tabsElem">';
    			// <!-- id tab = cout_recurrent -->
    				$menu .='<div id="cout_recurrent" class="tabunactive tab inline-block" >Coûts récurrents générés</div>';
    		$menu .='</div>';
        }
	$menu .='</div>';

	return $menu;
}

function parcautomobileAdminPrepareHead()
{
    global $langs, $conf, $db;
    $langs->load("parcautomobiles@parcautomobiles");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/parcautomobile/admin/parcautomobile_setup.php", 2);
    $head[$h][1] = $langs->trans("Configuration");
    $head[$h][2] = 'setting';
    $h++;

    $head[$h][0] = dol_buildpath("/parcautomobile/admin/admin_vehicule.php", 2);
    $head[$h][1] = $langs->trans("Champs_vehicule");
    $head[$h][2] = 'champsvehicule';
    $h++;

    $head[$h][0] = dol_buildpath("/parcautomobile/admin/admin_kilometrage.php", 2);
    $head[$h][1] = $langs->trans("Champs_kilomtr");
    $head[$h][2] = 'champskilometr';
    $h++;

    $head[$h][0] = dol_buildpath("/parcautomobile/admin/admin_ravitaillement.php", 2);
    $head[$h][1] = $langs->trans("Champs_suivi_essenc");
    $head[$h][2] = 'champsessanc';
    $h++;

    $head[$h][0] = dol_buildpath("/parcautomobile/admin/admin_intervention.php", 2);
    $head[$h][1] = $langs->trans("Champs_intervention");
    $head[$h][2] = 'champsintervention';
    $h++;

    $head[$h][0] = dol_buildpath("/parcautomobile/admin/admin_contrat.php", 2);
    $head[$h][1] = $langs->trans("Champs_contrat");
    $head[$h][2] = 'champscontrat';
    $h++;

    $head[$h][0] = dol_buildpath("/parcautomobile/admin/admin_costs.php", 2);
    $head[$h][1] = $langs->trans("Champs_costs");
    $head[$h][2] = 'champscosts';
    $h++;

    return $head;
}


