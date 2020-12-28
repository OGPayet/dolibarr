<?php

    if ($action == 'create' && $request_method === 'POST') {


        $vehicule = GETPOST('vehicule');
        $type = GETPOST('typeintervention');
        $prix = GETPOST('prix');
        $kilometre = GETPOST('kilometre');
        $ref_facture = GETPOST('ref_facture');
        $fournisseur = GETPOST('fournisseur');
        $notes = GETPOST('notes');
        if(GETPOST('date')){
            $date = explode('/', GETPOST('date'));
            $date=$date[2].'-'.$date[1].'-'.$date[0];
        }

        $services=NULL;
        if(!empty(GETPOST('services')) && count(GETPOST('services')) > 0){
            $services = json_encode(GETPOST('services'));
        }

        $object = new interventions_parc($db);

        $insert = array(
            'vehicule'          =>  $vehicule,
            'typeintervention'  =>  $type,
            'prix'              =>  $prix,
            'date'              =>  $date,
            'notes'             =>  addslashes($notes),
            'ref_facture'       =>  $ref_facture,
            'kilometrage'       =>  $kilometre,
            'fournisseur'       =>  $fournisseur,
            'service_inclus'    =>  $services,
        );

        $object->vehicule = $vehicule;
        $object->typeintervention = $type;
        $object->prix = $prix;
        $object->date = $date;
        $object->notes = addslashes($notes);
        $object->ref_facture = $ref_facture;
        $object->kilometrage = $kilometre;
        $object->fournisseur = $fournisseur;
        $object->service_inclus = $services;
        // print_r($object);die();

        if((GETPOST('datevalidate')) && !empty(GETPOST('datevalidate'))){
            $date2 = explode('/', GETPOST('datevalidate'));
            $datevalidate = $date2[2].'-'.$date2[1].'-'.$date2[0];

            $object->datevalidate = $datevalidate;
        }

        $extrafield_interv = new ExtraFields($db);
        $extrafield_interv->fetch_name_optionals_label($object->table_element);


        $ret = $extrafield_interv->setOptionalsFromPost(null, $object);
        $avance = $object->create(1);
        // If no SQL error we redirect to the request card
        if ($avance > 0 ) {

            $typeintervention->fetch($type);
            $objectcost = new costsvehicule($db);

            $objectcost->vehicule = $vehicule;
            $objectcost->type = $typeintervention->label;
            $objectcost->id_intervention = $avance;
            $objectcost->date = $date;
            $objectcost->prix = $prix;

            // $extrafield_cout = new ExtraFields($db);
            // $extrafield_cout->fetch_name_optionals_label($objectcost->table_element);
            // $ret = $extrafield_cout->setOptionalsFromPost(null, $objectcost);
            $avance = $objectcost->create(1);

            // create kilometrage
            if($kilometre && $vehicule){

                $kilom = new kilometrage($db);
                $kilom->vehicule = $vehicule;
                $kilom->kilometrage = $kilometre;
                $kilom->date = $date;

                // $extrafield_kilo = new ExtraFields($db);
                // $extrafield_kilo->fetch_name_optionals_label($kilom->table_element);
                // $ret_kilom = $extrafield_kilo->setOptionalsFromPost(null, $kilom);
                $test = $kilom->create(1);

                // $test=$kilometrage->create(1);

                if($test){
                    $max=$kilometrage->Max_kilometrage($vehicule);
                    $vehicl = new vehiculeparc($db);
                    $vehicl->fetch($vehicule);
                    $vehicl->kilometrage = $max;
                    $vehicl->update($vehicule);
                }
            }

            // header('Location: ./card.php?id='. $avance.'&action=edit');
            header('Location: ./index.php');
            exit;
        }

        else {
            header('Location: card.php?action=request&error=SQL_Create&msg='.$parcautomobile->error);
            exit;
        }
    }

    if($action == "add"){
        $id_poste=GETPOST('poste');
        print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="card_interv" >';

            print '<input type="hidden" name="action" value="create" />';
            print '<input type="hidden" name="page" value="'.$page.'" />';
            print '<input type="hidden" name="poste" value="'.$id_poste.'" />';

            print '<div class="title_div"> <span>'.$langs->trans("detail_inter").'</span> </div>';
            print '<table class="noborder" width="100%">';
                print '<tbody>';

                    print '<tr>';
                        print '<th align="left">'.$langs->trans('vehicule').'</th>';
                        print '<td >'.$vehicules->select_with_filter().'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<th align="left">'.$langs->trans('label_typeintervention').'</th>';
                        print '<td >'.$typeintervention->select_with_filter().'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<th align="left">'.$langs->trans('prix_inter').'</th>';
                        print '<td ><input type="number" step="0.001" class="" id="prix" name="prix"  autocomplete="off"/>';
                        print '   ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')';
                        print '</td>';
                    print '</tr>';
                    print '<tr>';
                        print '<td align="left">'.$langs->trans('datevalidate').'</td>';
                        print '<td ><input type="text" value="'.date("d/m/Y").'" id="datevalidate" class="datepickerparc"  name="datevalidate"  autocomplete="off"/>';
                        print '</td>';
                    print '</tr>';
                print '</tbody>';
            print '</table>';

            print '<div class="div_2"> ';
                print '<div class="div_left"> ';
                    print '<div class="title_div"> <span>'.$langs->trans("info_suplm").'</span> </div>';
                    print '<table class="noborder" width="100%">';
                        print '<tbody>';
                            print '<tr>';
                                print '<td align="left">'.$langs->trans('date').'</td>';
                                print '<td ><input type="text" value="'.date("d/m/Y").'" id="date" class="datepickerparc"  name="date"  autocomplete="off"/>';
                                print '</td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left">'.$langs->trans('acheteur').'</td>';
                                print '<td> <span id="acheteur"></span> </td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left">'.$langs->trans('fournisseur').'</td>';
                                print '<td >'.$vehicules->select_fournisseur().'</td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left">'.$langs->trans('ref_facture').'</td>';
                                print '<td ><input type="text" class="" id="ref_facture"  name="ref_facture"  autocomplete="off"/>';
                                print '</td>';
                            print '</tr>';


                        print '</tbody>';
                    print '</table>';
                print '</div>';

                print '<div class="div_right"> ';
                    print '<div class="title_div"> <span>'.$langs->trans("detail_kilom").'</span> </div>';
                    print '<table class="noborder" width="100%">';
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
            print '</div>';

            print '<div style="clear:both;"></div>';
            print '<div id="sevices_inclus">';
                $head = service_inclus();
                print $head;
                print '<div id="tab_services">';
                    print '<table class="noborder" style="width:100%;" >';
                        print '<tbody id="tr_services">';
                            print '<tr>';
                                print '<th align="left" >'.$langs->trans('service').'</th>';
                                print '<th align="left" >'.$langs->trans('notes').'</th>';
                                print '<th align="center" >'.$langs->trans('couts_estime').'</th>';
                            print '</tr>';
                        print '</tbody>';
                        print '<tr id="total_services">';
                            print '<td colspan="2">';
                                print '<b>'.$langs->trans("Total").':</b>';
                            print '</td>';
                            print '<td align="center">';
                                print '<input type="hidden" id="sum_total" value="0" >';
                                print '<strong>'.number_format($total,2,',',' ').'</strong>';
                                print '<span> &nbsp;('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</span>';
                            print '</td>';
                        print '</tr>';
                    print '</table>';
                    print '<div class="text_right" > <a id="add_lign_service">'.$langs->trans('add_lign').'</a></div>';
                print '</div>';

            print '</div>';

            print '<div id="notes">';
                print '<div class="cnd">'.$langs->trans('notes').':</div>';
                print '<div class="txt_condition"><textarea name="notes" class="notes"  ></textarea></div>';
            print '</div>';

        $object = new interventions_parc($db);
        if($extrafields->attributes[$object->table_element]['label']){
            print '<div class="fichecenter">';
                print '<div class="topheaderrecrutmenus" style="text-align:left !important"><span>'.$langs->trans('champs_add').'</span></div>';
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