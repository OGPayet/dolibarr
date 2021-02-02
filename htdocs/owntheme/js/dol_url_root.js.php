<?php
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom"

global $dolibarr_main_url_root_alt;
$dircustom = DOL_DOCUMENT_ROOT.$dolibarr_main_url_root_alt.'/owntheme/';
$customtxt = $dolibarr_main_url_root_alt;
if (!is_dir($dircustom)) {
    $customtxt = "";
}

echo 'var dol_url_root = "'.DOL_MAIN_URL_ROOT.$customtxt.'";';
echo 'var customp = "'.$customtxt.'";';
?>
