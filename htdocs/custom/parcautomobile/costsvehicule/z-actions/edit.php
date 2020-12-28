<?php

if ($action == 'update' && $request_method === 'POST') {

    $page  = GETPOST('page');

    // $d1 = GETPOST('debut');
    // $f1 = GETPOST('fin');
    $id=GETPOST('id');

    $vehicule = GETPOST('vehicule');
    $type = GETPOST('type');
    $prix = GETPOST('prix');
    $notes = GETPOST('notes');
    if(GETPOST('date')){
        $date = explode('/', GETPOST('date'));
        $date = $date[2].'-'.$date[1].'-'.$date[0];
    }


    $object = new costsvehicule($db);
    $object->fetch($id);

    $object->vehicule = $vehicule;
    $object->type = $type;
    $object->prix = $prix;
    $object->date = $date;
    $object->notes = $notes;

    $ret = $extrafields->setOptionalsFromPost(null, $object);
    $isvalid = $object->update($id);
    // $composantes_new = (GETPOST('composantes_new'));
    // $composantes = (GETPOST('composantes'));
    // $composants_deleted = explode(',', GETPOST('composants_deleted'));

    if ($isvalid > 0) {
        header('Location: ./card.php?id='.$id);
        exit;
    }
    else {
        header('Location: ./card.php?id='. $id .'&update=0');
        exit;
    }
}


if($action == "edit"){

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="card_costvehicul" >';

    print '<input type="hidden" name="action" value="update" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
    print '<table class="noborder" width="100%">';
        print '<tbody>';
            $extrafields = new ExtraFields($db);
            $object = new costsvehicule($db);

            $object->fetch($id);
            $item = $object;

            $extrafields->fetch_name_optionals_label($object->table_element);
            $object->fetch_optionals();


            print '<tr>';
                    print '<td align="left">'.$langs->trans('vehicule').'</td>';
                    print '<td >'.$vehicule->select_with_filter($item->vehicule).'</td>';
                print '</tr>';

                print '<tr>';
                    print '<td align="left">'.$langs->trans('label_type').'</td>';
                    print '<td >'.$costs->select_types($item->type).'</td>';
                print '</tr>';

                print '<tr>';
                    print '<td align="left">'.$langs->trans('prix_inter').'</td>';
                    print '<td ><input type="number" step="0.001" class="" id="prix" name="prix" value="'.$item->prix.'" autocomplete="off"/>';
                    print '('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')';
                    print '</td>';
                print '</tr>';
                $date= explode('-', $item->date);
                $date=$date[2].'/'.$date[1].'/'.$date[0];
                print '<tr>';
                    print '<td align="left"td >'.$langs->trans('date').'</td>';
                    print '<td ><input type="text" class="datepickerparc" id="date" value="'.$date.'"  name="date"  autocomplete="off"/>';
                    print '</td>';
                print '</tr>';

                print '<tr>';
                    print '<td align="left">'.$langs->trans('notes').'</td>';
                    print '<td ><textarea name="notes_txt" id="notes">'.$item->notes.'</textarea></td>';
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
    print '<br><br>';
    print '<table class="" width="100%">';
    print '<tr>';
        print '<td colspan="2" >';
            print '<br>';
            print '<input type="submit" style="display:none" id="sub_valid"  value="'.$langs->trans('Validate').'" style="" name="bouton" class="butAction" />';
            print '<a  class="butAction" id="btn_valid">'.$langs->trans('Validate').'</a>';

            print '<a href="./index.php?page='.$page.'" class="butAction">'.$langs->trans('Cancel').'</a>';
        print '</td>';
    print '</tr>';
    print '</table>';

    print '</form>';
    print '<div id="lightbox" style="display:none;"><p>X</p><div id="content"><img src="" /></div></div>';

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
