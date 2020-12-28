<?php

if ($action == 'update' && $request_method === 'POST') {

    $page  = GETPOST('page');

    // $d1 = GETPOST('debut');
    // $f1 = GETPOST('fin');
    $id=GETPOST('id');

    $vehicule       = GETPOST('vehicule');
    $kilometre      = GETPOST('kilometre');
    $type           = GETPOST('typeintervention');
    $prix           = GETPOST('prix');
    $notes          = GETPOST('notes');
    $ref_facture    = GETPOST('ref_facture');
    $fournisseur    = GETPOST('fournisseur');
    $date = '';
    if(GETPOST('date')){
        $date = explode('/', GETPOST('date'));
        $date = $date[2].'-'.$date[1].'-'.$date[0];
    }


    $services=NULL;
    if(count(GETPOST('services')) > 0){
        $services = json_encode(GETPOST('services'));
    }
    //  new condition by imane*******


    $data = array(
        'vehicule'          =>  $vehicule,
        'typeintervention'  =>  $type,
        'prix'              =>  $prix,
        'date'              =>  $date,
        'notes'             =>  addslashes($notes),
        'ref_facture'       =>  $ref_facture,
        'fournisseur'       =>  $fournisseur,
        'kilometrage'       =>  $kilometre,
        'service_inclus'    =>  $services,
    );


    $object = new interventions_parc($db);
    $object->fetch($id);

    $old_kilometre = $object->kilometrage;

    $object->vehicule = $vehicule;
    $object->typeintervention = $type;
    $object->prix  = $prix;
    $object->date = $date;
    $object->notes = addslashes($notes);
    $object->ref_facture = $ref_facture;
    $object->fournisseur = $fournisseur;
    $object->kilometrage = $kilometre;
    $object->service_inclus = $services;

    $ret = $extrafields->setOptionalsFromPost(null, $object);


    if(GETPOST('datevalidate')){
        $date2 = explode('/', GETPOST('datevalidate'));
        $datevalidate = $date2[2].'-'.$date2[1].'-'.$date2[0];
        $object->datevalidate = $datevalidate;
    }

    $object->checkmail = NULL;

    // print_r($data);die;
    $isvalid = $object->update($id);
    // $composantes_new = (GETPOST('composantes_new'));
    // $composantes = (GETPOST('composantes'));
    // $composants_deleted = explode(',', GETPOST('composants_deleted'));

    if ($isvalid > 0) {
        $costs = new costsvehicule($db);
        $costs->fetchAll('','',0,0,'and id_intervention ='.$id);
        $elem=$costs->rows[0];
        $cout = new costsvehicule($db);

        if($elem->rowid){
            $cout->fetch($elem->rowid);
        }
        $typeintervention->fetch($type);

        $cout->vehicule = $vehicule;
        $cout->type = $typeintervention->label;
        $cout->id_intervention = $id;
        $cout->prix = $prix;
        $cout->date = $date;



        if($elem->rowid){
            $cout->update($elem->rowid);
        }else{
            $cout->create(1);
        }
        // create kilometrage
            //  new condition by Imane*******
        if($kilometre && $kilometre != $old_kilometre){
            $kilo = new kilometrage($db);
            $kilo->vehicule = $vehicule;
            $kilo->kilometrage = $kilometre;
            $kilo->date = $date;

            $test=$kilo->create(1);

            if($test){
                $max = $kilometrage->Max_kilometrage($vehicule);
                $objvehcul = new vehiculeparc($db);
                $objvehcul->fetch($vehicule);
                $objvehcul->kilometrage = $max;
                $objvehcul->update($vehicule);
            }
        }

        header('Location: ./card.php?id='.$id);
        exit;
    }    else {
        header('Location: ./card.php?id='. $id .'&update=0');
        exit;
    }
}


