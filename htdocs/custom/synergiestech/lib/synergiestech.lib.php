<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
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
 *	\file       htdocs/synergiestech/lib/synergiestech.lib.php
 *	\brief      Ensemble de fonctions pour le module synergiestech
 * 	\ingroup	synergiestech
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function synergiestech_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/synergiestech/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/synergiestech/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath("/synergiestech/admin/changelog.php", 1);
    $head[$h][1] = $langs->trans("OpenDsiChangeLog");
    $head[$h][2] = 'changelog';
    $h++;

    complete_head_from_modules($conf,$langs,null,$head,$h,'synergiestech_admin');

    return $head;
}

/**
 * Check if has shipping equipment to serialize
 *
 * @param   DoliDB  $db             Database handler
 * @param   int     $shipping_id    Id of the shipping
 * @return  bool
 */
function synergiestech_has_shipping_equipment_to_serialize($db, $shipping_id)
{
    $sql = "SELECT IF(IFNULL(ts.nb, 0) != IFNULL(s.nb, 0), 1, 0) AS result
            FROM (
              SELECT SUM(ed.qty) as nb, cd.fk_product FROM " . MAIN_DB_PREFIX . "expeditiondet AS ed
              LEFT JOIN " . MAIN_DB_PREFIX . "commandedet AS cd ON cd.rowid = ed.fk_origin_line
              LEFT JOIN " . MAIN_DB_PREFIX . "product_extrafields AS pe ON pe.fk_object = cd.fk_product
              WHERE ed.fk_expedition = " . $shipping_id ."
              AND ed.qty > 0
              AND pe.synergiestech_to_serialize = 1
              GROUP BY cd.fk_product
            ) AS ts
            LEFT JOIN (
              SELECT count(ee.rowid) as nb, e.fk_product FROM " . MAIN_DB_PREFIX . "equipementevt AS ee
              LEFT JOIN " . MAIN_DB_PREFIX . "equipement AS e ON e.rowid = ee.fk_equipement
              WHERE ee.fk_expedition = " . $shipping_id . "
              GROUP BY e.fk_product
           ) AS s ON ts.fk_product = s.fk_product";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            if($obj->result == 1)
                return true;
        }
    }

    return false;
}

/**
 * Check if has shipping equipment to serialize
 *
 * @param   DoliDB  $db             Database handler
 * @param   int     $shipping_id    Id of the shipping
 * @return  bool
 */
function synergiestech_has_shipping_equipment_to_validate($db, $shipping_id)
{
    $sql = "SELECT IF(count(ee.rowid) > 0, 1, 0) as result FROM " . MAIN_DB_PREFIX . "equipementevt AS ee
            LEFT JOIN " . MAIN_DB_PREFIX . "equipement AS e ON e.rowid = ee.fk_equipement
            WHERE ee.fk_expedition = " . $shipping_id . "
            AND e.fk_entrepot IS NOT NULL";

    $resql = $db->query($sql);
    if ($resql) {
        if ($obj = $db->fetch_object($resql)) {
            return $obj->result == 1;
        }
    }

    return false;
}

/**
 * Check if has supplier order equipment to serialize
 *
 * @param   DoliDB  $db                     Database handler
 * @param   int     $supplier_order_id      Id of the supplier order
 * @return  bool
 */
function synergiestech_has_dispatching_equipment_to_serialize($db, $supplier_order_id) {
    $sql = "SELECT IF(IFNULL(ts.nb, 0) != IFNULL(s.nb, 0), 1, 0) AS result
            FROM (
              SELECT SUM(cfd.qty) as nb, cfd.fk_product FROM " . MAIN_DB_PREFIX . "commande_fournisseurdet AS cfd
              LEFT JOIN " . MAIN_DB_PREFIX . "product_extrafields AS pe ON pe.fk_object = cfd.fk_product
              WHERE cfd.fk_commande = " . $supplier_order_id ."
              AND cfd.qty > 0
              AND pe.synergiestech_to_serialize = 1
              GROUP BY cfd.fk_product
            ) AS ts
            LEFT JOIN (
              SELECT count(e.quantity) as nb, e.fk_product FROM " . MAIN_DB_PREFIX . "equipement AS e
              WHERE e.fk_commande_fourn = " . $supplier_order_id . "
              GROUP BY e.fk_product
           ) AS s ON ts.fk_product = s.fk_product";

    $resql = $db->query($sql);
    if ($resql) {
        if ($obj = $db->fetch_object($resql)) {
            return $obj->result == 1;
        }
    }

    return false;
}
