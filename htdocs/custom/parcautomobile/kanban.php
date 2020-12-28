<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom"


dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
dol_include_once('/parcautomobile/class/etiquettes_parc.class.php');
dol_include_once('/parcautomobile/class/statut.class.php');
dol_include_once('/parcautomobile/class/marque.class.php');
dol_include_once('/parcautomobile/class/modeles.class.php');
dol_include_once('/parcautomobile/lib/parcautomobile.lib.php');
dol_include_once('/core/class/html.form.class.php');

$modname = $langs->trans("vehicules");

$langs->load('parcautomobile@parcautomobile');

$vehicules         = new vehiculeparc($db);
$vehicules2         = new vehiculeparc($db);
$statut         = new statut($db);
$model         = new modeles($db);
$marque         = new marques($db);
$user_ = new User($db);
$etiquettes_parc     = new etiquettes_parc($db);
$selectyear         = GETPOST('selectyear');

// if (!empty($selectyear) && $selectyear != -1 ) {
//   $filter .= " AND YEAR(date_depot) = '".$selectyear."'";
// }
// elseif($selectyear == -1){
//   $filter.='';
// }

$limit  = $conf->liste_limit+1;
$page   = GETPOST("page",'int');
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$nbrtotal = $vehicules->fetchAll($sortorder, $sortfield, $limit, $offset, $filter);
$nbrtotalnofiltr = $vehicules2->fetchAll();


$arretiquette = $etiquettes_parc->getEtiquetteByRowid();
// print_r($arretiquette);

$morejs  = array();
llxHeader(array(), $modname,'','','','',$morejs,0,0);

print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $nbrtotal, $nbrtotalnofiltr);
// print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $nbrtotal, $nbrtotalnofiltr,'',0,'','',$limit);


// print '<link rel="stylesheet" href="https://www.jqwidgets.com/jquery-widgets-documentation/jqwidgts/styles/jqx.base.css" type="text/css" />';
print '<link rel="stylesheet" href="'.dol_buildpath('/parcautomobile/css/kanban.css',2).'" type="text/css" />';
print '<script type="text/javascript" src="'.dol_buildpath('/parcautomobile/js/jqxcore.js',2).'"></script>';
print '<script type="text/javascript" src="'.dol_buildpath('/parcautomobile/js/jqxsortable.js',2).'"></script>';
print '<script type="text/javascript" src="'.dol_buildpath('/parcautomobile/js/jqxkanban.js',2).'"></script>';
print '<script type="text/javascript" src="'.dol_buildpath('/parcautomobile/js/jqxdata.js',2).'"></script>';

print '<form method="get" action="'.$_SERVER["PHP_SELF"].'" class="kanban_parc">'."\n";
  print '<div style="float: left; margin-bottom: 8px; width:100%;">';
      print '<div style="width:10%; float:left;" >';
          print '<a class="icon_list" data-type="list" href="'.dol_buildpath("/parcautomobile/index.php",2).'"> <img  src="'.dol_buildpath("/parcautomobile/img/list.png",2).'" style="height:30px" id="list" ></a>';
          print '<a class="icon_list" data-type="grid" href="'.dol_buildpath("/parcautomobile/kanban.php",2).'"> <img src="'.dol_buildpath("/parcautomobile/img/grip.png",2).'" style="height:30px" id="grid" ></a> ';
      print '</div>';

      print '<div class="statusdetailcolorsback" style="">';
          $statut->fetchAll();
          $parcautomobile = new vehiculeparc($db);
          $parcautomobile->fetchAll();
          $arr_statut=[];
          for ($i=0; $i <count($statut->rows); $i++) {
            $etape=$statut->rows[$i];
            $arr_statut[$etape->rowid]=0;
            for ($j=0; $j < count($parcautomobile->rows) ; $j++) {
              $parc=$parcautomobile->rows[$j];
              if($parc->statut == $etape->rowid){ $arr_statut[$etape->rowid]++; };
            }
              print '<span class="statusname STATUSPROPAL_0">';
                print '<span class="colorstatus" style="background:'.$etape->color.';"></span>';
                print '<span class="labelstatus"><span class="counteleme">'.$arr_statut[$etape->rowid].'</span></span>&nbsp';
                print $langs->trans($etape->label);
              print '</span>';
          }
          // print_r($arr_statut);die();
      print '</div>';

       print '<div style="width:20%; float:right;" >';
          print '<a href="card.php?action=add" class="butAction" id="add" >'.$langs->trans("Add").'</a>';
      print '</div>';
  print '</div>';

  // print '<div style="width:100%; float:left" >';
  //     print'<select style="float:left;" id="selectyear" name="selectyear">';
  //       $years = $parcauromobile->getYears("date_depot");
  //       // die($selectyear);
  //       print'<option value="-1" >Toutes</option>';
  //       krsort($years);
  //       foreach ($years as $key => $value) {
  //         $slctd2="";
  //         if($key == $selectyear){
  //           $slctd2="selected";
  //         }
  //         print'<option value="'.$key.'" '.$slctd2.'>'.$key.'</option>';
  //       }
  //     print'</select>';
  //     print '<input type="image" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
  // print '</div>';