if($action == "edit"){

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data"  class="card_interv" >';

        print '<input type="hidden" name="action" value="update" />';
        print '<input type="hidden" name="id" value="'.$id.'" />';
        print '<input type="hidden" name="page" value="'.$page.'" />';

        print '<div class="title_div"> <span>'.$langs->trans("detail_inter").'</span> </div>';
        print '<table class="noborder" width="100%">';
            print '<tbody>';
                $intervention->fetch($id);
                $item = $intervention;

                $extrafields = new ExtraFields($db);
                $object = new interventions_parc($db);

                $object->fetch($id);

                $extrafields = new ExtraFields($db);
                $extrafields->fetch_name_optionals_label($object->table_element);
                // $object->fetch($item->rowid);
                $object->fetch_optionals();


                print '<tr>';
                    print '<td align="left"><b>'.$langs->trans('vehicule').'</b></td>';
                    print '<td >'.$vehicules->select_with_filter($item->vehicule).'</td>';
                print '</tr>';

                print '<tr>';
                    print '<td align="left"><b>'.$langs->trans('label_typeintervention').'</b></td>';
                    print '<td >'.$typeintervention->select_with_filter($item->typeintervention).'</td>';
                print '</tr>';

                print '<tr>';
                    print '<td align="left"><b>'.$langs->trans('prix_inter').'</b></td>';
                    print '<td ><input type="number" step="0.001" class="" id="prix" name="prix" value="'.$item->prix.'" autocomplete="off"/>';
                    print ' &nbsp;('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')';
                    print '</td>';
                print '</tr>';
                $datevalidate='';
                if(!empty($item->datevalidate)){
                    $date2= explode('-', $item->datevalidate);
                    $datevalidate=$date2[2].'/'.$date2[1].'/'.$date2[0];
                }
                print '<tr>';
                    print '<td align="left" >'.$langs->trans('datevalidate').'</td>';
                    print '<td ><input type="text" class="datepickerparc" id="datevalidate" value="'.$datevalidate.'"  name="datevalidate"  autocomplete="off"/>';
                    print '</td>';
                print '</tr>';
            print '</tbody>';
        print '</table>';

        print '<div class="div_2"> ';

            print '<div class="div_left"> ';
                print '<div class="title_div"> <span>'.$langs->trans("info_suplm").'</span> </div>';
                print '<table class="noborder" width="100%">';
                    print '<tbody>';
                        $date= explode('-', $item->date);
                        $date=$date[2].'/'.$date[1].'/'.$date[0];
                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('date').'</td>';
                            print '<td ><input type="text" class="datepickerparc" id="date" value="'.$date.'"  name="date"  autocomplete="off"/>';
                            print '</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('acheteur').'</td>';
                            $vehicules->fetch($item->vehicule);
                            $user_->fetch($vehicules->conducteur);
                            print '<td> <span id="acheteur">'.$user_->getNomUrl(1).'</span> </td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('fournisseur').'</td>';
                            print '<td >'.$vehicules->select_fournisseur($item->fournisseur).'</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td align="left">'.$langs->trans('ref_facture').'</td>';
                            print '<td ><input type="text" class="" id="ref_facture" value="'.$item->ref_facture.'" name="ref_facture"  autocomplete="off"/>';
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
                                print '<input type="number" name="kilometre" step="0.001" value="'.$item->kilometrage.'" min="0" id="kilometre">';
                                print '<span id="unite">'.$vehicules->unite.'</span>';
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
                            if($item->service_inclus){
                                $total = 0;
                                $services = json_decode($item->service_inclus);
                                if($services){
                                // print_r($services);die;
                                    foreach ($services as $key => $value){
                                        $total+=$value->prix;
                                        print '<tr id="'.$key.'">';
                                            print '<td class="type_service"><select class="type_service" name="services['.$key.'][type]">'.$typeintervention->get_types($value->type).'</select></td>';
                                            print '<td class="note_service"><input type="text" name="services['.$key.'][note]" value="'.$value->note.'"></td>';
                                            print '<td class="prix_service"><input type="text" onchange="total_services(this)" required name="services['.$key.'][prix]" value="'.$value->prix.'">';
                                            print '<a class="delete"  onclick="delete_tr(this)" ><img src="'.dol_buildpath('/parcautomobile/img/delete.png',2).'"></a>';
                                            print '</td>';
                                        print '</tr>';
                                    }
                                }
                            }
                        // }
                    print '</tbody>';
                    print '<tr id="total_services">';
                        print '<td colspan="2" align="right">';
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
            print '<div class="txt_condition"><textarea name="notes" class="notes"  >'.$item->notes.'</textarea></div>';
        print '</div>';


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
                print '<input type="submit" style="display:none" id="sub_valid" value="'.$langs->trans('Validate').'" style="" name="bouton" class="butAction" />';
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

<script>
    $(document).ready(function() {
        $('#btn_valid').click(function(){
            $('#sub_valid').trigger('click');
        })
    })
</script>
