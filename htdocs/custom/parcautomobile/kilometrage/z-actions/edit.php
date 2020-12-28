<?php

if ($action == 'update' && $request_method === 'POST') {

    $page  = GETPOST('page');

    // $d1 = GETPOST('debut');
    // $f1 = GETPOST('fin');
    $id=GETPOST('id');

    $kilometre= GETPOST('kilometrage');
    $vehicule = GETPOST('vehicule');
    $unite = GETPOST('unite');
    $date_=explode('/', GETPOST('date'));
    $date=$date_[2].'-'.$date_[1].'-'.$date_[0];


    $object = new kilometrage($db);
    $object->fetch($id);

    $object->kilometrage = $kilometre;
    $object->vehicule = $vehicule;
    $object->unite  = $unite;
    $object->date = $date;

    // print_r($object);die();
    $ret = $extrafields->setOptionalsFromPost(null, $object);

    $isvalid = $object->update($id);
    // $composantes_new = (GETPOST('composantes_new'));
    // $composantes = (GETPOST('composantes'));
    // $composants_deleted = explode(',', GETPOST('composants_deleted'));

    if ($isvalid > 0) {
        if($kilometre > $max){
            $objvehicul = new vehiculeparc($db);
            $objvehicul->fetch($vehicule);
            $objvehicul->kilometrage = $kilometre;
            $objvehicul->update($vehicule);
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

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" >';

    print '<input type="hidden" name="action" value="update" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
    print '<table class="border nc_table_" width="100%">';
        print '<tbody>';
            $object = new kilometrage($db);
            $object->fetch($id);
            $item = $object;

            $extrafields = new ExtraFields($db);
            $extrafields->fetch_name_optionals_label($object->table_element);
            // $object->fetch($item->rowid);
            $object->fetch_optionals();

            $date=explode('-', $item->date);
            $date=$date[2].'/'.$date[1].'/'.$date[0];
            print '<tr>';
                print '<td >'.$langs->trans('date').'</td>';
                print '<td ><input type="text" name="date" class="datepickerparc" value="'.$date.'" ></td>';
            print '</tr>';

            print '<tr>';
                print '<td >'.$langs->trans('vehicule').'</td>';
                print '<td >'.$vehicule->select_with_filter($item->vehicule).'</td>';
            print '</tr>';
            $vehicule->fetch($item->vehicule);
            print '<tr>';
                print '<td >'.$langs->trans('kilometrage').'</td>';
                print '<td >';
                    print '<input type="number" name="kilometrage" class="kilometrage" value="'.$item->kilometrage.'" > ';
                    print '<span id="unite">'.$vehicule->unite.'</span>';
                print '</td>';
            print '</tr>';


        print '</tbody>';
    print '</table>';

    if($extrafields->attributes[$object->table_element]['label']){
        print '<div class="fichecenter">';
            print '<div class="div_extrafield">';
                print '<table class="noborder nc_table_" width="100%">';
                    print '<body>';
                        print '<tr class="liste_titre"> <th colspan="2">'.$langs->trans("champs_add").'</th> </tr>';
                        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';
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
            print '<input type="submit" style="display:none" id="sub_valid" value="'.$langs->trans('Validate').'" style="" name="bouton" class="butAction" />';
            print '<a  class="butAction" id="btn_valid">'.$langs->trans('Validate').'</a>';

            print '<a href="./index.php?page='.$page.'" class="butAction">'.$langs->trans('Cancel').'</a>';
        print '</td>';
    print '</tr>';
    print '</table>';

    print '</form>';

    ?>
    <?php
}

?>
<script>
    $(document).ready(function() {
        $('#btn_valid').click(function(){
            $('#sub_valid').trigger('click');
        })
    })
</script>
