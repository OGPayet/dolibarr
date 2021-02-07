<?php

if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {
    
    if (!$id || $id <= 0) {
        header('Location: ./card.php?action=request&error=dalete_failed&id='.$id);
        exit;
    }

    $page  = GETPOST('page');

    $kilometrage->fetch($id);

    $error = $kilometrage->delete();

    if ($error == 1) {
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
    
    
    $object = new kilometrage($db);
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

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" >';

    print '<input type="hidden" name="confirm" itemue="no" id="confirm" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';

    print '<div class="fichecenter">';    
        // print '<div class="fichethirdleft">';    

        $vehicule->fetch($item->vehicule);
        print '<table class="noborder centpercent" width="100%">';
            print '<tbody>';
                print '<tr class="liste_titre" style="text-align:left !important"><th colspan="2">'.$langs->trans('Details').'</th></tr>';
                $date=explode('-', $item->date);
                $date=$date[2].'/'.$date[1].'/'.$date[0];

                print '<tr>';
                    print '<td style="width:200px">'.$langs->trans('date').'</td>';
                    print '<td >'.$date.'</td>';
                print '</tr>';

                print '<tr>';
                    print '<td >'.$langs->trans('vehicule').'</td>';
                    // print '<td ><a href="'.dol_buildpath('/parcautomobile/card.php?id='.$item->vehicule,2).'">'.$vehicule->getNomUrl(1).'</a></td>';
                    print '<td >'.$vehicule->get_nom_url($item->vehicule,1).'</td>';
                print '</tr>';

                print '<tr>';
                    print '<td >'.$langs->trans('kilometrage').'</td>';
                    print '<td >'.$item->kilometrage.'</td>';
                print '</tr>';

                print '<tr>';
                    print '<td >'.$langs->trans('unite').'</td>';
                    print '<td >'.$vehicule->unite.'</td>';
                print '</tr>';
                
            print '</tbody>';
        print '</table>';
        // print '</div>';

        // print '<div class="fichetwothirdright">';    
        //     print '<div class="ficheaddleft">';    
        //         print '<table class="noborder centpercent" width="100%">';
        //             print '<tbody>';
        //                 print '<tr class="liste_titre"> <th colspan="2">'.$langs->trans("detail_vehicul").'</th> </tr>';
        //                 print '<tr>';
        //                     print '<td align="left" >'.$langs->trans('vehicule').'</td>';
        //                     print '<td ><a href="'.dol_buildpath('/parcautomobile/card.php?id='.$item->vehicule,2).'" >'.$vehicule->get_nom($item->vehicule,1).'</a></td>';
        //                 print '</tr>';
        //                 if($vehicule->conducteur){
        //                     $user_ = new User($db);
        //                     $user_->fetch($vehicule->conducteur);
        //                     print '<tr>';
        //                         print '<td align="left" >'.$langs->trans('conducteur').'</td>';
        //                         print '<td >'.$user_->getNomUrl(1).'</td>';
        //                     print '</tr>';
        //                 }
        //             print '</tbody>';
        //         print '</table>';
        //     print '</div>';
        // print '</div>';
    print '</div>';

    print '<br><br>';

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
    
    print '<div id="lightbox" style="display:none;"><p>X</p><div id="content"><img src="" /></div></div>';
    
}

?>
