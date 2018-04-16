<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class to manage products or services
 */
class Ouvrage extends CommonObject
{
	public $element='ouvrage';
	public $table_element='works';
	//public $fk_element='fk_product';
	//protected $childtables=array('product', 'works_det', 'tva');    // To test if we can delete object
	protected $isnolinkedbythird = 1;     // No field fk_soc
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'ref';

	/**
	 * Ouvrage label
	 * @var string
	 */
	var $label;
        /**
         * Ouvrage descripion
         * @var string
         */
	var $description;
        /**
         * Ouvrage tva
         * @var string
         */
	var $tva;
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
	 *  @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
            global $langs;

            $this->db = $db;
            $this->ref = '';
            $this->label = '';
            $this->description = '';
            $this->tva = 0;
            $this->dets = array();
	}

        /**
	 *    Check that ref and label are ok
	 *
	 *    @return     int         >1 if OK, <=0 if KO
	 */
	function check()
	{
		$this->ref = dol_sanitizeFileName(stripslashes($this->ref));

		$err = 0;
		if (dol_strlen(trim($this->ref)) == 0)
		$err++;

		if (dol_strlen(trim($this->label)) == 0)
		$err++;

		if ($err > 0)
		{
			return 0;
		}
		else
		{
			return 1;
		}
	}

        /**
	 *	Insert ouvrage into database
	 *
	 *	@param	User	$user     		User making insert
	 *  @param	int		$notrigger		Disable triggers
	 *	@return int			     		Id of product/service if OK, < 0 if KO
	 */
	function create($user,$notrigger=0)
	{
		global $conf, $langs;

                $error=0;

		// Clean parameters
		$this->ref = dol_string_nospecial(trim($this->ref));
		$this->label = trim($this->label);


                // Check parameters
		if (empty($this->label))
		{
			$this->error='ErrorMandatoryParametersNotProvided';
			return -1;
		}
		if (empty($this->ref))
		{
                    $this->error='ErrorMandatoryParametersNotProvided';
                    return -2;
		}

		dol_syslog(get_class($this)."::create ref=".$this->ref." price=".$this->price." price_ttc=".$this->price_ttc." tva_tx=".$this->tva_tx." price_base_type=".$this->price_base_type, LOG_DEBUG);

        $now=dol_now();

		$this->db->begin();

		// Check more parameters
        // If error, this->errors[] is filled
        $result = $this->verify();

        if ($result >= 0)
        {
			$sql = "SELECT count(*) as nb";
			$sql.= " FROM ".MAIN_DB_PREFIX."works";
			$sql.= " WHERE entity IN (".getEntity('ouvrage', 1).")";
			$sql.= " AND ref = '" .$this->ref."'";

			$result = $this->db->query($sql);
			if ($result)
			{
				$obj = $this->db->fetch_object($result);
				if ($obj->nb == 0)
				{
					// Ouvrage non deja existant
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."works (";
					$sql.= "`entity`";
					$sql.= ", ref";
					$sql.= ", label";
					$sql.= ", `desc`";
					$sql.= ", fk_tva";
					$sql.= ") VALUES (";
					$sql.= $conf->entity;
					$sql.= ", '".$this->db->escape($this->ref)."'";
					$sql.= ", ".(! empty($this->label)?"'".$this->db->escape($this->label)."'":"null");
					$sql.= ", ".(! empty($this->description)?"'".$this->db->escape($this->description)."'":"null");
					$sql.= ", ".$this->tva;
					$sql.= ")";

					dol_syslog(get_class($this)."::Create", LOG_DEBUG);
					$result = $this->db->query($sql);
					if ( $result )
					{
						$id = $this->db->last_insert_id(MAIN_DB_PREFIX."product");

						if ($id > 0)
						{
                                                    $this->id = $id;

                                                    $sqlDet = '';
                                                    foreach ($this->dets as $det) {
                                                        $sqlDet = "INSERT INTO ".MAIN_DB_PREFIX."works_det (fk_works, fk_product, `order`, qty)";
                                                        $sqlDet .= " VALUES (" . $this->id . ',' . $det['product'] . ',' . $det['order'] . ',' . $det['qty'] . ');';
                                                        $result = $this->db->query($sqlDet);
                                                    }
                                                    if (!$result) {
                                                        $error++;
                                                        $this->error=$this->db->lasterror();
                                                    }
						}
						else
						{
                                                    $error++;
						    $this->error='ErrorFailedToGetInsertedId';
						}
					}
					else
					{
						$error++;
					    $this->error=$this->db->lasterror();
					}
				}
				else
				{
					// Product already exists with this ref
					$langs->load("ouvrage@ouvrage");
					$error++;
					$this->error = $conf->global->OUVRAGE_TYPE."ErrorOuvrageAlreadyExists";
				}
			}
			else
			{
				$error++;
			    $this->error=$this->db->lasterror();
			}

			if (! $error)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				$this->db->rollback();
				return -$error;
			}
        }
        else
       {
            $this->db->rollback();
            dol_syslog(get_class($this)."::Create fails verify ".join(',',$this->errors), LOG_WARNING);
            return -3;
        }

	}

        function verify()
        {
        $this->errors=array();

        $result = 0;
        $this->ref = trim($this->ref);

        if (! $this->ref)
        {
            $this->errors[] = 'ErrorBadRef';
            $result = -2;
        }

        return $result;
    }

    function addProduct($productid, $qty, $order) {
        $this->dets[] = array('product' => (int)$productid, 'qty' => (int)$qty, 'order' => (int)$order);
    }

    function fetch($id) {
        global $user, $conf;
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."works WHERE `entity` = " . $conf->entity . " AND rowid = " . $id;

        $resql = $this->db->query($sql);
        if ( $resql )
        {
            if ($this->db->num_rows($resql) > 0)
            {
                $obj = $this->db->fetch_object($resql);

                $this->id           = $obj->rowid;
                $this->ref          = $obj->ref;
                $this->label        = $obj->label;
                $this->description  = $obj->desc;
                $this->tva  = $obj->fk_tva;

                // On Charge les produits
                $sqlDet = "SELECT * FROM ".MAIN_DB_PREFIX."works_det WHERE fk_works = " . $this->id ." ORDER BY `order`";

                $resqlDet = $this->db->query($sqlDet);

                if ($resqlDet) {
                    if ($this->db->num_rows($resqlDet) > 0) {
                        while ($result= $this->db->fetch_array($resqlDet)) {
                            $this->dets[] = array('product' => $result['fk_product'], 'qty' => $result['qty'], 'order' => $result['order']);
                        }
                    }
                }
            }
        }
    }

    function delete($id) {

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."works WHERE rowid = " . $id;

	dol_syslog(get_class($this).'::delete', LOG_DEBUG);
        $result = $this->db->query($sql);
        if (! $result)
        {
                $error++;
                $this->errors[] = $this->db->lasterror();

                return 0;
        }

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."works_det WHERE fk_works = " . $id;
        $result = $this->db->query($sql);
        if (! $result)
        {
                $error++;
                $this->errors[] = $this->db->lasterror();

                return 0;
        }


        return 1;

    }

    public function update($id, $user, $notrigger=false, $action='update') {
        global $langs, $conf, $hookmanager;

        $error=0;

        $this->ref = dol_string_nospecial(trim($this->ref));
        $this->label = trim($this->label);
        $this->description = trim($this->description);

        $sql = "UPDATE ".MAIN_DB_PREFIX."works ";
        $sql.= " SET label = '" . $this->db->escape($this->label) ."'";
	$sql.= ", ref = '" . $this->db->escape($this->ref) ."'";
        $sql.= ", `desc` = '" . $this->db->escape($this->description) ."'";
        $sql .= ", fk_tva = " . $this->tva;
        $sql.= " WHERE rowid = " . $this->id;

        /*var_dump($this->description);
        var_dump($sql);exit;*/

        $result = $this->db->query($sql);

        if (! $result)
        {
                $error++;
                $this->errors[] = $this->db->lasterror();

                return 0;
        }

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."works_det WHERE fk_works = " . $id;
        $result = $this->db->query($sql);
        if (! $result)
        {
                $error++;
                $this->errors[] = $this->db->lasterror();

                return 0;
        }

        $sqlDet = '';
        foreach ($this->dets as $det) {
            $sqlDet = "INSERT INTO ".MAIN_DB_PREFIX."works_det (fk_works, fk_product, `order`, qty)";
            $sqlDet .= " VALUES (" . $this->id . ',' . $det['product'] . ',' . $det['order'] . ',' . $det['qty'] . ');';
            $result = $this->db->query($sqlDet);
        }
        if (!$result) {
            $error++;
            $this->error=$this->db->lasterror();

            return 0;
        }


        return 1;
    }

    public function createFromClone($ref) {
        $this->id=0;
        $this->ref = $ref;

        // Create clone
        $result=$this->create($user);
        if ($result < 0) $error++;

        if (! $error)
        {
            $this->db->commit();
            return $this->id;
        }
        else
        {
            $this->db->rollback();
            return -1;
        }
    }

}