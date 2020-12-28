<?php

if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {

    if (!$id || $id <= 0) {
        header('Location: ./card.php?action=request&error=dalete_failed&id='.$id);
        exit;
    }

    $page  = GETPOST('page');

    $suivi_essence->fetch($id);

    $error = $suivi_essence->delete();

    if ($error == 1) {
        $costs->fetchAll('','',0,0,'AND id_suiviessence ='.$id);
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
    $object = new suivi_essence($db);
    $object->fetch($id);
    $item = $object;

    $extrafields = new ExtraFields($db);
    $extrafields->fetch_name_optionals_label($object->table_element);

    dol_include_once('/parcautomobile/class/parcautomobile.class.php');
    $parcautomobile = new parcautomobile($db);
    $linkback = '<a href="./index.php?page='.$page.'">'.$langs->trans("BackToList").'</a>';
    print $parcautomobile->showNavigations($item, $linkback);






    // $extrafields = new ExtraFields($db);
    // $extralabels=$extrafields->fetch_name_optionals_label($item->table_element);
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" class="card_suiviessenc">';

    print '<input type="hidden" name="confirm" itemue="no" id="confirm" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
    print '<div class="div_1">';
        print '<div class="div_left">';
            print '<div class="title_div"> <span>'.$langs->trans("detail_vehicul").'</span> </div>';
            print '<table class="border nc_table_" width="100%">';
                print '<tbody>';
                     $vehicules->fetch($item->vehicule);
                      print '<tr>';
                            print '<td align="left" >'.$langs->trans('vehicule').'</td>';
                            print '<td ><a href="'.dol_buildpath('/parcautomobile/card.php?id='.$item->vehicule,2).'" >'.$vehicules->get_nom($item->vehicule,1).'</a></td>';
                        print '</tr>';

                print '</tbody>';
            print '</table>';
        print '</div>';
        print '<div class="div_right">';
            print '<div class="title_div"> <span>'.$langs->trans("detail_carb").'</span> </div>';
            print '<table class="border nc_table_" width="100%">';
                print '<tbody>';
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('litre').'</td>';
                        print '<td >'.$item->litre.' Litre</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('prix').'</td>';
                        print '<td > '.number_format($item->prix,2,',',' ').'    ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).') </td>';
                    print '</tr>';
                    $prix_T=$item->litre*$item->prix;
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('prix_T').'</td>';
                        print '<td id="prix_T" >'.number_format($prix_T,2,',',' ').'   ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</td>';
                    print '</tr>';
                print '</tbody>';
            print '</table>';
        print '</div>';
    print '</div>';

    print '<div class="div_2">';
        print '<div class="div_left">';
            print '<div class="title_div"> <span>'.$langs->trans("detail_kilom").'</span> </div>';
            print '<table class="border nc_table_" width="100%">';
                print '<tbody>';
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('kilometrique').'</td>';
                        if($vehicules->unite)
                            print '<td >'.$item->kilometrage.' '.$langs->trans($vehicules->unite).'</td>';
                        else
                            print '<td >'.$item->kilometrage.'</td>';
                    print '</tr>';
                print '</tbody>';
            print '</table>';
        print '</div>';
        print '<div class="div_right">';
            print '<div class="title_div"> <span>'.$langs->trans("info_suplm").'</span> </div>';
            print '<table class="border nc_table_" width="100%">';
                print '<tbody>';
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('acheteur').'</td>';
                        if($vehicules->conducteur > 0){
                            $user_->fetch($vehicules->conducteur);
                            print '<td id="acheteur">'.$user_->getNomUrl(1).'</td>';
                        }else
                            print '<td id="acheteur"></td>';
                    print '</tr>';



                    $date=explode('-',$item->date);
                    $date = $date[2].'/'.$date[1].'/'.$date[0];
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('date').'</td>';
                        print '<td >'.$date.'</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('ref_facture').'</td>';
                        print '<td >'.$item->ref_facture.'</td>';
                    print '</tr>';
                    print '<tr>';
                        print '<td align="left" >'.$langs->trans('fournisseur').'</td>';
                        if($item->fournisseur > 0){
                            $soc->fetch($item->fournisseur);
                            print '<td >'.$soc->getNomUrl(1).'</td>';
                        }else
                            print '<td ></td>';
                    print '</tr>';
                print '</tbody>';
            print '</table>';
        print '</div>';
    print '</div>';

    print '<div id="remarques">';
        print '<div class="cnd">'.$langs->trans('remarques').':</div>';
        print '<div class="txt_condition in_show">'.nl2br($item->remarques).'</div>';
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

    print '<div id="lightbox" style="display:none;"><p>X</p><div id="content"><img src="" /></div></div>';

}

?>
<style>

</style>
