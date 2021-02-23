<?php
/* Copyright (C) 2014-2017	Charlie BENKE		<charlie@patas-monkey.com>
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
 *	\file	   htdocs/factory/class/factory.class.php
 *	\ingroup	categorie
 *	\brief	  File of class to factory
 */

/**
 *	Class to manage Factory
 */
class Factory extends CommonObject
{
	public $element='factory';
	public $table_element='factory';
	public $fk_element='fk_factory';
	public $table_element_line='factorydet';

	var $id;
	var $ref;
	var $fk_product;
	var $fk_entrepot;
	var $description;
	var $statut;
	var $model_pdf;
	// -----
	var $qty_planned;
	var $date_end_planned;
	var $date_start_planned;
	var $duration_planned;
	// -----
	var $qty_made;
	var $date_end_made;
	var $date_start_made;
	var $duration_made;
    var $manufacturing_cost;
    var $manufacturing_cost_excluding_services;

	var $is_sousproduit_qty=0;
	var $is_sousproduit_qtyglobal=0;
	var $is_sousproduit_description="";


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db	 Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->statut = 0;

		// List of language codes for status
		$this->statuts[0]='Draft';
		$this->statuts[1]='Activated';
		$this->statuts[2]='Closed';
		$this->statuts[3]='Canceled';
		$this->statuts_short[0]='Draft';
		$this->statuts_short[1]='Activated';
		$this->statuts_short[2]='Closed';
		$this->statuts_short[3]='Canceled';

