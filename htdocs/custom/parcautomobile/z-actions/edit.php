<?php

if ($action == 'update' && $request_method === 'POST') {

    $page  = GETPOST('page');

    // $d1 = GETPOST('debut');
    // $f1 = GETPOST('fin');
    $id=GETPOST('id');
    $sendmail=GETPOST('sendmail','int');

    $model = GETPOST('model');
    $plaque = GETPOST('plaque');
    $etiquettes = GETPOST('etiquettes');
    $conducteur = GETPOST('conducteur');
    $etat = GETPOST('statut');
    $lieu = GETPOST('lieu');
    $num_chassi = GETPOST('num_chassi');
    $anne = GETPOST('anne_model');
    $nb_place = GETPOST('nb_place');
    $nb_port = GETPOST('nb_port');
    $color = GETPOST('color');
    $type_carburant = GETPOST('type_carburant');

    $valeur_catalogue = GETPOST('valeur_catalogue');
    $transmission = GETPOST('transmission');
    $emission_co2 = GETPOST('emission_co2');
    $nb_chevaux = GETPOST('nb_chevaux');
    $tax = GETPOST('tax');
    $puissance = GETPOST('puissance');
    $value_residuelle = GETPOST('value_residuelle');
    if(GETPOST('date_contrat')){
        $date =explode('/',GETPOST('date_contrat'));
        $date_contrat = $date[2].'-'.$date[1].'-'.$date[0];
    }

    if(GETPOST('date_immatriculation')){
        $date =explode('/',GETPOST('date_immatriculation'));
        $date_immatriculation = $date[2].'-'.$date[1].'-'.$date[0];
    }
    $etiquette = "";
    if($etiquettes)
    $etiquette = implode(",", $etiquettes);

    $kilometre = GETPOST('kilometre');
    // $val_kilometre = GETPOST('val_kilometre');
    $type_kilom = GETPOST('unite');
    if(empty($type_kilom)){
        $type_kilom = 'kilometers';
    }
    $max=$kilometrage->Max_kilometrage($id);
    if($max > $kilometre){
        $kilometre = $max;
    }

    $data = array(
        'sendmail'              =>  $sendmail,
        'plaque'                =>  $plaque,
        'model'                 =>  $model,
        'conducteur'            =>  $conducteur,
        'etiquettes'            =>  $etiquette,
        'lieu'                  =>  $lieu,
        'num_chassi'            =>  $num_chassi,
        'anne_model'            =>  $anne,
        'nb_place'              =>  $nb_place,
        'nb_porte'              =>  $nb_port,
        'color'                 =>  $color,
        'type_carburant'        =>  $type_carburant,
        'date_immatriculation'  =>  $date_immatriculation,
        'date_contrat'          =>  $date_contrat,
        'value_catalogue'       =>  $valeur_catalogue,
        'transmission'          =>  $transmission,
        'emission_co2'          =>  $emission_co2,
        'nb_chevaux'            =>  $nb_chevaux,
        'tax'                   =>  $tax,
        'puissance'             =>  $puissance,
        'statut'                =>  $etat,
        'kilometrage'           =>  $kilometre,
        'unite'                 =>  $type_kilom,
        'value_residuelle'                 =>  $value_residuelle,
    );
    $object = new vehiculeparc($db);
    $object->fetch($id);
    $kilometre_1=$object->kilometrage;

    $object->plaque                =  $plaque;
    $object->model                 =  $model;
    $object->conducteur            =  $conducteur;
    $object->etiquettes            =  $etiquette;
    $object->lieu                  =  $lieu;
    $object->num_chassi            =  $num_chassi;
    $object->anne_model            =  $anne;
    $object->nb_place              =  $nb_place;
    $object->nb_porte              =  $nb_port;
    $object->color                 =  $color;
    $object->type_carburant        =  $type_carburant;
    $object->date_immatriculation  =  $date_immatriculation;
    $object->date_contrat          =  $date_contrat;
    $object->value_catalogue       =  $valeur_catalogue;
    $object->transmission          =  $transmission;
    $object->emission_co2          =  $emission_co2;
    $object->nb_chevaux            =  $nb_chevaux;
    $object->tax                   =  $tax;
    $object->puissance             =  $puissance;
    $object->statut                =  $etat;
    $object->unite                 =  $type_kilom;
    $object->kilometrage           =  $kilometre;
    $object->value_residuelle      =  $value_residuelle;
    $object->sendmail              =  $sendmail;

    $ret = $extrafields->setOptionalsFromPost(null, $object);


    $isvalid = $object->update($id);
    if ($isvalid > 0) {

        if(!empty($kilometre) && $kilometre_1 != $kilometre){
            $objkilom = new kilometrage($db);
            $objkilom->vehicule = $id;
            $objkilom->kilometrage = $kilometre;
            $objkilom->date = date('Y-m-d');

            $objkilom->create(1);
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

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="card_parc">';

    print '<input type="hidden" name="action" value="update" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
        $parc->fetchAll('','',0,0,'AND rowid='.$id);
        $item = $parc->rows[0];
        $modeles->fetch($item->model);
        $marques->fetch($modeles->marque);

        $object = new vehiculeparc($db);
        $object->fetch($item->rowid);

        $extrafields = new ExtraFields($db);
        $extrafields->fetch_name_optionals_label($object->table_element);
        // $object->fetch($item->rowid);
        $object->fetch_optionals();



        $checked = '';
            print '<div  class="td_h">';
                if(!empty($conf->global->PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL)){
                    if($item->sendmail) $checked = 'checked';
                    print '<div class="test_sendmail"><input id="cb1" class="flat checkforselect" type="checkbox" '.$checked.' name="sendmail" value="1"> <span class="title_sendmail">'.$langs->trans("test_sendmail_vehicul").'</span></div>';
                }
                print '<div class="alletapesrecru">';
                $statut->fetchAll();
                $statuts='';
                for($i=0; $i < count($statut->rows); $i++){
                    $statu = $statut->rows[$i];
                    $statuts .='<label class="etapes" >';
                        $statuts .= '<input type="radio" id="'.$statu->rowid.'" style="display:none;" value="'.$statu->rowid.'" name="statut" class="etapes">';
                        $statuts .= ' <span class="radio"></span>';
                        $statuts .= '<span style="font-size:14px"> '.$langs->trans($statu->label).'</span>';
                    $statuts .= '</label>';
                }

                    $statuts = str_replace('<input type="radio" id="'.$item->statut.'"', '<input type="radio" id="'.$item->statut.'" checked ', $statuts);
                    print $statuts;
                print '</div>';
            print '</div>';
            print '<div id="div_1">';
                print '<table class="border nc_table_ model" width="100%">';
                    print '<tbody>';
                        print '<tr>';
                            print '<td><b>'.$langs->trans('model').'</b></td>';
                            print '<td colspan="3">'.$modeles->select_with_filter($item->model).'</td>';
                            print '<td rowspan="2" align="right" id="img">';
                                if($item->model){
                                    $model   = new modeles($db);
                                    $marque  = new marques($db);
                                    $model->fetch($item->model);
                                    $marque->fetch($model->marque);
                                     if(!empty($marque->logo)){
                                        $minifile = getImageFileNameForSize($marque->logo, '');
                                        $dt_files = getAdvancedPreviewUrl('parcautomobile', '/marques/'.$marque->rowid.'/'.$minifile, 1, '&entity='.(!empty($object->entity)?$object->entity:$conf->entity));

                                        print '<img align="left" height="20px" class="photo" height="" title="'.$minifile.'" alt="Fichier binaire" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=parcautomobile&entity='.(!empty($object->entity)?$object->entity:$conf->entity).'&file=marques/'.$marque->rowid.'/'.$minifile.'&perm=download" border="0" name="image" >';
                                    }
                                }

                            print '</td>';
                            // print '<td >'.$modeles->select_with_filter().'</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td><b>'.$langs->trans('plaque').'</b></td>';
                            print '<td class="plaque"><input type="text" name="plaque" id="plaque" value="'.$item->plaque.'" ></td>';
                            print '<td style="width: 12%;">'.$langs->trans('etiquettes').'</td>';
                            print '<td >'.$etiquettes->select_with_filter($item->etiquettes).'</td>';
                        print '</tr>';

                    print '</tbody>';
                print '</table>';
            print '</div>';

            print '<div id="div_2">';
                print '<div class="div_left">';
                    print '<div class="title_div"> <span>'.$langs->trans("propriete").'</span> </div>';
                    print '<table class="border nc_table_" width="100%">';
                        print '<tbody>';
                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('conducteur').'</td>';
                                print '<td >'.$parc->select_conducteur($item->conducteur).'</td>';
                            print '</tr>';
                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('lieu').'</td>';
                                print '<td ><input type="text" name="lieu" id="lieu" value="'.$item->lieu.'" ></td>';
                            print '</tr>';
                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('num_chassi').'</td>';
                                print '<td ><input type="number" name="num_chassi" min="0" id="num_chassi" value="'.$item->num_chassi.'" ></td>';
                            print '</tr>';
                             print '<tr>';
                                print '<td align="left" >'.$langs->trans('nb_place').'</td>';
                                print '<td ><input type="number" name="nb_place" value="'.$item->nb_place.'" > </td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('nb_port').'</td>';
                                print '<td ><input type="number" name="nb_port" value="'.$item->nb_porte.'"  > </td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('color').'</td>';
                                print '<td ><input type="color" name="color" id="color" value="'.$item->color.'" ></td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('anne_model').'</td>';
                                print '<td >'.$parc->select_anne($item->anne_model).'</td>';
                            print '</tr>';
                            $date=explode('-',$item->date_immatriculation);
                            $date_immatriculation = $date[2].'/'.$date[1].'/'.$date[0];
                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('date_immatriculation').'</td>';
                                print '<td ><input type="text" autocomplete="off"  class="datepickerparc" value="'.$date_immatriculation.'" name="date_immatriculation" id="date_immatriculation" ></td>';
                            print '</tr>';
                            $date=explode('-',$item->date_contrat);
                            $date_contrat = $date[2].'/'.$date[1].'/'.$date[0];
                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('date_contrat').'</td>';
                                print '<td ><input type="text" class="datepickerparc" name="date_contrat" value="'.$date_contrat.'" id="date_contrat" ></td>';
                            print '</tr>';

                        print '</tbody>';
                    print '</table>';
                print '</div>';

                print '<div class="div_right">';
                    print '<div class="title_div"><span>'.$langs->trans("options").'</span></div>';
                    print '<table class="border nc_table_" width="100%">';
                        print '<tbody>';

                             print '<tr>';
                                print '<td align="left" >'.$langs->trans('kilometrage_vehicule').'</td>';
                                print '<td >';
                                    print '<input type="number" name="kilometre" min="0" id="kilometre" value="'.$item->kilometrage.'">';
                                    // print '<input type="hidden" id="val_kilometre" name="val_kilometre">';

                                    print $parc->select_unite($item->unite);
                                    print '</td>';
                            print '</tr>';

                             print '<tr>';
                                print '<td >'.$langs->trans('transmission').'</td>';
                                print '<td >';
                                    print '<select  name="transmission" class="transmission">';
                                        $option  ='<option value="false"></option>';
                                        $option .='<option value="manual">'.$langs->trans("manual").'</option>';
                                        $option .='<option value="automatic">'.$langs->trans("automatic").'</option>';
                                        $option =str_replace('value="'.$item->transmission.'"', 'value="'.$item->transmission.'" selected', $option);
                                    print $option.'</select>';
                                print '</td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('emission_co2').'</td>';
                                print '<td ><input type="number" step="0.001" min="0" name="emission_co2" value="'.$item->emission_co2.'" > g/km</td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('nb_chevaux').'</td>';
                                print '<td ><input type="number" name="nb_chevaux" min="0" id="nb_chevaux" value="'.$item->nb_chevaux.'" ></td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('tax').'</td>';
                                print '<td ><input type="number" name="tax" step="0.001" min="0" id="tax" value="'.$item->tax.'" ></td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('puissance').'</td>';
                                print '<td ><input type="number" name="puissance" min="0" id="puissance" value="'.$item->puissance.'" >kW</td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('type_carburant').'</td>';
                                print '<td >'.$parc->type_carburant($item->type_carburant).'</td>';
                            print '</tr>';
                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('valeur_catalogue').'</td>';
                                print '<td ><input type="number" name="valeur_catalogue" id="valeur_catalogue" value="'.$item->value_catalogue.'" ></td>';
                            print '</tr>';
                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('value_residuelle').'</td>';
                                print '<td ><input class="value_residuelle" name="value_residuelle" type="number" value="'.$item->value_residuelle.'" ></td>';
                            print '</tr>';

                        print '</tbody>';
                    print '</table>';
                print '</div>';
            print '</div>';


     print '<div class="fichecenter">';
            print '<div class="topheaderrecrutmenus" style="text-align:left !important"><span>'.$langs->trans('champs_add').'</span></div>';
            print '<div class="div_extrafield">';
                print '<table class="border nc_table_" width="100%">';
                    print '<body>';
                        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';
                    print '</tbody>';
                print '</table>';
            print '</div>';
    print '</div>';
    print '</div>';


    // Actions

    print '<table class="" width="100%">';
    print '<tr>';
        print '<td colspan="2" >';
            print '<br>';
            print '<input type="submit" style="display:none;" id="sub_valid" value="'.$langs->trans('Validate').'" style="" name="bouton" class="butAction" />';
            print '<a  class="butAction" id="btn_valid">'.$langs->trans('Validate').'</a>';

            print '<a href="./index.php?page='.$page.'" class="butAction">'.$langs->trans('annuler').'</a>';
        print '</td>';
    print '</tr>';
    print '</table>';

    print '</form>';

    ?>
    <?php
}

?>
<style>
    .etapes:hover {
        cursor: pointer;
    }
</style>
<script >
    $(document).ready(function(){
        $('#btn_valid').click(function(){
            $('#sub_valid').trigger('click');
        })
    });
</script>
