<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2013-2016    Jean-François FERRY    <jfefe@aternatik.fr>
 *                  2016        Christophe Battarel <christophe@altairis.fr>
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
 *        \file       htdocs/hosting/lib/hosting.lib.php
 *        \brief      Ensemble de fonctions de base pour le module hosting
 *      \ingroup    business
 *      \version    $Id$
 */

function retourproduits_prepare_head($object)
{

    global $db, $langs, $conf, $user;

    $langs->load("retourproduits");
    $object->fetch($object->id);
    $h = 0;
    $head = array();
    $head[$h][0] = dol_buildpath('/retourproduits/card.php', 1) . '?action=view&id=' . $object->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'retourproduits';
    $h++;


    if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
    {
        $objectsrc = $object;
        if ($object->origin == 'commande' && $object->origin_id > 0)
        {
            $objectsrc = new Commande($db);
            $objectsrc->fetch($object->origin_id);
        }
        $nbContact = count($objectsrc->liste_contact(-1,'internal')) + count($objectsrc->liste_contact(-1,'external'));
        $head[$h][0] = dol_buildpath('/retourproduits/contact.php?id=', 1).$object->id;
        $head[$h][1] = $langs->trans("ContactsAddresses");
        if ($nbContact > 0) $head[$h][1].= ' <span class="badge">'.$nbContact.'</span>';
        $head[$h][2] = 'contact';
        $h++;
    }

    if (empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
        $head[$h][0] = dol_buildpath('/retourproduits/note.php', 1).'?id='.$object->id;
        $head[$h][1] = $langs->trans('Notes');
        $nbNotes = ($object->note_private?1:0);
        $nbNotes+= ($object->note_public?1:0);
        if ($nbNotes > 0) $head[$h][1].= ' <span class="badge">'.$nbNotes.'</span>';
        $head[$h][2] = 'note';
        $h++;
    }

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'retourproduits');



    return $head;
}

function retourproduits_admin_prepare_head($object=null)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/retourproduits/admin/retourproduits_conf.php", 1);
	$head[$h][1] = $langs->trans('Parameters');
	$head[$h][2] = 'general';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'retourproduits_admin');

	$head[$h][0] = dol_buildpath('/retourproduits/admin/retourproduits_extrafields.php', 1);
	$head[$h][1] = $langs->trans("ExtraFieldsRetProd");
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = dol_buildpath('/retourproduits/admin/retourproduitsdet_extrafields.php', 1);
	$head[$h][1] = $langs->trans("ExtraFieldsRetProdDet");
	$head[$h][2] = 'attributesdet';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'retourproduits_admin', 'remove');

	return $head;
}

/**
 *	Get all products sent for a order ID with equipments availables
 *
 * @param	DoliDB		$db             Database handler
 * @param	int		    $order_id       Id of the order
 *
 * @return array                        List of products sent for a order ID with equipments availables
 */
function retourproduits_get_product_list($db, $order_id)
{
    $lines = array();

    // Get nb sent for each product
    $sql = "SELECT cd.rowid, p.rowid as product_id, p.ref, p.label, SUM(exp.qty) as qty_sent";
    $sql .= " FROM " . MAIN_DB_PREFIX . "commande as c, " . MAIN_DB_PREFIX . "product as p, ";
    $sql .= MAIN_DB_PREFIX . "commandedet as cd, " . MAIN_DB_PREFIX . "expeditiondet as exp";
    $sql .= " WHERE c.rowid = cd.fk_commande";
    $sql .= " AND p.rowid = cd.fk_product";
    $sql .= " AND cd.rowid = exp.fk_origin_line";
    $sql .= " AND c.rowid = " . $order_id;
    $sql .= " GROUP BY cd.rowid";
    $sql .= " ORDER BY cd.rang, cd.rowid";
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $lines[$obj->rowid] = array(
                'produit_id' => $obj->product_id,
                'product' => $obj->ref . ' - ' . $obj->label,
                'qty_sent' => $obj->qty_sent,
                'equipments' => array(),
            );
        }
    }

    // Get list of available serial numbers for each product
    if (!empty($lines)) {
        $sql = "SELECT DISTINCT cd.rowid, e.rowid as equipment_id, e.ref, e.fk_product";
        $sql .= " FROM " . MAIN_DB_PREFIX . "commande as c";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commandedet as cd ON cd.fk_commande = c.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "expeditiondet as ed ON ed.fk_origin_line = cd.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "equipementevt AS ee ON ee.fk_expedition = ed.fk_expedition";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "equipement AS e ON e.rowid = ee.fk_equipement";
        $sql .= " WHERE c.rowid = " . $order_id;
        $sql .= " AND e.fk_soc_client = c.fk_soc";
        $sql .= " AND e.fk_entrepot IS NULL";
        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                if (isset($lines[$obj->rowid])) {
                    $lines[$obj->rowid]['equipments'][$obj->equipment_id] = $obj->ref;
                }
            }
        }
    }

    return $lines;
}
