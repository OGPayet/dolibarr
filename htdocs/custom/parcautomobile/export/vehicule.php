<?php



$d=explode(' ', $item->date);
$date_d = explode('-', $d[0]);
$date = $date_d[2]."/".$date_d[1]."/".$date_d[0];
$modeles->fetch($item->model);
$marques->fetch($modeles->marque);

$html='<style>';
$html.='th {color:black;}';
$html.='table.border_1 {border:1px solid #e0e0e0; height:25px;border-collapse: 0;width:100%;}';
$html.='.logo {width:10%; text-align:center;}';
$html.='.info_vehicule {width:90%;}';
$html.='.label_vehicule {font-size:18px;}';
$html.='.sp_td {line-height:2;}';
$html.='.div_1 {width:100px;}';
$html.='.name_vehicule {width:100px;}';
$html.='.etiquette {color:white;}';
$html.='.title_div {color:black; background-color: #dcdcdf; width:100%; height: 33px; line-height: 2;}';
$html.='.d_right {float:left;width:200px;border:1px solid #e0e0e0;}';
$html.='.d_left {float:left;width:800px;border:1px solid #e0e0e0;}';
$html.='table#border_services{width:100%;}';
$html.='table#border_services td{border:1px solid #e0e0e0;}';
$html.='table#border_services tr{border:1px solid #e0e0e0; height:25px;line-height:2}';
$html.='.plus_info{width:100%; font-size:14px; color:black;}';
$html.='</style>';


    $html.= '<table class="border" width="100%" style="border:1px solid #e0e0e0;">';
        $html.= '<tbody>';
            $html.= '<tr>';
                $html.= '<td rowspan="2" class="logo" style=" height:25px;">';
                    
                    $minifile = getImageFileNameForSize($marques->logo, '');
                    $urlfile = $conf->parcautomobile->dir_output.'/marques/'.$marques->rowid.'/'.$minifile;
                    $html.= '<img alt="Photo" style="height:120px;" src="'.$urlfile.'" >';
                    
                $html.= '</td>';
                $html.= '<td class="info_vehicule">';
                    $html.= '<div><br><strong class="label_vehicule">'.$parc->get_nom($item->rowid).'</strong></div>';
                $html.= '</td>';
            $html.= '</tr>';

            $date=explode('-',$item->date_immatriculation);
            $date_immatriculation = $date[2].'/'.$date[1].'/'.$date[0];
            $html.= '<tr>';
                $html.= '<td class="info_vehicule">'.$item->plaque.'&nbsp;&nbsp;';
                    $etiquet=explode(',', $item->etiquettes);
                    foreach ($etiquet as $key => $value) {
                        $etiquettes->fetch($value);
                        $html.= '<span class="etiquette" style="background-color:'.$etiquettes->color.'"><b>'.$etiquettes->label.'</b></span>&nbsp;&nbsp;';
                    }
                $html.= '</td>';
            $html.= '</tr>';
        $html.= '</tbody>';
    $html.= '</table>';
