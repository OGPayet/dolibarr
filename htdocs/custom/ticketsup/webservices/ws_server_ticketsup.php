<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013-2016 Jean-François FERRY    <jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       ticketsup/webservices/server_ticketsup.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'] . '/htdocs');

$res = '';
$res = @require_once "../../master.inc.php";
if (!$res) {
    @include_once "../../../master.inc.php";
}

require_once NUSOAP_PATH . '/nusoap.php'; // Include SOAP
require_once DOL_DOCUMENT_ROOT . "/core/lib/ws.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/functions.lib.php";
require_once DOL_DOCUMENT_ROOT . "/user/class/user.class.php";
dol_include_once("/ticketsup/class/ticketsup.class.php");
dol_include_once("/ticketsup/lib/ticketsup.lib.php");

dol_syslog("Call Ticket webservices interfaces");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES)) {
    $langs->load("admin");
    dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
    print $langs->trans("WarningModuleNotActive", 'WebServices') . '.<br><br>';
    print $langs->trans("ToActivateModule");
    exit;
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding = 'UTF-8';
$server->decode_utf8=false;
$ns = 'http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrTicket', $ns);
$server->wsdl->schemaTargetNamespace = $ns;

// Define WSDL content
$server->wsdl->addComplexType(
    'authentication',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'dolibarrkey' => array('name' => 'dolibarrkey', 'type' => 'xsd:string'),
        'sourceapplication' => array('name' => 'sourceapplication', 'type' => 'xsd:string'),
        'login' => array('name' => 'login', 'type' => 'xsd:string'),
        'password' => array('name' => 'password', 'type' => 'xsd:string'),
        'entity' => array('name' => 'entity', 'type' => 'xsd:string'),
    )
);

    $server->wsdl->addComplexType(
        'Ticket',
        'complexType',
        'struct',
        'all',
        '',
        array(
        'element' => array('name' => 'element', 'type' => 'xsd:string'),
        'id' => array('name' => 'id', 'type' => 'xsd:string'),
        'ref' => array('name' => 'ref', 'type' => 'xsd:string'),
        'track_id' => array('name' => 'track_id', 'type' => 'xsd:string'),
        'fk_soc' => array('name' => 'fk_soc', 'type' => 'xsd:string'),
        'fk_user_create' => array('name' => 'fk_user_create', 'type' => 'xsd:string'),
        'fk_user_create_string' => array('name' => 'fk_user_create_string', 'type' => 'xsd:string'),
        'fk_user_assign' => array('name' => 'fk_user_assign', 'type' => 'xsd:string'),
        'fk_user_assign_string' => array('name' => 'fk_user_assign_string', 'type' => 'xsd:string'),
        'subject' => array('name' => 'subject', 'type' => 'xsd:string'),
        'message' => array('name' => 'message', 'type' => 'xsd:string'),
        'origin_email' => array('name' => 'origin_email', 'type' => 'xsd:string'),
        'fk_statut' => array('name' => 'fk_statut', 'type' => 'xsd:string'),
        'statuts_short' => array('name' => 'statuts_short', 'type' => 'xsd:string'),
        'resolution' => array('name' => 'resolution', 'type' => 'xsd:string'),
        'progress' => array('name' => 'progress', 'type' => 'xsd:string'),
        'timing' => array('name' => 'timing', 'type' => 'xsd:string'),
        'type_code' => array('name' => 'type_code', 'type' => 'xsd:string'),
        'category_code' => array('name' => 'category_code', 'type' => 'xsd:string'),
        'severity_code' => array('name' => 'severity_code', 'type' => 'xsd:string'),
        //'' => array('name'=>'','type'=>'xsd:string'),
        'datec' => array('name' => 'datec', 'type' => 'xsd:string'),
        'date_read' => array('name' => 'date_read', 'type' => 'xsd:string'),
        'date_close' => array('name' => 'date_close', 'type' => 'xsd:string'),
        //'nbmsg_notread' => array('name'=>'nbmsg_notread','type'=>'xsd:string'),
        //'nbmsg_onticket' => array('name'=>'nbmsg_onticket','type'=>'xsd:string'),
        'messages' => array('name' => 'messages', 'type' => 'tns:MessagesArray'),
        'history' => array('name' => 'history', 'type' => 'tns:MessagesArray'),

        )
    );

    $server->wsdl->addComplexType(
        'Message',
        'complexType',
        'struct',
        'all',
        '',
        array(
        'id' => array('name' => 'id', 'type' => 'xsd:string'),
        'track_id' => array('name' => 'track_id', 'type' => 'xsd:string'),
        'fk_user_action' => array('name' => 'fk_user_action', 'type' => 'xsd:string'),
        'fk_user_action_string' => array('name' => 'fk_user_action_string', 'type' => 'xsd:string'),
        'message' => array('name' => 'message', 'type' => 'xsd:string'),
        'datec' => array('name' => 'datec', 'type' => 'xsd:string'),
        )
    );

    $server->wsdl->addComplexType(
        'TicketsArray',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Ticket[]'),
        ),
        'tns:Ticket'
    );

    $server->wsdl->addComplexType(
        'MessagesArray',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Message[]'),
        ),
        'tns:Message'
    );

    $server->wsdl->addComplexType(
        'ClassificationCodes',
        'complexType',
        'struct',
        'all',
        '',
        array(
        'type_code' => array('name' => 'type_code', 'type' => 'tns:ClassificationArray'),
        'category_code' => array('name' => 'category_code', 'type' => 'tns:ClassificationArray'),
        'severity_code' => array('name' => 'severity_code', 'type' => 'tns:ClassificationArray'),
        )
    );

    $server->wsdl->addComplexType(
        'ClassificationArray',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Classification[]'),
        ),
        'tns:Classification'
    );

    $server->wsdl->addComplexType(
        'Classification',
        'complexType',
        'struct',
        'all',
        '',
        array(
        'id' => array('name' => 'id', 'type' => 'xsd:string'),
        'COde' => array('name' => 'code', 'type' => 'xsd:string'),
        'label' => array('name' => 'label', 'type' => 'xsd:string'),
        'pos' => array('name' => 'pos', 'type' => 'xsd:string'),
        'use_default' => array('name' => 'use_default', 'type' => 'xsd:string'),
        )
    );

    $server->wsdl->addComplexType(
        'result',
        'complexType',
        'struct',
        'all',
        '',
        array(
        'result_code' => array('name' => 'result_code', 'type' => 'xsd:string'),
        'result_label' => array('name' => 'result_label', 'type' => 'xsd:string'),
        )
    );


    // 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
    // Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
    // http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
    $styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
    $styleuse='encoded';   // encoded/literal/literal wrapped
    // Better choice is document/literal wrapped but literal wrapped not supported by nusoap.


    // Register WSDL
    $server->register(
        'getTicket',
        // Entry values
        array('authentication' => 'tns:authentication', 'id' => 'xsd:string', 'track_id' => 'xsd:string'),
        // Exit values
        array('result' => 'tns:result', 'Ticket' => 'tns:Ticket'),
        $ns,
        $ns.'#getTicket',
        $styledoc,
        $styleuse,
        'WS to get a particular ticket'
    );

    // Register WSDL
    $server->register(
        'getTickets',
        // Entry values
        array('authentication' => 'tns:authentication', 'socid' => 'xsd:string'),
        // Exit values
        array('result' => 'tns:result', 'Tickets' => 'tns:TicketsArray'),
        $ns,
        $ns.'#getTickets',
        $styledoc,
        $styleuse,
        'WS to get a list of ticket'
    );

    // Register WSDL
    $server->register(
        'createTicket',
        // Entry values
        array('authentication' => 'tns:authentication', 'Ticket' => 'tns:Ticket'),
        // Exit values
        array('result' => 'tns:result', 'id' => 'xsd:string', 'track_id' => 'xsd:string'),
        $ns
    );

    // Register WSDL
    $server->register(
        'createMessageOnTicket',
        // Entry values
        array('authentication' => 'tns:authentication', 'Message' => 'tns:Message'),
        // Exit values
        array('result' => 'tns:result', 'id' => 'xsd:string', 'track_id' => 'xsd:string'),
        $ns,
        $ns.'#createMessageOnTicket',
        $styledoc,
        $styleuse,
        'WS to create a message on a ticket'
    );

    // Register WSDL
    $server->register(
        'getClassifications',
        // Entry values
        array('authentication' => 'tns:authentication'),
        // Exit values
        array('result' => 'tns:result', 'ClassificationCodes' => 'tns:ClassificationCodes'),
        $ns,
        $ns.'#getClassifications',
        $styledoc,
        $styleuse,
        'WS to get classifications'
    );

    // Full methods code
    function getTicket($authentication, $id, $track_id = '')
    {
        global $db, $conf, $langs;

        dol_syslog("Function: getTicket login=" . $authentication['login'] . " id=" . $id . " track_id=" . $track_id . " ref_ext=" . $ref_ext);

        if ($authentication['entity']) {
            $conf->entity = $authentication['entity'];
        }

        // Init and check authentication
        $objectresp = array();
        $errorcode = '';
        $errorlabel = '';
        $error = 0;
        $fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);

        // Check parameters
        if (!$error && (($id && $track_id))) {
            $error++;
            $errorcode = 'BAD_PARAMETERS';
            $errorlabel = "Parameter id and  track_id can't be both provided. You must choose one or other but not both.";
        }

        if (!$error) {
            $fuser->getrights();

            if ($fuser->rights->ticketsup->read) {
                $ticket = new Ticketsup($db);
                $result = $ticket->fetch($id, $track_id);
                if ($result > 0) {
                    if ($ticket->fk_user_assign > 0) {
                        $user_assign = new User($db);
                        $user_assign->fetch($ticket->fk_user_assign);
                    }
                    $user_create = new User($db);
                    $user_create->fetch($ticket->fk_user_create);
                    $resticket = array(
                    'element' => $ticket->element,
                    'id' => $ticket->id,
                    'ref' => $ticket->ref,
                    'track_id' => $ticket->track_id,
                    'fk_soc' => $ticket->fk_soc,
                    'fk_user_create' => $ticket->fk_user_create > 0 ? $ticket->fk_user_create : 0,
                    'fk_user_create_string' => dolGetFirstLastname($user_create->firstname, $user_create->lastname),
                    'fk_user_assign' => $ticket->fk_user_assign,
                    'fk_user_assign_string' => dolGetFirstLastname($user_assign->firstname, $user_assign->lastname),
                    'subject' => $ticket->subject,
                    'message' => $ticket->message,
                    'fk_statut' => $ticket->fk_statut,
                    'statuts_short' => $ticket->getLibStatut(0),
                    'fk_statut' => $ticket->fk_statut,
                    'resolution' => $ticket->resolution,
                    'progress' => $ticket->progress,
                    'timing' => $ticket->timing,
                    'type_code' => $ticket->type_code,
                    'category_code' => $ticket->category_code,
                    'severity_code' => $ticket->severity_code,

                    'datec' => $ticket->datec,
                    'date_read' => $ticket->date_read,
                    'date_close' => $ticket->date_close,
                    //'nbmsg_notread' => $ticket->nbmsg_notread,
                    //'nbmsg_onticket' => $ticket->nbmsg_onticket

                    );

                    // Messages du ticket
                    $messages = array();
                    $ticket->loadCacheMsgsTicket();
                    if (is_array($ticket->cache_msgs_ticket) && count($ticket->cache_msgs_ticket) > 0) {
                        $num = count($ticket->cache_msgs_ticket);
                        $i = 0;
                        while ($i < $num) {
                            if ($ticket->cache_msgs_ticket[$i]['fk_user_action'] > 0) {
                                $user_action = new User($db);
                                $user_action->fetch($ticket->cache_msgs_ticket[$i]['fk_user_action']);
                            }

                            // Now define messages
                            $messages[] = array(
                            'id' => $ticket->cache_msgs_ticket[$i]['id'],
                            'fk_user_action' => $ticket->cache_msgs_ticket[$i]['fk_user_action'],
                            'fk_user_action_string' => dolGetFirstLastname($user_action->firstname, $user_action->lastname),
                            'message' => $ticket->cache_msgs_ticket[$i]['message'],
                            'datec' => $ticket->cache_msgs_ticket[$i]['datec'],
                            );
                            $i++;
                        }
                        $resticket['messages'] = $messages;
                    }

                    // History
                    $history = array();
                    $ticket->loadCacheLogsTicket();
                    if (is_array($ticket->cache_logs_ticket) && count($ticket->cache_logs_ticket) > 0) {
                        $num = count($ticket->cache_logs_ticket);
                        $i = 0;
                        while ($i < $num) {
                            if ($ticket->cache_logs_ticket[$i]['fk_user_create'] > 0) {
                                $user_action = new User($db);
                                $user_action->fetch($ticket->cache_logs_ticket[$i]['fk_user_create']);
                            }

                            // Now define messages
                            $history[] = array(
                            'id' => $ticket->cache_logs_ticket[$i]['id'],
                            'fk_user_action' => $ticket->cache_logs_ticket[$i]['fk_user_create'],
                            'fk_user_action_string' => dolGetFirstLastname($user_action->firstname, $user_action->lastname),
                            'message' => $ticket->cache_logs_ticket[$i]['message'],
                            'datec' => $ticket->cache_logs_ticket[$i]['datec'],
                            );
                            $i++;
                        }
                        $resticket['history'] = $history;
                    }

                    // Create
                    $objectresp = array(
                    'result' => array('result_code' => 'OK', 'result_label' => ''),
                    'Ticket' => $resticket,
                    );
                } else {
                    $error++;
                    $errorcode = 'NOT_FOUND';
                    $errorlabel = 'Object not found for id=' . $id . ' nor track_id=' . $track_id;
                }
            } else {
                $error++;
                $errorcode = 'PERMISSION_DENIED';
                $errorlabel = 'User does not have permission for this request';
            }
        }

        if ($error) {
            $objectresp = array('result' => array('result_code' => $errorcode, 'result_label' => $errorlabel));
        }

        return $objectresp;
    }

    // Full methods code
    function getTickets($authentication, $socid = '')
    {
        global $db, $conf, $langs;

        dol_syslog("Function: getTickets login=" . $authentication['login'] . " socid=" . $socid);

        if ($authentication['entity']) {
            $conf->entity = $authentication['entity'];
        }

        // Init and check authentication
        $objectresp = array();
        $errorcode = '';
        $errorlabel = '';
        $error = 0;
        $fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);

        if ($fuser->societe_id) {
            $socid = $fuser->societe_id;
        }

        if (!$error) {
            $fuser->getrights();

            if ($fuser->rights->ticketsup->read) {
                $linesticket = array();

                $sql .= 'SELECT t.rowid, t.ref, t.track_id, t.fk_soc, t.fk_user_create,t.fk_user_assign,t.subject,t.message, t.fk_statut, resolution, progress, timing, type_code, category_code, severity_code
			, t.datec, t.date_read, t.date_close';
                $sql .= ' FROM ' . MAIN_DB_PREFIX . 'ticketsup as t';
                if ($socid > 0) {
                    $sql .= " WHERE t.fk_soc = " . $db->escape($socid);
                }

                $sql .= " ORDER BY datec DESC";
                dol_syslog("Function: getTickets " . $sql);
                $resql = $db->query($sql);
                if ($resql) {
                    $num = $db->num_rows($resql);
                    $i = 0;
                    while ($i < $num) {
                        $obj = $db->fetch_object($resql);

                        if ($obj->fk_user_assign > 0) {
                            $user_assign = new User($db);
                            $user_assign->fetch($obj->fk_user_assign);
                        }
                        if ($obj->fk_user_create > 0) {
                            $user_create = new User($db);
                            $user_create->fetch($obj->fk_user_create);
                        }

                        // Now define tickets
                        $linesticket[] = array(
                        'element' => $obj->element,
                        'id' => $obj->rowid,
                        'ref' => $obj->ref,
                        'track_id' => $obj->track_id,
                        'fk_soc' => $obj->fk_soc,
                        'fk_user_create' => $obj->fk_user_create,
                        'fk_user_create_string' => $user_create->firstname . ' ' . $user_create->lastname,
                        'fk_user_assign' => $obj->fk_user_assign,
                        'fk_user_assign_string' => $user_assign->firstname . ' ' . $user_assign->lastname,

                        'subject' => $obj->subject,
                        'message' => $obj->message,
                        'fk_statut' => $obj->fk_statut,
                        'statuts_short' => $obj->statuts_short[$obj->fk_statut],

                        'resolution' => $obj->resolution,
                        'progress' => $obj->progress,
                        'timing' => $obj->timing,
                        'type_code' => $obj->type_code,
                        'category_code' => $obj->category_code,
                        'severity_code' => $obj->severity_code,

                        'datec' => $obj->datec,
                        'date_read' => $obj->date_read,
                        'date_close' => $obj->date_close,

                        );
                        $i++;
                    }

                    //var_dump($linesticket);

                    $objectresp = array(
                    'result' => array('result_code' => 'OK', 'result_label' => ''),
                    'Tickets' => $linesticket,

                    );
                } else {
                    $error++;
                    $errorcode = $db->lasterrno();
                    $errorlabel = $db->lasterror();
                }
            } else {
                $error++;
                $errorcode = 'PERMISSION_DENIED';
                $errorlabel = 'User does not have permission for this request';
            }
        }

        if ($error) {
            $objectresp = array('result' => array('result_code' => $errorcode, 'result_label' => $errorlabel));
        }

        return $objectresp;
    }

    // Create ticket
    function createTicket($authentication, $ticket_array)
    {
        global $db, $conf, $langs;

        dol_syslog("Function: createTicket login=" . $authentication['login'] . " id=" . $id . " ref=" . $ref . " ref_ext=" . $ref_ext);

        if ($authentication['entity']) {
            $conf->entity = $authentication['entity'];
        }

        // Init and check authentication
        $objectresp = array();
        $errorcode = '';
        $errorlabel = '';
        $error = 0;
        $fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);

        if (!$error && !$ticket_array) {
            $error++;
            $errorcode = 'BAD_PARAMETERS';
            $errorlabel = "Parameter ticket_array must be provided.";
        }

        if (!$error) {
            $fuser->getrights();

            if ($fuser->rights->ticketsup->write) {
                /* Logguer les webservices
                ob_start();
                var_dump($ticket_array);
                $logging = ob_get_contents();
                ob_end_clean();
                dol_syslog('log :: '. $logging);
                 */
                $ticket = new Ticketsup($db);

                $ticket->ref = (!empty($ticket_array['ref']) ? $ticket_array['ref'] : $ticket->getDefaultRef());
                $ticket->track_id = $ticket_array['track_id'] ? $ticket_array['track_id'] : generate_random_id(16);
                $ticket->fk_soc = $ticket_array['fk_soc'];
                $ticket->fk_user_create = $fuser->id;
                $ticket->origin_email = $ticket_array['origin_email'];
                $ticket->fk_user_assign = '0';
                $ticket->subject = $ticket_array['subject'];
                $ticket->message = $ticket_array['message'];
                $ticket->origin_email = $ticket_array['origin_email'];
                $ticket->status = 0;
                $ticket->datec = dol_now();

                $ticket->resolution = $ticket_array['resolution'];
                $ticket->progress = $ticket_array['progress'];
                $ticket->timing = $ticket_array['timing'];
                $ticket->type_code = $ticket_array['type_code'];
                $ticket->category_code = $ticket_array['category_code'];
                $ticket->severity_code = $ticket_array['severity_code'];

                $ticketid = $ticket->create($fuser);
                if ($ticketid > 0) {
                    if (!$error) {
                        $objectresp = array(
                        'result' => array('result_code' => 'OK', 'result_label' => 'Success'),
                        'id' => $ticketid,
                        'track_id' => $ticket->track_id,
                        );
                        // retour Creation OK
                    }
                } else {
                    // retour creation KO
                    $error++;
                    $errorcode = 'NOT_CREATE';
                    $errorlabel = 'Object not create';
                }
            } else {
                $error++;
                $errorcode = 'PERMISSION_DENIED';
                $errorlabel = 'User does not have permission for this request';
            }
        }
        if ($error) {
            $objectresp = array(
            'result' => array('result_code' => $errorcode, 'result_label' => $errorlabel),
            );
        }

        return $objectresp;
    }

    // Create message on a ticket
    function createMessageOnTicket($authentication, $message_array)
    {
        global $db, $conf, $langs;

        dol_syslog("Function: createMessageOnTicket login=" . $authentication['login'] . " id=" . $id . " ref=" . $ref . " ref_ext=" . $ref_ext);

        if ($authentication['entity']) {
            $conf->entity = $authentication['entity'];
        }

        // Init and check authentication
        $objectresp = array();
        $errorcode = '';
        $errorlabel = '';
        $error = 0;
        $fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);

        if (!$error && !$message_array) {
            $error++;
            $errorcode = 'BAD_PARAMETERS';
            $errorlabel = "Parameter message_array must be provided.";
        }

        // Check parameters
        if (!$error && (empty($message_array['track_id']))) {
            $error++;
            $errorcode = 'BAD_PARAMETERS';
            $errorlabel = "Parameter track_id must be both provided.";
        }

        if (!$error) {
            $fuser->getrights();

            if ($fuser->rights->ticketsup->write) {
                /* Logguer les webservices
                ob_start();
                var_dump($ticket_array);
                $logging = ob_get_contents();
                ob_end_clean();
                dol_syslog('log :: '. $logging);
                 */
                $ticket = new Ticketsup($db);

                $res = $ticket->fetch('', $message_array['track_id']);
                if ($res) {
                    $ticket->message = $message_array['message'];

                    $ticketid = $ticket->createTicketMessage($fuser);
                    if ($ticketid > 0) {
                        if (!$error) {
                            $objectresp = array(
                            'result' => array('result_code' => 'OK', 'result_label' => 'Success'),
                            'id' => $ticket->id,
                            'track_id' => $ticket->track_id,
                            );
                            // retour Creation OK
                        }
                    } else {
                        // retour creation KO
                        $error++;
                        $errorcode = 'NOT_CREATE';
                        $errorlabel = 'Message not create';
                    }
                } else {
                    $error++;
                    $errorcode = 'NOT_FOUND';
                    $errorlabel = "Ticket not found";
                }
            } else {
                $error++;
                $errorcode = 'PERMISSION_DENIED';
                $errorlabel = 'User does not have permission for this request';
            }
        }
        if ($error) {
            $objectresp = array(
            'result' => array('result_code' => $errorcode, 'result_label' => $errorlabel),
            );
        }

        return $objectresp;
    }

    // Full methods code
    function getClassifications($authentication)
    {
        global $db, $conf, $langs;

        dol_syslog("Function: getClassifications login=" . $authentication['login']);

        if ($authentication['entity']) {
            $conf->entity = $authentication['entity'];
        }

        // Init and check authentication
        $objectresp = array();
        $errorcode = '';
        $errorlabel = '';
        $error = 0;
        $fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);

        if ($fuser->societe_id) {
            $socid = $fuser->societe_id;
        }

        if (!$error) {
            $fuser->getrights();

            if ($fuser->rights->ticketsup->read) {
                $linesclassification = array();

                $ticket = new Ticketsup($db);

                $ticket->load_cache_types_tickets();
                $linesclassification['type_code'] = $ticket->cache_types_tickets;

                $ticket->load_cache_categories_tickets();
                $linesclassification['category_code'] = $ticket->cache_category_tickets;

                $ticket->load_cache_severities_tickets();
                $linesclassification['severity_code'] = $ticket->cache_severity_tickets;

                $objectresp = array(
                'result' => array('result_code' => 'OK', 'result_label' => ''),
                'ClassificationCodes' => $linesclassification,
                );
            } else {
                $error++;
                $errorcode = 'PERMISSION_DENIED';
                $errorlabel = 'User does not have permission for this request';
            }
        }

        if ($error) {
            $objectresp = array('result' => array('result_code' => $errorcode, 'result_label' => $errorlabel));
        }

        return $objectresp;
    }

    // Return the results.
    $server->service(file_get_contents("php://input"));
