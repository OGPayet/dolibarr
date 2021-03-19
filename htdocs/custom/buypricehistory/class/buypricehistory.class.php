<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/buypricehistory.class.php
 * \ingroup     buypricehistory
 * \brief       This file is a CRUD class file for BuyPriceHistory (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/product/class/productfournisseurprice.class.php';

/**
 * Class for BuyPriceHistory
 */
class BuyPriceHistory extends CommonObject
{
    /**
     * @var string ID of module.
     */
    public $module = 'buypricehistory';

    /**
     * @var string ID to identify managed object.
     */
    public $element = 'buypricehistory';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'buypricehistory_buypricehistory';

    /**
     * @var int  Does this object support multicompany module ?
     * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string String with name of icon for buypricehistory. Must be the part after the 'object_' into object_buypricehistory.png
     */
    public $picto = 'buypricehistory@buypricehistory';
    /**
     *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
     *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
     *  'label' the translation key.
     *  'picto' is code of a picto to show before value in forms
     *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM)
     *  'position' is the sort order of field.
     *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
     *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
     *  'noteditable' says if field is not editable (1 or 0)
     *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
     *  'index' if we want an index in database.
     *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
     *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
     *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'maxwidth200', 'wordbreak', 'tdoverflowmax200'
     *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
     *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
     *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
     *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
     *  'comment' is not used. You can store here any text of your choice. It is not used by application.
     *
     *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
     */

    // BEGIN MODULEBUILDER PROPERTIES
    /**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public $fields = array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>0,),
        'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>15, 'notnull'=>1, 'visible'=>-2, 'default'=>'1', 'index'=>1,),
        'datec' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>20, 'notnull'=>0, 'visible'=>-1,),
        'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>25, 'notnull'=>1, 'visible'=>-1,),
        'fk_product' => array('type'=>'integer:Product:product/class/product.class.php:1', 'label'=>'Fkproduct', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>-1,),
        'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'ThirdParty', 'enabled'=>'1', 'position'=>35, 'notnull'=>0, 'visible'=>-1,),
        'ref_fourn' => array('type'=>'varchar(255)', 'label'=>'Reffourn', 'enabled'=>'1', 'position'=>40, 'notnull'=>0, 'visible'=>-1,),
        'desc_fourn' => array('type'=>'text', 'label'=>'Descfourn', 'enabled'=>'1', 'position'=>45, 'notnull'=>0, 'visible'=>-1,),
        'fk_availability' => array('type'=>'integer', 'label'=>'Fkavailability', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>-1,),
        'price' => array('type'=>'double(24,8)', 'label'=>'Price', 'enabled'=>'1', 'position'=>55, 'notnull'=>0, 'visible'=>-1,),
        'quantity' => array('type'=>'double', 'label'=>'Quantity', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>-1,),
        'remise_percent' => array('type'=>'double', 'label'=>'Remisepercent', 'enabled'=>'1', 'position'=>65, 'notnull'=>1, 'visible'=>-1,),
        'remise' => array('type'=>'double', 'label'=>'Remise', 'enabled'=>'1', 'position'=>70, 'notnull'=>1, 'visible'=>-1,),
        'unitprice' => array('type'=>'double(24,8)', 'label'=>'Unitprice', 'enabled'=>'1', 'position'=>75, 'notnull'=>0, 'visible'=>-1,),
        'charges' => array('type'=>'double(24,8)', 'label'=>'Charges', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>-1,),
        'default_vat_code' => array('type'=>'varchar(10)', 'label'=>'Defaultvatcode', 'enabled'=>'1', 'position'=>85, 'notnull'=>0, 'visible'=>-1,),
        'tva_tx' => array('type'=>'double(6,3)', 'label'=>'Tvatx', 'enabled'=>'1', 'position'=>90, 'notnull'=>1, 'visible'=>-1,),
        'info_bits' => array('type'=>'integer', 'label'=>'Infobits', 'enabled'=>'1', 'position'=>95, 'notnull'=>1, 'visible'=>-1,),
        'fk_user' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fkuser', 'enabled'=>'1', 'position'=>100, 'notnull'=>0, 'visible'=>-1,),
        'fk_supplier_price_expression' => array('type'=>'integer', 'label'=>'Fksupplierpriceexpression', 'enabled'=>'1', 'position'=>105, 'notnull'=>0, 'visible'=>-1,),
        'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>900, 'notnull'=>0, 'visible'=>-2,),
        'delivery_time_days' => array('type'=>'integer', 'label'=>'Deliverytimedays', 'enabled'=>'1', 'position'=>115, 'notnull'=>0, 'visible'=>-1,),
        'supplier_reputation' => array('type'=>'varchar(10)', 'label'=>'Supplierreputation', 'enabled'=>'1', 'position'=>120, 'notnull'=>0, 'visible'=>-1,),
        'fk_multicurrency' => array('type'=>'integer', 'label'=>'Fkmulticurrency', 'enabled'=>'1', 'position'=>125, 'notnull'=>0, 'visible'=>-1,),
        'multicurrency_code' => array('type'=>'varchar(255)', 'label'=>'Multicurrencycode', 'enabled'=>'1', 'position'=>130, 'notnull'=>0, 'visible'=>-1,),
        'multicurrency_tx' => array('type'=>'double(24,8)', 'label'=>'Multicurrencytx', 'enabled'=>'1', 'position'=>135, 'notnull'=>0, 'visible'=>-1,),
        'multicurrency_price' => array('type'=>'double(24,8)', 'label'=>'Multicurrencyprice', 'enabled'=>'1', 'position'=>140, 'notnull'=>0, 'visible'=>-1,),
        'multicurrency_unitprice' => array('type'=>'double(24,8)', 'label'=>'Multicurrencyunitprice', 'enabled'=>'1', 'position'=>145, 'notnull'=>0, 'visible'=>-1,),
        'localtax1_tx' => array('type'=>'double(6,3)', 'label'=>'Localtax1tx', 'enabled'=>'1', 'position'=>150, 'notnull'=>0, 'visible'=>-1,),
        'localtax1_type' => array('type'=>'varchar(10)', 'label'=>'Localtax1type', 'enabled'=>'1', 'position'=>155, 'notnull'=>1, 'visible'=>-1,),
        'localtax2_tx' => array('type'=>'double(6,3)', 'label'=>'Localtax2tx', 'enabled'=>'1', 'position'=>160, 'notnull'=>0, 'visible'=>-1,),
        'localtax2_type' => array('type'=>'varchar(10)', 'label'=>'Localtax2type', 'enabled'=>'1', 'position'=>165, 'notnull'=>1, 'visible'=>-1,),
        'barcode' => array('type'=>'varchar(180)', 'label'=>'Barcode', 'enabled'=>'1', 'position'=>170, 'notnull'=>0, 'visible'=>-1,),
        'fk_barcode_type' => array('type'=>'integer', 'label'=>'Fkbarcodetype', 'enabled'=>'1', 'position'=>175, 'notnull'=>0, 'visible'=>-1,),
        'packaging' => array('type'=>'varchar(64)', 'label'=>'Packaging', 'enabled'=>'1', 'position'=>180, 'notnull'=>0, 'visible'=>-1,),
		'fk_object' => array('type'=>'integer:ProductFournisseurPrice:product/class/productfournisseurprice.class.php', 'label'=>'Linked Supplier price', 'enabled'=>'1', 'position'=>10, 'notnull'=>0, 'visible'=>0),
		'begin_date' => array('type'=>'datetime', 'label'=>'BuyPriceHistoryStartDate', 'enabled'=>'1', 'position'=>20, 'notnull'=>0, 'visible'=>-1,),
        'end_date' => array('type'=>'datetime', 'label'=>'BuyPriceHistoryEndDate', 'enabled'=>'1', 'position'=>25, 'notnull'=>0, 'visible'=>-1,),
        'original_datec' => array('type'=>'datetime', 'label'=>'OriginalDateCreation', 'enabled'=>'1', 'position'=>20, 'notnull'=>0, 'visible'=>-1,),
        'original_tms' => array('type'=>'timestamp', 'label'=>'OriginalDateModification', 'enabled'=>'1', 'position'=>25, 'notnull'=>1, 'visible'=>-1,),
        'original_fk_user' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'OriginalFkuser', 'enabled'=>'1', 'position'=>100, 'notnull'=>0, 'visible'=>-1,),
	);

    public $rowid;
    public $entity;
    public $datec;
    public $original_datec;
    public $begin_date;
    public $tms;
    public $end_date;
    public $original_tms;
    public $fk_product;
    public $fk_soc;
    public $ref_fourn;
    public $desc_fourn;
    public $fk_availability;
    public $price;
    public $quantity;
    public $remise_percent;
    public $remise;
    public $unitprice;
    public $charges;
    public $default_vat_code;
    public $tva_tx;
    public $info_bits;
    public $fk_user;
    public $original_fk_user;
    public $fk_supplier_price_expression;
    public $import_key;
    public $delivery_time_days;
    public $supplier_reputation;
    public $fk_multicurrency;
    public $multicurrency_code;
    public $multicurrency_tx;
    public $multicurrency_price;
    public $multicurrency_unitprice;
    public $localtax1_tx;
    public $localtax1_type;
    public $localtax2_tx;
    public $localtax2_type;
    public $barcode;
    public $fk_barcode_type;
    public $packaging;
    // END MODULEBUILDER PROPERTIES

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        global $conf, $langs;

        $this->db = $db;

        if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
            $this->fields['rowid']['visible'] = 0;
        }
        if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
            $this->fields['entity']['enabled'] = 0;
        }

        // Unset fields that are disabled
        foreach ($this->fields as $key => $val) {
            if (isset($val['enabled']) && empty($val['enabled'])) {
                unset($this->fields[$key]);
            }
        }
    }

    /**
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, Id of created object if OK
     */
    public function create($user, $notrigger = 0)
    {
        return $this->createCommon($user, $notrigger);
    }

    /**
     * Load list of objects in memory from the database.
     *
     * @param  string      $sortorder    Sort Order
     * @param  string      $sortfield    Sort field
     * @param  int         $limit        limit
     * @param  int         $offset       Offset
     * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
     * @param  string      $filtermode   Filter mode (AND or OR)
     * @return BuyPriceHistory[]|int                 int <0 if KO, array of pages if OK
     */
    public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
    {
        global $conf;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $records = array();

        $sql = 'SELECT ';
        $sql .= $this->getFieldList();
        $sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
        if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
            $sql .= ' WHERE t.entity IN ('.getEntity($this->table_element).')';
        } else {
            $sql .= ' WHERE 1 = 1';
        }
        // Manage filter
        $sqlwhere = array();
        if (count($filter) > 0) {
            foreach ($filter as $key => $value) {
                if ($key == 't.rowid') {
                    $sqlwhere[] = $key.'='.$value;
                } elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
                    $sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
                } elseif ($key == 'customsql') {
                    $sqlwhere[] = $value;
                } elseif (strpos($value, '%') === false) {
                    $sqlwhere[] = $key.' IN ('.$this->db->sanitize($this->db->escape($value)).')';
                } else {
                    $sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
                }
            }
        }
        if (count($sqlwhere) > 0) {
            $sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
        }

        if (!empty($sortfield)) {
            $sql .= $this->db->order($sortfield, $sortorder);
        }
        if (!empty($limit)) {
            $sql .= ' '.$this->db->plimit($limit, $offset);
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < ($limit ? min($limit, $num) : $num)) {
                $obj = $this->db->fetch_object($resql);

                $record = new self($this->db);
                $record->setVarsFromFetchObj($obj);

                $records[$record->id] = $record;

                $i++;
            }
            $this->db->free($resql);

            return $records;
        } else {
            $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

            return -1;
        }
    }

    /**
     * Delete object in database
     *
     * @param User $user       User that deletes
     * @param bool $notrigger  false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger = false)
    {
        return $this->deleteCommon($user, $notrigger);
    }

    /**
     * Log a supplier price not anymore valid from instance
     * @param ProductFournisseurPrice $productPrice
     * @return bool
     */
    public function logOldPriceFromInstance($productPrice)
    {
        global $user;
        $errors = array();
        if ($productPrice->id) {
			$payload = new self($this->db);
            $instancesToUpdate = $payload->fetchAll('DESC', 'begin_date', 0, 0, array('fk_object'=>$productPrice->id, 'customsql' => 'end_date IS NULL'), 'AND');
			$errors = array_merge($errors, $payload->errors);
        }
        if (is_array($instancesToUpdate) && !empty($instancesToUpdate)) {
			$instancesToUpdate = array_reverse(array_values($instancesToUpdate));
            $mostRecentPriceLogged = array_pop($instancesToUpdate);
            if ($mostRecentPriceLogged) {
                $mostRecentPriceLogged->end_date = self::getPriceValidityStartDate($productPrice);
                $mostRecentPriceLogged->updateCommon($user);
                $errors = array_merge($errors, $mostRecentPriceLogged->errors);
            }
            $date_start = $mostRecentPriceLogged->date_start;
            foreach ($instancesToUpdate as $instance) {
                $instance->end_date = $date_start;
                $date_start = $instance->start_date;
                $instance->updateCommon($user);
                $errors = array_merge($errors, $instance->errors);
            }
        }
        $this->errors = array_merge($this->errors, $errors);
        return empty($errors);
    }
    /**
     * Log a supplier price thanks to a ProductFournisseurPrice instance
     * @param ProductFournisseurPrice $productPrice
     * @return BuyPriceHistory|null instance of the history created
     */
    public function logNewPriceFromInstance($productPrice)
    {
        global $user;
        $result = null;
        if ($productPrice->id) {
            //We set old log instance price end date
            if ($this->logOldPriceFromInstance($productPrice)) {
//We create a new log instance with this price
                $productPriceHistory = new self($this->db);
                $fieldToBulkUpdate = array_keys($productPrice->fields);
                $fieldToBulkUpdate = array_diff($fieldToBulkUpdate, array('datec', 'tms', 'fk_user'));
                foreach ($fieldToBulkUpdate as $field) {
                    $productPriceHistory->$field = $productPrice->$field;
                }
                $productPriceHistory->original_fk_user = $productPrice->fk_user;
                $productPriceHistory->original_tms = $productPrice->tms;
                $productPriceHistory->original_datec = $productPrice->date_creation;
                $productPriceHistory->begin_date = self::getPriceValidityStartDate($productPrice);
                $productPriceHistory->fk_object = $productPrice->id;
                if (!$productPrice->array_options) {
                    $productPrice->fetch_optionals();
                }
                $productPriceHistory->array_options = $productPrice->array_options;
                $result = $productPriceHistory->create($user) > 0 ? $productPriceHistory : null;
                $this->errors = array_merge($this->errors, $productPriceHistory->errors);
            }
        }
        return $result;
    }


    /**
     * Function to get supplier price which have not been saved into history
     * @param int $productId limit research of price to archive to a product id
     * @return int[]|null array of supplier price row id not saved
     */
    public function getSupplierPriceToArchive($productId = null)
    {
        $result = null;
        $sql = 'SELECT product_fournisseur_price.rowid as rowid ';
        $sql .= " FROM " . MAIN_DB_PREFIX . "product_fournisseur_price as product_fournisseur_price ";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX ."buypricehistory_buypricehistory as buy_price_history ";
        $sql .= " ON (product_fournisseur_price.rowid = buy_price_history.fk_object) ";
        $sql .= " WHERE ( buy_price_history.fk_object IS NULL OR product_fournisseur_price.tms != buy_price_history.original_tms ) ";
        $sql .= " AND product_fournisseur_price.entity IN (" . getEntity('productsupplierprice') . ")";
        if ($productId) {
            $sql .= 'AND product_fournisseur_price.fk_product = ' . $productId;
        }
		$sql .= ' GROUP BY product_fournisseur_price.rowid ';
        $resql = $this->db->query($sql);
        if ($resql) {
            $result = array();
            while ($obj = $this->db->fetch_object($resql)) {
                $result[] = $obj->rowid;
            }
        } else {
            $this->errors[] = $this->db->error();
        }
        return $result;
    }

    /**
     * Function to archive all data according to filter
     * @param int $productId limit research of price to archive to a product id
     * @return BuyPriceHistory[] array of history object created
     */
    public function archiveAllPrice($productId = null)
    {
        $supplierPriceIdToArchive = $this->getSupplierPriceToArchive($productId);
        $supplierPriceInstanceToArchive = array();
        $result = array();
        if (!empty($supplierPriceIdToArchive)) {
            $productFournisseurPrice = new ProductFournisseurPrice($this->db);
            $supplierPriceInstanceToArchive = $productFournisseurPrice->fetchAll('', '', 0, 0, array('rowid'=>implode(',', $supplierPriceIdToArchive)));
            $this->errors = array_merge($this->errors, $productFournisseurPrice->errors);
        }
        if (is_array($supplierPriceInstanceToArchive)) {
            foreach ($supplierPriceInstanceToArchive as $supplierPrice) {
                $payload = $this->logNewPriceFromInstance($supplierPrice);
                if ($payload) {
                    $result[] = $payload;
                }
            }
        }
        return $result;
    }

    /**
     * Function to get starting price valid date from ProductFournisseurPrice instance
     * @param ProductFournisseurPrice $productPrice
     * @return Date|null
     */
    public static function getPriceValidityStartDate($productPrice)
    {
        return !empty($productPrice->tms) ? $productPrice->tms : $productPrice->date_creation;
    }
}
