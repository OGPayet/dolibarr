<?php

if ($action == 'update' && $request_method === 'POST') {

    $page  = GETPOST('page');

    // $d1 = GETPOST('debut');
    // $f1 = GETPOST('fin');
    $id=GETPOST('id');

    $vehicul = GETPOST('vehicule');
    $kilometre = GETPOST('kilometre');
    $litre = GETPOST('litre');
    $prix = GETPOST('prix');
    $fournisseur = GETPOST('fournisseur');
    $ref_facture = GETPOST('ref_facture');
    $remarques = GETPOST('remarques');
    if(GETPOST('date')){
        $date =explode('/',GETPOST('date'));
        $date = $date[2].'-'.$date[1].'-'.$date[0];
    }

    $object = new suivi_essence($db);
    $object->fetch($id);
    $kilometre_1 = $object->kilometrage;

    $object->vehicule     =  $vehicul;
    $object->kilometrage  =  $kilometre;
    $object->litre        =  $litre;
    $object->prix         =  $prix;
    $object->date         =  $date;
    $object->fournisseur  =  $fournisseur;
    $object->ref_facture  =  $ref_facture;
    $object->remarques    =  $remarques;

    // print_r($object);die();

    $ret = $extrafields->setOptionalsFromPost(null, $object);
    $isvalid = $object->update($id);

    if ($isvalid > 0) {
        $vehicules->fetch($vehicul);
        // update costs
        $costs->fetchAll('','',0,0,'and id_suiviessence ='.$id);
        $elem=$costs->rows[0];
        $prixT = $prix*$litre;

        $objcout = new costsvehicule($db);

        $objcout->vehicule = $vehicul;
        $objcout->id_suiviessence = $avance;
        $objcout->date = date('Y-m-d');
        $objcout->prix = $prixT;
        $objcout->type = $langs->trans("ravitaillement");


        // $data_cost=[
        //     'vehicule'=>$vehicule,
        //     'id_suiviessence'=>$id,
        //     'date'=>date('Y-m-d'),
        //     'prix'=>$prixT,
        // ];

        if($elem->rowid){
            $objcout->fetch($table_element->rowid);
            $objcout->update($elem->rowid);
        }else{
            $objcout->create(1);
        }
        // create kilometrage

        if(!empty($kilometre) && $kilometre_1 != $kilometre){
            $objkilom = new kilometrage($db);

            $objkilom->kilometrage = $kilometre;
            $objkilom->vehicule = $vehicul;
            $objkilom->date = $date;

            $test=$objkilom->create(1);
            if($test){
                $max=$kilometrage->Max_kilometrage();
                $objvehicule = new vehiculeparc($db);
                $objvehicule->fetch($vehicul);
                $objvehicule->kilometrage = $max;
                $objvehicule->update($vehicul);
            }
        }

        header('Location: ./card.php?id='.$id);
        exit;
    }
    else {
        header('Location: ./card.php?id='. $id .'&update=0');
        exit;
    }
}


