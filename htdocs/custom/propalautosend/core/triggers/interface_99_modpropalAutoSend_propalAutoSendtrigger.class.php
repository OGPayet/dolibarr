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
 * 	\file		core/triggers/interface_99_modpropalautosend_propalAutoSendtrigger.class.php
 * 	\ingroup	propalautosend
 * 	\brief		Sample trigger
 * 	\remarks	You can create other triggers by copying this one
 * 				- File name should be either:
 * 					interface_99_modMymodule_Mytrigger.class.php
 * 					interface_99_all_Mytrigger.class.php
 * 				- The file must stay in core/triggers
 * 				- The class name must be InterfaceMytrigger
 * 				- The constructor method must be named InterfaceMytrigger
 * 				- The name property name must be Mytrigger
 */
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 * Trigger class
 */
class InterfacepropalAutoSendtrigger extends DolibarrTriggers
{

	protected $db;

    /**
     * Constructor
     *
     * 	@param		DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "demo";
        $this->description = "Triggers of this module are empty functions."
            . "They have no effect."
            . "They are provided for tutorial purpose only.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'development';
        $this->picto = 'propalautosend@propalautosend';
    }

    /**
     * Trigger name
     *
     * 	@return		string	Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * 	@return		string	Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Trigger version
     *
     * 	@return		string	Version of trigger file
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') {
            return $langs->trans("Development");
        } elseif ($this->version == 'experimental')

                return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else {
            return $langs->trans("Unknown");
        }
    }

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "run_trigger" are triggered if file
     * is inside directory core/triggers
     *
     * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if ($action == 'PROPAL_VALIDATE' && !empty($conf->global->PROPALAUTOSEND_CALCUL_DATE_ON_VALIDATION))
        {
            dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

			if (!empty($conf->global->PROPALAUTOSEND_DEFAULT_NB_DAY) && empty($object->array_options['options_date_relance']))
			{
				$object->array_options['options_date_relance'] = date('Y-m-d', strtotime('+'.(int) $conf->global->PROPALAUTOSEND_DEFAULT_NB_DAY.' day'));
				if((float)DOL_VERSION < 7) $object->update_extrafields($user);
				else $object->insertExtrafields();
			}

        }
        if ($action == 'PROPAL_SENTBYMAIL' && !empty($conf->global->PROPALAUTOSEND_CALCUL_DATE_ON_EMAIL))
        {
		dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

		if (!empty($conf->global->PROPALAUTOSEND_DEFAULT_NB_DAY) && empty($object->array_options['options_date_relance']))
		{
			$object->array_options['options_date_relance'] = date('Y-m-d', strtotime('+'.(int) $conf->global->PROPALAUTOSEND_DEFAULT_NB_DAY.' day'));
			if((float)DOL_VERSION < 7) $object->update_extrafields($user);
				else $object->insertExtrafields();
		}

        }
        // Executed by cron job
        if ($action == 'PROPAL_AUTOSENDBYMAIL') {

		$langs->load("agenda");
		$langs->load("other");
		$langs->load("propal");

		if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ProposalSentByEMail",$object->ref);
		if (empty($object->actionmsg))
		{
			$object->actionmsg=$langs->transnoentities("ProposalSentByEMail",$object->ref);
			$object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
		}

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
		if ($object->sendtoid > 0) $contactforaction->fetch($object->sendtoid);
		if ($object->socid > 0)    $societeforaction->fetch($object->socid);

		// Insertion action
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($this->db);
		$actioncomm->type_code   = $object->actiontypecode;		// code of parent table llx_c_actioncomm (will be deprecated)
		$actioncomm->code        = 'AC_'.$action;
		$actioncomm->label       = $object->actionmsg2;
		$actioncomm->note        = $object->actionmsg;          // TODO Replace with $actioncomm->email_msgid ? $object->email_content : $object->actionmsg
		$actioncomm->fk_project  = isset($object->fk_project)?$object->fk_project:0;
		$actioncomm->datep       = $now;
		$actioncomm->datef       = $now;
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

		$actioncomm->fk_element  = $object->id;
		$actioncomm->elementtype = $object->element;

		$ret=$actioncomm->create($user);       // User creating action

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

			dol_syslog(__METHOD__.": ".$this->error, LOG_ERR);
			return -1;
		}

        }
        return 0;
    }
}