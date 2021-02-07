<?php

$d=explode(' ', $object->date);
$date_d = explode('-', $d[0]);
$date = $date_d[2]."/".$date_d[1]."/".$date_d[0];

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
    

$html .= '<br><br>';
$html.= '<div class="title_div"><b>'.$langs->trans('detail_kilom').'</b></div>';
$html .= '<br><br>';

$html.='<div style="width:100% !important;">';
    $html.= '<div id="div_2">';
        $html.= '<div class="cnd" > <span><b> &nbsp;'.$langs->trans("detail_vehicul").':</span> </b></div>';
        $html.= '<table class="border_1" width="100%">';
            $html.= '<tbody>';
                
                $vehicule->fetch($object->vehicule);
                $model->fetch($vehicule->model);
                $marque->fetch($model->marque);

                $html.= '<tr class="pair">';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('vehicule').' :  </span></th>';
                    $html.= '<td ><span class="sp_td">'.$marque->label.'/'.$model->label.'/'.$vehicule->plaque.'</span></td>';
                    $html.= '<th></th>';
                    $html.= '<td align="right" rowspan="3">';
                    $html.= '<br>';
                        $minifile = getImageFileNameForSize($marque->logo, '');
                        // $urlfile = DOL_URL_ROOT.'/viewimage.php?modulepart=parcautomobile&entity='.$conf->entity.'&file=marques/'.$marques->rowid.'/'.$minifile.'&perm=download';
                        $urlfile = $conf->parcautomobile->dir_output.'/marques/'.$marque->rowid.'/'.$minifile;
                        $html.= '<img alt="Photo" style="height:65px;" src="'.$urlfile.'" >';
                    $html.= '</td>';
                $html.= '</tr>';

                $html.= '<tr>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('conducteur').' :  </span></th>';
                    $user_->fetch($vehicule->conducteur);
                    $html.= '<td ><span class="sp_td">'.$user_->firstname.' '.$user_->lastname.'</span></td>';
                    if($vehicule->fournisseur){
                        $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('fournisseur').' :  </span></th>';
                        $soc->fetch($vehicule->fournisseur);
                        $html.= '<td ><span class="sp_td">'.$soc->nom.'</span></td>';               
                    }else{
                        $html.= '<th></th>';
                    }
                $html.= '</tr>';
   
                $html.= '<tr>';
                    $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('kilometrage_vehicule').' : </span> </th>';
                    $html.= '<td ><span class="sp_td">'.$vehicule->kilometrage.' '.$langs->trans($vehicule->unite).'</span> </td>';
                    $html.= '<th></th>';
                $html.= '</tr>';

                // $date=explode('-',$object->date);
                // $date_d = $date[2].'/'.$date[1].'/'.$date[0];


                // $html.= '<tr class="pair">';
                //     $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('date').' : </span> </th>';
                //     $html.= '<td> <span class="sp_td">'.$date_d.'</span> </td>';
                // $html.= '</tr>';
            $html.= '</tbody>';
        $html.= '</table>';
        $html.= '<br><br>';
        $html.= '<div class="cnd" > <span><b> &nbsp;'.$langs->trans("detail_kilom").':</span> </b></div>';
        $html.= '<table  class="border_1" width="100%">';
            $html.= '<tr>';

                $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('date').' :  </span></th>';
                $html.= '<td ><span class="sp_td">'.$date.'</span></td>';

            $html.= '</tr>';
            $html.= '<tr>';
                $html.= '<th align="left" ><span class="sp_td">'.$langs->trans('kilometrage').' :  </span></th>';
                $html.= '<td ><span class="sp_td">'.$object->kilometrage.' '.$langs->trans($vehicule->unite).'</span></td>';

            $html.= '</tr>';
        $html.= '</table>';

    $html.= '<br><br>';

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

    $html.= '</div>';
$html.= '</div>';
    

