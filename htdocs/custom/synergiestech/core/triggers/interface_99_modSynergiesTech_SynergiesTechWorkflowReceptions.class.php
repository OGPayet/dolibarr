<?php
/* Copyright (C) 2005-2014 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014 Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2014      Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2015      Bahfir Abbes         <bafbes@gmail.com>
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
 *  \file       htdocs/synergiestech/core/triggers/interface_99_all.class.php
 *  \ingroup    core
 *  \brief      Fichier de demo de personalisation des actions du workflow
 *  \remarks    Son propre fichier d'actions peut etre cree par recopie de celui-ci:
 *              - Le nom du fichier doit etre: interface_99_modMymodule_Mytrigger.class.php
 *				                           ou: interface_99_all_Mytrigger.class.php
 *              - Le fichier doit rester stocke dans core/triggers
 *              - Le nom de la classe doit etre InterfaceMytrigger
 *              - Le nom de la propriete name doit etre Mytrigger
 */
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for synergiestech module
 */
class InterfaceSynergiesTechWorkflowReceptions extends DolibarrTriggers
{

	public $family = 'synergiestech';
	public $picto = 'technic';
	public $description = "Triggers of the module Synergies-Tech.";
	public $version = self::VERSION_DOLIBARR;

	/**
     * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
     *
     * @param string		$action		Event action code
     * @param Object		$object     Object concerned. Some context information may also be provided into array property object->context.
     * @param User		    $user       Object user
     * @param Translate 	$langs      Object langs
     * @param conf		    $conf       Object conf
     * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if (empty($conf->synergiestech->enabled)) return 0;     // Module not active, we do nothing

	    switch ($action) {
            // Supplier orders
/*            case 'ORDER_SUPPLIER_RECEIVE':
                if ($object->statut != 4 && $object->statut != 5) // N'est pas une livraison partielle ou totale
                    return 0;

                header("Location: ".DOL_MAIN_URL_ROOT."/fourn/commande/dispatch.php?id=".$object->id);

                dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
                return 0;
*/
            case 'SHIPPING_CREATE':
                if (!empty($conf->global->SYNERGIESTECH_ENABLED_WORKFLOW_SHIPPING_CREATED_TO_ATTACH_EQUIPMENTS)) {
                    $sql = "SELECT ed.rowid FROM ".MAIN_DB_PREFIX."expeditiondet AS ed".
                        " LEFT JOIN ".MAIN_DB_PREFIX."commandedet AS cd ON cd.rowid = ed.fk_origin_line".
                        " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields AS pe ON pe.fk_object = cd.fk_product".
                        " WHERE ed.fk_expedition = ".$object->id.
                        " AND ed.qty > 0".
                        " AND pe.synergiestech_to_serialize = 1";

                    $resql = $this->db->query($sql);
                    if ($resql) {
                        if ($this->db->num_rows($resql) > 0) {
                            header("Location: ".dol_buildpath('/equipement/tabs/expeditionAdd.php', 2)."?id=".$object->id);
                            $object->context['workflow_to_serialize'] = true;
                        }
                    }
                }

                dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
                return 0;

            case 'ORDER_SUPPLIER_DISPATCH':
                if (!empty($conf->global->SYNERGIESTECH_ENABLED_WORKFLOW_ORDER_SUPPLIER_DISPATCH_TO_SET_EQUIPMENTS)) {
                    $sql = "SELECT cfd.rowid FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch AS cfd".
                        " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields AS pe ON pe.fk_object = cfd.fk_product".
                        " WHERE cfd.fk_commande = ".$object->id.
                        " AND cfd.qty > 0".
                        " AND pe.synergiestech_to_serialize = 1";

                    $resql = $this->db->query($sql);
                    if ($resql) {
                        if ($this->db->num_rows($resql) > 0) {
                            header("Location: ".dol_buildpath('/equipement/tabs/supplier_order.php', 2)."?id=".$object->id);
                            $object->context['workflow_to_serialize'] = true;
                        }
                    }
                }

                dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
                return 0;
        }

        return 0;
	}

}
