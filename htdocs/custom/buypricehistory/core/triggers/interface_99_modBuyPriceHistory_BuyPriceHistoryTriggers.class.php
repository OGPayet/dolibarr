<?php
/* Copyright (C) 2021 Infra <infra@synergies-france.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modBuyPriceHistory_BuyPriceHistoryTriggers.class.php
 * \ingroup buypricehistory
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modBuyPriceHistory_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
dol_include_once("/buypricehistory/class/buypricehistory.class.php");
dol_include_once('/atlantis/class/extrafieldsToolbox.class.php');

/**
 *  Class of triggers for BuyPriceHistory module
 */
class InterfaceBuyPriceHistoryTriggers extends DolibarrTriggers
{
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->description = "BuyPriceHistory triggers.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'development';
        $this->picto = 'buypricehistory@buypricehistory';
    }

    /**
     * Trigger name
     *
     * @return string Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * @return string Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }


    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "runTrigger" are triggered if file
     * is inside directory core/triggers
     *
     * @param string        $action     Event action code
     * @param CommonObject  $object     Object
     * @param User          $user       Object user
     * @param Translate     $langs      Object langs
     * @param Conf          $conf       Object conf
     * @return int                      <0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if (empty($conf->buypricehistory->enabled)) {
            return 0; // If module is not enabled, we do nothing
        }
        // Or you can execute some code here
        switch ($action) {
            case "SUPPLIER_PRODUCT_BUYPRICE_UPDATE":
            case "SUPPLIER_PRODUCT_BUYPRICE_CREATE":
                $staticBuyPriceHistory = new BuyPriceHistory($this->db);
                $extrafieldsToolBox = new ExtrafieldsToolbox($this->db);
                $this->errors = array_merge($this->errors, $extrafieldsToolBox->cloneExtrafields('product_fournisseur_price', $staticBuyPriceHistory->table_element, true));
                if (empty($this->errors)) {
                    $staticBuyPriceHistory->archiveAllPrice();
                    $this->errors = $staticBuyPriceHistory->errors;
                }
                return $staticBuyPriceHistory ? 1 : -1;
                break;
            default:
                dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
                break;
        }
        return 0;
    }
}
