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
$id=GETPOST('id');
$user_ = new User($db);
$vehicule->fetch($id);
$user_->fetch($vehicule->conducteur);
$name=$user_->firstname.' '.$user_->lastname;
$unite=$vehicule->unite;
if($unite == 'kilometers'){
	$unite='Kilomètres';
}else
$unite=$langs->trans($vehicule->unite);

$data=['name'=>$name,'unite'=> $unite];
echo json_encode($data);
?>
