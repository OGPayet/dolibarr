<?php



$d=explode(' ', $item->date);
$date_d = explode('-', $d[0]);
$date = $date_d[2]."/".$date_d[1]."/".$date_d[0];
$typeintervention->fetch($item->typeintervention);
$html='<style>';
$html.='th {color:black;}';
$html.='table.border_1 {border:1px solid #e0e0e0; border-collapse: 0;width:100%;}';
$html.='table.border_1 td{height:25px; line-height:2;}';
$html.='.logo {width:10%; text-align:center;}';
$html.='.info_vehicule {width:90%;}';
$html.='.label_vehicule {font-size:18px;}';
$html.='.sp_td {line-height:2;}';
$html.='.div_1 {width:100px;}';
$html.='.name_vehicule {width:100px;}';
$html.='.etiquette {color:white;}';
$html.='.title_div {text-align:center; height: 33px; line-height: 2; font-size:14px;color:black;}';
$html.='.d_right {float:left;width:200px;border:1px solid #e0e0e0;}';
$html.='.d_left {float:left;width:800px;border:1px solid #e0e0e0;}';
$html.='table.border_services{width:100%;}';
$html.='table.border_services td{border:1px solid #e0e0e0;}';
$html.='table.border_services tr{border:1px solid #e0e0e0; height:20px; line-height:2}';
$html.='.plus_info{width:100%; font-size:12px; color:black;}';
$html.='tr.pair{background-color:#fafafd;}';
$html.='.txt_condition{ border: 1px solid #e0e0e0; width: 99%;line-height:2}';
$html.='.cnd{width: 6%;float: left;height: 25px;font-size: 12px;font-weight: bold;padding: 9px;background-color: #dcdcdf;width: 99%;line-height:2;color:black;}';
$html.='</style>';



$html.= '<div class="title_div"><b>'.$langs->trans('detail_intervention').'</b></div>';
$html.='<div style="width:100% !important;">';
    $html.= '<div id="div_2">';
        $html.= '<table class="border_1" width="100%">';
            $html.= '<tbody>';

                $vehicules->fetch($item->vehicule);
                $model->fetch($vehicules->model);
                $marque->fetch($model->marque);

                $html.= '<tr class="pair">';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('vehicule').' :  </span></th>';
                    $html.= '<td ><span class="sp_td">'.$marque->label.'/'.$model->label.'/'.$vehicules->plaque.'</span></td>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('typeintervention').' :  </span></th>';
                    $html.= '<td ><span class="sp_td">'.$typeintervention->label.'</span></td>';
                $html.= '</tr>';

                $html.= '<tr>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('acheteur').' :  </span></th>';
                    $user_->fetch($vehicules->conducteur);
                    $html.= '<td ><span class="sp_td">'.$user_->firstname.' '.$user_->lastname.'</span></td>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('fournisseur').' :  </span></th>';
                    $soc->fetch($item->fournisseur);
                    $html.= '<td ><span class="sp_td">'.$soc->nom.'</span></td>';
                $html.= '</tr>';


                $html.= '<tr class="pair">';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('kilometrage_vehicule').' : </span> </th>';
                    $html.= '<td ><span class="sp_td">'.$item->kilometrage.' '.$langs->trans($vehicules->unite).'</span> </td>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('prix_inter').'  : </span></th>';
                    $html.= '<td ><span class="sp_td">'.number_format($item->prix,2,","," ").'  ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</span></td>';
                $html.= '</tr>';

                $html.= '<tr>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('responsable').' :  </span></th>';
                    $html.= '<td ><span class="sp_td">'.$user_->firstname.' '.$user_->lastname.'</span></td>';

                    $datevalidate = '';
                    if(!empty($item->datevalidate)){
                        $date2= explode('-', $item->datevalidate);
                        $datevalidate=$date2[2].'/'.$date2[1].'/'.$date2[0];
                    }

                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('datevalidate').' :  </span></th>';
                    $html.= '<td ><span class="sp_td">'.$datevalidate.'</span></td>';
                $html.= '</tr>';

                $date=explode('-',$item->date);
                $date_d = $date[2].'/'.$date[1].'/'.$date[0];


                $html.= '<tr class="pair">';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('date').' : </span> </th>';
                    $html.= '<td> <span class="sp_td">'.$date_d.'</span> </td>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('ref_facture').' : </span></th>';
                    $html.= '<td ><span class="sp_td">'.$item->ref_facture.'</span></td>';
                $html.= '</tr>';

            $html.= '</tbody>';
        $html.= '</table>';
    $html.= '</div>';


            $html.= '</tbody>';
        $html.= '</table>';
    $html.= '</div>';
$html.= '</div>';

    $html.= '<div class="cnd" > <span><b> &nbsp;'.$langs->trans("sevice_inclu").':</span> </b></div>';
    $html.= '<table class="border_services" width="100%">';
        $html.= '<tbody>';
            $html.= '<tr style="height:25px;line-height:3;background-color:#f1f1f7">';
                $html.= '<th align="center">'.$langs->trans("date").'</th>';
                $html.= '<th align="center">'.$langs->trans("description").'</th>';
                $html.= '<th align="center">'.$langs->trans("prix_inter").' ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</th>';
            $html.= '</tr>';
            if($item->service_inclus){
                $total_1 = 0;
                $services = json_decode($item->service_inclus);
                foreach ($services as $key => $value) {
                    $html.= '<tr id="'.$key.'">';
                    $total_1+=$value->prix;
                    $typeintervention->fetch($value->type);
                        $html.= '<td class="type_service">'.$typeintervention->label.'</td>';
                        $html.= '<td class="note_service">'.$value->note.'</td>';
                        $html.= '<td align="center">'.number_format($value->prix,2,","," ").'</td>';
                    $html.= '</tr>';
                }
            }
            if($total_1 > 0){
                $html.= '<tr style="height:25px;line-height:3;background-color:#f1f1f7">';
                    $html.='<th colspan="2" align="right"><b>'.$langs->trans("Total").' : </b></th>';
                    $html.='<th align="center">'.number_format($total_1,2,","," ").'  ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</th>';
                $html.= '</tr>';
            }
        $html.= '</tbody>';
    $html.= '</table>';

    $html.= '<br><br>';




    $html.= '<div id="conditions">';
        $html.= '<div class="cnd">  '.$langs->trans('notes').':</div>';
        $html.= '<div class="txt_condition">  '.$item->notes.'</div>';
    $html.= '</div>';


    $html.= '<br>';

    $html.= '<div class="cnd" > <span><b> &nbsp;'.$langs->trans("champs_add").':</span> </b></div>';
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
