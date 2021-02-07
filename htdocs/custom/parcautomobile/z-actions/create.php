<?php

    if ($action == 'create' && $request_method === 'POST') {


        $model = GETPOST('model');
        $sendmail=GETPOST('sendmail');

        $plaque = GETPOST('plaque');
        $etiquettes = GETPOST('etiquettes');
        $conducteur = GETPOST('conducteur');
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
        $etat = GETPOST('statut');
        $value_residuelle = GETPOST('value_residuelle');
        if(empty(GETPOST('statut'))){
            $etat = 2;
        }
        $kilometre = GETPOST('kilometre');
        $type_kilom = GETPOST('unite');
        if(empty(GETPOST('unite'))){
            $type_kilom = 'kilometers';
        }
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

        $insert = array(
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
            'unite'                 =>  $type_kilom,
            'kilometrage'           =>  $kilometre,
            'value_residuelle'      =>  $value_residuelle,
        );
        
        $object = new vehiculeparc($db);

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
        $object->sendmail           =  $sendmail;
        $object->value_residuelle      =  $value_residuelle;

        $ret = $extrafields->setOptionalsFromPost(null, $object);

        $avance = $object->create(1);
        // $object->fetch($avance);
        // If no SQL error we redirect to the request card
        if ($avance > 0 ) {

            if($kilometre){
                $objkilom = new kilometrage($db);
                $objkilom->vehicule = $avance;
                $objkilom->kilometrage = $kilometre;
                $objkilom->date = date('Y-m-d');

                $objkilom->create(1);
            }
            
            header('Location: ./card.php?id='. $avance);
            exit;
        } 
        else {
            header('Location: card.php?action=request&error=SQL_Create&msg='.$recrutement->error);
            exit;
        }
    }

    if($action == "add"){

        
        print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="card_parc">';

            print '<input type="hidden" name="action" value="create" />';
            print '<input type="hidden" name="page" value="'.$page.'" />';

            print '<div  class="td_h">';
                if(!empty($conf->global->PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL)){
                    print '<div class="test_sendmail"><label for="cb1"><input id="cb1" class="flat checkforselect" type="checkbox"  name="sendmail" value="1"> <span class="title_sendmail">'.$langs->trans("test_sendmail_vehicul").'</span></label></div>';
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
                    $statuts = str_replace('<input type="radio" id="2"', '<input type="radio" id="2" checked ', $statuts);
                    print $statuts;
                print '</div>';
            print '</div>';

            print '<div id="div_1">';
                // print '<div id="img">';
                // print '</div>';
                print '<table class="border nc_table_ model" width="100%">';
                    print '<tbody>';
                        print '<tr>';
                            print '<td align="left" ><b>'.$langs->trans('model').'</b></td>';
                            print '<td colspan="3">'.$modeles->select_with_filter().'</td>';
                            print '<td rowspan="2" align="right" id="img"></td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td align="left" ><b>'.$langs->trans('plaque').'</b></td>';
                            print '<td class="plaque"><input type="text" name="plaque" id="plaque" required></td>';
                            print '<td align="left" style="width:14%;">'.$langs->trans('etiquettes').'</td>';
                            print '<td >'.$etiquettes->select_with_filter().'</td>';
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
                                print '<td >';
                                    print '<div class="Conducttype">';
                                        print $parc->select_conducteur('','conducteur',1,'Internal');
                                    print '</div>';
                                    print '<input type="radio" name="typeconduct" checked onchange="getConducts(this)" id="conductintern" value="Internal"><label for="conductintern">'.$langs->trans('Internal').'</label> ';
                                    print '  <input type="radio" name="typeconduct" onchange="getConducts(this)" id="conductextern" value="External"><label for="conductextern">'.$langs->trans('External').'</label>';
                                print '</td>';
                            print '</tr>';
                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('lieu').'</td>';
                                print '<td ><input type="text" name="lieu" id="lieu" ></td>';
                            print '</tr>';
                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('num_chassi').'</td>';
                                print '<td ><input type="number" name="num_chassi" min="0" id="num_chassi" ></td>';
                            print '</tr>';
                             print '<tr>';
                                print '<td align="left" >'.$langs->trans('nb_place').'</td>';
                                print '<td ><input type="number" name="nb_place" > </td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('nb_port').'</td>';
                                print '<td ><input type="number" name="nb_port"  > </td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('color').'</td>';
                                print '<td ><input type="color" name="color" id="color" ></td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('anne_model').'</td>';
                                print '<td >'.$parc->select_anne().'</td>';
                            print '</tr>';

                             print '<tr>';
                                print '<td align="left" >'.$langs->trans('date_immatriculation').'</td>';
                                print '<td ><input type="text" autocomplete="off" class="datepickerparc" value="'.date('d/m/Y').'" name="date_immatriculation" id="date_immatriculation" ></td>';
                            print '</tr>';
                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('date_contrat').'</td>';
                                print '<td ><input type="text" class="datepickerparc" value="'.date('d/m/Y').'" name="date_contrat" id="date_contrat" ></td>';
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
                                    print '<input type="number" name="kilometre" step="0.001" min="0" id="kilometre">'.$parc->select_unite();
                                    print '</td>';
                            print '</tr>';
                            
                             print '<tr>';
                                print '<td >'.$langs->trans('transmission').'</td>';
                                print '<td >';
                                    print '<select  name="transmission" class="transmission minwidth200 maxwidth200">';
                                        print '<option value="false"></option>';
                                        print '<option value="manual">'.$langs->trans("manual").'</option>';
                                        print '<option value="automatic">'.$langs->trans("automatic").'</option>';
                                    print '</select>';
                                print '</td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('emission_co2').'</td>';
                                print '<td ><input type="number" step="0.001" min="0" name="emission_co2"  > g/km</td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('nb_chevaux').'</td>';
                                print '<td ><input type="number" name="nb_chevaux" min="0" id="nb_chevaux" ></td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('tax').'</td>';
                                print '<td ><input type="number" name="tax" step="0.001" min="0" id="tax" ></td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('puissance').'</td>';
                                print '<td ><input type="number" name="puissance" min="0" id="puissance" > kW</td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('type_carburant').'</td>';
                                print '<td >'.$parc->type_carburant().'</td>';
                            print '</tr>';
                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('valeur_catalogue').'</td>';
                                print '<td ><input type="text" name="valeur_catalogue" id="valeur_catalogue" ></td>';
                            print '</tr>';
                            print '<tr>';
                                print '<td align="left" >'.$langs->trans('value_residuelle').'</td>';
                                print '<td ><input class="value_residuelle" name="value_residuelle" type="text" ></td>';
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
                            print '<tr>';
                                $object = new vehiculeparc($db);
                                include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';
                            print '</tr>';
                        print '</tbody>';
                    print '</table>';
                print '</div>';
            print '</div>';
            // Actions
            print '<table class="" width="100%">';
                print '<tr>';
                    print '<td colspan="2" >';
                    print '<br>';
                    print '<input type="submit" style="display:none" id="sub_valid" value="'.$langs->trans('Validate').'" name="bouton" class="butAction" />';
                    print '<a  class="butAction" id="btn_valid">'.$langs->trans('Validate').'</a>';
                    print '<a href="./index.php?page='.$page.'" class="butAction">'.$langs->trans('annuler').'</a>';
                print '</tr>';
            print '</table>';
        print '</form>';
    }

?>
<style>
    .etapes:hover {
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