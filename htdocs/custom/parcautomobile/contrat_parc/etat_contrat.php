<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom"
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

dol_include_once('/parcautomobile/class/contrat_parc.class.php');
dol_include_once('/core/class/html.form.class.php');



$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("parcautomobile");
$contrat  = new contrat_parc($db);
$id_item=GETPOST('id_item');
$id_etat=GETPOST('id_etat');

// $vehicule->fetch($id);
$etat=GETPOST('etat');
$id=GETPOST('id');
if($etat =='renouveler'){
	$contrat->fetch($id);

	$date=explode('-', $contrat->date_fin);
	$y= trim($date[0]+1);
	$date_fin=$y.'-'.$date[1].'-'.$date[2];
	$contrat_new = [
	'ref_contrat'       =>  $contrat->ref_contrat,
        'vehicule'          =>  $contrat->vehicule_,
        'typecontrat'       =>  $contrat->typecontrat,
        'activation_couts'  =>  $contrat->activation_couts,
        'type_montant'      =>  $contrat->type_montant,
        'montant_recurrent' =>  $contrat->montant_recurrent,
        'date_facture'      =>  $contrat->date_facture,
        'date_fin'          =>  $contrat->date_fin,
        'date_debut'        =>  $contrat->date_debut,
        'etat'              =>  $contrat->etat,
        'responsable'       =>  $contrat->responsable,
        'fournisseur'       =>  $contrat->fournisseur,
        'conducteur'        =>  $contrat->conducteur,
        'condition'         =>  $contrat->conditions,
	];
        // $avance = $contrat->create(1,$contrat_new);


        $object = new contrat_parc($db);
        $object->fetch($id);

        $object->ref_contrat       =>  $contrat->ref_contrat;
        $object->vehicule          =>  $contrat->vehicule_;
        $object->typecontrat       =>  $contrat->type;
        $object->activation_couts  =>  $contrat->activation_cout;
        $object->type_montant      =>  $contrat->type_montant;
        $object->montant_recurrent =>  $contrat->montant_recurrent;
        $object->date_facture      =>  $contrat->date_facture;
        $object->date_fin          =>  $contrat->date_fin;
        $object->date_debut        =>  $contrat->date_debut;
        $object->etat              =>  $contrat->etape;
        $object->kilometrage       =>  $contrat->kilometrage;
        $object->responsable       =>  $contrat->responsable;
        $object->fournisseur       =>  $contrat->fournisseur;
        $object->conducteur        =>  $contrat->conducteur;
        $object->condition         =>  addslashes($contrat->conditions);
        $object->services_inclus   =>  addslashes($contrat->services_inclus);

        $ret = $extrafields->setOptionalsFromPost(null, $object);

        $avance = $object->create(1);



}
if($etat!='renouveler'){
	$test = $contrat->update($id,['etat'=>$etat]);
}
?>
