<?php
class TGrappeFruit
{
	public $error;

	static function createSupplierPriceFromProposal(&$object) {

		global $conf, $langs, $db, $user;

		if (empty($conf->global->GRAPEFRUIT_SUPPLIER_PROPAL_CREATE_PRICE_ON_ACCEP))
			return true;


		foreach($object->lines as &$line) {

			if(!empty($line->ref_fourn) && $line->subprice>0) {

				$product = new ProductFournisseur($db);
				$product->fetch($line->fk_product);

				$fourn = new Fournisseur($db);
				$fourn->id = $object->socid;
				$product->product_fourn_id = $fourn->id;

				// La methode update_buyprice() renvoie -1 ou -2 en cas d'erreur ou l'id de l'objet modifié ou créé en cas de réussite
				$ret=$product->update_buyprice( $line->qty, $line->total_ht, $user, 'HT', $fourn, 1, $line->ref_fourn, $line->tva_tx, 0, $line->remise_percent);

			}

		}


	}

	static function checkNoDuplicateRef(&$object) {
		global $conf, $langs, $db;

		if (empty($conf->global->GRAPEFRUIT_DISALLOW_SAME_REF_MULTICOMPANY))
			return true;

		$res = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."projet WHERE ref = '".$object->ref."' AND rowid!=".(int)$object->id." LIMIT 1");
		if($obj = $db->fetch_object($res)) {
			setEventMessage($langs->trans('DuplicateProjectRef'), 'errors');

			return false;

		}

