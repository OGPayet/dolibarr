<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013       Jean-François FERRY    <jfefe@aternatik.fr>
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
 *       \file       ticketsup/webservices/demo_ticketsup.php
 *       \brief      Demo page to make a client call to Dolibarr WebServices "ticketsup"
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'] . '/htdocs');

$res = '';
$res = @require_once "../../master.inc.php";
if (!$res) {
    include_once "../../../master.inc.php";
}

require_once NUSOAP_PATH . '/nusoap.php'; // Include SOAP

$WS_DOL_URL = DOL_MAIN_URL_ROOT . '/custom/ticketsup/webservices/ws_server_ticketsup.php';

$user_ws = 'demo';
$pass_ws = 'demo';

$WS_METHOD = 'getTicket';

// Set the WebService URL
dol_syslog("Create nusoap_client for URL=" . $WS_DOL_URL);
$soapclient = new nusoap_client($WS_DOL_URL);
if ($soapclient) {
    $soapclient->soap_defencoding = 'UTF-8';
}

// Call the WebService method and store its result in $result.
$authentication = array(
    'dolibarrkey' => $conf->global->WEBSERVICES_KEY,
    'sourceapplication' => 'SPIP',
    'login' => $user_ws,
    'password' => $pass_ws,
    'entity' => '');
$parameters = array('authentication' => $authentication, 'id' => 1);
dol_syslog("Call method " . $WS_METHOD);
$result = $soapclient->call($WS_METHOD, $parameters);

if (!$result) {
    print $soapclient->error_str;
    exit;
}

/*
 * View
 */

header("Content-type: text/html; charset=utf8");
print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">' . "\n";
echo '<html>' . "\n";
echo '<head>';
echo '<title>WebService Test: ' . $WS_METHOD . '</title>';
echo '</head>' . "\n";

echo '<body>' . "\n";

echo "<h2>Request:</h2>";
echo '<h4>Function</h4>';
echo $WS_METHOD;
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars($soapclient->request, ENT_QUOTES) . '</pre>';

echo '<hr>';

echo "<h2>Response:</h2>";
echo '<h4>Result</h4>';
echo '<pre>';
print_r($result);
echo '</pre>';
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars($soapclient->response, ENT_QUOTES) . '</pre>';

echo '<hr />';

$WS_METHOD = 'getTickets';

// Set the WebService URL
dol_syslog("Create nusoap_client for URL=" . $WS_DOL_URL);
$soapclient = new nusoap_client($WS_DOL_URL);
if ($soapclient) {
    $soapclient->soap_defencoding = 'UTF-8';
}

// Call the WebService method and store its result in $result.
$authentication = array(
    'dolibarrkey' => $conf->global->WEBSERVICES_KEY,
    'sourceapplication' => 'SPIP',
    'login' => $user_ws,
    'password' => $pass_ws,
    'entity' => '');
$parameters = array('authentication' => $authentication, 'socid' => 43);
dol_syslog("Call method " . $WS_METHOD);
$result = $soapclient->call($WS_METHOD, $parameters);

if (!$result) {
    print $soapclient->error_str;
    exit;
}

/*
 * View
 */
echo "<h2>Request:</h2>";
echo '<h4>Function</h4>';
echo $WS_METHOD;
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars($soapclient->request, ENT_QUOTES) . '</pre>';

echo '<hr>';

echo "<h2>Response:</h2>";
echo '<h4>Result</h4>';
echo '<pre>';
print_r($result);
echo '</pre>';
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars($soapclient->response, ENT_QUOTES) . '</pre>';

echo "<hr />";

$WS_METHOD = 'getClassifications';

// Set the WebService URL
dol_syslog("Create nusoap_client for URL=" . $WS_DOL_URL);
$soapclient = new nusoap_client($WS_DOL_URL);
if ($soapclient) {
    $soapclient->soap_defencoding = 'UTF-8';
}

// Call the WebService method and store its result in $result.
$authentication = array(
    'dolibarrkey' => $conf->global->WEBSERVICES_KEY,
    'sourceapplication' => 'SPIP',
    'login' => $user_ws,
    'password' => $pass_ws,
    'entity' => '');
$parameters = array('authentication' => $authentication, 'socid' => 43);
dol_syslog("Call method " . $WS_METHOD);
$result = $soapclient->call($WS_METHOD, $parameters);

if (!$result) {
    print $soapclient->error_str;
    exit;
}

/*
 * View
 */
echo "<h2>Request:</h2>";
echo '<h4>Function</h4>';
echo $WS_METHOD;
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars($soapclient->request, ENT_QUOTES) . '</pre>';

echo '<hr>';

echo "<h2>Response:</h2>";
echo '<h4>Result</h4>';
echo '<pre>';
print_r($result);
echo '</pre>';
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars($soapclient->response, ENT_QUOTES) . '</pre>';

echo '</body>' . "\n";
echo '</html>' . "\n";
