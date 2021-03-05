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
 * \file    buypricehistory/class/actions_buypricehistory.class.php
 * \ingroup buypricehistory
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */
dol_include_once('/buypricehistory/class/buypricehistory.class.php');
dol_include_once('/atlantis/class/extrafieldsToolbox.class.php');
/**
 * Class ActionsBuyPriceHistory
 */
class ActionsBuyPriceHistory
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
     * @var array Errors
     */
    public $errors = array();


    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;


    /**
     * Constructor
     *
     *  @param      DoliDB      $db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }
    /**
     * printCommonFooter
     *
     * @param   array()      $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string        &$action      Current action (if set). Generally create or edit or null
     * @param   HookManager  $hookmanager   Hook manager propagated to allow calling another hook
     * @return  int                          < 0 on error, 0 on success, 1 to replace standard code
     */
    public function printCommonFooter($parameters, &$object, &$action, $hookmanager)
    {
        //We create history when we have successfully imported some supplier price
        global $step;
        global $obj;
        global $datatoimport;
        if ($datatoimport == 'produit_supplierprices' && $obj->datatoimport == 'produit_supplierprices' && $step == 6 && ($obj->nbinsert > 0 || $obj->nbupdate > 0)) {
            $extrafieldsToolBox = new ExtrafieldsToolbox($this->db);
            $staticBuyPriceHistory = new BuyPriceHistory($this->db);
            $this->errors = array_merge($this->errors, $extrafieldsToolBox->cloneExtrafields('product_fournisseur_price', $staticBuyPriceHistory->table_element, true));
            if (empty($this->errors)) {
                $staticBuyPriceHistory->archiveAllPrice();
                $this->errors = array_merge($this->errors, $staticBuyPriceHistory->errors);
            }
            if (!empty($this->errors)) {
                setEventMessages('', $this->errors, 'errors');
            }
        }
        return 0;
    }
}
