<?php

if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {
    
    if (!$id || $id <= 0) {
        header('Location: ./card.php?action=request&error=dalete_failed&id='.$id);
        exit;
    }

    $page  = GETPOST('page');

    $intervention->fetch($id);
    $error = $intervention->delete();

    if ($error == 1) {
        $costs->fetchAll('','',0,0,'AND id_intervention ='.$id);
        $item=$costs->rows[0];
        $costs->fetch($item->rowid);
        $costs->delete();
        header('Location: index.php?delete='.$id.'&page='.$page);
        exit;
    }
    else {      
        header('Location: card.php?delete=1&page='.$page);
        exit;
    }
}


if( ($id && empty($action)) || $action == "delete" ){
    
    if($action == "delete"){
        print $form->formconfirm("card.php?id=".$id."&page=".$page,$langs->trans('Confirmation') , $langs->trans('msgconfirmdelet'),"confirm_delete", 'index.php?page='.$page, 0, 1);
    }

   
    $object = new interventions_parc($db);
    $object->fetch($id);
    $item = $object;

    $extrafields = new ExtraFields($db);
    $extrafields->fetch_name_optionals_label($object->table_element);
    $object->fetch($item->rowid);
    $object->fetch_optionals();
    
    
    dol_include_once('/parcautomobile/class/parcautomobile.class.php');
    $parcautomobile = new parcautomobile($db);
    $linkback = '<a href="./index.php?page='.$page.'">'.$langs->trans("BackToList").'</a>';
    print $parcautomobile->showNavigations($item, $linkback);

    

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" class="card_interv" >';

        print '<input type="hidden" name="confirm" itemue="no" id="confirm" />';
        print '<input type="hidden" name="id" value="'.$id.'" />';
        print '<input type="hidden" name="page" value="'.$page.'" />';
        print '<div class="title_div"> <span>'.$langs->trans("detail_inter").'</span> </div>';
        print '<table class="noborder" width="100%">';
            print '<tbody>';
               
                print '<tr>';
                    print '<td align="left" style="width: 20%;">'.$langs->trans('vehicule').'</td>';
                        $objvehicul = new vehiculeparc($db);
                        $objvehicul->fetch($item->vehicule);
                    print '<td >'.$objvehicul->get_nom_url($item->vehicule,1).'</td>';
                print '</tr>';
                $typeintervention->fetch($item->typeintervention);
                print '<tr>';
                    print '<td align="left" >'.$langs->trans('label_typeintervention').'</td>';
                    print '<td >'.$typeintervention->label.'</td>';
                print '</tr>';

                print '<tr>';
                    print '<td align="left" >'.$langs->trans('prix_inter').'</td>';
                    print '<td >'.number_format($item->prix,2,',',' ').'  ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</td>';
                print '</tr>';

                $datevalidate = '';
                if(!empty($item->datevalidate)){
                    $date2= explode('-', $item->datevalidate);
                    $datevalidate=$date2[2].'/'.$date2[1].'/'.$date2[0];
                }
                print '<tr>';
                    print '<td align="left" >'.$langs->trans('datevalidate').'</td>';
                    print '<td >'.$datevalidate.'</td>';
                print '</tr>';
            print '</tbody>';
        print '</table>';

        print '<div class="div_2"> ';
            print '<div class="div_left"> ';
                print '<div class="title_div"> <span>'.$langs->trans("info_suplm").'</span> </div>';
                print '<table class="noborder" width="100%">';
                    print '<tbody>';
                        
                        $date = '';
                        if(!empty($item->date)){
                            $date= explode('-', $item->date);
                            $date=$date[2].'/'.$date[1].'/'.$date[0];
                        }
                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('date').'</td>';
                            print '<td >'.$date.'</td>';
                        print '</tr>';
                        
                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('conducteur').'</td>';
                            $ache = '';
                            $vehicules->fetch($item->vehicule);
                            if(!empty($vehicules->conducteur)){
                                $user_->fetch($vehicules->conducteur);
                                $ache = $user_->getNomUrl(1);
                            }
                            print '<td> <span id="acheteur">'.$ache.'</span> </td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td align="left" >'.$langs->trans('fournisseur').'</td>';
                            $fours = '';
                            if(!empty($item->fournisseur)){
                                $soc->fetch($item->fournisseur);
                                $fours = $soc->getNomUrl(1);
                            }
                            print '<td >'.$fours.'</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td align="left">'.$langs->trans('ref_facture').'</td>';
                            print '<td >'.$item->ref_facture.'</td>';
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
                            print '<td >'.$item->kilometrage.' &nbsp;<span id="unite">'.$vehicules->unite.'</span></td>';
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
                            print '<th align="left" >'.$langs->trans('couts_estime').'</th>';
                        print '</tr>';
                        if(!empty($item->service_inclus)){
                            $total = 0;
                            $services = json_decode($item->service_inclus);
                            if($services){
                                foreach ($services as $key => $value) {
                                    $total+=$value->prix;

                                    print '<tr id="'.$key.'">';
                                    $typeintervention->fetch($value->type);
                                        print '<td class="type_service">'.$typeintervention->label.'</td>';
                                        print '<td class="note_service">'.$value->note;
                                        print '<td class="">'.number_format($value->prix,2,","," ").'</td>';
                                    print '</tr>';
                                }
                            }
                        }
                        print '<tr id="total_services">';
                            print '<td colspan="2"  align="right">';
                                print '<b>'.$langs->trans("Total").':</b>';
                            print '</td>';
                            print '<td align="left">';
                                print '<input type="hidden" id="sum_total" value="0" >';
                                print '<strong>'.number_format($total,2,',',' ').'</strong>';
                                print '<span> &nbsp;('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</span>';
                            print '</td>';
                        print '</tr>';
                        // }
                    print '</tbody>';
                print '</table>';
            print '</div>';
        print '</div>';

        print '<div id="notes">';
            print '<div class="cnd">'.$langs->trans('notes').':</div>';
            print '<div class="txt_condition" style="border: solid 1px rgba(0,0,0,.2);">'.$item->notes.'</div>';
        print '</div>';

            print '<div class="fichecenter">';    
                // print '<div class="liste_titre" style="text-align:left !important"><span>'.$langs->trans('champs_add').'</span></div>';
                print '<br><br>';
                print '<div class="div_extrafield">';
                    print '<table class="noborder centpercent" width="100%">';
                        print '<body>';
                            if($extrafields->attributes[$object->table_element]['label']){
                                $colspn = 1+count($extrafields->attributes[$object->table_element]['label']);
                                print '<tr class="liste_titre"> <th colspan="'.$colspn.'">'.$langs->trans("champs_add").'</th> </tr>';
                                include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';
                            }
                        print '</tbody>';
                    print '</table>';
                print '</div>';
            print '</div>';

        // Actions
        
        print '<table class="" width="100%">';
        print '<tr>';
            print '<td colspan="2" >';
                print '<br>';
                print '<a href="./card.php?id='.$id.'&action=edit" class="butAction">'.$langs->trans('Modify').'</a>';
                print '<a href="./card.php?id='.$id.'&action=delete" class="butAction butActionDelete">'.$langs->trans('Delete').'</a>';
                print '<a href="./index.php?page='.$page.'" class="butAction">'.$langs->trans('Cancel').'</a>';
                print '<a href="./card.php?id='.$item->rowid.'&action=pdf" class="butAction" style="float:right" target="_blank" >'.img_mime('test.pdf').' '.$langs->trans("export").'</a>';

            print '</td>';
        print '</tr>';
        print '</table>';

    print '</form>';
    
}

?>
<style>
    
</style>
