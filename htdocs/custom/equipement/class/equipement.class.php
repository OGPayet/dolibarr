<?php
/* Copyright (C) 2012-2017	Charlene Benke	<charlie@patas-monkey.com>
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
 * 	\file	   htdocs/equipement/class/equipement.class.php
 * 	\ingroup	equipement
 * 	\brief	  Fichier de la classe des gestion des �quipements
 */
require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
 * 	\class	  Equipement
 *	\brief	  Classe des gestion des equipements
 */
class Equipement extends CommonObject
{
    const EQUIPEMENT_ETAT_CODE_LOST = 'LOST';

	public $element='equipement';
	public $table_element='equipement';
	public $fk_element='fk_equipement';
	public $table_element_line='equipementevt';

    /**
     * Array of whitelist of properties keys for this object used for the API
     * @var  array
     *      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static public $API_WHITELIST_OF_PROPERTIES = array(
        "id" => '', "ref" => '', "fk_product" => '', "product" => '', "fk_product_batch" => '', "numimmocompta" => '',
        "numversion" => '', "fk_soc_fourn" => '', "fk_commande_fourn" => '', "fk_commande_fournisseur_dispatch" => '',
        "fk_fact_fourn" => '', "ref_fourn" => '', "fk_soc_client" => '', "fk_fact_client" => '', "fk_etatequipement" => '',
        "etatequiplibelle" => '', "fk_entrepot" => '', "isentrepotmove" => '', "unitweight" => '', "SerialMethod" => '',
        "quantity" => '', "nbCreateEquipement" => '', "fk_factory" => '', "datec" => '', "dateo" => '', "datee" => '',
        "dated" => '', "statut" => '', "localisation" => '', "description" => '', "note_public" => '', "extraparams" => '',
        "array_options" => '', "socid" => '', "datev" => '', "datem" => '',"linkedObjectsIds" => ''
    );

    /**
     * Array of whitelist of properties keys for this object when is a linked object used for the API
     * @var  array
     *      if empty array then equal at $api_whitelist_of_properties
     *      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static public $API_WHITELIST_OF_PROPERTIES_LINKED_OBJECT = array(
    );

    /**
     * Array of blacklist of properties keys for this object used for the API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get blacklist of his object element
     *      if property is a object and this properties_name value is a array then get blacklist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get blacklist set in the array
     */
    static protected $API_BLACKLIST_OF_PROPERTIES = array(
    );

    /**
     * Array of blacklist of properties keys for this object when is a linked object used for the API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get blacklist of his object element
     *      if property is a object and this properties_name value is a array then get blacklist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get blacklist set in the array
     */
    static protected $API_BLACKLIST_OF_PROPERTIES_LINKED_OBJECT = array(
    );

	var $id;					// ID de l'�quipement
	var $ref;					// num�ro de s�rie unique pour l'�quipement
	var $fk_product;			// ID du produit
    var $product;               // Product fetched by fetch_product()
	var $fk_product_batch;		// num�ro de lot
	var $numimmocompta;			// num�ro de compte immo pour les recherches
	var $numversion; 			// num�ro de version associ� au produit
	var $fk_soc_fourn;			// ID du fournisseur du produit
	var $fk_commande_fourn;		// ID commande fournisseur du produit
    var $fk_commande_fournisseur_dispatch;		// ID commande fournisseur dispatch du produit
    var $fk_fact_fourn;			// ID fact fournisseur du produit
	var $ref_fourn;				// r�f�rence produit du fournisseur (non stock�e en base, juste pour la g�n�ration multiple)
	var $fk_soc_client;			// Id client du produit
	var $fk_fact_client;		// ID fact client du produit
	var $fk_contact;			// contact � qui est rattach� l'�quipement si besoin (sert accessoirement � sa localisation)
	var $client;				// Objet societe client (a charger par fetch_client)
	var $fk_etatequipement;		// �tat de l'�quipement
	var $etatequiplibelle;		// �tat de l'�quipement (lib�ll�
	var $fk_entrepot;			// entrepot de l'�quipement chez soit
	var $isentrepotmove;		// on effectue un mouvement de stock oui/non

	var $unitweight;			// poids unitaire de l'�quipement
	var $SerialMethod;			// M�thode de g�n�ration des num�ros de s�rie
	var $quantity;				// Quantit� de produit en cas de gestion par lot
	var $nbCreateEquipement; 	// nombre d'�quipement � cr�er
	var $fk_factory;			// ID de la fabrication

	var $author;
	var $datec;		// date cr�ation de l'�quipement
	var $dateo;		// date de d�but de l'�quipement
	var $datee;		// date de fin de l'�quipement
	var $dated;		// date de DLUO de l'�quipement

	var $barcode;				// value
	var $barcode_type;			// id
	var $barcode_type_code;		// code (loaded by equipement_fetch_barcode)
	var $barcode_type_label;	// label (loaded by equipement_fetch_barcode)
	var $barcode_type_coder;	// coder (loaded by equipement_fetch_barcode)

	var $statut;				// 0=draft, 1=validated, 2=closed
	var $localisation;
	var $description;
	var $note_private;
	var $note_public;

	var $model_pdf;

	var $extraparams=array();

	var $lines = array();

	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db ;
		$this->statut = 0;

