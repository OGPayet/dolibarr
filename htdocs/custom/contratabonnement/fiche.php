<?php
/* Copyright (C) 2015    Maxime MANGIN    <maxime@tuxserv.fr>
 * Copyright (C) 2012    Regis Houssin    <regis@dolibarr.fr>
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
 *     \file       /contratabonnement/fiche.php
 *     \ingroup    contratabonnement
 *     \brief      Fiche d'abonnement sur un contrat
 */

$res=@include("../main.inc.php");                    // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

require 'form/html.form.class.php';
require 'class/contratabonnement.class.php';
require 'class/contratabonnement_term.class.php';

require_once(DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php');
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");

$usehm = (!empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE:0);

$socid = GETPOST('socid', 'int');

$langs->load("companies");
$langs->load("contracts");
$langs->load("contratabonnement@contratabonnement");

// Security check
$id = GETPOST('id', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat', $id);

$contrat = new Contrat($db);
$contrat->fetch($id);

llxHeader();

$form = new FormAbonnement($db);

/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

//Ajout d'un abonnement
?>
<script type="text/javascript">
    $(function() {
        $("select[name=cbContratDet]").on("change", function() {
            var startDate = $(this).find(":selected").attr("data-startdate");
            var startDateSels = $(".subscription-startdate");
            if (startDate && startDate != "//" && startDateSels) {
                startDateSels.show();
                startDateSels.find("input").val(startDate);
            }
            else {
                startDateSels.hide();
                startDateSels.find("input").val('');
            }
        });
    });
</script>

<?php

if ($_POST["action"] == 'addligne' && $user->rights->contrat->creer) {
    if ($_POST["cbContratDet"] != -1) { //Si on à séléctionné un contrat
        $datedebut = null;
        $datefin = null;
        $sql = " SELECT cd.date_ouverture, cd.date_fin_validite, cd.remise_percent,cd.qty,cd.subprice, cd.date_cloture,cd.date_ouverture_prevue";
        $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
        $sql.= " WHERE cd.rowid=".$_POST["cbContratDet"];
        $result = $db->query($sql);
        $num = $db->num_rows($result);
        if($num){
            $resultat = $db->fetch_object($result);
            if (isset($_POST["startdate"]) && !empty($_POST["startdate"])) {
               $datedebut = substr($_POST["startdate"], 6, 4) .'-'.substr($_POST["startdate"], 3, 2).'-'.substr($_POST["startdate"], 0, 2). ' 00:00:00';
            }
            else if ($resultat->date_ouverture) {$datedebut=$resultat->date_ouverture;}
            else {$datedebut = $resultat->date_ouverture_prevue;}

            if ($resultat->date_cloture) {$datefin=$resultat->date_cloture;}
            else{$datefin=$resultat->date_fin_validite;}
        }
        if (!$datedebut || (!$datefin && !file_exists('fiche_reconduction.php'))) {
            dol_htmloutput_mesg($langs->trans('ErrorDateContractDet'), null, 'error');
        }
        else { //Info valides pour un abonnement
            if (!$datefin && file_exists('fiche_reconduction.php')) {include('fiche_reconduction.php');} // Calcul avec tacite reconduction
            else if (file_exists('fiche_noncivile.php') && $conf->global->SUBSCRIPTION_USE_CIVIL_BILLING == 0) {include('fiche_noncivile.php');} // On ne base pas sur les périodes civiles
            else { // Calcul des dates avec abonnement classique
                $tabDates = array();
                $tabDatesDebutFin = array();
                $sql = " SELECT dr.moidebut";
                $sql.= " FROM ".MAIN_DB_PREFIX."date_repetition as dr";
                $sql.= " WHERE dr.fk_frequence_repetition=".$_POST["cbFrequence"];
                $sql.= " ORDER BY dr.moidebut";
                $resultMoisDebut = $db->query($sql);
                $numMoisDebut  = $db->num_rows($resultMoisDebut);
                $sql = " SELECT dr.moifin";
                $sql.= " FROM ".MAIN_DB_PREFIX."date_repetition as dr";
                $sql.= " WHERE dr.fk_frequence_repetition=".$_POST["cbFrequence"];
                $sql.= " ORDER BY dr.moifin";
                $resultMoisFin = $db->query($sql);
                $numMoisFin  = $db->num_rows($resultMoisFin);
                if($numMoisDebut && $numMoisFin){ //Selection des dates du service ok
                    $tabMoisDebut = array();
                    $tabMoisFin = array();
                    //Remplissage des mois correspondants à la fréquence choisie
                    for($m = 0 ; $m < $numMoisDebut ; $m++){
                        $resultatMois = $db->fetch_object($resultMoisDebut);
                        array_push($tabMoisDebut,$resultatMois->moidebut);
                    }
                    for($m = 0 ; $m < $numMoisFin ; $m++){
                        $resultatMois = $db->fetch_object($resultMoisFin);
                        array_push($tabMoisFin,$resultatMois->moifin);
                    }
                    $datefintimestamp = strtotime($datefin);
                    $datetmpDebut = substr($datedebut,0,10);
                    $datetmptimestampDebut = strtotime($datetmpDebut); //strtotime pour le timestamp
                    while ($datetmptimestampDebut < $datefintimestamp) {
                        $tabDatesDebutFin = array();
                        //Ajout Date du début
                        array_push($tabDatesDebutFin, $datetmpDebut);
                        $moisDebutChoisi = substr($datetmpDebut,5,2);
                        //Dates de fin
                        $leMoisFin=0;
                        foreach($tabMoisFin as $mois){
                            if($mois >= $moisDebutChoisi){
                                $leMoisFin = $mois;
                                break;
                            }
                        }
                        if ($leMoisFin == 0) {$datetmptimestampFin = mktime(0, 0, 0, $tabMoisFin[0], 28,  substr($datetmpDebut,0,4)+1);}
                        else {$datetmptimestampFin = mktime(0, 0, 0, $leMoisFin, 28,  substr($datetmpDebut,0,4));}
                        $datetmpFin = date("Y-m-d", $datetmptimestampFin);
                        $datetmpFin = substr($datetmpFin,0,8).date("t",strtotime($datetmpFin));
                        if(strtotime($datetmpFin)>$datefintimestamp) {$datetmpFin = date("Y-m-d", $datefintimestamp);}
                        //Date de début
                        $leMoisDebut=0;
                        $moisDatetmp = substr($datetmpDebut,5,2);
                        foreach($tabMoisDebut as $mois){
                            if ($mois > $moisDatetmp) {
                                $leMoisDebut = $mois;
                                break;
                            }
                        }
                        if ($leMoisDebut == 0) {$datetmptimestampDebut = mktime(0, 0, 0, $tabMoisDebut[0], 1,  substr($datetmpDebut,0,4)+1);}
                        else {$datetmptimestampDebut = mktime(0, 0, 0, $leMoisDebut, 1,  substr($datetmpDebut,0,4));}
                        $datetmpDebut = date("Y-m-d", $datetmptimestampDebut);
                        array_push($tabDatesDebutFin, $datetmpFin);
                        //Remplissage des dates finales
                        array_push($tabDates,$tabDatesDebutFin);
                    }
                }
            }
            //Enregistrement
            if (sizeof($tabDates) <= 0) { //paiement en 0 fois
                dol_htmloutput_mesg($langs->trans('CantAddSubDet'), null, 'error');
            }
            else {
                //Create subscription
                $objContratAbo = new Contratabonnement($db);
                $objContratAbo->fk_frequencerepetition = $_POST["cbFrequence"];
                $objContratAbo->fk_contratdet = $_POST["cbContratDet"];
                $objContratAbo->periodepaiement = $_POST["cbPeriode"];
                $rqt = "SELECT remise_percent FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid=".$_POST["cbContratDet"];
                $remise = $db->query($rqt);
                $remise = $db->fetch_object($remise);
                $objContratAbo->remise = $remise->remise_percent;
                $idObjContratAbo=$objContratAbo->create($user);
                if ($idObjContratAbo < 0) { $error++; dol_print_error($db,$myobject->error); }
                else {
                    //Add date term
                    $i = 1;
                    foreach ($tabDates as $date) {
                        $objTerm = new Contratabonnement_term($db);
                        $objTerm->fk_contratabonnement = $idObjContratAbo;
                        $objTerm->datedebutperiode = $date[0];
                        $objTerm->datefinperiode = $date[1];
                        if (file_exists('fiche_noncivile.php') && $conf->global->SUBSCRIPTION_USE_CIVIL_BILLING == 0) { // - un jour pour les non civiles
                            if ($date[1] != $datefin) {
                                $dateTms = mktime(0, 0, 0, substr($date[1],5,2), substr($date[1],8,2),  substr($date[1],0,4));
                                $objTerm->datefinperiode = date("Y-m-d", $dateTms - 86400);
                            }
                        }
                        $id=$objTerm->create($user);
                        if ($id < 0) { $error++; dol_print_error($db,$myobject->error); }
                        $i++;
                    }
                }
                //Calcul of price
                $sql = " SELECT cat.datedebutperiode,cat.datefinperiode,cat.rowid";
                $sql.= " FROM ".MAIN_DB_PREFIX."contratabonnement_term as cat";
                $sql.= " WHERE cat.fk_contratabonnement=".$idObjContratAbo;
                $sql.= " ORDER BY cat.rowid";
                $resultDebutFin = $db->query($sql);
                $numDebutFin = $db->num_rows($resultDebutFin);

                $tabDebutFin = array();
                for ($i=0;$i<$numDebutFin;$i++) { //Pour chaque date
                    $resultatDebutFin = $db->fetch_object($resultDebutFin);
                    $tabTmp = array();
                    array_push($tabTmp, $resultatDebutFin->rowid);
                    array_push($tabTmp, $resultatDebutFin->datedebutperiode);
                    array_push($tabTmp, $resultatDebutFin->datefinperiode);
                    array_push($tabDebutFin,$tabTmp);
                }
                $montantArondi = 0;

                //Coeff Période
                $sqlPeriod = "SELECT f.coeffrepetition FROM ".MAIN_DB_PREFIX."frequence_repetition as f WHERE f.rowid =".$_POST["cbFrequence"];
                $resultPeriod=$db->query($sqlPeriod);
                $C = $db->fetch_object($resultPeriod);
                $C = $C->coeffrepetition;

                for ($i = 0; $i < $numDebutFin; $i++) {
                    $montantP = 0;
                    $N = substr($tabDebutFin[$i][1],5,2); //Numéro du mois dans la période
                    $D = substr($tabDebutFin[$i][1],8,2); //Numéro du jour dans la période
                    if($D == 31) {$D = 30;}
                    while ($N > $C) {$N = $N - $C;}
                    if ($i == 0) {$montantP = (($C-$N)*30+(30-($D-1)))/30;} // Premier paiement
                    else if($i == $numDebutFin-1) { // Dernier paiement
                        $NFin = substr($tabDebutFin[$i][2],5,2); //Numéro du moi de fin dans la période
                        $DFin = substr($tabDebutFin[$i][2],8,2); //Numéro du jour de fin dans la période
                        if($DFin == 31) {$DFin = 30;}
                        while($NFin > $C){$NFin = $NFin - $C;}
                        $montantP = (($NFin-$N)*30+$DFin)/30;
                    }
                    else {$montantP = $C;} // Paiements intermédiares

                    //Produit du montantP et du prix
                    $durationContrat = 1;
                    if ($_POST["cbDurationContrat"]) {
                        $durationContrat = $_POST["cbDurationContrat"];
                        if ($durationContrat == "0.083333333") {$durationContrat = 1/12;}
                        else if ($durationContrat == "0.166666666") {$durationContrat = 1/6;}
                        else if ($durationContrat == "0.333333333") {$durationContrat = 1/3;}
                    }
                    $montantP = $montantP * $resultat->subprice * $resultat->qty * $durationContrat;

                    // On ajoute pas la remise, elle se met toute seule sur la facture
                    // if($resultat->remise_percent>0) {$montantP = $montantP - ($resultat->remise_percent/100*$montantP);}

                    if ($i == $numDebutFin-1) {$montantP+=$montantArondi;} // Dernier paiement : on rajoute l'arrondi
                    $explodeMontant = explode('.',$montantP);
                    $montantP = $explodeMontant[0].'.'.substr($explodeMontant[1],0,2);
                    $montantArondi += '0.00'.substr($explodeMontant[1],2,strlen($explodeMontant[1])-2); // Montant arrondi

                    if(file_exists('fiche_noncivile.php') && $conf->global->SUBSCRIPTION_USE_CIVIL_BILLING == 0) {$montantP = $C * $resultat->subprice * $resultat->qty * $durationContrat;} // Si se on base pas sur les périodes civiles, on prends le montant du contrat
                    //
                    $objTerm->fetch($tabDebutFin[$i][0]); //Rowid
                    $objTerm->datedebutperiode = $tabDebutFin[$i][1];
                    $objTerm->datefinperiode = $tabDebutFin[$i][2];
                    $objTerm->montantperiode = $montantP;
                    $resultUpdateMontant = $objTerm->Update($user);
                    if ($resultUpdateMontant < 0) {$error++; dol_print_error($db,$objTerm->error);}
                }
            }
            // Passe le service à cloturé
            //$sql= " UPDATE ".MAIN_DB_PREFIX."contratdet as cd SET statut=5 WHERE cd.rowid=".$_POST["cbContratDet"];
            //$db->query($sql);
        }

    }
}

// Ajout d'une période
if ($_GET["action"] == "addperiod" && $user->rights->contrat->creer) {
    $idAbonnement = $_GET["rowid"];

    if (isset($idAbonnement) && $idAbonnement > 0) {
        $sql= " SELECT cat.rowid, cat.fk_contratabonnement, cat.datedebutperiode, cat.datefinperiode, cat.montantperiode, fr.coeffrepetition";
        $sql.= " FROM ".MAIN_DB_PREFIX."contratabonnement_term as cat";
        $sql.= " INNER JOIN ".MAIN_DB_PREFIX."contratabonnement as ca ON ca.rowid = cat.fk_contratabonnement";
        $sql.= " INNER JOIN ".MAIN_DB_PREFIX."frequence_repetition as fr ON fr.rowid = ca.fk_frequencerepetition";
        $sql.= " WHERE fk_contratabonnement=".intval($idAbonnement);
        $sql.= " ORDER BY datefinperiode DESC LIMIT 1";
        $cat = $db->query($sql);
        $cat = $db->fetch_object($cat);
        $lastPeriod = $cat->datefinperiode;
        $amount = $cat->montantperiode;

        $tmsDateStart = strtotime($lastPeriod) + 86400;
        $dateStop = date('Y-m-d', strtotime('+'.$cat->coeffrepetition.' month', $tmsDateStart) - 86400);


        $objTerm = new Contratabonnement_term($db);
        $objTerm->fk_contratabonnement = $idAbonnement;
        $objTerm->datedebutperiode = date("Y-m-d", $tmsDateStart);
        $objTerm->montantperiode = $amount;
        $objTerm->datefinperiode = $dateStop;
        $id = $objTerm->create($user);
        if ($id < 0) { $error++; dol_print_error($db,$myobject->error); }

        // Pour ne pas fermer le mode edition
        $_GET["action"] = 'editline';
        $_GET["id"] = $_GET["rowid"];
    }
}

//Suppresion d'un abonnement
if ($_REQUEST["action"] == 'confirm_deleteline' && $_REQUEST["confirm"] == 'yes') {
        $objContratAbo = new Contratabonnement($db);
        $objContratAbo->id = $_GET["lineid"];
        $result=$objContratAbo->delete($user);
        if ($result < 0) { $error++; dol_print_error($db,$myobject->error); }
}

//Suppresion d'une période
if ($_REQUEST["action"] == 'confirm_deleteperiod' && $_REQUEST["confirm"] == 'yes') {
        $obj = new Contratabonnement_term($db);
        $obj->id = $_GET["lineid"];
        $result = $obj->delete($user);
        if ($result < 0) { $error++; dol_print_error($db,$myobject->error); }
}

// Désactivation d'un abonnement
if ($_POST["action"] == 'updateligne' && $user->rights->contrat->creer && !$_POST["cancel"] && isset($_POST['closeSubscription'])) {
    $objContratAbo = new Contratabonnement($db);
    $objContratAbo->fetch($_POST["elrowid"]);
    $objContratAbo->statut = 0;
    $result=$objContratAbo->update($user);
    if ($result < 0) { $error++; dol_print_error($db,$myobject->error); }
}

// Activation d'un abonnement
else if ($_POST["action"] == 'updateligne' && $user->rights->contrat->creer && !$_POST["cancel"] && isset($_POST['enableSubscription'])) {
    $objContratAbo = new Contratabonnement($db);
    $objContratAbo->fetch($_POST["elrowid"]);
    $objContratAbo->statut = 1;
    $result=$objContratAbo->update($user);
    if ($result < 0) { $error++; dol_print_error($db,$myobject->error); }
}
//Edition d'un abonnement
else if ($_POST["action"] == 'updateligne' && $user->rights->contrat->creer && ! $_POST["cancel"]) {
    $sql= " SELECT cat.rowid, cat.fk_contratabonnement, cat.datedebutperiode, cat.datefinperiode, cat.montantperiode";
    $sql.= " FROM ".MAIN_DB_PREFIX."contratabonnement_term as cat";
    $sql.= " WHERE fk_contratabonnement=".$_POST["elrowid"];

    $resultcat = $db->query($sql);
    $numcat = $db->num_rows($resultcat);

    $objContratAboTerm = new Contratabonnement_term($db);
    for ($i = 0; $i < $numcat; $i++) {
        $resultatcat = $db->fetch_object($resultcat);
        $objContratAboTerm->fetch($resultatcat->rowid);
        if (isset($_POST['factureoupas'.$i])) {$objContratAboTerm->facture = 1;}
        else {$objContratAboTerm->facture=0;}
        if (isset($_POST['datedebutperiode'.$i])) {$objContratAboTerm->datedebutperiode = datFrVersDateUs($_POST['datedebutperiode'.$i]);}
        if (isset($_POST['datefinperiode'.$i])) {$objContratAboTerm->datefinperiode = datFrVersDateUs($_POST['datefinperiode'.$i]);}
        if (is_numeric($_POST['montant'.$i])) {$objContratAboTerm->montantperiode = $_POST['montant'.$i];}
        $result = $objContratAboTerm->update($user);
        if ($result < 0) {$error++; dol_print_error($db,$objContratAboTerm->error);}
    }
}

/******************************************************************************/
/* Affichage fiche                                                            */
/******************************************************************************/

if (isset($_GET["id"])) {
    if ($mesg) {print $mesg;}
    // Entête
    $soc = new Societe($db);
    $soc->fetch($contrat->socid);
    $head = contract_prepare_head($contrat);
    dol_fiche_head($head, 2, $langs->trans("Contract"), 0, 'contract');
    print '<table class="border" width="100%">';
    // Reference
    print '    <tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="5">'.$contrat->ref.'</td></tr>';
    // Societe
    print '    <tr><td>'.$langs->trans("Customer").'</td>';
    print '    <td colspan="3">'.$soc->getNomUrl(1).'</td></tr>';
    print '</table>';

    // Enregistrements
    $sql = "SELECT";
    $sql.= " ca.rowid, ca.fk_contratdet, ca.fk_frequencerepetition, ca.periodepaiement, ca.remise, ca.statut,";
    $sql.= " p.ref, p.label,";
    $sql.= " cd.fk_product, cd.description,";
    $sql.= " f.nomfrequencerepetition";
    $sql.= " FROM ".MAIN_DB_PREFIX."contratabonnement as ca";
    $sql.= " INNER JOIN ".MAIN_DB_PREFIX."frequence_repetition as f";
    $sql.= " ON f.rowid = ca.fk_frequencerepetition";
    $sql.= " INNER JOIN ".MAIN_DB_PREFIX."contratdet as cd";
    $sql.= " ON cd.rowid = ca.fk_contratdet";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p";
    $sql.= " ON p.rowid = cd.fk_product";
    $sql.= " INNER JOIN ".MAIN_DB_PREFIX."contrat as c";
    $sql.= " ON c.rowid = cd.fk_contrat";
    $sql.= " WHERE c.rowid = ".$contrat->id; //Produits/services fermés
    $sql.= " ORDER BY ca.rowid";
    $result = $db->query($sql);

    $num = $db->num_rows($result);
    print '<br/>';
    if ($num > 0) { // Si il y a des abonnements déja entrés
        $colorb='666666';
        print '<table class="notopnoleft" width="100%">';
        for ($i=0;$i<$num;$i++) { //Pour chaque ligne d'abonnement
            $resultat = $db->fetch_object($result);

            print '<tr height="16">';
            print '<td class="tab"  style="border-left: 1px solid #'.$colorb.'; border-top: 1px solid #'.$colorb.'; border-bottom: 1px solid #'.$colorb.';">';
            $i++;
            print $langs->trans("Subscription").' N°'.$i.'<br/>'.$langs->trans("Status").' : ';
            if ($resultat->statut == 1) {
                print img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans("Enabled");
            }
            else {
                print img_picto($langs->trans('Closed'),'statut6').' '.$langs->trans("Closed");
            }
            print '</td>';
            $i--;
            print '<td class="tab" style="border-right: 1px solid #'.$colorb.'; border-top: 1px solid #'.$colorb.'; border-bottom: 1px solid #'.$colorb.';" rowspan="2">';

            // Détail de l'abonnement
                print '<table class="notopnoleft" width="100%" >';
                print '<tr class="liste_titre">';
                print '<td>'.$langs->trans("Service").'</td>';
                if ($_GET["action"] != 'editline' || $_GET["rowid"] != $resultat->rowid) { //Show
                    print '<td     align="center" width="100">'.$langs->trans("ActualPerio").'</td>';
                    print '<td     align="center" width="200">'.$langs->trans("Repetition").'</td>';
                }
                else{ //Update
                    print '<td     align="center" width="285">'.$langs->trans("Period").'</td>';
                    print '<td     align="center" width="200">'.$langs->trans("Sum").'</td>';
                    if ($resultat->remise > 0) {print '<td     align="center" width="200">'.$langs->trans("SumAfterDiscount").'</td>';}
                    print '<td     align="center" width="40">'.$langs->trans("Billing").'</td>';
                }
                print '<td align="center" width="200">'.$langs->trans("Invoice").'</td>';
                print '<td width="100">&nbsp;</td>';
                print '</tr>'."\n";

                if ($_GET["action"] != 'editline' || $_GET["rowid"] != $resultat->rowid){
                    print '<tr valign="top" class="impair">';
                    // Libelle
                    if ($resultat->fk_product > 0) {
                        print '<td>';
                        print '<a href="'.DOL_URL_ROOT.'/product/card.php?id='.$resultat->fk_product.'">';
                        print img_object($langs->trans("ShowService"),"service").' '.$resultat->ref.'</a>';
                        print $resultat->label?' - '.$resultat->label:'';
                        if ($resultat->description) print '<br>'.nl2br($resultat->description);
                        print '</td>';
                    }
                    else {
                        print "<td>".nl2br($resultat->description)."</td>\n";
                    }
                    //Dates
                    print '<td align="center">';
                    $sql = "SELECT cat.rowid, cat.datedebutperiode, cat.datefinperiode";
                    $sql.= " FROM ".MAIN_DB_PREFIX."contratabonnement_term as cat";
                    $sql.= " WHERE cat.fk_contratabonnement = ".$resultat->rowid;
                    $sql.= " AND cat.datedebutperiode<=NOW() AND cat.datefinperiode>=NOW()";
                    $sql.= " LIMIT 0,1";
                    $resultDate = $db->query($sql);
                    $numDate = $db->num_rows($resultDate);
                    if ($numDate) {
                        $date = $db->fetch_object($resultDate);
                        print dateUsVersDateFr($date->datedebutperiode).' '.$langs->trans("to").' '.dateUsVersDateFr($date->datefinperiode);
                    }
                    print '</td>';
                    // Répétition
                    print '<td align="center">'.$langs->trans($resultat->nomfrequencerepetition).'</td>';
                    //Période de facturation
                    if ($resultat->periodepaiement == 0) {print '<td align="center">'.$langs->trans("BegPeriod").'</td>';}
                    else if ($resultat->periodepaiement == 1) {print '<td align="center">'.$langs->trans("EndPeriod").'</td>';}
                    else {print '<td align="center">ERROR</td>';}
                    // Icon update et delete (statut contrat 0=brouillon,1=valide,2=ferme)
                    print '<td align="right" nowrap="nowrap">';
                    if ($user->rights->contrat->creer && ($contrat->statut >= 0))
                    {
                        print '<a style="vertical-align: middle; display: inline-block;" href="fiche.php?id='.$contrat->id.'&amp;action=editline&amp;rowid='.$resultat->rowid.'">';
                        print img_edit();
                        print '</a>';
                    }
                    else {
                        print '&nbsp;';
                    }
                    if ( $user->rights->contrat->creer && ($contrat->statut >= 0)) {
                        print '&nbsp;';
                        print '<a style="vertical-align: middle; display: inline-block;" href="fiche.php?id='.$contrat->id.'&amp;action=deleteline&amp;rowid='.$resultat->rowid.'">';
                        print img_delete();
                        print '</a>';
                    }
                    print '</td>';
                    print '</tr>'."\n";

                }
                else { //Ligne en mode update
                    print '<form name="update" action="fiche.php?id='.$contrat->id.'" method="post">';
                    print '<input type="hidden" name="action" value="updateligne">';
                    print '<input type="hidden" name="elrowid" value="'.$_GET["rowid"].'">';

                    $sql = "SELECT cat.rowid, cat.datedebutperiode, cat.datefinperiode, cat.montantperiode,cat.facture";
                    $sql.= " FROM ".MAIN_DB_PREFIX."contratabonnement_term as cat";
                    $sql.= " WHERE cat.fk_contratabonnement = ".$resultat->rowid;

                    $resultDetail = $db->query($sql);
                    $numDetail = $db->num_rows($resultDetail);
                    for($j=0;$j<$numDetail;$j++){
                        $resultatDetail = $db->fetch_object($resultDetail);
                        print '<tr class="impair" >';
                        //Service
                        print '<td >';
                        if($j==0) {
                            if ($resultat->fk_product > 0)
                            {
                                print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$resultat->fk_product.'">';
                                print img_object($langs->trans("ShowService"),"service").' '.$resultat->ref.'</a>';
                                print $resultat->label?' - '.$resultat->label:'';
                                //if ($resultat->description) print '<br>'.nl2br($resultat->description);
                            }
                            else
                            {
                                print nl2br($resultat->description);
                            }
                        }
                        print '</td>';
                        //Date des périodes
                        print '<td align="center">';
                            $form->select_date($resultatDetail->datedebutperiode, 'datedebutperiode'.$j, $usehm, $usehm, '', 'datedebutperiode'.$j);
                            print ' '.$langs->trans("to").' ';
                            $form->select_date($resultatDetail->datefinperiode, 'datefinperiode'.$j, $usehm, $usehm, '', 'datefinperiode'.$j);
                            print '<a style="margin-left: 15px" href="fiche.php?id='.$contrat->id.'&amp;action=deleteperiod&amp;rowid='.$resultatDetail->rowid.'">'.img_delete().'</a>';
                        print '</td>';
                        //Montant
                        print '<td align="center">';
                            print '<input style="max-width:75px;text-align:center"name="montant'.$j.'" type="text" value="'.$resultatDetail->montantperiode.'" />';
                        print '</td>';
                        // Montant après réduction
                        if ($resultat->remise > 0) {
                            print '<td align="center">';
                            $reduc = $resultatDetail->montantperiode - ($resultatDetail->montantperiode * $resultat->remise / 100);
                            print $reduc;
                            print '</td>';
                        }
                        //Case à cocher
                        print '<td align="center">';
                        if($resultatDetail->facture){
                            print '<input name="factureoupas'.$j.'" type="checkbox" checked="checked"/>';
                        }
                        else {print '<input name="factureoupas'.$j.'" type="checkbox"  />';}
                        print '</td>';
                        //Debut ou fin de période
                        print '<td align="center">';
                        if($j==0){
                            if($resultat->periodepaiement==0)
                                print $langs->trans("BegPeriod");
                            else if($resultat->periodepaiement==1)
                                print $langs->trans("EndPeriod");
                            else
                                print 'ERROR';
                        }
                        print '</td>';

                        //Btn modifier et annuler
                        if($j+1>=$numDetail && $j==0){
                            print '<td align="center" colspan="3" rowspan="2" valign="middle">';
                            print '<input type="submit" class="button" name="save" value="'.$langs->trans("Modify").'">';
                            print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
                        }
                        else if($j==0){
                            print '<td align="center" valign="middle">';
                            print '<input type="submit" class="button" name="save" value="'.$langs->trans("Modify").'">';
                            print '</td>';
                        }
                        else if($j==1){
                            print '<td align="center" valign="middle">';
                            print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
                            print '</td>';
                        }
                        else{print '<td align="center"></td>';}
                        print '</td>';

                        print '</tr>';
                    }

                    print '<tr><td></td>';
                    print '<td align="center"><a href="fiche.php?id='.$contrat->id.'&amp;action=addperiod&amp;rowid='.$resultat->rowid.'"><input style="margin-top: 7px; font-size: 10px;" type="button" class="button" name="addperiod" value="'.$langs->trans("AddANewPeriod").'"></a></td>';
                    print '<td colspan="10"></td></tr>';

                    // Activer désactiver l'abonnement
                    print '<td><tr></tr></td><tr><td colspan="10" align="right"><hr>';
                    if ($resultat->statut==1) {
                        print '<input type="submit" class="button" name="closeSubscription" value="'.$langs->trans("closeSubscription").'">';
                    }
                    else {
                        print '<input type="submit" class="button" name="enableSubscription" value="'.$langs->trans("Activate").'">';
                    }
                    print '</td></tr><tr><td></td></tr>';
                    print "</form>\n";
                }



            print '</table>';
            print '</td>';
            print '</tr>';
            print '<tr><td style="border-right: 1px solid #'.$colorb.'">&nbsp;</td></tr>';
        }
        print '</table>';
    }

    /*
        * Confirmation to delete subscription
    */
    if ($_REQUEST["action"] == 'deleteline' && ! $_REQUEST["cancel"] && $user->rights->contrat->creer){
        $ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$contrat->id."&lineid=".$_GET["rowid"],$langs->trans("DeleteSubscriptionLine"),$langs->trans("ConfirmDeleteSubscriptionLine"),"confirm_deleteline",'',0,1);
        if ($ret == 'html') print '<table class="notopnoleftnoright" width="100%"><tr height="6"><td></td></tr></table>';
    }
    else if ($_REQUEST["action"] == 'deleteperiod' && ! $_REQUEST["cancel"] && $user->rights->contrat->creer){
        $ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$contrat->id."&lineid=".$_GET["rowid"],$langs->trans("DeletePeriodLine"),$langs->trans("ConfirmDeletePeriodLine"),"confirm_deleteperiod",'',0,1);
        if ($ret == 'html') print '<table class="notopnoleftnoright" width="100%"><tr height="6"><td></td></tr></table>';
    }


    /*
     * Ajout d'une ligne
     */

    if ($user->rights->contrat->creer && ($contrat->statut >= 0)){
        print '<br>';
        print '<form name="addligne_sl" action="'.$_SERVER["PHP_SELF"].'?id='.$contrat->id.'" method="post" class="impair">';
        print '<input type="hidden" name="action" value="addligne">';
        print '<table class="noborder" width="100%">';    // Array with (n*2)+1 lines

        print '<tr class="liste_titre" >';
        print '<td>'.$langs->trans("ServicesProductsInContract").'</td>';
        print '<td class="subscription-startdate" style="display: none;">'.$langs->trans("subscriptionStartDate").'</td>';
        if (file_exists('fiche_reconduction.php')) {
            print '<td align="center" width="200">'.$langs->trans("ReconductionNbPeriod").'</td>';
        }
        $priceAs = $form->textwithpicto($langs->trans("PriceAs"),$langs->transnoentities("DescPriceAs"),2);
        print '<td align="center" width="200">'.$priceAs.'</td>';
        print '<td align="center" width="200">'.$langs->trans("Repeat").'</td>';
        print '<td align="center" width="200">'.$langs->trans("Invoice").'</td>';
        print '<td width="100">&nbsp;</td>';
        print '</tr>'."\n";

        print '<tr>';
        print '<td >';
            $form->select_services_contrat($contrat->id);
        print '</td>';
        print '<td class="subscription-startdate" style="display: none;">';
            $form->select_date('','startdate', $usehm, $usehm, '', "startdate");
        print '</td>';
        if (file_exists('fiche_reconduction.php')) {
            print '<td align="center">';
            print '<input name="nbPeriodsRecond" value="'.$conf->global->SUBSCRIPTION_RECOND_PERIOD.'" />';
            print '</td>';
        }
        print '<td align="center">';
            $form->select_duration_contrat();
        print '</td>';
        print '<td align="center">';
            $form->select_frequence_repetition();
        print '</td>';
        print '<td align="center">';
            $form->select_periode_facturation();
        print '</td>';

        print '<td align="center" ><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
        print '</tr>'."\n";
        print '</table>';
        print '</form>';
    }

    /*
    * Actions (boutons du bas)
    */
    print '</div>';


    if ($user->societe_id == 0)
    {
        print '<div class="tabsAction">';

        if ($conf->facture->enabled )
        {
            $langs->load("bills");
            print '<a class="butAction" href="imprimer.php?action=create&amp;id='.$contrat->id.'">'.$langs->trans("PrintSub").'</a>';

            if ($user->rights->facture->creer && $contrat->statut > 0) {
                print '<a class="butAction" href="facturer.php?action=create&amp;id='.$contrat->id.'">'.$langs->trans("CreateSubBill").'</a>';
            }
            else {
                print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissionsOrStatut").'">'.$langs->trans("CreateSubBill").'</a>';
            }
        }
        print '</div>';
        print '<br>';
    }
    $object = $contrat;
    $object->element = 'contratabonnement';
    print '<table width="100%"><tr><td width="50%" valign="top">';
    /*
     * Linked object block
     */
    //$somethingshown = $object->showLinkedObjectBlock();
    $somethingshown = $form->showLinkedObjectBlock($object);
    print '</td><td valign="top" width="50%">';
    print '</td></tr></table>';



}

function dateUsVersDateFr($date){
    return substr($date,8,2).'/'.substr($date,5,2).'/'.substr($date,0,4);
}

function datFrVersDateUs($date){
    return substr($date,6,4).'-'.substr($date,3,2).'-'.substr($date,0,2);
}

$db->close();

llxFooter('$Date: 2015/03/12 15:00:00');
?>
