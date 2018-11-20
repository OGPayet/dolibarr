#!/usr/bin/env php
<?php

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__) . '/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ";
    echo $script_file;
    echo " from command line, you must use PHP for CLI mode.\n";
    exit;
}

if (! isset($argv[1]) || ! $argv[1]) {
    print "Usage: ".$script_file." userlogin [mailing_id] \n";
    exit;
}

// Global variables
$version = '1.0.0';
$error = 0;

/*
 * -------------------- YOUR CODE STARTS HERE --------------------
 */
/* Set this define to 0 if you want to allow execution of your script
 * even if dolibarr setup is "locked to admin user only". */
define('EVEN_IF_ONLY_LOGIN_ALLOWED', 0);

if(!empty($argv[3])) $company_code = $argv[3];

/* Include Dolibarr environment
 * Customize to your needs
 */
require_once $path . '../../../master.inc.php';
/* After this $db, $conf, $langs, $mysoc, $user and other Dolibarr utility variables should be defined.
 * Warning: this still requires a valid htdocs/conf.php file
 */

// No timeout for this script
@set_time_limit(0);

$langs->load("main");
$langs->load("mailchimp@mailchimp");
$langs->load("mails");


// Display banner and help
echo "***** " . $script_file . " (" . $version . ") *****\n";
if (! isset($argv[1]) || ! isset($argv[2])) {
    // Check parameters
    echo "Usage: " . $script_file . " userlogin [mailing_id] [company_code] \n";
    exit;
}

/* User and permissions loading
 * Loads user for login 'admin'.
 * Comment out to run as anonymous user. */
$userlogin = $argv[1];

$result = $user->fetch('', $userlogin);
if (! $result > 0) {
    dol_print_error('', $user->error);
    exit;
}
$user->getrights();

// Display banner and help
echo '--- start ' . dol_print_date(dol_now(),'dayhourtext')."\n";
echo 'userlogin=' . $userlogin . "\n";

// Examples for manipulating a class
dol_include_once('/mailchimp/class/dolmailchimp.class.php');
$mailchimp = new DolMailchimp($db);

if (isset($argv[2])) {
    /*echo 'list_id=' . $argv[2] . "\n";
    */

    $result=$mailchimp->fetch_by_mailing($argv[2]); // finalement c'est plus simple de passer par l'id campagne pour avoir celui de la liste
    echo 'campaign_id=' . $argv[2] . "\n";
    $list_id = $mailchimp->mailchimp_listid;

    echo 'list_id=' . $list_id . "\n";

    $mailchimp->listdest_lines[0]['id']=$list_id;

}
else {
    $mailchimp->getListDestinaries();
}

$mailchimp->debug = true;

foreach($mailchimp->listdest_lines as $destline) {

    $listid = $destline['id'];
    echo $listid."\n";

    $mailchimp->updateMembersStatusForList($listid);
    $mailchimp->updateMembersStatusForList($listid,'cleaned');

}

/*
 * --------------------- YOUR CODE ENDS HERE ----------------------
 */

print '--- end  ' . dol_print_date(dol_now(),'dayhourtext') . "\n";
// Error management
if (! $error) {
    // $db->commit();
    echo '--- end ok' . "\n";
    $exit_status = 0; // UNIX no errors exit status
} else {
    echo '--- end error code=' . $error . "\n";
    //$db->rollback();
    $exit_status = 1; // UNIX general error exit status
}

// Close database handler
$db->close();

// Return exit status code
return $exit_status;