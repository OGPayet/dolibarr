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
class InterfaceSynergiesTech extends DolibarrTriggers
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
	    switch ($action) {
            case 'RETURN_CREATE':
                if (isset($object->context['synergiestech_create_returnproducts']) && $object->context['synergiestech_create_returnproducts'] > 0) {
                    $id = $object->context['synergiestech_create_returnproducts'];

                    // todo link to create
                }

                dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
                return 0;
            case 'LINEORDER_INSERT':
                if (isset($object->context['synergiestech_addline_not_into_formula'])) {
                    $langs->load('synergiestech@synergiestech');
                    $now = dol_now();

                    // Get order/thirdparty
                    require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
                    $order = new Commande($this->db);
                    $order->fetch($object->fk_commande);
                    $order->fetch_thirdparty();

                    // Get product
                    require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
                    $product = new Product($this->db);
                    $product->fetch($object->fk_product);

                    // Insertion action
                    require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
                    $actioncomm = new ActionComm($this->db);

                    $actioncomm->type_code = 'AC_OTH_AUTO';
                    $actioncomm->code = 'AC_OTH_AUTO';
                    $actioncomm->label = $langs->trans('SynergiesTechProductOffFormulaEventTitle');
                    $actioncomm->note = $langs->trans('SynergiesTechProductOffFormulaEventMessage',
                        $user->getNomUrl(1), $product->getNomUrl(1), $object->context['synergiestech_addline_not_into_formula'], $order->getNomUrl(1));
                    $actioncomm->datep = $now;
                    $actioncomm->datef = $now;
                    $actioncomm->percentage = -1;   // Not applicable
                    $actioncomm->socid = $order->socid;
                    $actioncomm->authorid = $user->id;   // User saving action
                    $actioncomm->userownerid = $user->id;    // Owner of action

                    $actioncomm->fk_element = $order->id;
                    $actioncomm->elementtype = $order->element;

                    $ret = $actioncomm->create($user);       // User creating action
                    if ($ret > 0) {
                        return 1;
                    } else {
                        $error = "Failed to insert event : " . $actioncomm->errorsToString();
                        $this->error = $error;
                        $this->errors = $actioncomm->errors;

                        dol_syslog("interface_99_modSynergiesTech_SynergiesTech.class.php: " . $error, LOG_ERR);
                        return -1;
                    }


                }

                dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
                return 0;
        }

        return 0;
	}

}
