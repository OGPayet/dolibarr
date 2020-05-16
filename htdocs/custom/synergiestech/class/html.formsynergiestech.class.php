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
     * @var Form  Instance of the form
     */
    public $form;

    var $cache_equipment_contracts = null;
    /**
     * @var array
     */
    public static $cache_colored_product_label_info = null;
    /**
     * @var array
     */
    public static $cache_product_categories_list = null;

    /**
     * @var array
     */
    public static $cache_contract_list = array();
    /**
     * @var array
     */
    public static $cache_equipement_list = array();

    /**
     * @var array
     */
    public static $cache_extrafields_contract = null;


    /**
     * @var array
     */
    public static $errors = array();

    /**
     * Constructor
     *
     * @param   DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
        $this->form = new Form($db);
    }

    /**
     *  Return list of products categories for the emplacements of the advanced ticket
     *
     * @return  array
     */
    /*function requestmanager_emplacments_array()
    {
        global $conf;

        $list = array();

        // Get categories who has the contract formule category in the full path (exclude the contract formule category)
        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
        $categorie_static = new Categorie($this->db);
        $all_categories = $categorie_static->get_full_arbo('product');
        foreach ($all_categories as $cat) {
            if ((preg_match('/^' . $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_ADVANCEDTICKECT_EMPLACEMENT . '$/', $cat['fullpath']) ||
                    preg_match('/_' . $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_ADVANCEDTICKECT_EMPLACEMENT . '$/', $cat['fullpath']) ||
                    preg_match('/^' . $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_ADVANCEDTICKECT_EMPLACEMENT . '_/', $cat['fullpath']) ||
                    preg_match('/_' . $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_ADVANCEDTICKECT_EMPLACEMENT . '_/', $cat['fullpath'])
                ) && $cat['id'] != $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_ADVANCEDTICKECT_EMPLACEMENT
            ) {
                $list[$cat['id']] = $cat['fulllabel'];
            }
        }

        return $list;
    }*/

    /**
     *  Return list of products for customer in Ajax if Ajax activated or go to select_produits_list
     *
     * @param   int $selected Preselected products
     * @param   string $htmlname Name of HTML select field (must be unique in page)
     * @param   int $filtertype Filter on product type (''=nofilter, 0=product, 1=service)
     * @param   array $include_into_contract_categories If not null only products include into categories
     * @param   int $free_into_categories 1=The products include into categories is free
     * @param   array $include_into_tag_categories Bold lines of product include into the tag categories
     * @param   int $show_mode Show mode of the options (0=for orders, 1=for request manager: bold/into equipment; normal/not into equipment; black/into contract; gray/not into contract)
     * @param   array $only_in_categories Show only product in the categories (all if none founded)
     * @param   int $limit Limit on number of returned lines
     * @param   int $price_level Level of price to show
     * @param   int $status -1=Return all products, 0=Products not on sell, 1=Products on sell
     * @param   int $finished 2=all, 1=finished, 0=raw material
     * @param   string $selected_input_value Value of preselected input text (for use with ajax)
     * @param   int $hidelabel Hide label (0=no, 1=yes, 2=show search icon (before) and placeholder, 3 search icon after)
     * @param   array $ajaxoptions Options for synergiestech_ajax_autocompleter
     * @param   int $socid Thirdparty Id (to get also price dedicated to this customer)
     * @param   string $showempty '' to not show empty line. Translation key to show an empty line. '1' show empty line with no text.
     * @param   int $forcecombo Force to use combo box
     * @param   string $morecss Add more css on select
     * @param   int $hidepriceinlabel 1=Hide prices in label
     * @param   string $warehouseStatus warehouse status filter, following comma separated filter options can be used
     *                                                        'warehouseopen' = select products from open warehouses,
     *                                                        'warehouseclosed' = select products from closed warehouses,
     *                                                        'warehouseinternal' = select products from warehouses for internal correct/transfer only
     * @param   array $selected_combinations Selected combinations. Format: array([attrid] => attrval, [...])
     * @return  void
     */
    function select_produits($selected = '', $htmlname = 'productid', $filtertype = '', $include_into_contract_categories = array(), $free_into_categories = 0, $include_into_tag_categories = array(), $show_mode = 0, $only_in_categories = array(), $limit = 20, $price_level = 0, $status = 1, $finished = 2, $selected_input_value = '', $hidelabel = 0, $ajaxoptions = array(), $socid = 0, $showempty = '1', $forcecombo = 0, $morecss = '', $hidepriceinlabel = 0, $warehouseStatus = '', $selected_combinations = array())
    {
        global $langs, $conf;

        if (!is_array($include_into_contract_categories) || count($include_into_contract_categories) == 0) $free_into_categories = 0;

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
            $urloptioncat = http_build_query(array('include_contract_categories' => $include_into_contract_categories, 'include_tag_categories' => $include_into_tag_categories, 'show_mode' => $show_mode, 'only_in_categories' => $only_in_categories));
            $urloption = 'htmlname=' . $htmlname . '&outjson=1&price_level=' . $price_level . '&type=' . $filtertype . '&mode=1&status=' . $status . '&finished=' . $finished . '&warehousestatus=' . $warehouseStatus . "&" . $urloptioncat;
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

                    jQuery(document).ready(function() {

                        jQuery("input[name='prod_entry_mode']").change(function() {
                            if (jQuery(this).val() == 'free') {
                                jQuery('div#attributes_box').empty();
                            }
                        });

                        jQuery("input#<?php echo $htmlname ?>").change(function() {

                            if (!jQuery(this).val()) {
                                jQuery('div#attributes_box').empty();
                                return;
                            }

                            jQuery.getJSON("<?php echo dol_buildpath('/variants/ajax/getCombinations.php', 2) ?>", {
                                id: jQuery(this).val()
                            }, function(data) {
                                jQuery('div#attributes_box').empty();

                                jQuery.each(data, function(key, val) {

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

                                    jQuery.each(combvalues[val.id], function(key, val) {
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

                        <?php if ($selected) : ?>
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
            print $this->select_produits_list($selected, $htmlname, $filtertype, $include_into_contract_categories, $free_into_categories, $include_into_tag_categories, $show_mode, $only_in_categories, $limit, $price_level, '', $status, $finished, 0, $socid, $showempty, $forcecombo, $morecss, $hidepriceinlabel, $warehouseStatus);
        }
    }

    /**
     *  Return list of products for a customer
     *
     * @param   int $selected Preselected product
     * @param   string $htmlname Name of select html
     * @param   string $filtertype Filter on product type (''=nofilter, 0=product, 1=service)
     * @param   array $include_into_contract_categories If not null only products include into categories
     * @param   int $free_into_categories 1=The products include into categories is free
     * @param   array $include_into_tag_categories Bold lines of product include into the tag categories
     * @param   int $show_mode Show mode of the options (0=for orders, 1=for request manager: bold/into equipment; normal/not into equipment; black/into contract; gray/not into contract)
     * @param   array $only_in_categories Show only product in the categories (all if none founded)
     * @param   int $limit Limit on number of returned lines
     * @param   int $price_level Level of price to show
     * @param   string $filterkey Filter on product
     * @param   int $status -1=Return all products, 0=Products not on sell, 1=Products on sell
     * @param   int $finished Filter on finished field: 2=No filter
     * @param   int $outputmode 0=HTML select string, 1=Array
     * @param   int $socid Thirdparty Id (to get also price dedicated to this customer)
     * @param   string $showempty '' to not show empty line. Translation key to show an empty line. '1' show empty line with no text.
     * @param   int $forcecombo Force to use combo box
     * @param   string $morecss Add more css on select
     * @param   int $hidepriceinlabel 1=Hide prices in label
     * @param   string $warehouseStatus warehouse status filter, following comma separated filter options can be used
     *                                              'warehouseopen' = select products from open warehouses,
     *                                              'warehouseclosed' = select products from closed warehouses,
     *                                              'warehouseinternal' = select products from warehouses for internal correct/transfer only
     * @return  array                             Array of keys for json
     */
    function select_produits_list($selected = '', $htmlname = 'productid', $filtertype = '', $include_into_contract_categories = array(), $free_into_categories = 0, $include_into_tag_categories = array(), $show_mode = 0, $only_in_categories = array(), $limit = 20, $price_level = 0, $filterkey = '', $status = 1, $finished = 2, $outputmode = 0, $socid = 0, $showempty = '1', $forcecombo = 0, $morecss = '', $hidepriceinlabel = 0, $warehouseStatus = '')
    {
        global $langs, $conf, $user, $db;

        $out = '';
        $outarray = array();

        if (!is_array($include_into_contract_categories) || count($include_into_contract_categories) == 0) $free_into_categories = 0;

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

        // Include in the contract categories
        if (is_array($include_into_contract_categories) && count($include_into_contract_categories) > 0) {
            $sql .= " , IFNULL(itcc.count, 0) as is_into_contract_categories";
        } else {
            $sql .= " , 0 as is_into_contract_categories";
        }

        // Include in the tag categories
        if ($show_mode == 1 && is_array($include_into_tag_categories) && count($include_into_tag_categories) > 0) {
            $sql .= " , IFNULL(ittc.count, 0) as is_into_tag_categories";
        } else {
            $sql .= " , 0 as is_into_tag_categories";
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

        // Include in the contract categories
        if (is_array($include_into_contract_categories) && count($include_into_contract_categories) > 0) {
            $sql .= " LEFT JOIN (";
            $sql .= "   SELECT fk_product, COUNT(*) as count";
            $sql .= "   FROM " . MAIN_DB_PREFIX . "categorie_product";
            $sql .= "   WHERE fk_categorie IN (" . implode(',', $include_into_contract_categories) . ")";
            $sql .= "   GROUP BY fk_product";
            $sql .= " ) AS itcc ON itcc.fk_product = p.rowid";
        }

        // Include in the tag categories
        if ($show_mode == 1 && is_array($include_into_tag_categories) && count($include_into_tag_categories) > 0) {
            $sql .= " LEFT JOIN (";
            $sql .= "   SELECT fk_product, COUNT(*) as count";
            $sql .= "   FROM " . MAIN_DB_PREFIX . "categorie_product";
            $sql .= "   WHERE fk_categorie IN (" . implode(',', $include_into_tag_categories) . ")";
            $sql .= "   GROUP BY fk_product";
            $sql .= " ) AS ittc ON ittc.fk_product = p.rowid";
        }

        // Include only product include in the categories
        if (is_array($only_in_categories) && count($only_in_categories) > 0) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "categorie_product as cp ON cp.fk_product=p.rowid";
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
        // Include only product include in the categories
        if (is_array($only_in_categories) && count($only_in_categories) > 0) {
            $subSql = $sql . " AND cp.fk_categorie IN (" . implode(',', $only_in_categories) . ")";
            $result = $this->db->query($subSql . ' GROUP BY ' . $selectFields);
            if ($result && $this->db->num_rows($result) > 0) {
                $sql = $subSql;
            }
        }
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
        if (
            count($warehouseStatusArray) || (is_array($include_into_contract_categories) && count($include_into_contract_categories) > 0) ||
            ($show_mode == 1 && is_array($include_into_tag_categories) && count($include_into_tag_categories) > 0) ||
            (is_array($only_in_categories) && count($only_in_categories) > 0)
        ) {
            $sql .= ' GROUP BY ' . $selectFields;
        }
        $sql .= $db->order("is_into_tag_categories, is_into_contract_categories, p.ref", "DESC, DESC, ASC");
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

                            $this->constructProductListOption($objp, $opt, $optJson, 0, $selected, $hidepriceinlabel, ($free_into_categories && $objp->is_into_contract_categories ? 100 : null), $show_mode);

                            $j++;

                            // Add new entry
                            // "key" value of json key array is used by jQuery automatically as selected value
                            // "label" value of json key array is used by jQuery automatically as text for combo box
                            $out .= $opt;
                            //if (!isset($objp->is_into_contract_categories) || $objp->is_into_contract_categories == 1) {
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

                    $this->constructProductListOption($objp, $opt, $optJson, $price_level, $selected, $hidepriceinlabel, ($free_into_categories && $objp->is_into_contract_categories ? 100 : null), $show_mode);
                    // Add new entry
                    // "key" value of json key array is used by jQuery automatically as selected value
                    // "label" value of json key array is used by jQuery automatically as text for combo box
                    $out .= $opt;
                    //if (!isset($objp->is_into_contract_categories) || $objp->is_into_contract_categories == 1) {
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
     * @param   resultset $objp Resultset of fetch
     * @param   string $opt Option (var used for returned value in string option format)
     * @param   string $optJson Option (var used for returned value in json format)
     * @param   int $price_level Price level
     * @param   string $selected Preselected value
     * @param   int $hidepriceinlabel Hide price in label
     * @param   int $forceDiscount Force discount pourcent
     * @param   int $show_mode Show mode of the options (0=for orders, 1=for request manager: bold/into equipment; normal/not into equipment; black/into contract; gray/not into contract)
     * @return  void
     */
    private function constructProductListOption(&$objp, &$opt, &$optJson, $price_level, $selected, $hidepriceinlabel = 0, $forceDiscount = null, $show_mode = 0)
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
        $opt .= (isset($objp->is_into_contract_categories) && $objp->is_into_contract_categories == 0) ? ' disabled' : '';
        $opt .= (!empty($objp->price_by_qty_rowid) && $objp->price_by_qty_rowid > 0) ? ' pbq="' . $objp->price_by_qty_rowid . '"' : '';
        if (!empty($conf->stock->enabled) && $objp->fk_product_type == 0) {
            if ($objp->stock > 0) $opt .= ' class="product_line_stock_ok"';
            else $opt .= ' class="product_line_stock_too_low"';
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

        if (!empty($conf->stock->enabled) && $objp->fk_product_type == 0) {
            $opt .= ' - ' . $langs->trans("Stock") . ':' . $objp->stock;

            if ($objp->stock > 0) {
                $outval .= ' - <span class="product_line_stock_ok">' . $langs->transnoentities("Stock") . ':' . $objp->stock . '</span>';
            } else {
                $outval .= ' - <span class="product_line_stock_too_low">' . $langs->transnoentities("Stock") . ':' . (isset($objp->stock) ? $objp->stock : 0) . '</span>';
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

        $style = (empty($objp->is_into_tag_categories) ? '' : 'font-weight:bolder;') . (empty($objp->is_into_contract_categories) ? 'color:red;' : 'color:green;');
        $optJson = array('key' => $outkey, 'value' => $outref, 'label' => (!empty($style) ? '<span style="' . $style . '">' : '') . $outval . (!empty($style) ? '</span>' : ''), 'label2' => $outlabel, 'desc' => $outdesc, 'type' => $outtype, 'price_ht' => $outprice_ht, 'price_ttc' => $outprice_ttc, 'pricebasetype' => $outpricebasetype, 'tva_tx' => $outtva_tx, 'qty' => $outqty, 'discount' => $outdiscount, 'duration_value' => $outdurationvalue, 'duration_unit' => $outdurationunit);
        /*if ($isNotIntoCategories) {
            $optJson['opt_disabled'] = true;
        }*/
    }

    /**
     *  Get HTML picto of the state of contract for the equipment
     *
     * @param   int $equipment_id Equipment ID
     * @param   int $reload Reload cache of contract ref list
     * @return  string                    HTML picto of the state of contract for the equipment
     */
    function picto_equipment_has_contract($equipment_id = 0, $reload = 0)
    {
        global $langs;
        $langs->load("synergiestech@synergiestech");

        if ($equipment_id > 0 && !isset($this->cache_equipment_contracts[$equipment_id]) || $reload) {
            $this->cache_contracts_list = array();

            require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

            $sql = "SELECT DISTINCT IF(sourcetype = 'equipement', fk_target, fk_source) AS fk_contrat FROM " . MAIN_DB_PREFIX . "element_element" .
                " WHERE (sourcetype = 'equipement' AND fk_source = " . $equipment_id . " AND targettype = 'contrat')" .
                " OR (sourcetype = 'contrat' AND targettype = 'equipement' AND fk_target = " . $equipment_id . ")";

            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $contract = new Contrat($this->db);
                    if ($contract->fetch($obj->fk_contrat) > 0 && $contract->statut == 1 && $contract->nbofservicesopened > 0) {
                        $this->cache_equipment_contracts[$equipment_id][$obj->fk_contrat] = $contract;
                    }
                }
            }
        }

        if (count($this->cache_equipment_contracts[$equipment_id]) > 0) {
            $ref_list = array();
            foreach ($this->cache_equipment_contracts[$equipment_id] as $contract) {
                $ref_list[] = $contract->ref;
            }

            return img_picto(implode('; ', $ref_list), 'status_green.png@synergiestech');
        } else {
            return img_picto($langs->trans('SynergiesTechDontHaveContract'), 'status_red.png@synergiestech');
        }
    }


    /**
     *  Load colored product label info for a object
     *
     * @param    CommonObject $object Object instance.
     * @return  array
     */
    function loadColoredProductLabelInfo($object)
    {
        global $conf;

        if (!isset(self::$cache_colored_product_label_info[$object->element][$object->id])) {
            // Get products categories of the contracts list
            //------------------------------------------------------------
            $mode = 0; // Show mode for orders lines

            // Gat all contracts of the thirdparty
            require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
            $contract_static = new Contrat($this->db);
            $contract_static->socid = $object->socid;
            $list_contract = $contract_static->getListOfContracts();

            // Get extrafields of the contract
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            $contract_extrafields = new ExtraFields($this->db);
            $contract_extralabels = $contract_extrafields->fetch_name_optionals_label($contract_static->table_element);

            // Get categories who has the contract formule category in the full path (exclude the contract formule category)
            require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
            $categorie_static = new Categorie($this->db);
            $all_categories = $categorie_static->get_full_arbo('product');
            $contract_formule_categories = array();
            foreach ($all_categories as $cat) {
                if ((preg_match('/^' . $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE . '$/', $cat['fullpath']) ||
                        preg_match('/_' . $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE . '$/', $cat['fullpath']) ||
                        preg_match('/^' . $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE . '_/', $cat['fullpath']) ||
                        preg_match('/_' . $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE . '_/', $cat['fullpath'])) && $cat['id'] != $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE
                ) {
                    $contract_formule_categories[$cat['label']] = $cat['id'];
                }
            }

            // Match all formules for the contracts of the thirdparty
            $contract_categories = array();
            $contracts_list = array();
            $formules_list = array();
            $formules_not_found_list = array();
            if (!empty($list_contract)) {
                foreach ($list_contract as $contract) {
                    if (($contract->nbofserviceswait + $contract->nbofservicesopened) > 0 && $contract->statut != 2) {
                        $contract->fetch_optionals();
                        $formule_id = $contract->array_options['options_formule'];
                        $formule_label = $contract_extrafields->attribute_param['formule']['options'][$formule_id];
                        if (!empty($formule_label)) {
                            $contract_category_id = $contract_formule_categories[$formule_label];
                            if (isset($contract_category_id)) {
                                $formules_list[$formule_id] = $formule_label;
                                $contracts_list[] = $contract->getNomUrl(1);
                                $contract_categories[$contract_category_id] = $contract_category_id;
                            } else {
                                $formules_not_found_list[$formule_label] = $formule_label;
                            }
                        }
                    }
                }
            }

            // Get products categories of the equipments list
            //------------------------------------------------------------
            $tag_categories = array();
            $equipment_categories = array();
            if ($object->element == 'requestmanager') {
                $mode = 1; // Show mode for request manager lines

                $object->fetchObjectLinked();

                if (isset($object->linkedObjects['equipement']) && is_array($object->linkedObjects['equipement'])) {
                    foreach ($object->linkedObjects['equipement'] as $equipment) {
                        if ($equipment->fk_product > 0) {
                            $categories = $categorie_static->containing($equipment->fk_product, 'product', 'id');
                            foreach ($categories as $category_id) {
                                if (!isset($equipment_categories[$category_id])) {
                                    // Get all sub categories of the categories founded
                                    foreach ($all_categories as $cat) {
                                        if ((preg_match('/^' . $category_id . '$/', $cat['fullpath']) ||
                                                preg_match('/_' . $category_id . '$/', $cat['fullpath']) ||
                                                preg_match('/^' . $category_id . '_/', $cat['fullpath']) ||
                                                preg_match('/_' . $category_id . '_/', $cat['fullpath'])) &&
                                            (preg_match('/^' . $conf->global->REQUESTMANAGER_ROOT_PRODUCT_CATEGORIES . '$/', $cat['fullpath']) ||
                                                preg_match('/_' . $conf->global->REQUESTMANAGER_ROOT_PRODUCT_CATEGORIES . '$/', $cat['fullpath']) ||
                                                preg_match('/^' . $conf->global->REQUESTMANAGER_ROOT_PRODUCT_CATEGORIES . '_/', $cat['fullpath']) ||
                                                preg_match('/_' . $conf->global->REQUESTMANAGER_ROOT_PRODUCT_CATEGORIES . '_/', $cat['fullpath']))
                                        ) {
                                            $equipment_categories[$cat['id']] = $cat['id'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $categories = $object->loadCategorieList('id');
                foreach ($categories as $category_id) {
                    if (!isset($tag_categories[$category_id])) {
                        // Get all sub categories of the categories founded
                        foreach ($all_categories as $cat) {
                            if (
                                preg_match('/^' . $category_id . '$/', $cat['fullpath']) ||
                                preg_match('/_' . $category_id . '$/', $cat['fullpath']) ||
                                preg_match('/^' . $category_id . '_/', $cat['fullpath']) ||
                                preg_match('/_' . $category_id . '_/', $cat['fullpath'])
                            ) {
                                $tag_categories[$cat['id']] = $cat['id'];
                            }
                        }
                    }
                }
            }

            self::$cache_colored_product_label_info[$object->element][$object->id] = array('contract_categories' => $contract_categories, 'tag_categories' => $tag_categories, 'equipment_categories' => $equipment_categories);
        }

        return (isset(self::$cache_colored_product_label_info[$object->element][$object->id]) ? self::$cache_colored_product_label_info[$object->element][$object->id] : array());
    }

    /**
     *  Load product categories list of a product
     *
     * @param    int $fk_product Product ID.
     * @return  array
     */
    function loadProductCategoriesList($fk_product)
    {
        if (!isset(self::$cache_product_categories_list[$fk_product])) {
            require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
            $categorie_static = new Categorie($this->db);
            $cats = $categorie_static->containing($fk_product, Categorie::TYPE_PRODUCT);
            foreach ($cats as $cat) {
                self::$cache_product_categories_list[$fk_product][] = $cat->id;
            }
        }

        return (isset(self::$cache_product_categories_list[$fk_product]) ? self::$cache_product_categories_list[$fk_product] : array());
    }

    /**
     *  Get colored product label for the objectline_view.tpl.php file
     *
     * @param    CommonObject $_this Object who launch the printObjectLine function.
     * @param   Product $product_static Product object
     * @param   CommonObjectLine $line Selected object line to output
     * @return  string                              Return colored product label
     */
    function getObjectLineViewColoredProductLabel($_this, $product_static, $line)
    {
        global $conf, $langs, $user, $object, $hookmanager;
        $text = '';

        if ($line->fk_product > 0) {

            $this->loadColoredProductLabelInfo($object);

            $contract_categories = isset(self::$cache_colored_product_label_info[$object->element][$object->id]['contract_categories']) ? self::$cache_colored_product_label_info[$object->element][$object->id]['contract_categories'] : array();
            $tag_categories = isset(self::$cache_colored_product_label_info[$object->element][$object->id]['tag_categories']) ? self::$cache_colored_product_label_info[$object->element][$object->id]['tag_categories'] : array();
            $equipment_categories = isset(self::$cache_colored_product_label_info[$object->element][$object->id]['equipment_categories']) ? self::$cache_colored_product_label_info[$object->element][$object->id]['equipment_categories'] : array();

            $this->loadProductCategoriesList($line->fk_product);

            $product_categories = isset(self::$cache_product_categories_list[$line->fk_product]) ? self::$cache_product_categories_list[$line->fk_product] : array();

            $is_into_contract_categories = count(array_diff($contract_categories, $product_categories)) != count($contract_categories);
            $is_into_tag_categories = count(array_diff($tag_categories, $product_categories)) != count($tag_categories);

            if (!is_object($product_static) || !($product_static->id > 0)) {
                $product_static = new Product($this->db);
                $product_static->fetch($line->fk_product);
            }

            $product_static->ref = $line->ref; //can change ref in hook
            $product_static->label = $line->label; //can change label in hook
            $text = $product_static->getNomUrl(1);

            // Define output language and label
            if (!empty($conf->global->MAIN_MULTILANGS)) {
                if (!is_object($_this->thirdparty)) {
                    dol_print_error('', 'Error: Method printObjectLine was called on an object and object->fetch_thirdparty was not done before');
                    return '';
                }

                $outputlangs = $langs;
                $newlang = '';
                if (empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
                if (!empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE) && empty($newlang)) $newlang = $_this->thirdparty->default_lang;    // For language to language of customer
                if (!empty($newlang)) {
                    $outputlangs = new Translate("", $conf);
                    $outputlangs->setDefaultLang($newlang);
                }

                $label = (!empty($product_static->multilangs[$outputlangs->defaultlang]["label"])) ? $product_static->multilangs[$outputlangs->defaultlang]["label"] : $line->product_label;
            } else {
                $label = $line->product_label;
            }

            $style = (empty($is_into_tag_categories) ? '' : 'font-weight:bolder;') . (empty($is_into_contract_categories) ? 'color:red;' : 'color:green;');
            $text .= ' - <span style="' . $style . '">' . (!empty($line->label) ? $line->label : $label) . '</span>';
        }

        return $text;
    }

    /**
     *  Get colored product label for the originproductline.tpl.php file
     *
     * @param    CommonObject $object Object instance.
     * @param   CommonObjectLine $line Selected object line to output
     * @return  string                              Return colored product label
     */
    function getOriginProductLineViewColoredProductLabel($object, $line)
    {
        if ($line->fk_product > 0) {
            $this->loadColoredProductLabelInfo($object);

            $contract_categories = isset(self::$cache_colored_product_label_info[$object->element][$object->id]['contract_categories']) ? self::$cache_colored_product_label_info[$object->element][$object->id]['contract_categories'] : array();
            $tag_categories = isset(self::$cache_colored_product_label_info[$object->element][$object->id]['tag_categories']) ? self::$cache_colored_product_label_info[$object->element][$object->id]['tag_categories'] : array();
            $equipment_categories = isset(self::$cache_colored_product_label_info[$object->element][$object->id]['equipment_categories']) ? self::$cache_colored_product_label_info[$object->element][$object->id]['equipment_categories'] : array();

            $this->loadProductCategoriesList($line->fk_product);

            $product_categories = isset(self::$cache_product_categories_list[$line->fk_product]) ? self::$cache_product_categories_list[$line->fk_product] : array();

            $is_into_contract_categories = count(array_diff($contract_categories, $product_categories)) != count($contract_categories);
            $is_into_tag_categories = count(array_diff($tag_categories, $product_categories)) != count($tag_categories);

            if (!(($line->info_bits & 2) == 2) && !empty($line->fk_product)) {
                $object->tpl['label'] = '';
                if (!empty($line->fk_parent_line)) $object->tpl['label'] .= img_picto('', 'rightarrow');

                $productstatic = new Product($this->db);
                $productstatic->id = $line->fk_product;
                $productstatic->ref = $line->ref;
                $productstatic->type = $line->fk_product_type;
                $object->tpl['label'] .= $productstatic->getNomUrl(1);

                $style = (empty($is_into_tag_categories) ? '' : 'font-weight:bolder;') . (empty($is_into_contract_categories) ? 'color:red;' : 'color:green;');
                $object->tpl['label'] .= ' - <span style="' . $style . '">';

                $object->tpl['label'] .= (!empty($line->label) ? $line->label : $line->product_label);
                // Dates
                if (!empty($line->date_start)) {
                    $date_start = $line->date_start;
                } else {
                    $date_start = $line->date_debut_prevue;
                    if ($line->date_debut_reel) $date_start = $line->date_debut_reel;
                }
                if (!empty($line->date_end)) {
                    $date_end = $line->date_end;
                } else {
                    $date_end = $line->date_fin_prevue;
                    if ($line->date_fin_reel) $date_end = $line->date_fin_reel;
                }
                if ($line->product_type == 1 && ($date_start || $date_end)) {
                    $object->tpl['label'] .= get_date_range($date_start, $date_end);
                }
                $object->tpl['label'] .= '</span>';
            }
        }
    }

    /**
     *     Show a confirmation HTML form or AJAX popup.
     *     Easiest way to use this is with useajax=1.
     *     If you use useajax='xxx', you must also add jquery code to trigger opening of box (with correct parameters)
     *     just after calling this method. For example:
     *       print '<script type="text/javascript">'."\n";
     *       print 'jQuery(document).ready(function() {'."\n";
     *       print 'jQuery(".xxxlink").click(function(e) { jQuery("#aparamid").val(jQuery(this).attr("rel")); jQuery("#dialog-confirm-xxx").dialog("open"); return false; });'."\n";
     *       print '});'."\n";
     *       print '</script>'."\n";
     *
     * @param    string $page Url of page to call if confirmation is OK
     * @param  string $title Title
     * @param  string $question Question
     * @param  string $action Action
     * @param    array $formquestion An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , ))
     * @param    string $selectedchoice "" or "no" or "yes"
     * @param    int $useajax 0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=Yes and preoutput confirm box with div id=dialog-confirm-xxx
     * @param    int $height Force height of box
     * @param  int $width Force width of box ('999' or '90%'). Ignored and forced to 90% on smartphones.
     * @param  int $post Send by form POST if =1,  if string send from existed form name.
     * @return  string                  HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
     */
    function formconfirm($page, $title, $question, $action, $formquestion = array(), $selectedchoice = "", $useajax = 0, $height = 200, $width = 500, $post = 0)
    {
        global $langs, $conf, $form;
        global $useglobalvars;

        if (!is_object($form)) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
            $form = new Form($this->db);
        }

        $more = '';
        $formconfirm = '';
        $inputok = array();
        $inputko = array();

        // Clean parameters
        $newselectedchoice = empty($selectedchoice) ? "no" : $selectedchoice;
        if ($conf->browser->layout == 'phone') $width = '95%';

        if (is_array($formquestion) && !empty($formquestion)) {
            if ($post && !is_string($post)) {
                $more .= '<form id="form_dialog_confirm" name="form_dialog_confirm" action="' . $page . '" method="POST" enctype="multipart/form-data">';
                $more .= '<input type="hidden" id="confirm" name="confirm" value="yes">' . "\n";
                $more .= '<input type="hidden" id="action" name="action" value="' . $action . '">' . "\n";
            }
            // First add hidden fields and value
            foreach ($formquestion as $key => $input) {
                if (is_array($input) && !empty($input)) {
                    if ($post && ($input['name'] == "confirm" || $input['name'] == "action")) continue;
                    if ($input['type'] == 'hidden') {
                        $more .= '<input type="hidden" id="' . $input['name'] . '" name="' . $input['name'] . '" value="' . dol_escape_htmltag($input['value'], 1, 1) . '">' . "\n";
                    }
                }
            }

            // Now add questions
            $more .= '<table class="paddingtopbottomonly" width="100%">' . "\n";
            $more .= '<tr><td colspan="3">' . (!empty($formquestion['text']) ? $formquestion['text'] : '') . '</td></tr>' . "\n";
            foreach ($formquestion as $key => $input) {
                if (is_array($input) && !empty($input)) {
                    $size = (!empty($input['size']) ? ' size="' . $input['size'] . '"' : '');

                    if ($input['type'] == 'text') {
                        $more .= '<tr><td>' . $input['label'] . '</td><td colspan="2" align="left"><input type="text" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '"' . $size . ' value="' . $input['value'] . '" /></td></tr>' . "\n";
                    } else if ($input['type'] == 'password') {
                        $more .= '<tr><td>' . $input['label'] . '</td><td colspan="2" align="left"><input type="password" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '"' . $size . ' value="' . $input['value'] . '" /></td></tr>' . "\n";
                    } else if ($input['type'] == 'select') {
                        $more .= '<tr><td>';
                        if (!empty($input['label'])) $more .= $input['label'] . '</td><td valign="top" colspan="2" align="left">';
                        $more .= $form->selectarray($input['name'], $input['values'], $input['default'], 1);
                        $more .= '</td></tr>' . "\n";
                    } else if ($input['type'] == 'checkbox') {
                        $more .= '<tr>';
                        $more .= '<td>' . $input['label'] . ' </td><td align="left">';
                        $more .= '<input type="checkbox" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '"';
                        if (!is_bool($input['value']) && $input['value'] != 'false') $more .= ' checked';
                        if (is_bool($input['value']) && $input['value']) $more .= ' checked';
                        if (isset($input['disabled'])) $more .= ' disabled';
                        $more .= ' /></td>';
                        $more .= '<td align="left">&nbsp;</td>';
                        $more .= '</tr>' . "\n";
                    } else if ($input['type'] == 'radio') {
                        $i = 0;
                        foreach ($input['values'] as $selkey => $selval) {
                            $more .= '<tr>';
                            if ($i == 0) $more .= '<td class="tdtop">' . $input['label'] . '</td>';
                            else $more .= '<td>&nbsp;</td>';
                            $more .= '<td width="20"><input type="radio" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '" value="' . $selkey . '"';
                            if ($input['disabled']) $more .= ' disabled';
                            $more .= ' /></td>';
                            $more .= '<td align="left">';
                            $more .= $selval;
                            $more .= '</td></tr>' . "\n";
                            $i++;
                        }
                    } else if ($input['type'] == 'date') {
                        $more .= '<tr><td>' . $input['label'] . '</td>';
                        $more .= '<td colspan="2" align="left">';
                        $more .= $form->select_date($input['value'], $input['name'], 0, 0, 0, '', 1, 0, 1);
                        $more .= '</td></tr>' . "\n";
                        $formquestion[] = array('name' => $input['name'] . 'day');
                        $formquestion[] = array('name' => $input['name'] . 'month');
                        $formquestion[] = array('name' => $input['name'] . 'year');
                        $formquestion[] = array('name' => $input['name'] . 'hour');
                        $formquestion[] = array('name' => $input['name'] . 'min');
                    } else if ($input['type'] == 'other') {
                        $more .= '<tr><td>';
                        if (!empty($input['label'])) $more .= $input['label'] . '</td><td colspan="2" align="left">';
                        $more .= $input['value'];
                        $more .= '</td></tr>' . "\n";
                    } else if ($input['type'] == 'onecolumn') {
                        $more .= '<tr><td colspan="3" align="left">';
                        $more .= $input['value'];
                        $more .= '</td></tr>' . "\n";
                    }
                }
            }
            $more .= '</table>' . "\n";
            if ($post && !is_string($post)) $more .= '</form>';
        }

        // JQUI method dialog is broken with jmobile, we use standard HTML.
        // Note: When using dol_use_jmobile or no js, you must also check code for button use a GET url with action=xxx and check that you also output the confirm code when action=xxx
        // See page product/card.php for example
        if (!empty($conf->dol_use_jmobile)) $useajax = 0;
        if (empty($conf->use_javascript_ajax)) $useajax = 0;

        if ($useajax) {
            $autoOpen = true;
            $dialogconfirm = 'dialog-confirm';
            $button = '';
            if (!is_numeric($useajax)) {
                $button = $useajax;
                $useajax = 1;
                $autoOpen = false;
                $dialogconfirm .= '-' . $button;
            }
            $pageyes = $page . (preg_match('/\?/', $page) ? '&' : '?') . 'action=' . $action . '&confirm=yes';
            $pageno = ($useajax == 2 ? $page . (preg_match('/\?/', $page) ? '&' : '?') . 'action=' . $action . '&confirm=no' : '');
            // Add input fields into list of fields to read during submit (inputok and inputko)
            if (is_array($formquestion)) {
                foreach ($formquestion as $key => $input) {
                    //print "xx ".$key." rr ".is_array($input)."<br>\n";
                    if (is_array($input) && isset($input['name'])) {
                        // Modification Open-DSI - Begin
                        if (is_array($input['name'])) $inputok = array_merge($inputok, $input['name']);
                        else array_push($inputok, $input['name']);
                        // Modification Open-DSI - End
                    }
                    if (isset($input['inputko']) && $input['inputko'] == 1) array_push($inputko, $input['name']);
                }
            }
            // Show JQuery confirm box. Note that global var $useglobalvars is used inside this template
            $formconfirm .= '<div id="' . $dialogconfirm . '" title="' . dol_escape_htmltag($title) . '" style="display: none;">';
            if (!empty($more)) {
                $formconfirm .= '<div class="confirmquestions">' . $more . '</div>';
            }
            $formconfirm .= ($question ? '<div class="confirmmessage">' . img_help('', '') . ' ' . $question . '</div>' : '');
            $formconfirm .= '</div>' . "\n";

            $formconfirm .= "\n<!-- begin ajax form_confirm page=" . $page . " -->\n";
            $formconfirm .= '<script type="text/javascript">' . "\n";
            $formconfirm .= 'jQuery(document).ready(function() {
                $(function() {
			$( "#' . $dialogconfirm . '" ).dialog(
			{
                        autoOpen: ' . ($autoOpen ? "true" : "false") . ',';
            if ($newselectedchoice == 'no') {
                $formconfirm .= '
						open: function() {
						$(this).parent().find("button.ui-button:eq(2)").focus();
						},';
            }
            if ($post && !is_string($post)) {
                $formconfirm .= '
                        resizable: false,
                        height: "' . $height . '",
                        width: "' . $width . '",
                        modal: true,
                        closeOnEscape: false,
                        buttons: {
                            "' . dol_escape_js($langs->transnoentities("Yes")) . '": function() {
                                var form_dialog_confirm = $("form#form_dialog_confirm");
                                form_dialog_confirm.find("input#confirm").val("yes");
                                form_dialog_confirm.submit();
                                $(this).dialog("close");
                            },
                            "' . dol_escape_js($langs->transnoentities("No")) . '": function() {
                                if (' . ($useajax == 2 ? '1' : '0') . ' == 1) {
                                    var form_dialog_confirm = $("form#form_dialog_confirm");
                                    form_dialog_confirm.find("input#confirm").val("no");
                                    form_dialog_confirm.submit();
                                }
                                $(this).dialog("close");
                            }
                        }
                    }
                    );

                  var button = "' . $button . '";
                  if (button.length > 0) {
                      $( "#" + button ).click(function() {
                        $("#' . $dialogconfirm . '").dialog("open");
                  });
                    }
                });
                });
                </script>';
            } elseif ($post && is_string($post)) {
                $formconfirm .= '
                        resizable: false,
                        height: "' . $height . '",
                        width: "' . $width . '",
                        modal: true,
                        closeOnEscape: false,
                        buttons: {
                            "' . dol_escape_js($langs->transnoentities("Yes")) . '": function() {
                                var dialog_div = $("#' . $dialogconfirm . '");
                                var form = $("form#' . $post . '");
                                if (form.length == 0) form = $(\'form[name="' . $post . '"]\');

                                var inputok = ' . json_encode($inputok) . ';
                                if (inputok.length>0) {
                                  $.each(inputok, function(i, inputname) {
                                    var input = dialog_div.find("#" + inputname);
                                    var form_input = find_form_input(form, inputname);

                                    var more = "";
                                    if (input.attr("type") == "checkbox") { more = ":checked"; }
                                    if (input.attr("type") == "radio") { more = ":checked"; }

                                    var inputvalue = dialog_div.find("#" + inputname + more).val();
                                    if (typeof inputvalue == "undefined") { inputvalue=""; }
                                    form_input.val(inputvalue);
                                  });
                                }

                                var form_action_input = find_form_input(form, "action");
                                var form_confirm_input = find_form_input(form, "confirm");
                                form_action_input.val("' . $action . '");
                                form_confirm_input.val("yes");
                                form.submit();
                                $(this).dialog("close");
                            },
                            "' . dol_escape_js($langs->transnoentities("No")) . '": function() {
                                if (' . ($useajax == 2 ? '1' : '0') . ' == 1) {
                                  var dialog_div = $("#' . $dialogconfirm . '");
                                  var form = $("form#' . $post . '");
                                  if (form.length == 0) form = $(\'form[name="' . $post . '"]\');

				    var inputko = ' . json_encode($inputko) . ';
                                  if (inputko.length>0) {
                                    $.each(inputko, function(i, inputname) {
                                      var input = dialog_div.find("#" + inputname);
                                      var form_input = find_form_input(form, inputname);

                                      var more = "";
                                      if (input.attr("type") == "checkbox") { more = ":checked"; }
                                      if (input.attr("type") == "radio") { more = ":checked"; }

                                      var inputvalue = dialog_div.find("#" + inputname + more).val();
                                      if (typeof inputvalue == "undefined") { inputvalue=""; }
                                      form_input.val(inputvalue);
                                    });
                                  }

                                  var form_action_input = find_form_input(form, "action");
                                  var form_confirm_input = find_form_input(form, "confirm");
                                  form_action_input.val("' . $action . '");
                                  form_confirm_input.val("no");
                                  form.submit();
                                }
                                $(this).dialog("close");
                            }
                        }
                    }
                    );

                    function find_form_input(form, inputname) {
                      var form_input = form.find("#" + inputname);
                      if (form_input.length == 0) form_input = form.find(\'[name="' . $post . '"]\');
                      if (form_input.length == 0) {
                        form.append(\'<input type="hidden" id="\' + inputname + \'" name="\' + inputname + \'" value="">\');
                        form_input = form.find("#" + inputname);
                      }
                      return form_input;
                    }

                  var button = "' . $button . '";
                  if (button.length > 0) {
                      $( "#" + button ).click(function() {
                        $("#' . $dialogconfirm . '").dialog("open");
                  });
                    }
                });
                });
                </script>';
            } else {
                $formconfirm .= '
                        resizable: false,
                        height: "' . $height . '",
                        width: "' . $width . '",
                        modal: true,
                        closeOnEscape: false,
                        buttons: {
                            "' . dol_escape_js($langs->transnoentities("Yes")) . '": function() {
				var options="";
				var inputok = ' . json_encode($inputok) . ';
				var pageyes = "' . dol_escape_js(!empty($pageyes) ? $pageyes : '') . '";
				if (inputok.length>0) {
					$.each(inputok, function(i, inputname) {
						var more = "";
						if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
					    if ($("#" + inputname).attr("type") == "radio") { more = ":checked"; }
						var inputvalue = $("#" + inputname + more).val();
						if (typeof inputvalue == "undefined") { inputvalue=""; }
						options += "&" + inputname + "=" + urlencode(inputvalue);
					});
				}
				var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "") + options;
				//alert(urljump);
						if (pageyes.length > 0) { location.href = urljump; }
                                $(this).dialog("close");
                            },
                            "' . dol_escape_js($langs->transnoentities("No")) . '": function() {
				var options = "";
				var inputko = ' . json_encode($inputko) . ';
				var pageno="' . dol_escape_js(!empty($pageno) ? $pageno : '') . '";
				if (inputko.length>0) {
					$.each(inputko, function(i, inputname) {
						var more = "";
						if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
						var inputvalue = $("#" + inputname + more).val();
						if (typeof inputvalue == "undefined") { inputvalue=""; }
						options += "&" + inputname + "=" + urlencode(inputvalue);
					});
				}
				var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "") + options;
				//alert(urljump);
						if (pageno.length > 0) { location.href = urljump; }
                                $(this).dialog("close");
                            }
                        }
                    }
                    );

			var button = "' . $button . '";
			if (button.length > 0) {
			$( "#" + button ).click(function() {
				$("#' . $dialogconfirm . '").dialog("open");
				});
                    }
                });
                });
                </script>';
            }
            $formconfirm .= "<!-- end ajax form_confirm -->\n";
        } else {
            $formconfirm .= "\n<!-- begin form_confirm page=" . $page . " -->\n";

            $formconfirm .= '<form method="POST" action="' . $page . '" class="notoptoleftroright">' . "\n";
            $formconfirm .= '<input type="hidden" name="action" value="' . $action . '">' . "\n";
            $formconfirm .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";

            $formconfirm .= '<table width="100%" class="valid">' . "\n";

            // Line title
            $formconfirm .= '<tr class="validtitre"><td class="validtitre" colspan="3">' . img_picto('', 'recent') . ' ' . $title . '</td></tr>' . "\n";

            // Line form fields
            if ($more) {
                $formconfirm .= '<tr class="valid"><td class="valid" colspan="3">' . "\n";
                $formconfirm .= $more;
                $formconfirm .= '</td></tr>' . "\n";
            }

            // Line with question
            $formconfirm .= '<tr class="valid">';
            $formconfirm .= '<td class="valid">' . $question . '</td>';
            $formconfirm .= '<td class="valid">';
            $formconfirm .= $form->selectyesno("confirm", $newselectedchoice);
            $formconfirm .= '</td>';
            $formconfirm .= '<td class="valid" align="center"><input class="button valignmiddle" type="submit" value="' . $langs->trans("Validate") . '"></td>';
            $formconfirm .= '</tr>' . "\n";

            $formconfirm .= '</table>' . "\n";

            $formconfirm .= "</form>\n";
            $formconfirm .= '<br>';

            $formconfirm .= "<!-- end form_confirm -->\n";
        }

        return $formconfirm;
    }

    /**
     *     Show a confirmation HTML form or AJAX popup with file upload
     *     Easiest way to use this is with useajax=1.
     *     If you use useajax='xxx', you must also add jquery code to trigger opening of box (with correct parameters)
     *     just after calling this method. For example:
     *       print '<script type="text/javascript">'."\n";
     *       print 'jQuery(document).ready(function() {'."\n";
     *       print 'jQuery(".xxxlink").click(function(e) { jQuery("#aparamid").val(jQuery(this).attr("rel")); jQuery("#dialog-confirm-xxx").dialog("open"); return false; });'."\n";
     *       print '});'."\n";
     *       print '</script>'."\n";
     *
     * @param    string $page Url of page to call if confirmation is OK
     * @param  string $title Title
     * @param  string $question Question
     * @param  string $action Action
     * @param    array $formquestion An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , ))
     * @param    string $selectedchoice "" or "no" or "yes"
     * @param    int $useajax 0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=Yes and preoutput confirm box with div id=dialog-confirm-xxx
     * @param    int $height Force height of box
     * @param  int $width Force width of box ('999' or '90%'). Ignored and forced to 90% on smartphones.
     * @return  string                  HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
     */
    function formconfirmfile($page, $title, $question, $action, $formquestion = '', $selectedchoice = "", $useajax = 0, $height = 200, $width = 500)
    {
        global $langs, $conf;
        global $useglobalvars;

        $more = '';
        $formconfirm = '';
        $inputok = array();
        $inputko = array();

        // Clean parameters
        $newselectedchoice = empty($selectedchoice) ? "no" : $selectedchoice;
        if ($conf->browser->layout == 'phone') $width = '95%';

        if (is_array($formquestion) && !empty($formquestion)) {
            // First add hidden fields and value
            foreach ($formquestion as $key => $input) {
                if (is_array($input) && !empty($input)) {
                    if ($input['type'] == 'hidden') {
                        $more .= '<input type="hidden" id="' . $input['name'] . '" name="' . $input['name'] . '" value="' . dol_escape_htmltag($input['value']) . '">' . "\n";
                    }
                }
            }

            // Now add questions
            $more .= '<table class="paddingtopbottomonly" width="100%">' . "\n";
            $more .= '<tr><td colspan="3">' . (!empty($formquestion['text']) ? $formquestion['text'] : '') . '</td></tr>' . "\n";
            foreach ($formquestion as $key => $input) {
                if (is_array($input) && !empty($input)) {
                    $size = (!empty($input['size']) ? ' size="' . $input['size'] . '"' : '');

                    if ($input['type'] == 'text') {
                        $more .= '<tr><td>' . $input['label'] . '</td><td colspan="2" align="left"><input type="text" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '"' . $size . ' value="' . $input['value'] . '" /></td></tr>' . "\n";
                    } else if ($input['type'] == 'password') {
                        $more .= '<tr><td>' . $input['label'] . '</td><td colspan="2" align="left"><input type="password" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '"' . $size . ' value="' . $input['value'] . '" /></td></tr>' . "\n";
                    } else if ($input['type'] == 'select') {
                        $more .= '<tr><td>';
                        if (!empty($input['label'])) $more .= $input['label'] . '</td><td valign="top" colspan="2" align="left">';
                        $more .= $this->form->selectarray($input['name'], $input['values'], $input['default'], 1);
                        $more .= '</td></tr>' . "\n";
                    } else if ($input['type'] == 'checkbox') {
                        $more .= '<tr>';
                        $more .= '<td>' . $input['label'] . ' </td><td align="left">';
                        $more .= '<input type="checkbox" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '"';
                        if (!is_bool($input['value']) && $input['value'] != 'false') $more .= ' checked';
                        if (is_bool($input['value']) && $input['value']) $more .= ' checked';
                        if (isset($input['disabled'])) $more .= ' disabled';
                        $more .= ' /></td>';
                        $more .= '<td align="left">&nbsp;</td>';
                        $more .= '</tr>' . "\n";
                    } else if ($input['type'] == 'radio') {
                        $i = 0;
                        foreach ($input['values'] as $selkey => $selval) {
                            $more .= '<tr>';
                            if ($i == 0) $more .= '<td class="tdtop">' . $input['label'] . '</td>';
                            else $more .= '<td>&nbsp;</td>';
                            $more .= '<td width="20"><input type="radio" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '" value="' . $selkey . '"';
                            if ($input['disabled']) $more .= ' disabled';
                            $more .= ' /></td>';
                            $more .= '<td align="left">';
                            $more .= $selval;
                            $more .= '</td></tr>' . "\n";
                            $i++;
                        }
                    } else if ($input['type'] == 'date') {
                        $more .= '<tr><td>' . $input['label'] . '</td>';
                        $more .= '<td colspan="2" align="left">';
                        $more .= $this->form->select_date($input['value'], $input['name'], 0, 0, 0, '', 1, 0, 1);
                        $more .= '</td></tr>' . "\n";
                        $formquestion[] = array('name' => $input['name'] . 'day');
                        $formquestion[] = array('name' => $input['name'] . 'month');
                        $formquestion[] = array('name' => $input['name'] . 'year');
                        $formquestion[] = array('name' => $input['name'] . 'hour');
                        $formquestion[] = array('name' => $input['name'] . 'min');
                    } else if ($input['type'] == 'other') {
                        $more .= '<tr><td>';
                        if (!empty($input['label'])) $more .= $input['label'] . '</td><td colspan="2" align="left">';
                        $more .= $input['value'];
                        $more .= '</td></tr>' . "\n";
                    } else if ($input['type'] == 'onecolumn') {
                        $more .= '<tr><td colspan="3" align="left">';
                        $more .= $input['value'];
                        $more .= '</td></tr>' . "\n";
                    }
                }
            }
            $more .= '</table>' . "\n";
        }

        // JQUI method dialog is broken with jmobile, we use standard HTML.
        // Note: When using dol_use_jmobile or no js, you must also check code for button use a GET url with action=xxx and check that you also output the confirm code when action=xxx
        // See page product/card.php for example
        if (!empty($conf->dol_use_jmobile)) $useajax = 0;
        if (empty($conf->use_javascript_ajax)) $useajax = 0;

        if ($useajax) {
            $autoOpen = true;
            $dialogconfirm = 'dialog-confirm';
            $button = '';
            if (!is_numeric($useajax)) {
                $button = $useajax;
                $useajax = 1;
                $autoOpen = false;
                $dialogconfirm .= '-' . $button;
            }
            $pageyes = $page . (preg_match('/\?/', $page) ? '&' : '?') . 'action=' . $action . '&confirm=yes';
            //$pageno=($useajax == 2 ? $page.(preg_match('/\?/',$page)?'&':'?').'confirm=no':'');
            // Add input fields into list of fields to read during submit (inputok and inputko)
            if (is_array($formquestion)) {
                foreach ($formquestion as $key => $input) {
                    //print "xx ".$key." rr ".is_array($input)."<br>\n";
                    if (is_array($input) && isset($input['name'])) {
                        // Modification Open-DSI - Begin
                        if (is_array($input['name'])) $inputok = array_merge($inputok, $input['name']);
                        else array_push($inputok, $input['name']);
                        // Modification Open-DSI - End
                    }
                    if (isset($input['inputko']) && $input['inputko'] == 1) array_push($inputko, $input['name']);
                }
            }
            // Show JQuery confirm box. Note that global var $useglobalvars is used inside this template
            $formconfirm .= '<div id="' . $dialogconfirm . '" class="dialog-confirmfile" title="' . dol_escape_htmltag($title) . '" style="display: none;">';
            if (!empty($more)) {
                $inputToken = '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '" />';
                //$inputAction  = '<input type="hidden" name="action" value="' . $action .'" />';
                //$inputConfirm = '<input type="hidden" name="confirm" value="yes" />';
                $more = '<form action="' . $pageyes . '" id="synergiestech_formconfirmfile" name="synergiestech_formconfirmfile" enctype="multipart/form-data" method="post">' . $inputToken . $more . '</form>';
                $formconfirm .= '<div class="confirmquestions">' . $more . '</div>';
            }
            $formconfirm .= ($question ? '<div class="confirmmessage">' . img_help('', '') . ' ' . $question . '</div>' : '');
            $formconfirm .= '</div>' . "\n";

            $formconfirm .= "\n<!-- begin ajax form_confirm page=" . $page . " -->\n";
            $formconfirm .= '<script type="text/javascript">' . "\n";
            $formconfirm .= 'jQuery(document).ready(function() {
            $(function() {
		$( "#' . $dialogconfirm . '" ).dialog(
		{
                    autoOpen: ' . ($autoOpen ? "true" : "false") . ',';
            if ($newselectedchoice == 'no') {
                $formconfirm .= '
						open: function() {
						    var dialog_confirm = $("#dialog-confirm:not([class*=\'dialog-confirmfile\'])");
						    if (dialog_confirm.length > 0) {
						        dialog_confirm.dialog("close");
						    }
					$(this).parent().find("button.ui-button:eq(2)").focus();
						},';
            }
            $formconfirm .= '
                    resizable: false,
                    height: "' . $height . '",
                    width: "' . $width . '",
                    modal: true,
                    closeOnEscape: false,
                    buttons: {
                        "' . dol_escape_js($langs->transnoentities("Yes")) . '": function() {
				$("#synergiestech_formconfirmfile").submit();
                            $(this).dialog("close");
                        },
                        "' . dol_escape_js($langs->transnoentities("No")) . '": function() {
                            $(this).dialog("close");
                        }
                    }
                }
                );

		var button = "' . $button . '";
		if (button.length > 0) {
			$( "#" + button ).click(function() {
				$("#' . $dialogconfirm . '").dialog("open");
				});
                }
            });
            });
            </script>';
            $formconfirm .= "<!-- end ajax form_confirm -->\n";
        } else {
            $formconfirm .= "\n<!-- begin form_confirm page=" . $page . " -->\n";

            $formconfirm .= '<form method="POST" action="' . $page . '" class="notoptoleftroright">' . "\n";
            $formconfirm .= '<input type="hidden" name="action" value="' . $action . '">' . "\n";
            $formconfirm .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";

            $formconfirm .= '<table width="100%" class="valid">' . "\n";

            // Line title
            $formconfirm .= '<tr class="validtitre"><td class="validtitre" colspan="3">' . img_picto('', 'recent') . ' ' . $title . '</td></tr>' . "\n";

            // Line form fields
            if ($more) {
                $formconfirm .= '<tr class="valid"><td class="valid" colspan="3">' . "\n";
                $formconfirm .= $more;
                $formconfirm .= '</td></tr>' . "\n";
            }

            // Line with question
            $formconfirm .= '<tr class="valid">';
            $formconfirm .= '<td class="valid">' . $question . '</td>';
            $formconfirm .= '<td class="valid">';
            $formconfirm .= $this->form->selectyesno("confirm", $newselectedchoice);
            $formconfirm .= '</td>';
            $formconfirm .= '<td class="valid" align="center"><input class="button valignmiddle" type="submit" value="' . $langs->trans("Validate") . '"></td>';
            $formconfirm .= '</tr>' . "\n";

            $formconfirm .= '</table>' . "\n";

            $formconfirm .= "</form>\n";
            $formconfirm .= '<br>';

            $formconfirm .= "<!-- end form_confirm -->\n";
        }

        return $formconfirm;
    }


    /**
     *  Output html form to select a actioncomm
     *
     * @param   int $idActionComm Id of the actioncomm
     * @param   array $actionCommCodeList [=array] List of actioncomm code
     * @param   string $selected Preselected actioncomm
     * @param   string $htmlname Name of field in form
     * @param   string $showempty Add an empty field (Can be '1' or text key to use on empty line like 'SelectThirdParty')
     * @param   int $forcecombo Force to use combo box
     * @param   array $events Ajax event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     * @param    int $usesearchtoselect Minimum length of input string to start autocomplete
     * @param   string $morecss Add more css styles to the SELECT component
     * @param   string $moreparam Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     * @param   bool $options_only Return options only (for ajax treatment)
     * @return  string                              HTML string with select box for status.
     */
    function select_actioncomm($idActionComm, $actionCommCodeList = array(), $selected = '', $htmlname = 'actioncomm_id', $showempty = 0, $forcecombo = 0, $events = array(), $usesearchtoselect = 0, $morecss = 'minwidth100', $moreparam = '', $options_only = false)
    {
        global $conf, $langs, $user;

        $langs->load('requestmanager@requestmanager');

        $out = '';

        $moreparam = 'style="width: 95%"';

        // search actioncomm
        $sql = "SELECT";
        $sql .= " ac.id as id";
        $sql .= ", ac.label as label";
        $sql .= ", ac.datep as datep";
        $sql .= ", ac.datep2 as datep2";
        $sql .= ", user.lastname as name";
        $sql .= ", user.firstname as firstname";
        $sql .= " FROM " . MAIN_DB_PREFIX . "actioncomm as ac";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as user ON user.rowid=ac.fk_user_action";

        $sql .= " WHERE ac.entity IN (" . getEntity('agenda') . ")";
        if (count($actionCommCodeList) > 0) {
            $sql .= " AND ac.code IN (";
            $sqlCodeIn = '';
            $i = 0;
            foreach ($actionCommCodeList as $actionCommCode) {
                if ($i > 0) {
                    $sqlCodeIn .= ", ";
                }
                $sqlCodeIn .= "'" . $this->db->escape($actionCommCode) . "'";

                $i++;
            }
            $sql .= $sqlCodeIn;
            $sql .= ")";
        }
        $sql .= " AND ac.elementtype IS NULL";
        if ($idActionComm > 0) {
            $sql .= " AND ac.id = " . $idActionComm;
        }
        //ADD By Alexis LAURIER - 07/03/2019
        //We remove view of calls not assigned to the current user or wildix user (id 1632) when call was yesterday
        //Display all today calls

        $sql .= " AND ( (  DATEDIFF(NOW(), ac.datep) >= 1 ";
        $sql .= " AND (ac.fk_user_action = '1632' OR ac.fk_user_action = " . $user->id . ")) OR  DATEDIFF(NOW(), ac.datep) = 0 ) ";
        $sql .= " AND  DATEDIFF(NOW(), ac.datep) <= 15 ";

        ///END

        $sql .= " ORDER BY ac.datep DESC";

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);

        if ($resql) {
            if ($conf->use_javascript_ajax && !$forcecombo && !$options_only) {
                include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
                $comboenhancement = ajax_combobox($htmlname, $events, $usesearchtoselect);
                $out .= $comboenhancement;
            }

            if (!$options_only) $out .= '<select id="' . $htmlname . '" class="flat' . ($morecss ? ' ' . $morecss : '') . '"' . ($moreparam ? ' ' . $moreparam : '') . ' name="' . $htmlname . '">';

            $textifempty = '';
            // Do not use textifempty = ' ' or '&nbsp;' here, or search on key will search on ' key'.
            //if (! empty($conf->use_javascript_ajax) || $forcecombo) $textifempty='';
            if (!empty($usesearchtoselect)) {
                if ($showempty && !is_numeric($showempty)) $textifempty = $langs->trans($showempty);
                else $textifempty .= $langs->trans("All");
            }
            if ($showempty) $out .= '<option value="-1">' . $textifempty . '</option>' . "\n";

            $num = $this->db->num_rows($resql);
            $i = 0;
            require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
            require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
            if ($num) {
                while ($i < $num) {
                    $obj = $this->db->fetch_object($resql);
                    //We build a special label for displayed action comm

                    $label = $obj->label;
                    //Dispalying begin date event
                    if (num_between_day(dol_stringtotime($obj->datep), dol_now(), 0) >= 1) $label = dol_print_date($obj->datep, ' %A %d/%m') . " - " . $label;
                    //Displaying user owner event
                    $label = $label . " - " . $obj->firstname . " " . $obj->name;

                    //Different color between different call;
                    $color = "#999999";
                    $color_class = 'st_color_default';
                    //incomming call
                    $duration = "";
                    if (strpos($obj->label, $langs->trans('RequestManagerAnswerPhone')) !== false) {
                        //It is a voice mail
                        $color_class = 'st_color_voicemail';
                    } else if (strpos($obj->label, $langs->trans('RequestManagerIncomingCall')) !== false) {
                        //We are in an incoming call
                        //We check date to determine duration
                        $duration = dol_stringtotime($obj->datep2) - dol_stringtotime($obj->datep);

                        if ($duration > 6) {
                            //call has been answered
                            //We may check if it was not a voice mail

                            //It is a normal incoming call
                            //$color = "#008000";
                            $color_class = 'st_color_call_has_been_answered';
                        } else {
                            //Call has not been answered
                            //$color = "#000099";
                            $color_class = 'st_color_call_has_not_been_answered';
                        }
                    }

                    //outgoing call
                    else if (strpos($obj->label, $langs->trans('RequestManagerOutgoingCall')) !== false) {
                        //We are in an outgoing call
                        //$color = "#999999";
                        $color_class = 'st_color_outgoing_call';
                    }

                    //transfered call
                    else if (strpos($obj->label, $langs->trans('RequestManagerTransferedCall')) !== false) {
                        //We are in an transfered call
                        //$color = "#999999";
                        $color_class = 'st_color_transfered_call';
                    }


                    $out .= '<option class="' . $color_class . '" value="' . $obj->id . '"';
                    if ($selected && $selected == $obj->id) $out .= ' selected';
                    //$out .= " style='color: " . $color . "' ";
                    $out .= '>';
                    $out .= $label;

                    $out .= '</option>';
                    $i++;
                }
            } else {
                $out .= '<option value="-1" disabled>' . $langs->trans("RequestManagerNoActionComm") . '</option>';
            }

            if (!$options_only) {
                $out .= '</select>';
            }

            if ($conf->use_javascript_ajax && !$forcecombo && !$options_only) {
                $out .= '<style>
                           .select2-result.st_color_default {
                             color: #999999;
                           }
                           .select2-result.st_color_call_has_been_answered {
                             color: #008000;
                           }
                           .select2-result.st_color_call_has_not_been_answered {
                             color: #ff0000;
                           }
                           .select2-result.st_color_outgoing_call {
                             color: #000099;
                           }
                           .select2-result.st_color_transfered_call {
                             color: #808080;
                           }
						   .select2-result.st_color_voicemail {
                             color: #d759fe;
                           }
                         </style>';
            }

            $this->num = $num;
            return $out;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *  load in cache contract related to a requestor or a benefactor
     *  return result according to selected param with value taken from existing cache or db if not available
     * Cache is filled with all parsed data into db, so only from new item
     *
     * @param   int $socId Id of the principal thirdparty
     * @param   int $benefactorId Id of the benefactor thirdparty
     * @return  Contract[] List of contract
     */

    function fetch_all_contract_for_these_company($socId, $benefactorId)
    {
        //We saved in memory all contract related to this company : were it is a benefactor and/or a requester
        global $conf;

        $result = array();

        if (!empty($conf->contrat->enabled) && ($socId > 0 || (!empty($conf->companyrelationships->enabled) && $benefactorId > 0))) {
            $sql = "SELECT DISTINCT c.rowid";
            $sql .= " FROM " . MAIN_DB_PREFIX . "contrat as c";
            if (!empty($conf->companyrelationships->enabled) && $benefactorId > 0) {
                $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "contrat_extrafields as cf ON c.rowid = cf.fk_object";
            }
            $sql .= " WHERE c.entity IN (" . getEntity('contrat') . ")";
            if ($socId > 0 && !empty($conf->companyrelationships->enabled) && $benefactorId > 0) {
                $separator = 'OR';
            } else {
                $separator = '';
            }
            $sql .= ' AND (';
            if ($socId > 0) {
                $sql .= " c.fk_soc = " . $socId;
            }
            $sql .= $separator;
            if (!empty($conf->companyrelationships->enabled) && $benefactorId > 0) {
                $sql .= " cf.companyrelationships_fk_soc_benefactor = " . $benefactorId;
            }
            $sql .= ')';

            dol_syslog(__METHOD__, LOG_DEBUG);
            $resql = $this->db->query($sql);
            if ($resql) {
                if ($this->db->num_rows($resql) > 0) {
                    dol_include_once('/contrat/class/contrat.class.php');
                    while ($obj = $this->db->fetch_object($resql)) {
                        $contrat = self::$cache_contract_list[$obj->rowid];
                        if (!$contrat) {
                            $contrat = new Contrat($this->db);
                            $contrat->fetch($obj->rowid);
                            $contrat->fetchObjectLinked();
                            self::$cache_contract_list[$obj->rowid] = $contrat;
                        }
                        if($contrat->nbofservicesopened > 0 && $contrat->statut == 1){
                            $result[$obj->rowid] = $contrat;
                        }
                    }
                }
            } else {
                $msg_error = $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $msg_error, LOG_DEBUG);
                $this->errors[] = $msg_error;
            }
        }
        return $result;
    }

    /**
     *  load in cache equipements related to a socid
     *  return result according to selected param with value taken from existing cache or db if not available
     * Cache is filled with all parsed data into db, so only from new item
     *
     * @param   int $socId Id of the principal thirdparty
     * @return  Equipement[] List of contract
     */

    function fetch_all_equipement_for_these_company($socId)
    {
        //We saved in memory all equipement related to this company
        global $conf;

        $result = array();

        $sql = "SELECT e.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "equipement as e";
        $sql .= " LEFT JOIN  " . MAIN_DB_PREFIX . "equipement_extrafields as eef ON eef.fk_object = e.rowid";
        $sql .= " WHERE e.entity IN (" . getEntity('equipement') . ")";
        $sql .= " AND e.fk_soc_client = " . $socId;
        $sql .= " AND eef.machineclient = 1";
        $sql .= " AND e.fk_statut = 1";
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql) > 0) {
                dol_include_once('/equipement/class/equipement.class.php');
                while ($obj = $this->db->fetch_object($resql)) {
                    if (!self::$cache_equipement_list[$obj->rowid]) {
                        $equipement = new Equipement($this->db);
                        $equipement->fetch($obj->rowid);
                        $equipement->fetchObjectLinked();
                        $equipement->fetch_product();
                        self::$cache_equipement_list[$obj->rowid] = $equipement;
                    }
                    $result[$obj->rowid] = self::$cache_equipement_list[$obj->rowid];
                }
            }
        } else {
            $msg_error = $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $msg_error, LOG_DEBUG);
            $this->errors[] = $msg_error;
        }
        return $result;
    }

    /**
     *  Display equipement not under contract
     *
     * @param   Equipement[] containing an array of equipement to display
     * @return  string
     */

    public function display_equipements_without_contract($arrayOfEquipement, $textColor)
    {
        global $langs;
        $result = "";
        $result = '<h1 style="color:' . $textColor . '!important;text-align:center;font-size: 2em!important;">' . $langs->trans('SynergiesTechBannerTabEquipementListWithoutContract') . '</h1>';
        foreach ($arrayOfEquipement as $equipement) {
            $result .= '<p style="font-size: 1.5em!important;">' . self::display_equipement($equipement, $textColor) . '</p>';
        }
        return $result;
    }

    /**
     *  Display equipement under contract
     *
     * @param   Equipement[]
     * @return  string
     */

    public function display_equipements_with_contracts($arrayOfEquipement, $textColor)
    {
        global $langs;
        $result = '<h1 style="color:' . $textColor . '!important;text-align:center;font-size: 2em!important;">'. $langs->trans('SynergiesTechBannerTabEquipementListWithContract') . ' </h1>';
        foreach ($arrayOfEquipement as $equipement) {
            $result .= '<p style="color:' . $textColor . '!important;font-size: 1.5em!important;">' . self::display_equipement($equipement, $textColor) . ' : ' . $this->display_contracts_from_equipement($equipement, $textColor) . '</p>';
        }
        return $result;
    }

    /**
     *   Filter an array of contract to keep only active contract linked to equipement which is a machine and not a serialised product
     *
     * @param   Contrat[] $arrayOfContract
     * @return  Contrat[]
     */

    public static function filter_contract_without_equipement_for_these_company($arrayOfContract)
    {
        return array_filter($arrayOfContract, function ($value) {
            $test = empty($value->linkedObjectsIds) || empty($value->linkedObjectsIds['equipement']);
            if(!$test){
                //We may check that equipement is a machine equipement type
                foreach($value->linkedObjects['equipement'] as $equipement){
                    if(empty($equipement->array_options)){
                        $equipement->fetch_optionals();
                    }
                    if($equipement->array_options['options_machineclient'] != 1){
                        $test = true;
                        break;
                    }
                }
            }
            return $test;
        });
    }

    /**
     *   Helper static function to know if a contract is active
     *
     * @param   Contrat $contract
     * @return  boolean
     */

    public static function isContractActive($contract){
        return $contract->nbofservicesopened > 0 && $contract->statut == 1;
    }

     /**
     *   Helper function to know if a contract is active by id
     *
     * @param   int $contractId
     * @return  boolean
     */

    public function isContractActiveById($contractId){
        $contract = self::$cache_contract_list[$contractId];
        if(!$contract){
            dol_include_once('/contrat/class/contrat.class.php');
            $contract = new Contrat($this->db);
            $contract->fetch($contractId);
            $contract->fetchObjectLinked();
        }
        return self::isContractActive($contract);
    }


     /**
     *  Helper static function to filter an array of equipement and return only equipement linked to an active contract
     *
     * @param   Equipement[] $arrayOfEquipement
     * @return  Equipement[]
     */

    public static function filter_equipement_with_contract($arrayOfEquipement)
    {
        return array_filter($arrayOfEquipement, function ($value) {
            $hasEquipementNoLinkedContract = empty($value->linkedObjects) || empty($value->linkedObjects['contrat']);
            $hasEquipementAtLeastOneActiveContract = false;
            if(!$hasEquipementNoLinkedContract){
                foreach($value->linkedObjects['contrat'] as $contrat){
                    if(self::isContractActive($contrat)){
                    $hasEquipementAtLeastOneActiveContract = true;
                    break;
                    }
                }
            }
            return  !$hasEquipementNoLinkedContract && $hasEquipementAtLeastOneActiveContract;
        });
    }

     /**
     *  Helper static function to filter an array of equipement and return only equipement not linked to at least one active contract
     *
     * @param   Equipement[] $arrayOfEquipement
     * @return  Equipement[]
     */

    public static function filter_equipement_without_contract($arrayOfEquipement)
    {
        return array_filter($arrayOfEquipement, function ($value) {
            $hasEquipementNoLinkedContract = empty($value->linkedObjects) || empty($value->linkedObjects['contrat']);
            $hasEquipementAtLeastOneActiveContract = false;
            if(!$hasEquipementNoLinkedContract){
                foreach($value->linkedObjects['contrat'] as $contrat){
                    if(self::isContractActive($contrat)){
                    $hasEquipementAtLeastOneActiveContract = true;
                    break;
                    }
                }
            }
            return  $hasEquipementNoLinkedContract && !$hasEquipementAtLeastOneActiveContract;
        });
    }

     /**
     *  Static function to display active contracts linked to an equipement
     *
     * @param   Equipement[] $arrayOfEquipement
     * @param   string $textColor
     * @return  Equipement[]
     */

    public function display_contracts_from_equipement($equipement, $textColor)
    {
        $arrayOfContractIds = $equipement->linkedObjectsIds ? $equipement->linkedObjectsIds['contrat'] : array();
        $arrayOfContracts = array();
        foreach ($arrayOfContractIds as $id) {
            $contract = self::$cache_contract_list[$id];
            if(self::isContractActive($contract)){
                $arrayOfContracts[] = $contract;
            }
        }
        return $this->display_contracts($arrayOfContracts, $textColor);
    }


    /**
     *  Display contract in one line
     *
     * @param   Contract[] $arrayOfContracts
     * @param   string $textColor
     * @return  string
     */

    public function display_contracts($arrayOfContracts, $textColor)
    {
        $toPrint = array();
        if ($arrayOfContracts) {
            foreach ($arrayOfContracts as $contract) {
                $toPrint[] = $this->display_contract($contract, $textColor);
            }
        }
        return implode(' - ', $toPrint);
    }


    /**
     *  Display contract
     *
     * @param   Contract $contract
     * @param   string $textColor
     * @return  string
     */

    public function display_contract($contract, $textColor)
    {
        global $user;
        $this->load_cache_extrafields_contract();
        $result = "";

        if ($contract) {

            if(empty($contract->array_options)){
                $contract->fetch_optionals();
            }

            $result = '<a href="' . DOL_URL_ROOT . '/contrat/card.php?id=' . $contract->id . '" ';
            if (!$user->rights->contrat->lire) {
                $result .= 'onclick="return false"';
            }
            if ($textColor) {
                $result .= 'style="color:' . $textColor . ';"';
            }
            $result .= '> ' . self::$cache_extrafields_contract->showOutputField('formule', $contract->array_options['options_formule']) . " - " . $contract->ref . "</a> ";
        }
        return $result;
    }

    /**
     *  Display contract without equipement
     *
     * @param   Contract
     * @param   string $textColor
     * @return  string
     */

    public function display_contract_without_equipement($arrayOfContract, $textColor)
    {
        global $langs;
        return '<h1 style="color:' . $textColor . '!important;text-align:center;font-size: 2em!important;">' . $langs->trans('SynergiesTechBannerTabContractWithoutEquipement') . $this->display_contracts($arrayOfContract, $textColor) . '</h1>';
    }

    /**
     *  Load Contract extrafields label informations into cache
     *
     */
    public function load_cache_extrafields_contract()
    {
        if (!self::$cache_extrafields_contract) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            self::$cache_extrafields_contract = new ExtraFields($this->db);
            self::$cache_extrafields_contract->fetch_name_optionals_label('contrat');
        }
    }
    /**
     *  Display Equipement
     *
     * @param   Contract
     * @param   string $textColor
     * @return  string
     */

    public static function display_equipement($equipement, $textColor)
    {
        global $user;
        $result = "";
        if ($equipement) {
            $result .= $equipement->product->label . ' - ' . $equipement->ref;

            if ($user->rights->equipement->lire) {
                $result = '<a ' . 'style="color:' . $textColor . '!important;"' . ' href="' . DOL_URL_ROOT . '/custom/equipement/card.php?id=' . $equipement->id . '" >' . $result . '</a>';
            } else {
                $result = '<h1 ' . 'style="color:' . $textColor . '!important;">' . $result . '</h1>';
            }
        }
        return $result;
    }

    /**
     *  Display Text when NoContractAndEquipement found
     *
     * @param   string $textColor
     * @return  string
     */

    public static function displayNoContractAndNoEquipement($textColor)
    {
        global $langs;
        return '<h2 style="color:' . $textColor . '!important">' . $langs->trans('SynergiesTechBannerTabNoEqNoContract') . '</h2>';
    }

     /**
     *  Display Text about contract where thirdparty is only requester
     *
     * @param   string $socId
     * @param   string $numberOfContract
     * @param   string $textColor
     * @return  string
     */
    public static function displayContractAsRequesterOnly($socId, $numberOfContract, $textColor)
    {
        global $user, $langs;
        $result = '<h2 style="color:' . $textColor . '!important">';
        $result .= $langs->trans('SynergiesTechBannerTabContractAsRequesterOnly', $numberOfContract);

        if (!empty($user->rights->contrat->lire)) {
            $result .= ' : ';
            $result .= '<a href="' . DOL_URL_ROOT . '/contrat/list.php?socid=' . $socId . '">';
            $result .= 'Liste des contrats';
            $result .= '</a>';
        }

        $result .= '</h2>';
        return $result;
    }
     /**
     *  Display BannerTab containing equipement and contracts informations
     *
     * @param   string $socId
     * @return  string
     */
    public function bannerTab($socId)
    {
        $result = "";
        $listOfEquipementOfThisCustomer = $this->fetch_all_equipement_for_these_company($socId);
        $equipementUnderContract = self::filter_equipement_with_contract($listOfEquipementOfThisCustomer);
        $equipementWithoutContract = self::filter_equipement_without_contract($listOfEquipementOfThisCustomer);
        $listOfContractOfThisBenefactor = $this->fetch_all_contract_for_these_company(null, $socId);
        $listOfContractOfThisBenefactorWithoutEquipement = self::filter_contract_without_equipement_for_these_company($listOfContractOfThisBenefactor);
        $listOfCOntractAsRequester = $this->fetch_all_contract_for_these_company($socId, null);
        $listOfContractWhereThisSocIdIsOnlyRequesterAndNotBenefactor = array_diff_key(
            $listOfCOntractAsRequester,
            $listOfContractOfThisBenefactor
        );
        if (empty($listOfContractOfThisBenefactor) && empty($listOfContractWhereThisSocIdIsOnlyRequesterAndNotBenefactor)) {
            $backgroundColor = "red";
            $textColor = "white";
        } else if (!empty($equipementUnderContract) && empty($equipementWithoutContract)) {
            $backgroundColor = "green";
            $textColor = "white";
        } else {
            $backgroundColor = "orange";
            $textColor = "black";
        }

        $result .= '<table class="border" width="100%">';
        $result .= '<tr style="background-color :' . $backgroundColor . ';text-align:center">';
        $result .= '<td>';

        if (!empty($equipementUnderContract)) {
            $result .= $this->display_equipements_with_contracts($equipementUnderContract, $textColor);
        }

        if (!empty($equipementWithoutContract)) {
            $result .= $this->display_equipements_without_contract($equipementWithoutContract, $textColor);
        }
        if (!empty($listOfContractOfThisBenefactorWithoutEquipement)) {
            $result .= $this->display_contract_without_equipement($listOfContractOfThisBenefactorWithoutEquipement, $textColor);
        }

        if (!empty($listOfContractWhereThisSocIdIsOnlyRequesterAndNotBenefactor)) {
            $result .= $this->displayContractAsRequesterOnly($socId, count($listOfContractWhereThisSocIdIsOnlyRequesterAndNotBenefactor), $textColor);
        }

        if (empty($listOfContractOfThisBenefactor) && empty($listOfContractWhereThisSocIdIsOnlyRequesterAndNotBenefactor) && empty($listOfEquipementOfThisCustomer)) {
            $result .= self::displayNoContractAndNoEquipement($textColor);
        }

        $result .= '</td>';
        $result .= '</tr>';
        $result .= '</table>';
        return $result;
    }
}
