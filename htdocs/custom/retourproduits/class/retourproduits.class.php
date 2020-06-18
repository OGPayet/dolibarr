<?php
/* Copyright (C) 2003-2008	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2007		Franky Van Liedekerke	<franky.van.liedekerke@telenet.be>
 * Copyright (C) 2006-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2017	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2014		Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2014-2015  Francis Appels          <francis.appels@yahoo.com>
 * Copyright (C) 2015       Claudio Aschieri        <c.aschieri@19.coop>
 * Copyright (C) 2016		Ferran Marcet			<fmarcet@2byte.es>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/expedition/class/expedition.class.php
 *  \ingroup    expedition
 *  \brief      Fichier de la classe de gestion des expeditions
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/commonobjectline.class.php";
if (! empty($conf->propal->enabled)) require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->productbatch->enabled)) require_once DOL_DOCUMENT_ROOT.'/expedition/class/expeditionbatch.class.php';


/**
 *	Class to manage shipments
 */
class RetourProduits extends CommonObject
{
	public $element="retourproduits";
	public $fk_element="fk_retourproduits";
	public $table_element="retourproduits";
	public $table_element_line="retourproduitsdet";
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
    public $picto = 'sending';

	var $socid;
	var $ref_customer;
	var $ref_int;
	var $brouillon;
	var $entrepot_id;
	var $lines=array();
	var $tracking_number;
	var $tracking_url;
	var $billed;
	var $model_pdf;

	var $trueWeight;
	var $weight_units;
	var $trueWidth;
	var $width_units;
	var $trueHeight;
	var $height_units;
	var $trueDepth;
	var $depth_units;
	// A denormalized value
	var $trueSize;

	var $date_delivery;		// Date delivery planed
	/**
	 * @deprecated
	 * @see date_shipping
	 */
	var $date;
	/**
	 * @deprecated
	 * @see date_shipping
	 */
	var $date_expedition;
	/**
	 * Effective delivery date
	 * @var int
	 */
	public $date_shipping;
	var $date_creation;
	var $date_valid;

	var $meths;
	var $listmeths;			// List of carriers


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CLOSED = 2;



	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->lines = array();
		$this->products = array();