$html.='<div style="width:100% !important;">';
    $html.= '<div id="div_2">';
        $html.= '<div class="title_div" > <span><b> &nbsp;'.$langs->trans("propriete").'</b></span> </div>';
        $html.= '<table class="border_1" width="100%">';
            $html.= '<tbody>';
                $html.= '<tr>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('conducteur').' :  </span></th>';
                    $user_->fetch($item->conducteur);
                    $html.= '<td ><span class="sp_td">'.$user_->firstname.' '.$user_->lastname.'</span></td>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('lieu').' :  </span></th>';
                    $html.= '<td ><span class="sp_td">'.$item->lieu.'</span></td>';
                $html.= '</tr>';
                
                $html.= '<tr>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('num_chassi').' :  </span></th>';
                    $html.= '<td ><span class="sp_td">'.$item->num_chassi.'</span></td>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('nb_place').' :  </span></th>';
                    $html.= '<td ><span class="sp_td">'.$item->nb_place.'</span></td>';
                $html.= '</tr>';
                $html.= '<tr>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('nb_port').' :  </span></th>';
                    $html.= '<td ><span class="sp_td">'.$item->nb_porte.'</span></td>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('color').'  : </span></th>';
                    $html.= '<td ><span class="sp_td">'.$item->color.'</span></td>';
                $html.= '</tr>';
                $date_contrat = $date[2].'/'.$date[1].'/'.$date[0];
                $date=explode('-',$item->date_contrat);
                $html.= '<tr>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('anne_model').'  : </span></th>';
                    $html.= '<td ><span class="sp_td">'.$item->anne_model.'</span></td>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('date_contrat').'  : </span></th>';
                    $html.= '<td ><span class="sp_td">'.$date_contrat.'</span></td>';
                $html.= '</tr>';
            $html.= '</tbody>';
        $html.= '</table>';
    $html.= '</div>';

    $html.= '<div id="div_3">';
        $html.= '<div class="title_div" > <span><b> &nbsp;'.$langs->trans("options").'</b></span> </div>';
        $html.= '<table class="border_1 nc_table_">';
            $html.= '<tbody>';
                $html.= '<tr>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('kilometrage_vehicule').' : </span> </th>';
                    $html.= '<td colspan="3"><span class="sp_td">'.$item->kilometrage.' '.$item->unite.'</span> </td>';
                $html.= '</tr>';

                

                $html.= '<tr>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('transmission').' : </span> </th>';
                    $html.= '<td> <span class="sp_td">'.$langs->trans($item->transmission).'</span> </td>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('type_carburant').' : </span></th>';
                    $html.= '<td ><span class="sp_td">'.$langs->trans($item->type_carburant).'</span></td>';
                $html.= '</tr>';
                
                

                $html.= '<tr>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('emission_co2').' : </span></th>';
                    $html.= '<td><span class="sp_td">'.$item->emission_co2.' g/km</span></td>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('puissance').' : </span></th>';
                    $html.= '<td ><span class="sp_td">'.$item->puissance.' kW</span></td>';
                $html.= '</tr>';

                $html.= '<tr>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('nb_chevaux').' : </span></th>';
                    $html.= '<td ><span class="sp_td">'.$item->nb_chevaux.'</span></td>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('tax').' : </span></th>';
                    $html.= '<td ><span class="sp_td">'.$item->tax.'</span></td>';
                $html.= '</tr>';

                

                $html.= '<tr>';
                $html.= '</tr>';

                $html.= '<tr>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('valeur_catalogue').' : </span></th>';
                    $html.= '<td ><span class="sp_td">'.$item->value_catalogue.'</span></td>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('value_residuelle').' : </span></th>';
                    $html.= '<td ><span class="sp_td">'.$item->value_residuelle.'</span> </td>';
                $html.= '</tr>';
               
            $html.= '</tbody>';
        $html.= '</table>';
    $html.= '</div>';
