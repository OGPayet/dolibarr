<?php

    if ($action == 'create' && $request_method === 'POST') {

        $kilometre= GETPOST('kilometrage');
        $vehicule = GETPOST('vehicule');
        $unite = GETPOST('unite');
        $date_=explode('/', GETPOST('date'));
        $date=$date_[2].'-'.$date_[1].'-'.$date_[0];
        
        $object = new kilometrage($db);
        $max = $object->Max_kilometrage($vehicule);

        $object->vehicule = $vehicule;
        $object->kilometrage = $kilometre;
        $object->unite = $unite;
        $object->date = $date;
        $ret = $extrafields->setOptionalsFromPost(null, $object);
        $avance = $object->create(1);
        
        $object->fetch($avance);
        // If no SQL error we redirect to the request card
        if ($avance > 0 ) {
            if($kilometre > $max){
                $objvehicul = new vehiculeparc($db);
                $objvehicul->fetch($vehicule);
                $objvehicul->kilometrage = $kilometre;
                $objvehicul->update($vehicule);
            }
            header('Location: ./card.php?id='. $avance.'');
            exit;
        } 
        else {
            header('Location: card.php?action=request&error=SQL_Create&msg='.$recrutement->error);
            exit;
        }
    }

    if($action == "add"){
        $id_poste=GETPOST('poste');
        print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" >';

        print '<input type="hidden" name="action" value="create" />';
        print '<input type="hidden" name="page" value="'.$page.'" />';
        print '<input type="hidden" name="poste" value="'.$id_poste.'" />';
            print '<table class="border nc_table_" width="100%">';
                print '<tbody>';

                print '<tr>';
                    print '<td >'.$langs->trans('date').'</td>';
                    print '<td ><input type="text" name="date" class="datepickerparc" ></td>';
                print '</tr>';

                print '<tr>';
                    print '<td >'.$langs->trans('vehicule').'</td>';
                    print '<td >'.$vehicule->select_with_filter().'</td>';
                print '</tr>';

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
                print '<div class="topheaderrecrutmenus" style="text-align:left !important"><span>'.$langs->trans('champs_add').'</span></div>';
                print '<div class="div_extrafield">';
                    print '<table class="noborder nc_table_" width="100%">';
                        print '<body>';
                            print '<tr class="liste_titre"> <th colspan="2">'.$langs->trans("champs_add").'</th> </tr>';
                            print '<tr>';
                                $object = new kilometrage($db);
                                include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';
                            print '</tr>';
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
                print '<input type="submit" style="display:none" id="sub_valid" value="'.$langs->trans('Validate').'" name="bouton" class="butAction" />';
                print '<a  class="butAction" id="btn_valid">'.$langs->trans('Validate').'</a>';

                print '<a href="./index.php?page='.$page.'" class="butAction">'.$langs->trans('Cancel').'</a>';
            print '</tr>';
            print '</table>';

        print '</form>';
    }

?>

<script>
    $(document).ready(function() {
        $('#btn_valid').click(function(){
            $('#sub_valid').trigger('click');
        })
    })
</script>