                $this->model_pdf = 'retourproduits_rouget';
		// List of long language codes for status
		$this->statuts = array();
		$this->statuts[-1] = 'StatusSendingCanceled';
		$this->statuts[0]  = 'StatusSendingDraft';
		$this->statuts[1]  = 'StatusSendingValidated';
		$this->statuts[2]  = 'StatusSendingProcessed';
	}

	/**
	 *	Return next contract ref
	 *
	 *	@param	Societe		$soc	Thirdparty object
	 *	@return string				Free reference for contract
	 */
	function getNextNumRef($soc)
	{
		global $langs, $conf;
		$langs->load("sendings");

        $defaultref = '';
        $modele = empty($conf->global->RETURNPRODUCTS_ADDON_NUMBER) ? 'mod_retourproduits_simple' : $conf->global->RETURNPRODUCTS_ADDON_NUMBER;

        // Search template files
        $file = '';
        $classname = '';
        $filefound = 0;
        $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
        foreach ($dirmodels as $reldir) {
            $file = dol_buildpath($reldir . "core/modules/" . $modele . '.php', 0);
            if (file_exists($file)) {
                $filefound = 1;
                $classname = $modele;
                break;
            }
        }
        if ($filefound) {
            $result = dol_include_once($reldir . "core/modules/" . $modele . '.php');
            $modReturnProducts = new $classname;

            $defaultref = $modReturnProducts->getNextValue($soc, $this);
        }

        if (is_numeric($defaultref) && $defaultref <= 0) {
            $defaultref = 'XXX';
        }
        return $defaultref;

	}

	/**
	 *  Create retourproduits en base
	 *
	 *  @param	User	$user       Objet du user qui cree
   * 	@param		int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return int 				<0 si erreur, id retourproduits creee si ok
	 */
	function create($user, $notrigger=0)
	{
		global $conf, $hookmanager;

		$now=dol_now();

		require_once DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php';
		$error = 0;

		// Clean parameters
		$this->brouillon = 1;
		$this->tracking_number = dol_sanitizeFileName($this->tracking_number);
		if (empty($this->fk_project)) $this->fk_project = 0;

		$this->user = $user;


		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."retourproduits (";
		$sql.= "ref";
		$sql.= ", entity";
		$sql.= ", ref_customer";
		$sql.= ", ref_int";
		$sql.= ", date_creation";
		$sql.= ", fk_user_author";
		$sql.= ", date_expedition";
		$sql.= ", date_delivery";
		$sql.= ", fk_soc";
		$sql.= ", fk_projet";
		$sql.= ", fk_address";
		$sql.= ", fk_shipping_method";
		$sql.= ", tracking_number";
/*		$sql.= ", weight";
		$sql.= ", size";
		$sql.= ", width";
		$sql.= ", height";
		$sql.= ", weight_units";
		$sql.= ", size_units";*/
		$sql.= ", note_private";
		$sql.= ", note_public";
		$sql.= ", model_pdf";
		$sql.= ", fk_incoterms, location_incoterms";
		$sql.= ") VALUES (";
		$sql.= "'(PROV)'";
		$sql.= ", ".$conf->entity;
		$sql.= ", ".($this->ref_customer?"'".$this->db->escape($this->ref_customer)."'":"null");
		$sql.= ", ".($this->ref_int?"'".$this->db->escape($this->ref_int)."'":"null");
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", ".$user->id;
		$sql.= ", ".($this->date_expedition>0?"'".$this->db->idate($this->date_expedition)."'":"null");
		$sql.= ", ".($this->date_delivery>0?"'".$this->db->idate($this->date_delivery)."'":"null");
		$sql.= ", ".$this->socid;
		$sql.= ", ".$this->fk_project;
		$sql.= ", ".($this->fk_delivery_address>0?$this->fk_delivery_address:"null");
		$sql.= ", ".($this->shipping_method_id>0?$this->shipping_method_id:"null");
		$sql.= ", '".$this->db->escape($this->tracking_number)."'";
		//$sql.= ", 'tracking_number'";
/*		$sql.= ", ".$this->weight;
		$sql.= ", ".$this->sizeS;	// TODO Should use this->trueDepth
		$sql.= ", ".$this->sizeW;	// TODO Should use this->trueWidth
		$sql.= ", ".$this->sizeH;	// TODO Should use this->trueHeight
		$sql.= ", ".$this->weight_units;
		$sql.= ", ".$this->size_units;*/
		$sql.= ", ".(!empty($this->note_private)?"'".$this->db->escape($this->note_private)."'":"null");
		$sql.= ", ".(!empty($this->note_public)?"'".$this->db->escape($this->note_public)."'":"null");
		$sql.= ", ".(!empty($this->model_pdf)?"'".$this->db->escape($this->model_pdf)."'":"null");
        $sql.= ", ".(int) $this->fk_incoterms;
        $sql.= ", '".$this->db->escape($this->location_incoterms)."'";
		$sql.= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."retourproduits");

			$sql = "UPDATE ".MAIN_DB_PREFIX."retourproduits";
			$sql.= " SET ref = '(RETURN".$this->id.")'";
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			if ($this->db->query($sql))
			{
				// Insertion des lignes
				$num=count($this->lines);
				for ($i = 0; $i < $num; $i++)
				{
					if (! isset($this->lines[$i]->detail_batch))
					{	// no batch management
						if (! $this->create_line($this->lines[$i]->fk_product, $this->lines[$i]->fk_equipement, $this->lines[$i]->fk_entrepot_dest, $this->lines[$i]->qty, $this->lines[$i]->fk_origin_line) > 0)
						{
							$error++;
						}
					}
					else
					{	// with batch management
						if (! $this->create_line_batch($this->lines[$i],$this->lines[$i]->array_options) > 0)
						{
							$error++;
						}
					}
				}

				if (! $error && $this->id && $this->origin_id)
				{
					$ret = $this->add_object_linked();
					if (!$ret)
					{
						$error++;
					}
				}

				// Actions on extra fields (by external module or standard code)
				// TODO le hook fait double emploi avec le trigger !!
				$hookmanager->initHooks(array('retourproduitsdao'));
				$parameters=array('socid'=>$this->id);
				$reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
				if (empty($reshook))
				{
					if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
					{
						$result=$this->insertExtraFields();
						if ($result < 0)
						{
							$error++;
						}
					}
				}
				else if ($reshook < 0) $error++;

				if (! $error && ! $notrigger)
				{
                    // Call trigger
                    $result=$this->call_trigger('RETURN_CREATE',$user);
                    if ($result < 0) { $error++; }
                    // End call triggers

					if (! $error)
					{
						$this->db->commit();
						return $this->id;
					}
					else
					{
						foreach($this->errors as $errmsg)
						{
							dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
							$this->error.=($this->error?', '.$errmsg:$errmsg);
						}
						$this->db->rollback();
						return -1*$error;
					}

				}
				else
				{
					$error++;
					$this->error=$this->db->lasterror()." - sql=$sql";
					$this->db->rollback();
					return -3;
				}
			}
			else
			{
				$error++;
				$this->error=$this->db->lasterror()." - sql=$sql";
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$error++;
			$this->error=$this->db->error()." - sql=$sql";
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Create a expedition line
	 *
	 * @param 	int		$entrepot_id		Id of warehouse
	 * @param 	int		$equipement_id		Id equipement evement
	 * @param 	int		$entrepot			Id entrepot
	 * @param 	int		$qty				Quantity
	 * @param 	int		$origin_id			Origine id de la ligne commande
	 * @param	array	$array_options		extrafields array
	 * @return	int							<0 if KO, line_id if OK
	 */
	function create_line($product_id, $eq_id, $entrepot_id, $qty, $origin_id,$array_options=0)
	{
		global $conf;
		$error = 0;
		$line_id = 0;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."retourproduitsdet (";
		$sql.= "fk_retourproduits";
		$sql.= ", fk_origin_line" ;
		$sql.= ", fk_product";
		$sql.= ", fk_equipement";
		$sql.= ", fk_entrepot_dest";
		$sql.= ", qty";
		$sql.= ") VALUES (";
		$sql.= $this->id;
		$sql.= ", ".$origin_id;
		$sql.= ", ".$product_id;
		$sql.= ", ".$eq_id;
		$sql.= ", ".$entrepot_id;
		$sql.= ", ".$qty;
		$sql.= ")";

		dol_syslog(get_class($this)."::create_line", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
		    $line_id = $this->db->last_insert_id(MAIN_DB_PREFIX."expeditiondet");
		}
		else
		{
			$error++;
		}

		if (! $error && empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($array_options) && count($array_options)>0) // For avoid conflicts if trigger used
		{
			$expeditionline = new ExpeditionLigne($this->db);
			$expeditionline->array_options=$array_options;
			$expeditionline->id= $this->db->last_insert_id(MAIN_DB_PREFIX.$expeditionline->table_element);
			$result=$expeditionline->insertExtraFields();
			if ($result < 0)
			{
				$this->error[]=$expeditionline->error;
				$error++;
			}
		}
		if (! $error) return $line_id;
		else return -1;
	}


	/**
	 * Create the detail (eat-by date) of the expedition line
	 *
	 * @param 	object		$line_ext		full line informations
	 * @param	array		$array_options		extrafields array
	 * @return	int							<0 if KO, >0 if OK
	 */
	function create_line_batch($line_ext,$array_options=0)
	{
		$error = 0;
		$stockLocationQty = array(); // associated array with batch qty in stock location

		$tab=$line_ext->detail_batch;
		// create stockLocation Qty array
		foreach ($tab as $detbatch)
		{
			if ($detbatch->entrepot_id)
			{
				$stockLocationQty[$detbatch->entrepot_id] += $detbatch->dluo_qty;
			}
		}
		// create shipment lines
		foreach ($stockLocationQty as $stockLocation => $qty)
		{
			if (($line_id = $this->create_line($stockLocation,$line_ext->origin_line_id,$qty,$array_options)) < 0)
			{
				$error++;
			}
			else
			{
				// create shipment batch lines for stockLocation
				foreach ($tab as $detbatch)
				{
					if ($detbatch->entrepot_id == $stockLocation){
						if (! ($detbatch->create($line_id) >0))		// Create an expeditionlinebatch
						{
							$error++;
						}
					}
				}
			}
		}

		if (! $error) return 1;
		else return -1;
	}

	/**
	 *	Get object and lines from database
	 *
	 *	@param	int		$id       	Id of object to load
	 * 	@param	string	$ref		Ref of object
	 * 	@param	string	$ref_ext	External reference of object
     * 	@param	string	$ref_int	Internal reference of other object
	 *	@return int			        >0 if OK, 0 if not found, <0 if KO
	 */
	function fetch($id, $ref='', $ref_ext='', $ref_int='')
	{
		global $conf;

		// Check parameters
		if (empty($id) && empty($ref) && empty($ref_ext) && empty($ref_int)) return -1;

		$sql = "SELECT rp.rowid, rp.ref, rp.fk_soc as socid, rp.date_creation, rp.ref_customer, rp.ref_ext, rp.ref_int, rp.fk_user_author, rp.fk_statut";
		$sql.= ", rp.weight, rp.weight_units, rp.size, rp.size_units, rp.width, rp.height";
		$sql.= ", rp.date_expedition as date_expedition, rp.model_pdf, rp.fk_address, rp.date_delivery";
		$sql.= ", rp.fk_shipping_method, rp.tracking_number";
		$sql.= ", el.fk_source as origin_id, el.sourcetype as origin";
		$sql.= ", rp.note_private, rp.note_public";
        $sql.= ', rp.fk_incoterms, rp.location_incoterms';
        $sql.= ', i.libelle as libelle_incoterms';
		$sql.= " FROM ".MAIN_DB_PREFIX."retourproduits as rp";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_target = rp.rowid AND el.targettype = '".$this->db->escape($this->element)."'";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON rp.fk_incoterms = i.rowid';
		$sql.= " WHERE rp.entity IN (".getEntity('retourproduits').")";
		if ($id)   	  $sql.= " AND rp.rowid=".$id;
        if ($ref)     $sql.= " AND rp.ref='".$this->db->escape($ref)."'";
        if ($ref_ext) $sql.= " AND rp.ref_ext='".$this->db->escape($ref_ext)."'";
        if ($ref_int) $sql.= " AND rp.ref_int='".$this->db->escape($ref_int)."'";

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id                   = $obj->rowid;

				$this->ref                  = $obj->ref;
				$this->socid                = $obj->socid;
				$this->ref_customer			= $obj->ref_customer;
				$this->ref_ext				= $obj->ref_ext;
				$this->ref_int				= $obj->ref_int;
				$this->statut               = $obj->fk_statut;
				$this->user_author_id       = $obj->fk_user_author;
				$this->date_creation        = $this->db->jdate($obj->date_creation);
				$this->date                 = $this->db->jdate($obj->date_expedition);	// TODO deprecated
				$this->date_expedition      = $this->db->jdate($obj->date_expedition);	// TODO deprecated
				$this->date_shipping        = $this->db->jdate($obj->date_expedition);	// Date real
				$this->date_delivery        = $this->db->jdate($obj->date_delivery);	// Date planed
				$this->fk_delivery_address  = $obj->fk_address;
				$this->modelpdf             = $obj->model_pdf;
				$this->shipping_method_id	= $obj->fk_shipping_method;
				$this->tracking_number      = $obj->tracking_number;
				$this->origin               = ($obj->origin?$obj->origin:'commande'); // For compatibility
				$this->origin_id            = $obj->origin_id;
				$this->billed				= ($obj->fk_statut==2?1:0);

				$this->trueWeight           = $obj->weight;
				$this->weight_units         = $obj->weight_units;

				$this->trueWidth            = $obj->width;
				$this->width_units          = $obj->size_units;
				$this->trueHeight           = $obj->height;
				$this->height_units         = $obj->size_units;
				$this->trueDepth            = $obj->size;
				$this->depth_units          = $obj->size_units;

				$this->note_public          = $obj->note_public;
				$this->note_private         = $obj->note_private;

				// A denormalized value
				$this->trueSize           	= $obj->size."x".$obj->width."x".$obj->height;
				$this->size_units           = $obj->size_units;

				//Incoterms
				$this->fk_incoterms = $obj->fk_incoterms;
				$this->location_incoterms = $obj->location_incoterms;
				$this->libelle_incoterms = $obj->libelle_incoterms;

				$this->db->free($result);

				if ($this->statut == 0) $this->brouillon = 1;

				$file = $conf->retourproduits->dir_output . '/' .get_exdir($this->id, 2, 0, 0, $this, 'shipment') . "/" . $this->id.".pdf";
				$this->pdf_filename = $file;

				// Tracking url
				$this->GetUrlTrackingStatus($obj->tracking_number);

				/*
				 * Thirparty
				 */
				$result=$this->fetch_thirdparty();

				// Retrieve all extrafields for expedition
				// fetch optionals attributes and labels
				require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
				$extrafields=new ExtraFields($this->db);
				$extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
				$this->fetch_optionals($this->id,$extralabels);

				/*
				 * Lines
				 */
				$result=$this->fetch_lines();
				if ($result < 0)
				{
					return -3;
				}

				return 1;
			}
			else
			{
				dol_syslog(get_class($this).'::Fetch no expedition found', LOG_ERR);
				$this->error='Delivery with id '.$id.' not found';
				return 0;
			}
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *  Validate object and update stock if option enabled
	 *
	 *  @param      User		$user       Object user that validate
     *  @param		int			$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return     int						<0 if OK, >0 if KO
	 */
	function valid($user, $notrigger=0)
	{
		global $conf, $langs;


        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		dol_syslog(get_class($this)."::valid");

		// Protection
		if ($this->statut)
		{
			dol_syslog(get_class($this)."::valid no draft status", LOG_WARNING);
			return 0;
		}

        if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->expedition->creer))
	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->expedition->shipping_advance->validate))))
		{
			$this->error='Permission denied';
			dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
			return -1;
		}

		$this->db->begin();

		$error = 0;

		// Define new ref
		$soc = new Societe($this->db);
		$soc->fetch($this->socid);

		// Class of company linked to order
		$result=$soc->set_as_client();

		// Define new ref
		if (! $error && (preg_match('/^[\(]?RETURN/i', $this->ref) || empty($this->ref))) // empty should not happened, but when it occurs, the test save life
		{
			$numref = $this->getNextNumRef($soc);
		}
		else
		{
			$numref = "RP".$this->id;
		}
        $this->newref = $numref;

		$now=dol_now();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX."retourproduits SET";
		$sql.= " ref='".$numref."'";
		$sql.= ", fk_statut = 1";
		$sql.= ", date_valid = '".$this->db->idate($now)."'";
		$sql.= ", fk_user_valid = ".$user->id;
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::valid update retourproduits", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (! $resql)
		{
			$this->error=$this->db->lasterror();
			$error++;
		}

		// If stock increment is done on sending (recommanded choice)
		if (! $error && ! empty($conf->stock->enabled))
		{
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

			$langs->load("agenda");

			// Loop on each product line to add a stock movement
			// TODO in future, shipment lines may not be linked to order line
			$sql = "SELECT cd.fk_product, cd.subprice,";
			$sql.= " det.rowid, det.qty, det.fk_entrepot_dest,";
			$sql.= " rpb.rowid as rpbrowid, rpb.eatby, rpb.sellby, rpb.batch, rpb.qty as rpbqty, rpb.fk_dest_stock";
			$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
			$sql.= " ".MAIN_DB_PREFIX."retourproduitsdet as det";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."retourproduitsdet_batch as rpb on rpb.fk_retourproduitsdet = det.rowid";
			$sql.= " WHERE det.fk_retourproduits = ".$this->id;
			$sql.= " AND cd.rowid = det.fk_origin_line";

			dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$cpt = $this->db->num_rows($resql);
				for ($i = 0; $i < $cpt; $i++)
				{
					$obj = $this->db->fetch_object($resql);
					if (empty($obj->rpbrowid))
					{
						$qty = -$obj->qty;
					}
					else
					{
						$qty = -$obj->rpbqty;
					}
					if ($qty >= 0) continue;
					dol_syslog(get_class($this)."::valid movement index ".$i." ed.rowid=".$obj->rowid." rpb.rowid=".$obj->rpbrowid);

					//var_dump($this->lines[$i]);
					$mouvS = new MouvementStock($this->db);
					$mouvS->origin = &$this;

					if (empty($obj->rpbrowid))
					{
						// line without batch detail

						// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record.
						$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot_dest, $qty, $obj->subprice, $langs->trans("ReturnValidatedInDolibarr",$numref));
						if ($result < 0) {
							$error++;
							$this->errors[]=$mouvS->error;
							$this->errors = array_merge($this->errors, $mouvS->errors);
							break;
						}
					}
					else
					{
						// line with batch detail

						// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record.
					    // Note: ->fk_origin_stock = id into table llx_product_batch (may be rename into llx_product_stock_batch in another version)
						$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->subprice, $langs->trans("ShipmentValidatedInDolibarr",$numref), '', $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch, $obj->fk_origin_stock);
						if ($result < 0) {
							$error++;
							$this->errors[]=$mouvS->error;
							$this->errors = array_merge($this->errors, $mouvS->errors);
							break;
						}
					}
				}
			}
			else
			{
				$this->db->rollback();
				$this->error=$this->db->error();
				return -2;
			}

		}

		if (! $error) {
		    $this->fetch_lines();
		    dol_include_once('/equipement/class/equipement.class.php');

            $fk_equipementevt_type = dol_getIdFromCode($this->db, 'RETURN', 'c_equipementevt_type', 'code', 'rowid');
            $now = dol_now();

		    foreach ($this->lines as $line) {
		        if ($line->fk_equipement > 0) {
                    $equipement = new Equipement($this->db);
                    $equipement->fetch($line->fk_equipement);

                    // Set warehouse
                    $result = $equipement->set_entrepot($user, $line->fk_entrepot_dest);
                    if ($result > 0) {
                        // Add event
                        $result = $equipement->addline(
                            $equipement->id,
                            $fk_equipementevt_type,
                            $langs->trans('RetourProduits') . ' - ' . $numref,
                            $now,
                            $now,
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            0,
                            $this->id
                        );
                    }

                    if ($result < 0) {
                        $this->db->rollback();
                        $this->error=$equipement->error;
                        return -1;
                    }
                }
            }
        }

		// Change status of order to "shipment in process"
		//$ret = $this->setStatut(Commande::STATUS_SHIPMENTONPROCESS, $this->origin_id, $this->origin);

