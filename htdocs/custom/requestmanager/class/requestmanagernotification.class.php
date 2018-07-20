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
     * @return  int     <0 if KO, >0 if OK
     */
    public function create()
    {
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
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "requestmanager_notification (";
                $sql .= " fk_actioncomm, fk_user, status";
                $sql .= " ) VALUES (";
                $sql .= $fkActionComm . ", " . $contact->id . ", " . self::STATUS_NOT_READ;
                $sql .= ")";

                $resql = $this->db->query($sql);

                if (!$resql) {
                    return -1;
                }
            }
        }

        return 1;
    }


    /**
     * Notify all contact by email
     *
     * @param   RequestManager      $requestManager
     * @return  int     <0 if KO, >0 if OK
     */
    public function notifyByMail($requestManager)
    {
        global $conf;

        dol_include_once('/requestmanager/class/html.formrequestmanagermessage.class.php');

        $contactList = $this->contactList;

        $formRequestManagerMessage = new FormRequestManagerMessage($this->db, $requestManager);
        $substitutionarray = $formRequestManagerMessage->getAvailableSubstitKey($requestManager);

        $sql  = "SELECT crmt.subject, crmt.boby";
        $sql .= " FROM " . MAIN_DB_PREFIX . "c_requestmanager_message_template crmt";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "c_requestmanager_message_template_cbl_request_type as crmtcrt ON crmtcrt.fk_line = crmt.rowid";
        $sql .= " WHERE crmt.template_type = 'notify_status_modified'";
        $sql .= " AND crmt.active = 1";
        $sql .= " AND crmt.entity = " . $conf->entity;
        $sql .= " AND crmtcrt.fk_target = " . $requestManager->fk_type;
        $sql .= " ORDER BY crmt.position ASC";
        $sql .= " LIMIT 1";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            return -1;
        }

        if ($this->db->num_rows() > 0) {
            $obj = $this->db->fetch_object($resql);
            $sendFrom = (!empty($conf->global->MAIN_MAIL_EMAIL_FROM) ? $conf->global->MAIN_MAIL_EMAIL_FROM : '');
            $subject = make_substitutions($obj->subject, $substitutionarray);
            $message = make_substitutions($obj->boby, $substitutionarray);

            // unique email list
            $contactEmailList = array();
            foreach ($contactList as $contact) {
                if ($contact->email && !in_array($contact->email, $contactEmailList)) {
                    $contactEmailList[] = $contact->email;
                }
            }

            // send mail
            if (count($contactEmailList) > 0) {
                $result = $this->mailSend($sendFrom, implode(", ", $contactEmailList), $subject, $message, 1);

                if ($result < 0) {
                    return -1;
                }
            }
        }

        return 1;
    }


    /**
     * Send a mail
     *
     * @param   string      sendFrom    Sender of the maim
     * @param   string      $sendTo     Receipient of the mail
     * @param   string      $subject    Subject of the mail
     * @param   string      $message    Message of the mail (HTML format)
     * @param   int         $isHtml     [=-1] plain text or HTML message
     * @return  int         <0 if KO, >0 if OK
     */
    public function mailSend($sendFrom, $sendTo, $subject, $message, $isHtml = -1)
    {
        $cMailFile = new CMailFile($subject, $sendTo, $sendFrom, $message, array(), array(), array(), '', '', 0, 1);
        $result = $cMailFile->sendfile();

        if (!$result) {
            $this->error = $cMailFile->error;
            dol_syslog( __CLASS__ . ":sendMail Error send email", LOG_ERR);
            return -1;
        } else {
            dol_syslog( __CLASS__ . ":sendMail email envoye", LOG_ERR);
            return 1;
        }
    }
}