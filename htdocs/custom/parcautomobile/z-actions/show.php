<?php

if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {

    if (!$id || $id <= 0) {
        header('Location: ./card.php?action=request&error=dalete_failed&id='.$id);
        exit;
    }

    $page  = GETPOST('page');

    $parc->fetch($id);





    $error = $parc->delete();

    if ($error == 1) {
          //contrat
        $contrats->fetchAll('','',0,0,'AND vehicule = '.$id);
        if(count($contrats->rows) > 0){
            for ($i=0; $i < count($contrats->rows) ; $i++) {
                $contrat=$contrats->rows[$i];
                $contrats->fetch($contrat->rowid);
                $d=$contrats->delete();
            }
        }
        //interventions
        $interventions->fetchAll('','',0,0,'AND vehicule = '.$id);
        for ($i=0; $i < count($interventions->rows) ; $i++) {
            $intervention=$interventions->rows[$i];
            $interventions->fetch($intervention->rowid);
            $interventions->delete();
        }

        //kilometrages
        $kilometrage->fetchAll('','',0,0,'AND vehicule = '.$id);
        if(count($kilometrage->rows) > 0){
            for ($i=0; $i < count($kilometrage->rows) ; $i++) {
                $kilometre=$kilometrage->rows[$i];
                $kilometrage->fetch($kilometre->rowid);
                $kilometrage->delete();
            }
        }

        //suivi_essence
        $suivi_essence->fetchAll('','',0,0,'AND vehicule = '.$id);
        if(count($suivi_essence->rows) > 0){
            for ($i=0; $i < count($suivi_essence->rows) ; $i++) {
                $essence=$suivi_essence->rows[$i];
                $suivi_essence->fetch($essence->rowid);
                $suivi_essence->delete();
            }
        }

        //costs_vehicules
        $costs->fetchAll('','',0,0,'AND vehicule = '.$id);
        if(count($costs->rows) > 0){
            for ($i=0; $i < count($costs->rows) ; $i++) {
                $cost=$costs->rows[$i];
                $costs->fetch($cost->rowid);
                $costs->delete();
            }
        }
        header('Location: index.php?delete='.$id.'&page='.$page);
        exit;
    }
    else {
        header('Location: card.php?delete=1&page='.$page);
        exit;
    }
}