		$this->statuts_img[0]='statut0';
		$this->statuts_img[1]='statut4';
		$this->statuts_img[2]='statut6';
		$this->statuts_img[3]='statut5';
	}


    /**
     * Create factory order (OF)
     *
     * @param   array   $warehouseToUseList             [=array()] Use factory warehouse to extract product component or list of warehouses with quantities to extract each product component ( ex : array(array(array('fk_entrepot' => 1, 'qty' => 1), array('fk_entrepot' => 6, 'qty' => 2))) )
     * @return  int     <0 if KO, Id of factory if OK
     *
     * @throws Exception
     */
	public function createof($warehouseToUseList=array())
	{
        global $user, $conf;
        global $orig;

        $error = 0;

		$this->db->begin();

		$obj = $conf->global->FACTORY_ADDON;
		$modfactory = new $obj;
		$refOF = $modfactory->getNextValue();
		$this->ref=$refOF ;

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'factory ( ref, fk_product, fk_entrepot, ';
		$sql.= 'description, date_start_planned, date_end_planned, duration_planned, qty_planned, fk_user_author';
		$sql.= ' ) VALUES ("'.$refOF.'", '.($this->fk_product ? $this->fk_product : $this->id).', '.$this->fk_entrepot.', "';
		$sql.= $this->db->escape($this->description).'"';
        $sql.= ", " . ($this->date_start_planned ? "'" . $this->db->idate($this->date_start_planned) . "'" : 'null');
        $sql.= ", " . ($this->date_end_planned ? "'" . $this->db->idate($this->date_end_planned) . "'" : 'null');
		$sql.= ', '.($this->duration_planned?$this->duration_planned:'null');
		$sql.= ', '.($this->qty_planned?$this->qty_planned:'null').', '.$user->id.' )';

		if (! $this->db->query($sql)) {
			$error++;
			$this->error    = $this->db->lasterror();
			$this->errors[] = $this->error;
		} else {
			// get the last inserted value
			$factoryid=$this->db->last_insert_id(MAIN_DB_PREFIX."factory");

			$tmpid = $this->id;

			if ($this->context['createfromclone'] == 'createfromclone') {
				// on recupere la composition de l'of Clone
				$prods_arbo = $this->getChildsOF($orig->id);
			} else {
				// sinon c'est ceux du produit associe a l'OF
				// on memorise les composants utilises pour la fabrication
				//$prodsfather = $this->getFather(); //Parent Products
				$this->get_sousproduits_arbo($factoryid);
				$prods_arbo = $this->get_arbo_each_prod();
				// Number of subproducts
			}
			// something wrong in recurs, change id of object
			$this->id = $tmpid ;

			// List of subproducts
			if (count($prods_arbo) > 0) {

                // detect mode to create component OF
                $createOFComponentMode = 1;
                if (!empty($warehouseToUseList)) {
                    // dispatch lines
                    $createOFComponentMode = 2;
                }

				// on boucle sur les composants	pour creer les lignes de detail
				foreach ($prods_arbo as $value) {
				    if ($createOFComponentMode == 1) {
                        // for clone only
				        if ($this->context['createfromclone'] == 'createfromclone') {
				            // only dispatch first line
				            if ($value['id_dispatched_line']==0) {
                                //$result = $this->createof_component($factoryid, $this->qty_planned, $value, 0, $value['child_fk_entrepot']);
                                $result = $this->createof_component($factoryid, $this->qty_planned, $value, 0, $value['child_fk_entrepot'], $value['id_dispatched_line'], $value['nb'], $value['indice_factory_build']);
                            }
                        }
                        // create component in standard mode
                        else {
                            $result = $this->createof_component($factoryid, $this->qty_planned, $value, 0);
                        }
                    } else {
                        // dispatch lines
                        $result = $this->_createOFComponentFromWarehouseToUseList($warehouseToUseList[$value['id']], $factoryid, $value, 0);
                    }

                    if ($result < 0) {
                        $error++;
                        break;
                    }
                }
			}

			if (! $error) {
                // les extrafields sont a associ� � l'of pas au produit
                $this->id = $factoryid;
                $result = $this->insertExtraFields();
                if ($result < 0) {
                    $error++;
                }
            }

            if (! $error) {
                // Call trigger
                $result=$this->call_trigger('FACTORY_CREATE', $user);
                if ($result < 0) $error++;
                // End call triggers
            }
		}

		// commit or rollback
        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->db->commit();
            return $factoryid;
        }
	}


    /**
     * Create component lines in factory order (OF) from a list of warehouses with quantities to use
     *
     * @param   array       $warehouseToUseList             [=array()] Use factory warehouse to extract product component or list of warehouses with quantities to extract each product component ( ex : array(array(array('fk_entrepot' => 1, 'qty' => 1), array('fk_entrepot' => 6, 'qty' => 2))) )
     * @param   int         $fk_factory                     Id of factory
     * @param   array       $valuearray                     Array of values
     * @param   int         $fk_mouvementstock              Stock movement
     * @return  int         <0 if KO, Id of factory if OK
     *
     * @throws  Exception
     */
    private function _createOFComponentFromWarehouseToUseList($warehouseToUseList, $fk_factory, $valuearray, $fk_mouvementstock=0)
    {
        $error = 0;

        if (count($warehouseToUseList) > 0) {
            foreach ($warehouseToUseList as $indiceFactoryBuild => $warehouseToUseByIndiceList) {
                foreach ($warehouseToUseByIndiceList as $idDispatchedLine => $warehouseToUse) {
                    $result = $this->createof_component($fk_factory, 0, $valuearray, $fk_mouvementstock, $warehouseToUse['fk_entrepot'], $idDispatchedLine, $warehouseToUse['qty'], $indiceFactoryBuild);

                    if ($result < 0) {
                        $error++;
                        break;
                    }
                }

                if ($error) {
                    break;
                }
            }
        }

        if ($error) {
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Create a component line in factory order (OF)
     *
     * @param   int         $fk_factory             Id of factory
     * @param   int         $qty_build              Qty to build
     * @param   array       $valuearray             Array of values
     * @param   int         $fk_mouvementstock      Stock movement
     * @param   int|NULL    $fk_entrepot            [=NULL] Id of warehouse to use
     * @param   int         $id_dispatched_line     [=O] Id of dispatched line
     * @param   int|NULL    $qty_planned            [=NULL] Qty planned or NULL to use default (calculated by qty to build)
     * @param   int         $indice_factory_build   [=O] Id of factory build
     * @return  int         <0 if KO, Id of factorydet if OK
     *
     * @throws  Exception
     */
	public function createof_component($fk_factory, $qty_build, $valuearray, $fk_mouvementstock=0, $fk_entrepot=NULL, $id_dispatched_line=0, $qty_planned=NULL, $indice_factory_build=0)
	{
		$sql  = "INSERT INTO " . MAIN_DB_PREFIX . "factorydet (";
		$sql .= "fk_factory, fk_product, qty_unit, qty_planned, pmp, price";
		$sql .= ", fk_mvtstockplanned, globalqty, description";
        $sql .= ", fk_entrepot";
        $sql .= ", id_dispatched_line";
        $sql .= ", indice_factory_build";
		$sql .= ")";
		if ($qty_planned===NULL) {
            // pour gerer les quantites
            if ($valuearray['globalqty'] == 0)
                $qty_planned=$qty_build * $valuearray['nb'];
            else
                $qty_planned=$valuearray['nb'];
        }
		$sql .= " VALUES (" . $fk_factory . ", " . $valuearray['id'] .", " . $valuearray['nb'];
		$sql .= ", " . $qty_planned . ", " . $valuearray['pmp'] . ", " . $valuearray['price'];
		$sql .= ", " . $fk_mouvementstock . ", " . $valuearray['globalqty'];
        $sql .= ", '" . $this->db->escape($valuearray['description']) . "'";
        $sql .= ", " . ($fk_entrepot > 0 ? $fk_entrepot : 'NULL');
        $sql .= ", " . ($id_dispatched_line > 0 ? $id_dispatched_line : 0);
        $sql .= ", " . ($indice_factory_build > 0 ? $indice_factory_build : 0);
        $sql .= ")";
		if (! $this->db->query($sql)) {
		    $this->error    = $this->db->lasterror();
		    $this->errors[] = $this->error;
            dol_syslog(__METHOD__ . " sql=" . $sql, LOG_ERR);
			return -1;
		} else {
            // get the last inserted value
            $factorydetId = $this->db->last_insert_id(MAIN_DB_PREFIX . "factorydet");
			return $factorydetId;
		}
	}

	function fetch($rowid, $ref='')
	{
		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."factory as f";
		if ($ref) $sql.= " WHERE f.ref='".$this->db->escape($ref)."'";
		else $sql.= " WHERE f.rowid=".$rowid;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id				= $obj->rowid;
				$this->ref				= $obj->ref;
				$this->description  	= $obj->description;
				$this->qty_planned		= $obj->qty_planned;
				$this->qty_made			= $obj->qty_made;
				$this->date_start_planned = $this->db->jdate($obj->date_start_planned);
				$this->date_start_made 	= $this->db->jdate($obj->date_start_made);
				$this->date_end_planned	= $this->db->jdate($obj->date_end_planned);
				$this->date_end_made	= $this->db->jdate($obj->date_end_made);
				$this->duration_planned	= $obj->duration_planned;
				$this->duration_made	= $obj->duration_made;
                $this->manufacturing_cost	= $obj->manufacturing_cost;
                $this->manufacturing_cost_excluding_services	= $obj->manufacturing_cost_excluding_services;
				$this->fk_product		= $obj->fk_product;
				$this->fk_entrepot		= $obj->fk_entrepot;
				$this->note_public		= $obj->note_public;
				$this->note_private		= $obj->note_private;
				$this->model_pdf		= $obj->model_pdf;
				$this->statut			= $obj->fk_statut;

				$this->extraparams	= (array) json_decode($obj->extraparams, true);

				$this->db->free($resql);
				return 1;
			}
		} else {
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * 	Information sur l'objet fiche intervention
	 *
	 *	@param	int		$id	  Id de la fiche d'intervention
	 *	@return	void
	 */
	function info($id)
	{
		global $conf;

		$sql = "SELECT f.rowid,";
		$sql.= " date_start_planned,";
		$sql.= " date_start_made,";
		$sql.= " date_end_planned,";
		$sql.= " date_end_made,";
		$sql.= " fk_user_author,";
		$sql.= " fk_user_valid,";
		$sql.= " fk_user_close";
		$sql.= " FROM ".MAIN_DB_PREFIX."factory as f";
		$sql.= " WHERE f.rowid = ".$id;

		$result = $this->db->query($sql);

		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id	= $obj->rowid;
				$this->date_creation = $this->db->jdate($obj->date_start_planned);

				if ($obj->date_start_made)
					$this->date_creation = $this->db->jdate($obj->date_start_made);

				if ($obj->date_end_made)
					$this->date_cloture = $this->db->jdate($obj->date_end_made);

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

				if ($obj->fk_user_close) {
					$euser = new User($this->db);
					$euser->fetch($obj->fk_user_close);
					$this->user_cloture = $euser;
				}
			}
			$this->db->free($result);
		}
		else
			dol_print_error($this->db);
	}

	/**
	 *  Lie un produit associe au produit/service
	 *
	 *  @param	  int		$id_pere		Id du produit auquel sera lie le produit a lier
	 *  @param	  int		$id_fils		Id du produit a lier
	 *  @param		int		$qty			Quantity
	 *  @param		double	$pmp			buy price
	 *  @param		double	$price			sell price
	 *  @param		int		$qtyglobal		Quantity is a global value
	 *  @param		string	$description	description
	 *  @param		int		$qtyglobal		order of composition
	 *  @return	 int						< 0 if KO, > 0 if OK
	 */
	function add_component($fk_parent, $fk_child, $qty, $pmp=0, $price=0, $qtyglobal=0, $description='', $ordercomponent=0)
	{
		$sql = 'DELETE from '.MAIN_DB_PREFIX.'product_factory';
		$sql .= ' WHERE fk_product_father  = "'.$fk_parent.'" AND fk_product_children = "'.$fk_child.'"';
		//print $sql.'<br>';
		if (! $this->db->query($sql)) {
			dol_print_error($this->db);
			return -1;
		} else {
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'product_factory(fk_product_father, fk_product_children,';
			$sql .= 'qty, pmp, price, globalqty, description, ordercomponent)';
			$sql .= ' VALUES ('.$fk_parent.', '.$fk_child.', '.price2num($qty).', '.price2num($pmp).', '.price2num($price);
			$sql .= ', '.($qtyglobal?$qtyglobal:'0').', "'.$this->db->escape($description).'"';
			$sql .= ', '.($ordercomponent?$ordercomponent:'0');
			$sql .= ' )';
			//print $sql.'<br>';
			if (! $this->db->query($sql)) {
				dol_print_error($this->db);
				return -1;
			}
			else
				return 1;
		}
	}

	/**
	 *  Lie un produit associe au produit/service
	 *
	 *  @param	  int		$id_pere		Id du produit auquel sera lie le produit a lier
	 *  @param	  int		$id_fils		Id du produit a lier
	 *  @param		int		$qty			Quantity
	 *  @param		double	$pmp			buy price
	 *  @param		double	$price			sell price
	 *  @param		int		$qtyglobal		Quantity is a global value
	 *  @param		string	$description	descrption
	 *  @return	 int						< 0 if KO, > 0 if OK
	 */
	function add_componentOF($fk_factory, $fk_product, $qty, $pmp=0, $price=0, $qtyglobal=0, $description='')
	{
		$sql = 'DELETE from '.MAIN_DB_PREFIX.'factorydet';
		$sql .= ' WHERE fk_factory = '.$fk_factory.' AND fk_product= '.$fk_product;
		if (! $this->db->query($sql)) {
			dol_print_error($this->db);
			return -1;
		} else {
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'factorydet(fk_factory, fk_product,';
			$sql .= 'qty_unit, qty_planned, pmp, price, globalqty, description)';
			$sql .= ' VALUES ('.$fk_factory.', '.$fk_product.', '.price2num($qty);
			if ($qtyglobal ==1)
				$sql .= ', '.price2num($qty);
			else
				$sql .= ', '.price2num($qty * $this->qty_planned);
			$sql .= ', '.price2num($pmp).', '.price2num($price);
			$sql .= ', '.($qtyglobal?$qtyglobal:'0').', "'.$this->db->escape($description).'"';
			$sql .= ' )';
			if (! $this->db->query($sql)) {
				dol_print_error($this->db);
				return -1;
			}
			else
				return 1;
		}
	}

	/**
	 *  Lie un produit associe � une tache
	 *
	 *  @param	  int	$id_pere	Id du produit auquel sera lie le produit a lier
	 *  @param	  int	$id_fils	Id du produit a lier
	 *  @param		int	$qty		Quantity
	 *  @param		double	$pmp	buy price
	 *  @param		double	$price	sell price
	 *  @return	 int				< 0 if KO, > 0 if OK
	 */
	function add_componenttask($fk_task, $fk_product, $qty_planned, $pmp=0, $price=0)
	{
		// dans le doute on supprime toujours la ligne
		$sql = 'DELETE from '.MAIN_DB_PREFIX.'projet_taskdet';
		$sql .= ' WHERE fk_task = '.$fk_task.' AND fk_product = '.$fk_product;
		if (! $this->db->query($sql)) {
			dol_print_error($this->db);
			return -1;
		} else {
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'projet_taskdet(fk_task, fk_product, qty_planned, pmp, price)';
			$sql .= ' VALUES ('.$fk_task.', '.$fk_product.', ';
			$sql .= price2num($qty_planned).', '.price2num($pmp).', '.price2num($price).' )';
			if (! $this->db->query($sql)) {
				dol_print_error($this->db);
				return -1;
			} else
				return 1;
		}
	}

	/**
	 *  Verifie si c'est un sous-produit
	 *
	 *  @param	  int	$fk_parent		Id du produit auquel le produit est lie
	 *  @param	  int	$fk_child		Id du produit lie
	 *  @param	  int	$basetable		Id du produit lie

	 *  @return	 int					< 0 si erreur, > 0 si ok
	 */
	function is_sousproduit($fk_parent, $fk_child)
	{
		$sql = "SELECT qty, globalqty, description, ordercomponent";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_factory";
		$sql.= " WHERE fk_product_father  = '".$fk_parent."'";
		$sql.= " AND fk_product_children = '".$fk_child."'";

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);

			if ($num > 0) {
				$obj = $this->db->fetch_object($result);
				$this->is_sousproduit_qty = $obj->qty;
				$this->is_sousproduit_qtyglobal = $obj->globalqty;
				$this->is_sousproduit_description = $obj->description;
				$this->is_sousproduit_ordercomponent = $obj->ordercomponent;
				return true;
			} else
				return false;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Verifie si c'est un sous-produit
	 *
	 *  @param	  int	$fk_parent		Id du produit auquel le produit est lie
	 *  @param	  int	$fk_child		Id du produit lie
	 *  @param	  int	$basetable		Id du produit lie

	 *  @return	 int					< 0 si erreur, > 0 si ok
	 */
	function is_sousproduitOF($fk_factory, $fk_child)
	{
		$sql = "SELECT qty_unit, globalqty, description";
		$sql.= " FROM ".MAIN_DB_PREFIX."factorydet";
		$sql.= " WHERE fk_factory = ".$fk_factory;
		$sql.= " AND fk_product = ".$fk_child;

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);

			if ($num > 0) {
				$obj = $this->db->fetch_object($result);
				$this->is_sousproduit_qty = $obj->qty_unit;
				$this->is_sousproduit_qtyglobal = $obj->globalqty;
				$this->is_sousproduit_description = $obj->description;
				return true;
			} else
				return false;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}



	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	void
	 */
	function initAsSpecimen()
	{
		global $user, $langs, $conf;

		$now=dol_now();

		// Initialise parametres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$this->socid = 1;
		$this->date = $now;
		$this->note_public='SPECIMEN';
		$this->duree = 0;
		$nbp = 5;
		$xnbp = 0;
		while ($xnbp < $nbp) {
			$this->lines[$xnbp]=$line;
			$xnbp++;
			$this->duree+=$line->duration;
		}
	}


	/**
	 *  Retire le lien entre un sousproduit et un produit/service
	 *
	 *  @param	  int	$fk_parent		Id du produit auquel ne sera plus lie le produit lie
	 *  @param	  int	$fk_child		Id du produit a ne plus lier, 0 si tous les d�lier
	 *  @return	 int					< 0 si erreur, > 0 si ok
	 */
	function del_component($fk_parent, $fk_child=0)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_factory";
		$sql.= " WHERE fk_product_father  = '".$fk_parent."'";
		if ($fk_child > 0)
			$sql.= " AND fk_product_children = '".$fk_child."'";

		if (! $this->db->query($sql)) {
			dol_print_error($this->db);
			return -1;
		}

		return 1;
	}

		/**
	 *  Retire le lien entre un sousproduit et un produit/service dans l'of
	 *
	 *  @param	  int	$fk_factory		la ligne de l'of
	 *  @param	  int	$fk_child		la ligne du produit (si 0 tous les produits d'un coups)
	 *  @return	 int					< 0 si erreur, > 0 si ok
	 */
	function del_componentOF($fk_factory, $fk_product=0)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."factorydet";
		$sql.= " WHERE fk_factory = ".$fk_factory;
		if ($fk_product > 0)
			$sql.= " AND fk_product = ".$fk_product;

		if (! $this->db->query($sql)) {
			dol_print_error($this->db);
			return -1;
		}

		return 1;
	}

	/**
	 *  Retire le lien entre un sousproduit et un produit/service
	 *
	 *  @param	  int	$fk_parent		Id du produit auquel ne sera plus lie le produit lie
	 *  @param	  int	$fk_child		Id du produit a ne plus lie
	 *  @return	 int					< 0 si erreur, > 0 si ok
	 */
	function del_componenttask($fk_task, $fk_product)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."projet_taskdet";
		$sql.= " WHERE fk_task  = ".$fk_task;
		$sql.= " AND fk_product = ".$fk_product;

		if (! $this->db->query($sql)) {
			dol_print_error($this->db);
			return -1;
		}

		return 1;
	}

	// r�cup�ration du nombre de produit en cours de fabrication
	function getQtylink ($orderid, $fk_product)
	{
		$sql = "SELECT sum(qty_planned) as total_planned, sum(qty_made) as total_made";
		$sql.= " FROM ".MAIN_DB_PREFIX."element_element as ee , ".MAIN_DB_PREFIX."factory as f";
		$sql.= " WHERE ee.fk_target = f.rowid";
		$sql.= " AND sourcetype='commande' AND targettype='factory'";
		$sql.= " AND ee.fk_source = ".$orderid;
		$sql.= " AND f.fk_product = ".$fk_product;

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			if ($num > 0) {
				$obj = $this->db->fetch_object($result);
				if ($obj->total_made > 0)
					return $obj->total_made;
				else
					return $obj->total_planned;
			} else
				return 0;
		}
	}


	/**
	 *	Return number of product buildable in entrepot
	  *
	 * 	@param	int		$entrepotid		id of the entrepot
	 * 	@param	int		$productid		id of the product to build
	 *  @return	int						number of product buildable
	 */
	function getNbProductBuildable($entrepotid, $productid)
	{
		$this->id=$productid;
		//$this->fetch($productid);

		$fabricable=0;
		$this->get_sousproduits_arbo();
		$prods_arbo = $this->get_arbo_each_prod();
		if (count($prods_arbo) > 0) {
			$fabricable=-1;
			$bAllService = true;
			foreach ($prods_arbo as $value) {
				$productstatic = new Product($this->db);
				$productstatic->id=$value['id'];
				$productstatic->fetch($value['id']);
				if ($value['type']==0) {
					// we have at list a service
					$bAllService = false;
					$productstatic->load_stock();
					// for the first loop, buildable is the stock divide by number need
					if ($fabricable==-1)
						$fabricable=$productstatic->stock_warehouse[$entrepotid]->real/$value['nb'];
					else {
						// other loop, buildable changed only if the number is smaller
						if ($fabricable >= $productstatic->stock_reel/$value['nb'])
							$fabricable=$productstatic->stock_warehouse[$entrepotid]->real/$value['nb'];
					}
				}
			}
		}
		if ($bAllService)
			return -2;
		// attention buildable product are always an integer
		return (int) $fabricable;
	}

	function get_nb_ProductInTask($taskid, $productid)
	{
		$sql = "SELECT qty_planned as qtyplanned";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet_taskdet as ptd";
		$sql.= " WHERE ptd.fk_task = ".$taskid;
		$sql.= " AND ptd.fk_product=".$productid;

		$res = $this->db->query($sql);
		if ($res) {
			//$objp = $this->db->fetch_array($res);
			$objp = $this->db->fetch_object($res);
			return $objp->qtyplanned;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	function getQtyFromStock($projectid, $productid)
	{

		$sql = "SELECT sum(qty_from_stock) as nbinproject ";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet_stock as ps";
		$sql.= " WHERE ps.fk_project = ".$projectid;
		$sql.= " AND ps.fk_product= ".$productid;
		$res = $this->db->query($sql);

		if ($res) {
			$obj = $this->db->fetch_object($res);
			return $obj->nbinproject;
		}
		return 0;
	}

	function get_value_ProductInTask($taskid, $productid, $valuetype, $defaultvalue=0)
	{
		$sql = "SELECT pmp, price FROM ".MAIN_DB_PREFIX."projet_taskdet as ptd";
		$sql.= " WHERE ptd.fk_task = ".$taskid;
		$sql.= " AND ptd.fk_product=".$productid;
		$res = $this->db->query($sql);
		if ($res) {
			//$objp = $this->db->fetch_array($res);
			$objp = $this->db->fetch_object($res);
			if ($valuetype=='pmp')
				return ($objp->pmp ? $objp->pmp : $defaultvalue);
			else
				return ($objp->price ? $objp->price : $defaultvalue);
		}
		return 0;
	}

	/**
	 *  Fonction recursive uniquement utilisee par get_arbo_each_prod, recompose l'arborescence des sousproduits
	 * 	Define value of this->res
	 *
	 *	@param		array	$prod			Products array
	 *	@param		string	$compl_path		Directory path
	 *	@param		int		$multiply		Because each sublevel must be multiplicated by parent nb
	 *	@param		int		$level			Init level
	 *  @return 	void
	 */
	function fetch_prod_arbo($prod, $compl_path="", $multiply=1, $level=1)
	{
		global $conf, $langs;
		foreach ($prod as $nom_pere => $desc_pere) {
			$product = new Product($this->db);
			if (is_array($desc_pere)) {
				// If this parent desc is an array, this is an array of childs
				//var_dump($desc_pere);
				$id=(! empty($desc_pere[0]) ? $desc_pere[0] :'');
				$nb=(! empty($desc_pere[1]) ? $desc_pere[1] :'0');
				$type=(! empty($desc_pere[2]) ? $desc_pere[2] :'');
				$label=(! empty($desc_pere[3]) ? $desc_pere[3] :'');
				$pmp=(! empty($desc_pere[4]) ? $desc_pere[4] :'0');
				$price=(! empty($desc_pere[5]) ? $desc_pere[5] :'0');
				$globalqty=(! empty($desc_pere[6]) ? $desc_pere[6] :'0');
				$description=(! empty($desc_pere[7]) ? $desc_pere[7] :'');
				$ordercomponent=(! empty($desc_pere[8]) ? $desc_pere[8] :'');

				if ($multiply) {
					//print "XXX ".$desc_pere[1]." nb=".$nb." multiply=".$multiply."<br>";
					$img="";
					$product->fetch($id);
					$product->load_stock();
					if ($product->stock_warehouse[1]->real < $this->seuil_stock_alerte)
						$img=img_warning($langs->trans("StockTooLow"));

					// si en quantit� global on ne g�re pas de la m�me fa�on les quantit�s
					if ($globalqty == 0)
						$nb*$multiply;
					else
						$nb;

					$this->res[]= array(
						'id'=>$id,									// Id product
						'label'=>$label,							// label product
						'pmp'=>$pmp,								// pmp of the product
						'price'=>$price,							// price of the product
						'nb'=>$nb,									// Nb of units that compose parent product
						'nb_total'=>$nb*$multiply,					// Nb of units for all nb of product
						'stock'=>$this->stock_warehouse[1]->real,	// Stock
						'stock_alert'=>$this->seuil_stock_alerte,	// Stock alert
						'fullpath' => $compl_path.$nom_pere,		// Label
						'type'=>$type,								// Nb of units that compose parent product
						'globalqty'=>$globalqty,					// Nb of units that compose parent product
						'description'=>$description,				// description additionnel sur l'of
						'ordercomponent'=>$ordercomponent			// ordre de la composition
					);
				} else {
					$product->fetch($desc_pere[0]);
					$product->load_stock();
					$$this->res[]= array(
						'id'=>$id,									// Id product
						'label'=>$label,							// label product
						'pmp'=>$pmp,								// pmp of the product
						'price'=>$price,							// price of the product
						'nb'=>$nb,									// Nb of units that compose parent product
						'nb_total'=>$nb,							// Nb of units for all nb of product
						'stock'=>$this->stock_warehouse[1]->real,	// Stock
						'stock_alert'=>$this->seuil_stock_alerte,	// Stock alert
						'fullpath' => $compl_path.$nom_pere,		// Label
						'type'=>$type,								// Nb of units that compose parent product
						'globalqty'=>$globalqty,					// Nb of units that compose parent product
						'description'=>$description,				// description additionnel sur l'of
						'ordercomponent'=>$ordercomponent			// ordre de la composition
					);
				}
			} elseif ($nom_pere != "0" && $nom_pere != "1")
				$this->product[]= array($compl_path.$nom_pere, $desc_pere);
		}
	}

	/**
	 *  fonction recursive uniquement utilisee par get_each_prod, ajoute chaque sousproduits dans le tableau res
	 *
	 *	@param	array	$prod	Products array
	 *  @return void
	 */
	function fetch_prods($prod)
	{
		$this->res = array();
//var_dump($prod);
		foreach ($prod as $nom_pere => $desc_pere) {
			// on est dans une sous-categorie
			if (is_array($desc_pere))
				$this->res[]= array($desc_pere[1], $desc_pere[0]);

			if (count($desc_pere) >1)
				$this->fetch_prods($desc_pere);
		}
	}

	/**
	 *  reconstruit l'arborescence des composants sous la forme d'un tableau
	 *
	 *	@param		int		$multiply		Because each sublevel must be multiplicated by parent nb
	 *  @return 	array 					$this->res
	 */
	function get_arbo_each_prod($multiply=1)
	{
		$this->res = array();
		if (isset($this->sousprods) && is_array($this->sousprods)) {
			foreach ($this->sousprods as $nom_pere => $desc_pere)
				if (is_array($desc_pere)) $this->fetch_prod_arbo($desc_pere, "", $multiply);
		}
		return $this->res;
	}

	/**
	 *  Renvoie tous les sousproduits dans le tableau res, chaque ligne de res contient : id -> qty
	 *
	 *  @return array $this->res
	 */
	function get_each_prod()
	{
		$this->res = array();
		if (is_array($this->sousprods)) {
			foreach ($this->sousprods as $nom_pere => $desc_pere)
				if (count($desc_pere) >1)
					$this->fetch_prods($desc_pere);
			sort($this->res);
		}
		return $this->res;
	}


	/**
	 *  Return all Father products fo current product
	 *
	 *  @return 	array prod
	 */
	function getFather()
	{
		$sql = "SELECT p.label as label, p.rowid, pf.fk_product_father as id, p.fk_product_type";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_factory as pf,";
		$sql.= " ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE p.rowid = pf.fk_product_father";
		$sql.= " AND pf.fk_product_children=".$this->id;

		$res = $this->db->query($sql);
		if ($res) {
			$prods = array ();
			while ($record = $this->db->fetch_array($res)) {
				$prods[$record['id']]['id'] =  $record['rowid'];
				$prods[$record['id']]['label'] =  $this->db->escape($record['label']);
				$prods[$record['id']]['fk_product_type'] =  $record['fk_product_type'];
			}
			return $prods;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *  Return all parent products fo current product
	 *
	 *  @return 	array prod
	 */
	function getParent()
	{
		// si on se base sur la structure par d�faut de l'of
		$sql = "SELECT p.label as label, p.rowid as id";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE p.rowid = ".$this->id;

		$res = $this->db->query($sql);
		if ($res) {
			$prods = array ();
			while ($record = $this->db->fetch_array($res))
				$prods[$this->db->escape($record['label'])] = array(0=>$record['id']);
			return $prods;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Return all parent products fo current product
	 *
	 *  @return 	array prod
	 */
	function getComponentOF($factoryid)
	{
		$sql = "SELECT p.rowid, p.label as label, fd.qty_planned as qty, fd.pmp as pmp, fd.price as price";
		$sql.= " FROM ".MAIN_DB_PREFIX."factorydet as fd,";
		$sql.= " ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE fd.fk_product = p.rowid";
		$sql.= " AND fd.fk_factory = ".$factoryid;
		$sql.= " ORDER BY fd.ordercomponent";

		$res = $this->db->query($sql);
		if ($res) {
			$prods = array ();
			while ($record = $this->db->fetch_array($res))
				$prods[$this->db->escape($record['label'])] = array(0=>$record['id']);
			return $prods;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	function getExportComposition($tblCompositionLine)
	{
		$tmp.="<?xml version='1.0' encoding='ISO-8859-1'?>\n";
		$tmp.="<FactoryComposition>\n";
		// r�cup�ration des champs associ�s au customtabs
		$tmp.="<FactoryCompositionLines>\n";
		foreach ($tblCompositionLine as $key => $value) {
			$tmp.="\t".'<FactoryCompositionLine>'."\n";
			$tmp.="\t \t<productid>".$value['id']."</productid>\n";
			$tmp.="\t \t<nb>".$value['nb']."</nb>\n";
			$tmp.="\t \t<pmp>".$value['pmp']."</pmp>\n";
			$tmp.="\t \t<price>".$value['price']."</price>\n";
			$tmp.="\t \t<globalqty>".$value['globalqty']."</globalqty>\n";
			$tmp.="\t \t<description>".$value['description']."</description>\n";
			$tmp.="\t \t<ordercomponent>".$value['ordercomponent']."</ordercomponent>\n";
			$tmp.="\t".'</FactoryCompositionLine>'."\n";
		}
		$tmp.="</FactoryCompositionLines>\n";
		$tmp.="</FactoryComposition>\n";
		return $tmp;
	}

	function importComposition($xml)
	{
		// on r�cup�re le fichier et on le parse
		libxml_use_internal_errors(true);
		$sxe = simplexml_load_string($xml);
		if ($sxe === false) {
			echo "Erreur lors du chargement du XML\n";
			foreach (libxml_get_errors() as $error) {
				echo "\t", $error->message;
			}
		}
		else
			$arraydata = json_decode(json_encode($sxe), TRUE);

		// on vire la pr�c�dente composition
		$this->del_component($this->id);
		$tblfields=$arraydata['FactoryCompositionLines'];
		$tblfields=$tblfields['FactoryCompositionLine'];

		foreach ($tblfields as $fields) {
			$this->add_component($this->id, $fields['productid'],
							$fields['nb'],
							$fields['pmp'],
							$fields['price'],
							$fields['globalqty'],
							$fields['description'],
							$fields['ordercomponent']
			);
		}

	}

	function getexportOF($id)
	{
		$this->fetch($id);
		$tmp.="<?xml version='1.0' encoding='ISO-8859-1'?><FactoryOF>\n";
		$tmp.="<ref>".$this->ref."</ref>\n";
		$tmp.="<fk_product>".$this->fk_product."</fk_product>\n";
		$tmp.="<description>".$this->description."</description>\n";
		$tmp.="<qty_planned>".$this->qty_planned."</qty_planned>\n";
		$tmp.="<fk_entrepot>".$this->fk_entrepot."</fk_entrepot>\n";
		$tmp.="<duration_planned>".$this->duration_planned."</duration_planned>\n";

		// r�cup�ration des champs associ�s au customtabs
		$tmp.="<FactoryOFLines>\n";
		$tblOFLine = $this->getChildsOF($id);

		foreach ($tblOFLine as $key => $value) {
			$tmp.="\t".'<FactoryOFLine>'."\n";
			$tmp.="\t \t<productid>".$key."</productid>\n";
			$tmp.="\t \t<ref>".$value['ref']."</ref>\n";
			$tmp.="\t \t<label>".$value['label']."</label>\n";
			$tmp.="\t \t<price>".$value['price']."</price>\n";
			$tmp.="\t \t<qtyunit>".$value['nb']."</qtyunit>\n";
			$tmp.="\t \t<globalqty>".$value['globalqty']."</globalqty>\n";
			$tmp.="\t \t<description>".$value['description']."</description>\n";
			$tmp.="\t \t<qtyused>".$value['qtyused']."</qtyused>\n";
			$tmp.="\t \t<qtydeleted>".$value['qtydeleted']."</qtydeleted>\n";
			$tmp.="\t \t<qtyplanned>".$value['qtyplanned']."</qtyplanned>\n";
			$tmp.="\t \t<mvtstockplanned>".$value['mvtstockplanned']."</mvtstockplanned>\n";
			$tmp.="\t \t<mvtstockused>".$value['mvtstockused']."</mvtstockused>\n";
			$tmp.="\t \t<fk_product_type>".$value['type']."</fk_product_type>\n";
			$tmp.="\t \t<ordercomponent>".$value['ordercomponent']."</ordercomponent>\n";
			$tmp.="\t".'</FactoryOFLine>'."\n";
		}
		$tmp.="</FactoryOFLine>\n";
		$tmp.="</FactoryOF>\n";
		return $tmp;
	}


	function importCompositionOF($xml)
	{
		// on r�cup�re le fichier et on le parse
		libxml_use_internal_errors(true);
		$sxe = simplexml_load_string($xml);
		if ($sxe === false) {
			echo "Erreur lors du chargement du XML\n";
			foreach (libxml_get_errors() as $error) {
				echo "\t", $error->message;
			}
		} else
			$arraydata = json_decode(json_encode($sxe), TRUE);

		// on vire la pr�c�dente composition
		$this->del_componentOF($this->id);
		$tblfields=$arraydata['FactoryOFLines']['FactoryOFLine'];

		foreach ($tblfields as $fields) {
			$newArray=array();
			$newArray['fk_product'] = $fields['productid'];
			$newArray['ref'] = $fields['ref'];
			$newArray['label'] = $fields['label'];
			$newArray['price'] = $fields['price'];
			$newArray['qtyunit'] = $fields['qtyunit'];
			$newArray['description'] = $fields['description'];
			$newArray['qtyplanned'] = $fields['qtyplanned'];
			$newArray['fk_product_type'] = $fields['fk_product_type'];
			$newArray['ordercomponent'] = $fields['ordercomponent'];
			$this->createof_component($this->rowid, $this->qty_planned, $newArray);
		}

	}

	/**
	 *	Returns the label status
	 *
	 *	@param	  int		$mode
	 *	@param	  int		$noentities 0=use classic translation, 1=use noentities translation (for pdf print)
	 *	@return	 string	  		Label
	 */
	function getLibStatut($mode=0, $noentities=0)
	{
		return $this->LibStatut($this->statut, $mode, $noentities);
	}


	/**
	 *	Returns the label of a statut
	 *
	 *	@param	  int		$statut	 id statut
	 *	@param	  int		$mode
	 *	@return	 string	  		Label
	 */

	function LibStatut($statut, $mode=0, $noentities=0)
	{
		global $langs;

		if ($mode == 0) {
			if ($noentities == 0)
				return $langs->trans($this->statuts[$statut]);
			else
				return $langs->transnoentities($this->statuts[$statut]);
		}
		if ($mode == 1)
			return $langs->trans($this->statuts_short[$statut]);

		if ($mode == 2)
			return img_picto(
							$langs->trans($this->statuts_short[$statut]),
							$this->statuts_img[$statut]
			).' '.$langs->trans($this->statuts_short[$statut]);

		if ($mode == 3)
			return img_picto($langs->trans($this->statuts_short[$statut]), $this->statuts_img[$statut]);

		if ($mode == 4)
			return img_picto(
							$langs->trans($this->statuts_short[$statut]),
							$this->statuts_img[$statut]
			).' '.$langs->trans($this->statuts[$statut]);

		if ($mode == 5)
			return $langs->trans($this->statuts_short[$statut]).' '.img_picto(
							$langs->trans($this->statuts_short[$statut]), $this->statuts_img[$statut]
			);

	}

	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param		int			$withpicto		0=_No picto, 1=Includes the picto in the linkn, 2=Picto only
	 *	@return		string						String with URL
	 */
	function getNomUrl($withpicto=0)
	{
		global $langs;

		$result='';

		$lien = '<a href="'.dol_buildpath('/factory/fiche.php?id='.$this->id, 1).'"';

		$lienfin='</a>';

		$picto='factory@factory';

		$label=$langs->trans("Show").': '.$this->ref;

		$linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
		$linkclose.=' class="classfortooltip" >';
		if (! is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager=new HookManager($this->db);
		}
		$hookmanager->initHooks(array('factorydao'));
		$parameters=array('id'=>$this->id);
		// Note that $action and $object may have been modified by some hooks
		$reshook=$hookmanager->executeHooks('getnomurltooltip', $parameters, $this, $action);
		$linkclose = ($hookmanager->resPrint ? $hookmanager->resPrint : $linkclose);


		if ($withpicto) $result.=($lien.$linkclose.img_object($label, $picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$linkclose.$this->ref.$lienfin;
		return $result;
	}

	/**
	 *	Return clicable link of object (with eventually picto)
	 *
	 *	@param		int		$withpicto		Add picto into link
	 *	@param		string	$option			Where point the link
	 *	@param		int		$maxlength		Maxlength of ref
	 *	@return		string					String with URL
	 */
	function getNomUrlFactory($id, $withpicto=0, $option='', $maxlength=0, $productref='')
	{
		global $langs;
		global $conf;

		$result='';

		if ($option == 'index') {
			$lien = '<a href="'.dol_buildpath('/factory/product/', 1).'index.php?id='.$id.'">';
			$lienfin='</a>';
		} elseif ($option == 'fiche') {
			$lien = '<a href="'.dol_buildpath('/factory/product/', 1).'fiche.php?id='.$id.'">';
			$lienfin='</a>';
		} elseif ($option == 'direct') {
			$lien = '<a href="'.dol_buildpath('/factory/product/', 1).'direct.php?id='.$id.'">';
			$lienfin='</a>';
		} else {
			$lien = '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$id.'">';
			$lienfin='</a>';
		}
		if ($productref=='') {
			$tmpproduct = new Product($this->db);
			$tmpproduct->fetch($id);
			$productref=$tmpproduct->ref;
		}
		$newref=$productref;
		if ($maxlength)
			$newref=dol_trunc($newref, $maxlength, 'middle');

		if ($withpicto ) {
			if ($tmpproduct->type == 0)
				$result.=($lien.img_object(
								$langs->trans("ShowProduct").' '.$productref, 'product'
				).$lienfin.' ');
			if ($tmpproduct->type == 1)
				$result.=($lien.img_object(
								$langs->trans("ShowService").' '.$productref, 'service'
				).$lienfin.' ');
		}
		$result.=$lien.$newref.$lienfin;
		return $result;
	}

	/**
	 *	Return clicable link of object (with eventually picto)
	 *
	 *	@param		int		$withpicto		Add picto into link
	 *	@param		string	$option			Where point the link
	 *	@return		string					String with URL
	 */
	function PopupProduct($id, $idsecond="")
	{
		global $conf;

		$tmpproduct = new Product($this->db);
		$result='';
		$tmpproduct->fetch($id);
		if ($tmpproduct->is_photo_available($conf->product->multidir_output [$tmpproduct->entity])) {
			// pour g�rer le cas d'une m�me photo sur un meme document
			if ($idsecond)
				$id.="-".$idsecond;
			$result='<a id="trigger'.$id.'" >'.img_down().'</a>';
			$result.='<div id="pop-up'.$id.'"';
			$result.='style="display: none;  position: absolute;   ';
			$result.='padding: 2px;  background: #eeeeee;  color: #000000;  border: 1px solid #1a1a1a;" >';

			$result.=$tmpproduct->show_photos($conf->product->multidir_output [$tmpproduct->entity], 1, 1, 0, 0, 0, 80);
			$result.='</div>';
			$result.='<script>$(function() {';
			$result.="$('a#trigger".$id."').hover(function() {";
			$result.="$('div#pop-up".$id."').show();";
			$result.="},";
			$result.="function() {";
			$result.="$('div#pop-up".$id."').hide();";
			$result.="});   });";
			$result.='</script>';
		}
		return $result;
	}


	/**
	 *	Return clicable link of object (with eventually picto)
	 *
	 *	@param		int		$withpicto		Add picto into link
	 *	@param		string	$option			Where point the link
	 *	@param		int		$maxlength		Maxlength of ref
	 *	@return		string					String with URL
	 */
	function getUrlStock($id, $withpicto=0, $nbStock=0)
	{
		global $langs;

		$tmpproduct = new Product($this->db);
		$result='';
		$tmpproduct->fetch($id);

		$lien = '<a href="'.DOL_URL_ROOT.'/product/stock/product.php?id='.$id.'">';
		$lienfin='</a>';

		$result.=$lien.$nbStock.$lienfin;
		return $result;
	}


	/**
	 *  Return childs of product with if fk_parent
	 *
	 * 	@param		int		$fk_parent	Id of product to search childs of
	 *  @return	 array	   		Prod
	 */
	function getChildsArbo($fk_parent, $fk_factory =0, $maxlevel=0)
	{
	    global $langs;

		// si on est pas dans trop loin dans la r�cursivit�,
		if ($maxlevel > 1) {
            dol_syslog(get_class($this)."::getChildsArbo : ".$langs->trans("RecursivityLimitReached", $fk_parent), LOG_DEBUG);
			return array();
		}

		if ($fk_parent > 0) {
			$sql = "SELECT p.rowid, p.label as label, p.fk_product_type,";
			$sql.= " pf.qty as qty, pf.pmp as pmp, pf.price as price, pf.fk_product_children as id,";
			$sql.= " pf.globalqty as globalqty, pf.description as description, pf.ordercomponent";
			$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
			$sql.= ", ".MAIN_DB_PREFIX."product_factory as pf";
			$sql.= " WHERE p.rowid = pf.fk_product_children";
			$sql.= " AND pf.fk_product_father = ".$fk_parent;
			$sql.= " ORDER by pf.ordercomponent, p.ref";
		} else {
			$sql = "SELECT p.rowid, p.label as label, p.fk_product_type,";
			$sql.= " fd.qty_unit as qty, fd.pmp as pmp, fd.price as price, fd.fk_product as id,";
			$sql.= " fd.globalqty as globalqty, fd.description as description, 1 as ordercomponent";
			$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
			$sql.= ", ".MAIN_DB_PREFIX."factorydet as fd";
			$sql.= " WHERE p.rowid = fd.fk_product";
			$sql.= " AND fd.fk_factory = ".$fk_factory ;
			$sql.= " ORDER by p.ref";
		}

		$res  = $this->db->query($sql);
		if ($res) {
			$prods = array();
			while ($rec = $this->db->fetch_array($res)) {
				$prods[$rec['rowid']]= array(0=>$rec['id'],
								1=>$rec['qty'],
								2=>$rec['fk_product_type'],
								3=>$this->db->escape($rec['label']),
								4=>$rec['pmp'],
								5=>$rec['price'],
								6=>$rec['globalqty'],
								7=>$rec['description'],
								8=>$rec['ordercomponent'],
								9=>array()			// pour stocker les enfants sans fiche le basard
							);
				$listofchilds=$this->getChildsArbo($rec['id'], 0, $maxlevel++);
				foreach ($listofchilds as $keyChild => $valueChild)
					$prods[$rec['rowid']][9] = $valueChild;  // on stock les enfants dans le 6e tableau
			}
			return $prods;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}


    /**
     * @param $fkProductFather
     * @param $fkProductChildren
     * @param int $qty
     * @param int $pmp
     * @param int $price
     * @return int
     */
	public function addProduct($fkProductFather, $fkProductChildren, $qty = 0, $pmp = 0, $price = 0)
    {
        $sql  = "INSERT INTO " . MAIN_DB_PREFIX . "product_factory(";
        $sql .= "fk_product_father, fk_product_children, qty, pmp, price";
        $sql .= ") VALUES (";
        $sql .= $fkProductFather;
        $sql .= ", " . $fkProductFather;
        $sql .= ", " . $fkProductChildren;
        $sql .= ", '" . $qty . "''";
        $sql .= ", '" . $pmp . "''";
        $sql .= ", '" . $price . "''";
        $sql .= ")";

        $resql = $this->db->query($sql);

        if (!$resql) {
            dol_print_error($this->db);
            return -1;
        } else {
            return 1;
        }
    }


	/**
	 *  Return return true of false depending of product a composed or not
	 * 	@param		int		$fk_parent	Id of product to search childs of
	 *  @return	 array	   		Prod
	 */
	function is_FactoryProduct($fk_product)
	{
		// cas des produits saisies libres
		if ($fk_product == 0)
			return 0;

		$sql = "SELECT pf.fk_product_father";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_factory as pf";
		$sql.= " WHERE pf.fk_product_father = ".$fk_product;

		$res  = $this->db->query($sql);
		if ($res) {
			if ($this->db->num_rows($res) > 0)
				return 1;
			else
				return 0;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}


	function getChildsOF($fk_factory)
	{
		$sql = "SELECT fd.fk_product as id, p.label as label, fd.qty_unit as qtyunit, fd.qty_planned as qtyplanned,";
		$sql.= " fd.qty_used as qtyused, fd.qty_deleted as qtydeleted, fd.globalqty, fd.description";
		$sql.= ", fd.fk_mvtstockplanned as mvtstockplanned, fd.fk_mvtstockused as mvtstockused";
		$sql.= ", fd.pmp as pmp, fd.price as price, p.ref, p.fk_product_type";
        $sql.= ", fd.fk_entrepot as child_fk_entrepot";
        $sql.= ", fd.id_dispatched_line";
        $sql.= ", fd.indice_factory_build";
        $sql.= ", p.ref, p.fk_product_type";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql.= ", ".MAIN_DB_PREFIX."factorydet as fd";
		$sql.= " WHERE p.rowid = fd.fk_product";
		$sql.= " AND fd.fk_factory = ".$fk_factory;
		$sql.= " ORDER BY fd.ordercomponent";


		$res  = $this->db->query($sql);
		if ($res) {
			$prods = array();
			while ($rec = $this->db->fetch_array($res)) {
					$prods[]= array(
					    'id'                   => $rec['id'],                                    // Id product
                        'refproduct'           => $rec['ref'],                                   // label product
                        'label'                => $rec['label'],                                 // label product
                        'pmp'                  => $rec['pmp'],                                   // pmp of the product
                        'price'                => $rec['price'],                                 // price of the product
                        'price'                => $rec['price'],                                 // price of the product
                        'nb'                   => $rec['qtyunit'],                               // Nb of units that compose parent product
                        'globalqty'            => $rec['globalqty'],                             //
                        'description'          => $rec['description'],                           //
                        'qtyused'              => $rec['qtyused'],                               // Nb of units that compose parent product
                        'qtydeleted'           => $rec['qtydeleted'],                            // Nb of units that compose parent product
                        'qtyplanned'           => $rec['qtyplanned'],                            // Nb of units that compose parent product
                        'mvtstockplanned'      => $rec['mvtstockplanned'],                       // Nb of units that compose parent product
                        'mvtstockused'         => $rec['mvtstockused'],                          // Nb of units that compose parent product
                        'type'                 => $rec['fk_product_type'],                       // Nb of units that compose parent product
                        'child_fk_entrepot'    => $rec['child_fk_entrepot'],                     // Id of child warehouse
                        'id_dispatched_line'   => $rec['id_dispatched_line'],                    // Id of child warehouse
                        'dispatch_suffix'      => $rec['id'] . '_' . $rec['id_dispatched_line'], // unique key for dispatching
                        'indice_factory_build' => $rec['indice_factory_build']                   // indice of build factory
					);
			}
			return $prods;
		}

		dol_print_error($this->db);
		return -1;
	}


	function getChildsTasks($fk_project, $fk_task)
	{
		$sql = "SELECT ptd.fk_product as id, p.label as label, p.fk_product_type, ";
		$sql.= " pt.rowid as idtask, pt.ref as reftask, p.ref as refproduct, ";
		$sql.= " ptd.qty_planned as qtyplanned, ptd.qty_used as qtyused, ";
		$sql.= " ptd.qty_deleted as qtydeleted, ptd.pmp as pmp, ptd.price as price";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql.= ", ".MAIN_DB_PREFIX."projet_taskdet as ptd";
		$sql.= ", ".MAIN_DB_PREFIX."projet_task as pt";
		$sql.= " WHERE p.rowid = ptd.fk_product";
		$sql.= " AND pt.rowid = ptd.fk_task";
		if ($fk_task > 0)
			$sql.= " AND ptd.fk_task = ".$fk_task;
		else
			$sql.= " AND pt.fk_projet = ".$fk_project;
		$sql.= " ORDER BY p.ref, pt.ref";

		$res  = $this->db->query($sql);
		if ($res) {
			$prods = array();
			while ($rec = $this->db->fetch_array($res)) {
				$prods[]= array(
					'id'=>$rec['id'],					// Id product
					'refproduct'=>$rec['refproduct'],	// ref of  product
					'label'=>$rec['label'],				// label of product
					'idtask'=>$rec['idtask'],			// ref of task
					'reftask'=>$rec['reftask'],			// ref of task
					'pmp'=>$rec['pmp'],					// pmp of the product
					'price'=>$rec['price'],				// price of the product
					'nb'=>1,							// Nb of units that compose parent product
					'qtyplanned'=>$rec['qtyplanned'],	// Nb of units planned to use on build
					'qtyused'=>$rec['qtyused'],			// Nb of units realy used on build
					'qtydeleted'=>$rec['qtydeleted'],	// Nb of units deleted during ther build
					'type'=>$rec['fk_product_type']		// type of product (materiel or service)
				);
			}

			return $prods;
		}
		dol_print_error($this->db);
		return -1;
	}

	/**
	 *  Return childs of prodcut with if fk_parent
	 *
	 * 	@param		int		$fk_parent	Id of product to search childs of
	 *  @return	 array	   		Prod
	 */
	function clonefromvirtual()
	{
		$sql = "SELECT fk_product_fils, qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_association as pa";
		$sql.= " WHERE pa.fk_product_pere = ".$this->id;

		$res  = $this->db->query($sql);
		if ($res) {
			while ($rec = $this->db->fetch_array($res)) {
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'product_factory (fk_product_father, fk_product_children, qty)';
				$sql .= ' VALUES ('.$this->id.','.$rec['fk_product_fils'].','.$rec['qty'].')';
				if (! $this->db->query($sql)) {
					dol_print_error($this->db);
					return -1;
				}
			}

			// � la fin du transfert on supprime le param�trage du produit virtuel
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_association as pa";
			$sql.= " WHERE pa.fk_product_pere = ".$this->id;
			$res  = $this->db->query($sql);
			return 0;
		}
		dol_print_error($this->db);
		return -1;
	}

	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *	@param		int			$socid			Id of thirdparty
	 *	@return		int							New id of clone
	 */
	function createFromClone($productid=0, $qty_planned=0, $fk_entrepot=0 )
	{
		global $conf, $user, $langs, $hookmanager;

		$error=0;

		$this->context['createfromclone'] = 'createfromclone';
		$this->db->begin();

		// get extrafields so they will be clone
		//foreach ($this->lines as $line)
		//	$line->fetch_optionals($line->rowid);

		// Load source object
		$objFrom = dol_clone($this);

		// Change productid if needed
		if (!  $productid != $this->id) {
			$objproduct = new Product($this->db);

			if ($objproduct->fetch($productid)>0) {
				$this->fk_product	= $productid;
				$this->id			= $productid;
			}
		}

		// Change productid if needed
		if (!  $qty_planned != $this->qty_planned)
			$this->qty_planned 	= $qty_planned;

		if (!  $fk_entrepot != $this->fk_entrepot)
			$this->fk_entrepot 	= $fk_entrepot;

		$this->statut=0;

		// Clear fields
		$this->user_author_id	 = $user->id;
		$this->user_valid		 = '';
		$this->date_creation	  = '';
		$this->date_validation	= '';
		$this->ref_client		 = '';

		// Set ref
		dol_include_once("/factory/core/modules/factory/".$conf->global->FACTORY_ADDON.'.php');
		$obj = $conf->global->FACTORY_ADDON;
		$modFactory = new $obj;
		$this->ref = $modFactory->getNextValue($objproduct, $this);

		// Create clone
		$result=$this->createof();
		$cloneid=$result;
		if ($result < 0) $error++;

		if (! $error) {
			// Hook of thirdparty module
			if (is_object($hookmanager)) {
				$parameters=array('objFrom'=>$objFrom);
				$action='';
				// Note that $action and $object may have been modified by some hooks
				$reshook=$hookmanager->executeHooks('createFrom', $parameters, $this, $action);
				if ($reshook < 0) $error++;
			}

			// Call trigger
			if ($conf->global->MAIN_VERSION_LAST_UPGRADE > '3.6.0') {
				$restrigger=$this->call_trigger('FACTORY_CLONE', $user);
				if ($restrigger < 0) $error++;
			} else {
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('FACTORY_CLONE', $this, $user, $langs, $conf);
				if ($result < 0) {
					$error++; $this->errors=$interface->errors;
				}
			}
		}

		unset($this->context['createfromclone']);

		// End
		if (! $error) {
			$this->db->commit();
			return $cloneid;
		}

		 $this->db->rollback();
		return -1;
	}

	/**
	 *  Return childs of product with if fk_parent
	 *
	 * 	@param		$mode		0 : on modifie le prix de la composition  / 1 : on modifie le prix de l'of

	 *  @return	 array	   		Prod
	 */
	function getdefaultprice($mode=0)
	{
		global $conf;

		$sql = "SELECT p.rowid, p.pmp, p.price, p.fk_product_type";
		if (DOL_VERSION >= "3.9.0")
			$sql.= ", p.cost_price ";
		if ($mode == 0) {
			$sql.= " FROM ".MAIN_DB_PREFIX."product_factory as pf, ".MAIN_DB_PREFIX."product as p";
			$sql.= " WHERE pf.fk_product_children = p.rowid";
			$sql.= " and pf.fk_product_father = ".$this->id;
		} else {
			$sql.= " FROM ".MAIN_DB_PREFIX."factorydet as fd, ".MAIN_DB_PREFIX."product as p";
			$sql.= " WHERE fd.fk_product = p.rowid";
			$sql.= " and fd.fk_factory = ".$this->id;
		}
		$res  = $this->db->query($sql);
		if ($res) {
			// on boucle sur la liste des composants
			while ($rec = $this->db->fetch_array($res)) {
				$pmprice = 'null';
				if ($rec['fk_product_type'] == 0)
					$selpricemode = $conf->global->FACTORY_COMPONENT_BUYINGPRICE;
				else
					$selpricemode = $conf->global->FACTORY_COMPONENT_BUYINGPRICESERVICE;

				switch($selpricemode) {
					case 'pmpprice':
					case '':
						$pmprice = $rec['pmp']?price2num($rec['pmp']):'null';
						break;
					case 'costprice':
						$pmprice = $rec['cost_price']?price2num($rec['cost_price']):'null';
						break;
					case 'fournishmore':
					case 'fournishless':
						if ($selpricemode == 'fournishmore')
							$sql = "SELECT MAX(price) AS pricefourn";
						else	// r�cup du prix fournisseur le plus haut
							$sql = "SELECT MIN(price) AS pricefourn";
						$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price ";
						$sql.= " WHERE quantity=1 AND fk_product=".$rec['rowid'];
						$resfournishprice  = $this->db->query($sql);
						if ($resfournishprice) {
							$objfournishprice = $this->db->fetch_object($resfournishprice);
							$pmprice = $objfournishprice->pricefourn ?price2num($objfournishprice->pricefourn):'null';
						}
						break;
				}

				if ($mode == 0) {
					$sql = 'UPDATE '.MAIN_DB_PREFIX.'product_factory';
					$sql .= ' SET pmp= '.$pmprice ;
					$sql .= ' , price='.($rec['price']?price2num($rec['price']):'null');
					$sql .= ' where fk_product_father= '.$this->id ;
					$sql .= ' and fk_product_children= '.$rec['rowid'] ;
				} else {
					$sql = 'UPDATE '.MAIN_DB_PREFIX.'factorydet';
					$sql .= ' SET pmp= '.$pmprice ;
					$sql .= ' , price='.($rec['price']?price2num($rec['price']):'null');
					$sql .= ' where fk_factory= '.$this->id ;
					$sql .= ' and fk_product = '.$rec['rowid'] ;
				}

				if (! $this->db->query($sql)) {
					dol_print_error($this->db);
					return -1;
				}
			}
			return 0;
		} else {
			dol_print_error($this->db);
			return -1;
		}

	}


	function getdefaultpricetask($fk_task)
	{
		$sql = "SELECT p.rowid, p.pmp, p.price, p.fk_product_type";
		if (DOL_VERSION >= "3.9.0")
			$sql.= ", p.cost_price ";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet_taskdet as ptd, ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE ptd.fk_product = p.rowid";
		$sql.= " and ptd.fk_task = ".$fk_task;

		$res  = $this->db->query($sql);
		if ($res) {
			while ($rec = $this->db->fetch_array($res)) {
				$pmprice = 'null';
				if ($rec['fk_product_type'] == 0)
					$selpricemode = $conf->global->FACTORY_COMPONENT_BUYINGPRICE;
				else
					$selpricemode = $conf->global->FACTORY_COMPONENT_BUYINGPRICESERVICE;

				switch($selpricemode) {
					case 'pmpprice':
					case '':
						$pmprice = $rec['pmp']?price2num($rec['pmp']):'null';
						break;
					case 'costprice':
						$pmprice = $rec['cost_price']?price2num($rec['cost_price']):'null';
						break;
					case 'fournishmore':
					case 'fournishless':
						// r�cup du prix fournisseur le plus haut
						if ($selpricemode == 'fournishmore')
							$sql = "SELECT MAX(price) AS pricefourn";
						else	// r�cup du prix fournisseur le plus bas
							$sql = "SELECT MIN(price) AS pricefourn";
						$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price ";
						$sql.= " WHERE quantity=1 AND fk_product=".$rec['rowid'];
						$resfournishprice  = $this->db->query($sql);
						if ($resfournishprice) {
							$objfournishprice = $this->db->fetch_object($resfournishprice);
							$pmprice = $objfournishprice->pricefourn ?price2num($objfournishprice->pricefourn):'null';
						}
						break;
				}

				$sql = 'UPDATE '.MAIN_DB_PREFIX.'projet_taskdet';
				$sql .= ' SET pmp= '.$pmprice;
				$sql .= ' where fk_product='.$rec['rowid'];
				$sql .= ' and fk_task='. $fk_task;

				if (! $this->db->query($sql)) {
					dol_print_error($this->db);
					return -1;
				}
			}
			return 0;
		}
		dol_print_error($this->db);
		return -1;
	}

	/**
	 *  Return childs of product with if fk_parent
	 *
	 * 	@param		int		$fk_parent	Id of product to search childs of
	 *  @return	 array	   		Prod
	 */
	function updatefactoryprices($fk_product_children, $pmp=0, $price=0)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'product_factory';
		$sql .= ' SET pmp= "'.price2num($pmp).'", price="'.price2num($price) . '"';
		$sql .= ' where fk_product_father= "'.$this->id . '"';
		$sql .= ' and fk_product_children= "'.$fk_product_children . '"' ;
//print $sql."<br>";
		if (! $this->db->query($sql)) {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Return childs of product with if fk_parent
	 *
	 * 	@param		int		$fk_product	Id of product to search childs of
	 *  @return	 array	   		Prod
	 */
	function updateOFprices($fk_product, $pmp=0, $price=0)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'factorydet';
		$sql .= ' SET pmp= "'.price2num($pmp).'", price="'.price2num($price) . '"';
		$sql .= ' where fk_factory= "'.$this->id . '"';
		$sql .= ' and fk_product= "'.$fk_product . '"';
//print $sql."<br>";
		if (! $this->db->query($sql)) {
			dol_print_error($this->db);
			return -1;
		}
	}



	/**
	 *  Return childs of prodcut with if fk_parent
	 *
	 * 	@param		int		$fk_parent	Id of product to search childs of
	 *  @return	 array	   		Prod
	 */
	function updatefactorytaskprices($fk_task, $fk_product, $pmp, $price)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'projet_taskdet';
		$sql .= ' SET pmp='.($pmp?'"' . $pmp . '"':'null').', price='.($price?'"' . $price . '"':'null');
		$sql .= ' where fk_task= "'.$fk_task .'"';
		$sql .= ' and fk_product= "'.$fk_product . '"';
//print $sql."<br>";
		if (! $this->db->query($sql)) {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Return childs of product with if fk_parent
	 *
	 * 	@param		int		$fk_parent	Id of product to search childs of
	 *  @return	 array	   		Prod
	 */
	function updatefactorytaskqty($fk_task, $fk_product, $qtyused, $qtydeleted)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'projet_taskdet';
		$sql .= ' SET qty_used='.($qtyused ? '"' . $qtyused . '"' : 'null').', qty_deleted='.($qtydeleted ? '"' . $qtydeleted . '"' : 'null');
		$sql .= ' where fk_task= "'.$fk_task . '"';
		$sql .= ' and fk_product= "'.$fk_product . '"';
//print $sql."<br>";
		if (! $this->db->query($sql)) {
			dol_print_error($this->db);
			return -1;
		}
	}


	function get_equipement_linked($equipementid)
	{
		$sql = "SELECT fk_factory";
		$sql.= " FROM ".MAIN_DB_PREFIX."equipement_factory as ef";
		$sql.= " WHERE ef.fk_equipement = ".$equipementid;

		$res = $this->db->query($sql);
		if ($res) {
			$obj = $this->db->fetch_object($res);
			return $obj->fk_factory;
		}
		return 0;
	}

	/**
	 * 	Return tree of all subproducts for product. Tree contains id, name and quantity.
	 * 	Set this->sousprods
	 *
	 *  @return		void
	 */

	function get_sousproduits_factory_arbo($factoryid)
	{
		$this->sousprods["fab"][0]=$this->id;
		$sql = "SELECT fd.fk_product as id, fd.qty_unit, p.label, p.fk_product_type";
		$sql.= " FROM ".MAIN_DB_PREFIX."factorydet as fd";
		$sql.= " , ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE fd.fk_product = p.rowid";
		$sql.= " AND fd.fk_factory = ".$factoryid;

		$resql=$this->db->query($sql);
		if ($resql) {
			$nump = $this->db->num_rows($resql);
			if ($nump) {
				$i = 0;
				while ($i < $nump) {
					$obj = $this->db->fetch_object($resql);

					$this->sousprods["fab"][$obj->id]= array(0=>$obj->id,
											1=>$obj->qty_unit,
											2=>$obj->fk_product_type,
											3=>$this->db->escape($obj->label)
								);
					$i++;
				}
			}
		}
	}


	function get_sousproduits_arbo()
	{
		$parent = $this->getParent();
		foreach ($parent as $key => $value)
			foreach ($this->getChildsArbo($value[0]) as $keyChild => $valueChild)
				$parent[$key][$keyChild] = $valueChild;

		foreach ($parent as $key => $value)
			$this->sousprods[$key] = $value;

	}

	public function set_datestartmade($user, $datestartmade)
	{
        $error = 0;

		// c'est lors de la premiere validation que l'on effectue les mouvements de stocks des composants
		if ($user->rights->factory->creer) {
		    $this->db->begin();

		    $sql  = "UPDATE " . MAIN_DB_PREFIX . "factory SET";
			$sql .= ' date_start_made = ' . ($datestartmade ? '"' . $this->db->idate($datestartmade) . '"' : 'null');
			$sql .= ', fk_statut ="' . ($datestartmade ? '1' : '0') . '"';
			$sql .= " WHERE rowid = " . $this->id;

			if (! $this->db->query($sql)) {
                $error++;
			    $this->error = $this->db->lasterror();
                $this->errors[] = $this->error;
                dol_syslog(__METHOD__ ." Erreur SQL=" . $this->error, LOG_ERR);
			} else {
                $this->date_start_made = $datestartmade;
                if ($datestartmade) {
                    $this->statut = 1;
                } else {
                    $this->statut = 0;
                }
			}

			// commit or rollback
			if ($error) {
                $this->db->rollback();
            } else {
                $this->db->commit();
            }
		}

        if ($error) {
            return -1;
        } else {
            return 1;
        }
	}

	function set_datestartplanned($user, $datestartplanned)
	{
		global $conf;

		if ($user->rights->factory->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."factory ";
			$sql.= ' SET date_start_planned = '.($datestartplanned? '"' . $this->db->idate($datestartplanned) . '"' :'null');
			$sql.= ' WHERE rowid = "'.$this->id . '"';

			if ($this->db->query($sql)) {
				$this->date_start_planned = $datestartplanned;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog(get_class($this)."::set_datestartplanned Erreur SQL ".$this->error, LOG_ERR);
				return -1;
			}
		}
	}

	function set_dateendplanned($user, $dateendplanned)
	{
		global $conf;

		if ($user->rights->factory->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."factory ";
			$sql.= ' SET date_end_planned = '.($dateendplanned? '"'. $this->db->idate($dateendplanned).'"' :'null');
			$sql.= ' WHERE rowid = "'.$this->id . '"';

			if ($this->db->query($sql)) {
				$this->date_end_planned = $dateendplanned;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog(get_class($this)."::set_dateendplanned Erreur SQL ".$this->error, LOG_ERR);
				return -1;
			}
		}
	}

	function set_durationplanned($user, $durationplanned)
	{
		global $conf;

		if ($user->rights->factory->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."factory ";
			$sql.= ' SET duration_planned = '.($durationplanned ? '"' . $durationplanned . '"' :'null');
			$sql.= ' WHERE rowid = "'.$this->id . '"';

			if ($this->db->query($sql)) {
				$this->duration_planned = $durationplanned;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog(get_class($this)."::set_durationplanned Erreur SQL ".$this->error, LOG_ERR);
				return -1;
			}
		}
	}
	function set_description($user, $description)
	{
		global $conf;

		if ($user->rights->factory->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."factory ";
			$sql.= ' SET description = "'.$this->db->escape($description).'"';
			$sql.= ' WHERE rowid = "'.$this->id . '"';

			if ($this->db->query($sql)) {
				$this->description = $description;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog(get_class($this)."::set_description Erreur SQL ".$this->error, LOG_ERR);
				return -1;
			}
		}
	}
	function set_entrepot($user, $fk_entrepot)
	{
		global $conf;

		if ($user->rights->factory->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."factory ";
			$sql.= ' SET fk_entrepot = "'.$fk_entrepot . '"';
			$sql.= ' WHERE rowid = "'.$this->id . '"';

			if ($this->db->query($sql)) {
				$this->fk_entrepot = $fk_entrepot;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog(get_class($this)."::set_description Erreur SQL ".$this->error, LOG_ERR);
				return -1;
			}
		}
	}
	function set_qtyplanned($user, $qty_planned)
	{
		global $conf;

		if ($user->rights->factory->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."factory ";
			$sql.= ' SET qty_planned = '.($qty_planned?'"' . $qty_planned . '"':'null');
			$sql.= ' WHERE rowid = "'.$this->id . '"';

			if ($this->db->query($sql)) {
				$this->qty_planned = $qty_planned;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog(get_class($this)."::set_description Erreur SQL ".$this->error, LOG_ERR);
				return -1;
			}
		}
	}
	/**
	 *  Return list of contacts emails or mobile existing for third party
	 *
	 *  @param	string	$mode	   		'email' or 'mobile'
	 * 	@param	int		$hidedisabled		1=Hide contact if disabled
	 *  @return array	   				Array of contacts emails or mobile
	*/
	function contact_entrepot_email_array($mode='email', $hidedisabled=0)
	{
		$contact_property = array();

		// r�cup�ration des contacts soci�t� associ� � l'entrepot
		$sql = "SELECT s.rowid, s.email, s.statut, s.lastname, s.firstname";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as s";
		$sql.= " ,".MAIN_DB_PREFIX."element_contact as ec";
		$sql.= " ,".MAIN_DB_PREFIX."c_type_contact  as tc";
		$sql.= " WHERE ec.element_id= ".$this->fk_entrepot;
		$sql.= " AND ec.fk_c_type_contact = tc.rowid";
		$sql.= " AND ec.fk_socpeople = s.rowid";
		$sql.= " AND tc.element =  'stock'";
		$sql.= " AND tc.source = 'external'";
		$sql.= " AND tc.active =1";
		$resql=$this->db->query($sql);
		if ($resql) {
			$nump = $this->db->num_rows($resql);
			if ($nump) {
				$i = 0;
				while ($i < $nump) {
					$obj = $this->db->fetch_object($resql);

					// Show all contact. If hidedisabled is 1, showonly contacts with status = 1
					if ($obj->statut == 1 || empty($hidedisabled)) {
						$contact_property["E-".$obj->rowid] = trim(dolGetFirstLastname($obj->firstname, $obj->lastname));
						$contact_property["E-".$obj->rowid].= " &lt;".$obj->email."&gt;";
					}
					$i++;
				}
			}
		} else
			dol_print_error($this->db);

		// r�cup�ration des contacts interne
		$sql = "SELECT s.rowid, s.email, s.statut, s.lastname, s.firstname";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as s";
		$sql.= " ,".MAIN_DB_PREFIX."element_contact as ec";
		$sql.= " ,".MAIN_DB_PREFIX."c_type_contact  as tc";
		$sql.= " WHERE ec.element_id= ".$this->fk_entrepot;
		$sql.= " AND ec.fk_c_type_contact = tc.rowid";
		$sql.= " AND ec.fk_socpeople = s.rowid";
		$sql.= " AND tc.element =  'stock'";
		$sql.= " AND tc.source = 'internal'";
		$sql.= " AND tc.active =1";
		$resql=$this->db->query($sql);
		if ($resql) {
			$nump = $this->db->num_rows($resql);
			if ($nump) {
				$i = 0;
				while ($i < $nump) {
					$obj = $this->db->fetch_object($resql);

					// Show all contact. If hidedisabled is 1, showonly contacts with status = 1
					if ($obj->statut == 1 || empty($hidedisabled)) {
						$contact_property["I-".$obj->rowid] = trim(dolGetFirstLastname($obj->firstname, $obj->lastname));
						$contact_property["I-".$obj->rowid].= " &lt;".$obj->email."&gt;";
					}
					$i++;
				}
			}
		} else {
			dol_print_error($this->db);
		}
		return $contact_property;
	}

	function createmvtproject($projectid, $productid, $entrepotid, $qtylefted, $idmvt=-1)
	{
		global $user;

		$pmp=0;
		$price=0;

		// on r�cup�re le pmp et le price pour une utilisation juste des prix
		$sql = "SELECT p.rowid, p.pmp, p.price";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE p.rowid=".$productid;

		$resql  = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$pmp=$obj->pmp;
			$price=$obj->price;
		}

		// et on conserve le mouvement
		$sql = "insert into ".MAIN_DB_PREFIX."projet_stock";
		$sql.= " (fk_project, fk_product, fk_entrepot, qty_from_stock, date_creation, fk_user_author,";
		$sql.= " pmp, price, fk_product_stock)";
		$sql.= " values (".$projectid.", ".$productid.", ".$entrepotid.", ".$qtylefted;
		$sql.= ", '".$this->db->idate(dol_now())."'"; // date de cr�ation aliment� automatiquement
		$sql.= ", ".$user->id;
		$sql.= ", ".$pmp.", ".$price.", ".$idmvt.")";
		//print $sql;
		$this->db->query($sql);

	}

	function getProductsListFromOrders()
	{
		$tblof = array();

		// les commandes factorisable sont celle valid� non trait� (statut � 1)
		// qui n'on pas �t� associ� � un OF
		$sql = 'SELECT cd.rowid, cd.fk_commande, cd.fk_product, cd.qty';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'commande as c, '.MAIN_DB_PREFIX.'commandedet as cd';
		$sql.= ' WHERE c.fk_statut = 1 AND cd.fk_product > 0 AND cd.product_type = 0 AND c.rowid=cd.fk_commande';
		$sql.= ' AND c.rowid not in (SELECT el.fk_source FROM '.MAIN_DB_PREFIX.'element_element as el';
		$sql.= ' WHERE el.sourcetype="commande" AND  el.targettype="factory")';
		$sql.= ' ORDER BY cd.fk_product, c.rowid ';

		$resql=$this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			// on constitue la liste des produits � fabriquer
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				// si le produit est fabricable
				if ($this->is_FactoryProduct($obj->fk_product) > 0) {
					// on ajoute au tableau ( la ligne de la commande est la cl�e unique)
					$tblof[] = array ('fk_product' => $obj->fk_product,
												'fk_commande' => $obj->fk_commande,
												'qty' => $obj->qty);
				}
				$i++;
			}
		}
		return $tblof;
	}
}
