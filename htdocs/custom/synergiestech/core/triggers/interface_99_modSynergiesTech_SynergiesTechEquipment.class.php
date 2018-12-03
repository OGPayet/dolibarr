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
class InterfaceSynergiesTechEquipment extends DolibarrTriggers
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
//	        case 'SET_COMPONENT_ADD':
//               $now = dol_now();
//
//               $fk_equipementevt_type = dol_getIdFromCode($this->db, 'COMPO', 'c_equipementevt_type', 'code', 'rowid');
//
//               // object is child equipment
//               $parameters = $object->context['parameters'];
//               //$parameters['parent']
//               //$parameters['old_child']
//               //$parameters['position']
//               $parentEquipement = $parameters['parent'];
//               $result = $object->addline(
//                   $object->id,
//                   $fk_equipementevt_type,
//                   $langs->trans('EquipmentAddEquipmentToComposition', $object->getNomUrl(1), $parentEquipement->getNomUrl(1)),
//                   $now,
//                   $now,
//                   '',
//                   '',
//                   '',
//                   '',
//                   '',
//                   '',
//                   '',
//                   ''
//               );
//
//               if ($result < 0) {
//                   return -1;
//               }
//
//               $this->db->query(
//                   'INSERT INTO '.MAIN_DB_PREFIX.'synergiestech_equipementevtassociation(fk_equipement_evt_pere, fk_equipement_evt_fils)'.
//                   ' VALUES('.$parentEquipement->rowid.', '.$object->rowid.')'
//               );
//
//               dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
//               return 0;

            case 'LINEEQUIPEMENTEVT_INSERT':
                $equipment = new Equipement($this->db);
                $equipment->fetch($object->fk_equipement);
                $ref_link = ' ['.$equipment->getNomUrl(1).']';

                if (isset($object->context['set_component_add'])) {
                    $event = clone $object;
                    $event->fk_equipement = $object->context['component_add_id'];
                    $event->desc .= $ref_link;
                    if ($event->insert(1) < 0) {
                        return -1;
                    }
                    $this->db->query(
                        'INSERT INTO '.MAIN_DB_PREFIX.'synergiestech_equipementevtassociation(fk_equipement_evt_pere, fk_equipement_evt_fils)'.
                        ' VALUES('.$object->rowid.', '.$event->rowid.')'
                    );
                } else {
                    // Get children of equipment
                    $children = $equipment->get_Childs();

                    foreach ($children as $child) {
                        $event = clone $object;
                        $event->fk_equipement = $child;
                        $event->desc .= $ref_link;
                        if ($event->insert() < 0) {
                            return -1;
                        }
                        $this->db->query(
                            'INSERT INTO '.MAIN_DB_PREFIX.'synergiestech_equipementevtassociation(fk_equipement_evt_pere, fk_equipement_evt_fils)'.
                            ' VALUES('.$object->rowid.', '.$event->rowid.')'
                        );
                    }
                }

                dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
                return 0;

            case 'LINEEQUIPEMENTEVT_UPDATE':
                $equipment = new Equipement($this->db);
                $equipment->fetch($object->fk_equipement);
                $ref_link = ' ['.$equipment->getNomUrl(1).']';

                // Get children of event
                $sql = 'SELECT fk_equipement_evt_fils AS rowid, ee.fk_equipement FROM '.MAIN_DB_PREFIX.'synergiestech_equipementevtassociation AS steea'.
                    ' LEFT JOIN '.MAIN_DB_PREFIX.'equipementevt AS ee ON ee.rowid = steea.fk_equipement_evt_fils'.
                    ' WHERE steea.fk_equipement_evt_pere = '.$object->rowid;

                $resql = $this->db->query($sql);
                if ($resql) {
                    while ($obj = $this->db->fetch_object($resql)) {
                        $event = clone $object;
                        $event->rowid = $obj->rowid;
                        $event->fk_equipement = $obj->fk_equipement;
                        if (strpos($event->desc, $ref_link) === false) $event->desc .= $ref_link;
                        if ($event->update() < 0) {
                            return -1;
                        }
                    }
                }

                dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
                return 0;

            case 'LINEEQUIPEMENTEVT_DELETE':
                // Get children of event
                $sql = 'SELECT fk_equipement_evt_fils AS rowid, ee.fk_equipement FROM '.MAIN_DB_PREFIX.'synergiestech_equipementevtassociation AS steea'.
                    ' LEFT JOIN '.MAIN_DB_PREFIX.'equipementevt AS ee ON ee.rowid = steea.fk_equipement_evt_fils'.
                    ' WHERE steea.fk_equipement_evt_pere = '.$object->rowid;

                $resql = $this->db->query($sql);
                if ($resql) {
                    while ($obj = $this->db->fetch_object($resql)) {
                        $this->db->query(
                            'DELETE FROM '.MAIN_DB_PREFIX.'synergiestech_equipementevtassociation'.
                            ' WHERE fk_equipement_evt_pere = '.$object->rowid.
                            ' AND fk_equipement_evt_fils = '.$obj->rowid
                        );

                        $event = clone $object;
                        $event->rowid = $obj->rowid;
                        $event->fk_equipement = $obj->fk_equipement;
                        if ($event->deleteline() < 0) {
                            return -1;
                        }
                    }
                }

                dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
                return 0;
        }

        return 0;
	}

}
