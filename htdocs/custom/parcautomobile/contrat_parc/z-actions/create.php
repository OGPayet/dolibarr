<?php

    if ($action == 'create' && $request_method === 'POST') {


        $ref_contrat = GETPOST('ref_contrat');
        $vehicule_ = GETPOST('vehicule');
        $kilometrage = GETPOST('kilometre');
        $type = GETPOST('typecontrat');
        $activation_cout = GETPOST('activation_cout');
        $type_montant = GETPOST('type_montant');
        $montant_recurrent = GETPOST('montant_recurrent');
        $etape = GETPOST('etape');
        $conditions = GETPOST('conditions');
        if(GETPOST('date_fac')){
            $date = explode('/', GETPOST('date_fac'));
            $date_fac=$date[2].'-'.$date[1].'-'.$date[0];
        }
        if(GETPOST('date_f')){
            $date = explode('/', GETPOST('date_f'));
            $date_f=$date[2].'-'.$date[1].'-'.$date[0];
        }
        if(GETPOST('date_d')){
            $date = explode('/', GETPOST('date_d'));
            $date_d=$date[2].'-'.$date[1].'-'.$date[0];
        }
        $services=NULL;
        if(GETPOST('services')){
            $services = json_encode(GETPOST('services'));
        }
        $couts=json_encode(GETPOST('couts'));
        $responsable = GETPOST('responsable');
        $fournisseur = GETPOST('fournisseur');
        $conducteur  = GETPOST('conducteur');

        // $insert = array(
        //     'ref_contrat'       =>  $ref_contrat,
        //     'vehicule'          =>  $vehicule_,
        //     'kilometrage'       =>  $kilometrage,
        //     'typecontrat'       =>  $type,
        //     'activation_couts'  =>  $activation_cout,
        //     'type_montant'      =>  $type_montant,
        //     'montant_recurrent' =>  $montant_recurrent,
        //     'date_facture'      =>  $date_fac,
        //     'date_fin'          =>  $date_f,
        //     'date_debut'        =>  $date_d,
        //     'etat'              =>  $etape,
        //     'responsable'       =>  $responsable,
        //     'fournisseur'       =>  $fournisseur,
        //     'conducteur'        =>  $conducteur,
        //     'condition'         =>  addslashes($conditions),
        //     'services_inclus'   =>  addslashes($services),
        // );

        $object = new contrat_parc($db);
        $object->fetch($id);

        $object->ref_contrat       =  $ref_contrat;
        $object->vehicule          =  $vehicule_;
        $object->typecontrat       =  $type;
        $object->activation_couts  =  $activation_cout;
        $object->type_montant      =  $type_montant;
        $object->montant_recurrent =  $montant_recurrent;
        $object->date_facture      =  $date_fac;
        $object->date_fin          =  $date_f;
        $object->date_debut        =  $date_d;
        $object->etat              =  $etape;
        $object->kilometrage       =  $kilometrage;
        $object->responsable       =  $responsable;
        $object->fournisseur       =  $fournisseur;
        $object->conducteur        =  $conducteur;
        $object->condition         =  addslashes($conditions);
        $object->services_inclus   =  $services;

        $ret = $extrafields->setOptionalsFromPost(null, $object);

        $avance = $object->create(1);

        // If no SQL error we redirect to the request card
        if ($avance > 0 ) {
            $contrat->fetch($avance);
            $typecontrat->fetch($contrat->typecontrat);
            $data_cost=[
                'vehicule'=>$contrat->vehicule,
                'type'=>$typecontrat->label,
                'id_contrat'=>$avance,
                'date'=>date('Y-m-d'),
                'prix'=>$contrat->activation_couts,
            ];

            $cout = new costsvehicule($db);

            $cout->vehicule = $vehicule_;
            $cout->type = $typecontrat->label;
            $cout->id_contrat = $avance;
            $cout->date = date('Y-m-d');
            $cout->prix = $contrat->activation_couts;
            $ret = $extrafields->setOptionalsFromPost(null, $cout);

            $d = $cout->create(1);

            $couts_recurrent=[];
            if( GETPOST('couts')){
                if(count(GETPOST('couts')) > 0){
                    $couts =  GETPOST('couts');
                    foreach ($couts as $key => $value) {
                        $d = explode('/', $value['date']);
                        $date_c=$d[2].'-'.$d[1].'-'.$d[0];

                        $couts = new costsvehicule($db);
                        $data_cout=[
                            'id_contrat' => $id,
                            'vehicule'   => $vehicule_,
                            'type'       => $typecontrat->label,
                            'date'       => $date_c,
                            'prix'       => $value['prix'],
                        ];

                        $couts->id_contrat = $avance;
                        $couts->vehicule = $vehicule_;
                        $couts->type = $typecontrat->label;
                        $couts->date = $date_c;
                        $couts->prix = $value['prix'];

                        $ret = $extrafields->setOptionalsFromPost(null, $couts);
                        $dd=$couts->create(1);
                        $couts_recurrent[]=$dd;
                    }
                    
                }
            }
            if(count($couts_recurrent)>0){
                $couts_recurrent=implode(',', $couts_recurrent);
                $objcontr= new contrat_parc($db);
                $objcontr->fetch($avance);
                $objcontr->couts_recurrent =$couts_recurrent;
                $ddd = $objcontr->update($avance);
            }
            header('Location: ./card.php?id='. $avance.'&action=edit');
            exit;
        } 
        else {
            header('Location: card.php?action=request&error=SQL_Create&msg='.$parcautomobile->error);
            exit;
        }
    }

    if($action == "add"){
        $id_poste=GETPOST('poste');
        print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="card_contract">';

        print '<input type="hidden" name="action" value="create" />';
        print '<input type="hidden" name="page" value="'.$page.'" />';
        print '<input type="hidden" name="poste" value="'.$id_poste.'" />';
        print '<table class="border nc_table_" width="100%">';

            print '<tbody>';
                print '<tr>';
                     print '<td colspan="2" class="td_h" >';
                        print '<div class="" style="float:left">';
                        if($item->etat == 'ferme'){
                            $stl_encours='display:initial';
                            $stl_ferme='display:none';
                        }else{
                            $stl_encours='display:none';
                            $stl_ferme='display:initial';
                        }
                            print '<a class="butEtat" id="fermer" style="'.$stl_ferme.'">'.$langs->trans('fermer').'</a>';
                            print '<a class="butEtat" id="passer_encours" style="'.$stl_encours.'">'.$langs->trans('passer_encours').'</a>';
                            print '<a class="butEtat" id="renouveler" style="'.$stl_encours.'" >'.$langs->trans('renouveler').'</a>';
                        print '</div>';
                        print '<div class="alletapesrecru">';
                            $statuts = '<label class="etapes" >';
                                $statuts .= '<input type="radio" id="reception" checked style="display:none;" value="reception" name="etape" class="etapes">';
                                $statuts .= ' <span class="radio"></span>';
                                $statuts .= '<span style="font-size:14px"> '.$langs->trans("reception").'</span>';
                            $statuts .= '</label>';

                            $statuts .= '<label class="etapes" >';
                                $statuts .= '<input type="radio" id="encours" style="display:none;" value="encours" name="etape" class="etapes">';
                                $statuts .= ' <span class="radio"></span>';
                                $statuts .= '<span style="font-size:14px"> '.$langs->trans("encours").'</span>';
                            $statuts .= '</label>';

                            $statuts .= '<label class="etapes" >';
                                $statuts .= '<input type="radio" id="expire_bientot" style="display:none;" value="expire_bientot" name="etape" class="etapes">';
                                $statuts .= ' <span class="radio"></span>';
                                $statuts .= '<span style="font-size:14px"> '.$langs->trans("expire_bientot").'</span>';
                            $statuts .= '</label>';

                            $statuts .= '<label class="etapes" >';
                                $statuts .= '<input type="radio" id="expire" style="display:none;" value="expire" name="etape" class="etapes">';
                                $statuts .= ' <span class="radio"></span>';
                                $statuts .= '<span style="font-size:14px"> '.$langs->trans("expire").'</span>';
                            $statuts .= '</label>';

                            $statuts .= '<label class="etapes" >';
                                $statuts .= '<input type="radio" id="ferme" style="display:none;" value="ferme" name="etape" class="etapes">';
                                $statuts .= ' <span class="radio"></span>';
                                $statuts .= '<span style="font-size:14px"> '.$langs->trans("ferme").'</span>';
                            $statuts .= '</label>';

                            print $statuts;
                        print '</div>';
                    print '</td>';
                    // print '<td colspan="2" class="alletapesrecru">';
                    //     $statuts = '<label class="etapes" >';
                    //         $statuts .= '<input type="radio" id="reception" checked style="display:none;" value="reception" name="etape" class="etapes">';
                    //         $statuts .= ' <span class="radio"></span>';
                    //         $statuts .= '<span style="font-size:14px"> '.$langs->trans("reception").'</span>';
                    //     $statuts .= '</label>';

                    //     $statuts .= '<label class="etapes" >';
                    //         $statuts .= '<input type="radio" id="encours" style="display:none;" value="encours" name="etape" class="etapes">';
                    //         $statuts .= ' <span class="radio"></span>';
                    //         $statuts .= '<span style="font-size:14px"> '.$langs->trans("encours").'</span>';
                    //     $statuts .= '</label>';

                    //     $statuts .= '<label class="etapes" >';
                    //         $statuts .= '<input type="radio" id="expire" style="display:none;" value="expire" name="etape" class="etapes">';
                    //         $statuts .= ' <span class="radio"></span>';
                    //         $statuts .= '<span style="font-size:14px"> '.$langs->trans("expire").'</span>';
                    //     $statuts .= '</label>';

                    //     $statuts .= '<label class="etapes" >';
                    //         $statuts .= '<input type="radio" id="ferme" style="display:none;" value="ferme" name="etape" class="etapes">';
                    //         $statuts .= ' <span class="radio"></span>';
                    //         $statuts .= '<span style="font-size:14px"> '.$langs->trans("ferme").'</span>';
                    //     $statuts .= '</label>';

                    //     $statuts = str_replace('<input type="radio" id="1"', '<input type="radio" id="1" checked ', $statuts);
                    //     print $statuts;
                    // print '</td>';
                print '</tr>';
                
            print '</tbody>';
        print '</table>';

        print '<div class="cl_parent">';
            print '<div class="left">';
                print '<table class="border nc_table_" width="100%">';
                    print '<tbody>';

                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('vehicule').'</td>';
                            print '<td >'.$vehicule->select_with_filter().'</td>';
                        print '</tr>';

                       
                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('label_typecontrat').'</td>';
                            print '<td >'.$typecontrat->select_with_filter().'</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('responsable').'</td>';
                            print '<td >'.$vehicule->select_conducteur(0,'responsable').'</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('activation_cout').'</td>';
                            print '<td ><input type="number" step="0.001" class="" id="activation_cout" name="activation_cout"  autocomplete="off"/>';
                            print '</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('montant_recurrent').'</td>';
                            print '<td id="type_montant_">';
                                print '<input type="number" step="0.001" class="" id="montant_recurrent" name="montant_recurrent"  autocomplete="off"/>';
                                print $contrat->types_montant();
                            print '</td>';
                        print '</tr>';
                       
                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('date_d').'</td>';
                            print '<td ><input type="text" class="datepickerparc" id="date_d" value="'.date('d/m/Y').'" name="date_d"  autocomplete="off"/>';
                            print '</td>';
                        print '</tr>';
                        $date = date('d/m/Y', strtotime('+1 years'));
                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('date_f').'</td>';
                            print '<td ><input type="text" class="datepickerparc" id="date_f" value="'.$date.'"  name="date_f"  autocomplete="off"/>';
                            print '</td>';
                        print '</tr>';

                         print '<tr>';
                            print '<td align="left" >'.$langs->trans('date_fac').'</td>';
                            print '<td ><input type="text" class="datepickerparc" id="date_fac" value="'.date('d/m/Y').'" name="date_fac"  autocomplete="off"/>';
                            print '</td>';
                        print '</tr>';


                    print '</tbody>';
                print '</table>';
            print '</div>';

            print '<div class="d_right">';
                 print '<table class="border nc_table_" width="100%">';
                    print '<tbody>';
                       
                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('kilometr_contrat').'</td>';
                            print '<td>';
                                print '<input type="number" name="kilometre" step="0.001" min="0" id="kilometre">';
                                print '<span id="unite"></span>';
                            print '</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('fournisseur').'</td>';
                            print '<td >'.$vehicule->select_fournisseur().'</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('conducteur').'</td>';
                            print '<td >'.$vehicule->select_conducteur().'</td>';
                        print '</tr>';

                       

                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('ref_contrat').'</td>';
                            print '<td >';
                                print '<input type="text" name="ref_contrat"  id="ref_contrat">';
                            print '</td>';
                        print '</tr>';


                    print '</tbody>';
                print '</table>';
            print '</div>';
        print '</div>';
        
        print '<div style="clear:both;"></div>';
        print '<div id="sevices_inclus">';
            $head = service_inclus('contrats');
            print $head;
            print '<div id="tab_services">';
                print '<table class="noborder" style="width:100%;" >';
                    print '<tbody id="tr_services">';
                        print '<tr>';
                            print '<th align="left" >'.$langs->trans('service').'</th>';
                            print '<th align="left" >'.$langs->trans('notes').'</th>';
                            print '<th align="center" >'.$langs->trans('couts_estime').'  &nbsp;('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</th>';
                        print '</tr>';
                    print '</tbody>';
                    print '<tr id="total_services">';
                        print '<td colspan="2">';
                            print '<b>'.$langs->trans("Total").':</b>';
                        print '</td>';
                        print '<td align="center">';
                            print '<input type="hidden" id="sum_total" value="0" >';
                            print '<strong>'.number_format(0,2,',',' ').'</strong>';
                            print '<span> &nbsp;('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</span>';
                        print '</td>';
                    print '</tr>';
                print '</table>';
                // print '<div id="total_services"><b>'.$langs->trans("Total").':</b> <strong>'.number_format(0,2,',',' ').'</strong><input type="hidden" value="0" ></div>';
                print '<div class="text_right" ><a id="add_lign_service">'.$langs->trans('add_lign').'</a> </div>';
            print '</div>';

            print '<div id="tab_couts" style="display:none;width:100%;">';
                print '<table class="noborder" >';
                    print '<tbody id="tr_couts">';
                        print '<tr>';
                            print '<th align="left" >'.$langs->trans('date').'</th>';
                            print '<th align="left" >'.$langs->trans('prix').'  &nbsp;('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</th>';
                        print '</tr>';
                    print '</tbody>';
                    print '<tr id="total_couts">';
                        print '<td class="date_cout">';
                            print '<b>'.$langs->trans("Total").':</b>';
                        print '</td>';
                        print '<td align="left">';
                            print '<input type="hidden"  value="0" >';
                            print '<strong>'.number_format(0,2,',',' ').'</strong>';
                            print '<span> &nbsp;('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</span>';
                        print '</td>';
                    print '</tr>';
                print '</table>';
                print ' <div class="text_right"> <a id="add_lign_cout">'.$langs->trans('add_lign').'</a></div>';
            print '</div>';
        print '</div>';

        print '<div id="conditions">';
            print '<div class="cnd">'.$langs->trans('conditions').':</div>';
            print '<div class="txt_condition"><textarea name="conditions" class="conditions" style="border:none !important;" ></textarea></div>';
        print '</div>';

        $object = new contrat_parc($db);
        if($extrafields->attributes[$object->table_element]['label']){

            print '<div class="fichecenter">';    
                // print '<div class="topheaderrecrutmenus" style="text-align:left !important"><span>'.$langs->trans('champs_add').'</span></div>';
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
        // Actions
            print '<table class="" width="100%">';
                print '<tr>';
                    print '<td colspan="2" >';
                    print '<br>';
                    print '<input type="submit" style="display:none;" id="sub_valid" value="'.$langs->trans('Validate').'" name="bouton" class="butAction" />';
                    print '<a  class="butAction" id="btn_valid">'.$langs->trans('Validate').'</a>';

                    print '<a href="./index.php?page='.$page.'" class="butAction">'.$langs->trans('Cancel').'</a>';
                print '</tr>';
            print '</table>';

        print '</form>';
    }

?>
<style>
    .etapes:hover input ~ .radio {
      background-color: #ccc;
    }
    .etapes{
      cursor: pointer;
    }
   
</style>

<script>
    $(document).ready(function() {
        $('#btn_valid').click(function(){
            $('#sub_valid').trigger('click');
        })
    })
</script>

