<?php

/* Copyright (C) 2014		 Support       <support@oscss-shop.fr>
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
 * or see http://www.gnu.org/
 */

Class subConfig {

    public function __construct(PageConfigSubModule $Master) {

        $this->Master = $Master;
    }

    public function PrepareContext() {
        global $langs, $conf, $html, $mysoc;

        $html = new Form($this->Master->db);

// var_dump( GETPOST("action")) ;

        if (GETPOST("action") == 'setvalue') {

						dolibarr_set_const($this->Master->db, "PRODUCT_PRICE_SUPPLIER_NO_LOG", GETPOST('product_price_supplier_no_log'), 'chaine', 0, '', $conf->entity);

						dolibarr_set_const($this->Master->db, "ODSFOURN_TRIGGER_INVOICESUPPLIER", GETPOST('odsfourn_trigger_invoicesupplier'), 'chaine', 0, '', $conf->entity);

						dolibarr_set_const($this->Master->db, "ODSFOURN_TRIGGER_ORDERSUPPLIER", GETPOST('odsfourn_trigger_ordersupplier'), 'chaine', 0, '', $conf->entity);



        }
        elseif ($_POST["action"] == 'setlistpath') {
					dol_include_once('/masterlink/class/masterlink.class.php');

					$ml = new masterlink($this->Master->db);

					$original = GETPOST("original");
					$custom = GETPOST("custom");

// print_r($original);
// exit;


					foreach($original as $k=>$v) {

						$ml->fetch($k);

						$ml->original = $v;
						$ml->custom = $custom[$k];

						$ml->update();
					}

        }
    }

    /**
      @brief constructor
     */
    public function DisplayPage() {
        global $langs, $conf, $html;

        // Translations
        $langs->load("admin");
        $langs->load($this->Master->filelang);


        dol_include_once('/' . $this->Master->module . '/admin/tpl/' . $this->Master->currentpage . '.tpl');
    }

}

?>
