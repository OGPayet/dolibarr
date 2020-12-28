<?php

if ($action == 'update' && $request_method === 'POST') {

    $page  = GETPOST('page');

    // $d1 = GETPOST('debut');
    // $f1 = GETPOST('fin');
    $id=GETPOST('id');

$date_fac = dol_mktime(0, 0, 0, GETPOST('date_facmonth', 'int'), GETPOST('date_facday', 'int'), GETPOST('date_facyear', 'int'));
$date_f = dol_mktime(0, 0, 0, GETPOST('date_fmonth', 'int'), GETPOST('date_fday', 'int'), GETPOST('date_fyear', 'int'));
$date_d = dol_mktime(0, 0, 0, GETPOST('date_dmonth', 'int'), GETPOST('date_dday', 'int'), GETPOST('date_dyear', 'int'));

     $ref_contrat = GETPOST('ref_contrat');
        $vehicule_ = GETPOST('vehicule');
        $type = GETPOST('typecontrat');
        $activation_cout = GETPOST('activation_cout');
        $type_montant = GETPOST('type_montant');
        $montant_recurrent = GETPOST('montant_recurrent');
        $conditions = GETPOST('conditions');
        // if(GETPOST('date_fac')){
        //     $date = explode('/', GETPOST('date_fc'));
        //     $date_fc=$date[2].'-'.$date[1].'-'.$date[0];
        // }
        if(GETPOST('date_f')){
            $date = explode('/', GETPOST('date_f'));
            $date_fin=$date[2].'-'.$date[1].'-'.$date[0];
        }
        if(GETPOST('date_d')){
            $date = explode('/', GETPOST('date_d'));
            $date_db=$date[2].'-'.$date[1].'-'.$date[0];
        }
        $kilometre   = GETPOST('kilometrage');
        $responsable = GETPOST('responsable');
        $fournisseur = GETPOST('fournisseur');
        $conducteur  = GETPOST('conducteur');
        $dif=date_diff(date_create($date_fin),date_create($date_db));
        $jour = $dif->format("%a");
        $etape = GETPOST('etape');
        if($jour == 0){
            $etape ='expire';
        }
        elseif($jour <= 15){
            $etape ='expire_bientot';
        }
        else{
            $etape ='encours';
        }
        $services=NULL;
        if(!empty(GETPOST('services'))){
            $services = json_encode(GETPOST('services'));
        }
        if(GETPOST('element_delete')){
            $data_deleted = explode(',', GETPOST('element_delete'));
        }
        // $data = array(
        //     'ref_contrat'       =>  $ref_contrat,
        //     'vehicule'          =>  $vehicule_,
        //     'typecontrat'       =>  $type,
        //     'activation_couts'  =>  $activation_cout,
        //     'type_montant'      =>  $type_montant,
        //     'montant_recurrent' =>  $montant_recurrent,
        //     'date_facture'      =>  $date_fac,
        //     'date_fin'          =>  $date_f,
        //     'date_debut'        =>  $date_d,
        //     'etat'              =>  $etape,
        //     'kilometrage'       =>  $kilometre,
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
        $object->kilometrage       =  $kilometre;
        $object->responsable       =  $responsable;
        $object->fournisseur       =  $fournisseur;
        $object->conducteur        =  $conducteur;
        $object->condition         =  addslashes($conditions);
        $object->services_inclus   =  $services;

        // print_r($object);die();
        $ret = $extrafields->setOptionalsFromPost(null, $object);


    $isvalid = $object->update($id);
    if ($isvalid > 0) {
        // $object->fetch($id);
        $typecontrat->fetch($object->typecontrat);
        $couts_recurrent =[];
        if(!empty($object->couts_recurrent)){
            $couts_recurrent=explode(',', $object->couts_recurrent);
        }

        if($data_deleted){
            foreach ($data_deleted as $key => $value) {
                if(in_array($value, $couts_recurrent)){
                    unset($couts_recurrent[array_search($value,$couts_recurrent)]);
                    if(count($couts_recurrent) > 0){
                        $couts_recurrent = implode(',', $couts_recurrent);
                    }else{
                        $couts_recurrent = "";
                    }
                    $object->couts_recurrent = $couts_recurrent;

                    $object->update($id);
                }
                $costs->fetch($value);
                $costs->delete();
            }
        }
        $costs->fetchAll('','',0,0,'and id_contrat ='.$id);
        $elem=$costs->rows[0];
        // $typecontrat->fetch($type);
        $cout = new costsvehicule($db);
        $cout->fetch($elem->rowid);

        $data_cost=[
            'vehicule'   => $vehicule_,
            'type'       => $typecontrat->label,
            'id_contrat' => $id,
            'prix'       => $contrat->activation_couts,
        ];

        $cout->vehicule = $vehicule_;
        $cout->type = $typecontrat->label;
        $cout->id_contrat = $id;
        $cout->date = date('Y-m-d');
        $cout->prix = $contrat->activation_couts;

        $cout->update($elem->rowid);

        if( GETPOST('couts')){
            if(count(GETPOST('couts')) > 0){
                $couts =  GETPOST('couts');
                foreach ($couts as $key => $value) {
                    $d = explode('/', $value['date']);
                    $date_c=$d[2].'-'.$d[1].'-'.$d[0];
                    $couts = new costsvehicule($db);

                    $couts->vehicule = $vehicule_;
                    $couts->type = $typecontrat->label;
                    $couts->id_contrat = $id;
                    $couts->prix = $value['prix'];
                    $couts->date = $date_c;

                    // $data_cout=[
                    //     'id_contrat' => $id,
                    //     'vehicule'   => $vehicule_,
                    //     'type'       => $typecontrat->label,
                    //     'date'       => $date_c,
                    //     'prix'       => $value['prix'],
                    // ];

                    $dd=$couts->create(1);
                    array_push($couts_recurrent, $dd);
                }
            }
        }

        if($couts_recurrent){
            $couts_recurrent=implode(',', $couts_recurrent);
            // $object = new contrat_parc($db);
            $object->couts_recurrent = $couts_recurrent;
            $test =  $object->update($id);
        }
        if(GETPOST('couts_edit')){
            $couts_edit = GETPOST('couts_edit');
            foreach ($couts_edit as $key => $value){
                $d = explode('/', $value['date']);
                $date_c=$d[2].'-'.$d[1].'-'.$d[0];


                $cot = new costsvehicule($db);
                $cot->fetch($key);
                $cot->vehicule = $vehicule_;
                $cot->type = $typecontrat->label;
                $cot->id_contrat = $id;
                $cot->prix = $value['prix'];
                $cot->date = $date_c;



                $cot->update($key);
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

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data"  class="card_contract" >';

    print '<input type="hidden" name="action" value="update" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
    print '<table class="border nc_table_" width="100%">';
        print '<tbody>';
                print '<tr>';
                $contrat->fetchAll('','',0,0,'and rowid ='.$id);
                $item = $contrat->rows[0];

                $object = new contrat_parc($db);
                $object->fetch($item->rowid);

                $extrafields = new ExtraFields($db);
                $extrafields->fetch_name_optionals_label($object->table_element);
                // $object->fetch($item->rowid);
                $object->fetch_optionals();


                // print_r($iconv(in_charset, out_charset, str)tem);die();
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
                                $statuts .= '<input type="radio" id="reception" style="display:none;" value="reception" name="etape" class="etapes">';
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

                            $statuts = str_replace('<input type="radio" id="'.$item->etat.'"', '<input type="radio" id="'.$item->etat.'" checked ', $statuts);
                            $dif=date_diff(date_create($item->date_fin),date_create($item->date_debut));
                            $jour = $dif->format("%a");
                            if($jour == 0){
                                $statuts = str_replace('<input type="radio" id="expire"', '<input type="radio" id="expire" checked ', $statuts);
                            }
                            if($jour <= 15){
                                $statuts = str_replace('<input type="radio" id="expire_bientot"', '<input type="radio" id="expire_bientot" checked ', $statuts);

                            }

                            print $statuts;
                        print '</div>';
                    print '</td>';
                print '</tr>';

        print '</tbody>';
    print '</table>';
    print '<div class="cl_parent">';
        print '<div class="left">';
            print '<table class="border nc_table_" width="100%">';
                print '<tbody>';

                    print '<tr>';
                        print '<td align="left">'.$langs->trans('vehicule').'</td>';
                        print '<td >'.$vehicule->select_with_filter($item->vehicule).'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('label_typecontrat').'</td>';
                        print '<td>'.$typecontrat->select_with_filter($item->typecontrat).'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left">'.$langs->trans('responsable').'</td>';
                        print '<td >'.$vehicule->select_conducteur($item->responsable,'responsable').'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('activation_cout').'</td>';
                        print '<td ><input type="number" step="0.001" value="'.$item->activation_couts.'" class="" id="activation_cout" name="activation_cout"  autocomplete="off"/>';
                        print '('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')';
                        print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left">'.$langs->trans('montant_recurrent').'</td>';
                        print '<td id="type_montant_">';
                            print '<input type="number" step="0.001" class="" value="'.$item->montant_recurrent.'" id="montant_recurrent" name="montant_recurrent"  autocomplete="off"/>';
                            // print ' ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')';
                            print $contrat->types_montant($item->type_montant);
                        print '</td>';
                    print '</tr>';

                    $date = explode('-', $item->date_fin);
                    $date_f = $date[2].'/'.$date[1].'/'.$date[0];

                    $date = explode('-', $item->date_debut);
                    $date_d = $date[2].'/'.$date[1].'/'.$date[0];

                    print '<tr>';
                        print '<td align="left">'.$langs->trans('date_d').'</td>';
                        print '<td >';
                        // '<input type="text" class="datepickerparc" id="date_d" value="'.$date_d.'" name="date_d"  autocomplete="off"/>';
            print $form->selectDate($item->date_debut ? $item->date_debut : -1, 'date_d', 0, 0, 0, '', 1, 0);

                        print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left">'.$langs->trans('date_f').'</td>';
                        print '<td >';
                        // '<input type="text" class="datepickerparc" id="date_f" value="'.$date_f.'" name="date_f"  autocomplete="off"/>';
            print $form->selectDate($item->date_fin ? $item->date_fin : -1, 'date_f', 0, 0, 0, '', 1, 0);
                        print '</td>';
                    print '</tr>';

                    $date = explode('-', $item->date_facture);
                    $date_fac = $date[2].'/'.$date[1].'/'.$date[0];

                    print '<tr>';
                        print '<td align="left">'.$langs->trans('date_fac').'</td>';
                        print '<td >';
                        // '<input type="text" class="datepickerparc" id="date_fac" value="'.$date_fac.'" name="date_fac"  autocomplete="off"/>';
            print $form->selectDate($item->date_facture ? $item->date_facture : -1, 'date_fac', 0, 0, 0, '', 1, 0);
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
                            $vehicule->fetch($item->vehicule);
                       print '<td>';
                            print '<input type="number" name="kilometrage" step="0.001" value="'.$item->kilometrage.'" min="0" id="kilometre">';
                            print '<span id="unite">'.$vehicule->unite.'</span>';
                        print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('fournisseur').'</td>';
                        print '<td >'.$vehicule->select_fournisseur($item->fournisseur).'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('conducteur').'</td>';
                        print '<td >'.$vehicule->select_conducteur($item->conducteur).'</td>';
                    print '</tr>';


                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('ref_contrat').'</td>';
                        print '<td >';
                            print '<input type="text" name="ref_contrat" value="'.$item->ref_contrat.'" id="ref_contrat">';
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
                        print '<th align="center" >'.$langs->trans('couts_estime').' ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</th>';
                    print '</tr>';
                    if($item->services_inclus){
                        $services = json_decode($item->services_inclus);
                        $total = 0;
                        foreach ($services as $key => $value) {
                            print '<tr id="'.$key.'">';
                                $prix = ($value->prix ? $value->prix : 0);
                                print '<td class="type_service"><select class="type_service" name="services['.$key.'][type]">'.$typeintervention->get_types($value->type).'</select></td>';
                                print '<td class="note_service"><input type="text" name="services['.$key.'][note]" value="'.$value->note.'"></td>';
                                print '<td class="prix_service"><input type="number" step="0.001" onchange="total_services(this)" required name="services['.$key.'][prix]" value="'.$prix.'">';
                                print '   <a class="delete" onclick="delete_tr(this)" ><img src="'.dol_buildpath('/parcautomobile/img/delete.png',2).'"></a>';
                                print '</td>';
                            print '</tr>';
                            if(!empty($prix)){
                                $total += $prix;
                            }
                        }
                    }
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
            // print '<div id="total_services" class="cnd">';
            //     print '<div>';
            //     print '</div>';
            // print '</div>';
            print '<div class="text_right" ><a id="add_lign_service">'.$langs->trans('add_lign').'</a> </div>';
        print '</div>';
        print '<div id="tab_couts" style="display:none;width:100%;">';
            print '<table class="noborder" >';
                print '<tbody id="tr_couts">';
                    print '<input type="hidden" name="element_delete" class="element_delete" >';
                    print '<tr>';
                        print '<th align="left" >'.$langs->trans('date').'</th>';
                        print '<th align="left" >'.$langs->trans('prix').'  &nbsp;('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</th>';
                    print '</tr>';
                    if(!empty($item->couts_recurrent)){
                        $couts_recurrent = explode(',', $item->couts_recurrent);
                        $total_1=0;
                        if(count($couts_recurrent) > 0){
                            foreach ($couts_recurrent as $value) {
                                if(!empty($value)){
                                    $costs->fetch($value);
                                    $d=explode('-', $costs->date);
                                    $total_1+=$costs->prix;
                                    $date_c = $d[2].'/'.$d[1].'/'.$d[0];
                                    print '<tr id="cout_'.$costs->rowid.'">';
                                        print '<td class="date_cout">';
                                            print '<input type="text" class="datepickerparc" name="couts_edit['.$costs->rowid.'][date]" value="'.$date_c.'" >';
                                        print '</td>';
                                        print '<td class="prix_couts">';
                                            print '<input type="number" onchange="total_couts(this)" required name="couts_edit['.$costs->rowid.'][prix]" value="'.$costs->prix.'" step="0.001" min="0">';
                                            print '<a  class="delete" data-id="'.$costs->rowid.'" onclick="delete_tr(this)" ><img src="'.dol_buildpath('/parcautomobile/img/delete.png',2).'"></a>';
                                        print '</td>';
                                    print '</tr>';
                                }
                            }
                        }
                    }
                print '</tbody>';
                print '<tr id="total_couts">';
                    print '<td>';
                        print '<b>'.$langs->trans("Total").':</b>';
                    print '</td>';
                    print '<td align="left">';
                        print '<input type="hidden"  value="0" >';
                        print '<strong>'.number_format($total_1,2,',',' ').'</strong>';
                        print '<span> &nbsp;('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</span>';
                    print '</td>';
                print '</tr>';
            print '</table>';
            print ' <div class="text_right"> <a id="add_lign_cout">'.$langs->trans('add_lign').'</a></div>';
        print '</div>';
    print '</div>';

    print '<div id="conditions">';
        print '<div class="cnd">'.$langs->trans('conditions').':</div>';
        print '<div class="txt_condition"><textarea name="conditions" class="conditions" style="border:none !important;" >'.$item->condition.'</textarea></div>';
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
    // Actions

    print '<table class="" width="100%">';
    print '<tr>';
        print '<td colspan="2" >';
            print '<br>';
            print '<input type="submit"  style="display:none;" id="sub_valid" value="'.$langs->trans('Validate').'" style="" name="bouton" class="butAction" />';
            print '<a  class="butAction" id="btn_valid">'.$langs->trans('Validate').'</a>';

            print '<a href="./index.php?page='.$page.'" class="butAction">'.$langs->trans('Cancel').'</a>';
        print '</td>';
    print '</tr>';
    print '</table>';

    print '</form>';
    print '<div id="lightbox" style="display:none;"><p>X</p><div id="content"><img src="" /></div></div>';

    ?>
    <?php
}

?>
<style>

</style>
<script>
    $(document).ready(function() {
        $('#btn_valid').click(function(){
            $('#sub_valid').trigger('click');
        })
    })
</script>
