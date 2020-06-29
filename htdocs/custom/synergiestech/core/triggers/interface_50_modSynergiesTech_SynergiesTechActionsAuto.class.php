<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013	   Cedric GROSS         <c.gross@kreiz-it.fr>
 * Copyright (C) 2014       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2015       Bahfir Abbes        <bafbes@gmail.com>
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
 *	\file       htdocs/core/triggers/interface_50_modAgenda_ActionsAuto.class.php
 *  \ingroup    agenda
 *  \brief      Trigger file for agenda module
 */

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggered functions for agenda module
 */
class InterfaceSynergiesTechActionsAuto extends DolibarrTriggers
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
        $this->family = "synergiestech";
        $this->description = "Triggers of the module Synergies-Tech..";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = self::VERSION_DOLIBARR;
        $this->picto = 'technic';
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
     * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
     *
     * Following properties may be set before calling trigger. The may be completed by this trigger to be used for writing the event into database:
     *      $object->actiontypecode (translation action code: AC_OTH, ...)
     *      $object->actionmsg (note, long text)
     *      $object->actionmsg2 (label, short text)
     *      $object->sendtoid (id of contact or array of ids)
     *      $object->socid (id of thirdparty)
     *      $object->fk_project
     *      $object->fk_element
     *      $object->elementtype
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
        if (empty($conf->synergiestech->enabled)) return 0;     // Module not active, we do nothing

        $key = 'MAIN_AGENDA_ACTIONAUTO_' . $action;

        // Do not log events not enabled for this action
        if (empty($conf->global->$key)) {
            return 0;
        }

        $langs->load("synergiestech@synergiestech");

        if (empty($object->actiontypecode)) $object->actiontypecode = 'AC_OTH_AUTO';

        switch($action){
            case 'PROPAL_CREATE':
                $langKey="PropalCreatedInDolibarr";
            break;
            case 'ORDER_CREATE':
                $langKey="OrderCreatedInDolibarr";
            break;
            case 'BILL_CREATE':
                $langKey="BillCreatedInDolibarr";
            break;
            case 'BILL_SUPPLIER_CREATE':
                $langKey="SupplierBillCreatedInDolibarr";
            break;
            case 'CONTRACT_CREATE':
                $langKey="ContractCreatedInDolibarr";
            break;
            case 'SHIPPING_CREATE':
                $langKey="ShippingCreatedInDolibarr";
            break;
            case 'SUPPLIER_PROPOSAL_CREATE':
                $langKey="SupplierProposalCreatedInDolibarr";
            break;
            default:
                $langKey=null;
        }
        if($langKey==null){
            //Action not managed by this trigger
            return 0;
        }




        // Actions
        if (empty($object->actionmsg2)) $object->actionmsg2 = $langs->transnoentities($langKey, $object->ref);
        $object->actionmsg = $langs->transnoentities($langKey, $object->ref);

        $object->sendtoid = 0;

        $object->actionmsg .= "\n" . $langs->transnoentities("Author") . ': ' . $user->login;

        dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

        // Add entry in event table
        $now = dol_now();

        if (isset($_SESSION['listofnames-' . $object->trackid])) {
            $attachs = $_SESSION['listofnames-' . $object->trackid];
            if ($attachs && strpos($action, 'SENTBYMAIL')) {
                $object->actionmsg = dol_concatdesc($object->actionmsg, "\n" . $langs->transnoentities("AttachedFiles") . ': ' . $attachs);
            }
        }

        require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
        $contactforaction = new Contact($this->db);
        $societeforaction = new Societe($this->db);
        // Set contactforaction if there is only 1 contact.
        if (is_array($object->sendtoid)) {
            if (count($object->sendtoid) == 1) $contactforaction->fetch(reset($object->sendtoid));
        } else {
            if ($object->sendtoid > 0) $contactforaction->fetch($object->sendtoid);
        }
        // Set societeforaction.
        if ($object->socid > 0)    $societeforaction->fetch($object->socid);

        $projectid = isset($object->fk_project) ? $object->fk_project : 0;
        if ($object->element == 'project') $projectid = $object->id;

        // Insertion action
        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $actioncomm = new ActionComm($this->db);
        $actioncomm->type_code   = $object->actiontypecode;        // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
        $actioncomm->code        = 'AC_' . $action;
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
        $actioncomm->userownerid = $user->id;    // Owner of action
        // Fields when action is en email (content should be added into note)
        $actioncomm->email_msgid = $object->email_msgid;
        $actioncomm->email_from  = $object->email_from;
        $actioncomm->email_sender = $object->email_sender;
        $actioncomm->email_to    = $object->email_to;
        $actioncomm->email_tocc  = $object->email_tocc;
        $actioncomm->email_tobcc = $object->email_tobcc;
        $actioncomm->email_subject = $object->email_subject;
        $actioncomm->errors_to   = $object->errors_to;

        $actioncomm->fk_element  = $object->id;
        $actioncomm->elementtype = $object->element;

        $ret = $actioncomm->create($user);       // User creating action

        if ($ret > 0 && $conf->global->MAIN_COPY_FILE_IN_EVENT_AUTO) {
            if (is_array($object->attachedfiles) && array_key_exists('paths', $object->attachedfiles) && count($object->attachedfiles['paths']) > 0) {
                foreach ($object->attachedfiles['paths'] as $key => $filespath) {
                    $srcfile = $filespath;
                    $destdir = $conf->agenda->dir_output . '/' . $ret;
                    $destfile = $destdir . '/' . $object->attachedfiles['names'][$key];
                    if (dol_mkdir($destdir) >= 0) {
                        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
                        dol_copy($srcfile, $destfile);
                    }
                }
            }
        }

        unset($object->actionmsg);
        unset($object->actionmsg2);
        unset($object->actiontypecode);    // When several action are called on same object, we must be sure to not reuse value of first action.

        if ($ret > 0) {
            $_SESSION['LAST_ACTION_CREATED'] = $ret;
            return 1;
        } else {
            $error = "Failed to insert event : " . $actioncomm->error . " " . join(',', $actioncomm->errors);
            $this->error = $error;
            $this->errors = $actioncomm->errors;

            dol_syslog("interface_modAgenda_ActionsAuto.class.php: " . $this->error, LOG_ERR);
            return -1;
        }
    }
}