if($action == "edit"){

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="card_suiviessenc">';
    $object = new suivi_essence($db);
    $object->fetch($id);
    $item = $object;

    $extrafields = new ExtraFields($db);
    $extrafields->fetch_name_optionals_label($object->table_element);
    // $object->fetch($item->rowid);
    $object->fetch_optionals();

    print '<input type="hidden" name="action" value="update" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
    print '<div class="div_1">';
        print '<div class="div_left">';
            print '<div class="title_div"> <span>'.$langs->trans("detail_vehicul").'</span> </div>';
            print '<table class="border nc_table_" width="100%">';
                print '<tbody>';
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('vehicule').'</td>';
                        print '<td >'.$vehicules->select_with_filter($item->vehicule).'</td>';
                    print '</tr>';
                    $vehicules->fetch($item->vehicule);
                    $user_->fetch($vehicules->conducteur);
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('acheteur').'</td>';
                        print '<td> <span id="acheteur">'.$user_->getNomUrl(1).'</span> </td>';
                    print '</tr>';


                print '</body>';
            print '</table>';
        print '</div>';
        print '<div class="div_right">';
            print '<div class="title_div"> <span>'.$langs->trans("detail_carb").'</span> </div>';
            print '<table class="border nc_table_" width="100%">';
                print '<tbody>';
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('litre').'</td>';
                        print '<td ><input type="number" step="0.001" class="" id="litre" name="litre" value="'.$item->litre.'" autocomplete="off"/><span class="error">*'.$langs->trans("erreur_calcul").'</span>';
                        print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('prix').'</td>';
                        print '<td ><input type="number" step="0.001" class="" onchange=get_prixt(this) id="prix" value="'.$item->prix.'" name="prix"  autocomplete="off"/>';
                        print '    ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')';
                        print '</td>';
                    print '</tr>';
                    $prix_T=$item->litre*$item->prix;
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('prix_T').'</td>';
                        print '<td><span id="prix_T" >'.number_format($prix_T,2,',',' ').'</span>';
                        print '    ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')';
                        print '</td>';
                    print '</tr>';


                print '</tbody>';
            print '</table>';
        print '</div>';
    print '</div>';
    print '<div class="div_1">';
        print '<div class="div_left">';
            print '<div class="title_div"> <span>'.$langs->trans("detail_kilom").'</span> </div>';
             print '<table class="border nc_table_" width="100%">';
                print '<tbody>';
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('kilometrage').'</td>';
                        print '<td >';
                            print '<input type="number" name="kilometre" step="0.001" min="0" value="'.$item->kilometrage.'" id="kilometre">';
                            print '<span id="unite">'.$langs->trans($vehicules->unite).'</span>';
                        print '</td>';
                    print '</tr>';

                print '</tbody>';
            print '</table>';
        print '</div>';

        print '<div class="div_right">';
            print '<div class="title_div"> <span>'.$langs->trans("info_suplm").'</span> </div>';
            print '<table class="border nc_table_" width="100%">';
                print '<tbody>';
                    print '<tr>';
                        $date=explode('-',$item->date);
                        $date = $date[2].'/'.$date[1].'/'.$date[0];
                        print '<td align="left" >'.$langs->trans('date').'</td>';
                        print '<td ><input type="text" class="datepickerparc" id="date" class="datepickerparc" value="'.$date.'" name="date"  autocomplete="off"/></td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('ref_facture').'</td>';
                        print '<td ><input type="text" name="ref_facture" value="'.$item->ref_facture.'" ></td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('fournisseur').'</td>';
                        print '<td >'.$vehicules->select_fournisseur($item->fournisseur).'</td>';
                    print '</tr>';

                print '</tbody>';
            print '</table>';
        print '</div>';
    print '</div>';

    print '<div id="remarques">';
        print '<div class="cnd">'.$langs->trans('remarques').':</div>';
        print '<div class="txt_condition"><textarea name="remarques" class="remarques"  >'.$item->remarques.'</textarea></div>';
    print '<br><br>';
    print '</div>';

    if($extrafields->attributes[$object->table_element]['label']){
        print '<div class="fichecenter">';
            print '<div class="div_extrafield">';
                print '<table class="noborder nc_table_" width="100%">';
                    print '<body>';
                        print '<tr class="liste_titre"> <th colspan="2">'.$langs->trans("champs_add").'</th> </tr>';
                        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';
                    print '</tbody>';
                print '</table>';
            print '</div>';
        print '</div>';
    }
    print '<table class="" width="100%">';
    print '<tr>';
        print '<td colspan="2" >';
            print '<br>';
            print '<input type="submit" style="display:none" id="sub_valid" value="'.$langs->trans('Validate').'" style="" name="bouton" class="butAction" />';
            print '<a  class="butAction" id="btn_valid">'.$langs->trans('Validate').'</a>';

            print '<a href="./index.php?page='.$page.'" class="butAction">'.$langs->trans('Cancel').'</a>';
        print '</td>';
    print '</tr>';
    print '</table>';

    print '</form>';

    ?>
    <?php
}

?>

<script>
    $(document).ready(function() {
        $('#btn_valid').click(function(){
            $('#sub_valid').trigger('click');
        })
    })
</script>
