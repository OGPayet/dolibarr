<?php

if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {
    
    if (!$id || $id <= 0) {
        header('Location: ./card.php?action=request&error=dalete_failed&id='.$id);
        exit;
    }

    $page  = GETPOST('page');

    $costs->fetch($id);

    $error = $costs->delete();

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
    
    if($action == "delete"){
        print $form->formconfirm("card.php?id=".$id."&page=".$page,$langs->trans('Confirmation') , $langs->trans('msgconfirmdelet'),"confirm_delete", 'index.php?page='.$page, 0, 1);
    }

   
    $object = new costsvehicule($db);
    $object->fetch($id);
    $item = $object;

    
    
    dol_include_once('/parcautomobile/class/parcautomobile.class.php');
    $parcautomobile = new parcautomobile($db);
    $linkback = '<a href="./index.php?page='.$page.'">'.$langs->trans("BackToList").'</a>';
    print $parcautomobile->showNavigations($item, $linkback);



    $extrafields = new ExtraFields($db);
    $extrafields->fetch_name_optionals_label($object->table_element);
    $object->fetch_optionals();
    

    
    
    // print_r('('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')');
   
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" class="card_costvehicul" >';

    print '<input type="hidden" name="confirm" itemue="no" id="confirm" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
    print '<table class="noborder" width="100%">';

        print '<tbody>';
           print '<tr>';
                    print '<td style="width:165px;" align="left">'.$langs->trans('vehicule').'</td>';
                        $objvehicul = new vehiculeparc($db);
                        $objvehicul->fetch($item->vehicule);
                    print '<td align="left">'.$objvehicul->get_nom_url($item->vehicule,1).'</td>';
                print '</tr>';
                print '<tr>';
                    print '<td align="left">'.$langs->trans('type').'</td>';
                    print '<td >'.$item->type.'</td>';
                print '</tr>';

                print '<tr>';
                    print '<td align="left">'.$langs->trans('prix_inter').'</td>';
                    print '<td >'.number_format($item->prix,2,","," ").' ('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</td>';
                print '</tr>';
                $date= explode('-', $item->date);
                $date=$date[2].'/'.$date[1].'/'.$date[0];
                print '<tr>';
                    print '<td align="left">'.$langs->trans('date').'</td>';
                    print '<td >'.$date.'</td>';
                print '</tr>';

                print '<tr>';
                    print '<td align="left">'.$langs->trans('notes').'</td>';
                    print '<td >'.nl2br($item->notes).'</td>';
                print '</tr>';
        print '</tbody>';
    print '</table>';

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
    print '<br><br>';
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
