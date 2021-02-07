<?php
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");       // For root directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php"); // For "custom" 
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
dol_include_once('/core/class/html.form.class.php');



$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("parcautomobile");
$vehicule  = new vehiculeparc($db);
$id_item=GETPOST('id_item');
$id_etat=GETPOST('id_etat');

// $vehicule->fetch($id);
$test = $vehicule->update($id_item,['statut'=>$id_etat]);
echo $test;
?>
