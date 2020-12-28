<?php

    if ($action == 'create' && $request_method === 'POST') {


        $label = GETPOST('label');


        $insert = array(
            'label'         =>  addslashes($label),
        );
        $avance = $marque->create(1,$insert);
        $marque->fetch($avance);

        if ($avance > 0 ) {

            $upload_dir = $conf->parcautomobile->dir_output.'/marques/'.$avance.'/';
            if(!empty($_FILES['logo']['name'])){
                // if($marque->logo_soc){
                //     $file=$upload_dir."/".$item->logo;
                //     unlink($file);
                // }
                $TFile = $_FILES['logo'];
                $logo = array('logo' => dol_sanitizeFileName($TFile['name'],''));
                if (dol_mkdir($upload_dir) >= 0)
                {
                    $destfull = $upload_dir.$TFile['name'];
                    $info     = pathinfo($destfull);

                    $filname    = dol_sanitizeFileName($TFile['name'],'');
                    $destfull   = $info['dirname'].'/'.$filname;
                    $destfull   = dol_string_nohtmltag($destfull);
                    $resupload  = dol_move_uploaded_file($TFile['tmp_name'], $destfull, 0, 0, $TFile['error'], 0);
                    $marque->update($avance,$logo);
                }
            }

            header('Location: ./index.php?page='.$page);
            exit;
        }
        else {
            header('Location: card.php?action=request&error=SQL_Create&msg='.$parcautomobile->error);
            exit;
        }
    }

    if($action == "add"){
        $id_poste=GETPOST('poste');
        print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="" >';

        print '<input type="hidden" name="action" value="create" />';
        print '<input type="hidden" name="page" value="'.$page.'" />';
        print '<input type="hidden" name="poste" value="'.$id_poste.'" />';
        print '<table class="border nc_table_" width="100%">';
            print '<tbody>';

            print '<tr>';
                print '<td >'.$langs->trans('label_marque').'</td>';
                print '<td ><input type="text" class="" id="label"  style="padding:8px 0px 8px 8px; width:100%" name="label"  autocomplete="off"/>';
                print '</td>';
            print '</tr>';

            print '<tr>';
                print '<td>'.$langs->trans('photo_').'</td>';
                print '<td>';
                    print '<input type="file" name="logo" id="logo" style="display:none;">';
                    print '<input type="text" id="name" style="display:none;" readonly>';
                    print '<a class="butAction" id="importer" >'.$langs->trans('importer').'</a>';
                    print '<input type="text" name="url" id="url" style="display:none">';
                print '</td>';
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
    })
</script>
