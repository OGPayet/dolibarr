<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class to manage products or services
 */
class Ouvrage extends CommonObject
{
    public $element = 'ouvrage';
    public $table_element = 'works';
    //public $fk_element='fk_product';
    //protected $childtables=array('works_det');    // To test if we can delete object
    protected $isnolinkedbythird = 1;     // No field fk_soc
    public $ismultientitymanaged = 1;    // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CANCELED = 9;
    // BEGIN MODULEBUILDER PROPERTIES
    /**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public $fields = array(
        'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -1, 'position' => 1, 'notnull' => 1, 'index' => 1, 'comment' => "Id",),
        'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'visible' => 1, 'position' => 10, 'notnull' => 1, 'index' => 1, 'searchall' => 1, 'comment' => "Reference of object",),
        'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'visible' => 1, 'position' => 30, 'notnull' => 1, 'searchall' => 1, 'help' => "",),
        'fk_tva' => array('type' => 'double', 'label' => 'VAT', 'enabled' => 1, 'visible' => 1, 'position' => 30, 'notnull' => -1,/* 'arrayofkeyval'=> array( to complete with your main values) */),
        'description' => array('type' => 'html', 'label' => 'Description', 'enabled' => 1, 'visible' => 1, 'position' => 60, 'notnull' => -1,),
        'fk_unit' => array('type' => 'integer', 'label' => 'Unit', 'enabled' => 1, 'visible' => 1, 'position' => 1000, 'notnull' => -1,/* 'arrayofkeyval'=> array( to complete with your main values) */),
        'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => -2, 'position' => 61, 'notnull' => -1,),
        'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => -2, 'position' => 62, 'notnull' => -1,),
        'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -2, 'position' => 500, 'notnull' => 1,),
        'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -2, 'position' => 501, 'notnull' => -1,),
        'fk_user_creat' => array('type' => 'integer', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => -2, 'position' => 510, 'notnull' => 1,),
        'fk_user_modif' => array('type' => 'integer', 'label' => 'UserModif', 'enabled' => 1, 'visible' => -2, 'position' => 511, 'notnull' => -1,),
        'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 1000, 'notnull' => -1,),
        'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => -2, 'position' => 1000, 'notnull' => -1,),
        //FIX ME about convert propal in order,...loss the special product type
        //'fk_product' => array('type'=>'integer:Product:product/class/product.class.php', 'label'=>'Product', 'enabled'=>1, 'visible'=>-1, 'position'=>1000, 'notnull'=>-1, 'index'=>1, 'help'=>"Usediflinktoproduct",),
    );

    /**
     * {@inheritdoc}
     */
    protected $table_ref_field = 'ref';

    public $rowid;
    public $ref;
    public $label;
    public $fk_product;
    public $description;
    public $fk_unit;
    public $note_public;
    public $note_private;
    public $date_creation;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;
    public $import_key;
    public $entity;
    public $status;
    public $fk_tva;
    public $picto = 'ouvrage_work@ouvrage';
    /**
     * Ouvrage dets
     * @var array
     * @example array(
     *  array('product' => product_id, 'qty' => 1, 'order' => 1),
     *  array('product' => product_id, 'qty' => 1, 'order' => 2),
     *  array('product' => product_id, 'qty' => 1, 'order' => 3),
     *  ...
     * )
     */
    var $dets;

    /**
     *  Constructor
     *
     * @param DoliDB $db Database handler
     */
    function __construct($db)
    {
        global $conf, $langs;

        $this->db = $db;

        if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
        //if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled']=0;

        // Unset fields that are disabled
        foreach ($this->fields as $key => $val) {
            if (isset($val['enabled']) && empty($val['enabled'])) {
                unset($this->fields[$key]);
            }
        }

        // Translate some data of arrayofkeyval
        foreach ($this->fields as $key => $val) {
            if (is_array($val['arrayofkeyval'])) {
                foreach ($val['arrayofkeyval'] as $key2 => $val2) {
                    $this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
                }
            }
        }
        $this->fk_product = "";
        $this->dets = array();
    }

    /**
     *    Check that ref and label are ok
     *
     * @return     int         >1 if OK, <=0 if KO
     */
    function check()
    {
        $this->ref = dol_sanitizeFileName(stripslashes($this->ref));

        $err = 0;
        if (dol_strlen(trim($this->ref)) == 0)
            $err++;

        if (dol_strlen(trim($this->label)) == 0)
            $err++;

        if ($err > 0) {
            return 0;
        } else {
            return 1;
        }
    }

    function create(User $user, $notrigger = false)
    {

        global $conf, $langs;
        $error = 0;

        $id = $this->createCommon($user, $notrigger);
        if ($id > 0) {
            //$this->id = $id;

            $sqlDet = '';
            foreach ($this->dets as $det) {
                $sqlDet = "INSERT INTO " . MAIN_DB_PREFIX . "works_det (fk_works, fk_product, `order`, qty)";
                $sqlDet .= " VALUES (" . $id . ',' . $det['product'] . ',' . $det['order'] . ',' . $det['qty'] . ');';
                $result = $this->db->query($sqlDet);
            }

            if (!$result) {
                $error++;
                $this->error = $this->db->lasterror();
            }
            return $id;
        }
        return 0;
    }

    /**
     *    Insert ouvrage into database
     *
     * @param User $user User making insert
     * @param int $notrigger Disable triggers
     * @return int                        Id of product/service if OK, < 0 if KO
     */
    function create2($user, $notrigger = 0)
    {
        global $conf, $langs;


        $error = 0;

        // Clean parameters
        $this->ref = dol_string_nospecial(trim($this->ref));
        $this->label = trim($this->label);


        // Check parameters
        if (empty($this->label)) {
            $this->error = 'ErrorMandatoryParametersNotProvided';
            return -1;
        }

        if (empty($this->ref)) {
            $this->error = 'ErrorMandatoryParametersNotProvided';
            return -2;
        }

        dol_syslog(get_class($this) . "::create ref=" . $this->ref . " price=" . $this->price . " price_ttc=" . $this->price_ttc . " tva_tx=" . $this->tva_tx . " price_base_type=" . $this->price_base_type, LOG_DEBUG);

        $now = dol_now();

        $this->db->begin();

        // Check more parameters
        // If error, this->errors[] is filled
        $result = $this->verify();

        if ($result >= 0) {
            $sql = "SELECT count(*) as nb";
            $sql .= " FROM " . MAIN_DB_PREFIX . "works";
            $sql .= " WHERE entity IN (" . getEntity('ouvrage', 1) . ")";
            $sql .= " AND ref = '" . $this->ref . "'";

            $result = $this->db->query($sql);
            if ($result) {
                $obj = $this->db->fetch_object($result);
                if ($obj->nb == 0) {
                    // Ouvrage non deja existant
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "works (";
                    $sql .= "`entity`";
                    $sql .= ", ref";
                    $sql .= ", label";
                    $sql .= ", description";
                    if (!empty($this->tva))
                        $sql .= ", fk_tva";
                    if (!empty($this->fk_unit))
                        $sql .= ", fk_unit";
                    if (!empty($this->fk_product))
                        $sql .= ", fk_product";
                    $sql .= ") VALUES (";
                    $sql .= $conf->entity;
                    $sql .= ", '" . $this->db->escape($this->ref) . "'";
                    $sql .= ", " . (!empty($this->label) ? "'" . $this->db->escape($this->label) . "'" : "null");
                    $sql .= ", " . (!empty($this->description) ? "'" . $this->db->escape($this->description) . "'" : "null");
                    if (!empty($this->tva))
                        $sql .= ", " . $this->tva;
                    if (!empty($this->fk_unit))
                        $sql .= ", " . $this->fk_unit;
                    if (!empty($this->fk_product))
                        $sql .= ", " . $this->fk_product;
                    $sql .= ")";

                    dol_syslog(get_class($this) . "::Create", LOG_DEBUG);
                    $result = $this->db->query($sql);

                    if ($result) {
                        $id = $this->db->last_insert_id(MAIN_DB_PREFIX . "product");

                        if ($id > 0) {
                            $this->id = $id;

                            $sqlDet = '';
                            foreach ($this->dets as $det) {
                                $sqlDet = "INSERT INTO " . MAIN_DB_PREFIX . "works_det (fk_works, fk_product, `order`, qty)";
                                $sqlDet .= " VALUES (" . $this->id . ',' . $det['product'] . ',' . $det['order'] . ',' . $det['qty'] . ');';
                                $result = $this->db->query($sqlDet);
                            }

                            if (!$result) {
                                $error++;
                                $this->error = $this->db->lasterror();
                            }
                        } else {
                            $error++;
                            $this->error = 'ErrorFailedToGetInsertedId';
                        }
                    } else {
                        $error++;
                        $this->error = $this->db->lasterror();
                    }
                } else {
                    // Product already exists with this ref
                    $langs->load("ouvrage@ouvrage");
                    $error++;
                    $this->error = $conf->global->OUVRAGE_TYPE . "ErrorOuvrageAlreadyExists";
                }
            } else {
                $error++;
                $this->error = $this->db->lasterror();
            }

            if (!$error) {
                $this->db->commit();
                return $this->id;
            } else {
                $this->db->rollback();
                return -$error;
            }

        } else {
            $this->db->rollback();
            dol_syslog(get_class($this) . "::Create fails verify " . join(',', $this->errors), LOG_WARNING);
            return -3;
        }
    }

    function verify()
    {
        $this->errors = array();

        $result = 0;
        $this->ref = trim($this->ref);

        if (!$this->ref) {
            $this->errors[] = 'ErrorBadRef';
            $result = -2;
        }

        return $result;
    }

    function addProduct($productid, $qty, $order)
    {
        $this->dets[] = array('product' => (int)$productid, 'qty' => $qty, 'order' => (int)$order);
    }

    function fetch($id, $ref = "")
    {
        global $user, $conf;
        $res = $this->fetchCommon($id, $ref);
        $sqlDet = "SELECT * FROM " . MAIN_DB_PREFIX . "works_det WHERE fk_works = " . $this->id . " ORDER BY `order` ASC";

        $resqlDet = $this->db->query($sqlDet);

        if ($resqlDet) {
            if ($this->db->num_rows($resqlDet) > 0) {
                $this->dets = array();
                while ($result = $this->db->fetch_array($resqlDet)) {
                    $this->dets[] = array(
                        'product' => $result['fk_product'],
                        'qty' => $result['qty'],
                        'order' => $result['order']
                    );
                }
            }
        }

        return $res;
    }

    function delete($id)
    {

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "works WHERE rowid = " . $id;

        dol_syslog(get_class($this) . '::delete', LOG_DEBUG);
        $result = $this->db->query($sql);
        if (!$result) {
            $error++;
            $this->errors[] = $this->db->lasterror();

            return 0;
        }

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "works_det WHERE fk_works = " . $id;
        $result = $this->db->query($sql);
        if (!$result) {
            $error++;
            $this->errors[] = $this->db->lasterror();

            return 0;
        }


        return 1;
    }

    public function update($id, $user, $notrigger = false, $action = 'update')
    {
        global $langs, $conf, $hookmanager;

        $error = 0;

        $this->updateCommon($user, 1);
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "works_det WHERE fk_works = " . $id;
        $result = $this->db->query($sql);
        if (!$result) {
            $error++;

            $this->errors[] = $this->db->lasterror();

            return 0;
        }

        $sqlDet = '';
        foreach ($this->dets as $det) {
            $sqlDet = "INSERT INTO " . MAIN_DB_PREFIX . "works_det (fk_works, fk_product, `order`, qty)";
            $sqlDet .= " VALUES (" . $this->id . ',' . $det['product'] . ',' . $det['order'] . ',' . $det['qty'] . ');';
            $result = $this->db->query($sqlDet);
        }

        if (!$result) {
            $error++;
            $this->error = $this->db->lasterror();

            return 0;
        }


        return 1;
    }

    public function createFromClone($ref)
    {
        global $user;
        $this->id = 0;
        $this->ref = $ref;

        // Create clone
        $result = $this->create($user);
        if ($result < 0) $error++;

        if (!$error) {
            $this->db->commit();
            return $this->id;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    public function getPrice($prod, $object)
    {

        global $conf, $db;
        if ($prod->status == 1) {
            $object->fetch_thirdparty();
            $pu_ht = $prod->price;
            // If price per segment
            if (!empty($conf->global->PRODUIT_MULTIPRICES) && $object->thirdparty->price_level) {
                $pu_ht = $prod->multiprices[$object->thirdparty->price_level];
                $pu_ttc = $prod->multiprices_ttc[$object->thirdparty->price_level];
                $price_min = $prod->multiprices_min[$object->thirdparty->price_level];
                $price_base_type = $prod->multiprices_base_type[$object->thirdparty->price_level];
                if (!empty($conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL))  // using this option is a bug. kept for backward compatibility
                {
                    if (isset($prod->multiprices_tva_tx[$object->thirdparty->price_level])) $tva_tx = $prod->multiprices_tva_tx[$object->thirdparty->price_level];
                    if (isset($prod->multiprices_recuperableonly[$object->thirdparty->price_level])) $tva_npr = $prod->multiprices_recuperableonly[$object->thirdparty->price_level];
                }
            } // If price per customer
            elseif (!empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
                require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';

                $prodcustprice = new Productcustomerprice($db);

                $filter = array('t.fk_product' => $prod->id, 't.fk_soc' => $object->thirdparty->id);

                $result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
                if ($result) {
                    // If there is some prices specific to the customer
                    if (count($prodcustprice->lines) > 0) {
                        $pu_ht = price($prodcustprice->lines[0]->price);
                        $pu_ttc = price($prodcustprice->lines[0]->price_ttc);
                        $price_base_type = $prodcustprice->lines[0]->price_base_type;
                        $tva_tx = ($prodcustprice->lines[0]->default_vat_code ? $prodcustprice->lines[0]->tva_tx . ' (' . $prodcustprice->lines[0]->default_vat_code . ' )' : $prodcustprice->lines[0]->tva_tx);
                        if ($prodcustprice->lines[0]->default_vat_code && !preg_match('/\(.*\)/', $tva_tx)) $tva_tx .= ' (' . $prodcustprice->lines[0]->default_vat_code . ')';
                        $tva_npr = $prodcustprice->lines[0]->recuperableonly;
                        if (empty($tva_tx)) $tva_npr = 0;
                    }
                }
            } // If price per quantity
            elseif (!empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY)) {
                if ($prod->prices_by_qty[0])    // yes, this product has some prices per quantity
                {
                    // Search the correct price into loaded array product_price_by_qty using id of array retrieved into POST['pqp'].
                    $pqp = GETPOST('pbq', 'int');

                    // Search price into product_price_by_qty from $prod->id
                    foreach ($prod->prices_by_qty_list[0] as $priceforthequantityarray) {
                        if ($priceforthequantityarray['rowid'] != $pqp) continue;
                        // We found the price
                        if ($priceforthequantityarray['price_base_type'] == 'HT') {
                            $pu_ht = $priceforthequantityarray['unitprice'];
                        } else {
                            $pu_ttc = $priceforthequantityarray['unitprice'];
                        }
                        // Note: the remise_percent or price by qty is used to set data on form, so we will use value from POST.
                        break;
                    }
                }
            } // If price per quantity and customer
            elseif (!empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES)) {
                if ($prod->prices_by_qty[$object->thirdparty->price_level]) // yes, this product has some prices per quantity
                {
                    // Search the correct price into loaded array product_price_by_qty using id of array retrieved into POST['pqp'].
                    $pqp = GETPOST('pbq', 'int');

                    // Search price into product_price_by_qty from $prod->id
                    foreach ($prod->prices_by_qty_list[$object->thirdparty->price_level] as $priceforthequantityarray) {
                        if ($priceforthequantityarray['rowid'] != $pqp) continue;
                        // We found the price
                        if ($priceforthequantityarray['price_base_type'] == 'HT') {
                            $pu_ht = $priceforthequantityarray['unitprice'];
                        } else {
                            $pu_ttc = $priceforthequantityarray['unitprice'];
                        }
                        // Note: the remise_percent or price by qty is used to set data on form, so we will use value from POST.
                        break;
                    }
                }
            }
        }
        return $pu_ht;
    }


    /**
     *  Return a link to the object card (with optionaly the picto)
     *
     * @param int $withpicto Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
     * @param string $option On what the link point to ('nolink', ...)
     * @param int $notooltip 1=Disable tooltip
     * @param string $morecss Add more css on link
     * @param int $save_lastsearch_value -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     * @return    string                                String with URL
     */
    function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
    {
        global $db, $conf, $langs, $hookmanager;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;

        if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1;   // Force disable tooltips

        $result = '';

        $label = '<u>' . $langs->trans("ouvrage") . '</u>';
        $label .= '<br>';
        $label .= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

        $url = dol_buildpath('/ouvrage/card.php', 1) . '?id=' . $this->id;

        if ($option != 'nolink') {
            // Add param to save lastsearch_values or not
            $add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
            if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
            if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
        }

        $linkclose = '';
        if (empty($notooltip)) {
            if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
                $label = $langs->trans("ouvrage");
                $linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
            }

            $linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
            $linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';

            /*
             $hookmanager->initHooks(array('vehicledao'));
             $parameters=array('id'=>$this->id);
             $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
             if ($reshook > 0) $linkclose = $hookmanager->resPrint;
             */
        } else $linkclose = ($morecss ? ' class="' . $morecss . '"' : '');

        $linkstart = '<a href="' . $url . '"';
        $linkstart .= $linkclose . '>';
        $linkend = '</a>';

        $result .= $linkstart;
        if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
        if ($withpicto != 2) $result .= $this->ref;
        $result .= $linkend;
        //if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

        global $action, $hookmanager;
        $hookmanager->initHooks(array('workdao'));
        $parameters = array('id' => $this->id, 'getnomurl' => $result);
        $reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
        if ($reshook > 0) $result = $hookmanager->resPrint;
        else $result .= $hookmanager->resPrint;

        return $result;
    }

    /**
     *  Return label of the status
     *
     * @param int $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     * @return    string                   Label of status
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->status, $mode);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

    /**
     *  Return the status
     *
     * @param int $status Id status
     * @param int $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     * @return string                   Label of status
     */
    public function LibStatut($status, $mode = 0)
    {
        // phpcs:enable
        if (empty($this->labelstatus)) {
            global $langs;
            //$langs->load("ouvrage");
            $this->labelstatus[self::STATUS_DRAFT] = $langs->trans('Draft');
            $this->labelstatus[self::STATUS_VALIDATED] = $langs->trans('Enabled');
            $this->labelstatus[self::STATUS_CANCELED] = $langs->trans('Disabled');
        }

        if ($mode == 0) {
            return $this->labelstatus[$status];
        } elseif ($mode == 1) {
            return $this->labelstatus[$status];
        } elseif ($mode == 2) {
            return img_picto($this->labelstatus[$status], 'statut' . $status, '', false, 0, 0, '', 'valignmiddle') . ' ' . $this->labelstatus[$status];
        } elseif ($mode == 3) {
            return img_picto($this->labelstatus[$status], 'statut' . $status, '', false, 0, 0, '', 'valignmiddle');
        } elseif ($mode == 4) {
            return img_picto($this->labelstatus[$status], 'statut' . $status, '', false, 0, 0, '', 'valignmiddle') . ' ' . $this->labelstatus[$status];
        } elseif ($mode == 5) {
            return $this->labelstatus[$status] . ' ' . img_picto($this->labelstatus[$status], 'statut' . $status, '', false, 0, 0, '', 'valignmiddle');
        } elseif ($mode == 6) {
            return $this->labelstatus[$status] . ' ' . img_picto($this->labelstatus[$status], 'statut' . $status, '', false, 0, 0, '', 'valignmiddle');
        }
    }

    /**
     *    Load the info information in the object
     *
     * @param int $id Id of object
     * @return    void
     */
    public function info($id)
    {
        $sql = 'SELECT rowid, date_creation as datec, tms as datem,';
        $sql .= ' fk_user_creat, fk_user_modif';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
        $sql .= ' WHERE t.rowid = ' . $id;
        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);
                $this->id = $obj->rowid;
                if ($obj->fk_user_author) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_author);
                    $this->user_creation = $cuser;
                }

                if ($obj->fk_user_valid) {
                    $vuser = new User($this->db);
                    $vuser->fetch($obj->fk_user_valid);
                    $this->user_validation = $vuser;
                }

                if ($obj->fk_user_cloture) {
                    $cluser = new User($this->db);
                    $cluser->fetch($obj->fk_user_cloture);
                    $this->user_cloture = $cluser;
                }

                $this->date_creation = $this->db->jdate($obj->datec);
                $this->date_modification = $this->db->jdate($obj->datem);
                $this->date_validation = $this->db->jdate($obj->datev);
            }

            $this->db->free($result);
        } else {
            dol_print_error($this->db);
        }
    }

    /**
     * Initialise object with example values
     * Id must be 0 if object instance is a specimen
     *
     * @return void
     */
    public function initAsSpecimen()
    {
        $this->initAsSpecimenCommon();
    }

    /**
     *    Create an array of lines
     *
     * @return array|int        array of lines if OK, <0 if KO
     */
    public function getLinesArray()
    {
        $this->lines = array();

        $objectline = new WorkLine($this->db);
        $result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql' => 'fk_work = ' . $this->id));

        if (is_numeric($result)) {
            $this->error = $this->error;
            $this->errors = $this->errors;
            return $result;
        } else {
            $this->lines = $result;
            return $this->lines;
        }
    }

    /**
     *  Create a document onto disk according to template module.
     *
     * @param string $modele Force template to use ('' to not force)
     * @param Translate $outputlangs objet lang a utiliser pour traduction
     * @param int $hidedetails Hide details of lines
     * @param int $hidedesc Hide description
     * @param int $hideref Hide ref
     * @param null|array $moreparams Array to provide more information
     * @return     int                        0 if KO, 1 if OK
     */
    public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
    {
        global $conf, $langs;

        $langs->load("ouvrage@ouvrage");

        if (!dol_strlen($modele)) {

            $modele = 'standard';

            if ($this->modelpdf) {
                $modele = $this->modelpdf;
            } elseif (!empty($conf->global->WORK_ADDON_PDF)) {
                $modele = $conf->global->WORK_ADDON_PDF;
            }
        }

        $modelpath = "core/modules/ouvrage/doc/";

        return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
    }

    /**
     * Action executed by scheduler
     * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
     *
     * @return    int            0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
     */
    //public function doScheduledJob($param1, $param2, ...)
    public function doScheduledJob()
    {
        global $conf, $langs;

        //$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

        $error = 0;
        $this->output = '';
        $this->error = '';

        dol_syslog(__METHOD__, LOG_DEBUG);

        $now = dol_now();

        $this->db->begin();

        // ...

        $this->db->commit();

        return $error;
    }

    /**
     *    Returns the text label from units dictionary
     *
     * @param string $type Label type (long or short)
     * @return string|int <0 if ko, label if ok
     */
    public function getLabelOfUnit($type = 'long')
    {
        global $langs;

        if (!$this->fk_unit) {
            return '';
        }

        $langs->load('products');

        $label_type = 'label';

        if ($type == 'short') {
            $label_type = 'short_label';
        }

        $sql = 'select ' . $label_type . ' from ' . MAIN_DB_PREFIX . 'c_units where rowid=' . $this->fk_unit;
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql) > 0) {
            $res = $this->db->fetch_array($resql);
            $label = $res[$label_type];
            $this->db->free($resql);
            return $label;
        } else {
            $this->error = $this->db->error() . ' sql=' . $sql;
            dol_syslog(get_class($this) . "::getLabelOfUnit Error " . $this->error, LOG_ERR);
            return -1;
        }
    }
}
