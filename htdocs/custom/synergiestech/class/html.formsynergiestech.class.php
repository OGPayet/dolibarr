<?php
/* Copyright (c) 2002-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2017  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2006       Marc Barilley/Ocebo     <marc@ocebo.com>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2007       Patrick Raguin          <patrick.raguin@gmail.com>
 * Copyright (C) 2010       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2010-2014  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2011       Herve Prot              <herve.prot@symeos.com>
 * Copyright (C) 2012-2016  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2012       Cedric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014       Alexandre Spangaro      <aspangaro.dolibarr@gmail.com>
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
 *	\file       synergiestech/core/class/html.formsynergiestech.class.php
 *  \ingroup    core
 *	\brief      File of class with all html predefined components - formothers modified
 */


/**
 *	Class to manage generation of HTML components
 *	Only common components must be here.
 *
 *  TODO Merge all function load_cache_* and loadCache* (except load_cache_vatrates) into one generic function loadCacheTable
 */
class FormSynergiesTech
{
    var $db;
    var $error;
    var $num;

    /**
     * Constructor
     *
     * @param   DoliDB    $db     Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Return list of products for customer in Ajax if Ajax activated or go to select_produits_list
     *
     * @param   int     $selected                 Preselected products
     * @param   string  $htmlname                 Name of HTML select field (must be unique in page)
     * @param   int     $filtertype               Filter on product type (''=nofilter, 0=product, 1=service)
     * @param   array   $include_into_categories  If not null only products include into categories
     * @param   int     $free_into_categories     1=The products include into categories is free
     * @param   int     $limit                    Limit on number of returned lines
     * @param   int     $price_level              Level of price to show
     * @param   int     $status                   -1=Return all products, 0=Products not on sell, 1=Products on sell
     * @param   int     $finished                 2=all, 1=finished, 0=raw material
     * @param   string  $selected_input_value     Value of preselected input text (for use with ajax)
     * @param   int     $hidelabel                Hide label (0=no, 1=yes, 2=show search icon (before) and placeholder, 3 search icon after)
     * @param   array   $ajaxoptions              Options for synergiestech_ajax_autocompleter
     * @param   int     $socid                    Thirdparty Id (to get also price dedicated to this customer)
     * @param   string  $showempty                '' to not show empty line. Translation key to show an empty line. '1' show empty line with no text.
     * @param   int     $forcecombo               Force to use combo box
     * @param   string  $morecss                  Add more css on select
     * @param   int     $hidepriceinlabel         1=Hide prices in label
     * @param   string  $warehouseStatus          warehouse status filter, following comma separated filter options can be used
     *                                              'warehouseopen' = select products from open warehouses,
     *                                              'warehouseclosed' = select products from closed warehouses,
     *                                              'warehouseinternal' = select products from warehouses for internal correct/transfer only
     * @param   array   $selected_combinations    Selected combinations. Format: array([attrid] => attrval, [...])
     * @return  void
     */
    function select_produits($selected = '', $htmlname = 'productid', $filtertype = '', $include_into_categories = array(), $free_into_categories = 0, $limit = 20, $price_level = 0, $status = 1, $finished = 2, $selected_input_value = '', $hidelabel = 0, $ajaxoptions = array(), $socid = 0, $showempty = '1', $forcecombo = 0, $morecss = '', $hidepriceinlabel = 0, $warehouseStatus = '', $selected_combinations = array())
    {
        global $langs, $conf;

        if (!is_array($include_into_categories) || count($include_into_categories) == 0) $free_into_categories = 0;

        $price_level = (!empty($price_level) ? $price_level : 0);

        if (!empty($conf->use_javascript_ajax) && !empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT)) {
            $placeholder = '';

            if ($selected && empty($selected_input_value)) {
                require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
                $producttmpselect = new Product($this->db);
                $producttmpselect->fetch($selected);
                $selected_input_value = $producttmpselect->ref;
                unset($producttmpselect);
            }
            // mode=1 means customers products
            $urloptioncat = http_build_query(array('include_categories'=>$include_into_categories));
            $urloption = 'htmlname=' . $htmlname . '&outjson=1&price_level=' . $price_level . '&type=' . $filtertype . '&mode=1&status=' . $status . '&finished=' . $finished . '&warehousestatus=' . $warehouseStatus."&".$urloptioncat;
            //Price by customer
            if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES) && !empty($socid)) {
                $urloption .= '&socid=' . $socid;
            }
            dol_include_once('/synergiestech/lib/synergiestech.lib.php');
            print synergiestech_ajax_autocompleter($selected, $htmlname, dol_buildpath('/synergiestech/ajax/products.php', 1), $urloption, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT, 0, $ajaxoptions);

            if (!empty($conf->variants->enabled)) {
                ?>
              <script>

                selected = <?php echo json_encode($selected_combinations) ?>;
                combvalues = {};

                jQuery(document).ready(function () {

                  jQuery("input[name='prod_entry_mode']").change(function () {
                    if (jQuery(this).val() == 'free') {
                      jQuery('div#attributes_box').empty();
                    }
                  });

                  jQuery("input#<?php echo $htmlname ?>").change(function () {

                    if (!jQuery(this).val()) {
                      jQuery('div#attributes_box').empty();
                      return;
                    }

                    jQuery.getJSON("<?php echo dol_buildpath('/variants/ajax/getCombinations.php', 2) ?>", {
                      id: jQuery(this).val()
                    }, function (data) {
                      jQuery('div#attributes_box').empty();

                      jQuery.each(data, function (key, val) {

                        combvalues[val.id] = val.values;

                        var span = jQuery(document.createElement('div')).css({
                          'display': 'table-row'
                        });

                        span.append(
                          jQuery(document.createElement('div')).text(val.label).css({
                            'font-weight': 'bold',
                            'display': 'table-cell',
                            'text-align': 'right'
                          })
                        );

                        var html = jQuery(document.createElement('select')).attr('name', 'combinations[' + val.id + ']').css({
                          'margin-left': '15px',
                          'white-space': 'pre'
                        }).append(
                          jQuery(document.createElement('option')).val('')
                        );

                        jQuery.each(combvalues[val.id], function (key, val) {
                          var tag = jQuery(document.createElement('option')).val(val.id).html(val.value);

                          if (selected[val.fk_product_attribute] == val.id) {
                            tag.attr('selected', 'selected');
                          }

                          html.append(tag);
                        });

                        span.append(html);
                        jQuery('div#attributes_box').append(span);
                      });
                    })
                  });

                    <?php if ($selected): ?>
                  jQuery("input#<?php echo $htmlname ?>").change();
                    <?php endif ?>
                });
              </script>
                <?php
            }
            if (empty($hidelabel)) print $langs->trans("RefOrLabel") . ' : ';
            else if ($hidelabel > 1) {
                if (!empty($conf->global->MAIN_HTML5_PLACEHOLDER)) $placeholder = ' placeholder="' . $langs->trans("RefOrLabel") . '"';
                else $placeholder = ' title="' . $langs->trans("RefOrLabel") . '"';
                if ($hidelabel == 2) {
                    print img_picto($langs->trans("Search"), 'search');
                }
            }
            print '<input type="text" class="minwidth100" name="search_' . $htmlname . '" id="search_' . $htmlname . '" value="' . $selected_input_value . '"' . $placeholder . ' ' . (!empty($conf->global->PRODUCT_SEARCH_AUTOFOCUS) ? 'autofocus' : '') . ' />';
            if ($hidelabel == 3) {
                print img_picto($langs->trans("Search"), 'search');
            }
        } else {
            print $this->select_produits_list($selected, $htmlname, $filtertype, $include_into_categories, $free_into_categories, $limit, $price_level, '', $status, $finished, 0, $socid, $showempty, $forcecombo, $morecss, $hidepriceinlabel, $warehouseStatus);
        }
    }

    /**
     *  Return list of products for a customer
     *
     * @param   int     $selected                 Preselected product
     * @param   string  $htmlname                 Name of select html
     * @param   string  $filtertype               Filter on product type (''=nofilter, 0=product, 1=service)
     * @param   array   $include_into_categories  If not null only products include into categories
     * @param   int     $free_into_categories     1=The products include into categories is free
     * @param   int     $limit                    Limit on number of returned lines
     * @param   int     $price_level              Level of price to show
     * @param   string  $filterkey                Filter on product
     * @param   int     $status                   -1=Return all products, 0=Products not on sell, 1=Products on sell
     * @param   int     $finished                 Filter on finished field: 2=No filter
     * @param   int     $outputmode               0=HTML select string, 1=Array
     * @param   int     $socid                    Thirdparty Id (to get also price dedicated to this customer)
     * @param   string  $showempty                '' to not show empty line. Translation key to show an empty line. '1' show empty line with no text.
     * @param   int     $forcecombo               Force to use combo box
     * @param   string  $morecss                  Add more css on select
     * @param   int     $hidepriceinlabel         1=Hide prices in label
     * @param   string  $warehouseStatus          warehouse status filter, following comma separated filter options can be used
     *                                              'warehouseopen' = select products from open warehouses,
     *                                              'warehouseclosed' = select products from closed warehouses,
     *                                              'warehouseinternal' = select products from warehouses for internal correct/transfer only
     * @return  array                             Array of keys for json
     */
    function select_produits_list($selected = '', $htmlname = 'productid', $filtertype = '', $include_into_categories = array(), $free_into_categories = 0, $limit = 20, $price_level = 0, $filterkey = '', $status = 1, $finished = 2, $outputmode = 0, $socid = 0, $showempty = '1', $forcecombo = 0, $morecss = '', $hidepriceinlabel = 0, $warehouseStatus = '')
    {
        global $langs, $conf, $user, $db;

        $out = '';
        $outarray = array();

        if (!is_array($include_into_categories) || count($include_into_categories) == 0) $free_into_categories = 0;

        $warehouseStatusArray = array();
        if (!empty($warehouseStatus)) {
            require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
            if (preg_match('/warehouseclosed/', $warehouseStatus)) {
                $warehouseStatusArray[] = Entrepot::STATUS_CLOSED;
            }
            if (preg_match('/warehouseopen/', $warehouseStatus)) {
                $warehouseStatusArray[] = Entrepot::STATUS_OPEN_ALL;
            }
            if (preg_match('/warehouseinternal/', $warehouseStatus)) {
                $warehouseStatusArray[] = Entrepot::STATUS_OPEN_INTERNAL;
            }
        }

        $selectFields = " p.rowid, p.label, p.ref, p.description, p.barcode, p.fk_product_type, p.price, p.price_ttc, p.price_base_type, p.tva_tx, p.duration, p.fk_price_expression";
        (count($warehouseStatusArray)) ? $selectFieldsGrouped = ", sum(ps.reel) as stock" : $selectFieldsGrouped = ", p.stock";

        $sql = "SELECT ";
        $sql .= $selectFields . $selectFieldsGrouped;
        //Price by customer
        if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES) && !empty($socid)) {
            $sql .= ' ,pcp.rowid as idprodcustprice, pcp.price as custprice, pcp.price_ttc as custprice_ttc,';
            $sql .= ' pcp.price_base_type as custprice_base_type, pcp.tva_tx as custtva_tx';
            $sql .= ' ,pcp.rowid as idprodcustprice, pcp.price as custprice, pcp.price_ttc as custprice_ttc,';
            $sql .= ' pcp.price_base_type as custprice_base_type, pcp.tva_tx as custtva_tx';
            $selectFields .= ", idprodcustprice, custprice, custprice_ttc, custprice_base_type, custtva_tx";
        }

        // Include only product include in the categories
        if (is_array($include_into_categories) && count($include_into_categories) > 0) {
            $sql .= " , IF(SUM(IF(cp.fk_categorie IN(" . implode(',', $include_into_categories) . "), 1, 0)) > 0, 1, 0) as is_into_categories";
        }

        // Multilang : we add translation
        if (!empty($conf->global->MAIN_MULTILANGS)) {
            $sql .= ", pl.label as label_translated";
            $selectFields .= ", label_translated";
        }
        // Price by quantity
        if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY)) {
            $sql .= ", (SELECT pp.rowid FROM " . MAIN_DB_PREFIX . "product_price as pp WHERE pp.fk_product = p.rowid";
            if ($price_level >= 1 && !empty($conf->global->PRODUIT_MULTIPRICES)) $sql .= " AND price_level=" . $price_level;
            $sql .= " ORDER BY date_price";
            $sql .= " DESC LIMIT 1) as price_rowid";
            $sql .= ", (SELECT pp.price_by_qty FROM " . MAIN_DB_PREFIX . "product_price as pp WHERE pp.fk_product = p.rowid";
            if ($price_level >= 1 && !empty($conf->global->PRODUIT_MULTIPRICES)) $sql .= " AND price_level=" . $price_level;
            $sql .= " ORDER BY date_price";
            $sql .= " DESC LIMIT 1) as price_by_qty";
            $selectFields .= ", price_rowid, price_by_qty";
        }
        $sql .= " FROM " . MAIN_DB_PREFIX . "product as p";
        if (count($warehouseStatusArray)) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_stock as ps on ps.fk_product = p.rowid";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "entrepot as e on ps.fk_entrepot = e.rowid";
        }

        // Include only product include in the categories
        if (is_array($include_into_categories) && count($include_into_categories) > 0) {
            $sql .= " LEFT JOIN  " . MAIN_DB_PREFIX . "categorie_product as cp ON cp.fk_product=p.rowid";
        }

        //Price by customer
        if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES) && !empty($socid)) {
            $sql .= " LEFT JOIN  " . MAIN_DB_PREFIX . "product_customer_price as pcp ON pcp.fk_soc=" . $socid . " AND pcp.fk_product=p.rowid";
        }
        // Multilang : we add translation
        if (!empty($conf->global->MAIN_MULTILANGS)) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_lang as pl ON pl.fk_product = p.rowid AND pl.lang='" . $langs->getDefaultLang() . "'";
        }

        if (!empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD)) {
            $sql .= " LEFT JOIN llx_product_attribute_combination pac ON pac.fk_product_child = p.rowid";
        }

        $sql .= ' WHERE p.entity IN (' . getEntity('product') . ')';
        if (count($warehouseStatusArray)) {
            $sql .= ' AND (p.fk_product_type = 1 OR e.statut IN (' . $this->db->escape(implode(',', $warehouseStatusArray)) . '))';
        }

        if (!empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD)) {
            $sql .= " AND pac.rowid IS NULL";
        }

        if ($finished == 0) {
            $sql .= " AND p.finished = " . $finished;
        } elseif ($finished == 1) {
            $sql .= " AND p.finished = " . $finished;
            if ($status >= 0) $sql .= " AND p.tosell = " . $status;
        } elseif ($status >= 0) {
            $sql .= " AND p.tosell = " . $status;
        }
        if (strval($filtertype) != '') $sql .= " AND p.fk_product_type=" . $filtertype;
        // Add criteria on ref/label
        if ($filterkey != '') {
            $sql .= ' AND (';
            $prefix = empty($conf->global->PRODUCT_DONOTSEARCH_ANYWHERE) ? '%' : '';  // Can use index if PRODUCT_DONOTSEARCH_ANYWHERE is on
            // For natural search
            $scrit = explode(' ', $filterkey);
            $i = 0;
            if (count($scrit) > 1) $sql .= "(";
            foreach ($scrit as $crit) {
                if ($i > 0) $sql .= " AND ";
                $sql .= "(p.ref LIKE '" . $db->escape($prefix . $crit) . "%' OR p.label LIKE '" . $db->escape($prefix . $crit) . "%'";
                if (!empty($conf->global->MAIN_MULTILANGS)) $sql .= " OR pl.label LIKE '" . $db->escape($prefix . $crit) . "%'";
                $sql .= ")";
                $i++;
            }
            if (count($scrit) > 1) $sql .= ")";
            if (!empty($conf->barcode->enabled)) $sql .= " OR p.barcode LIKE '" . $db->escape($prefix . $filterkey) . "%'";
            $sql .= ')';
        }
        if (count($warehouseStatusArray) || (is_array($include_into_categories) && count($include_into_categories) > 0)) {
            $sql .= ' GROUP BY' . $selectFields;
        }
        $sql .= $db->order("p.ref");
        $sql .= $db->plimit($limit);

        // Build output string
        dol_syslog(get_class($this) . "::select_produits_list search product", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
            require_once DOL_DOCUMENT_ROOT . '/product/dynamic_price/class/price_parser.class.php';
            $num = $this->db->num_rows($result);

            $events = null;

            if ($conf->use_javascript_ajax && !$forcecombo) {
                include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
                $comboenhancement = ajax_combobox($htmlname, $events, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT);
                $out .= $comboenhancement;
            }

            $out .= '<select class="flat' . ($morecss ? ' ' . $morecss : '') . '" name="' . $htmlname . '" id="' . $htmlname . '">';

            $textifempty = '';
            // Do not use textifempty = ' ' or '&nbsp;' here, or search on key will search on ' key'.
            //if (! empty($conf->use_javascript_ajax) || $forcecombo) $textifempty='';
            if (!empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT)) {
                if ($showempty && !is_numeric($showempty)) $textifempty = $langs->trans($showempty);
                else $textifempty .= $langs->trans("All");
            }
            if ($showempty) $out .= '<option value="0" selected>' . $textifempty . '</option>';

            $i = 0;
            while ($num && $i < $num) {
                $opt = '';
                $optJson = array();
                $objp = $this->db->fetch_object($result);

                if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY) && !empty($objp->price_by_qty) && $objp->price_by_qty == 1) { // Price by quantity will return many prices for the same product
                    $sql = "SELECT rowid, quantity, price, unitprice, remise_percent, remise";
                    $sql .= " FROM " . MAIN_DB_PREFIX . "product_price_by_qty";
                    $sql .= " WHERE fk_product_price=" . $objp->price_rowid;
                    $sql .= " ORDER BY quantity ASC";

                    dol_syslog(get_class($this) . "::select_produits_list search price by qty", LOG_DEBUG);
                    $result2 = $this->db->query($sql);
                    if ($result2) {
                        $nb_prices = $this->db->num_rows($result2);
                        $j = 0;
                        while ($nb_prices && $j < $nb_prices) {
                            $objp2 = $this->db->fetch_object($result2);

                            $objp->quantity = $objp2->quantity;
                            $objp->price = $objp2->price;
                            $objp->unitprice = $objp2->unitprice;
                            $objp->remise_percent = $objp2->remise_percent;
                            $objp->remise = $objp2->remise;
                            $objp->price_by_qty_rowid = $objp2->rowid;

                            $this->constructProductListOption($objp, $opt, $optJson, 0, $selected, $hidepriceinlabel, ($free_into_categories ? 100 : null));

                            $j++;

                            // Add new entry
                            // "key" value of json key array is used by jQuery automatically as selected value
                            // "label" value of json key array is used by jQuery automatically as text for combo box
                            $out .= $opt;
                            //if (!isset($objp->is_into_categories) || $objp->is_into_categories == 1) {
                                array_push($outarray, $optJson);
                            //}
                        }
                    }
                } else {
                    if (!empty($conf->dynamicprices->enabled) && !empty($objp->fk_price_expression)) {
                        $price_product = new Product($this->db);
                        $price_product->fetch($objp->rowid, '', '', 1);
                        $priceparser = new PriceParser($this->db);
                        $price_result = $priceparser->parseProduct($price_product);
                        if ($price_result >= 0) {
                            $objp->price = $price_result;
                            $objp->unitprice = $price_result;
                            //Calculate the VAT
                            $objp->price_ttc = price2num($objp->price) * (1 + ($objp->tva_tx / 100));
                            $objp->price_ttc = price2num($objp->price_ttc, 'MU');
                        }
                    }

                    $this->constructProductListOption($objp, $opt, $optJson, $price_level, $selected, $hidepriceinlabel, ($free_into_categories ? 100 : null));
                    // Add new entry
                    // "key" value of json key array is used by jQuery automatically as selected value
                    // "label" value of json key array is used by jQuery automatically as text for combo box
                    $out .= $opt;
                    //if (!isset($objp->is_into_categories) || $objp->is_into_categories == 1) {
                        array_push($outarray, $optJson);
                    //}
                }

                $i++;
            }

            $out .= '</select>';

            $this->db->free($result);

            if (empty($outputmode)) return $out;
            return $outarray;
        } else {
            dol_print_error($db);
        }
    }

    /**
     * constructProductListOption
     *
     * @param   resultset   $objp               Resultset of fetch
     * @param   string      $opt                Option (var used for returned value in string option format)
     * @param   string      $optJson            Option (var used for returned value in json format)
     * @param   int         $price_level        Price level
     * @param   string      $selected           Preselected value
     * @param   int         $hidepriceinlabel   Hide price in label
     * @param   int         $forceDiscount      Force discount pourcent
     * @return  void
     */
    private function constructProductListOption(&$objp, &$opt, &$optJson, $price_level, $selected, $hidepriceinlabel = 0, $forceDiscount=null)
    {
        global $langs, $conf, $user, $db;

        $outkey = '';
        $outval = '';
        $outref = '';
        $outlabel = '';
        $outdesc = '';
        $outbarcode = '';
        $outtype = '';
        $outprice_ht = '';
        $outprice_ttc = '';
        $outpricebasetype = '';
        $outtva_tx = '';
        $outqty = 1;
        $outdiscount = 0;
        if (isset($forceDiscount)) {
            $objp->remise_percent = $forceDiscount;
            $outdiscount = $forceDiscount;
        }

        $maxlengtharticle = (empty($conf->global->PRODUCT_MAX_LENGTH_COMBO) ? 48 : $conf->global->PRODUCT_MAX_LENGTH_COMBO);

        $label = $objp->label;
        if (!empty($objp->label_translated)) $label = $objp->label_translated;
        if (!empty($filterkey) && $filterkey != '') $label = preg_replace('/(' . preg_quote($filterkey) . ')/i', '<strong>$1</strong>', $label, 1);

        $outkey = $objp->rowid;
        $outref = $objp->ref;
        $outlabel = $objp->label;
        $outdesc = $objp->description;
        $outbarcode = $objp->barcode;

        $outtype = $objp->fk_product_type;
        $outdurationvalue = $outtype == Product::TYPE_SERVICE ? substr($objp->duration, 0, dol_strlen($objp->duration) - 1) : '';
        $outdurationunit = $outtype == Product::TYPE_SERVICE ? substr($objp->duration, -1) : '';

        $opt = '<option value="' . $objp->rowid . '"';
        $opt .= ($objp->rowid == $selected) ? ' selected' : '';
        $opt .= (isset($objp->is_into_categories) && $objp->is_into_categories == 0) ? ' disabled' : '';
        $opt .= (!empty($objp->price_by_qty_rowid) && $objp->price_by_qty_rowid > 0) ? ' pbq="' . $objp->price_by_qty_rowid . '"' : '';
        if (!empty($conf->stock->enabled) && $objp->fk_product_type == 0 && isset($objp->stock)) {
            if ($objp->stock > 0) $opt .= ' class="product_line_stock_ok"';
            else if ($objp->stock <= 0) $opt .= ' class="product_line_stock_too_low"';
        }
        $opt .= '>';
        $opt .= $objp->ref;
        if ($outbarcode) $opt .= ' (' . $outbarcode . ')';
        $opt .= ' - ' . dol_trunc($label, $maxlengtharticle);

        $objRef = $objp->ref;
        if (!empty($filterkey) && $filterkey != '') $objRef = preg_replace('/(' . preg_quote($filterkey) . ')/i', '<strong>$1</strong>', $objRef, 1);
        $outval .= $objRef;
        if ($outbarcode) $outval .= ' (' . $outbarcode . ')';
        $outval .= ' - ' . dol_trunc($label, $maxlengtharticle);

        $found = 0;

        // Multiprice
        if (empty($hidepriceinlabel) && $price_level >= 1 && $conf->global->PRODUIT_MULTIPRICES)    // If we need a particular price level (from 1 to 6)
        {
            $sql = "SELECT price, price_ttc, price_base_type, tva_tx";
            $sql .= " FROM " . MAIN_DB_PREFIX . "product_price";
            $sql .= " WHERE fk_product='" . $objp->rowid . "'";
            $sql .= " AND entity IN (" . getEntity('productprice') . ")";
            $sql .= " AND price_level=" . $price_level;
            $sql .= " ORDER BY date_price DESC, rowid DESC"; // Warning DESC must be both on date_price and rowid.
            $sql .= " LIMIT 1";

            dol_syslog(get_class($this) . '::constructProductListOption search price for level ' . $price_level . '', LOG_DEBUG);
            $result2 = $this->db->query($sql);
            if ($result2) {
                $objp2 = $this->db->fetch_object($result2);
                if ($objp2) {
                    $found = 1;
                    if ($objp2->price_base_type == 'HT') {
                        $opt .= ' - ' . price($objp2->price, 1, $langs, 0, 0, -1, $conf->currency) . ' ' . $langs->trans("HT");
                        $outval .= ' - ' . price($objp2->price, 0, $langs, 0, 0, -1, $conf->currency) . ' ' . $langs->transnoentities("HT");
                    } else {
                        $opt .= ' - ' . price($objp2->price_ttc, 1, $langs, 0, 0, -1, $conf->currency) . ' ' . $langs->trans("TTC");
                        $outval .= ' - ' . price($objp2->price_ttc, 0, $langs, 0, 0, -1, $conf->currency) . ' ' . $langs->transnoentities("TTC");
                    }
                    $outprice_ht = price($objp2->price);
                    $outprice_ttc = price($objp2->price_ttc);
                    $outpricebasetype = $objp2->price_base_type;
                    $outtva_tx = $objp2->tva_tx;
                }
            } else {
                dol_print_error($this->db);
            }
        }

        // Price by quantity
        if (empty($hidepriceinlabel) && !empty($objp->quantity) && $objp->quantity >= 1 && !empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY)) {
            $found = 1;
            $outqty = $objp->quantity;
            $outdiscount = $objp->remise_percent;
            if ($objp->quantity == 1) {
                $opt .= ' - ' . price($objp->unitprice, 1, $langs, 0, 0, -1, $conf->currency) . "/";
                $outval .= ' - ' . price($objp->unitprice, 0, $langs, 0, 0, -1, $conf->currency) . "/";
                $opt .= $langs->trans("Unit");  // Do not use strtolower because it breaks utf8 encoding
                $outval .= $langs->transnoentities("Unit");
            } else {
                $opt .= ' - ' . price($objp->price, 1, $langs, 0, 0, -1, $conf->currency) . "/" . $objp->quantity;
                $outval .= ' - ' . price($objp->price, 0, $langs, 0, 0, -1, $conf->currency) . "/" . $objp->quantity;
                $opt .= $langs->trans("Units");  // Do not use strtolower because it breaks utf8 encoding
                $outval .= $langs->transnoentities("Units");
            }

            $outprice_ht = price($objp->unitprice);
            $outprice_ttc = price($objp->unitprice * (1 + ($objp->tva_tx / 100)));
            $outpricebasetype = $objp->price_base_type;
            $outtva_tx = $objp->tva_tx;
        }
        if (empty($hidepriceinlabel) && !empty($objp->quantity) && $objp->quantity >= 1) {
            $opt .= " (" . price($objp->unitprice, 1, $langs, 0, 0, -1, $conf->currency) . "/" . $langs->trans("Unit") . ")";  // Do not use strtolower because it breaks utf8 encoding
            $outval .= " (" . price($objp->unitprice, 0, $langs, 0, 0, -1, $conf->currency) . "/" . $langs->transnoentities("Unit") . ")";  // Do not use strtolower because it breaks utf8 encoding
        }
        if (empty($hidepriceinlabel) && !empty($objp->remise_percent) && $objp->remise_percent >= 1) {
            $opt .= " - " . $langs->trans("Discount") . " : " . vatrate($objp->remise_percent) . ' %';
            $outval .= " - " . $langs->transnoentities("Discount") . " : " . vatrate($objp->remise_percent) . ' %';
        }

        // Price by customer
        if (empty($hidepriceinlabel) && !empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
            if (!empty($objp->idprodcustprice)) {
                $found = 1;

                if ($objp->custprice_base_type == 'HT') {
                    $opt .= ' - ' . price($objp->custprice, 1, $langs, 0, 0, -1, $conf->currency) . ' ' . $langs->trans("HT");
                    $outval .= ' - ' . price($objp->custprice, 0, $langs, 0, 0, -1, $conf->currency) . ' ' . $langs->transnoentities("HT");
                } else {
                    $opt .= ' - ' . price($objp->custprice_ttc, 1, $langs, 0, 0, -1, $conf->currency) . ' ' . $langs->trans("TTC");
                    $outval .= ' - ' . price($objp->custprice_ttc, 0, $langs, 0, 0, -1, $conf->currency) . ' ' . $langs->transnoentities("TTC");
                }

                $outprice_ht = price($objp->custprice);
                $outprice_ttc = price($objp->custprice_ttc);
                $outpricebasetype = $objp->custprice_base_type;
                $outtva_tx = $objp->custtva_tx;
            }
        }

        // If level no defined or multiprice not found, we used the default price
        if (empty($hidepriceinlabel) && !$found) {
            if ($objp->price_base_type == 'HT') {
                $opt .= ' - ' . price($objp->price, 1, $langs, 0, 0, -1, $conf->currency) . ' ' . $langs->trans("HT");
                $outval .= ' - ' . price($objp->price, 0, $langs, 0, 0, -1, $conf->currency) . ' ' . $langs->transnoentities("HT");
            } else {
                $opt .= ' - ' . price($objp->price_ttc, 1, $langs, 0, 0, -1, $conf->currency) . ' ' . $langs->trans("TTC");
                $outval .= ' - ' . price($objp->price_ttc, 0, $langs, 0, 0, -1, $conf->currency) . ' ' . $langs->transnoentities("TTC");
            }
            $outprice_ht = price($objp->price);
            $outprice_ttc = price($objp->price_ttc);
            $outpricebasetype = $objp->price_base_type;
            $outtva_tx = $objp->tva_tx;
        }

        if (!empty($conf->stock->enabled) && isset($objp->stock) && $objp->fk_product_type == 0) {
            $opt .= ' - ' . $langs->trans("Stock") . ':' . $objp->stock;

            if ($objp->stock > 0) {
                $outval .= ' - <span class="product_line_stock_ok">' . $langs->transnoentities("Stock") . ':' . $objp->stock . '</span>';
            } elseif ($objp->stock <= 0) {
                $outval .= ' - <span class="product_line_stock_too_low">' . $langs->transnoentities("Stock") . ':' . $objp->stock . '</span>';
            }
        }

        if ($outdurationvalue && $outdurationunit) {
            $da = array("h" => $langs->trans("Hour"), "d" => $langs->trans("Day"), "w" => $langs->trans("Week"), "m" => $langs->trans("Month"), "y" => $langs->trans("Year"));
            if (isset($da[$outdurationunit])) {
                $key = $da[$outdurationunit] . ($outdurationvalue > 1 ? 's' : '');
                $opt .= ' - ' . $outdurationvalue . ' ' . $langs->trans($key);
                $outval .= ' - ' . $outdurationvalue . ' ' . $langs->transnoentities($key);
            }
        }

        $opt .= "</option>\n";
        $optJson = array('key' => $outkey, 'value' => $outref, 'label' => $outval, 'label2' => $outlabel, 'desc' => $outdesc, 'type' => $outtype, 'price_ht' => $outprice_ht, 'price_ttc' => $outprice_ttc, 'pricebasetype' => $outpricebasetype, 'tva_tx' => $outtva_tx, 'qty' => $outqty, 'discount' => $outdiscount, 'duration_value' => $outdurationvalue, 'duration_unit' => $outdurationunit);
        if (isset($objp->is_into_categories) && $objp->is_into_categories == 0) {
            $optJson['opt_disabled'] = true;
        }
    }
}

