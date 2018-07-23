<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018       Open-DSI            <support@open-dsi.fr>
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
 * \file    htdocs/requestmanager/class/requestmanagernotification.class.php
 * \ingroup requestmanager
 * \brief
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

/**
 * Class RequestManagerNotification
 *
 * Put here description of your class
 * @see CommonObject
 */
class RequestManagerNotification extends CommonObject
{
    public $element = 'requestmanager_notification';
    public $table_element = 'requestmanager_notification';
    protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    /**
     * Error message
     * @var string
     */
    public $error;

    /**
     * List of error message
     * @var array
     */
    public $errors;

    /**
     * ID of the notification
     * @var int
     */
    public $id;

    /**
     * ActionComm of the notification
     * @var int
     */
    public $fk_actioncomm;

    /**
     * User of the notification
     * @var int
     */
    public $fk_user;

    /**
     * Status of the notification
     * @var int (0=not read, 1=read)
     */
    public $status;

    /**
     * Contact list
     * @var array
     */
    public $contactList = array();

    /**
     * Contact copy carbone list
     * @var array
     */
    public $contactCcList = array();

    /**
     * Contact blind copy carbone list
     * @var array
     */
    public $contactBccList = array();


    /**
     * Status constants
     */
    const STATUS_NOT_READ = 0;
    const STATUS_READ = 1;


    /**
     * Constructor
     *
     * @param   DoliDb      $db     Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }


    /**
     * Create a notification in database
     *
     * @param   int     $fkActionComm       Id of ActionComm
     * @param   int     $fkUser             Id of user
     * @return  int     <0 if KO, >0 if OK
     */
    public function create($fkActionComm, $fkUser)
    {
        $status = self::STATUS_NOT_READ;

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
        $sql .= " fk_actioncomm, fk_user, status";
        $sql .= " ) VALUES (";
        $sql .= $fkActionComm . ", " . $fkUser . ", " . $status;
        $sql .= ")";

        $resql = $this->db->query($sql);

        if (!$resql) {
            dol_syslog( __METHOD__ . " Error sql=" . $sql, LOG_ERR);
            return -1;
        }

        return 1;
    }


    /**
     * Update a notification in database
     *
     * @return  int     <0 if KO, >0 if OK
     */
    public function update()
    {
        return 1;
    }


    /**
     * Save a notification in database
     *
     * @return  int     <0 if KO, >0 if OK
     */
    public function save()
    {
        if ($this->id > 0) {

        } else {

        }

        return 1;
    }


    /**
     * Notify all contact list
     *
     * @param   int     $fkActionComm        Id of ActionComm
     * @return  int     <0 if KO, >0 if OK
     */
    public function notify($fkActionComm)
    {
        $contactList = $this->contactList;

        foreach($contactList as $key => $contact)
        {
            // only users
            if ($key[0] == 'u') {

                // create notification
                $res = $this->create($fkActionComm, $contact->id);

                if (!$res) {
                    return -1;
                }
            }
        }

        return 1;
    }


    /**
     * Get the sender of the message
     *
     * @return  string   From contact
     */
    private static function _getSendFrom()
    {
        global $conf;

        $sendFrom = (!empty($conf->global->MAIN_MAIL_EMAIL_FROM) ? $conf->global->MAIN_MAIL_EMAIL_FROM : '');

        return $sendFrom;
    }


    /**
     * Make contact email unique list
     *
     * @param   array   $contactList        Contact list
     * @return  array   Contact unique email list
     */
    private static function _makeEmailUniqueListFromContactList($contactList)
    {
        $emailUniqueList = array();

        // unique email list
        foreach ($contactList as $contact) {
            if ($contact->email && !in_array($contact->email, $emailUniqueList)) {
                $emailUniqueList[] = $contact->email;
            }
        }

        return $emailUniqueList;
    }