		// List of language codes for status
		$this->statuts[0]='Draft';
		$this->statuts[1]='Validated';
		$this->statuts[2]='Closed';
		$this->statuts_short[0]='Draft';
		$this->statuts_short[1]='Validated';
		$this->statuts_short[2]='Closed';
		$this->statuts_image[0]='statut0';
		$this->statuts_image[1]='statut4';
		$this->statuts_image[2]='statut6';
	}


    /**
     * Create an equipement into data base
     *
     * @param   int     [=0] $notrigger
     * @return  int     <0 if KO, >0 if OK
     *
     * @throws Exception
     */
	function create($notrigger=0)
	{
		global $conf, $user, $soc, $langs;

		$error = 0;

		dol_syslog(__METHOD__ . " ref=" . $this->ref);

		// Check parameters
		if ($this->fk_product <= 0) {
			$this->error = 'ErrorBadParameterForFunc';
			dol_syslog(__METHOD__ . " " . $this->error, LOG_ERR);
			return -1;
		}

		$now=dol_now();

		// en mode serialisation externe, on determine le nombre de numeros de series transmis
		if ($this->SerialMethod == 2) {
			$separatorlist=$conf->global->EQUIPEMENT_SEPARATORLIST;
			$separatorlist =($separatorlist ? $separatorlist : ";");
			if ($separatorlist == "__N__")
				$separatorlist="\n";
			if ($separatorlist == "__B__")
				$separatorlist="\b";

			$tblSerial=explode($separatorlist, $this->SerialFourn);

			$nbCreateSerial=count($tblSerial);

			// calculate with list of serial number
			if ($this->nbAddEquipement == 0) {
                // si on a des ref, on determine le nombre d'equipements a creer
                $this->nbAddEquipement = $nbCreateSerial;
            } else {
			    // check qty with list of serial number
                if ($this->nbAddEquipement != $nbCreateSerial) {
                    $this->error = $langs->trans('EquipementErrorNbAddEquipement');
                    $this->errors[] = $this->error;
                    return -1;
                }
            }

			dol_syslog(__METHOD__ . " SerialMethod=" . $this->SerialMethod . "nb2create=" . $nbCreateSerial);
		}

        $this->db->begin();

		$i=0;
		// boucle sur les numeros de serie pour creer autant d'equipement
		while ($this->nbAddEquipement > $i) {
			// recup de la ref suivante
			$this->date = dol_now();

			// si on est en mode code fournisseur
			switch($this->SerialMethod) {
				case 1 : // en mode generation auto, on cree des numeros serie interne
					$obj = $conf->global->EQUIPEMENT_ADDON;
					$modequipement = new $obj;
					$numpr = $modequipement->getNextValue($soc, $this);
					break;

				case 2 : // on recupere le numero de serie dans la liste fournis
					// attention on peut ne recuperer qu'un bout du numero
					if ($conf->global->EQUIPEMENT_BEGINKEYSERIALLIST != 0)
						$numpr=substr($tblSerial[$i], $conf->global->EQUIPEMENT_BEGINKEYSERIALLIST);
					else
						$numpr=$tblSerial[$i];
					break;

				case 3 :  // en mode serie par lot, on reprend le numero de lot comme numero de serie
					// si en mode decoupage on recupere la ref, en creation on recupere le numero de lot
					if ($this->ref)
						$numpr=$this->ref;
					else
						$numpr=$this->numversion;
					break;
			}

			// si il a un soucis avec la ref, on ne cre pas l'equipement
			if ($numpr) {
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."equipement (";
				$sql.= "fk_product";
				$sql.= ", fk_soc_client";
				$sql.= ", fk_soc_fourn";
				$sql.= ", fk_commande_fourn";
                $sql.= ", fk_commande_fournisseur_dispatch";
                $sql.= ", fk_facture_fourn";
				$sql.= ", datec";
				$sql.= ", datee";
				$sql.= ", dated";
				$sql.= ", dateo";
				$sql.= ", ref";
				$sql.= ", numversion";
				$sql.= ", quantity";
				$sql.= ", unitweight";
				$sql.= ", entity";
				$sql.= ", fk_user_author";
				$sql.= ", fk_entrepot";
				$sql.= ", fk_product_batch";
				$sql.= ", description";
				$sql.= ", fk_etatequipement";
				$sql.= ", fk_statut";
				$sql.= ", note_private";
				$sql.= ", note_public";
				$sql.= ", model_pdf";
				$sql.= ") ";
				$sql.= " VALUES ( ".$this->fk_product;
				$sql.= ", ".($this->fk_soc_client?$this->db->escape($this->fk_soc_client):"null");
				$sql.= ", ".($this->fk_soc_fourn?$this->db->escape($this->fk_soc_fourn):"null");
				$sql.= ", ".($this->fk_commande_fourn?$this->db->escape($this->fk_commande_fourn):"null");
                $sql.= ", ".($this->fk_commande_fournisseur_dispatch?$this->db->escape($this->fk_commande_fournisseur_dispatch):"null");
                $sql.= ", ".($this->fk_facture_fourn?$this->db->escape($this->fk_facture_fourn):"null");
				$sql.= ", '".$this->db->idate($now)."'";
				$sql.= ", ".($this->datee?"'".$this->db->idate($this->datee)."'":"null");
				$sql.= ", ".($this->dated?"'".$this->db->idate($this->dated)."'":"null");
				$sql.= ", ".($this->dateo?"'".$this->db->idate($this->dateo)."'":"null");
				$sql.= ", '" . $this->db->escape(trim($numpr)) . "'";
				$sql.= ", '".$this->numversion."'";
				$sql.= ", ".$this->quantity;
				$sql.= ", ".($this->unitweight?$this->unitweight:"null");
				$sql.= ", ".$conf->entity;
				$sql.= ", ".$this->author;
				$sql.= ", ".($this->fk_entrepot?$this->db->escape($this->fk_entrepot):"null");
				$sql.= ", ".($this->fk_product_batch?$this->db->escape($this->fk_product_batch):"null");
				$sql.= ", ".($this->description?"'".$this->db->escape($this->description)."'":"null");
				$sql.= ", ".($this->fk_etatequipement?$this->db->escape($this->fk_etatequipement):"null");
				$sql.= ", ".($this->fk_entrepot>0?"1":"0"); // si il y a un entrepot de s�lectionn� on active ou non l'�quipement
				$sql.= ", ".($this->note_private?"'".$this->db->escape($this->note_private)."'":"null");
				$sql.= ", ".($this->note_public?"'".$this->db->escape($this->note_public)."'":"null");
				$sql.= ", ".($this->model_pdf?"'".$this->db->escape($this->model_pdf)."'":"null");
				$sql.= ")";
				dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
				$result=$this->db->query($sql);

				if ($result) {
					$i++;
					$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX."equipement");

					// si on veut faire un mouvement correspondant � la cr�ation
					// et que l'on utilise pas product batch
					if ($this->isentrepotmove
						&& $this->fk_entrepot > 0
						&& $this->fk_product_batch != -1) {
						require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
						$mouvP = new MouvementStock($this->db);
						$mouvP->origin = new Equipement($this->db);
						$mouvP->origin->id = $this->id;

                        $result = $mouvP->reception($user, $this->fk_product, $this->fk_entrepot, $this->quantity, 0, $langs->trans("EquipementMoveIn"));
                        if ($result < 0) {
                            $error++;
                            $this->error = $mouvP->error;
                            $this->errors[] = $this->error;
                        }
					}

					if (!$error && !$notrigger) {
						// Call trigger
						$result=$this->call_trigger('EQUIPEMENT_CREATE', $user);
						if ($result < 0) $error++;
						// End call triggers
					}
				} else {
                    $error++;
                    if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

                        $fournSocieteStatic = new Societe($this->db);
                        $fournSocieteStatic->fetch($this->fk_soc_fourn);
                        $this->error = $langs->trans('EquipementErrorAlreadyExists', $numpr, $fournSocieteStatic->getFullName($langs));
                    } else {
                        $this->error = $this->db->lasterror();
                    }
                    $this->errors[] = $this->error;
				}

                if (! $error) {
                    // si factory est pr�sent on v�rifie si il est n�cessaire de cr�er la liaison
                    if ($conf->global->MAIN_MODULE_FACTORY && $this->fk_factory > 0 ) {
                        // on ajoute la liaison avec l'of
                        $sql = "INSERT INTO ".MAIN_DB_PREFIX."equipement_factory (fk_equipement, fk_factory) ";
                        $sql.= " values (".$this->id.", ".$this->fk_factory.")";
                        $result=$this->db->query($sql);

                        // on cr�e le lien vers l'of
                        $this->add_object_linked('factory', $this->fk_factory);
                    }
                } else {
                    break;
                }
			} else {
			    $error++;
                $this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Ref"));
                $this->errors[] = $this->error;
                dol_syslog(__METHOD__ . "Error : " . $this->error, LOG_ERR);
                break;
            }
		}

		// commit or rollback
        if (! $error) {
            $this->db->commit();
        } else {
            dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
            $this->db->rollback();
        }

		// si on est all� jusqu'� la fin des cr�ation
		if ($this->nbAddEquipement == $i) // on se positionne sur le dernier cr�e en modif
			return $this->id;
		else
			return -1; // sinon on revient � la case d�part
	}

	/**
	 *	Fetch a equipement
	 *
	 *	@param		int		$rowid		Id of equipement
	 *	@param		string	$ref		Ref of equipement
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function fetch($rowid, $ref='')
	{
		$sql = "SELECT e.rowid, e.ref, e.description, e.fk_soc_fourn, e.fk_commande_fourn, e.fk_commande_fournisseur_dispatch, e.fk_facture_fourn, e.fk_statut, e.fk_entrepot,";
		$sql.= " e.numversion, e.numimmocompta, e.fk_etatequipement, ee.libelle as etatequiplibelle, e.quantity,";
		$sql.= " e.datec, e.datev, e.datee, e.dateo, e.dated, e.tms as datem, e.unitweight, e.fk_product_batch,";
		$sql.= " e.fk_product, e.fk_soc_client, e.fk_facture,";
		$sql.= " e.note_public, e.note_private ";
		$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as ee on e.fk_etatequipement = ee.rowid";
		if ($ref) $sql.= " WHERE e.ref='".$this->db->escape($ref)."'";
		else $sql.= " WHERE e.rowid=".$rowid;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->rowid			= $obj->rowid;
				$this->id				= $obj->rowid;
				$this->ref				= $obj->ref;
				$this->description  	= $obj->description;
				$this->socid			= $obj->fk_soc_client; //$obj->fk_soc;
				$this->statut			= $obj->fk_statut;
				$this->numversion		= $obj->numversion;
				$this->quantity			= $obj->quantity;
				$this->unitweight		= price($obj->unitweight);
				$this->numimmocompta	= $obj->numimmocompta;
				$this->dateo			= $this->db->jdate($obj->dateo);
				$this->datee			= $this->db->jdate($obj->datee);
				$this->dated			= $this->db->jdate($obj->dated);
				$this->datec			= $this->db->jdate($obj->datec);
				$this->datev			= $this->db->jdate($obj->datev);
				$this->datem			= $this->db->jdate($obj->datem);
				$this->fk_product		= $obj->fk_product;
				$this->fk_soc_fourn		= $obj->fk_soc_fourn;
				$this->fk_commande_fourn = $obj->fk_commande_fourn;
                $this->fk_commande_fournisseur_dispatch = $obj->fk_commande_fournisseur_dispatch;
                $this->fk_fact_fourn	= $obj->fk_facture_fourn;
				$this->fk_soc_client	= $obj->fk_soc_client;
				$this->fk_fact_client	= $obj->fk_facture;
				$this->fk_entrepot		= $obj->fk_entrepot;
				$this->fk_product_batch	= $obj->fk_product_batch;
				$this->fk_etatequipement= $obj->fk_etatequipement;
				$this->etatequiplibelle	= $obj->etatequiplibelle;
				$this->note_public		= $obj->note_public;
				$this->note_private		= $obj->note_private;
				$this->model_pdf		= $obj->model_pdf;
				$this->fulldayevent 	= $obj->fulldayevent;

				$this->extraparams	= (array) json_decode($obj->extraparams, true);

				if ($this->statut == 0)
					$this->brouillon = 1;

				/*
				 * r�cup�ration des Lines
				 */
				$result=$this->fetch_lines();
				if ($result < 0)
					return -3;

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
     * Find a product added to component list
     *
     * @param   int         $fkProduct        Id product
     * @return  resource    Resource SQL
     */
    public function findProductAdd($fkProduct)
    {
        $sql  = "SELECT rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "equipement_productadd";
        $sql .= " WHERE fk_equipement = " . $this->id;
        $sql .= " AND fk_product = " . $fkProduct;

        return $this->db->query($sql);
    }


    /**
     * Find a product added to component list
     *
     * @return  resource    Resource SQL
     */
    public function findAllProductAdd()
    {
        $sql  = "SELECT rowid, fk_product, qty";
        $sql .= " FROM " . MAIN_DB_PREFIX . "equipement_productadd";
        $sql .= " WHERE fk_equipement = " . $this->id;

        return $this->db->query($sql);
    }


    /**
     * Get a list of added products (uses the same structure as prodsArbo)
     *
     * @return  array   List of added products
     */
    private function _getAllProductAddListForProdsArbo()
    {
        $productAddList = array();

        $res = $this->findAllProductAdd();

        if ($res) {
            while ($productAdd = $this->db->fetch_object($res)) {
                $productAddList[] = array('id' => $productAdd->fk_product, 'type' => 0, 'nb' => $productAdd->qty);
            }
        }

        return $productAddList;
    }


    /**
     * Merge prods arbo with added products list of the quipement
     *
     * @param   array   $prodsArbo      Prods arbo list
     * @return  array   List of prods arbo with added products
     */
    public function mergeProdsArboWithProductAddList($prodsArbo)
    {
        $prodsArboWithProductAddList = $prodsArbo;

        $productAddList = $this->_getAllProductAddListForProdsArbo();

        foreach ($productAddList as $keyProductAdd => $productAdd) {
            $isFound = 0;

            foreach ($prodsArbo as $keyProdsArbo => $value) {
                // product id found
                if ($productAdd['id'] == $value['id']) {
                    $isFound = 1;
                    $prodsArboWithProductAddList[$keyProdsArbo]['nb'] += $productAdd['nb'];
                    break;
                }
            }

            // product id not found
            if ($isFound === 0) {
                $prodsArboWithProductAddList[] = $productAddList[$keyProductAdd];
            }
        }

        return $prodsArboWithProductAddList;
    }


    /**
     * Insert product to component list (product add)
     *
     * @param   int     $fk_product                 Id product
     * @param   int     $qty                        Quantity
     * @return  int     < 0 if KO, > 0 if OK
     */
    public function createProductAdd($fkProduct, $qty)
    {
        $sql  = "INSERT INTO " . MAIN_DB_PREFIX . "equipement_productadd (";
        $sql .= "fk_equipement, fk_product, qty";
        $sql .= ") VALUES (";
        $sql .= $this->id;
        $sql .= ", " . $fkProduct;
        $sql .= ", " . $qty;
        $sql .= ")";

        if (!$this->db->query($sql)) {
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Update product qty to component list (product add)
     *
     * @param   int     $fk_product                 Id product
     * @param   int     $qty                        Quantity
     * @return  int     < 0 if KO, > 0 if OK
     */
    public function updateProductAdd($fkProduct, $qty)
    {
        $sql  = "UPDATE " . MAIN_DB_PREFIX . "equipement_productadd";
        $sql .= " SET qty = qty + " . $qty;
        $sql .= " WHERE fk_equipement = " . $this->id;
        $sql .= " AND fk_product = " . $fkProduct;

        if (!$this->db->query($sql)) {
            return -1;
        } else {
            return 1;
        }
    }


	/**
	 *	Set status to draft
	 *
	 *	@param		User	$user	User that set draft
	 *	@return		int			<0 if KO, >0 if OK
	 */
	function setDraft($user)
	{
		global $langs, $conf;

		if ($this->statut != 0) {
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement";
			$sql.= " SET fk_statut = 0";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			dol_syslog(get_class($this)."::setDraft sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
				$this->db->commit();
			else {
				$this->db->rollback();
				$this->error=$this->db->lasterror();
				dol_syslog(get_class($this)."::setDraft ".$this->error, LOG_ERR);
				return -1;
			}
			return 1;
		}
	}

	/**
	 *	Set status to draft
	 *
	 *	@param		User	$user	User that set draft
	 *	@return		int			<0 if KO, >0 if OK
	 */
	function updateInfos($user, $bmoveentrepot=0)
	{
		global $conf;

		$updtSep=" SET";
		$sql = "UPDATE ".MAIN_DB_PREFIX."equipement";
		if ($this->fk_etatequipement > 0) {
			$sql.= $updtSep." fk_etatequipement=".$this->fk_etatequipement;
			$updtSep=" ,";
		} elseif ($this->fk_etatequipement == -1) {
			$sql.= $updtSep." fk_etatequipement=null";
			$updtSep=" ,";
		}

		if ($this->datee > 0) {
			$sql.= $updtSep." datee=".$this->db->idate($this->datee);
			$updtSep=" ,";
		} elseif ($this->datee == -1) {
			$sql.= $updtSep." datee = null";
			$updtSep=" ,";
		}

		if ($this->dateo > 0) {
			$sql.= $updtSep." dateo=".$this->db->idate($this->dateo);
			$updtSep=" ,";
		} elseif ($this->dateo == -1) {
			$sql.= $updtSep." dateo = null";
			$updtSep=" ,";
		}

		if ($this->dated > 0) {
			$sql.= $updtSep." dated=".$this->db->idate($this->dated);
			$updtSep=" ,";
		} elseif ($this->dated == -1) {
			$sql.= $updtSep." dated = null";
			$updtSep=" ,";
		}


		if ($this->fk_soc_client > 0) {
			$sql.= $updtSep." fk_soc_client=".$this->fk_soc_client;
			$updtSep=" ,";
		} elseif ($this->fk_soc_client == -1) {
			$sql.= $updtSep." fk_soc_client=null";
			$updtSep=" ,";
		}

		// gestion de l'entrepot � part (inclut les mouvements)
		if ($this->fk_etatentrepot > 0)
			$this->set_entrepot($user, $this->fk_etatentrepot, $bmoveentrepot);

		elseif ($this->fk_etatentrepot == -1)
			$this->set_entrepot($user, "null", $bmoveentrepot);

		if ($this->fk_statut != -1) {
			$sql.= $updtSep." fk_statut=".$this->fk_statut;
			$updtSep=" ,"; // pour g�rer la mise � jour
		}

		$sql.= " WHERE rowid = ".$this->id;
		$sql.= " AND entity = ".$conf->entity;

		// si on a fait une mise � jour
		if ($updtSep != " SET") {
			dol_syslog(get_class($this)."::updateInfos sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
				return 1;
			else {
				$this->error=$this->db->lasterror();
				dol_syslog(get_class($this)."::updateInfos ".$this->error, LOG_ERR);
				return -1;
			}
		}
	}


    /**
     * Update supplier order
     *
     * @param   User        $user                   User
     * @return  int         <0 if KO, >0 if OK
     *
     * @throws  Exception
     */
	public function updateSupplierOrder($user)
    {
        global $langs;

        if ($user->rights->equipement->creer) {
            $sql  = "UPDATE " . MAIN_DB_PREFIX . "equipement";
            $sql .= " SET fk_commande_fourn = " . ($this->fk_commande_fourn > 0 ? $this->fk_commande_fourn : "NULL");
            $sql .= ", fk_commande_fournisseur_dispatch = " . ($this->fk_commande_fournisseur_dispatch>0 ? $this->fk_commande_fournisseur_dispatch : "NULL");
            $sql .= ", description = '" . $this->db->escape($this->description) . "'";
            $sql .= ", fk_entrepot = ". ($this->fk_entrepot>0 ? $this->fk_entrepot : "NULL");
            $sql .= " WHERE rowid = " . $this->id;
            $sql .= " AND entity = " . getEntity('equipement');

            if ($this->db->query($sql)) {
                $result = 1;
            } else {
                $this->error    = $this->db->lasterror();
                $this->errors[] = $this->error;
                $result = -1;
            }
        } else {
            $this->error    = $langs->trans('ErrorForbidden');
            $this->errors[] = $this->error;
            dol_syslog(__METHOD__ . ' ' . $this->error, LOG_ERR);
            $result = -2;
        }

        return $result;
    }


	/**
	 *	Validate a Equipement
	 *
	 *	@param		User		$user		User that validate
	 *	@param		string		$outputdir	Output directory
	 *	@return		int			<0 if KO, >0 if OK
	 */
	function setValid($user, $outputdir)
	{
		global $langs, $conf;

		$error=0;

		if ($this->statut != 1) {
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement";
			$sql.= " SET fk_statut = 1";
			$sql.= ", datev = '".$this->db->idate(mktime())."'";
			$sql.= ", fk_user_valid = ".$user->id;
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND fk_statut = 0";

			dol_syslog(get_class($this)."::setValid sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql) {
				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('EQUIPEMENT_VALIDATE', $this, $user, $langs, $conf);
				if ($result < 0) {
					$error++;
					$this->errors=$interface->errors;
				}
				// Fin appel triggers

				if (! $error) {
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					$this->error=join(',', $this->errors);
					dol_syslog(get_class($this)."::setValid ".$this->error, LOG_ERR);
					return -1;
				}
			} else {
				$this->db->rollback();
				$this->error=$this->db->lasterror();
				dol_syslog(get_class($this)."::setValid ".$this->error, LOG_ERR);
				return -1;
			}
		}
	}


	/**
	 *	Returns the label status
	 *
	 *	@param	  int		$mode
	 *	@return	 string	  		Label
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut, $mode);
	}

	/**
	 *	Returns the label of a statut
	 *
	 *	@param	  int		$statut	 id statut
	 *	@param	  int		$mode
	 *	@return	 string	  		Label
	 */

	function LibStatut($statut, $mode=0)
	{
		global $langs;

		if ($mode == 0)
			return $langs->trans($this->statuts[$statut]);

		if ($mode == 1)
			return $langs->trans($this->statuts_short[$statut]);

		if ($mode == 2)
			return img_picto(
							$langs->trans($this->statuts_short[$statut]),
							$this->statuts_image[$statut].' '.$langs->trans($this->statuts_short[$statut])
			);

		if ($mode == 3)
			return img_picto(
							$langs->trans($this->statuts_short[$statut]),
							$this->statuts_image[$statut]
			);

		if ($mode == 4)
			return img_picto(
							$langs->trans($this->statuts_short[$statut]),
							$this->statuts_image[$statut]
			).' '.$langs->trans($this->statuts[$statut]);

		if ($mode == 5)
			return $langs->trans($this->statuts_short[$statut]).' '.img_picto(
							$langs->trans($this->statuts_short[$statut]),
							$this->statuts_image[$statut]
			);
	}

	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param		int			$withpicto		0=_No picto, 1=Includes the picto in the link, 2=Picto only
	 *	@param		int			$tolink		0=to main car, 1=to Event

	 *	@return		string						String with URL
	 */
	function getNomUrl($withpicto=0, $link=0)
	{
		global $langs;

		$result='';
		if ($link==1)
			$lien = '<a href="'.dol_buildpath('/equipement/events.php?id='.$this->id, 1).'"';
		else
			$lien = '<a href="'.dol_buildpath('/equipement/card.php?id='.$this->id, 1).'"';
		$lienfin='</a>';

		$picto='equipement@equipement';

		$label=$langs->trans("Show").': '.$this->ref;

		$linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
		$linkclose.=' class="classfortooltip" >';
		if (! is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager=new HookManager($this->db);
		}
		$hookmanager->initHooks(array('equipementdao'));
		$parameters=array('id'=>$this->id);
		// Note that $action and $object may have been modified by some hooks
		$reshook  = $hookmanager->executeHooks('getnomurltooltip', $parameters, $this, $action);
		$linkclose = ($hookmanager->resPrint ? $hookmanager->resPrint : $linkclose);

		if ($withpicto) $result.=($lien.$linkclose.img_object($label, $picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$linkclose.$this->ref.$lienfin;
		if ($this->quantity > 1)
			$result.="(".$this->quantity.")";
		return $result;
	}


	/**
	 *	Returns the next non used reference of intervention
	 *	depending on the module numbering assets within EQUIPEMENT_ADDON
	 *
	 *	@param		Societe		$soc		Object society
	 *	@return	 string					Free reference for intervention
	 */
	function getNextNumRef($soc)
	{
		global $conf, $db, $langs;
		$langs->load("equipement@equipement");

		$dir = dol_buildpath("/core/modules/equipement/", 1);

		if (! empty($conf->global->EQUIPEMENT_ADDON)) {
			$file = $conf->global->EQUIPEMENT_ADDON.".php";
			$classname = $conf->global->EQUIPEMENT_ADDON;
			if (! file_exists($dir.$file)) {
				$file='mod_'.$file;
				$classname='mod_'.$classname;
			}

			// Chargement de la classe de numerotation
			require_once($dir.$file);

			$obj = new $classname();

			$numref = "";
			$numref = $obj->getNumRef($soc, $this);

			if ( $numref != "")
				return $numref;
			else {
				dol_print_error($db, "Equipement::getNextNumRef ".$obj->error);
				return "";
			}
		} else {
			print $langs->trans("Error")." ".$langs->trans("Error_EQUIPEMENT_ADDON_NotDefined");
			return "";
		}
	}

	/**
	 * 	Information sur l'objet fiche equipement
	 *
	 *	@param	int		$id	  Id de la fiche equipement
	 *	@return	void
	 */
	function info($id)
	{
		global $conf;

		$sql = "SELECT e.rowid,";
		$sql.= " datec,";
		$sql.= " datev,";
		$sql.= " fk_user_author,";
		$sql.= " fk_user_valid";
		$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
		$sql.= " WHERE e.rowid = ".$id;
		$sql.= " AND e.entity = ".$conf->entity;

		$result = $this->db->query($sql);

		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id				= $obj->rowid;
				$this->date_creation	= $this->db->jdate($obj->datec);
				$this->date_validation	= $this->db->jdate($obj->datev);

				$cuser = new User($this->db);
				$cuser->fetch($obj->fk_user_author);
				$this->user_creation	= $cuser;

				if ($obj->fk_user_valid) {
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation	= $vuser;
				}
			}
			$this->db->free($result);
		} else
			dol_print_error($this->db);
	}

	/**
	 *	Delete Equipement
	 *
	 *	@param	  User	$user	Object user who delete
	 *	@return		int				<0 if KO, >0 if OK
	 */
	function delete($user)
	{
		global $conf, $langs;
		require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

		$error=0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."equipementevt";
		$sql.= " WHERE fk_equipement = ".$this->id;

		dol_syslog(get_class($this)."::delete sql=".$sql);
		if ( $this->db->query($sql) ) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."equipement";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			dol_syslog("Equipement::delete sql=".$sql);
			if ( $this->db->query($sql) ) {
				// Remove directory with files
				$equipementref = dol_sanitizeFileName($this->ref);
				if ($conf->equipement->dir_output) {
					$dir = $conf->equipement->dir_output."/".$equipementref;
					$file = $dir."/".$equipementref.".pdf";
					if (file_exists($file)) {
						dol_delete_preview($this);

						if (! dol_delete_file($file, 0, 0, 0, $this)) { // For triggers
							$this->error=$langs->trans("ErrorCanNotDeleteFile", $file);
							return 0;
						}
					}
					if (file_exists($dir)) {
						if (! dol_delete_dir_recursive($dir)) {
							$this->error=$langs->trans("ErrorCanNotDeleteDir", $dir);
							return 0;
						}
					}
				}

				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT."/core/class/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('EQUIPEMENT_DELETE', $this, $user, $langs, $conf);
				if ($result < 0) {
					$error++;
					$this->errors=$interface->errors;
				}
				// Fin appel triggers

				$this->db->commit();
				return 1;
			} else {
				$this->error=$this->db->lasterror();
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Defines a entrepot of the equipement
	 *
	 *	@param	  User	$user				Object user who define
	 *	@param	  int	$fk_entrepot   		id of the entrepot
	 *	@return	 int							<0 if ko, >0 if ok
	 */
	function set_entrepot($user, $fk_entrepot, $bmoveentrepot=0)
	{
		global $conf, $langs;

		if ($user->rights->equipement->creer) {
			$oldentrepot= $this->fk_entrepot;

			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement";
			$sql.= " SET fk_entrepot = ".($fk_entrepot!=-1? $fk_entrepot:"null");
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
//print "===".$sql."<br>";
			if ($this->db->query($sql)) {
				$this->fk_entrepot = $fk_entrepot;
				// si on a chang� d'entrepot et on veut faire un mouvement
				if ($bmoveentrepot==1 && $oldentrepot != $fk_entrepot) {
					require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
					$mouvP = new MouvementStock($this->db);
					$mouvP->origin = $this;
					$mouvP->origin->id = $this->id;

					// le prix est � 0 pour ne pas impacter le pmp
					if ( $oldentrepot > 0 ) // si il y avait un ancien entrepot
						$idmv=$mouvP->livraison(
										$user, $this->fk_product, $oldentrepot, $this->quantity, 0,
										$langs->trans("EquipementMoveOut")
						);

					if ($fk_entrepot > 0 )
						$idmv=$mouvP->reception(
										$user, $this->fk_product, $fk_entrepot, $this->quantity, 0,
										$langs->trans("EquipementMoveIn")
						);
				}

				//  gestion des sous composant si il y en a
				$sql = "SELECT * FROM ".MAIN_DB_PREFIX."equipementassociation ";
				$sql.= " WHERE fk_equipement_pere=".$this->id;

				dol_syslog(get_class($this)."::get_Parent sql=".$sql, LOG_DEBUG);
				$resql=$this->db->query($sql);
				if ($resql) {
					$num = $this->db->num_rows($resql);
					$i = 0;
					$tblrep=array();
					while ($i < $num) {
						$objp = $this->db->fetch_object($resql);

						$sql = "UPDATE ".MAIN_DB_PREFIX."equipement";
						$sql.= " SET fk_entrepot = ".($fk_entrepot!=-1? $fk_entrepot:"null");
						$sql.= " WHERE rowid = ".$objp->fk_equipement_fils;
						$sql.= " AND entity = ".$conf->entity;
						if ($this->db->query($sql)) {
							// si on a chang� d'entrepot et on veut faire un mouvement
							if ($bmoveentrepot && $oldentrepot != $fk_entrepot) {
//								$tmpequipement = new Equipement($this->db);
								$mouvP->origin->id = $objp->fk_equipement_fils;

								if ( $oldentrepot >0 ) // si il y avait un ancien entrepot
									$idmv=$mouvP->livraison(
													$user, $objp->fk_product,
													$oldentrepot, 1, 0, $langs->trans("EquipementCompMoveOut")
									);

								if ($fk_entrepot > 0 )
									$idmv=$mouvP->reception(
													$user, $objp->fk_product,
													$fk_entrepot, 1, 0, $langs->trans("EquipementCompMoveIn")
									);
							}
						}
						$i++;
					}
				}
				return 1;
			} else {
				$this->error=$this->db->error();
				print $this->db->error();
				dol_syslog("Equipement::set_entrepot Erreur SQL");
				return -1;
			}
		}
	}

	/**
	 *	Defines a etat of the equipement
	 *
	 *	@param      User	$user				Object user who define
	 *	@param      int     $fk_etatequipement  Id of equipment
     *	@param      int     $noCheckStatus      [=FALSE] Check status
	 *	@return	    int							<0 if ko, >0 if ok
	 */
	function set_etatEquipement($user, $fk_etatequipement, $noCheckStatus = FALSE)
	{
		global $conf;

		if ($user->rights->equipement->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement";
			$sql.= " SET fk_etatequipement= ".($fk_etatequipement!=-1? $fk_etatequipement:"null");
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			if ($noCheckStatus === FALSE) {
                $sql .= " AND fk_statut = 0";
            }

			if ($this->db->query($sql)) {
				$this->fk_etatequipement = $fk_etatequipement;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_entrepot Erreur SQL");
				return -1;
			}
		}
	}

	function set_datee($user, $datee)
	{
		global $conf;

		if ($user->rights->equipement->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";

			$sql.= " SET datee = ".($datee?"'".$this->db->idate($datee)."'":"null");
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND fk_statut = 0";

			if ($this->db->query($sql)) {
				$this->datee= $datee;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_datee Erreur SQL");
				return -1;
			}
		}
	}

	function set_dated($user, $dated)
	{
		global $conf;

		if ($user->rights->equipement->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET dated = ".($dated?"'".$this->db->idate($dated)."'":"null");
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND fk_statut = 0";

			if ($this->db->query($sql)) {
				$this->dated= $dated;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_dated Erreur SQL");
				return -1;
			}
		}
	}

	function set_unitweight($user, $unitweight)
	{
		global $conf;

		if ($user->rights->equipement->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET unitweight = ".price2num($unitweight);
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			if ($this->db->query($sql)) {
				$this->unitweight= $unitweight;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_unitweight Erreur SQL");
				return -1;
			}
		}
	}

	function set_dateo($user, $dateo)
	{
		global $conf;

		if ($user->rights->equipement->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET dateo = ".($dateo?"'".$this->db->idate($dateo)."'":"null");
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND fk_statut = 0";

			if ($this->db->query($sql)) {
				$this->dateo = $dateo;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_dateo Erreur SQL");
				return -1;
			}
		}
	}

	function set_client($user, $fk_soc_client)
	{
		global $conf;

		if ($user->rights->equipement->creer) {
			// quand on change le client, on raz la facture du client aussi
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET fk_soc_client = ".($fk_soc_client!=-1? $fk_soc_client:"null");
			$sql.= " , fk_facture=null";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			if ($this->db->query($sql)) {
				$this->fk_soc_client = ($fk_soc_client!=-1? $fk_soc_client:"null");
				// on g�re r�cursivement l'h�ritage des enfants
				$tblenfant=$this->get_Childs();

				foreach ($tblenfant as $key => $value) {
					$equipementChilds = new equipement($this->db);
					$equipementChilds->fetch($value);
					$equipementChilds->set_client($user, $fk_soc_client);
				}
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog(get_class($this)."::set_client Erreur SQL");
				return -1;
			}
		}
	}

	function set_fact_client($user, $fk_fact_client)
	{
		global $conf;

		if ($user->rights->equipement->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET fk_facture = ".($fk_fact_client!=-1 ? $fk_fact_client:"null");
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			//$sql.= " AND fk_statut = 0";

			if ($this->db->query($sql)) {
				$this->fk_fact_cli = $fk_fact_client;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_fact_client Erreur SQL");
				return -1;
			}
		}
	}

	function set_fact_fourn($user, $fk_fact_fourn)
	{
		global $conf;

		if ($user->rights->equipement->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET fk_facture_fourn = ".($fk_fact_fourn!=-1? $fk_fact_fourn:"null");
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			if ($this->db->query($sql)) {
				$this->fk_fact_fourn = $fk_fact_fourn;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_fact_client Erreur SQL");
				return -1;
			}
		}
	}

    function set_commande_fourn($user, $fk_commande_fourn)
	{
		global $conf;

		if ($user->rights->equipement->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET fk_commande_fourn = ".($fk_commande_fourn>0? $fk_commande_fourn:"null");
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			if ($this->db->query($sql)) {
				$this->fk_commande_fourn = $fk_commande_fourn;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_commande_fourn Erreur SQL");
				return -1;
			}
		}
	}

	/**
	 *	Define the label of the equipement
	 *
	 *	@param	  User	$user			Object user who modify
	 *	@param	  string	$description	description
	 *	@return	 int						<0 if ko, >0 if ok
	 */
	function set_description($user, $description)
	{
		global $conf;

		if ($user->rights->equipement->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET description = '".$this->db->escape($description)."'";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			if ($this->db->query($sql)) {
				$this->description = $description;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_description Erreur SQL");
				return -1;
			}
		}
	}

	/**
	 *	Define the numimmocompta of the intervention
	 *
	 *	@param	  User	$user			Object user who modify
	 *	@param	  string	$description	description
	 *	@return	 int						<0 if ko, >0 if ok
	 */
	function set_numimmocompta($user, $numimmocompta)
	{
		global $conf;

		if ($user->rights->equipement->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET numimmocompta = '".$this->db->escape($numimmocompta)."'";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			if ($this->db->query($sql)) {
				$this->numimmocompta = $numimmocompta;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_numimmocompta Erreur SQL");
				return -1;
			}
		}
	}

	/**
	 *	Change de reference of the equipement
	 *
	 *	@param	  User	$user			Object user who modify
	 *	@param	  string	$description	description
	 *	@return	 int						<0 if ko, >0 if ok
	 */
	function set_numref($user, $numref)
	{
		global $conf;

		if ($user->rights->equipement->majserial) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET ref = '".$this->db->escape($numref)."'";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			if ($this->db->query($sql)) {
				$this->ref = $numref;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_numref Erreur SQL");
				return -1;
			}
		}
	}

	/**
	 *	Define the numversion of the equipement
	 *
	 *	@param	  User	$user			Object user who modify
	 *	@param	  string	$description	description
	 *	@return	 int						<0 if ko, >0 if ok
	 */
	function set_numversion($user, $numversion)
	{
		global $conf;

		if ($user->rights->equipement->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET numversion = '".$this->db->escape($numversion)."'";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			if ($this->db->query($sql)) {
				$this->numversion = $numversion;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_numversion Erreur SQL");
				return -1;
			}
		}
	}

	/**
	 *	Define the numversion of the equipement
	 *
	 *	@param	  User	$user			Object user who modify
	 *	@param	  string	$description	description
	 *	@return	 int						<0 if ko, >0 if ok
	 */
	function set_quantity($user, $quantity)
	{
		global $conf;

		if ($user->rights->equipement->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET quantity = ".$quantity;
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			if ($this->db->query($sql)) {
				$this->quantity = $quantity;
				return 1;
			} else {
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_quantity Erreur SQL");
				return -1;
			}
		}
	}


    /**
     * Define the dipatch supplier order line
     *
     * @param   User	$user			                    Object user who modify
     * @param   int     $fkCommandeFournisseurDispatch      Id of dispatch supplier order line
     * @return  int     <0 if ko, >0 if ok
     *
     * @throws  Exception
     */
    public function setFkCommandeFournisseurDispatch($user)
    {
        global $conf;

        if ($user->rights->equipement->creer) {
            $sql  = "UPDATE " . MAIN_DB_PREFIX . "equipement";
            $sql .= " SET fk_commande_fournisseur_dispatch = " . ($this->fk_commande_fournisseur_dispatch>0 ? $this->fk_commande_fournisseur_dispatch : "NULL");
            $sql .= " WHERE rowid = " . $this->id;
            $sql .= " AND entity = " . $conf->entity;

            if ($this->db->query($sql)) {
                return 1;
            } else {
                $this->error    = $this->db->lasterror();
                $this->errors[] = $this->error;
                dol_syslog(__METHOD__ . ' Error : SQL=' . $sql);
                return -1;
            }
        }
    }


    /**
     * Adding a line of event into data base
     *
     * @param   int                 $equipementid               Id of equipement
     * @param   int                 $fk_equipementevt_type      Type of event
     * @param   string              $desc                       Desc of event
     * @param   date                $dateo                      Operation date
     * @param   date                $datee                      Event date
     * @param   int                 $fulldayevent               Full day event
     * @param   int                 $fk_contrat                 Id of contract
     * @param   int                 $fk_fichinter               Id of fichinter
     * @param   int                 $fk_expedition              Id of shipping
     * @param   int                 $fk_project                 Id of project
     * @param   int                 $fk_user_author             Id of author
     * @param   float               $total_ht                   Total HT
     * @param   array               $array_option               Array options
     * @param   int                 $fk_expeditiondet           Id of shipping line
     * @param   int                 $fk_retourproduits          [=0] Id of product return
     * @param   int                 $fk_factory                 [=0] Id of factory
     * @param   int                 $fk_factorydet              [=0] Id of factory line
     * @return  int                 >0 if ok, <0 if ko
     *
     * @throws  Exception
     */
	function addline($equipementid, $fk_equipementevt_type, $desc, $dateo, $datee, $fulldayevent, $fk_contrat, $fk_fichinter, $fk_expedition, $fk_project, $fk_user_author, $total_ht=0, $array_option=0, $fk_expeditiondet=0, $fk_retourproduits=0, $fk_factory=0, $fk_factorydet=0)
	{

		$this->db->begin();

		// Insertion ligne
		$line=new Equipementevt($this->db);

		$line->fk_equipement			= $equipementid;
		$line->desc						= $desc;
		$line->dateo					= $dateo;
		$line->datee					= $datee;
		$line->fulldayevent				= $fulldayevent;
		$line->total_ht					= $total_ht;
		$line->fk_equipementevt_type	= $fk_equipementevt_type;
		$line->fk_fichinter				= $fk_fichinter;
		$line->fk_contrat				= $fk_contrat;
		$line->fk_project				= $fk_project;
		$line->fk_expedition			= $fk_expedition;
        $line->fk_expeditiondet			= $fk_expeditiondet;
		$line->fk_user_author			= $fk_user_author;
		$line->datec					= dol_now();
		$line->fk_retourproduits        = $fk_retourproduits;
        $line->fk_factory               = $fk_factory;
        $line->fk_factorydet            = $fk_factorydet;
        $line->context                  = $this->context;

		if (is_array($array_option) && count($array_option)>0)
			$line->array_options=$array_option;

		$result=$line->insert();
		if ($result > 0) {
			$this->db->commit();
			return 1;
		} else {
			$this->error=$this->db->error();
			dol_syslog("Error Insert, error=".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Adding a line of consumption into data base
	 *
	 *	@param		int		$equipementid			Id of equipement
	 *	@param		string	$desc					Line description
	 *	@param	  date	$date_evenement			Intervention date
	 *	@param	  int		$duration				Intervention duration
	 *	@param	  int		$duration				Prix de l'�v�nement
	 *	@return		int			 				>0 if ok, <0 if ko
	 */
	function addconsumption($equipementid, $fk_product, $desc, $datecons, $fk_entrepot, $fk_entrepotmove, $fk_user_author, $qty=1)
	{

		// Insertion ligne
		$line=new Equipementconsumption($this->db);
		$product =new product($this->db);
		$product->fetch($fk_product);

		$line->fk_equipement			= $equipementid;
		$line->desc						= $desc;
		$line->datecons					= $datecons;
		$line->fk_product				= $fk_product;
		$line->price					= $product->subprice;
		$line->fk_entrepot				= $fk_entrepot;
		$line->fk_entrepotmove			= $fk_entrepotmove;
		// l'entrepot de l'�quipement devient l'entrepot du consomm�
		$line->fk_entrepot_dest			= $this->fk_entrepot;
		$line->fk_user_author			= $fk_user_author;

		for ($i=0; $i < $qty; $i++) {
			$result=$line->insert();
			if ($result < 0) {
				$this->error=$this->db->error();
				dol_syslog("Error addconsumption error=".$this->error, LOG_ERR);
				return -1;
			}
		}
		return $result;
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
		global $langs; //, $user, $conf;

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
			$line=new Equipementevt($this->db);
			$line->desc=$langs->trans("Description")." ".$xnbp;
			$line->datei=($now-3600*(1+$xnbp));
			$line->duration=600;
			$line->fk_fichinter=0;
			$this->lines[$xnbp]=$line;
			$xnbp++;

			$this->duree+=$line->duration;
		}
	}

	/**
	 *	Load array of lines
	 *
	 *	@return		int		<0 if Ko,	>0 if OK
	 */
	function fetch_lines()
	{
		$sql = 'SELECT ee.rowid, ee.fk_equipement, ee.description, ee.datec, ee.fk_equipementevt_type,';
		$sql.= ' ee.fk_user_author, ee.datee, ee.dateo, ee.fulldayevent, ee.total_ht, ee.fk_fichinter,';
		$sql.= '  ee.fk_contrat, ee.fk_expedition, fi.ref as reffichinter, co.ref as refcontrat, ex.ref as refexpedition ';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'equipementevt as ee';
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."fichinter as fi on ee.fk_fichinter = fi.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contrat as co on ee.fk_contrat = co.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."expedition as ex on ee.fk_expedition = ex.rowid";
		$sql.= ' WHERE fk_equipement = '.$this->id;

		dol_syslog(get_class($this)."::fetch_lines sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$objp = $this->db->fetch_object($resql);

				$line = new Equipementevt($this->db);
				$line->id 						= $objp->rowid;
				$line->fk_equipement 			= $objp->fk_equipement;
				$line->fk_equipementevt_type	= $objp->fk_equipementevt_type;
				$line->desc 					= $objp->description;
				$line->fk_fichinter				= $objp->fk_fichinter;
				$line->fk_contrat				= $objp->fk_contrat;
				$line->fk_expedition			= $objp->fk_expedition;
				$line->ref_fichinter			= $objp->reffichinter;
				$line->ref_contrat				= $objp->refcontrat;
				$line->ref_expedition			= $objp->refexpedition;
				$line->datec					= $this->db->jdate($objp->datec);
				$line->dateo					= $this->db->jdate($objp->dateo);
				$line->datee					= $this->db->jdate($objp->datee);
				$line->fulldayevent				= $objp->fulldayevent;
                $line->fk_user_author			= $objp->fk_user_author;
				$line->total_ht					= $objp->total_ht;
                $line->fetch_optionals();

				$this->lines[$i] = $line;

				$i++;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *	Get the id of the fisrt parent child
	 *	with recursive search
	 *	@param  	int		$fk_equipementcomponent	Id equipement component
	 *	@return 	int								id equipement main
	 */
	function get_firstParent($fk_equipementcomponent)
	{
		$sql = "SELECT fk_equipement_pere FROM ".MAIN_DB_PREFIX."equipementassociation ";
		$sql.= " WHERE fk_equipement_fils=".$fk_equipementcomponent;

		dol_syslog(get_class($this)."::get_firstParent sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql) {
			$objp = $this->db->fetch_object($resql);
			if ($objp->fk_equipement_pere)
				return($this->get_firstParent($objp->fk_equipement_pere));
			else
				return($fk_equipementcomponent);
		} else
			return($fk_equipementcomponent);
	}

	/**
	 *	Get the id of the fisrt parent child
	 *	with recursive search
	 *	@param  	int		$fk_equipementcomponent	Id equipement component
	 *	@return 	array								id equipement main
	 */
	function get_Parent($fk_equipementcomponent)
	{
		$sql = "SELECT fk_equipement_pere, fk_product FROM ".MAIN_DB_PREFIX."equipementassociation ";
		$sql.= " WHERE fk_equipement_fils=".$fk_equipementcomponent;

		dol_syslog(get_class($this)."::get_Parent sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql) {
			$objp = $this->db->fetch_object($resql);
			if ($objp->fk_equipement_pere) {
				$tblrep=array();
				$tblrep[0]=$objp->fk_equipement_pere;
				$tblrep[1]=$objp->fk_product;
				return $tblrep;
			}
		}
		return array();
	}


	/**
	 *	Get the id of the equipement child
	 *
	 *	@param  	int		$fk_parent			Id equipement parent
	 *	@param  	int		$fk_product			Id product of component
	 *	@param  	int		$position			position of the component in the parent
	 *	@return 	string						ref equipement child
	 */
	function get_component($fk_parent, $fk_product, $position)
	{
		$sql = "SELECT fk_equipement_fils FROM ".MAIN_DB_PREFIX."equipementassociation ";
		$sql.= " WHERE fk_equipement_pere=".$fk_parent;
		$sql.= " and fk_product=".$fk_product;
		$sql.= " and position=".$position;

		dol_syslog(get_class($this)."::get_component sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql) {
			$objp = $this->db->fetch_object($resql);
			$this->fetch($objp->fk_equipement_fils);
			return($this->ref);
		}
	}

	/**
	 *	Get the id of the childs
	 *	@return 	array				id equipement childs
	 */
	function get_Childs()
	{
		$tblrep=array();
		$sql = "SELECT fk_equipement_fils FROM ".MAIN_DB_PREFIX."equipementassociation ";
		$sql.= " WHERE fk_equipement_pere=".$this->id;

		dol_syslog(get_class($this)."::get_Parent sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			$tblrep=array();
			while ($i < $num) {
				$objp = $this->db->fetch_object($resql);
				$tblrep[$i]=$objp->fk_equipement_fils;
				$i++;
			}
		}
		return $tblrep;
	}


	/**
	 *	Get the number of events of an Equipement
	 *	@return 	array				id equipement childs
	 */
	function get_Events()
	{
		$nbevent=0;
		$sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."equipementevt";
		$sql.= " WHERE fk_equipement=".$this->id;

		dol_syslog(get_class($this)."::get_Events sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql) {
			$objp = $this->db->fetch_object($resql);
			$nbevent= $objp->nb;
		}
		return $nbevent;
	}

	/**
	 *	Get the number of events of an Equipement
	 *	@return 	array				id equipement childs
	 */
	function get_Consumptions()
	{
		$nbconsumption=0;
		$sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."equipementconsumption";
		$sql.= " WHERE fk_equipement=".$this->id;

		dol_syslog(get_class($this)."::get_Consumptions sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql) {
			$objp = $this->db->fetch_object($resql);
			$nbconsumption= $objp->nb;
		}
		return $nbconsumption;
	}


    /**
     * Find all equipements in a warehouse
     *
     * @param   int         $fkProduct      Id product
     * @return  resource    SQL resource
     *
     * @throws Exception
     */
	public function findAllInWarehouseByFkProduct($fkProduct)
    {
        global $conf;

        $sqlNotInEquipementUsed  = "SELECT ea.fk_equipement_fils";
        $sqlNotInEquipementUsed .= " FROM " . MAIN_DB_PREFIX . "equipementassociation ea";
        $sqlNotInEquipementUsed .= " WHERE ea.fk_product = " . $fkProduct;

        $sql  = "SELECT e.rowid, e.ref";
        $sql .= " FROM " . MAIN_DB_PREFIX . "equipement e";
        $sql .= " WHERE e.fk_product = " . $fkProduct;
        $sql .= " AND e.fk_entrepot > 0";
        $sql .= " AND e.quantity > 0";
        // not a component linked to another equipement
        $sql .= " AND e.rowid NOT IN (" . $sqlNotInEquipementUsed . ")";
        $sql .= " AND e.entity = " . $conf->entity;

        dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);

        return $this->db->query($sql);
    }


    /**
     * Find all equipment linked to a specific product and in a warehouse
     *
     * @param   int         $fkProduct      Id of product
     * @param   int         $fkEntrepot     Id of warehouse
     * @return  resource    SQL resource
     *
     * @throws  Exception
     */
    public function findAllByFkProductAndFkEntrepot($fkProduct, $fkEntrepot)
    {
        global $conf;

        $sql  = "SELECT e.rowid";
        $sql .= ", e.ref";
        $sql .= " FROM " . MAIN_DB_PREFIX . "equipement e";
        $sql .= " WHERE e.fk_statut >= 1";
        $sql .= " AND e.entity = " . $conf->entity;
        $sql .= " AND e.fk_product = " . $fkProduct;
        $sql .= " AND e.fk_entrepot = " . $fkEntrepot;
        $sql .= " ORDER BY e.ref DESC";

        dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);

        return $this->db->query($sql);
    }


    /**
     * Set the id of the equipment child
     *
     * @param  	int		$fk_parent          Id equipment parent
     * @param  	int		$fk_product         Id product of component
     * @param  	int		$position           Position of the component in the parent
     * @param  	string	$ref_child          Ref equipment child
     * @return 	int     <0 if KO, >0 if OK
     *
     * @throws Exception
     */
	function set_component($fk_parent, $fk_product, $position, $ref_child, $notrigger=0)
    {
        global $langs, $user;
        $langs->load('equipement@equipement');

        $now = dol_now();
        $error = 0;
        $this->db->begin();

        $equipment_statitc = new Equipement($this->db);
        $equipment_statitc->fetch($fk_parent);
        $fk_equipementevt_type = dol_getIdFromCode($this->db, 'COMPO', 'c_equipementevt_type', 'code', 'rowid');

        // Get current ref attached to this $fk_parent, $fk_product, $position
        $current_equipment_statitc = new Equipement($this->db);
        $current_ref_child = $current_equipment_statitc->get_component($fk_parent, $fk_product, $position);

        // on recupere l'id du composant a partir de sa ref
        $this->id = '';
        if (!empty($ref_child) && $this->fetch('', $ref_child) > 0) {
            if ($current_ref_child != $ref_child) {
                if (!empty($current_ref_child)) {
                    $result = $equipment_statitc->addline(
                        $equipment_statitc->id,
                        $fk_equipementevt_type,
                        //$langs->trans('EquipmentDeleteEquipmentToComposition', $current_equipment_statitc->getNomUrl(1)),
                        $langs->trans('EquipmentDeleteEquipmentToComposition', $current_equipment_statitc->getNomUrl(1), $equipment_statitc->getNomUrl(1)),
                        $now,
                        $now,
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        ''
                    );
                    if ($result < 0) {
                        $error++;
                        $this->error = $equipment_statitc->errorsToString();
                    }

                    if (!$error) {
                        // Delete old equiquipment
                        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "equipementassociation ";
                        $sql .= " WHERE fk_equipement_pere=" . $fk_parent;
                        $sql .= " and fk_product=" . $fk_product;
                        $sql .= " and position=" . $position;

                        dol_syslog(get_class($this) . "::set_component del sql=" . $sql, LOG_DEBUG);
                        $result = $this->db->query($sql);
                        if ($result < 0) {
                            $error++;
                            $this->error = $this->db->lasterror();
                        }
                    }

                    if (!$error && ! $notrigger) {
                        // Call trigger
                        $this->context['parameters'] = array('parent' => $equipment_statitc, 'old_child' => $current_equipment_statitc, 'position' => $position);
                        $result = $this->call_trigger('SET_COMPONENT_DEL', $user);
                        if ($result < 0) {
                            $error++;
                        }
                        // End call triggers
                    }

                }

                if (!$error) {
                    $equipment_statitc->context['set_component_add'] = 'set_component_add';
                    $equipment_statitc->context['component_add_id'] = $this->id;

                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "equipementassociation ";
                    $sql .= " (fk_equipement_fils, fk_equipement_pere, fk_product, position)";
                    $sql .= " values (" . $this->id . ", " . $fk_parent . ", " . $fk_product . ", " . $position . ")";
                    dol_syslog(get_class($this) . "::set_component trt sql=" . $sql, LOG_DEBUG);
                    $result = $this->db->query($sql);
                    if ($result < 0) {
                        $error++;
                        $this->error = $this->db->lasterror();
                    }

                    // remove child equipment from warehouse
                    if (!$error) {
                        $result = $this->set_entrepot($user, -1);
                        if ($result < 0) $error++;
                    }

                    if (!$error && ! $notrigger) {
                        // Call trigger
                        $this->context['parameters'] = array('parent' => $equipment_statitc, 'old_child' => $current_equipment_statitc, 'position' => $position);
                        $result = $this->call_trigger('SET_COMPONENT_ADD', $user);
                        if ($result < 0) {
                            $error++;
                        }
                        // End call triggers
                    }

                    if (!$error) {
                        $result = $equipment_statitc->addline(
                            $equipment_statitc->id,
                            $fk_equipementevt_type,
                            //$langs->trans('EquipmentAddEquipmentToComposition', $this->getNomUrl(1)),
                            $langs->trans('EquipmentAddEquipmentToComposition', $this->getNomUrl(1), $equipment_statitc->getNomUrl(1)),
                            $now,
                            $now,
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            ''
                        );
                        if ($result < 0) {
                            $error++;
                            $this->error = $equipment_statitc->errorsToString();
                        }
                    }

                    unset($equipment_statitc->context);
                }
            }
        } else {
            if (!empty($current_ref_child)) {
                $result = $equipment_statitc->addline(
                    $equipment_statitc->id,
                    $fk_equipementevt_type,
                    //$langs->trans('EquipmentDeleteEquipmentToComposition', $current_equipment_statitc->getNomUrl(1)),
                    $langs->trans('EquipmentDeleteEquipmentToComposition', $current_equipment_statitc->getNomUrl(1), $equipment_statitc->getNomUrl(1)),
                    $now,
                    $now,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    ''
                );
                if ($result < 0) {
                    $error++;
                    $this->error = $equipment_statitc->errorsToString();
                }

                if (!$error) {
                    // Delete old equiquipment
                    $sql = "DELETE FROM " . MAIN_DB_PREFIX . "equipementassociation ";
                    $sql .= " WHERE fk_equipement_pere=" . $fk_parent;
                    $sql .= " and fk_product=" . $fk_product;
                    $sql .= " and position=" . $position;

                    dol_syslog(get_class($this) . "::set_component del sql=" . $sql, LOG_DEBUG);
                    $result = $this->db->query($sql);
                    if ($result < 0) {
                        $error++;
                        $this->error = $this->db->lasterror();
                    }
                }

                if (!$error && ! $notrigger) {
                    // Call trigger
                    $this->context['parameters'] = array('parent' => $equipment_statitc, 'old_child' => $current_equipment_statitc, 'position' => $position);
                    $result = $this->call_trigger('SET_COMPONENT_DEL', $user);
                    if ($result < 0) {
                        $error++;
                    }
                    // End call triggers
                }
            }
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

	/**
	 *	cut an equipement
	 *
	 *	@param  	string	$ref_new			reference du nouveau lot
	 *	@param  	int		$quantiynew			quantite du nouveau lot
	 *	@param  	boolean	$cloneevent			top pour reprendre les evenements du lot ou pas
	 *  @return 	int		$fk_iddest			Id de l'evenement cree
	 */
	function cut_equipement($ref_new, $quantitynew, $cloneevent)
	{
		global $conf;
		global $soc;
		global $user;

		$cloned = new Equipement($this->db);

		$cloned->nbAddEquipement 	= 1;
		$cloned->SerialMethod 		= 3;
		//$cloned->id				= $this->id;
		$cloned->ref				= $ref_new;
		$cloned->description  		= $this->description;
		$cloned->socid				= $this->fk_soc;
		$cloned->statut				= $this->fk_statut;
		$cloned->numversion			= $this->numversion;
		$cloned->quantity			= $quantitynew;
		$cloned->author				= $user->id;
		$cloned->numimmocompta		= $this->numimmocompta;
		$cloned->dateo				= $this->db->jdate($this->dateo);
		$cloned->datee				= $this->db->jdate($this->datee);
		$cloned->dated				= $this->db->jdate($this->dated);
		$cloned->datec				= $this->db->jdate($this->datec);
		$cloned->datev				= $this->db->jdate($this->datev);
		$cloned->datem				= $this->db->jdate($this->datem);
		$cloned->fk_product			= $this->fk_product;
		$cloned->fk_soc_fourn		= $this->fk_soc_fourn;
		$cloned->fk_fact_fourn		= $this->fk_facture_fourn;
		$cloned->fk_soc_client		= $this->fk_soc_client;
		$cloned->fk_fact_client		= $this->fk_facture;
		$cloned->fk_entrepot		= $this->fk_entrepot;
		$cloned->fk_etatequipement	= $this->fk_etatequipement;
		$cloned->etatequiplibelle	= $this->etatequiplibelle;
		$cloned->note_public		= $this->note_public;
		$cloned->note_private		= $this->note_private;
		$cloned->model_pdf			= $this->model_pdf;
		$cloned->fulldayevent 		= $this->fulldayevent;

		// pas de mouvement de stock sur le d�coupage (les quantit� restent les m�mes dans le m�me entrepot)
		$cloned->isentrepotmove=0;

		$fk_iddest=$cloned->create();

		// si la cr�ation c'est bien pass� on met � jour la quantit� d'origine
		if ($fk_iddest > 0 ) {
			//print "cloned";
			$this->set_quantity($user, $this->quantity - $quantitynew);

			// TODO clone des extrafields

			// pas de clonage des compositions, aucune utilit� sur un lot


			// ensuite on clone les �v�nements de l'�quipement
			if ($cloneevent) {
				$sql = 'SELECT ee.rowid, ee.description, ee.fk_equipement, ee.fk_equipementevt_type, ee.total_ht, ee.fulldayevent,';
				$sql.= ' ee.datec, ee.fk_user_author, ee.dateo, ee.datee, ee.fk_fichinter, ee.fk_contrat, ee.fk_expedition';
				$sql.= ' FROM '.MAIN_DB_PREFIX.'equipementevt as ee';
				$sql.= ' WHERE ee.fk_equipement = '.$this->id;
				$result = $this->db->query($sql);
				if ($result) {
					$num = $this->db->num_rows($resql);
					$i = 0;
					while ($i < $num) {
						$objp = $this->db->fetch_object($resql);
						$line = new Equipementevt($this->db);

						$line->fk_equipement			= $fk_iddest;
						$line->desc						= $objp->desc;
						$line->dateo					= $objp->dateo;
						$line->datee					= $objp->datee;
						$line->fulldayevent				= $objp->fulldayevent;
						$line->total_ht					= $objp->total_ht;
						$line->fk_equipementevt_type	= $objp->fk_equipementevt_type;
						$line->fk_fichinter				= $objp->fk_fichinter;
						$line->fk_contrat				= $objp->fk_contrat;
						$line->fk_expedition			= $objp->fk_expedition;
						$line->datec					= $objp->datec;
						$result=$line->insert();
						$i++;
					}

				// on ajoute un �v�nement de clonage???
				}
			}
		}
		return $fk_iddest;
	}


	function fillinvoice($numfacture)
	{
		global $langs;
		// on r�cup�re les num�ro �quipements associ� � la facture, pour les afficher dans le d�tails de la facture
		$sql = "SELECT e.rowid, e.ref, e.description, e.fk_soc_fourn, e.fk_facture_fourn, e.fk_statut, e.fk_entrepot,";
		$sql.= " e.numversion, e.numimmocompta, e.fk_etatequipement, e.quantity,";
		$sql.= " e.datec, e.datev, e.datee, e.dated, e.dateo, e.tms as datem,";
		$sql.= " e.fk_product, e.fk_soc_client, e.fk_facture,";
		$sql.= " e.note_public, e.note_private ";
		$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
		$sql.= " WHERE e.fk_facture=".$numfacture;
		$sql.= " ORDER BY fk_product";
		dol_syslog(get_class($this)."::fillinvoice sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql) {	// on boucle sur les �quipements
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$serialLineInInvoice=$langs->trans("SerialLineInInvoice", $obj->ref);
				if ($obj->quantity >1)
					$SerialLineInInvoice.="(".$obj->quantity.")";

				// on ajoute les num�ros d'�quipements � la suite
				$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet";
				$sql.= " SET description =concat(description,'".$serialLineInInvoice."')";
				$sql.= " WHERE fk_facture=".$numfacture;
				$sql.= " AND fk_product=".$obj->fk_product;
				$res=$this->db->query($sql);
				$i++;
			}
		}
	}

	function fillintervention($numintervention)
	{
		global $langs;
		// on r�cup�re les num�ro �quipements associ� � la facture, pour les afficher dans le d�tails de la facture
		$sql = "SELECT e.ref as refequipement, p.ref as refproduct";
		$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e, ".MAIN_DB_PREFIX."equipementevt as ee";
		$sql.= " , ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE  e.rowid = ee.fk_equipement ";
		$sql.= " AND p.rowid = e.fk_product";
		$sql.= " AND ee.fk_fichinter=".$numintervention;
		$sql.= " ORDER BY e.fk_product";
		dol_syslog(get_class($this)."::fillinvoice sql=".$sql, LOG_DEBUG);


		$resql=$this->db->query($sql);
		$serialLineInIntervention="";
		if ($resql) {	// on boucle sur les �quipements
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$serialLineInIntervention=$langs->trans(
								"SerialLineInIntervention", $obj->refproduct, $obj->refequipement
				);

				// on ajoute les num�ros d'�quipements � la suite
				$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter";
				$sql.= " SET note_public = concat_ws('<br>',note_public,'".$serialLineInIntervention."')";
				$sql.= " WHERE rowid=".$numintervention;

				$res=$this->db->query($sql);
				$i++;
			}
		}
	}


	// d�termination du nombre d'�quipement d�j� associ� � l'exp�dition
	function get_nbEquipementProductExpedition($fk_product, $fk_expedition)
	{
		$sql = "SELECT sum(e.quantity) as nb ";
		$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."equipementevt as ee on e.rowid = ee.fk_equipement ";
		$sql.= " WHERE e.fk_product=".$fk_product;
		$sql.= " AND ee.fk_expedition=".$fk_expedition;

		dol_syslog(get_class($this)."::get_nbEquipementProductExpedition sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				return $obj->nb;
			}
		}
		return 0;
	}

	function GetEquipementFromShipping($fk_facture, $fk_expedition)
	{
		global $langs;

		$sql = "SELECT e.rowid ";
		$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."equipementevt as ee on e.rowid = ee.fk_equipement ";
		$sql.= " WHERE ee.fk_expedition=".$fk_expedition;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql) {
			dol_syslog(get_class($this)."::GetEquipementFromShipping sql=".$sql, LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql) {	// on boucle sur les �quipements
				$num = $this->db->num_rows($resql);
				$i=0;
				while ($i < $num) {
					$serialLineInInvoice=$langs->trans("SerialLineInInvoice", $obj->ref);
					$obj = $this->db->fetch_object($resql);
					// on ajoute les num�ros d'�quipements � la suite
					$sql = "UPDATE ".MAIN_DB_PREFIX."equipement";
					$sql.= " SET fk_facture =".$fk_facture;
					$sql.= " WHERE rowid=".$obj->rowid;

					$res=$this->db->query($sql);
					$i++;
				}
			}
		}
		return 0;
	}


    /**
     * Find equipment status from dictionary
     *
     * @param   string      $statusCode     Code of equipment status in dictionary
     * @return  resource    SQL resource
     *
     * @throws Exception
     */
	public function findDictionaryEquipementEtatByCode($statusCode)
    {
        dol_syslog(__METHOD__ . ' statusCode=' . $statusCode, LOG_DEBUG);

        $sql  = "SELECT cee.rowid, cee.libelle, cee.coder";
        $sql .= " FROM " . MAIN_DB_PREFIX . "c_equipement_etat as cee";
        $sql .= " WHERE cee.code = '" . $this->db->escape($statusCode) . "'";
        $sql .= " AND cee.entity = " . getEntity($this->element);

        return $this->db->query($sql);
    }


    /**
     * Get equipment status from dictionary
     *
     * @param   string          $statusCode     Code of equipment status in dictionary
     * @return  null|Object
     *
     * @throws Exception
     */
	public function getDictionaryEquipementEtatByCode($statusCode)
    {
        global $langs;

        $obj = NULL;

        dol_syslog(__METHOD__ . ' statusCode=' . $statusCode, LOG_DEBUG);

        $resql = $this->findDictionaryEquipementEtatByCode($statusCode);
        if (!$resql) {
            $this->error    = $this->db->lasterror();
            $this->errors[] = $this->error;
            dol_syslog(__METHOD__ . ' Error:' . $this->error, LOG_ERR);
        } else {
            if ($this->db->num_rows($resql) > 0) {
                $obj = $this->db->fetch_object($resql);
            }
        }

        if ($obj === NULL) {
            $this->error    = $langs->trans("EquipementErrorStatusNotDefined", $langs->transnoentitiesnoconv($statusCode));
            $this->errors[] = $this->error;
            dol_syslog(__METHOD__ . ' Error:' . $this->error, LOG_ERR);
        }

        return $obj;
    }

    /**
     *  Load object product with id=$this->fk_product into $this->product
     *
     * @param   int     $productid      Id du product. Use this->fk_product if empty.
     * @return  int						<0 if KO, >0 if OK
     */
    function fetch_product($productid=null)
    {
	if (empty($productid)) $productid=$this->fk_product;

	if (empty($productid)) return 0;

        require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
        $product = new Product($this->db);
        $result=$product->fetch($productid);
        $this->product = $product;
        return $result;
    }
}