		return true;

	}

	/**
	 *
	 * @param unknown $object
	 */
	static function checkBudgetNotEmpty(&$object) {
		global $conf, $langs;

		if (empty($conf->global->GRAPEFRUIT_BUDGET_NEEDED))
			return true;

		if (empty($object->budget_amount)) {
			setEventMessage($langs->trans('BudgetRequire'), 'errors');
			return false;
		} else {
			return true;
		}
	}

	/**
	 *
	 * @param unknown $object
	 */
	static function checkDateEndNotEmpty(&$object) {
		global $conf, $langs;
		if (empty($conf->global->GRAPEFRUIT_DATEEND_NEEDED))
			return true;
		if (empty($object->date_end)) {
			setEventMessage($langs->trans('ProjectDateEndRequire'), 'errors');
			return false;
		} else {
			return true;
		}
	}

	/**
	 *
	 * @param unknown $object
	 */
	static function createShippingFromOrderOnBillPayed(&$object,$user) {
		global $conf, $langs, $db;
		if (empty($conf->global->GRAPEFRUIT_SHIPPING_CREATE_FROM_ORDER_WHERE_BILL_PAID) || $object->element != 'facture')
			return true;

		if (empty($object->linked_objects))
			$object->fetchObjectLinked(null, null, $object->id, 'facture');

		if (empty($object->linkedObjects['commande']))
			return false;

		dol_include_once('/expedition/class/expedition.class.php');

		$TNotCopy = array (
				'db',
				'element',
				'table_element',
				'fk_element'
		);

		foreach ( $object->linkedObjects['commande'] as &$commande ) {

			$expedition = new Expedition($db);

			foreach ( $commande as $k => $v ) {
				if (! in_array($k, $TNotCopy)) {
					$expedition->{$k} = $commande->{$k};
				}
			}
			$expedition->tracking_number = '';

			$expedition->size = 0;
			$expedition->weight = 0;

			$expedition->sizeS = $expedition->sizeH = $expedition->sizeW = 0; // TODO Should use this->trueDepth

			$expedition->width = 0;
			$expedition->height = 0;
			$expedition->weight_units = 0;
			$expedition->size_units = 0;

			if (empty($conf->global->GRAPEFRUIT_SHIPPING_CREATE_FROM_ORDER_WHERE_BILL_PAID_WAREHOUSE)) {
				setEventMessage($langs->trans('DefaultWarehouseRequired'));
				return false;
			}

			foreach ( $expedition->lines as &$line ) {
				$line->entrepot_id = ( int ) $conf->global->GRAPEFRUIT_SHIPPING_CREATE_FROM_ORDER_WHERE_BILL_PAID_WAREHOUSE;
				$line->origin_line_id = $line->id;
			}

			$res = $expedition->create($user);
			if ($res > 0) {

				$expedition->add_object_linked($commande->element, $commande->id);

				setEventMessage($langs->trans('ShippingCreated'));
			} else {
				var_dump($expedition);
				exit();
			}
		}

		return true;
	}

	/**
	 *
	 * @param unknown $object
	 */
	static function createBillOnOrderValidate(&$object) {
		global $conf, $langs, $db, $user;
		if (empty($conf->global->GRAPEFRUIT_ORDER_CREATE_BILL_ON_VALIDATE))
			return true;

		dol_include_once('/compta/facture/class/facture.class.php');

		$facture = new Facture($db);
		$res = $facture->createFromOrder($object);
		if ($res > 0) {
			setEventMessage($langs->trans('BillCreated'));
		}

		// Transfert Contact from order to invoice

		return false;
	}

	static function billCloneLink(&$object, &$originObject) {
		global $conf, $db;

		if(!empty($conf->global->GRAPEFRUIT_BILL_COPY_LINKS_ON_CLONE)) {

				$originObject->fetchObjectLinked( $originObject->id, 'facture' ,  $originObject->id, 'facture' );
			//var_dump($originObject->linkedObjectsIds);exit;

				foreach($originObject->linkedObjectsIds as $typeObject=>$TId) {

					foreach($TId as $id) {

						$object->add_object_linked($typeObject,$id);

					}

				}

		}

	}

	/**
	 *
	 * @param unknown $object
	 */
	static function sendBillByMail(&$object) {
		global $conf, $langs, $user, $db;

		if (empty($object->thirdparty))
			$object->fetch_thirdparty();

		$sendto = $object->thirdparty->email;
		$sendtocc = '';

		$from = (empty($user->email) ? $conf->global->MAIN_MAIL_EMAIL_FROM : $user->email);
		$id = $object->id;

		$_POST['receiver'] = '-1';

		$_POST['frommail'] = $_POST['replytomail'] = $from;
		$_POST['fromname'] = $_POST['replytoname'] = $user->getFullName($langs);

		dol_include_once('/core/class/html.formmail.class.php');
		$formmail = new Formmail($db);
		$outputlangs = clone $langs;
		$id_template = ( int ) $conf->global->GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE_MODEL;

		$formmail->fetchAllEMailTemplate('facture_send', $user, $outputlangs);

		foreach ( $formmail->lines_model as &$model ) {

			if ($model->id == $id_template)
				break;
		}

		if (empty($model))
			setEventMessage($langs->trans('ModelRequire'), 'errors');

			// Find Order to put it into ref
		if (empty($object->linked_objects))
			$object->fetchObjectLinked(null, null, $object->id, 'facture');
		if(!empty($object->linkedObjects['commande'])) {
			foreach ( $object->linkedObjects['commande'] as $commande ) {
				$orderref .= $commande->ref;
			}
		}

		// Make substitution
		$substit['__REF__'] = (empty($object->newref)?$object->ref:$object->newref);
		$substit['__ORDER_REF__'] = $orderref;
		$substit['__SIGNATURE__'] = $user->signature;
		$substit['__REFCLIENT__'] = $object->ref_client;
		$substit['__THIRDPARTY_NAME__'] = $object->thirdparty->name;
		$substit['__PROJECT_REF__'] = (is_object($object->projet) ? $object->projet->ref : '');
		$substit['__PROJECT_NAME__'] = (is_object($object->projet) ? $object->projet->title : '');
		$substit['__PERSONALIZED__'] = '';
		$substit['__CONTACTCIVNAME__'] = '';

		// Find the good contact adress
		$custcontact = '';
		$contactarr = array ();
		$contactarr = $object->liste_contact(- 1, 'external');

		if (is_array($contactarr) && count($contactarr) > 0) {
			foreach ( $contactarr as $contact ) {
				dol_syslog(get_class($this) . '::' . __METHOD__ . 'lib=' . $contact['libelle']);
				dol_syslog(get_class($this) . '::' . __METHOD__ . 'trans=' . $langs->trans('TypeContact_facture_external_BILLING'));

				if ($contact['libelle'] == $langs->trans('TypeContact_facture_external_BILLING')) {

					require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

					$contactstatic = new Contact($db);
					$contactstatic->fetch($contact['id']);
					$custcontact = $contactstatic->getFullName($langs, 1);

					dol_syslog(get_class($this) . '::' . __METHOD__ . ' email=' . $contactstatic->email);
				}
			}

			if (! empty($custcontact)) {
				$substit['__CONTACTCIVNAME__'] = $custcontact;
			}
			if (! empty($contactstatic->email)) {
				$sendto = $contactstatic->email;
			}
		}

		$topic = make_substitutions($model->topic, $substit);
		$message = make_substitutions($model->content, $substit);

		$_POST['message'] = $message;
		$_POST['subject'] = $topic;

		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		// Add attached files
		$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $object->ref, preg_quote($object->ref, '/') . '[^\-]+');
		if (is_array($fileparams) && array_key_exists('fullname', $fileparams) && ! empty($fileparams['fullname'])) {
			$_SESSION["listofpaths"] = $fileparams['fullname'];
			$_SESSION["listofnames"] = basename($fileparams['fullname']);
			$_SESSION["listofmimes"] = dol_mimetype($fileparams['fullname']);
		} else {
			// generate invoice
			$result = $object->generateDocument($object->modelpdf, $outputlangs, 0, 0, 0);
			if ($result <= 0) {
				$this->error = $object->error;
			}
			$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $object->ref, preg_quote($object->ref, '/') . '[^\-]+');
			if (is_array($fileparams) && array_key_exists('fullname', $fileparams) && ! empty($fileparams['fullname'])) {
				$_SESSION["listofpaths"] = $fileparams['fullname'];
				$_SESSION["listofnames"] = basename($fileparams['fullname']);
				$_SESSION["listofmimes"] = dol_mimetype($fileparams['fullname']);
			}
		}

		$action = 'send';
		$actiontypecode = 'AC_FAC';
		$trigger_name = 'BILL_SENTBYMAIL';
		$paramname = 'id';
		$mode = 'emailfrominvoice';

		if (! empty($sendto)) {
			require_once __DIR__ . '/../tpl/actions_sendmails.inc.php';
		}
	}

	/**
	 *
	 * @param unknown $object
	 */
	static function sendOrderByMail(&$object) {
		global $conf, $langs, $user, $db;

		if (empty($object->thirdparty))
			$object->fetch_thirdparty();

		$sendto = $object->thirdparty->email;
		$sendtocc = '';

		$from = (empty($user->email) ? $conf->global->MAIN_MAIL_EMAIL_FROM : $user->email);
		$id = $object->id;

		$_POST['receiver'] = '-1';

		$_POST['frommail'] = $_POST['replytomail'] = $from;
		$_POST['fromname'] = $_POST['replytoname'] = $user->getFullName($langs);

		dol_include_once('/core/class/html.formmail.class.php');
		$formmail = new Formmail($db);
		$outputlangs = clone $langs;
		$id_template = ( int ) $conf->global->GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE_MODEL;

		$formmail->fetchAllEMailTemplate('facture_send', $user, $outputlangs);

		foreach ( $formmail->lines_model as &$model ) {

			if ($model->id == $id_template)
				break;
		}

		if (empty($model))
			setEventMessage($langs->trans('ModelRequire'), 'errors');

			// Make substitution
		$substit['__REF__'] = $object->ref;
		$substit['__SIGNATURE__'] = $user->signature;
		$substit['__REFCLIENT__'] = $object->ref_client;
		$substit['__THIRDPARTY_NAME__'] = $object->thirdparty->name;
		$substit['__PROJECT_REF__'] = (is_object($object->projet) ? $object->projet->ref : '');
		$substit['__PROJECT_NAME__'] = (is_object($object->projet) ? $object->projet->title : '');
		$substit['__PERSONALIZED__'] = '';
		$substit['__CONTACTCIVNAME__'] = '';

		// Find the good contact adress
		$custcontact = '';
		$contactarr = array ();
		$contactarr = $object->liste_contact(- 1, 'external');

		if (is_array($contactarr) && count($contactarr) > 0) {
			foreach ( $contactarr as $contact ) {
				dol_syslog(get_class($this) . '::' . __METHOD__ . ' lib=' . $contact['libelle']);
				dol_syslog(get_class($this) . '::' . __METHOD__ . ' trans=' . $langs->trans('TypeContact_commande_external_BILLING'));

				if ($contact['libelle'] == $langs->trans('TypeContact_commande_external_BILLING')) {

					require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

					$contactstatic = new Contact($db);
					$contactstatic->fetch($contact['id']);
					$custcontact = $contactstatic->getFullName($langs, 1);

					dol_syslog(get_class($this) . '::' . __METHOD__ . ' email=' . $contactstatic->email);
				}
			}

			if (! empty($custcontact)) {
				$substit['__CONTACTCIVNAME__'] = $custcontact;
			}
			if (! empty($contactstatic->email)) {
				$sendto = $contactstatic->email;
			}
		}

		$topic = make_substitutions($model->topic, $substit);
		$message = make_substitutions($model->content, $substit);

		$_POST['message'] = $message;
		$_POST['subject'] = $topic;

		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		// Add attached files
		$fileparams = dol_most_recent_file($conf->commande->dir_output . '/' . $object->ref, preg_quote($object->ref, '/') . '[^\-]+');
		if (is_array($fileparams) && array_key_exists('fullname', $fileparams) && ! empty($fileparams['fullname'])) {
			$_SESSION["listofpaths"] = $fileparams['fullname'];
			$_SESSION["listofnames"] = basename($fileparams['fullname']);
			$_SESSION["listofmimes"] = dol_mimetype($fileparams['fullname']);
		} else {
			// generate invoice
			$result = $object->generateDocument($object->modelpdf, $outputlangs, 0, 0, 0);
			if ($result <= 0) {
				$this->error = $object->error;
			}
			$fileparams = dol_most_recent_file($conf->commande->dir_output . '/' . $object->ref, preg_quote($object->ref, '/') . '[^\-]+');
			if (is_array($fileparams) && array_key_exists('fullname', $fileparams) && ! empty($fileparams['fullname'])) {
				$_SESSION["listofpaths"] = $fileparams['fullname'];
				$_SESSION["listofnames"] = basename($fileparams['fullname']);
				$_SESSION["listofmimes"] = dol_mimetype($fileparams['fullname']);
			}
		}

		$action = 'send';
		$actiontypecode = 'AC_FAC';
		$trigger_name = 'BILL_SENTBYMAIL';
		$paramname = 'id';
		$mode = 'emailfrominvoice';
		if (! empty($sendto)) {
			require_once __DIR__ . '/../tpl/actions_sendmails.inc.php';
		}
	}

	/**
	 *
	 * @param unknown $object
	 */
	static function createTasks(&$object) {
		global $conf, $langs, $db, $user;

		if (! empty($conf->global->GRAPEFRUIT_PROJECT_AUTO_ADD_TASKS_ON_CREATE)) {

			$TLabel = explode("\n", $conf->global->GRAPEFRUIT_PROJECT_AUTO_ADD_TASKS_ON_CREATE);

			dol_include_once('/projet/class/task.class.php');

			foreach ( $TLabel as $label ) {

				$label = trim($label);
				if(!empty($label)) { // pour ne pas prendre le cas du retour à la ligne vide

					$t = new Task($db);

					$defaultref = '';
					$obj = empty($conf->global->PROJECT_TASK_ADDON) ? 'mod_task_simple' : $conf->global->PROJECT_TASK_ADDON;
					if (! empty($conf->global->PROJECT_TASK_ADDON) && is_readable(DOL_DOCUMENT_ROOT . "/core/modules/project/task/" . $conf->global->PROJECT_TASK_ADDON . ".php")) {
						require_once DOL_DOCUMENT_ROOT . "/core/modules/project/task/" . $conf->global->PROJECT_TASK_ADDON . '.php';
						$modTask = new $obj();
						$defaultref = $modTask->getNextValue($soc, $object);
					}

					if (is_numeric($defaultref) && $defaultref <= 0)
						$defaultref = '';

					$t->ref = $defaultref;
					$t->label = $label;
					$t->fk_project = $object->id;
					$t->fk_task_parent = 0;
					$t->date_c = dol_now();

					$res = $t->create($user);

					if(!empty($conf->global->GRAPEFRUIT_PROJECT_TYPE_FOR_TASK) && $conf->global->GRAPEFRUIT_PROJECT_TYPE_FOR_TASK > 0){
					    $t->add_contact($user->id, $conf->global->GRAPEFRUIT_PROJECT_TYPE_FOR_TASK, 'internal');
					}


					if ($res < 0) {
						setEventMessage($langs->trans('ImpossibleToAdd', $label));
					}
				}
			}

			setEventMessage($langs->trans('autoTasksAdded'));
		}
	}

	/**
	 *
	 * @param unknown $object
	 * @return boolean
	 */
	static function checkContractFourn(&$object) {
		global $conf, $langs, $db;

		if (empty($conf->global->GRAPEFRUIT_CONTRACT_DEFAUL_FOURN) || $conf->global->GRAPEFRUIT_CONTRACT_DEFAUL_FOURN < 0)
			return true;

		dol_include_once('/fourn/class/fournisseur.product.class.php');

		foreach ( $object->lines as &$line ) {

			if (empty($line->fk_fournprice) && $line->fk_product > 0) {

				$p_static = new ProductFournisseur($db);
				$TPrice = $p_static->list_product_fournisseur_price($line->fk_product);

				foreach ( $TPrice as &$price ) {

					if ($price->fourn_id == $conf->global->GRAPEFRUIT_CONTRACT_DEFAUL_FOURN) {
						// TODO AA updateline sur contrat, là j'ai la flemme, no comment
						$db->query("UPDATE " . MAIN_DB_PREFIX . "contratdet
						SET fk_product_fournisseur_price=" . $price->product_fourn_price_id . ",buy_price_ht=" . ($price->fourn_price / $price->fourn_qty) . "
						WHERE rowid=" . $line->id);
						break;
					}
				}
			}
		}
	}

	/**
	 * @param $object : object expédition
	 */
	static function setOrderShippedIfAllProductShipped(&$object) {

		// Récupération de la commande d'origine
		$object->fetchObjectLinked();
		$TOriginOrder = array_values($object->linkedObjects['commande']);
		$order = $TOriginOrder[0];
		if(empty($order)) return 0;

		// On refait la fonction dans l'autre sens car la commande peut avoir été expédiée en plusieurs fois
		$order->fetchObjectLinked();
		$TShipping = array_values($order->linkedObjects['shipping']);

		// Rangement des quantités dans la commande par produit, uniquement les produits avec un fk_product
		$TOrderProductQty = array();
		if(!empty($order->lines)) {
			foreach($order->lines as $order_line) {
				if($order_line->product_type == 0 && !empty($order_line->fk_product)) $TOrderProductQty[$order_line->fk_product] += $order_line->qty;
			}
		}

		// Rangement des quantités dans l'expédition par produit
		$TShippingProductQty = array();
		foreach($TShipping as $shipping) {
			if(!empty($shipping->lines)) {
				foreach($shipping->lines as $shipping_line) {
					if (!empty($shipping_line->fk_product)) {
						$TShippingProductQty[$shipping_line->fk_product] += $shipping_line->qty;
					}
				}
			}
		}

		if(count($TOrderProductQty) != count($TShippingProductQty)) return 0;

		foreach($TShippingProductQty as $fk_product=>$qty) {
			if($qty < $TOrderProductQty[$fk_product]) return 0;
		}

		// Si on a passé le test des quantités sans problèmes, on passe la commande au statut "Livrée"
		if($order->setStatut(3) > 0) setEventMessage('Commande '.$order->getNomUrl().' passée au statut "livrée"');

	}

	function updateOrderStatusOnShippingDelete(&$expedition) {

		global $db, $user, $langs;

		require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

		$langs->load('grapefruit@grapefruit');
		$langs->load('orders');

		// On charge la commande d'origine pour vérifier s'il y a d'autres expéditions
		$commande = new Commande($db);
		$commande->fetch($expedition->origin_id);
		$commande->fetchObjectLinked($commande->id, 'commande', $expedition->id, 'shipping');

		// S'il n'y a pas d'autre exped, on repasse au status validée, Sinon au statut en cours
		if(empty($commande->linkedObjects['shipping'])) {
			// Je fais pas de set_reopen parce qu'il enlève aussi le statut facturé
			$db->query('UPDATE '.MAIN_DB_PREFIX.'commande SET fk_statut = 1 WHERE rowid = '.$commande->id);
			$status = $langs->trans('StatusOrderValidatedShort');
		}
		else {
			$db->query('UPDATE '.MAIN_DB_PREFIX.'commande SET fk_statut = 2 WHERE rowid = '.$commande->id);
			$status = $langs->trans('StatusOrderSentShort');
		}

		setEventMessage($langs->trans('grapefruit_order_status_set_to', $commande->getNomUrl(), $status));

	}

	/**
	 * @param $object : object facture
	 */
	static function setOrderBilledIfSameMontant(&$object) {

		global $user, $langs;

		$langs->load('orders');

		$order=array();
		// Récupération de la commande d'origine
		$object->fetchObjectLinked();

		if (is_array($object->linkedObjects) && array_key_exists('commande', $object->linkedObjects) && count($object->linkedObjects['commande'])>0 ) {
			$TOriginOrder = array_values($object->linkedObjects['commande']);
			$order = $TOriginOrder[0];
		}
		if(empty($order)) return 0;

		$TFact=array();
		// On refait la fonction dans l'autre sens car la commande peut avoir été facturée en plusieurs fois
		$order->fetchObjectLinked();
		if (is_array($object->linkedObjects) && array_key_exists('facture', $object->linkedObjects) && count($object->linkedObjects['facture'])>0 ) {
			$TFact = array_values($order->linkedObjects['facture']);
		}

		$total_ttc = 0;
		foreach($TFact as $f) {
			if($f->statut > 0) $total_ttc+=$f->total_ttc;
		}
		if(empty($TFact)) return 0;

		// On compare les montants
		if($total_ttc == $order->total_ttc) {
			// On classe facturée la commande uniquement si elle ne l'est pas déjà (peut avoir été fait avant)
			if(empty($order->billed)) {
				if((float)DOL_VERSION >= 4.0) $res_classifybill = $order->classifyBilled($user);
				else $res_classifybill = $order->classifyBilled();
				if($res_classifybill > 0) setEventMessage($langs->trans('grapefruit_order_status_set_to', $order->getNomUrl(), $langs->transnoentities('StatusOrderBilled')));
			}
		}

	}

	function orderSupplierOrder(&$object, $methode_id) {

		global $user;

		$object->commande($user, time(), $methode_id);

	}

	static function clotureOriginPropal(&$object) {

		global $user;

		$object->fetchObjectLinked();
		if(empty($object->linkedObjects['propal'])) return 0;

		$TOriginPropal = array_values($object->linkedObjects['propal']);
		$propal = $TOriginPropal[0];

		if(empty($propal)) return 0;
		if(empty($propal->statut)) $propal->valid($user); // On commence par la valider si c'est pas déjà fait
		if($propal->statut < 2) {
			if($propal->cloture($user, 2, '') > 0) setEventMessage('Proposition '.$propal->getNomUrl().' clôturée au statut "Signée" automatiquement');
		}

	}

	static function createFactureFromObject(&$object) {

		global $db, $conf, $user, $langs;

		dol_include_once('/compta/facture/class/facture.class.php');

		$langs->load('grapefruit@grapefruit');

		$dateinvoice = dol_mktime(0, 0, 0, date('m'), date('d'), date('Y'));

		$f = new Facture($db);

		$f->socid				= $object->socid;
		$f->type				= Facture::TYPE_STANDARD;
		$f->number				= $_POST['facnumber'];
		$f->date				= $dateinvoice;
		$f->note_public			= $object->note_public;
		$f->note_private		= $object->note_private;
		$f->ref_client			= $object->ref_client;
		$f->fk_project			= $object->fk_project;
		$f->cond_reglement_id	= $object->cond_reglement_id;
		$f->mode_reglement_id	= $object->mode_reglement_id;

		$origin = 'commande';
		$originid = $object->id;
		$f->linked_objects[$origin] = $originid;

		$id = $f->create($user);

		$lines = $object->lines;
		if (empty($lines) && method_exists($object, 'fetch_lines'))
		{
			$object->fetch_lines();
			$lines = $object->lines;
		}

		$fk_parent_line=0;
		$num=count($lines);
		for ($i=0;$i<$num;$i++)
		{

			$label=(! empty($lines[$i]->label)?$lines[$i]->label:'');
			$desc=(! empty($lines[$i]->desc)?$lines[$i]->desc:$lines[$i]->libelle);
			if ($f->situation_counter == 1) $lines[$i]->situation_percent =  0;

			if ($lines[$i]->subprice < 0)
			{
				// Negative line, we create a discount line
				$discount = new DiscountAbsolute($db);
				$discount->fk_soc = $f->socid;
				$discount->amount_ht = abs($lines[$i]->total_ht);
				$discount->amount_tva = abs($lines[$i]->total_tva);
				$discount->amount_ttc = abs($lines[$i]->total_ttc);
				$discount->tva_tx = $lines[$i]->tva_tx;
				$discount->fk_user = $user->id;
				$discount->description = $desc;
				$discountid = $discount->create($user);
				if ($discountid > 0) {
					$result = $f->insert_discount($discountid); // This include link_to_invoice
				} else {
					setEventMessages($discount->error, $discount->errors, 'errors');
					$error ++;
					break;
				}
			} else {
				// Positive line
				$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);

				// Date start
				$date_start = false;
				if ($lines[$i]->date_debut_prevue)
					$date_start = $lines[$i]->date_debut_prevue;
				if ($lines[$i]->date_debut_reel)
					$date_start = $lines[$i]->date_debut_reel;
				if ($lines[$i]->date_start)
					$date_start = $lines[$i]->date_start;

					// Date end
				$date_end = false;
				if ($lines[$i]->date_fin_prevue)
					$date_end = $lines[$i]->date_fin_prevue;
				if ($lines[$i]->date_fin_reel)
					$date_end = $lines[$i]->date_fin_reel;
				if ($lines[$i]->date_end)
					$date_end = $lines[$i]->date_end;

					// Reset fk_parent_line for no child products and special product
				if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
					$fk_parent_line = 0;
				}

				// Extrafields
				if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i], 'fetch_optionals')) {
					$lines[$i]->fetch_optionals($lines[$i]->rowid);
					$array_options = $lines[$i]->array_options;
				}

				// View third's localtaxes for now
				$localtax1_tx = get_localtax($lines[$i]->tva_tx, 1, $f->client);
				$localtax2_tx = get_localtax($lines[$i]->tva_tx, 2, $f->client);

				$result = $f->addline($desc, $lines[$i]->subprice, $lines[$i]->qty, $lines[$i]->tva_tx, $localtax1_tx, $localtax2_tx, $lines[$i]->fk_product, $lines[$i]->remise_percent, $date_start, $date_end, 0, $lines[$i]->info_bits, $lines[$i]->fk_remise_except, 'HT', 0, $product_type, $lines[$i]->rang, $lines[$i]->special_code, $f->origin, $lines[$i]->rowid, $fk_parent_line, $lines[$i]->fk_fournprice, $lines[$i]->pa_ht, $label, $array_options, $lines[$i]->situation_percent, $lines[$i]->fk_prev_id, $lines[$i]->fk_unit);

				if ($result > 0) {
					$lineid = $result;
				} else {
					$lineid = 0;
					$error ++;
					break;
				}

				// Defined the new fk_parent_line
				if ($result > 0 && $lines[$i]->product_type == 9) {
					$fk_parent_line = $result;
				}
			}
		}

		if(empty($error)) {
			if($f->validate($user) > 0) {

				if((float)DOL_VERSION >= 4.0) $object->classifyBilled($user);
				else $object->classifyBilled();

				// Redirection vers écrand de paiement
				setEventMessage($langs->trans('BillCreated'));
				header('Location: '.dol_buildpath('/compta/paiement.php?action=create&facid='.$f->id, 1));

			}
		}

	}

	/**
	 *
	 * @param unknown $action
	 * @return number
	 */
	public function setupUserDownloadRights($action='') {

		global $db, $conf, $user, $langs;
		$error=0;

		if ($action==='1') {
			if (dolibarr_set_const($db, 'USER_SUBPERMCATEGORY_FOR_DOCUMENTS', 'download', 'chaine', 0, '', $conf->entity) >0) {
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."rights_def";
				$sql.= " (id, entity, libelle, module, type, bydefault, perms, subperms)";
				$sql.= " VALUES ";
				$sql.= "(1049970,1,'DownloadDoc','user','r',0,'download','download')";
				$resqlinsert=$db->query($sql);
				if (!$resqlinsert) {
					if ($db->errno() != "DB_ERROR_RECORD_ALREADY_EXISTS")
					{
						$this->error=$db->lasterror();
						$error++;
					}
				}

				if (empty($error) && $conf->multicompany->enabled) {
					//Create rights on each entity
					$sqlentity="SELECT rowid FROM ".MAIN_DB_PREFIX."entity WHERE active=1 AND rowid<>1";
					$resqlentity=$db->query($sqlentity);
					if (!$resqlentity) {
						$this->error=$db->lasterror();
						$error++;
					} else {
						while ($obj=$db->fetch_object($resqlentity)) {
							if  (empty($error)) {
								$sql = "INSERT INTO ".MAIN_DB_PREFIX."rights_def";
								$sql.= " (id, entity, libelle, module, type, bydefault, perms, subperms)";
								$sql.= " VALUES ";
								$sql.= "(1049970,".$obj->rowid.",'DownloadDoc','user','r',0,'download','download')";
								$resqlinsert=$db->query($sql);
								if (!$resqlinsert) {
									if ($db->errno() != "DB_ERROR_RECORD_ALREADY_EXISTS")
									{
										$this->error=$db->lasterror();
										$error++;
									}
								}
							}
						}
					}
				}
			}else {
				$this->error=$db->lasterror();
				$error++;
			}
		}elseif ($action==='0') {
			if (dolibarr_del_const($db, 'USER_SUBPERMCATEGORY_FOR_DOCUMENTS', 0) > 0) {
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."rights_def";
				$sql.= " WHERE id=1049970";
				$resql=$db->query($sql);
				if (!$resql) {
					$this->error=$db->lasterror();
					$error++;
				}
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_rights";
				$sql.= " WHERE fk_id=1049970";
				$resql=$db->query($sql);
				if (!$resql) {
					$this->error=$db->lasterror();
					$error++;
				}
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights";
				$sql.= " WHERE fk_id=1049970";
				$resql=$db->query($sql);
				if (!$resql) {
					$this->error=$db->lasterror();
					$error++;
				}
			} else {
				$this->error=$db->lasterror();
				$error++;
			}
		}

		if(!empty($error)) {
			return -1;
		} else {
			return 1;
		}
	}

	/**
	 * Passe une facture au statut "Validé" après création depuis une origine
	 *
	 * @param	Facture				$object
	 * @param	Propal|Commande		$originObject
	 */
	public static function autoValidateIfFrom(&$object, &$originObject)
	{
		global $conf,$user;

		if (!empty($conf->global->GRAPEFRUIT_BILL_AUTO_VALIDATE_IF_ORIGIN) && !empty($originObject))
		{
			if (method_exists($object, 'fetch_lines')) $object->fetch_lines();
			else $object->fetch($object->id);

			if (!empty($object->lines)) $object->validate($user);
		}
	}

	static function getFormConfirmValidFacture(&$object) {

		global $conf, $langs, $form, $db;

		// on verifie si l'objet est en numerotation provisoire
		$objectref = substr($object->ref, 1, 4);
		if ($objectref == 'PROV') {
			$savdate = $object->date;
			if (! empty($conf->global->FAC_FORCE_DATE_VALIDATION)) {
				$object->date = dol_now();
				$object->date_lim_reglement = $object->calculate_date_lim_reglement();
			}
			$numref = $object->getNextNumRef($soc);
			// $object->date=$savdate;
		} else {
			$numref = $object->ref;
		}

		$text = $langs->trans('ConfirmValidateBill', $numref);
		if (! empty($conf->notification->enabled)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
			$notify = new Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('BILL_VALIDATE', $object->socid, $object);
		}
		$formquestion = array();

		$qualified_for_stock_change = 0;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}

		if ($object->type != Facture::TYPE_DEPOSIT && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change)
		{
			$langs->load("stocks");
			require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
			require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
			$formproduct = new FormProduct($db);
			$warehouse = new Entrepot($db);
			$warehouse_array = $warehouse->list_array();
			if (count($warehouse_array) == 1) {
				$label = $object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("WarehouseForStockIncrease", current($warehouse_array)) : $langs->trans("WarehouseForStockDecrease", current($warehouse_array));
				$value = '<input type="hidden" id="idwarehouse" name="idwarehouse" value="' . key($warehouse_array) . '">';
			} else {
				$label = $object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("SelectWarehouseForStockIncrease") : $langs->trans("SelectWarehouseForStockDecrease");
				$value = $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1);
			}
			$formquestion = array(
					// 'text' => $langs->trans("ConfirmClone"),
					// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' =>
					// 1),
					// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value'
					// => 1),
					array('type' => 'other','name' => 'idwarehouse','label' => $label,'value' => $value));
		}

		// Ajout des données de stock dans le formulaire
		$formquestion = array_merge($formquestion, self::getDataFormRestockProduct($object));

		if ($object->type != Facture::TYPE_CREDIT_NOTE && $object->total_ttc < 0) 		// Can happen only if $conf->global->FACTURE_ENABLE_NEGATIVE is on
		{
			$text .= '<br>' . img_warning() . ' ' . $langs->trans("ErrorInvoiceOfThisTypeMustBePositive");
		}
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id, $langs->trans('ValidateBill'), $text, 'confirm_valid', $formquestion, 'yes', 2, 220 + (31 * count($object->lines)), '700');

		return $formconfirm;

	}

	static function getDataFormRestockProduct(&$object) {

		global $db, $langs;

		$langs->load('grapefruit@grapefruit');
		$langs->load('main');
		$langs->load('stocks');

		require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';

		$formproduct=new FormProduct($db);
		$TWarehouses = array();

		$formproduct->loadWarehouses($fk_product, '', $filterstatus, true, $exclude);
		foreach($formproduct->cache_warehouses as $id_wh=>$tab_wh) $TWarehouses[$id_wh] = $tab_wh['label'];
		//var_dump($formproduct->cache_warehouses);exit;

		$tab=array(
				array('type'=>'select', 'name'=>'fk_entrepot', 'label'=>$langs->trans('WarehouseTarget'), 'values'=>$TWarehouses, 'default'=>key($TWarehouses), 'size'=>'2')
				,array('type'=>'other', 'name'=>'link_remplir', 'label'=>' ', 'value'=>'<a href="#" onclick="return false;" id="fillQty">'.$langs->trans('Fill').'</a>')
		);

		foreach($object->lines as &$line) {
			if(empty($line->product_type) && !empty($line->fk_product)) {
				$prod = new Product($db);
				$prod->fetch($line->fk_product);
				$tab[] = array('type'=>'text', 'name'=>'restock_line_'.$line->id, 'label'=>$langs->trans('QtyToRestockForProduct', $prod->getNomUrl(1), $line->qty), 'value'=>0, 'size'=>'2');
				$tab[] = array('type'=>'hidden', 'name'=>'qty_line_'.$line->id, 'value'=>$line->qty);
			}
		}

		return $tab;

	}

	static function printJSFillQtyToRestock() {

		?>

		<script type="text/javascript">
			$(document).ready(function() {
				$('#fillQty').click(function() {
					$('input[name*="restock_line_"]').each(function() {
						id_line = $(this).attr('name').replace('restock_line_', '');
						input_qty = $('#qty_line_'+id_line);
						$(this).val(input_qty.val());
					});
				});
			});
		</script>

		<?php

	}

}
