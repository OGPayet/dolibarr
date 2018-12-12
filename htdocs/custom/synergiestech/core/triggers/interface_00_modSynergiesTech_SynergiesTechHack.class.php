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
class InterfaceSynergiesTechHack extends DolibarrTriggers
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

        // Propagation of references
        if (preg_match('/_CREATE$/i', $action) && !empty($object->origin) && $object->origin_id > 0) {
            // Parse element/subelement (ex: project_task)
            $element = $subelement = $object->origin;
            if (preg_match('/^([^_]+)_([^_]+)/i', $object->origin, $regs)) {
                $element = $regs [1];
                $subelement = $regs [2];
            }

            // For compatibility
            if ($element == 'order' || $element == 'commande') {
                $element = $subelement = 'commande';
            }
            if ($element == 'propal') {
                $element = 'comm/propal';
                $subelement = 'propal';
            }
            if ($element == 'contract') {
                $element = $subelement = 'contrat';
            }

            dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

            $classname = ucfirst($subelement);
            $objectsrc = new $classname($this->db);
            $objectsrc->fetch($object->origin_id);

            $ref_client = !empty($objectsrc->ref_client) ? $objectsrc->ref_client : (!empty($objectsrc->ref_customer) ? $objectsrc->ref_customer : '');

            if (empty($object->ref_client)) $object->ref_client = $ref_client;
            if (empty($object->ref_customer)) $object->ref_customer = $ref_client;
            $object->update($user, 1);
        }

        return 0;
	}

}