/**
 *	\class	  EquipementLigne
 *	\brief	  Classe permettant la gestion des lignes d'�v�nement intervention
 */
class Equipementevt extends CommonObject
{
	var $db;
	var $error;

	public $element='equipementevt';
	public $table_element='equipementevt';

    /**
     * Array of whitelist of properties keys for this object used for the API
     * @var  array
     *      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static public $API_WHITELIST_OF_PROPERTIES = array(
        "fk_equipement" => '', "fk_equipementevt_type" => '', "equipeventlib" => '', "fk_fichinter" => '', "fk_contrat" => '',
        "fk_expedition" => '', "fk_expeditiondet" => '', "fk_user_author" => '', "fk_retourproduits" => '', "fk_factory" => '',
        "fk_factorydet" => '', "ref_fichinter" => '', "ref_contrat" => '', "ref_expedition" => '', "array_options" => '',
        "desc" => '', "datec" => '', "dateo" => '', "datee" => '', "id" => '',
    );

    /**
     * Array of whitelist of properties keys for this object when is a linked object used for the API
     * @var  array
     *      if empty array then equal at $api_whitelist_of_properties
     *      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static public $API_WHITELIST_OF_PROPERTIES_LINKED_OBJECT = array(
    );

	// From llx_equipementevt
	var $rowid;
	var $fk_equipement;
	var $fk_equipementevt_type;
	var $equipeventlib;
	var $fk_fichinter;
	var $fk_contrat;
	var $fk_project;
	var $fk_expedition;
    var $fk_expeditiondet;
	var $fk_user_author;
    var $fk_retourproduits;
    var $fk_factory;
    var $fk_factorydet;
	// pour �viter de se taper une recherche pour chaque ligne
	var $ref_fichinter;
	var $ref_contrat;
	var $ref_expedition;
	var $array_options;

	var $desc;					// Description de la ligne
	var $datec;					// Date creation l'evenement
	var $dateo;					// Date debut de l'evenement
	var $datee;					// Date fin de l'evenement
	var $fulldayevent;
	var $total_ht=0;			//montant total de l'�v�n�ment (pour information)

	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Retrieve the line of equipement event
	 *
	 *	@param  int		$rowid		Line id
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT ee.rowid, ee.fk_equipement, ee.description, ee.datec, ee.fk_equipementevt_type, ';
		$sql.= ' eet.libelle as equipeventlib, ee.datee, ee.dateo, ee.fulldayevent, ee.total_ht, ';
		$sql.= ' ee.fk_user_author, ee.fk_fichinter, ee.fk_contrat, ee.fk_expedition, ee.fk_expeditiondet, ee.fk_project ';
        $sql.= ", ee.fk_retourproduits";
        $sql.= ", ee.fk_factory";
        $sql.= ", ee.fk_factorydet";
		$sql.= ' FROM '.MAIN_DB_PREFIX.'equipementevt as ee';
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipementevt_type as eet on ee.fk_equipementevt_type = eet.rowid";
		$sql.= ' WHERE ee.rowid = '.$rowid;

		dol_syslog("EquipementEvt::fetch sql=".$sql);
		$result = $this->db->query($sql);
		if ($result) {
			$objp 	= $this->db->fetch_object($result);

            $this->id 						= $objp->rowid;
			$this->rowid					= $objp->rowid;
			$this->fk_equipement			= $objp->fk_equipement;
			$this->fk_equipementevt_type	= $objp->fk_equipementevt_type;
			$this->equipeventlib			= $objp->equipeventlib;
			$this->datec					= $this->db->jdate($objp->datec);
			$this->datee					= $this->db->jdate($objp->datee);
			$this->dateo					= $this->db->jdate($objp->dateo);
			$this->total_ht					= price2num($objp->total_ht);
			$this->fulldayevent				= $objp->fulldayevent;
			$this->desc						= $objp->description;
			$this->fk_fichinter				= $objp->fk_fichinter;
			$this->fk_contrat				= $objp->fk_contrat;
			$this->fk_expedition			= $objp->fk_expedition;
            $this->fk_expeditiondet			= $objp->fk_expeditiondet;
			$this->fk_project				= $objp->fk_project;
            $this->fk_retourproduits	    = $objp->fk_retourproduits;
			$this->fk_factory			    = $objp->fk_factory;
            $this->fk_factorydet			= $objp->fk_factorydet;
            $this->fk_user_author			= $objp->fk_user_author;
            $this->fetch_optionals();

			$this->db->free($result);
			return 1;
		} else {
			$this->error=$this->db->error().' sql='.$sql;
			dol_print_error($this->db, $this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *	Insert the line into database
	 *
	 *	@return		int		<0 if ko, >0 if ok
	 */
	function insert($notrigger=0)
	{
	    global $conf, $user;
		dol_syslog(get_class($this)."::insert rang=".$this->rang);

		$now=dol_now();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'equipementevt';
		$sql.= ' (fk_equipement, fk_equipementevt_type, description,';
		$sql.= ' fulldayevent, fk_fichinter, fk_contrat, fk_expedition, fk_expeditiondet, fk_project,';
		$sql.= ' datec, dateo, datee, total_ht, fk_user_author';
        $sql.= ", fk_retourproduits";
        $sql.= ", fk_factory";
        $sql.= ", fk_factorydet";
        $sql.= ")";
		$sql.= " VALUES (".$this->fk_equipement.",";
		$sql.= " ".($this->fk_equipementevt_type?$this->fk_equipementevt_type:"null").",";
		$sql.= " '".($this->desc?$this->db->escape($this->desc):"non saisie")."',";
		$sql.= " ".($this->fulldayevent?1:"null").",";
		$sql.= " ".($this->fk_fichinter?$this->fk_fichinter:"null").",";
		$sql.= " ".($this->fk_contrat?$this->fk_contrat:"null").",";
		$sql.= " ".($this->fk_expedition?$this->fk_expedition:"null").",";
        $sql.= " ".($this->fk_expeditiondet?$this->fk_expeditiondet:"null").",";
		$sql.= " ".($this->fk_project?$this->fk_project:"null").",";
		$sql.= " '".$this->db->idate($now)."',"; // date de cr�ation aliment� automatiquement
		$sql.= " '".$this->db->idate($this->dateo)."',";
		$sql.= " '".$this->db->idate($this->datee)."',";
		$sql.= ' '.($this->total_ht?price2num($this->total_ht):"null").",";
		$sql.= ' '.($this->fk_user_author?$this->fk_user_author:"null");
        $sql.= ", ".($this->fk_retourproduits?$this->fk_retourproduits:"null");
        $sql.= ", ".($this->fk_factory?$this->fk_factory:"null");
        $sql.= ", ".($this->fk_factorydet?$this->fk_factorydet:"null");
		$sql.= ')';
//print $sql.'<br>';
		dol_syslog(get_class($this)."::insert sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql) {
			// on g�re les extra fields
			$this->rowid=$this->db->last_insert_id(MAIN_DB_PREFIX.'equipementevt');

			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
				$this->id=$this->rowid;
				$result=$this->insertExtraFields();
				if ($result < 0)
                    return -1;
			}

            if (! $notrigger) {
                // Call trigger
                $result = $this->call_trigger('LINEEQUIPEMENTEVT_INSERT', $user);
                if ($result < 0) {
                    return -1;
                }
                // End call triggers
            }

			return $this->rowid;
		} else {
			$this->error=$this->db->error()." sql=".$sql;
			dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *	Delete a intervention line
	 *
	 *	@return	 int		>0 if ok, <0 if ko
	 */
	function deleteline($notrigger=0)
	{
	    global $conf, $user;

		if ($this->statut == 0) {
			dol_syslog(get_class($this)."::deleteline lineid=".$this->rowid);
			$this->db->begin();

            if (! $notrigger) {
                // Call trigger
                $result = $this->call_trigger('LINEEQUIPEMENTEVT_DELETE', $user);
                if ($result < 0) {
                    dol_syslog(get_class($this)."::delete error -3 ".$this->error, LOG_ERR);
                    $this->db->rollback();
                    return -3;
                }
                // End call triggers
            }

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."equipementevt WHERE rowid = ".$this->rowid;
			$resql = $this->db->query($sql);
			dol_syslog(get_class($this)."::deleteline sql=".$sql);

			if ($resql) {
				// Remove extrafields
				if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
					$this->id=$this->rowid;
					$result=$this->deleteExtraFields();
					if ($result < 0) {
						dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
                        $this->db->rollback();
                        return -4;
					}
				}

				$this->db->commit();
				return 1;
			} else {
				$this->error=$this->db->error()." sql=".$sql;
				dol_syslog(get_class($this)."::deleteline Error ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		} else
			return -2;
	}

	function update($notrigger=0)
	{
	    global $conf, $user;
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."equipementevt ";
		$sql.= "SET description='".$this->db->escape($this->desc)."'";
		$sql.= ",	fk_equipementevt_type=".($this->fk_equipementevt_type?$this->fk_equipementevt_type:"null");
		$sql.= ",	datee=".($this->datee?"'".$this->db->idate($this->datee)."'":"null");
		$sql.= ",	dateo=".($this->dateo?"'".$this->db->idate($this->dateo)."'":"null");
		$sql.= ",	fulldayevent=".($this->fulldayevent?1:"null");
		$sql.= ",	total_ht=".($this->total_ht?price2num($this->total_ht):"null");
		$sql.= ", 	fk_fichinter=".($this->fk_fichinter?$this->fk_fichinter:"null");
		$sql.= ", 	fk_contrat=".($this->fk_contrat?$this->fk_contrat:"null");
		$sql.= ", 	fk_expedition=".($this->fk_expedition?$this->fk_expedition:"null");
        $sql.= ", 	fk_expeditiondet=".($this->fk_expeditiondet?$this->fk_expeditiondet:"null");
		$sql.= ", 	fk_project=".($this->fk_project?$this->fk_project:"null");
		$sql.= ", 	fk_user_author=".($this->fk_user_author?$this->fk_user_author:"null");

		$sql.= " WHERE rowid = ".$this->rowid;

		dol_syslog(get_class($this)."::update sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql) {
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
				$this->id=$this->rowid;
				$result=$this->insertExtraFields();
				if ($result < 0) {
                    $this->db->rollback();
				return -1;
                }
			}
            if (! $notrigger) {
                // Call trigger
                $result = $this->call_trigger('LINEEQUIPEMENTEVT_UPDATE', $user);
                if ($result < 0) {
                    dol_syslog(get_class($this)."::delete error -3 ".$this->error, LOG_ERR);
                    $this->db->rollback();
                    return -3;
                }
                // End call triggers
            }
			$this->db->commit();
			return 1;
		} else {
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::update Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}
}

/**
 *	\class	  EquipementLigne
 *	\brief	  Classe permettant la gestion des lignes d'�v�nement intervention
 */
class Equipementconsumption extends CommonObject
{
	var $db;
	var $error;

	public $element='equipementconsumption';
	public $table_element='equipementconsumption';

    /**
     * Array of whitelist of properties keys for this object used for the API
     * @var  array
     *      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static public $API_WHITELIST_OF_PROPERTIES = array(
        "id" => '', "fk_equipement" => '', "fk_equipementcons" => '', "fk_equipementevt" => '', "datecons" => '',
        "desc" => '', "fk_product" => '', "price" => '', "fk_entrepot" => '', "fk_user_author" => '',
    );

    /**
     * Array of whitelist of properties keys for this object when is a linked object used for the API
     * @var  array
     *      if empty array then equal at $api_whitelist_of_properties
     *      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static public $API_WHITELIST_OF_PROPERTIES_LINKED_OBJECT = array(
    );

	// From llx_equipementevt
	var $rowid;
	var $fk_equipement;
	var $fk_product;
	var $fk_equipementcons;
	var $fk_equipementevt;
	var $fk_entrepot;
	var $fk_entrepot_dest;
	var $price;
	var $fk_user_author;
	var $array_options;

	var $desc;					// Description de la ligne
	var $datecons;					// Date de consommation


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Retrieve the line of equipement consumption
	 *
	 *	@param  int		$rowid		Line id
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT ec.rowid, ec.fk_equipement, ec.fk_equipementcons, fk_equipementevt, price';
		$sql.= ' , ec.datecons, ec.description, ec.fk_product, ec.fk_entrepot, ec.fk_user_author';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'equipementconsumption as ec';
		$sql.= ' WHERE ec.rowid = '.$rowid;

		dol_syslog("EquipementEvt::fetch sql=".$sql);
		$result = $this->db->query($sql);
		if ($result) {
			$objp 	= $this->db->fetch_object($result);

            $this->rowid				= $objp->rowid;
            $this->id				    = $objp->rowid;
			$this->fk_equipement		= $objp->fk_equipement;
			$this->fk_equipementcons	= $objp->fk_equipementcons;
			$this->fk_equipementevt		= $objp->fk_equipementevt;
			$this->datecons				= $this->db->jdate($objp->datecons);
			$this->desc					= $objp->description;
			$this->fk_product			= $objp->fk_product;
			$this->price				= $objp->price;
			$this->fk_entrepot			= $objp->fk_entrepot;
			$this->fk_user_author		= $objp->fk_user_author;

			$this->db->free($result);
			return 1;
		} else {
			$this->error=$this->db->error().' sql='.$sql;
			dol_print_error($this->db, $this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *	Insert the line into database
	 *
	 *	@return		int		<0 if ko, >0 if ok
	 */
	function insert()
	{
		global $user, $langs;
		dol_syslog(get_class($this)."::insert rang=".$this->rang);

		$now=dol_now();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'equipementconsumption';
		$sql.= ' (fk_equipement, fk_equipementcons, description,';
		$sql.= ' datecons, fk_product, price, fk_entrepot, fk_user_author)';
		$sql.= " VALUES (".$this->fk_equipement.",";
		$sql.= " ".($this->fk_equipementcons?$this->fk_equipementcons:"null").",";
		$sql.= " '".($this->desc?$this->db->escape($this->desc):"")."',";
		$sql.= " '".$this->db->idate($this->datecons)."',";
		$sql.= ' '.($this->fk_product?$this->fk_product:"null").",";
		$sql.= ' '.($this->price?$this->price:0).",";
		$sql.= ' '.($this->fk_entrepot?$this->fk_entrepot:"null").",";
		$sql.= ' '.($this->fk_user_author?$this->fk_user_author:"null");
		$sql.= ')';
//print $sql.'<br>';
		dol_syslog(get_class($this)."::insert sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql) {
			$newconso = $this->db->last_insert_id(MAIN_DB_PREFIX.'equipementconsumption');
			// si les entrepots sont diff�rent on cr�e un mouvement
			if ($this->fk_entrepotmove==1  && $this->fk_entrepot_dest != $this->fk_entrepot) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$equipementConsommant = new Equipement($this->db);
				$equipementConsommant->fetch($this->fk_equipementcons);
				$mouvP = new MouvementStock($this->db);
				$mouvP->origin = $equipementConsommant;
				$mouvP->origin->id = $this->fk_equipementcons;

				// le prix est � 0 pour ne pas impacter le pmp
				if ( $this->fk_entrepot > 0 ) // si il y avait un ancien entrepot
					$idmv=$mouvP->livraison(
									$user, $this->fk_product, $this->fk_entrepot, 1, 0,
									$langs->trans("EquipementConsumptionOut")
					);

				if ($this->fk_entrepot_dest > 0 )
					$idmv=$mouvP->reception(
									$user, $this->fk_product, $this->fk_entrepot_dest, 1, 0,
									$langs->trans("EquipementConsumptionIn")
					);
			}
			return $newconso;
		} else {
			$this->error=$this->db->error()." sql=".$sql;
			dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *	Delete a intervention line
	 *
	 *	@return	 int		>0 if ok, <0 if ko
	 */
	function deleteline()
	{
		if ($this->statut == 0) {
			dol_syslog(get_class($this)."::deleteline lineid=".$this->rowid);
			$this->db->begin();

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."equipementconsumption WHERE rowid = ".$this->rowid;
			$resql = $this->db->query($sql);
			dol_syslog(get_class($this)."::deleteline sql=".$sql);

			if ($resql) {
				$this->db->commit();
				return $resql;
			} else {
				$this->error=$this->db->error()." sql=".$sql;
				dol_syslog(get_class($this)."::deleteline Error ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		} else
			return -2;
	}

	function update()
	{
		global $langs;

		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."equipementconsumption ";
		$sql.= "SET description='".$this->db->escape($this->desc)."'";
		$sql.= ",	datecons=".($this->datecons?"'".$this->db->idate($this->datecons)."'":"null");
		$sql.= ",	fk_equipementcons=".($this->fk_equipementcons?$this->fk_equipementcons:"null");
		$sql.= ", 	price=".($this->price?$this->price:"null");
		$sql.= ", 	fk_entrepot=".($this->fk_entrepot_dest?$this->fk_entrepot_dest:"null");
		$sql.= ", 	fk_user_author=".($this->fk_user_author?$this->fk_user_author:"null");

		$sql.= " WHERE rowid = ".$this->rowid;

		dol_syslog(get_class($this)."::update sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql) {
			if ($this->fk_entrepotmove==1 && $this->fk_entrepot_dest != $this->fk_entrepot) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$equipementConsommant = new Equipement($this->db);
				$equipementConsommant->fetch($this->fk_equipementcons);
				$mouvP = new MouvementStock($this->db);
				$mouvP->origin = $equipementConsommant;
				$mouvP->origin->id = $this->fk_equipementcons;

				// le prix est � 0 pour ne pas impacter le pmp
				if ( $this->fk_entrepot > 0 ) // si il y avait un ancien entrepot
					$idmv=$mouvP->livraison(
									$user, $this->fk_product, $this->fk_entrepot, 1, 0,
									$langs->trans("EquipementConsumptionOut")
					);

				if ($this->fk_entrepot_dest > 0 )
					$idmv=$mouvP->reception(
									$user, $this->fk_product, $this->fk_entrepot_dest, 1, 0,
									$langs->trans("EquipementConsumptionIn")
					);
			}

			$this->db->commit();
			return $resql;
		} else {
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::update Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}
}