if( ($id && empty($action)) || $action == "delete" ){

    // $h = 0;
    // $head = array();
    // $head[$h][0] = dol_buildpath("/parcautomobile/card.php?id=".$id, 1);
    // $head[$h][1] = $langs->trans($modname);
    // $head[$h][2] = 'affichage';
    // $h++;
    // dol_fiche_head($head,'affichage',"",0,"logo@avancementtravaux");


    if($action == "delete"){
        print $form->formconfirm("card.php?id=".$id."&page=".$page,$langs->trans('Confirmation') , $langs->trans('msgconfirmdelet'),"confirm_delete", 'index.php?page='.$page, 0, 1);
    }

    // if (!$user->rights->avancementtravaux->gestion->consulter) {
    //     accessforbidden();
    // }
    // $avancementtravaux->fetchAll('','',0,0,' and rowid = '.$id);
    $parc->fetch($id);
    $item = $parc;
    $object = new vehiculeparc($db);
    $object->fetch($item->rowid);

    $extrafields = new ExtraFields($db);
    $extrafields->fetch_name_optionals_label($object->table_element);
    $object->fetch($item->rowid);
    $object->fetch_optionals();



    dol_include_once('/parcautomobile/class/parcautomobile.class.php');
    $parcautomobile = new parcautomobile($db);
    $linkback = '<a href="./index.php?page='.$page.'">'.$langs->trans("BackToList").'</a>';
    print $parcautomobile->showNavigations($item, $linkback);




    // $extrafields = new ExtraFields($db);
    // $extralabels=$extrafields->fetch_name_optionals_label($item->table_element);
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" class="card_parc">';

    print '<input type="hidden" name="confirm" itemue="no" id="confirm" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
    print '<div  class="td_h">';
        if(!empty($conf->global->PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL)){
            if($item->sendmail) $checked = 'checked';
            print '<div class="test_sendmail"><input id="cb1" disabled class="flat checkforselect" type="checkbox" '.$checked.' name="sendmail" value="1"> <span class="title_sendmail">'.$langs->trans("test_sendmail_vehicul").'</span></div>';
        }
        print '<div class="alletapesrecru">';
        $statut->fetchAll();
        $statuts='';
        for($i=0; $i < count($statut->rows); $i++){
            $statu = $statut->rows[$i];
            $statuts .='<label class="etapes" >';
                $statuts .= '<input type="radio" id="'.$statu->rowid.'" style="display:none;" value="'.$statu->rowid.'" name="etape" class="etapes">';
                $statuts .= ' <span class="radio"></span>';
                $statuts .= '<span style="font-size:14px"> '.$langs->trans($statu->label).'</span>';
            $statuts .= '</label>';
        }

            $statuts = str_replace('<input type="radio" id="'.$item->statut.'"', '<input type="radio" id="'.$item->statut.'" checked ', $statuts);
            print $statuts;
        print '</div>';
    print '</div>';
    print '<table class="border nc_table_" width="100%">';
        print '<tbody>';

        print '<tr>';
            print '<td rowspan="2" class="info_vehicule">';
                if($item->model){
                    $model   = new modeles($db);
                    $marque  = new marques($db);
                    $model->fetch($item->model);
                    $marque->fetch($model->marque);
                     if(!empty($marque->logo)){
                        $minifile = getImageFileNameForSize($marque->logo, '');
                        $dt_files = getAdvancedPreviewUrl('parcautomobile', '/marques/'.$marque->rowid.'/'.$minifile, 1, '&entity='.(!empty($object->entity)?$object->entity:$conf->entity));

                        print '<img align="left" class="photo" height="" title="'.$minifile.'" alt="Fichier binaire" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=parcautomobile&entity='.(!empty($object->entity)?$object->entity:$conf->entity).'&file=marques/'.$marque->rowid.'/'.$minifile.'&perm=download" border="0" name="image" >';
                    }
                }
            print '</td>';
            print '<td>';
                print '<span class="label_vehicule">'.$parc->get_nom($item->rowid).'</span>';
            print '</td>';
            // print '<td></td>';
        print '</tr>';


        print '<tr>';
            // print '<td></td>';
            print '<td>';
                print '<span><b>'.$item->plaque.'<b> &nbsp;</span>';
                $etiquet=explode(',', $item->etiquettes);
                foreach ($etiquet as $key => $value) {
                    $etiquettes->fetch($value);
                    print '<span class="etiquette" style="background-color:'.$etiquettes->color.'">'.$etiquettes->label.'</span>&nbsp;&nbsp;';
                }
            print '</td>';
        print '</tr>';
        print '</tbody>';
    print '</table>';

    print '<div id="div_2">';
        print '<div class="div_left">';
            print '<div class="title_div"> <span>'.$langs->trans("propriete").'</span> </div>';
            print '<table class="border nc_table_" width="100%">';
                print '<tbody>';
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('conducteur').'</td>';
                        $user_->fetch($item->conducteur);
                        print '<td >'.$user_->firstname.' '.$user_->lastname.'</td>';
                    print '</tr>';
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('lieu').'</td>';
                        print '<td >'.$item->lieu.'</td>';
                    print '</tr>';
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('num_chassi').'</td>';
                        print '<td >'.$item->num_chassi.'</td>';
                    print '</tr>';
                     print '<tr>';
                        print '<td align="left" >'.$langs->trans('nb_place').'</td>';
                        print '<td >'.$item->nb_place.' </td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('nb_port').'</td>';
                        print '<td >'.$item->nb_porte.'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('color').'</td>';
                        print '<td style="text-shadow: '.$item->color.' 1px 0 9px;">'.$item->color.'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('anne_model').'</td>';
                        print '<td >'.$item->anne_model.'</td>';
                    print '</tr>';
                    $date=explode('-',$item->date_immatriculation);
                    $date_immatriculation = $date[2].'/'.$date[1].'/'.$date[0];
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('date_immatriculation').'</td>';
                        print '<td >'.$date_immatriculation.'</td>';
                    print '</tr>';
                    $date=explode('-',$item->date_contrat);
                    $date_contrat = $date[2].'/'.$date[1].'/'.$date[0];
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('date_contrat').'</td>';
                        print '<td >'.$date_contrat.'</td>';
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
                            print $item->kilometrage.' '.$item->unite;
                            print '</td>';
                    print '</tr>';

                     print '<tr>';
                        print '<td >'.$langs->trans('transmission').'</td>';
                        print '<td >'.$langs->trans($item->transmission).'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('emission_co2').'</td>';
                        print '<td >'.$item->emission_co2.' g/km</t>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('nb_chevaux').'</td>';
                        print '<td >'.$item->nb_chevaux.'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('tax').'</td>';
                        print '<td >'.$item->tax.'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('puissance').'</td>';
                        print '<td >'.$item->puissance.' kW</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('type_carburant').'</td>';
                        print '<td >'.$langs->trans($item->type_carburant).'</td>';
                    print '</tr>';
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('valeur_catalogue').'</td>';
                        print '<td >'.$item->value_catalogue.'</td>';
                    print '</tr>';
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('value_residuelle').'</td>';
                        print '<td >'.$item->value_residuelle.'</td>';
                    print '</tr>';

                print '</tbody>';
            print '</table>';
        print '</div>';
    print '</div>';



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

    print '<div id="lightbox" style="display:none;"><p>X</p><div id="content"><img src="" /></div></div>';

}

?>

<script>
    $(document).ready(function(){

         $('.delete_copie').click(function(e) {
            e.preventDefault();
            var filename = $(this).data("file");
            var file_deleted = $('#copie_deleted').val();
            if( file_deleted == '' )
                $('#copie_deleted').val(filename);
            else
                $('#copie_deleted').val(file_deleted+','+filename);
            $(this).parent('li').remove();
        });

        $('.lightbox_trigger').click(function(e) {
            e.preventDefault();
            var image_href = $(this).attr("href");
            $('#lightbox #content').html('<img src="' + image_href + '" />');
            $('#lightbox').show();
        });

        $('#lightbox,#lightbox p').click(function() {
            $('#lightbox').hide();
        });
        $('.etapes').attr('disabled','disabled');
        $('.appreciation').attr('disabled','disabled');
    });
</script>