$html.= '</div>';

    $html.= '<div class="title_div" > <span><b> &nbsp;'.$langs->trans("lignes_intervention").':</span> </b></div>';
    $html.= '<table id="border_services" width="100%">';
        $html.= '<tbody>';
            $html.= '<tr style="height:25px;line-height:3;background-color:#f1f1f7">';
                $html.= '<th align="center">'.$langs->trans("ref_facture").'</th>';
                $html.= '<th align="center">'.$langs->trans("label_typeintervention").'</th>';
                $html.= '<th align="center">'.$langs->trans("fournisseur").'</th>';
                $html.= '<th align="center">'.$langs->trans("date").'</th>';
                $html.= '<th align="center">'.$langs->trans("prix_inter").' ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</th>';
            $html.= '</tr>';
            $sql="select * from vehiculesparc";
            $interventions->fetchAll('','',3,0,'ORDER BY  date ASC');
            $total_1 = 0;
            for ($i=0; $i < count($interventions->rows); $i++) { 
                $service = $interventions->rows[$i];
                $total_1 += $service->prix;
                $typeintervention->fetch($service->typeintervention);
                $html.= '<tr>';
                    $html.= '<td align="center">'.$service->ref_facture.'</td>';
                    $html.= '<td align="center">'.$typeintervention->label.'</td>';
                    $soc->fetch($service->fournisseur);
                    $html.= '<td align="center">'.$soc->nom.'</td>';
                    $date=explode('-', $service->date);
                    $date=$date[2].'/'.$date[1].'/'.$date[0];
                    $html.= '<td align="center">'.$date.'</td>';
                    $html.= '<td align="center">'.number_format($service->prix,2,","," ").'</td>';
                $html.= '</tr>';
                            
            }
            if($total_1 > 0){
                $html.= '<tr style="height:25px;line-height:3;background-color:#f1f1f7">';
                    $html.='<th colspan="4" align="right"><b>'.$langs->trans("Total").' : </b></th>';
                    $html.='<th align="center">'.number_format($total_1,2,","," ").'('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</th>';
                $html.= '</tr>';
            }
        $html.= '</tbody>';
    $html.= '</table>';

    $html.= '<br><br>';

    $html.= '<div class="title_div" > <span><b> &nbsp;'.$langs->trans("lignes_niveau_essence").':</b></span> </div>';
    $html.= '<table id="border_services" width="100%">';
        $html.= '<tbody>';
            $html.= '<tr style="height:25px;line-height:3;background-color:#f1f1f7">';
                $html.= '<th align="center">'.$langs->trans("Ref").'</th>';
                $html.= '<th align="center">'.$langs->trans("date").'</th>';
                $html.= '<th align="center">'.$langs->trans("kilometrage").'</th>';
                $html.= '<th align="center">'.$langs->trans("unite").'</th>';
                $html.= '<th align="center">'.$langs->trans("litre").' (Litre)</th>';
                $html.= '<th align="center">'.$langs->trans("prix_inter").' ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</th>';
            $html.= '</tr>';
            $sql="select * from vehiculesparc";
            $suivi_essence->fetchAll('','',3,0,'ORDER BY  date ASC');
            $total_2 = 0;
            for ($i=0; $i < count($suivi_essence->rows); $i++) { 
                $prix=$suivi->prix*$suivi->litre;
                $total_2+=$prix;
                $suivi = $suivi_essence->rows[$i];
                $html.= '<tr>';
                    $html.= '<td align="center">'.$suivi->rowid.'</td>';
                    $date=explode('-', $suivi->date);
                    $date=$date[2].'/'.$date[1].'/'.$date[0];
                    $html.= '<td align="center">'.$date.'</td>';
                    $html.= '<td align="center">'.$suivi->kilometrage.'</td>';
                    $html.= '<td align="center">'.$item->unite.'</td>';
                    $html.= '<td align="center">'.$suivi->litre.'</td>';
                    $html.= '<td align="center">'.number_format($prix,2,","," ").'</td>';
                $html.= '</tr>';
                            
            }
            if($total_2 > 0){
                $html.= '<tr style="height:25px;line-height:3;background-color:#f1f1f7">';
                    $html.='<th colspan="5" align="right"><b>'.$langs->trans("Total").' : </b></th>';
                    $html.='<th align="center">'.number_format($total_2,2,","," ").'('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</th>';
                $html.= '</tr>';
            }
        $html.= '</tbody>';
    $html.= '</table>';

    
    $html.= '<br><br>';

    $html.= '<div class="title_div" > <span><b> &nbsp;'.$langs->trans("champs_add").':</span> </b></div>';
    $html.= '<table  class="border_1" width="100%">';
        $html.= '<tbody>';
           if($extrafields->attributes[$object->table_element]['label'] && count($extrafields->attributes[$object->table_element]['label'])){
                foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val){
                    if($extrafields->attributes[$object->table_element]['list'][$key] != 2 && $extrafields->attributes[$object->table_element]['list'][$key] != 0){
                        $html.= '<tr>';
                            $html .= '<th align="left" ><span class="sp_td">'.$val.'</span> </th>';
                            $html .= '<td> <span class="sp_td"> ';
                                $tmpkey = 'options_'.$key;
                                $value = $object->array_options['options_'.$key];
                                // $html.= 'type::'.$extrafields->attributes[$object->table_element]['type'][$key];
                                if( $extrafields->attributes[$object->table_element]['type'][$key] == 'boolean'){
                                    if(!empty($value)){
                                        $img = '<img height="10px" src="'.dol_buildpath("parcautomobile/img/checked.png").'">';
                                    }else
                                        $img = '<img height="10px" src="'.dol_buildpath("parcautomobile/img/nochecked.png").'">';
                                    $html.= $img;
                                }else{
                                    $html .= $extrafields->showOutputField($key, $value, '', $object->table_element);
                                }
                            $html .= '</span></td>';
                        $html.= '</tr>';
                    }
                }
            }
        // $html.= $txt;
        $html.= '</tbody>';
    $html.= '</table>';

    $html.= '<br><br>';


