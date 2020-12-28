<?php

if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {

    if (!$id || $id <= 0) {
        header('Location: ./card.php?action=request&error=dalete_failed&id='.$id);
        exit;
    }

    $page  = GETPOST('page');

    $typeintervention->fetch($id);

    $error = $typeintervention->delete();

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


    $typeintervention->fetch($id);
    $item = $typeintervention;



    dol_include_once('/parcautomobile/class/parcautomobile.class.php');
    $parcautomobile = new parcautomobile($db);
    $linkback = '<a href="./index.php?page='.$page.'">'.$langs->trans("BackToList").'</a>';
    print $parcautomobile->showNavigations($item, $linkback);






    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" >';

    print '<input type="hidden" name="confirm" itemue="no" id="confirm" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
    print '<table class="border" width="100%">';

        print '<tbody>';
            print '<tr>';
                print '<td style="width:20% !important">'.$langs->trans('label_typeintervention');
                print '<td style="width:80% !important">'.$item->label.'</td>';
            print '</tr>';
        print '</tbody>';
    print '</table>';


    // Actions
    print '<table class="" width="100%">';
    print '<tr>';
        print '<td colspan="2" >';
            print '<br>';
            print '<a href="./card.php?id='.$id.'&action=edit" class="butAction">'.$langs->trans('Modify').'</a>';
            print '<a href="./card.php?id='.$id.'&action=delete" class="butAction butActionDelete">'.$langs->trans('Delete').'</a>';
            print '<a href="./index.php?page='.$page.'" class="butAction">'.$langs->trans('Cancel').'</a>';
        print '</td>';
    print '</tr>';
    print '</table>';

    print '</form>';

}

?>
