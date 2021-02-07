<?php

    if ($action == 'create' && $request_method === 'POST') {

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

        $object->vehicule     =  $vehicul;
        $object->kilometrage  =  $kilometre;
        $object->litre        =  $litre;
        $object->prix         =  $prix;
        $object->date         =  $date;
        $object->fournisseur  =  $fournisseur;
        $object->ref_facture  =  $ref_facture;
        $object->remarques    =  $remarques;

        $ret = $extrafields->setOptionalsFromPost(null, $object);
        $avance = $object->create(1);

        // If no SQL error we redirect to the request card
        if ($avance > 0 ) {
        // create costs
            $objcout = new costsvehicule($db);
            $prixT = $prix*$litre;

            $objcout->vehicule = $vehicul;
            $objcout->id_suiviessence = $avance;
            $objcout->date = date('Y-m-d');
            $objcout->prix = $prixT;
            $objcout->type = $langs->trans("ravitaillement");

            $objcout->create(1);
        // create kilometrage
            if($kilometre){

                $objkilom = new kilometrage($db);

                $objkilom->kilometrage = $kilometre;
                $objkilom->vehicule = $vehicul;
                $objkilom->date = $date;

                $test=$objkilom->create(1);

                if($test){
                    $objvehicule = new vehiculeparc($db);
                    $objvehicule->fetch($vehicul);
                    $max=$kilometrage->Max_kilometrage();
                    $objvehicule->kilometrage = $max;
                    $objvehicule->update($vehicul);
                }
            }

            header('Location: ./card.php?id='. $avance.'');
            exit;
        } 
        else {
            header('Location: card.php?action=request&error=SQL_Create&msg='.$recrutement->error);
            exit;
        }
    }

    if($action == "add"){
        $id_poste=GETPOST('poste');
        print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="card_suiviessenc">';

        print '<input type="hidden" name="action" value="create" />';
        print '<input type="hidden" name="page" value="'.$page.'" />';
        print '<input type="hidden" name="poste" value="'.$id_poste.'" />';
        print '<div class="div_1">';
            print '<div class="div_left">';
                print '<div class="title_div"> <span>'.$langs->trans("detail_vehicul").'</span> </div>';
                print '<table class="border nc_table_" width="100%">';
                    print '<tbody>';
                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('vehicule').'</td>';
                            print '<td >'.$vehicules->select_with_filter().'</td>';
                        print '</tr>';
                    print '</tbody>';
                print '</table>';
            print '</div>';
            print '<div class="div_right">';
                print '<div class="title_div"> <span>'.$langs->trans("detail_carb").'</span> </div>';
                print '<table class="border nc_table_" width="100%">';
                    print '<tbody>';
                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('litre').'</td>';
                            print '<td ><input type="number" step="0.001" class="" id="litre" onchange=get_prixt(this) name="litre"  autocomplete="off"/><span class="error">*'.$langs->trans("erreur_calcul").'</span>';
                            print '</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('prix').'</td>';
                            print '<td>';
                                print '<input type="number" step="0.001" class="" onchange=get_prixt(this) id="prix" name="prix"  autocomplete="off"/>';
                                print '('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')';
                            print '</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('prix_T').'</td>';
                            print '<td >';
                                print '<span id="prix_T" ></span> ';
                                print '    ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')';
                            print '</td>';
                        print '</tr>';
                    print '</tbody>';
                print '</table>';
            print '</div>';
        print '</div>';
        print '<div class="div_2">';
            print '<div class="div_left">';
                print '<div class="title_div"> <span>'.$langs->trans("detail_kilom").'</span> </div>';
                print '<table class="border nc_table_" width="100%">';
                    print '<tbody>';
                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('kilometrique').'</td>';
                            print '<td >';
                                print '<input type="number" name="kilometre" step="0.001" min="0" id="kilometre">';
                                print '<span id="unite"></span>';
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
                            print '<td align="left" >'.$langs->trans('date').'</td>';
                            print '<td ><input type="text" class="datepickerparc" value="'.date("d/m/Y").'" id="date" class="datepickerparc"  name="date"  autocomplete="off"/></td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('acheteur').'</td>';
                            print '<td> <span id="acheteur"></span> </td>';
                        print '</tr>';


                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('ref_facture').'</td>';
                            print '<td ><input type="text" name="ref_facture" ></td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('fournisseur').'</td>';
                            print '<td >'.$vehicules->select_fournisseur().'</td>';
                        print '</tr>';

                       

                    print '</tbody>';
                print '</table>';
            print '</div>';
        print '</div>';

        print '<div id="remarques">';
            print '<div class="cnd">'.$langs->trans('remarques').':</div>';
            print '<div class="txt_condition"><textarea name="remarques" class="remarques" ></textarea></div>';
        print '</div>';

        $object = new suivi_essence($db);
        if($extrafields->attributes[$object->table_element]['label']){
            print '<div class="fichecenter">';    
                print '<div class="div_extrafield">';
                    print '<table class="noborder nc_table_" width="100%">';
                        print '<body>';
                            print '<tr class="liste_titre"> <th colspan="2">'.$langs->trans("champs_add").'</th> </tr>';
                            print '<tr>';
                                include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';
                            print '</tr>';
                        print '</tbody>';
                    print '</table>';
                print '</div>';
            print '</div>';
        }
        // Actions
        print '<table class="" width="100%">';
            print '<tr>';
                print '<td colspan="2" >';
                print '<br>';
                print '<input type="submit" style="display:none" id="sub_valid" value="'.$langs->trans('Validate').'" name="bouton" class="butAction" />';
                print '<a  class="butAction" id="btn_valid">'.$langs->trans('Validate').'</a>';

                print '<a href="./index.php?page='.$page.'" class="butAction">'.$langs->trans('Cancel').'</a>';
            print '</tr>';
        print '</table>';

        print '</form>';
    }

?>
<script>
    $(document).ready(function() {
        $('#btn_valid').click(function(){
            $('#sub_valid').trigger('click');
        })
    })
</script>
