<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file core/triggers/interface_99_modMyodule_GrapeFruittrigger.class.php
 * \ingroup grapefruit
 * \brief Sample trigger
 * \remarks You can create other triggers by copying this one
 * - File name should be either:
 * interface_99_modMymodule_Mytrigger.class.php
 * interface_99_all_Mytrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be Mytrigger
 */

/**
 * Trigger class
 */
class InterfaceGrapeFruittrigger
{
	private $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db) {
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "Triggers of this module are empty functions." . "They have no effect." . "They are provided for tutorial purpose only.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'grapefruit@grapefruit';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc() {
		return $this->description;
	}

	/**
	 * Trigger version
	 *
	 * @return string Version of trigger file
	 */
	public function getVersion() {
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') {
			return $langs->trans("Development");
		} elseif ($this->version == 'experimental')

			return $langs->trans("Experimental");
		elseif ($this->version == 'dolibarr')
			return DOL_VERSION;
		elseif ($this->version)
			return $this->version;
		else {
			return $langs->trans("Unknown");
		}
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "run_trigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string $action Event action code
	 * @param Object $object Object
	 * @param User $user Object user
	 * @param Translate $langs Object langs
	 * @param conf $conf Object conf
	 * @return int <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function run_trigger($action, &$object, &$user, &$langs, &$conf) {
		$db = &$this->db;
		dol_include_once('/grapefruit/class/grapefruit.class.php');
		$langs->load('grapefruit@grapefruit');

		if($action==='ORDER_SUPPLIER_CREATE') {

		    if(!empty($conf->global->GRAPEFRUIT_SUPPLIER_ORDER_COPY_LINK_FROM_ORIGIN) && $object->origin=='supplier_proposal' && $object->origin_id>0) {

		        dol_include_once('/supplier_proposal/class/supplier_proposal.class.php');

		        $sp = new SupplierProposal($object->db);
		        $sp->fetch($object->origin_id);
		        $sp->fetchObjectLinked();

		        foreach($sp->linkedObjectsIds as $type=>$objs) {
		            foreach($objs as $fk_object) {
		                if($type!='supplier_order' && $fk_object!=$object->id)   $object->add_object_linked($type, $fk_object);
		            }
		        }

		    }

		}
		else if ($action == 'ACTION_CREATE') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

			if (! empty($conf->global->GRAPEFRUIT_CAN_ASSOCIATE_TASK_TO_ACTIONCOMM)) {
				// il faut récup le fk_task et fait un lien avec "related"
				$fk_task = GETPOST('fk_task', 'int');

				if ($fk_task > 0) {
					$r = $object->add_object_linked('task', $fk_task);
				}
			}
		}
		else if ($action === 'LINESUPPLIER_PROPOSAL_INSERT') {
//var_dump($object);exit;
			if(!empty($conf->global->GRAPEFRUIT_SUPPLIER_PROPAL_ADDLINE_ZERO)) {
				$parent = new SupplierProposal($object->db);
				$parent->fetch($object->fk_supplierproposal);
				$parent->updateline($object->id, 0, $object->qty, $object->remise_percent, $object->tva_tx, $object->txlocaltax1, $object->txlocaltax2, $object->desc, 'HT'
					, 0, 0, 0, 0, $object->fk_fournprice, 0, $object->label, $object->product_type, $object->array_options, $object->ref_fourn, $object->fk_unit);
			}
		}
		else if($action === 'SUPPLIER_PROPOSAL_CLOSE_SIGNED') {
			TGrappeFruit::createSupplierPriceFromProposal($object);
		}
		elseif ($action === 'LINECONTRACT_INSERT') {
			TGrappeFruit::checkContractFourn($object);
		} elseif ($action === 'ORDER_VALIDATE') {
			TGrappeFruit::createBillOnOrderValidate($object);

			if(!empty($conf->global->GRAPEFRUIT_ALLOW_CREATE_ORDER_AND_BILL_ON_UNSIGNED_PROPAL)) TGrappeFruit::clotureOriginPropal($object);

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
		} elseif ($action === 'BILL_CREATE') {

			if (! empty($conf->global->GRAPEFRUIT_LINK_INVOICE_TO_SESSION_IF_PROPAL_IS) && $conf->agefodd->enabled) {
				if ($object->origin == 'propal' && $object->origin_id > 0) {
					$db = &$this->db;

					dol_include_once('/agefodd.git/class/agefodd_session_element.class.php');

					$res = $db->query("SELECT fk_session_agefodd FROM " . MAIN_DB_PREFIX . "agefodd_session_element WHERE fk_element=" . $object->origin_id . " AND element_type='propal'");
					$obj = $db->fetch_object($res);

					if ($obj->fk_session_agefodd > 0) {
						$agf = new Agefodd_session_element($db);
						$agf->fk_element = $object->id;
						$agf->fk_session_agefodd = $obj->fk_session_agefodd;
						$agf->fk_soc = empty($object->socid) ? $object->fk_soc : $object->socid;
						$agf->element_type = 'invoice';
						$result = $agf->create($user);
					}
				}
			}
		} elseif ($action === 'BILL_PAYED') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

			TGrappeFruit::createShippingFromOrderOnBillPayed($object,$user);

			if (! empty ( $conf->global->GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_BILLED )) {
				if (empty($conf->global->GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE_ORDER)) {
					TGrappeFruit::sendBillByMail($object);
				} else {
					if (empty($object->linked_objects))
						$object->fetchObjectLinked(null, null, $object->id, 'facture');

					if (empty($object->linkedObjects['commande']))
						return false;

					foreach ( $object->linkedObjects['commande'] as &$commande ) {
						TGrappeFruit::sendOrderByMail($commande);
					}
				}
			}
		} elseif ($action === 'BILL_VALIDATE') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

			if (! empty ( $conf->global->GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE )) {
				if (empty($conf->global->GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE_ORDER)) {

					// Define output language
					if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
					{
						$outputlangs = $langs;

						$model=$object->modelpdf;
						$object->ref=$object->newref;
						$object->generateDocument($model, $outputlangs);
						//if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
					}


					TGrappeFruit::sendBillByMail($object);
				} else {
					if (empty($object->linked_objects))
						$object->fetchObjectLinked(null, null, $object->id, 'facture');

					if (empty($object->linkedObjects['commande']))
						return false;

					foreach ( $object->linkedObjects['commande'] as &$commande ) {
						TGrappeFruit::sendOrderByMail($commande);
					}
				}
			}

			//Création de l'évènement de la facture de relance
			if(!empty($object->array_options['options_grapefruitReminderBill']) ){//verification facture de relance
				if((!empty($conf->global->GRAPEFRUIT_REMINDER_BILL_DELAY) )){
					require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
					$actioncomm = new ActionComm($db);//evenement agenda
					$actioncomm->type_code = 'AC_STI_BILL';//code pour la relance facture
					if(strstr($object->ref,'PROV')){
						$actioncomm->label='Facture de relance : '.$object->newref;
					}else {
						$actioncomm->label='Facture de relance : '.$object->ref;
					}
					$actioncomm->datep = $object->date_lim_reglement+(3600 * 24) * $conf->global->GRAPEFRUIT_REMINDER_BILL_DELAY+(3600*10);
					$actioncomm->punctual = 1;
					if(!empty($conf->global->GRAPEFRUIT_EVENT_DESCRIPTION)){
						$actioncomm->note = $conf->global->GRAPEFRUIT_EVENT_DESCRIPTION;
					}
					$actioncomm->transparency = 1;
					$actioncomm->fulldayevent=0;
					$bill_rep = $object->getIdcontact('internal', 'SALESREPFOLL');
					if(!empty($bill_rep)){
						$u = new User($db);
						$u->fetch($bill_rep[0]);
						$actioncomm->userassigned = array();
						$actioncomm->userassigned[$u->id]['id']=$u->id;
						$actioncomm->userassigned[$u->id]['transparency']=1;
						$actioncomm->userownerid=$user->id;
					}else
					 if(!empty($user)){
						$actioncomm->userassigned = array();
						$actioncomm->userassigned[$user->id]['id']=$user->id;
						$actioncomm->userassigned[$u->id]['transparency']=1;
						$actioncomm->userownerid=$user->id;
					}
					if(!empty($object->thirdparty)){
						$actioncomm->societe=$object->thirdparty;
						$actioncomm->socid=$object->thirdparty->id;
						$actioncomm->thirdparty=$object->thirdparty;
					}
					$idcontacts=$object->getIdBillingContact();
					if(!empty($idcontacts)){
						$actioncomm->contactid=$idcontacts[0];
						$actioncomm->contact=$object->contact;

					}
					if(!empty($object->fk_project)){
						$actioncomm->fk_project=$object->fk_project;
					}
					$actioncomm->fk_element  = $object->id;
					$actioncomm->elementtype = $object->element;
					$object->fetchObjectLinked();
					if(!empty($object->linkedObjectsIds['ActionComm'])){
						foreach($object->linkedObjectsIds['ActionComm'] as $k => $v){

							$tempActionComm = new ActionComm($db);
							$tempActionComm->fetch($v);
							if($tempActionComm->type_code =='AC_STI_BILL' ){
								$tempActionComm->delete();
								$object->deleteObjectLinked($v,'ActionComm',null,'',$k);
							}
						}
					}
					$res = $actioncomm->create($user);
					$object->add_object_linked(get_class($actioncomm),$actioncomm->id);


				}else {
					setEventMessage($langs->trans('ReminderBillDelayForgotten'),'errors');
				}
			}

			if (! empty($conf->propal->enabled) && ! empty($conf->global->GRAPEFRUIT_INVOICE_CLASSIFY_BILLED_PROPAL))
			{
				$object->fetchObjectLinked('','propal',$object->id,$object->element);
				if (! empty($object->linkedObjects))
				{
					foreach($object->linkedObjects['propal'] as $element)
					{
						$ret=$element->classifyBilled($user);
					}
				}
			}

			if(!empty($conf->global->GRAPEFRUIT_SET_ORDER_BILLED_IF_SAME_MONTANT)) {

				TGrappeFruit::setOrderBilledIfSameMontant($object);

			}

			if(!empty($conf->global->GRAPEFRUIT_ALLOW_RESTOCK_ON_CREDIT_NOTES) && empty($conf->global->STOCK_CALCULATE_ON_BILL) && $object->element === 'facture' && $object->type == Facture::TYPE_CREDIT_NOTE) {
				require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
				require_once DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php';
				$fk_entrepot = GETPOST('fk_entrepot');
				if(!empty($fk_entrepot)) {
					$nb_restock=0;

					foreach($_REQUEST as $k=>$qty) {
						if(strpos($k, 'restock_line_') !== false && (float)$qty> 0) {
							$id_line = strtr($k, array('restock_line_'=>''));
							$line = new FactureLigne($db);
							$line->fetch($id_line);
							$fk_product = $line->fk_product;
							$prod = new Product($db);
							$prod->fetch($fk_product);

							// Restock entièrement créé à la main car la fonction correct_stock ne permet pas d'ajouter un avoir comme élément d'origine
							$prod->origin = $object;
							$prod->origin->id = $object->id;
							$movementstock=new MouvementStock($db);
							$movementstock->origin = $object;
							$movementstock->origin->id = $object->id;
							$result=$movementstock->_create($user,$prod->id,$fk_entrepot,'+'.$qty,0,$line->pa_ht,$langs->trans('Restockage via avoir'),'');

							if(!empty($result)) $nb_restock+=$qty;

						}
					}

					// Nécessité de faire un commit() car on coupe la fonction validate() avec le header en dessous
					$db->commit();

					setEventMessage($langs->trans('NbRestockedElements', $nb_restock));
					header('Location: '.$_SERVER['PHP_SELF'].'?facid='.$object->id); // Pour éviter un autre stockage si F5 (paramètres passés en GET)
					exit;
				}
			}

		} elseif ($action === 'PROJECT_CREATE') {
			if (! TGrappeFruit::checkNoDuplicateRef($object))
				return - 1;


			if (! TGrappeFruit::checkBudgetNotEmpty($object))
				return - 1;
			if (! TGrappeFruit::checkDateEndNotEmpty($object))
				return - 1;

			TGrappeFruit::createTasks($object);

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
		} elseif ($action === 'PROJECT_MODIFY') {
			if (! TGrappeFruit::checkBudgetNotEmpty($object))
				return - 1;
			if (! TGrappeFruit::checkDateEndNotEmpty($object))
				return - 1;

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
		}elseif ($action === 'PROPAL_CREATE') {
			$db= $this->db;
			if (!empty($conf->global->GRAPEFRUIT_LINK_PROPAL_2_PROJECT)){
				dol_include_once('/projet/class/project.class.php');

				$projId = 0;
				$sql  = "SELECT p.rowid AS rowid";
		        $sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
		        $sql .= " WHERE p.fk_soc = ".$object->socid;
		        $sql .= " ORDER BY p.dateo DESC";
				$sql .= " LIMIT 1";

				$resql = $db->query($sql);

				if ($resql){
					while ($line = $db->fetch_object($resql)){
								$projId = $line->rowid;
					}
				}

				//On fetch le projet
				$projet = new Project($db);
				$projet->fetch($projId);

				//TODO Ajouter la propale au projet
				//var_dump($object->table_element, $object->id);exit;
				$projet->update_element($object->table_element, $object->id);

			}
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
		 } elseif ($action == 'ORDER_SUPPLIER_DISPATCH') {
		// classify supplier order delivery status
		dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

		if (! empty($conf->commande->enabled) && ! empty($conf->fournisseur->enabled) && ! empty($conf->global->GRAPEFRUIT_SUPPLIER_ORDER_CLASSIFY_RECEIPT_ORDER))
		{
			dol_include_once('/grapefruit/class/supplier.commande.dispatch.class.php');

			$qtydelivered=array();
			$qtywished=array();

			$supplierorderdispatch = new CommandeFournisseurDispatchATM($this->db);
			$filter=array('t.fk_commande'=>$object->id);
			if (!empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS)) {
				$filter['t.status']=1;
			}
			$ret=$supplierorderdispatch->fetchAll('','',0,0,$filter);
			if ($ret<0) {
				$this->error=$supplierorderdispatch->error; $this->errors=$supplierorderdispatch->errors;
				return $ret;
			} else {
					if (is_array($supplierorderdispatch->lines) && count($supplierorderdispatch->lines)>0) {
						//Build array with quantity deliverd by product
						foreach($supplierorderdispatch->lines as $line) {
							if (!empty($line->fk_product)) {
								$qtydelivered[$line->fk_product]+=$line->qty;
							}
						}
						foreach($object->lines as $line) {
							if (!empty($line->fk_product)) {
								$qtywished[$line->fk_product]+=$line->qty;
							}
						}
						//Compare array
						$diff_array=array_diff_assoc($qtydelivered,$qtywished);
						$diff_array=array_merge($diff_array, array_diff_assoc($qtywished,$qtydelivered)); // dans les 2 sens parce que la fonction teste pas dans les 2 sens (array_diff_assoc($a, $b) ne donne pas forcement le meme res que array_diff_assoc($b, $a))

						if (count($diff_array)==0) {
							//No diff => mean everythings is received
							$ret=$object->setStatus($user,5);
							if ($ret<0) {
								$this->error=$object->error; $this->errors=$object->errors;
								return $ret;
							}
						} else {
							//Diff => received partially
							$ret=$object->setStatus($user,4);
							if ($ret<0) {
								$this->error=$object->error; $this->errors=$object->errors;
								return $ret;
							}
						}
					}
			}
		}
        } elseif ($action === 'SHIPPING_VALIDATE') {

			if(!empty($conf->global->GRAPEFRUIT_SET_ORDER_SHIPPED_IF_ALL_PRODUCT_SHIPPED)) TGrappeFruit::setOrderShippedIfAllProductShipped($object);

			if(! empty($conf->expedition->enabled) && !empty($conf->global->MAIN_SUBMODULE_LIVRAISON) && ! empty($conf->global->GRAPEFRUIT_CREATE_DELIVERY_FROM_SHIPPING))
			{

			    include_once DOL_DOCUMENT_ROOT.'/livraison/class/livraison.class.php';
			    $delivery = new Livraison($this->db);
			    $result=$delivery->create_from_sending($user, $object->id);
			    if ($result < 0)
			    {
			        setEventMessages($delivery->error, $delivery->errors, 'errors');
			    }

			}

		} elseif ($action === 'SHIPPING_DELETE') {

			if(!empty($conf->global->GRAPEFRUIT_SET_RIGHT_ORDER_STATUS_ON_SHIPPING_DELETE)) TGrappeFruit::updateOrderStatusOnShippingDelete($object);

		} elseif ($action === 'ORDER_SUPPLIER_VALIDATE') {

			if($conf->global->GRAPEFRUIT_AUTO_ORDER_ON_SUPPLIERORDER_VALIDATION_WITH_METHOD > 0) TGrappeFruit::orderSupplierOrder($object, $conf->global->GRAPEFRUIT_AUTO_ORDER_ON_SUPPLIERORDER_VALIDATION_WITH_METHOD);

		}
		elseif ($action === 'PRODUCT_CREATE') {

			if(!empty($conf->global->GRAPEFRUIT_COPY_CAT_ON_CLONE)) {

				if(GETPOST('action') === 'confirm_clone') {
					$origin_id = GETPOST('id');

					$categorie_static = new Categorie($db);
					$categoriesid = $categorie_static->containing($origin_id, 0,'object');

					//$object->setCategories($categoriesid);
					foreach($categoriesid as &$cat) {
						$cat->add_type($object,'product');
					}

				}

			}

			dol_syslog(
					"Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
			);
		}
		elseif ($action === 'ORDER_SUPPLIER_RECEIVE') {

			dol_syslog(
					"Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
					);

			if(!empty($conf->global->GRAPEFRUIT_VALIDATE_SUPPLIERINVOICE_ON_RECEIPT_SUPPLIERORDER)) {

				if(GETPOST('type') === 'tot') {

					$object->fetchObjectLinked($object->id,$object->element,'','invoice_supplier');

					if (! empty($object->linkedObjects))
					{

						foreach($object->linkedObjects['invoice_supplier'] as $element)
						{
							if ($element->status==$element::STATUS_DRAFT) {
								$ret=$element->validate($user);
							}
						}
					}

				}
			}
		}

		if($action === 'PROPAL_CREATE' || $action === 'ORDER_CREATE'){

			if($conf->global->GRAPEFRUIT_PROJECT_AUTO_WIN){

				if($object->fk_project){
					$projet = new Project($db);
					$projet->fetch($object->fk_project);

					if($projet->opp_status != 6){

						$projet->opp_status = 6;
						$projet->update($user);
					}
				}
			}

		}
		/*
		 if ($action === 'USER_LOGIN') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }
		 else if($action ==='LINECONTRACT_INSERT') {

		 TGrappeFruit::checkContractFourn($object);


		 }
		 elseif ($action === 'USER_UPDATE_SESSION') {
		 // Warning: To increase performances, this action is triggered only if
		 // constant MAIN_ACTIVATE_UPDATESESSIONTRIGGER is set to 1.
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'USER_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'USER_CREATE_FROM_CONTACT') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'USER_MODIFY') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'USER_NEW_PASSWORD') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'USER_ENABLEDISABLE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'USER_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'USER_LOGOUT') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'USER_SETINGROUP') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'USER_REMOVEFROMGROUP') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Groups
		 elseif ($action === 'GROUP_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'GROUP_MODIFY') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'GROUP_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Companies
		 elseif ($action === 'COMPANY_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'COMPANY_MODIFY') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'COMPANY_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Contacts
		 elseif ($action === 'CONTACT_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'CONTACT_MODIFY') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'CONTACT_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Products
		 elseif ($action === 'PRODUCT_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'PRODUCT_MODIFY') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'PRODUCT_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Customer orders
		 elseif ($action === 'ORDER_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'ORDER_CLONE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'ORDER_VALIDATE') {
		 TGrappeFruit::createBillOnOrderValidate($object);


		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'ORDER_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'ORDER_BUILDDOC') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'ORDER_SENTBYMAIL') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'LINEORDER_INSERT') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'LINEORDER_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Supplier orders
		 elseif ($action === 'ORDER_SUPPLIER_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'ORDER_SUPPLIER_VALIDATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'ORDER_SUPPLIER_SENTBYMAIL') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'SUPPLIER_ORDER_BUILDDOC') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Proposals
		 elseif ($action === 'PROPAL_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'PROPAL_CLONE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'PROPAL_MODIFY') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'PROPAL_VALIDATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'PROPAL_BUILDDOC') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'PROPAL_SENTBYMAIL') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'PROPAL_CLOSE_SIGNED') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'PROPAL_CLOSE_REFUSED') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'PROPAL_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'LINEPROPAL_INSERT') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'LINEPROPAL_MODIFY') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'LINEPROPAL_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Contracts
		 elseif ($action === 'CONTRACT_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'CONTRACT_MODIFY') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'CONTRACT_ACTIVATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'CONTRACT_CANCEL') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'CONTRACT_CLOSE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'CONTRACT_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Bills
		 elseif ($action === 'BILL_CREATE') {

		 if(!empty($conf->global->GRAPEFRUIT_LINK_INVOICE_TO_SESSION_IF_PROPAL_IS) && $conf->agefodd->enabled) {
		 //        		var_dump($object->origin, $object->origin_id);
		 if($object->origin == 'propal' && $object->origin_id>0) {
		 $db = &$this->db;

		 dol_include_once('/agefodd.git/class/agefodd_session_element.class.php');

		 $res = $db->query("SELECT fk_session_agefodd FROM ".MAIN_DB_PREFIX."agefodd_session_element WHERE fk_element=".$object->origin_id." AND element_type='propal'");
		 $obj = $db->fetch_object($res);
		 //var_dump($obj->fk_session_agefodd);

		 if($obj->fk_session_agefodd>0) {
		 $agf = new Agefodd_session_element($db);
		 $agf->fk_element = $object->id;
		 $agf->fk_session_agefodd = $obj->fk_session_agefodd;
		 $agf->fk_soc = empty($object->socid) ? $object->fk_soc : $object->socid;
		 $agf->element_type = 'invoice';0
		 $result = $agf->create($user);
		 //var_dump($result);
		 }
		 //var_dump($db);
		 //				exit('!');

		 }
		 }


		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'BILL_CLONE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'BILL_MODIFY') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'BILL_PAYED') {

		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );

		 TGrappeFruit::createShippingFromOrderOnBillPayed($object);

		 if (empty($conf->global->GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE_ORDER)) {
		 TGrappeFruit::sendBillByMail($object);
		 } else {
		 if(empty($object->linked_objects)) $object->fetchObjectLinked(null,null,$object->id,'facture');

		 if(empty($object->linkedObjects['commande'])) return false;

		 foreach($object->linkedObjects['commande'] as &$commande) {
		 TGrappeFruit::sendOrderByMail($commande);
		 }
		 }



		 } elseif ($action === 'BILL_BUILDDOC') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'BILL_SENTBYMAIL') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'BILL_CANCEL') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'BILL_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'LINEBILL_INSERT') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'LINEBILL_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Payments
		 elseif ($action === 'PAYMENT_CUSTOMER_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'PAYMENT_SUPPLIER_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'PAYMENT_ADD_TO_BANK') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'PAYMENT_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Interventions
		 elseif ($action === 'FICHEINTER_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'FICHEINTER_MODIFY') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'FICHEINTER_VALIDATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'FICHEINTER_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Members
		 elseif ($action === 'MEMBER_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'MEMBER_VALIDATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'MEMBER_SUBSCRIPTION') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'MEMBER_MODIFY') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'MEMBER_NEW_PASSWORD') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'MEMBER_RESILIATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'MEMBER_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Categories
		 elseif ($action === 'CATEGORY_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'CATEGORY_MODIFY') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'CATEGORY_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Projects
		 elseif ($action === 'PROJECT_CREATE') {
		 if(!TGrappeFruit::checkBudgetNotEmpty($object)) return -1;
		 if(!TGrappeFruit::checkDateEndNotEmpty($object)) return -1;

		 TGrappeFruit::createTasks($object);

		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'PROJECT_MODIFY') {
		 if(!TGrappeFruit::checkBudgetNotEmpty($object)) return -1;
		 if(!TGrappeFruit::checkDateEndNotEmpty($object)) return -1;

		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'PROJECT_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Project tasks
		 elseif ($action === 'TASK_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'TASK_MODIFY') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'TASK_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Task time spent
		 elseif ($action === 'TASK_TIMESPENT_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'TASK_TIMESPENT_MODIFY') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'TASK_TIMESPENT_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // Shipping
		 elseif ($action === 'SHIPPING_CREATE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'SHIPPING_MODIFY') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'SHIPPING_SENTBYMAIL') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'SHIPPING_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'SHIPPING_BUILDDOC') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }

		 // File
		 elseif ($action === 'FILE_UPLOAD') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 } elseif ($action === 'FILE_DELETE') {
		 dol_syslog(
		 "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
		 );
		 }
		 */
		return 0;
	}
}