print '</form>';
print '<div style="clear:both;"></div>';
print '<div id="kanban"></div>';

?>

<script type="text/javascript">
        $(document).ready(function () {
            var fields = [
                     { name: "id", type: "string" },
                     { name: "status", map: "state", type: "string" },
                     { name: "text", map: "label", type: "string" },
                     { name: "tags", type: "string" },
                     { name: "color", map: "hex", type: "string" },
                     { name: "resourceId", type: "number" }
            ];
            var source =
             {
                 localData: [
                      <?php
                        $statut->fetchAll();
                        $vehicules->fetchAll();
                        for ($i=0; $i < count($statut->rows); $i++) {
                            $etape = $statut->rows[$i];
                            for ($k=0; $k < count($vehicules->rows); $k++) {
                              $vehicule = $vehicules->rows[$k];
                              if($vehicule->statut == $etape->rowid){
                                $tag='';
                                  $etiquettes=explode(",", $vehicule->etiquettes);
                                  if($etiquettes){
                                    foreach($etiquettes as $key){
                                        $tag .= $langs->trans($arretiquette[$key]['label']).":".$arretiquette[$key]['color'].",";
                                    }
                                  }
                                  $tag = trim($tag,",");
                                  $user_->fetch($vehicule->conducteur);
                                  $conducteur = $user_->firstname.' '.$user_->lastname ;
                                  $model->fetch($vehicule->model);
                                  $marque->fetch($model->marque);
                                  $plaq =  $vehicule->plaque.':'.$vehicules->get_nom($vehicule->rowid);


                                  $minifile = getImageFileNameForSize($marque->logo, '');
                                  $urlfile = DOL_URL_ROOT.'/viewimage.php?modulepart=parcautomobile&entity='.$conf->entity.'&file=marques/'.$marque->rowid.'/'.$minifile.'&perm=download';
                                  $img='<img src=\''.$urlfile.'\' height=\'40px\'>';


                                  $label='<div>';
                                    $label.='<div class=\'d_left\'  style=\'padding-right: 8px;\'>'.$img.'</div>';
                                    // $label.='<div class=\'d_right\'>';
                                    $label.='<div>';
                                    $label.='<a href=\''.dol_buildpath('/parcautomobile/card.php?id='.$vehicule->rowid,2).'\'>';
                                    $label.='<b class=\'plaq\'>'.$plaq.'</b></a><br><span class=\'conducteur\'>'.$conducteur.'</span><br><span class=\'lieu\'>'.$vehicule->lieu.'</span>';
                                    $label.='</div>';
                                  $label.='</div>';
                                  print '{id:"'.$vehicule->rowid.'",state:"etape_'.$etape->rowid.'", label: "'.$label.'", tags:"'.$tag.'", hex: "'.$vehicule->color.'", resourceId: "'.$vehicule->rowid.'" },';
                              }
                            }
                        }
                      ?>
                 ],
                 dataType: "array",
                 dataFields: fields
             };
            var dataAdapter = new $.jqx.dataAdapter(source);
            var resourcesAdapterFunc = function () {
                var resourcesSource =
                {
                    localData: [
                    <?php
                      print '{ id: "", name: "", image: "'.dol_buildpath('/parcautomobile/img/user.png',2).'", common: true },';
                    ?>
                    ],
                    dataType: "array",
                    dataFields: [
                         { name: "id", type: "number" },
                         { name: "name", type: "string" },
                         { name: "image", type: "string" },
                         { name: "common", type: "boolean" }
                    ]
                };
                var resourcesDataAdapter = new $.jqx.dataAdapter(resourcesSource);
                return resourcesDataAdapter;
            }
            $('#kanban').jqxKanban({
                resources: resourcesAdapterFunc(),
                source: dataAdapter,
                columns: [
                <?php
                  $statut->fetchAll();
                  for ($i=0; $i < count($statut->rows); $i++) {
                    $item = $statut->rows[$i];
                    print '{ text: "'.$langs->trans($item->label).'", dataField: "etape_'.$item->rowid.'" },';
                  }
                    // { text: "Backlog", dataField: "new" },
                    // { text: "In Progress", dataField: "work" },
                    // { text: "Done", dataField: "done" }
                ?>
                ]
            });

            $('#kanban').on('itemMoved', function (event) {
                var args = event.args;
                var itemId = args.itemId;
                var oldParentId = args.oldParentId;
                var newParentId = args.newParentId;
                var itemData = args.itemData;
                var oldColumn = args.oldColumn;
                var newColumn = args.newColumn;
                var data_old = newColumn['dataField'];
                data_old = data_old.split('_');
                $id_old = data_old[1];
                var data_new = newColumn['dataField'];
                data_new = data_new.split('_');
                $id_new = data_new[1];
                $.ajax({
                  url:'<?php echo dol_buildpath('/parcautomobile/movement.php',2) ?>',
                  data:{'id_item':itemId,'id_etat':$id_new,},
                  type:'POST',
                  success:function(data){
                  }
                });
            });
        });
</script>

<?php
llxFooter();
if (is_object($db)) $db->close();
?>