<?php

if ($action == 'update' && $request_method === 'POST') {

    $page  = GETPOST('page');

    $id=GETPOST('id');

    $label = GETPOST('label');


    $data = array(
        'label'         =>  $label,
    );

    $isvalid = $typecontrat->update($id, $data);

    if ($isvalid > 0) {
        header('Location: ./index.php?page='.$page);
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
            $typecontrat->fetch($id);
            $item = $typecontrat;
            print '<tr>';
                print '<td width="200px">'.$langs->trans('label_typecontrat').'</td>';
                print '<td ><input type="text" class="" id="label" value="'.$item->label.'" style="padding:8px 0px 8px 8px; width:100%" name="label"  autocomplete="off"/>';
                print '</td>';
            print '</tr>';
        print '</tbody>';
    print '</table>';


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
