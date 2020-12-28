<?php

    if ($action == 'create' && $request_method === 'POST') {


        $vehicule = GETPOST('vehicule');
        $type = GETPOST('type');
        $prix = GETPOST('prix');
        $notes = GETPOST('notes');
        if(GETPOST('date')){
            $date = explode('/', GETPOST('date'));
            $date=$date[2].'-'.$date[1].'-'.$date[0];
        }

        $object = new costsvehicule($db);

        $object->vehicule = $vehicule;
        $object->type = $type;
        $object->prix = $prix;
        $object->date = $date;
        $object->notes = $notes;

        $ret = $extrafields->setOptionalsFromPost(null, $object);
        $avance = $object->create(1);
        // If no SQL error we redirect to the request card
        if ($avance > 0 ) {
            header('Location: ./card.php?id='. $avance.'&action=edit');
            exit;
        }
        else {
            header('Location: card.php?action=request&error=SQL_Create&msg='.$parcautomobile->error);
            exit;
        }
    }

    if($action == "add"){
        $id_poste=GETPOST('poste');
        print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="card_costvehicul">';

        print '<input type="hidden" name="action" value="create" />';
        print '<input type="hidden" name="page" value="'.$page.'" />';
        print '<input type="hidden" name="poste" value="'.$id_poste.'" />';
        print '<table class="noborder" width="100%">';
            print '<tbody>';

                print '<tr>';
                    print '<td style="width:165px;" align="left">'.$langs->trans('vehicule').'</td>';
                    print '<td >'.$vehicule->select_with_filter().'</td>';
                print '</tr>';

                print '<tr>';
                    print '<td align="left">'.$langs->trans('label_type').'</td>';
                    print '<td >'.$costs->select_types().'</td>';
                print '</tr>';

                print '<tr>';
                    print '<td align="left">'.$langs->trans('prix_inter').'</td>';
                    print '<td ><input type="number" step="0.001" class="" id="prix" name="prix"  autocomplete="off"/>';
                    print '('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')';
                    print '</td>';
                print '</tr>';

                print '<tr>';
                    print '<td align="left">'.$langs->trans('date').'</td>';
                    print '<td ><input type="text" class="datepickerparc" id="date" value="'.date("d/m/Y").'" name="date"  autocomplete="off"/>';
                    print '</td>';
                print '</tr>';

                print '<tr>';
                    print '<td align="left">'.$langs->trans('notes').'</td>';
                    print '<td ><textarea name="notes" id="notes_txt"></textarea></td>';
                print '</tr>';

            print '</tbody>';
        print '</table>';
        $object = new costsvehicule($db);
        if($extrafields->attributes[$object->table_element]['label']){
            print '<div class="fichecenter">';
                // print '<div class="topheaderrecrutmenus" style="text-align:left !important"><span>'.$langs->trans('champs_add').'</span></div>';
                print '<div class="div_extrafield">';
                    print '<table class="noborder nc_table_" width="100%">';
                        print '<body>';
                            print '<tr class="liste_titre"> <th colspan="2">'.$langs->trans("champs_add").'</th> </tr>';

                            print '<tr>';
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
