<?php
/* Copyright (C) 2018 		Netlogic			<info@netlogic.fr>
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
 * \file    core/triggers/interface_99_modDoliEsign_DoliEsignTriggers.class.php
 * \ingroup doliesign
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modDoliEsign_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for DoliEsign module
 */
class InterfaceDoliEsignTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "DoliEsign triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'doliesign@doliesign';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->doliesign->enabled)) return 0;     // Module not active, we do nothing

		$result = 0;

		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

		switch ($action) {

			// Users
			//case 'USER_CREATE':
			//case 'USER_MODIFY':
			//case 'USER_NEW_PASSWORD':
			//case 'USER_ENABLEDISABLE':
			//case 'USER_DELETE':
			//case 'USER_SETINGROUP':
			//case 'USER_REMOVEFROMGROUP':

			//case 'USER_LOGIN':
			//case 'USER_LOGIN_FAILED':
			//case 'USER_LOGOUT':
			//case 'USER_UPDATE_SESSION':      // Warning: To increase performances, this action is triggered only if constant MAIN_ACTIVATE_UPDATESESSIONTRIGGER is set to 1.

			// Actions
			//case 'ACTION_MODIFY':
			//case 'ACTION_CREATE':
			//case 'ACTION_DELETE':

			// Groups
			//case 'GROUP_CREATE':
			//case 'GROUP_MODIFY':
			//case 'GROUP_DELETE':

			// Companies
			//case 'COMPANY_CREATE':
			//case 'COMPANY_MODIFY':
			//case 'COMPANY_DELETE':

			// Contacts
			//case 'CONTACT_CREATE':
			//case 'CONTACT_MODIFY':
			//case 'CONTACT_DELETE':
			//case 'CONTACT_ENABLEDISABLE':

			// Products
			//case 'PRODUCT_CREATE':
			//case 'PRODUCT_MODIFY':
			//case 'PRODUCT_DELETE':
			//case 'PRODUCT_PRICE_MODIFY':
			//case 'PRODUCT_SET_MULTILANGS':
			//case 'PRODUCT_DEL_MULTILANGS':

			//Stock mouvement
			//case 'STOCK_MOVEMENT':

			//MYECMDIR
			//case 'MYECMDIR_DELETE':
			//case 'MYECMDIR_CREATE':
			//case 'MYECMDIR_MODIFY':

			// Customer orders
			//case 'ORDER_CREATE':
			//case 'ORDER_CLONE':
			//case 'ORDER_VALIDATE':
			case 'ORDER_DELETE':
			{
				$result = $this->cancelDoliEsign($object->id, $object->element, $user, $langs);
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			}
			case 'ORDER_CANCEL':
			{
				$result = $this->cancelDoliEsign($object->id, $object->element, $user, $langs);
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			}
			//case 'ORDER_SENTBYMAIL':
			//case 'ORDER_CLASSIFY_BILLED':
			case 'ORDER_SETDRAFT':
			{
				$result = $this->cancelDoliEsign($object->id, $object->element, $user, $langs);
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			}
			case 'ORDER_MODIFY':
			{
				$result = $this->cancelDoliEsign($object->id, $object->element, $user, $langs);
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			}
			case 'ORDER_UNVALIDATE':
			{
				$result = $this->cancelDoliEsign($object->id, $object->element, $user, $langs);
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			}
			//case 'LINEORDER_INSERT':
			//case 'LINEORDER_UPDATE':
			//case 'LINEORDER_DELETE':

			// Supplier orders
			//case 'ORDER_SUPPLIER_CREATE':
			//case 'ORDER_SUPPLIER_CLONE':
			//case 'ORDER_SUPPLIER_VALIDATE':
			//case 'ORDER_SUPPLIER_DELETE':
			//case 'ORDER_SUPPLIER_APPROVE':
			//case 'ORDER_SUPPLIER_REFUSE':
			//case 'ORDER_SUPPLIER_CANCEL':
			//case 'ORDER_SUPPLIER_SENTBYMAIL':
			//case 'ORDER_SUPPLIER_DISPATCH':
			//case 'LINEORDER_SUPPLIER_DISPATCH':
			//case 'LINEORDER_SUPPLIER_CREATE':
			//case 'LINEORDER_SUPPLIER_UPDATE':

			// Proposals
			//case 'PROPAL_CREATE':
			//case 'PROPAL_CLONE':
			case 'PROPAL_MODIFY':
			{
				$result = $this->cancelDoliEsign($object->id, $object->element, $user, $langs);
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			}
			//case 'PROPAL_VALIDATE':
			//case 'PROPAL_SENTBYMAIL':
			//case 'PROPAL_CLOSE_SIGNED':
			case 'PROPAL_CLOSE_REFUSED':
			{
				$result = $this->cancelDoliEsign($object->id, $object->element, $user, $langs);
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			}
			case 'PROPAL_DELETE':
			{
				$result = $this->cancelDoliEsign($object->id, $object->element, $user, $langs);
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			}
			//case 'LINEPROPAL_INSERT':
			//case 'LINEPROPAL_UPDATE':
			//case 'LINEPROPAL_DELETE':

			// SupplierProposal
			//case 'SUPPLIER_PROPOSAL_CREATE':
			//case 'SUPPLIER_PROPOSAL_CLONE':
			//case 'SUPPLIER_PROPOSAL_MODIFY':
			//case 'SUPPLIER_PROPOSAL_VALIDATE':
			//case 'SUPPLIER_PROPOSAL_SENTBYMAIL':
			//case 'SUPPLIER_PROPOSAL_CLOSE_SIGNED':
			//case 'SUPPLIER_PROPOSAL_CLOSE_REFUSED':
			//case 'SUPPLIER_PROPOSAL_DELETE':
			//case 'LINESUPPLIER_PROPOSAL_INSERT':
			//case 'LINESUPPLIER_PROPOSAL_UPDATE':
			//case 'LINESUPPLIER_PROPOSAL_DELETE':

			// Contracts
			//case 'CONTRACT_CREATE':
			//case 'CONTRACT_ACTIVATE':
			//case 'CONTRACT_CANCEL':
			//case 'CONTRACT_CLOSE':
			//case 'CONTRACT_DELETE':
			//case 'LINECONTRACT_INSERT':
			//case 'LINECONTRACT_UPDATE':
			//case 'LINECONTRACT_DELETE':

			// Bills
			//case 'BILL_CREATE':
			//case 'BILL_CLONE':
			//case 'BILL_MODIFY':
			//case 'BILL_VALIDATE':
			//case 'BILL_UNVALIDATE':
			//case 'BILL_SENTBYMAIL':
			//case 'BILL_CANCEL':
			//case 'BILL_DELETE':
			//case 'BILL_PAYED':
			//case 'LINEBILL_INSERT':
			//case 'LINEBILL_UPDATE':
			//case 'LINEBILL_DELETE':

			//Supplier Bill
			//case 'BILL_SUPPLIER_CREATE':
			//case 'BILL_SUPPLIER_UPDATE':
			//case 'BILL_SUPPLIER_DELETE':
			//case 'BILL_SUPPLIER_PAYED':
			//case 'BILL_SUPPLIER_UNPAYED':
			//case 'BILL_SUPPLIER_VALIDATE':
			//case 'BILL_SUPPLIER_UNVALIDATE':
			//case 'LINEBILL_SUPPLIER_CREATE':
			//case 'LINEBILL_SUPPLIER_UPDATE':
			//case 'LINEBILL_SUPPLIER_DELETE':

			// Payments
			//case 'PAYMENT_CUSTOMER_CREATE':
			//case 'PAYMENT_SUPPLIER_CREATE':
			//case 'PAYMENT_ADD_TO_BANK':
			//case 'PAYMENT_DELETE':

			// Online
			//case 'PAYMENT_PAYBOX_OK':
			//case 'PAYMENT_PAYPAL_OK':
			//case 'PAYMENT_STRIPE_OK':

			// Donation
			//case 'DON_CREATE':
			//case 'DON_UPDATE':
			//case 'DON_DELETE':

			// Interventions
			//case 'FICHINTER_CREATE':
			case 'FICHINTER_MODIFY':
			{
				$result = $this->cancelDoliEsign($object->id, $object->element, $user, $langs);
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			}
			//case 'FICHINTER_VALIDATE':
			case 'FICHINTER_DELETE':
			{
				$result = $this->cancelDoliEsign($object->id, $object->element, $user, $langs);
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			}
			//case 'LINEFICHINTER_CREATE':
			//case 'LINEFICHINTER_UPDATE':
			//case 'LINEFICHINTER_DELETE':

			// Members
			//case 'MEMBER_CREATE':
			//case 'MEMBER_VALIDATE':
			//case 'MEMBER_SUBSCRIPTION':
			//case 'MEMBER_MODIFY':
			//case 'MEMBER_NEW_PASSWORD':
			//case 'MEMBER_RESILIATE':
			//case 'MEMBER_DELETE':

			// Categories
			//case 'CATEGORY_CREATE':
			//case 'CATEGORY_MODIFY':
			//case 'CATEGORY_DELETE':
			//case 'CATEGORY_SET_MULTILANGS':

			// Projects
			//case 'PROJECT_CREATE':
			//case 'PROJECT_MODIFY':
			//case 'PROJECT_DELETE':

			// Project tasks
			//case 'TASK_CREATE':
			//case 'TASK_MODIFY':
			//case 'TASK_DELETE':

			// Task time spent
			//case 'TASK_TIMESPENT_CREATE':
			//case 'TASK_TIMESPENT_MODIFY':
			//case 'TASK_TIMESPENT_DELETE':

			// Shipping
			//case 'SHIPPING_CREATE':
			//case 'SHIPPING_MODIFY':
			//case 'SHIPPING_VALIDATE':
			//case 'SHIPPING_SENTBYMAIL':
			//case 'SHIPPING_BILLED':
			//case 'SHIPPING_CLOSED':
			//case 'SHIPPING_REOPEN':
			//case 'SHIPPING_DELETE':
			case 'UNIVERSIGN_CREATE':
			{
			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("DoliesignSentByEMail","");
            if (empty($object->actionmsg))  $object->actionmsg=$langs->transnoentities("DoliesignSentByEMail","");
		$result = $this->createEvent($action, $object,$user,$langs,$conf);
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			}
	//		case 'UNIVERSIGN_MODIFY':
	//		{
	//		if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("DoliesignUpdate","");
    //        if (empty($object->actionmsg))  $object->actionmsg=$langs->transnoentities("DoliesignUpdate","");
    //        	$result = $this->createEvent($action, $object,$user,$langs,$conf);
	//			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
	//			break;
	//		}
			case 'DOLIESIGN_DELETE':
			{
			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("DoliesignDelete","");
            if (empty($object->actionmsg))  $object->actionmsg=$langs->transnoentities("DoliesignDelete","");
		$result = $this->createEvent($action, $object,$user,$langs,$conf);
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			}
			default:
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			}

		return $result;
	}

	/**
	 * Cancel a signing if signing status is waiting
	 *
	 * @param	int	$objectId	id of dolibarr object to cancel
	 *
	 * @return int -1 NOK, 1 Cancelled, 0 not cancelled
	 */
	private function cancelDoliEsign($objectId, $objectType, $user, $langs) {
		global $conf;

		dol_include_once('/doliesign/lib/doliesign.lib.php');
		if (!empty($objectId)) {
			if (! empty($conf->global->DOLIESIGN_ENVIRONMENT)) $environment = $conf->global->DOLIESIGN_ENVIRONMENT;
			if ($environment == 'yousign-staging-api' || $environment == 'yousign-api') {
				dol_include_once('/doliesign/class/yousignrest.class.php');
				$doliEsign = new YousignRest($this->db);
			}  elseif ($environment == 'universign-prod' || $environment == 'universign-demo'){
				dol_include_once('/doliesign/class/universign.class.php');
				$doliEsign = new Universign($this->db);
			} else {
				dol_include_once('/doliesign/class/yousignsoap.class.php');
				$doliEsign = new YousignSoap($this->db);
			}
			$result = $doliEsign->fetch(null, null, $objectId, $objectType);
			if ($result > 0) {
				$signStatus = $doliEsign->status;
				if ($signStatus == DoliEsign::STATUS_WAITING) {
					$res = $doliEsign->signCancel($user);
					if ($res < 0) {
						if (!empty($doliEsign->$errors)) $this->errors=$doliEsign->$errors;
						if (DoliEsign::checkDolVersion('7.0')) {
							return -1;
						} else {
							// workaround for system error iso normal error
							setEventMessages('', $this->errors, 'errors');
							return 1;
						}
					} else {
						setEventMessages($langs->trans('DoliEsignCanceled'), null , 'warnings');
						return 1;
					}
				}
			}
		} else {
			return 0;
		}
	}
	/**
	 * Create a event
	 *
	 *
	 * @return int -1 NOK, 1 OK
	 */

	 private function createEvent($action, $object, User $user, Translate $langs, Conf $conf)
	 {
		 $langs->load("doliesign@doliesign");
		 // Add entry in event table
		$now=dol_now();

		if (isset($_SESSION['listofnames-'.$object->trackid]))
		{
			$attachs=$_SESSION['listofnames-'.$object->trackid];
			if ($attachs && strpos($action,'SENTBYMAIL'))
			{
                $object->actionmsg=dol_concatdesc($object->actionmsg, "\n".$langs->transnoentities("AttachedFiles").': '.$attachs);
			}
		}

        require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		$contactforaction=new Contact($this->db);
        $societeforaction=new Societe($this->db);
        // Set contactforaction if there is only 1 contact.
        if (is_array($object->sendtoid))
        {
            if (count($object->sendtoid) == 1) $contactforaction->fetch(reset($object->sendtoid));
        }
        else
        {
            if ($object->sendtoid > 0) $contactforaction->fetch($object->sendtoid);
        }
        // Set societeforaction.
        if ($object->socid > 0)    $societeforaction->fetch($object->socid);

        $projectid = isset($object->fk_project)?$object->fk_project:0;
        if ($object->element == 'project') $projectid = $object->id;

		// Insertion action
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($this->db);
		$actioncomm->type_code   = "AC_OTH_AUTO";		// Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
		$actioncomm->code        = 'AC_'.$action;
		$actioncomm->label       = $object->actionmsg2;
		$actioncomm->note        = $object->actionmsg;          // TODO Replace with $actioncomm->email_msgid ? $object->email_content : $object->actionmsg
		$actioncomm->fk_project  = $projectid;
		$actioncomm->datep       = $now;
		$actioncomm->datef       = $now;
		$actioncomm->durationp   = 0;
		$actioncomm->punctual    = 1;
		$actioncomm->percentage  = -1;   // Not applicable
		$actioncomm->societe     = $societeforaction;
		$actioncomm->contact     = $contactforaction;
		$actioncomm->socid       = $societeforaction->id;
		$actioncomm->contactid   = $contactforaction->id;
		$actioncomm->authorid    = $user->id;   // User saving action
		$actioncomm->userownerid = $user->id;	// Owner of action
        // Fields when action is en email (content should be added into note)
		$actioncomm->email_msgid = $object->email_msgid;
		$actioncomm->email_from  = $object->email_from;
		$actioncomm->email_sender= $object->email_sender;
		$actioncomm->email_to    = $object->email_to;
		$actioncomm->email_tocc  = $object->email_tocc;
		$actioncomm->email_tobcc = $object->email_tobcc;
		$actioncomm->email_subject = $object->email_subject;
		$actioncomm->errors_to   = $object->errors_to;

		$actioncomm->fk_element  = $object->fk_object;
		$actioncomm->elementtype = $object->object_type;

		$ret=$actioncomm->create($user);       // User creating action

		if ($ret > 0 && $conf->global->MAIN_COPY_FILE_IN_EVENT_AUTO)
		{
			if (is_array($object->attachedfiles) && array_key_exists('paths',$object->attachedfiles) && count($object->attachedfiles['paths'])>0) {
				foreach($object->attachedfiles['paths'] as $key=>$filespath) {
					$srcfile = $filespath;
					$destdir = $conf->agenda->dir_output . '/' . $ret;
					$destfile = $destdir . '/' . $object->attachedfiles['names'][$key];
					if (dol_mkdir($destdir) >= 0) {
						require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
						dol_copy($srcfile, $destfile);
					}
				}
			}
		}

		unset($object->actionmsg); unset($object->actionmsg2); unset($object->actiontypecode);	// When several action are called on same object, we must be sure to not reuse value of first action.

		if ($ret > 0)
		{
			$_SESSION['LAST_ACTION_CREATED'] = $ret;
			return 1;
		}
		else
		{
            $error ="Failed to insert event : ".$actioncomm->error." ".join(',',$actioncomm->errors);
            $this->error=$error;
            $this->errors=$actioncomm->errors;

            dol_syslog("interface_modAgenda_ActionsAuto.class.php: ".$this->error, LOG_ERR);
            return -1;
		}
	 }

}
