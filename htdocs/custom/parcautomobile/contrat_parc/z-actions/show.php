<?php

if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {

    if (!$id || $id <= 0) {
        header('Location: ./card.php?action=request&error=dalete_failed&id='.$id);
        exit;
    }

    $page  = GETPOST('page');

    $contrat->fetch($id);

    $error = $contrat->delete();

    if ($error == 1) {
        $costs->fetchAll('','',0,0,'AND id_contrat ='.$id);
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


    $contrat->fetch($id);
    $item = $contrat;



    dol_include_once('/parcautomobile/class/parcautomobile.class.php');
    $parcautomobile = new parcautomobile($db);
    $linkback = '<a href="./index.php?page='.$page.'">'.$langs->trans("BackToList").'</a>';
    print $parcautomobile->showNavigations($item, $linkback);





    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" class="card_contract">';

    print '<input type="hidden" name="confirm" itemue="no" id="confirm" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
    print '<table class="border" width="100%">';
         print '<tbody>';
                print '<tr>';
                $contrat->fetchAll('','',0,0,'and rowid ='.$id);
                $item = $contrat->rows[0];

                $object = new contrat_parc($db);
                $object->fetch($id);

                $extrafields = new ExtraFields($db);
                $extrafields->fetch_name_optionals_label($object->table_element);
                $object->fetch_optionals();

                    print '<td colspan="2" class="td_h">';

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
            print '<table class="border" width="100%">';
                print '<tbody>';
                    print '<tr>';
                        print '<td align="left">'.$langs->trans('vehicule').'</td>';
                        $objvehicul = new vehiculeparc($db);
                        $objvehicul->fetch($item->vehicule);
                        print '<td >'.$objvehicul->get_nom_url($item->vehicule,1).'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('label_typecontrat').'</td>';
                        $typecontrat->fetch($item->typecontrat);
                        print '<td >'.$typecontrat->label.'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('responsable').'</td>';
                        if($item->responsable > 0){
                            $user_->fetch($item->responsable);
                            print '<td >'.$user_->getNomUrl(1).'</td>';
                        }else
                            print '<td ></td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left">'.$langs->trans('activation_cout').'</td>';
                        print '<td >'.number_format($item->activation_couts,2,","," ").'('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('montant_recurrent').'</td>';
                        print '<td >';
                        print number_format($item->montant_recurrent,2,","," ").' ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).') &nbsp;&nbsp;';
                        if($item->type_montant)
                        print $langs->trans($item->type_montant);
                        print '</td>';
                    print '</tr>';

                    $date = explode('-', $item->date_fin);
                    $date_f = $date[2].'/'.$date[1].'/'.$date[0];

                    $date = explode('-', $item->date_debut);
                    $date_d = $date[2].'/'.$date[1].'/'.$date[0];

                    $date = explode('-', $item->date_facture);
                    $date_fac = $date[2].'/'.$date[1].'/'.$date[0];

                    print '<tr>';
                        print '<td align="left">'.$langs->trans('date_d').'</td>';
                        print '<td >'.$date_d.'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left">'.$langs->trans('date_f').'</td>';
                        print '<td >'.$date_f.'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left">'.$langs->trans('date_fac').'</td>';
                        print '<td >'.$date_fac.'</td>';
                    print '</tr>';

                print '</tbody>';
            print '</table>';
        print '</div>';
        print '<div class="d_right">';
            print '<table class="border" width="100%">';
                print '<tbody>';



                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('kilometr_contrat').'</td>';
                        print '<td >';
                            $vehicule->fetch($item->vehicule);
                            print '<span  id="kilometre">'.$item->kilometrage.'</span>';
                            print '<span id="unite"> '.$vehicule->unite.'</span>';
                            print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('fournisseur').'</td>';
                        if($item->fournisseur > 0){
                            $soc->fetch($item->fournisseur);
                            print '<td >'.$soc->getNomUrl(1).'</td>';
                        }else
                            print '<td ></td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('conducteur').'</td>';
                        if($item->conducteur > 0){
                            $user_->fetch($item->conducteur);
                            print '<td >'.$user_->getNomUrl(1).'</td>';
                        }else
                            print '<td ></td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('ref_contrat').'</td>';
                        print '<td >'.$item->ref_contrat.'</td>';
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
                    $total = 0;
                    if($item->services_inclus){
                        $services = json_decode($item->services_inclus);
                        foreach ($services as $key => $value) {
                            print '<tr id="'.$key.'">';
                            if(!empty($value->prix)){
                                $total+=$value->prix;
                            }
                            $typeintervention->fetch($value->type);
                                print '<td class="type_service">'.$typeintervention->label.'</td>';
                                print '<td class="note_service">'.$value->note;
                                print '<td align="center">';
                                    $prix = ( $value->prix ? $value->prix : 0);
                                    print number_format($prix,2,","," ");
                                print '</td>';
                            print '</tr>';
                        }
                    }
                    print '<tr id="total_services">';
                        print '<td colspan="2">';
                            print '<b>'.$langs->trans("Total").':</b>';
                        print '</td>';
                        print '<td align="center">';
                            print '<input type="hidden" id="sum_total" value="0" >';
                            print '<strong>'.number_format($total,2,',',' ');
                            print ' &nbsp;('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</strong>';
                        print '</td>';
                    print '</tr>';
                print '</tbody>';
            print '</table>';
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
                        $total_=0;
                        $couts_recurrent = explode(',', $item->couts_recurrent);
                        foreach ($couts_recurrent as $value) {
                            $costs->fetch($value);
                            $total_+=$costs->prix;
                            $d=explode('-', $costs->date);
                            $date_c = $d[2].'/'.$d[1].'/'.$d[0];
                            print '<tr id="cout_'.$costs->rowid.'">';
                                print '<td class="date_cout">'.$date_c.'</td>';
                                print '<td>'.number_format($costs->prix,2,","," ").'</td>';
                            print '</tr>';
                        }
                    }
                    print '<tr id="total_couts">';
                        print '<td>';
                            print '<b>'.$langs->trans("Total").':</b>';
                        print '</td>';
                        print '<td align="left">';
                            print '<input type="hidden"  value="0" >';
                            print '<strong>'.number_format($total_,2,',',' ');
                            print ' &nbsp;('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</strong>';
                        print '</td>';
                    print '</tr>';
                print '</tbody>';
            print '</table>';
        print '</div>';
    print '</div>';


    print '<div id="conditions">';
        print '<div class="cnd">'.$langs->trans('conditions').':</div>';
        print '<div class="txt_condition">'.nl2br($item->condition).'</div>';
    print '</div>';

    if($extrafields->attributes[$object->table_element]['label']){
        print '<div class="fichecenter">';
            // print '<div class="liste_titre" style="text-align:left !important"><span>'.$langs->trans('champs_add').'</span></div>';
            print '<br><br>';
            print '<div class="div_extrafield">';
                print '<table class="noborder centpercent" width="100%">';
                    print '<body>';
                        $colspn = 1+count($extrafields->attributes[$object->table_element]['label']);
                        print '<tr class="liste_titre"> <th colspan="'.$colspn.'">'.$langs->trans("champs_add").'</th> </tr>';
                        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';
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
<script>
    $(function(){
        $('.etapes').attr('disabled','disabled');
        $('.appreciation').attr('disabled','disabled');
    });
</script>