/*        if (! $ret)
		{
		    $error++;
		}*/

		if (! $error && ! $notrigger)
		{
            // Call trigger
            $result=$this->call_trigger('RETURN_VALIDATE',$user);
            if ($result < 0) { $error++; }
            // End call triggers
		}

		if (! $error)
		{
            $this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref))
			{
				// On renomme repertoire ($this->ref = ancienne ref, $numfa = nouvelle ref)
				// in order not to lose the attached files
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($numref);
				$dirsource = $conf->retourproduits->dir_output . '/'.$oldref;
				$dirdest = $conf->retourproduits->dir_output . '/'.$newref;
				if (file_exists($dirsource))
				{
					dol_syslog(get_class($this)."::valid rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest))
					{
					    dol_syslog("Rename ok");
                        // Rename docs starting with $oldref with $newref
                        $listoffiles=dol_dir_list($conf->retourproduits->dir_output . '/'.$newref, 'files', 1, '^'.preg_quote($oldref,'/'));
                        foreach($listoffiles as $fileentry)
                        {
				$dirsource=$fileentry['name'];
				$dirdest=preg_replace('/^'.preg_quote($oldref,'/').'/',$newref, $dirsource);
				$dirsource=$fileentry['path'].'/'.$dirsource;
				$dirdest=$fileentry['path'].'/'.$dirdest;
				@rename($dirsource, $dirdest);
                        }
					}
				}
			}
		}

		// Set new ref and current status
		if (! $error)
		{
			$this->ref = $numref;
			$this->statut = 1;
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::valid ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}

	}


	/**
	 *	Create a delivery receipt from a shipment
	 *
	 *	@param	User	$user       User
	 *  @return int  				<0 if KO, >=0 if OK
	 */
	function create_delivery($user)
	{
		global $conf;

		if ($conf->livraison_bon->enabled)
		{
			if ($this->statut == 1 || $this->statut == 2)
			{
				// Expedition validee
				include_once DOL_DOCUMENT_ROOT.'/livraison/class/livraison.class.php';
				$delivery = new Livraison($this->db);
				$result=$delivery->create_from_sending($user, $this->id);
				if ($result > 0)
				{
					return $result;
				}
				else
				{
					$this->error=$delivery->error;
					return $result;
				}
			}
			else return 0;
		}
		else return 0;
	}

	/**
	 * Add an expedition line.
	 * If STOCK_WAREHOUSE_NOT_REQUIRED_FOR_SHIPMENTS is set, you can add a shipment line, with no stock source defined
	 * If STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT is not set, you can add a shipment line, even if not enough into stock
	 *
	 * @param 	int		$entrepot_id		Id of warehouse
	 * @param 	int		$id					Id of source line (order line)
	 * @param 	int		$qty				Quantity
	 * @param	array	$array_options		extrafields array
	 * @return	int							<0 if KO, >0 if OK
	 */
	function addline($entrepot_id, $id, $qty,$array_options=0)
	{
		global $conf, $langs;

		$num = count($this->lines);
		$line = new RetourProduitsLigne($this->db);

		$line->entrepot_id = $entrepot_id;
		$line->origin_line_id = $id;
		$line->qty = $qty;

		$orderline = new OrderLine($this->db);
		$orderline->fetch($id);

		if (! empty($conf->stock->enabled) && ! empty($orderline->fk_product))
		{
			$fk_product = $orderline->fk_product;

			if (! ($entrepot_id > 0) && empty($conf->global->STOCK_WAREHOUSE_NOT_REQUIRED_FOR_SHIPMENTS))
			{
			    $langs->load("errors");
				$this->error=$langs->trans("ErrorWarehouseRequiredIntoShipmentLine");
				return -1;
			}

			if ($conf->global->STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT)
			{
			    // Check must be done for stock of product into warehouse if $entrepot_id defined
				$product=new Product($this->db);
				$result=$product->fetch($fk_product);

				if ($entrepot_id > 0) {
					$product->load_stock('warehouseopen');
					$product_stock = $product->stock_warehouse[$entrepot_id]->real;
				}
				else
					$product_stock = $product->stock_reel;

				$product_type=$product->type;
				if ($product_type == 0 && $product_stock < $qty)
				{
                    $langs->load("errors");
				    $this->error=$langs->trans('ErrorStockIsNotEnoughToAddProductOnShipment', $product->ref);
					$this->db->rollback();
					return -3;
				}
			}
		}

		// If product need a batch number, we should not have called this function but addline_batch instead.
		if (! empty($conf->productbatch->enabled) && ! empty($orderline->fk_product) && ! empty($orderline->product_tobatch))
		{
		    $this->error='ADDLINE_WAS_CALLED_INSTEAD_OF_ADDLINEBATCH';
		    return -4;
		}

		// extrafields
		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($array_options) && count($array_options)>0) // For avoid conflicts if trigger used
			$line->array_options = $array_options;

		$this->lines[$num] = $line;
	}

    /**
	 * Add a shipment line with batch record
	 *
	 * @param 	array		$dbatch		Array of value (key 'detail' -> Array, key 'qty' total quantity for line, key ix_l : original line index)
	 * @param	array		$array_options		extrafields array
	 * @return	int						<0 if KO, >0 if OK
	 */
	function addline_batch($dbatch,$array_options=0)
	{
		global $conf,$langs;

		$num = count($this->lines);
		if ($dbatch['qty']>0)
		{
			$line = new RetourProduitsLigne($this->db);
			$tab=array();
			foreach ($dbatch['detail'] as $key=>$value)
			{
				if ($value['q']>0)
				{
					// $value['q']=qty to move
					// $value['id_batch']=id into llx_product_batch of record to move
					//var_dump($value);

				    $linebatch = new ExpeditionLineBatch($this->db);
					$ret=$linebatch->fetchFromStock($value['id_batch']);	// load serial, sellby, eatby
					if ($ret<0)
					{
						$this->error=$linebatch->error;
						return -1;
					}
					$linebatch->dluo_qty=$value['q'];
					$tab[]=$linebatch;

					if ($conf->global->STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT)
					{
						require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
						$prod_batch = new Productbatch($this->db);
						$prod_batch->fetch($value['id_batch']);

						if ($prod_batch->qty < $linebatch->dluo_qty)
						{
                            $langs->load("errors");
					    $this->errors[]=$langs->trans('ErrorStockIsNotEnoughToAddProductOnShipment', $prod_batch->fk_product);
							dol_syslog(get_class($this)."::addline_batch error=Product ".$prod_batch->batch.": ".$this->errorsToString(), LOG_ERR);
							$this->db->rollback();
							return -1;
						}
					}

					//var_dump($linebatch);
				}
			}
			$line->entrepot_id = $linebatch->entrepot_id;
			$line->origin_line_id = $dbatch['ix_l'];
			$line->qty = $dbatch['qty'];
			$line->detail_batch=$tab;

			// extrafields
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($array_options) && count($array_options)>0) // For avoid conflicts if trigger used
				$line->array_options = $array_options;

			//var_dump($line);
			$this->lines[$num] = $line;
			return 1;
		}
	}

    /**
     *  Update database
     *
     *  @param	User	$user        	User that modify
     *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
     *  @return int 			       	<0 if KO, >0 if OK
     */
    function update($user=null, $notrigger=0)
    {
	global $conf;
		$error=0;

		// Clean parameters

		if (isset($this->ref)) $this->ref=trim($this->ref);
		if (isset($this->entity)) $this->entity=trim($this->entity);
		if (isset($this->ref_customer)) $this->ref_customer=trim($this->ref_customer);
		if (isset($this->socid)) $this->socid=trim($this->socid);
		if (isset($this->fk_user_author)) $this->fk_user_author=trim($this->fk_user_author);
		if (isset($this->fk_user_valid)) $this->fk_user_valid=trim($this->fk_user_valid);
		if (isset($this->fk_delivery_address)) $this->fk_delivery_address=trim($this->fk_delivery_address);
		if (isset($this->shipping_method_id)) $this->shipping_method_id=trim($this->shipping_method_id);
		if (isset($this->tracking_number)) $this->tracking_number=trim($this->tracking_number);
		if (isset($this->statut)) $this->statut=(int) $this->statut;
		if (isset($this->trueDepth)) $this->trueDepth=trim($this->trueDepth);
		if (isset($this->trueWidth)) $this->trueWidth=trim($this->trueWidth);
		if (isset($this->trueHeight)) $this->trueHeight=trim($this->trueHeight);
		if (isset($this->size_units)) $this->size_units=trim($this->size_units);
		if (isset($this->weight_units)) $this->weight_units=trim($this->weight_units);
		if (isset($this->trueWeight)) $this->weight=trim($this->trueWeight);
		if (isset($this->note_private)) $this->note=trim($this->note_private);
		if (isset($this->note_public)) $this->note=trim($this->note_public);
		if (isset($this->modelpdf)) $this->modelpdf=trim($this->modelpdf);



		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."retourproduits SET";

		//$sql.= " tms=".(dol_strlen($this->tms)!=0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
		$sql.= " ref=".(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"null").",";
		$sql.= " ref_customer=".(isset($this->ref_customer)?"'".$this->db->escape($this->ref_customer)."'":"null").",";
		$sql.= " fk_soc=".(isset($this->socid)?$this->socid:"null").",";
		$sql.= " date_creation=".(dol_strlen($this->date_creation)!=0 ? "'".$this->db->idate($this->date_creation)."'" : 'null').",";
		$sql.= " fk_user_author=".(isset($this->fk_user_author)?$this->fk_user_author:"null").",";
		$sql.= " date_valid=".(dol_strlen($this->date_valid)!=0 ? "'".$this->db->idate($this->date_valid)."'" : 'null').",";
		$sql.= " fk_user_valid=".(isset($this->fk_user_valid)?$this->fk_user_valid:"null").",";
		$sql.= " date_expedition=".(dol_strlen($this->date_expedition)!=0 ? "'".$this->db->idate($this->date_expedition)."'" : 'null').",";
		$sql.= " date_delivery=".(dol_strlen($this->date_delivery)!=0 ? "'".$this->db->idate($this->date_delivery)."'" : 'null').",";
		$sql.= " fk_address=".(isset($this->fk_delivery_address)?$this->fk_delivery_address:"null").",";
		$sql.= " fk_shipping_method=".((isset($this->shipping_method_id) && $this->shipping_method_id > 0)?$this->shipping_method_id:"null").",";
		$sql.= " tracking_number=".(isset($this->tracking_number)?"'".$this->db->escape($this->tracking_number)."'":"null").",";
		$sql.= " fk_statut=".(isset($this->statut)?$this->statut:"null").",";
		$sql.= " height=".(($this->trueHeight != '')?$this->trueHeight:"null").",";
		$sql.= " width=".(($this->trueWidth != '')?$this->trueWidth:"null").",";
		$sql.= " size_units=".(isset($this->size_units)?$this->size_units:"null").",";
		$sql.= " size=".(($this->trueDepth != '')?$this->trueDepth:"null").",";
		$sql.= " weight_units=".(isset($this->weight_units)?$this->weight_units:"null").",";
		$sql.= " weight=".(($this->trueWeight != '')?$this->trueWeight:"null").",";
		$sql.= " note_private=".(isset($this->note_private)?"'".$this->db->escape($this->note_private)."'":"null").",";
		$sql.= " note_public=".(isset($this->note_public)?"'".$this->db->escape($this->note_public)."'":"null").",";
		$sql.= " model_pdf=".(isset($this->modelpdf)?"'".$this->db->escape($this->modelpdf)."'":"null").",";
		$sql.= " entity=".$conf->entity;

        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
                // Call trigger
                $result=$this->call_trigger('SHIPPING_MODIFY',$user);
                if ($result < 0) { $error++; }
                // End call triggers
		}
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
    }

	/**
	 * 	Delete shipment.
	 *  Warning, do not delete a shipment if a delivery is linked to (with table llx_element_element)
	 *
	 * 	@return	int		>0 if OK, 0 if deletion done but failed to delete files, <0 if KO
	 */
	function delete()
	{
		global $conf, $langs, $user;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
/*		if ($conf->productbatch->enabled)
		{
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/expeditionbatch.class.php';
		} */
		$error=0;
		$this->error='';

		// Add a protection to refuse deleting if shipment has at least one delivery
		$this->fetchObjectLinked($this->id, 'shipping', 0, 'delivery');	// Get deliveries linked to this shipment
		if (count($this->linkedObjectsIds) > 0)
		{
			$this->error='ErrorThereIsSomeDeliveries';
			return -1;
		}

		$this->db->begin();
		// Stock control
		if ($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_SHIPMENT && $this->statut > 0)
		{
			require_once(DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php");

			$langs->load("agenda");

			// Loop on each product line to add a stock movement
			$sql = "SELECT cd.fk_product, cd.subprice, ed.qty, ed.fk_entrepot, ed.rowid as expeditiondet_id";
			$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
			$sql.= " ".MAIN_DB_PREFIX."retourproduitsdet as ed";
			$sql.= " WHERE ed.fk_retourproduits = ".$this->id;
			$sql.= " AND cd.rowid = ed.fk_origin_line";

			dol_syslog(get_class($this)."::delete select details", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$cpt = $this->db->num_rows($resql);
				for ($i = 0; $i < $cpt; $i++)
				{
					dol_syslog(get_class($this)."::delete movement index ".$i);
					$obj = $this->db->fetch_object($resql);

					$mouvS = new MouvementStock($this->db);
					// we do not log origin because it will be deleted
					$mouvS->origin = null;
					// get lot/serial
					$lotArray = null;
					if ($conf->productbatch->enabled)
					{
						$lotArray = ExpeditionLineBatch::fetchAll($this->db,$obj->expeditiondet_id);
						if (! is_array($lotArray))
						{
							$error++;$this->errors[]="Error ".$this->db->lasterror();
						}
					}
					if (empty($lotArray)) {
						// no lot/serial
						// We increment stock of product (and sub-products)
						// We use warehouse selected for each line
						$result=$mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $obj->qty, 0, $langs->trans("ShipmentDeletedInDolibarr", $this->ref));  // Price is set to 0, because we don't want to see WAP changed
						if ($result < 0)
						{
							$error++;$this->errors=$this->errors + $mouvS->errors;
							break;
						}
					}
					else
					{
						// We increment stock of batches
						// We use warehouse selected for each line
						foreach($lotArray as $lot)
						{
							$result=$mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $lot->dluo_qty, 0, $langs->trans("ShipmentDeletedInDolibarr", $this->ref), $lot->eatby, $lot->sellby, $lot->batch);  // Price is set to 0, because we don't want to see WAP changed
							if ($result < 0)
							{
								$error++;$this->errors=$this->errors + $mouvS->errors;
								break;
							}
						}
						if ($error) break; // break for loop incase of error
					}
				}
			}
			else
			{
				$error++;$this->errors[]="Error ".$this->db->lasterror();
			}
		}

		// delete batch expedition line
		if (! $error && $conf->productbatch->enabled)
		{
			if (ExpeditionLineBatch::deletefromexp($this->db,$this->id) < 0)
			{
				$error++;$this->errors[]="Error ".$this->db->lasterror();
			}
		}

		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."retourproduitsdet";
			$sql.= " WHERE fk_retourproduits = ".$this->id;

			if ( $this->db->query($sql) )
			{
				// Delete linked object
				$res = $this->deleteObjectLinked();
				if ($res < 0) $error++;

				if (! $error)
				{
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."retourproduits";
					$sql.= " WHERE rowid = ".$this->id;

					if ($this->db->query($sql))
					{
						// Call trigger
						$result=$this->call_trigger('SHIPPING_DELETE',$user);
						if ($result < 0) { $error++; }
						// End call triggers

						if (! empty($this->origin) && $this->origin_id > 0)
						{
						    $this->fetch_origin();
						    $origin=$this->origin;
						    if ($this->$origin->statut == Commande::STATUS_SHIPMENTONPROCESS)     // If order source of shipment is "shipment in progress"
						    {
                                // Check if there is no more shipment. If not, we can move back status of order to "validated" instead of "shipment in progress"
						        $this->$origin->loadExpeditions();
						        //var_dump($this->$origin->expeditions);exit;
						        if (count($this->$origin->expeditions) <= 0)
						        {
                                    $this->$origin->setStatut(Commande::STATUS_VALIDATED);
						        }
						    }
						}

						if (! $error)
						{
							$this->db->commit();

							// We delete PDFs
							$ref = dol_sanitizeFileName($this->ref);
							if (! empty($conf->retourproduits->dir_output))
							{
								$dir = $conf->retourproduits->dir_output . '/' . $ref ;
								$file = $dir . '/' . $ref . '.pdf';
								if (file_exists($file))
								{
									if (! dol_delete_file($file))
									{
										return 0;
									}
								}
								if (file_exists($dir))
								{
									if (!dol_delete_dir_recursive($dir))
									{
										$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
										return 0;
									}
								}
							}

							return 1;
						}
						else
						{
							$this->db->rollback();
							return -1;
						}
					}
					else
					{
						$this->error=$this->db->lasterror()." - sql=$sql";
						$this->db->rollback();
						return -3;
					}
				}
				else
				{
					$this->error=$this->db->lasterror()." - sql=$sql";
					$this->db->rollback();
					return -2;
				}
			}
			else
			{
				$this->error=$this->db->lasterror()." - sql=$sql";
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->db->rollback();
			return -1;
		}

	}

	/**
	 *	Load lines
	 *
	 *	@return	int		>0 if OK, Otherwise if KO
	 */
	function fetch_lines()
	{
		global $conf, $mysoc;
		// TODO: recuperer les champs du document associe a part

		$sql= "SELECT DISTINCT(rp.rowid) as line_id, rp.qty as qty_return, rp.fk_product, rp.fk_entrepot_dest, rp.fk_equipement, rp.fk_origin_line, ";
		$sql.= " p.ref as product_ref, p.label as product_label, p.fk_product_type, cd.qty as qty_asked, ";
		$sql.= " p.weight, p.weight_units, p.length, p.length_units, p.surface, p.surface_units, p.volume, p.volume_units, p.tobatch as product_tobatch, ";
        $sql.= " e.ref as equipement_ref";
		$sql.= " FROM (".MAIN_DB_PREFIX."retourproduitsdet as rp,";
		$sql.= " ".MAIN_DB_PREFIX."commandedet as cd,";
		$sql.= " ".MAIN_DB_PREFIX."expeditiondet as exp)";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = rp.fk_product";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."equipement as e ON e.rowid = rp.fk_equipement";
		$sql.= " WHERE rp.fk_retourproduits = ".$this->id;
		$sql.= " AND rp.fk_origin_line = cd.rowid" ;
		$sql.= " AND rp.fk_origin_line = exp.fk_origin_line" ;
		$sql.= " GROUP BY line_id";
		$sql.= " ORDER BY rp.rang, rp.fk_equipement";

		dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

			$num = $this->db->num_rows($resql);
			$i = 0;
			$lineindex = 0;
			$originline = 0;

			$this->total_ht = 0;
			$this->total_tva = 0;
			$this->total_ttc = 0;
			$this->total_localtax1 = 0;
			$this->total_localtax2 = 0;

			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$line = new RetourProduitsLigne($this->db);
				$line->entrepot_id = $obj->fk_entrepot ;

                $line->line_id          = $obj->line_id;

				$line->product_type     = $obj->product_type;
				$line->fk_product     	= $obj->fk_product;
				$line->fk_product_type	= $obj->fk_product_type;
				$line->ref				= $obj->product_ref;		// TODO deprecated
                $line->product_ref		= $obj->product_ref;
                $line->product_label	= $obj->product_label;
				$line->libelle        	= $obj->product_label;		// TODO deprecated
				$line->product_tobatch  = $obj->product_tobatch;
				$line->weight         	= $obj->weight;
				$line->weight_units   	= $obj->weight_units;
				$line->length         	= $obj->length;
				$line->length_units   	= $obj->length_units;
				$line->surface        	= $obj->surface;
				$line->surface_units   	= $obj->surface_units;
				$line->volume         	= $obj->volume;
				$line->volume_units   	= $obj->volume_units;
				$line->fk_entrepot_dest = $obj->fk_entrepot_dest;
				$line->qty_return       = $obj->qty_return ;
                $line->fk_equipement    = $obj->fk_equipement ;
                $line->equipement_ref   = $obj->equipement_ref ;
				// Quantité commandé dans la commande origine
				$line->qty_asked      	= $obj->qty_asked;
				$line->qty_shipped      = $obj->qty_shipped;
				$line->fk_expedition       = $obj->fk_expedition;

/*				if ($originline != $obj->fk_origin_line)
				{*/
				    $this->lines[$lineindex] = $line;
				    $lineindex++;
/*				}*/

				$i++;
				$originline = $obj->fk_origin_line;
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -3;
		}
	}

	/**
     *	Return clicable link of object (with eventually picto)
     *
     *	@param      int			$withpicto      Add picto into link
     *	@param      int			$option         Where point the link
     *	@param      int			$max          	Max length to show
     *	@param      int			$short			Use short labels
     *  @param      int         $notooltip      1=No tooltip
     *	@return     string          			String with URL
     */
	function getNomUrl($withpicto=0,$option=0,$max=0,$short=0,$notooltip=0)
	{
		global $langs;

		$result='';
        $label = '<u>' . $langs->trans("ShowSending") . '</u>';
        $label .= '<br><b>' . $langs->trans('Ref') . ':</b> '.$this->ref;
        $label .= '<br><b>'.$langs->trans('RefCustomer').':</b> '.($this->ref_customer ? $this->ref_customer : $this->ref_client);

		$url = dol_buildpath('/retourproduits/card.php?id=',1).$this->id;

		if ($short) return $url;

		$linkclose='';
		if (empty($notooltip))
		{
		    if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
		    {
		        $label=$langs->trans("ShowSending");
		        $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
		    }
		    $linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
		    $linkclose.=' class="classfortooltip"';
		}

        $linkstart = '<a href="'.$url.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

		$picto='sending';

		if ($withpicto) $result.=($linkstart.img_object(($notooltip?'':$label), $picto, ($notooltip?'':'class="classfortooltip"'), 0, 0, $notooltip?0:1).$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		$result.=$linkstart.$this->ref.$linkend;
		return $result;
	}

	/**
     *	Return status label
     *
     *	@param      int		$mode      	0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto
     *	@return     string      		Libelle
     */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 * Return label of a status
	 *
	 * @param      int		$statut		Id statut
	 * @param      int		$mode       0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto
	 * @return     string				Label of status
	 */
	function LibStatut($statut,$mode)
	{
		global $langs;

		if ($mode==0)
		{
			if ($statut==0) return $langs->trans($this->statuts[$statut]);
			if ($statut==1)  return $langs->trans($this->statuts[$statut]);
			if ($statut==2)  return $langs->trans($this->statuts[$statut]);
		}
		if ($mode==1)
		{
			if ($statut==0) return $langs->trans('StatusSendingDraftShort');
			if ($statut==1) return $langs->trans('StatusSendingValidatedShort');
			if ($statut==2) return $langs->trans('StatusSendingProcessedShort');
		}
		if ($mode == 3)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts[$statut]),'statut0');
			if ($statut==1) return img_picto($langs->trans($this->statuts[$statut]),'statut4');
			if ($statut==2) return img_picto($langs->trans('StatusSendingProcessed'),'statut6');
		}
		if ($mode == 4)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts[$statut]),'statut0').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==1) return img_picto($langs->trans($this->statuts[$statut]),'statut4').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==2) return img_picto($langs->trans('StatusSendingProcessed'),'statut6').' '.$langs->trans('StatusSendingProcessed');
		}
		if ($mode == 5)
		{
			if ($statut==0) return $langs->trans('StatusSendingDraftShort').' '.img_picto($langs->trans($this->statuts[$statut]),'statut0');
			if ($statut==1) return $langs->trans('StatusSendingValidatedShort').' '.img_picto($langs->trans($this->statuts[$statut]),'statut4');
			if ($statut==2) return $langs->trans('StatusSendingProcessedShort').' '.img_picto($langs->trans('StatusSendingProcessedShort'),'statut6');
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
		global $langs;

		$now=dol_now();

		dol_syslog(get_class($this)."::initAsSpecimen");

        // Load array of products prodids
		$num_prods = 0;
		$prodids = array();
		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."product";
		$sql.= " WHERE entity IN (".getEntity('product').")";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num_prods = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_prods)
			{
				$i++;
				$row = $this->db->fetch_row($resql);
				$prodids[$i] = $row[0];
			}
		}

		$order=new Commande($this->db);
		$order->initAsSpecimen();

		// Initialise parametres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$this->statut               = 1;
		$this->livraison_id         = 0;
		$this->date                 = $now;
		$this->date_creation        = $now;
		$this->date_valid           = $now;
		$this->date_delivery        = $now;
		$this->date_expedition      = $now + 24*3600;

		$this->entrepot_id          = 0;
		$this->fk_delivery_address  = 0;
		$this->socid                = 1;

		$this->commande_id          = 0;
		$this->commande             = $order;

        $this->origin_id            = 1;
        $this->origin               = 'commande';

        $this->note_private			= 'Private note';
        $this->note_public			= 'Public note';

		$nbp = 5;
		$xnbp = 0;
		while ($xnbp < $nbp)
		{
			$line=new ExpeditionLigne($this->db);
			$line->desc=$langs->trans("Description")." ".$xnbp;
			$line->libelle=$langs->trans("Description")." ".$xnbp;
			$line->qty=10;
			$line->qty_asked=5;
			$line->qty_shipped=4;
			$line->fk_product=$this->commande->lines[$xnbp]->fk_product;

			$this->lines[]=$line;
			$xnbp++;
		}

	}

	/**
	 *	Set the planned delivery date
	 *
	 *	@param      User			$user        		Objet utilisateur qui modifie
	 *	@param      timestamp		$date_livraison     Date de livraison
	 *	@return     int         						<0 if KO, >0 if OK
	 */
	function set_date_livraison($user, $date_livraison)
	{
		if ($user->rights->expedition->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."retourproduits";
			$sql.= " SET date_delivery = ".($date_livraison ? "'".$this->db->idate($date_livraison)."'" : 'null');
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::set_date_livraison", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->date_delivery = $date_livraison;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				return -1;
			}
		}
		else
		{
			return -2;
		}
	}

	/**
	 *	Fetch deliveries method and return an array. Load array this->meths(rowid=>label).
	 *
	 * 	@return	void
	 */
	function fetch_delivery_methods()
	{
		global $langs;
		$this->meths = array();

		$sql = "SELECT em.rowid, em.code, em.libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
		$sql.= " WHERE em.active = 1";
		$sql.= " ORDER BY em.libelle ASC";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
				$label=$langs->trans('SendingMethod'.$obj->code);
				$this->meths[$obj->rowid] = ($label != 'SendingMethod'.$obj->code?$label:$obj->libelle);
			}
		}
	}

    /**
     *  Fetch all deliveries method and return an array. Load array this->listmeths.
     *
     *  @param  id      $id     only this carrier, all if none
     *  @return void
     */
    function list_delivery_methods($id='')
    {
        global $langs;

        $this->listmeths = array();
        $i=0;

        $sql = "SELECT em.rowid, em.code, em.libelle, em.description, em.tracking, em.active";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
        if ($id!='') $sql.= " WHERE em.rowid=".$id;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            while ($obj = $this->db->fetch_object($resql))
            {
                $this->listmeths[$i]['rowid'] = $obj->rowid;
                $this->listmeths[$i]['code'] = $obj->code;
                $label=$langs->trans('SendingMethod'.$obj->code);
                $this->listmeths[$i]['libelle'] = ($label != 'SendingMethod'.$obj->code?$label:$obj->libelle);
                $this->listmeths[$i]['description'] = $obj->description;
                $this->listmeths[$i]['tracking'] = $obj->tracking;
                $this->listmeths[$i]['active'] = $obj->active;
                $i++;
            }
        }
    }

    /**
     *  Update/create delivery method.
     *
     *  @param	string      $id     id method to activate
     *
     *  @return void
     */
    function update_delivery_method($id='')
    {
        if ($id=='')
        {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_shipment_mode (code, libelle, description, tracking)";
            $sql.=" VALUES ('".$this->update['code']."','".$this->update['libelle']."','".$this->update['description']."','".$this->update['tracking']."')";
            $resql = $this->db->query($sql);
        }
        else
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."c_shipment_mode SET";
            $sql.= " code='".$this->db->escape($this->update['code'])."'";
            $sql.= ",libelle='".$this->db->escape($this->update['libelle'])."'";
            $sql.= ",description='".$this->db->escape($this->update['description'])."'";
            $sql.= ",tracking='".$this->db->escape($this->update['tracking'])."'";
            $sql.= " WHERE rowid=".$id;
            $resql = $this->db->query($sql);
        }
        if ($resql < 0) dol_print_error($this->db,'');
    }

    /**
     *  Activate delivery method.
     *
     *  @param      id      $id     id method to activate
     *
     *  @return void
     */
    function activ_delivery_method($id)
    {
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'c_shipment_mode SET active=1';
        $sql.= ' WHERE rowid='.$id;

        $resql = $this->db->query($sql);

    }

    /**
     *  DesActivate delivery method.
     *
     *  @param      id      $id     id method to desactivate
     *
     *  @return void
     */
    function disable_delivery_method($id)
    {
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'c_shipment_mode SET active=0';
        $sql.= ' WHERE rowid='.$id;

        $resql = $this->db->query($sql);

    }


	/**
	 * Forge an set tracking url
	 *
	 * @param	string	$value		Value
	 * @return	void
	 */
	function GetUrlTrackingStatus($value='')
	{
		if (! empty($this->shipping_method_id))
		{
			$sql = "SELECT em.code, em.tracking";
			$sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
			$sql.= " WHERE em.rowid = ".$this->shipping_method_id;

			$resql = $this->db->query($sql);
			if ($resql)
			{
				if ($obj = $this->db->fetch_object($resql))
				{
					$tracking = $obj->tracking;
				}
			}
		}

		if (!empty($tracking) && !empty($value))
		{
			$url = str_replace('{TRACKID}', $value, $tracking);
			$this->tracking_url = sprintf('<a target="_blank" href="%s">'.($value?$value:'url').'</a>',$url,$url);
		}
		else
		{
			$this->tracking_url = $value;
		}
	}

	/**
	 *	Classify the shipping as closed.
	 *
	 *	@return     int     <0 if KO, >0 if OK
	 */
	function setClosed()
	{
		global $conf,$langs,$user;

		$error=0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'retourproduits SET fk_statut='.self::STATUS_CLOSED;
		$sql .= ' WHERE rowid = '.$this->id.' AND fk_statut > 0';

		$resql=$this->db->query($sql);
		if ($resql)
		{
			// Set order billed if 100% of order is shipped (qty in shipment lines match qty in order lines)
/*			if ($this->origin == 'commande' && $this->origin_id > 0)
			{
				$order = new Commande($this->db);
				$order->fetch($this->origin_id);

				$order->loadExpeditions(self::STATUS_CLOSED);		// Fill $order->expeditions = array(orderlineid => qty)

				$shipments_match_order = 1;
				foreach($order->lines as $line)
				{
					$lineid = $line->id;
					$qty = $line->qty;
					if (($line->product_type == 0 || ! empty($conf->global->STOCK_SUPPORTS_SERVICES)) && $order->expeditions[$lineid] != $qty)
					{
						$shipments_match_order = 0;
						$text='Qty for order line id '.$lineid.' is '.$qty.'. However in the shipments with status Expedition::STATUS_CLOSED='.self::STATUS_CLOSED.' we have qty = '.$order->expeditions[$lineid].', so we can t close order';
						dol_syslog($text);
						break;
					}
				}
				if ($shipments_match_order)
				{
					dol_syslog("Qty for the ".count($order->lines)." lines of order have same value for shipments with status Expedition::STATUS_CLOSED=".self::STATUS_CLOSED.', so we close order');
					$order->cloture($user);
				}
			}*/

			$this->statut=self::STATUS_CLOSED;


			// If stock increment is done on closing
			if (! $error && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE))
			{
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

				$langs->load("agenda");

				// Loop on each product line to add a stock movement
				// TODO in future, shipment lines may not be linked to order line
				$sql = "SELECT cd.fk_product, cd.subprice,";
				$sql.= " det.rowid, det.qty, det.fk_entrepot_dest,";
				$sql.= " rpb.rowid as rpbrowid, rpb.eatby, rpb.sellby, rpb.batch, rpb.qty as rpbqty, rpb.fk_dest_stock";
				$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
				$sql.= " ".MAIN_DB_PREFIX."retourproduitsdet as det";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."retourproduitsdet_batch as rpb on rpb.fk_retourproduitsdet = det.rowid";
				$sql.= " WHERE det.fk_retourproduits = ".$this->id;
				$sql.= " AND cd.rowid = det.fk_origin_line";

				dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
				$resql=$this->db->query($sql);
				if ($resql)
				{
					$cpt = $this->db->num_rows($resql);
					for ($i = 0; $i < $cpt; $i++)
					{
						$obj = $this->db->fetch_object($resql);
						if (empty($obj->rpbrowid))
						{
							$qty = -$obj->qty;
						}
						else
						{
							$qty = -$obj->rpbqty;
						}
						if ($qty >= 0) continue;
						dol_syslog(get_class($this)."::valid movement index ".$i." ed.rowid=".$obj->rowid." rpb.rowid=".$obj->rpbrowid);

						//var_dump($this->lines[$i]);
						$mouvS = new MouvementStock($this->db);
						$mouvS->origin = &$this;

						if (empty($obj->rpbrowid))
						{
							// line without batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record.
							$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot_dest, $qty, $obj->subprice, $langs->trans("ReturnClassifyClosedInDolibarr",$numref));
							if ($result < 0) {
								$error++;
								$this->errors[]=$mouvS->error;
								$this->errors = array_merge($this->errors, $mouvS->errors);
								break;
							}
						}
						else
						{
							// line with batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record.
						    // Note: ->fk_origin_stock = id into table llx_product_batch (may be rename into llx_product_stock_batch in another version)
							$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->subprice, $langs->trans("ReturnClassifyClosedInDolibarr",$numref), '', $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch, $obj->fk_origin_stock);
							if ($result < 0) {
								$error++;
								$this->errors[]=$mouvS->error;
								$this->errors = array_merge($this->errors, $mouvS->errors);
								break;
							}
						}
					}
				}
				else
				{
					$this->db->rollback();
					$this->error=$this->db->error();
					return -2;
				}
			}

			// Call trigger
			if (! $error)
			{
			$result=$this->call_trigger('RETURN_CLOSED',$user);
			if ($result < 0) {
			    $error++;
			}
			}
		}
		else
		{
			dol_print_error($this->db);
            $error++;
		}

		if (! $error)
		{
		    $this->db->commit();
		    return 1;
		}
		else
		{
		    $this->db->rollback();
		    return -1;
		}
	}

	/**
	 *	Classify the shipping as invoiced (used when WORKFLOW_BILL_ON_SHIPMENT is on)
	 *
	 *	@return     int     <0 if ko, >0 if ok
	 */
	function set_billed()
	{
	    global $user;
		$error=0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'retourproduits SET fk_statut=2, billed=1';    // TODO Update only billed
		$sql .= ' WHERE rowid = '.$this->id.' AND fk_statut > 0';

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->statut=2;
			$this->billed=1;

			// Call trigger
			$result=$this->call_trigger('SHIPPING_BILLED',$user);
			if ($result < 0) {
				$error++;
			}

		} else {
			$error++;
			$this->errors[]=$this->db->lasterror;
		}

		if (empty($error)) {
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Classify the shipping as validated/opened
	 *
	 *	@return     int     <0 if ko, >0 if ok
	 */
	function reOpen()
	{
		global $conf,$langs,$user;

		$error=0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'retourproduits SET fk_statut=1';
		$sql .= ' WHERE rowid = '.$this->id.' AND fk_statut > 0';

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->statut=1;
			$this->billed=0;

			// If stock increment is done on closing
			if (! $error && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE))
			{
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

				$langs->load("agenda");

				// Loop on each product line to add a stock movement
				// TODO in future, shipment lines may not be linked to order line
				$sql = "SELECT cd.fk_product, cd.subprice,";
				$sql.= " det.rowid, det.qty, det.fk_entrepot_dest,";
				$sql.= " rpb.rowid as rpbrowid, rpb.eatby, rpb.sellby, rpb.batch, rpb.qty as rpbqty, rpb.fk_dest_stock";
				$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
				$sql.= " ".MAIN_DB_PREFIX."retourproduitsdet as det";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."retourproduitsdet_batch as rpb on rpb.fk_retourproduitsdet = det.rowid";
				$sql.= " WHERE det.fk_retourproduits = ".$this->id;
				$sql.= " AND cd.rowid = det.fk_origin_line";

				dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
				$resql=$this->db->query($sql);
				if ($resql)
				{
					$cpt = $this->db->num_rows($resql);
					for ($i = 0; $i < $cpt; $i++)
					{
						$obj = $this->db->fetch_object($resql);
						if (empty($obj->rpbrowid))
						{
							$qty = -$obj->qty;
						}
						else
						{
							$qty = -$obj->rpbqty;
						}
						if ($qty >= 0) continue;
						dol_syslog(get_class($this)."::valid returnproducts index ".$i." ed.rowid=".$obj->rowid." rpb.rowid=".$obj->rpbrowid);

						//var_dump($this->lines[$i]);
						$mouvS = new MouvementStock($this->db);
						$mouvS->origin = &$this;

						if (empty($obj->rpbrowid))
						{
							// line without batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record.
							$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot_dest, $qty, $obj->subprice, $langs->trans("ReturnUnClassifyCloseddInDolibarr",$numref));
							if ($result < 0) {
								$error++;
								$this->errors[]=$mouvS->error;
								$this->errors = array_merge($this->errors, $mouvS->errors);
								break;
							}
						}
						else
						{
							// line with batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record.
						    // Note: ->fk_origin_stock = id into table llx_product_batch (may be rename into llx_product_stock_batch in another version)
							$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->subprice, $langs->trans("ReturnUnClassifyCloseddInDolibarr",$numref), '', $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch, $obj->fk_origin_stock);
							if ($result < 0) {
								$error++;
								$this->errors[]=$mouvS->error;
								$this->errors = array_merge($this->errors, $mouvS->errors);
								break;
							}
						}
					}
				}
				else
				{
					$this->error=$this->db->lasterror();
					$error++;
				}
			}

			if (! $error)
			{
			// Call trigger
			$result=$this->call_trigger('RETURN_REOPEN',$user);
			if ($result < 0) {
				$error++;
			}
			}

		} else {
			$error++;
			$this->errors[]=$this->db->lasterror();
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force the model to using ('' to not force)
	 *  @param		Translate	$outputlangs	object lang to use for translations
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs,$hidedetails=0, $hidedesc=0, $hideref=0)
	{
		global $conf,$langs;

		$langs->load("sendings");

		if (! dol_strlen($modele)) {

			$modele = 'retourproduits_rouget';

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (! empty($conf->global->RETURNPRODUCTS_ADDON_PDF)) {
				$modele = $conf->global->RETURNPRODUCTS_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/retourproduits/doc/";

		$this->fetch_origin();

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old thirdparty id
	 * @param int $dest_id New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'expedition'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}
}


/**
 * Classe de gestion des lignes de bons d'expedition
 */
class RetourProduitsLigne extends CommonObjectLine
{
	var $db;

	// From llx_expeditiondet
	var $qty;
	var $qty_shipped;
	var $fk_product;
	var $fk_equipement;
	var $fk_entrepot_dest;
	var $detail_batch;

	// From llx_commandedet or llx_propaldet
	var $qty_asked;
	public $product_ref;
	public $product_label;
	public $product_desc;


	// Invoicing
	var $remise_percent;
	var $total_ht;			// Total net of tax
	var $total_ttc;			// Total with tax
	var $total_tva;			// Total VAT
	var $total_localtax1;   // Total Local tax 1
	var $total_localtax2;   // Total Local tax 2

	public $element='retourproduitsdet';
	public $table_element='retourproduitsdet';

	public $fk_origin_line;

	// Deprecated
	/**
	 * @deprecated
	 * @see fk_origin_line
	 */
	var $origin_line_id;
	/**
	 * @deprecated
	 * @see product_ref
	 */
	var $ref;
	/**
	 * @deprecated
	 * @see product_label
	 */
	var $libelle;

    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
	function __construct($db)
	{
		$this->db=$db;
	}

}
