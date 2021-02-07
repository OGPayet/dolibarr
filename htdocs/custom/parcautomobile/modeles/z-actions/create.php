<?php

    if ($action == 'create' && $request_method === 'POST') {


        $label = GETPOST('label');
        $marque = GETPOST('marque');
       

        $insert = array(
            'label'           =>  addslashes($label),
            'marque'          =>  $marque,
        );
        $avance = $modele->create(1,$insert);
        $modele->fetch($avance);
        // If no SQL error we redirect to the request card
        if ($avance > 0 ) {
           
            header('Location: ./index.php?page='.$page);
            exit;
        } 
        else {
            header('Location: card.php?action=request&error=SQL_Create&msg='.$recrutement->error);
            exit;
        }
    }

    if($action == "add"){
        $id_poste=GETPOST('poste');
        print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" id="tab_marque">';

        print '<input type="hidden" name="action" value="create" />';
        print '<input type="hidden" name="page" value="'.$page.'" />';
        print '<input type="hidden" name="poste" value="'.$id_poste.'" />';
        print '<table class="border nc_table_" width="100%">';
            print '<tbody>';

            print '<tr>';
                print '<td style="width:20%;">'.$langs->trans('label_model').'</td>';
                print '<td style="width:80%;"><input type="text" class="" id="label"  style="padding:8px 0px 8px 8px; width:100%" name="label"  autocomplete="off"/>';
                print '</td>';
            print '</tr>';

            print '<tr>';
                print '<td >'.$langs->trans('marque').'</td>';
                print '<td>'.$marques->select_with_filter().'</td>';
            print '</tr>';

            print '</tbody>';
        print '</table>';
       


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
        $('#select_marque').select2();
    })
</script>
