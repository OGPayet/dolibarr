<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (c) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2017 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2005      Lionel Cousteix      <etm_ltd@tiscali.co.uk>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
 * Copyright (C) 2013-2014 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2013-2015 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015      Alexis LAURIER       contact@alexislaurier.fr
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
 *  \file       htdocs/custom/synergiestech/class/extendedGroup.class.php
 *	\brief      File of class to manage group
 *  \ingroup	synergiestech
 */

require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
dol_include_once('/commande/class/commande.class.php');

/**
 *	Class to manage Dolibarr users and groups with custom links between user and group - we only edit values for setInLdap column to true
 */
class ExtendedInterventionValidation extends FichInter
{
    /**
     * @var FicheInter Object to to test on
     */
    public $fichinter;

    /**
     * @var DoliDb		Database handler (result of a new DoliDB)
     */
    public $db;

    /**
     * Constructor
     *
     * @param FichInter $db Database handler
     * @param DoliDb $db Database handler
     */
    public function __construct(FichInter &$object, DoliDB &$db = null)
    {
        $this->fichinter = $object;
        $this->db = $db;
    }

    /**
     * Check if this fichinter can be validate by this user
     * @param User $user user to do test on
     * @return Boolean return if user can validate this fichinter
     */

    public function canUserValidateThisFichInter(User $user)
    {
        return $this->canUserValidateThisFichInterAccordingToLinkedContract($user) || $this->canUserValidateThisFichInterAccordingToLinkedOrder($user);
    }

    /**
     * Check if this fichinter can be validate thanks to linked contract
     * @param User $user user to do test on
     * @return Boolean return if user can validate this fichinter according to contract linked to fichinter
     */

    public function canUserValidateThisFichInterAccordingToLinkedContract(User $user)
    {
        $contractId = $this->fichinter->fk_contrat;
        return $this->canUserValidateThisFichInterAccordingToThisContractId($user, $contractId);
    }

    /**
     * Check if User can validate this fichinter according to this contract Id
     * @param User $user to do test on
     * @param int $contractId
     * @return Boolean return if user can validate this fichinter according to this contract Id
     */

    public function canUserValidateThisFichInterAccordingToThisContractId(User $user, $contractId)
    {
        if (empty($contractId) || $contractId < 0) {
            $result = false;
        } else if ($user->rights->synergiestech->intervention->validateWithStaleContract) {
            $result = true;
        } else {
            dol_include_once('/contrat/class/contrat.class.php');
            $contract = new Contrat($this->db);
            $contract->fetch($contractId);
            dol_include_once('synergiestech/class/html.formsynergiestech.class.php');
            $result = FormSynergiesTech::isContractActive($contract);
        }
        return $result;
    }

    /**
     * Check if User can validate this fichinter according to linked order
     * @param array $doNotConsiderTheseOrderId allow to remove orders that could make this fichinter to be validated
     * @return Boolean return if user can validate this fichinter according to linked orders
     */

    public function canUserValidateThisFichInterAccordingToLinkedOrder($doNotConsiderTheseOrderId = array())
    {
        if (empty($this->fichinter->linkedObjectsIds)) {
            $this->fichinter->fetchObjectLinked();
        }
        $listOfOrder = $this->fichinter->linkedObjects["commande"];
        $result = false;
        foreach ($listOfOrder as $order) {
            if (!in_array($order->id, $doNotConsiderTheseOrderId) && $this->canUserValidateThisFichInterAccordingToThisOrder($order)) {
                $result = true;
                break;
            }
        }
        return $result;
    }

    /**
     * Check if User can validate this fichinter according to this Order
     * @param Commande $user to do test on
     * @return Boolean return if user can validate this fichinter according to linked orders
     */

    public function canUserValidateThisFichInterAccordingToThisOrder(Commande $order)
    {
        global $conf;
        $listOfProductId = explode(",", $conf->global->SYNERGIESTECH_FICHINTER_INTERVENTIONPRODUCT);
        $orderLines = $order->lines;
        $result = false;
        if($order->statut > 0){
            foreach ($orderLines as $line) {
                if (in_array($line->fk_product, $listOfProductId)) {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Check if this contract can be linked on this fichinter, in order to check if remove of old contract can be confirmed
     * @param int $contractId
     * @return Boolean return if this user can remove this contract from beeing linked on this fichInter
     */

     public function canThisNewContractBeLinkedToThisFichinter(User $user, $contractId){
         return $this->canUserValidateThisFichInterAccordingToThisContractId($user,$contractId) || $this->canUserValidateThisFichInterAccordingToLinkedOrder();
     }

     /**
     * Check if this order can be unlinked on this fichinter
     * @param int $orderId
     * @return Boolean return if this user can remove this order from beeing linked on this fichInter
     */

    public function canThisOrderBeUnlinkedToThisFichinter(User $user, $orderId){
        return $this->canUserValidateThisFichInterAccordingToLinkedContract($user) || $this->canUserValidateThisFichInterAccordingToLinkedOrder(array($orderId));
    }
}