    /**
     * Make a contact string for recipients of the message
     *
     * @param   array       $contactList        Contact list
     * @return  string      Contact string for recipients of the message
     */
    private static function _makeEmailUniqueStringFromContactList($contactList)
    {
        $emailUniqueString = '';

        $emailUniqueList = self::_makeEmailUniqueListFromContactList($contactList);
        if (count($emailUniqueList) > 0) {
            $emailUniqueString = implode(', ', $emailUniqueList);
        }

        return $emailUniqueString;
    }


    /**
     * Notify all contact by email
     *
     * @param   RequestManager      $requestManager     RequestManager object
     * @param   string              $templateType       Template type
     * @return  int                 <0 if KO, >0 if OK
     */
    public function notifyByMailFromTemplateType($requestManager, $templateType)
    {
        dol_include_once('/requestmanager/class/html.formrequestmanagermessage.class.php');

        $resql = $requestManager->findNotificationEmailTemplate($templateType);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            return -1;
        }

        if ($this->db->num_rows($resql) > 0) {
            $formRequestManagerMessage = new FormRequestManagerMessage($this->db, $requestManager);
            $substitutionarray = $formRequestManagerMessage->getAvailableSubstitKey($requestManager);

            $obj = $this->db->fetch_object($resql);
            $subject = make_substitutions($obj->subject, $substitutionarray);
            $message = make_substitutions($obj->boby, $substitutionarray);

            // send mail
            $result = $this->_mailSend($subject, $message, 1);

            return $result;
        } else {
            dol_syslog( __METHOD__ . " Request[" . $requestManager->id . "] : No message template in dictionnary configuration for this type of request", LOG_DEBUG);
        }

        return 1;
    }


    /**
     * Notify by mail for input and output messages
     *
     * @param   string      $subject            Subject of message
     * @param   string      $message            Message content
     * @return  int         <0 if KO, >0 if OK
     */
    public function notifyByMailForMessageInAndOut($subject, $message)
    {
        $result = $this->_mailSend($subject, $message, 1);

        return $result;
    }


    /**
     * Send a mail
     *
     * @param   string      sendFrom    Sender of the maim
     * @param   string      $sendTo     Receipient of the mail
     * @param   string      $subject    Subject of the mail
     * @param   string      $message    [=''] Message of the mail (HTML format)
     * @param   int         $isHtml     [=-1] plain text or HTML message
     * @return  int         <0 if KO, >0 if OK
     */
    private function _mailSend($subject, $message = '', $isHtml = -1)
    {
        // from
        $sendFrom = self::_getSendFrom();
        if (!$sendFrom) {
            $this->error = "No paramater sendFrom";
            dol_syslog(__METHOD__ . " Error : no parameter sendFrom", LOG_ERR);
            return -1;
        }

        // send to (with unique email)
        $sendTo = self::_makeEmailUniqueStringFromContactList($this->contactList);
        if (!$sendTo) {
            dol_syslog( __METHOD__ . " Nobody to notify by mail", LOG_ERR);
            return -1;
        }

        // subject
        if (!$subject) {
            $this->error = "No parameter subject";
            dol_syslog(__METHOD__ . " Error : no parameter subject", LOG_ERR);
            return -1;
        }

        // copy carbone (with unique email)
        $addrCc = self::_makeEmailUniqueStringFromContactList($this->contactCcList);

        // blind copy carbone (with unique email)
        $addrBcc = self::_makeEmailUniqueStringFromContactList($this->contactBccList);

        $cMailFile = new CMailFile($subject, $sendTo, $sendFrom, $message, array(), array(), array(), $addrCc, $addrBcc, 0, $isHtml);
        $result = $cMailFile->sendfile();

        if (!$result) {
            $this->error = $cMailFile->error;
            dol_syslog(__METHOD__ . " Error : mail not sent", LOG_ERR);
            return -1;
        } else {
            dol_syslog(__METHOD__ . " mail sent", LOG_DEBUG);
            return 1;
        }
    }
}