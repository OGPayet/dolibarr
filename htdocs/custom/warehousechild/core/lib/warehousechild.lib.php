<?php

/**
  @brief Brief construct html table section, type of link vs qualityreport
  @note
  Require $formfile declared
 */
function DrawLink($object
, $key
, $element
, $classname
, $tablename
, $datefieldname
, $langtoload
, $title
)
{

    global $langs, $conf, $user, $formfile;



    print load_fiche_titre($langs->trans($title), $addform, '');

    print "\n".'<!-- Table for tablename = '.$tablename.' -->'."\n";
    print '<table class="noborder" width="100%">';

    print '<tr class="liste_titre">';
    // Ref
    print '<td'.(($tablename != 'actioncomm' /* && $tablename != 'taskin' */) ? ' ' : '').'>'.$langs->trans("Ref").'</td>';

    // Status
    if (in_array($tablename, array('taskin'))) print '<td align="right" width="200">'.$langs->trans("ProgressDeclared").'</td>';
    else print '<td align="right" width="200">'.$langs->trans("Status").'</td>';
    print '</tr>';

    $object->id   = $element->id;
    $elementarray = $object->get_element_list('qualityreport', $key, $datefieldname, $dates, $datee);
    if (is_array($elementarray) && count($elementarray) > 0) {
        $var       = true;
        $total_ht  = 0;
        $total_ttc = 0;

        $total_ht_by_third  = 0;
        $total_ttc_by_third = 0;

        $saved_third_id = 0;
        $breakline      = '';

// 			if (canApplySubtotalOn($tablename))
// 			{
// 			   // Sort
// 			   $elementarray = sortElementsByClientName($elementarray);
// 			}

        $num = count($elementarray);
        for ($i = 0; $i < $num; $i++) {
            $tmp             = explode('_', $elementarray[$i]);
            $idofelement     = $tmp[0];
            $idofelementuser = $tmp[1];

            $object->fetch($idofelement);


// 				if ($idofelementuser) $elementuser->fetch($idofelementuser);
// 				if ($tablename != 'expensereport_det')
// 				{
// 					$element->fetch_thirdparty();
// 				}
// 				else
// 				{
// 					$expensereport=new ExpenseReport($db);
// 					$expensereport->fetch($element->fk_expensereport);
// 				}
            //print 'xxx'.$tablename;
// 				print $classname;

            if ($breakline && $saved_third_id != $object->thirdparty->id) {
                print $breakline;
                $var = true;

                $saved_third_id = $object->thirdparty->id;
                $breakline      = '';

                $total_ht_by_third  = 0;
                $total_ttc_by_third = 0;
            }
            $saved_third_id = $object->thirdparty->id;

            $qualifiedfortotal = true;
            if ($key == 'invoice') {
                if (!empty($object->close_code) && $object->close_code == 'replaced') $qualifiedfortotal = false; // Replacement invoice, do not include into total
            }

            $var = !$var;
            print "<tr ".$bc[$var].">";
            // Ref
            print '<td align="left">';

            if ($tablename == 'expensereport_det') {
                print $expensereport->getNomUrl(1);
            } else {
                if ($element instanceof Taskin) {
                    print $element->getNomUrl(1, 'withqualityreport', 'time');
                    print ' - '.dol_trunc($element->label, 48);
                } else print $object->getNomUrl(1,'',1);

                $element_doc = $object->element;
                $filename    = dol_sanitizeFileName($object->ref);
                $filedir     = $conf->{$element_doc}->dir_output.'/'.dol_sanitizeFileName($object->ref);

// 					if($element_doc === 'order_supplier') {
// 						$element_doc='commande_fournisseur';
// 						$filedir = $conf->fournisseur->commande->dir_output.'/'.dol_sanitizeFileName($element->ref);
// 					}
// 					else if($element_doc === 'invoice_supplier') {
// 						$element_doc='facture_fournisseur';
// 						$filename = get_exdir($element->id,2,0,0,$this,'product').dol_sanitizeFileName($element->ref);
// 						$filedir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($element->id,2,0,0,null,'invoice_supplier').dol_sanitizeFileName($element->ref);
// 					}
//
// 					print $formfile->getDocumentsLink($element_doc, $filename, $filedir);
//
// 					// Show supplier ref
// 					if (! empty($element->ref_supplier)) print ' - '.$element->ref_supplier;
// 					// Show customer ref
// 					if (! empty($element->ref_customer)) print ' - '.$element->ref_customer;
            }

            print "</td>\n";


            // Status
            print '<td align="right">';
// 				if ($tablename == 'expensereport_det')
// 				{
// 					print $expensereport->getLibStatut(5);
// 				}
// 				else if ($element instanceof CommonInvoice)
// 				{
// 					//This applies for Facture and FactureFournisseur
// 					print $element->getLibStatut(5, $element->getSommePaiement());
// 				}
// 				else if ($element instanceof Task)
// 				{
// 					if ($element->progress != '')
// 					{
// 						print $element->progress.' %';
// 					}
// 				}
// 				else
// 				{
            print $object->getLibStatut(5);
// 				}
            print '</td>';

            print '</tr>';

// 				if ($qualifiedfortotal)
// 				{
// 					$total_ht = $total_ht + $total_ht_by_line;
// 					$total_ttc = $total_ttc + $total_ttc_by_line;
//
// 					$total_ht_by_third += $total_ht_by_line;
// 					$total_ttc_by_third += $total_ttc_by_line;
// 				}
//
// 				if (canApplySubtotalOn($tablename))
// 				{
// 					$breakline='<tr class="liste_total liste_sub_total">';
// 					$breakline.='<td colspan="2">';
// 					$breakline.='</td>';
// 					$breakline.='<td>';
// 					$breakline.='</td>';
// 					$breakline.='<td class="right">';
// 					$breakline.=$langs->trans('SubTotal').' : ';
// 					if (is_object($element->thirdparty)) $breakline.=$element->thirdparty->getNomUrl(0,'',48);
// 					$breakline.='</td>';
// 					$breakline.='<td align="right">'.price($total_ht_by_third).'</td>';
// 					$breakline.='<td align="right">'.price($total_ttc_by_third).'</td>';
// 					$breakline.='<td></td>';
// 					$breakline.='</tr>';
// 				}
            //var_dump($element->thirdparty->name.' - '.$saved_third_id.' - '.$element->thirdparty->id);
        }

        if ($breakline) print $breakline;

        // Total
        print '<tr class="liste_total"><td>'.$langs->trans("Number").': '.$i.'</td>';
        //if (empty($value['disableamount']) && ! in_array($tablename, array('taskin'))) print '<td align="right" width="100">'.$langs->trans("TotalHT").' : '.price($total_ht).'</td>';
        //elseif (empty($value['disableamount']) && in_array($tablename, array('taskin'))) print '<td align="right" width="100">'.$langs->trans("Total").' : '.price($total_ht).'</td>';
        print '<td></td>';
        print '</tr>';
    }
    else { // error
        print $elementarray;
    }
    print "</table>";
    print "<br>\n";
}

/**
 *  Show linked object block.
 *
 *  @param	CommonObject	$object		      Object we want to show links to
 *  @param  string          $morehtmlright    More html to show on right of title
 *  @return	int							      <0 if KO, >=0 if OK
 */
function showLinkedProductBlock($object, $morehtmlright = '')
{
    global $conf, $langs, $hookmanager;
    global $bc;

    $object->fetchObjectLinked();

    // Bypass the default method
    $hookmanager->initHooks(array('commonobject'));
    $parameters = array();
    $reshook    = $hookmanager->executeHooks('showLinkedObjectBlock', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook

    if (empty($reshook)) {
        $nbofdifferenttypes = count($object->linkedObjects);

        print '<br><!-- showLinkedObjectBlock -->';
        print load_fiche_titre($langs->trans('DefaultWarehouseForRelatedProducts'), $morehtmlright, '');


        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder allwidth">';

        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans("Type").'</td>';
        print '<td>'.$langs->trans("Ref").'</td>';
        print '<td align="right">'.$langs->trans("AmountHTShort").'</td>';
        print '</tr>';

        $nboftypesoutput = 0;
        foreach ($object->linkedObjects as $objecttype => $objects) {
            $tplpath    = $element    = $subelement = $objecttype;

            if ($objecttype != 'supplier_proposal' && preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs)) {
                $element    = $regs[1];
                $subelement = $regs[2];
                $tplpath    = $element.'/'.$subelement;
            }
            $tplname = 'linkedobjectblock';

            // To work with non standard path
            if ($objecttype == 'product') {
                $tplpath = 'product/'.$element;
                $tplname = 'linkedproductblockForRec';
                if (empty($conf->product->enabled)) continue; // Do not show if module disabled
            }
            else if ($objecttype == 'facture') {
                $tplpath = 'compta/'.$element;
                if (empty($conf->facture->enabled)) continue; // Do not show if module disabled
            }
            else if ($objecttype == 'facturerec') {
                $tplpath = 'compta/facture';
                $tplname = 'linkedobjectblockForRec';
                if (empty($conf->facture->enabled)) continue; // Do not show if module disabled
            }
            else if ($objecttype == 'propal') {
                $tplpath = 'comm/'.$element;
                if (empty($conf->propal->enabled)) continue; // Do not show if module disabled
            }
            else if ($objecttype == 'supplier_proposal') {
                if (empty($conf->supplier_proposal->enabled)) continue; // Do not show if module disabled
            }
            else if ($objecttype == 'shipping' || $objecttype == 'shipment') {
                $tplpath = 'expedition';
                if (empty($conf->expedition->enabled)) continue; // Do not show if module disabled
            }
            else if ($objecttype == 'delivery') {
                $tplpath = 'livraison';
                if (empty($conf->expedition->enabled)) continue; // Do not show if module disabled
            }
            else if ($objecttype == 'invoice_supplier') {
                $tplpath = 'fourn/facture';
            } else if ($objecttype == 'order_supplier') {
                $tplpath = 'fourn/commande';
            } else if ($objecttype == 'expensereport') {
                $tplpath = 'expensereport';
            } else if ($objecttype == 'subscription') {
                $tplpath = 'adherents';
            }

            global $linkedObjectBlock;
            $linkedObjectBlock = $objects;


            // Output template part (modules that overwrite templates must declare this into descriptor)
            $dirtpls = array_merge($conf->modules_parts['tpl'], array('/'.$tplpath.'/tpl'));
            foreach ($dirtpls as $reldir) {
                if ($nboftypesoutput == ($nbofdifferenttypes - 1)) {    // No more type to show after
                    global $noMoreLinkedObjectBlockAfter;
                    $noMoreLinkedObjectBlockAfter = 1;
                }

                $res = @include dol_buildpath($reldir.'/'.$tplname.'.tpl.php');
                if ($res) {
                    $nboftypesoutput++;
                    break;
                }
            }
        }

        if (!$nboftypesoutput) {
            print '<tr><td class="impair opacitymedium" colspan="7">'.$langs->trans("None").'</td></tr>';
        }

        print '</table>';
        print '</div>';

        return $nbofdifferenttypes;
    }
}

/**
 *  Show block with links to link to other objects.
 *
 *  @param	CommonObject	$object				Object we want to show links to
 *  @param	array			$restrictlinksto	Restrict links to some elements, for exemple array('order') or array('supplier_order'). null or array() if no restriction.
 *  @param	array			$excludelinksto		Do not show links of this type, for exemple array('order') or array('supplier_order'). null or array() if no exclusion.
 *  @return	string								<0 if KO, >0 if OK
 */
function showLinkToProductBlock($object, $restrictlinksto = array(), $excludelinksto = array())
{
    global $conf, $langs, $hookmanager;
    global $bc, $db;

    $linktoelem     = '';
    $linktoelemlist = '';

    if (!is_object($object->thirdparty)) $object->fetch_thirdparty();

    $possiblelinks = array(
        'product' => array('enabled' => $conf->product->enabled, 'perms' => 1, 'label' => 'LinkToProduct', 'sql' => "SELECT `rowid`,`ref`, `label`,`price` FROM `".MAIN_DB_PREFIX."product` WHERE `entity` = $conf->entity AND tobuy = 1 AND fk_product_type=0"),
    );

    global $action;

    // Can complete the possiblelink array
    $hookmanager->initHooks(array('commonobject'));
    $parameters = array();
    $reshook    = $hookmanager->executeHooks('showLinkToObjectBlock', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
    if (empty($reshook)) {
        if (is_array($hookmanager->resArray) && count($hookmanager->resArray)) {
            $possiblelinks = array_merge($possiblelinks, $hookmanager->resArray);
        }
    } else if ($reshook > 0) {
        if (is_array($hookmanager->resArray) && count($hookmanager->resArray)) {
            $possiblelinks = $hookmanager->resArray;
        }
    }

    foreach ($possiblelinks as $key => $possiblelink) {
        $num = 0;

        if (empty($possiblelink['enabled'])) continue;

        if (!empty($possiblelink['perms']) && (empty($restrictlinksto) || in_array($key, $restrictlinksto)) && (empty($excludelinksto) || !in_array($key, $excludelinksto))) {
            print '<div id="'.$key.'list"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? ' style="display:none"' : '').'>';
            $sql = $possiblelink['sql'];

            $resqllist = $db->query($sql);
            if ($resqllist) {
                $num = $db->num_rows($resqllist);
                $i   = 0;

                print '<br><form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formlinked'.$key.'">';
                print '<input type="hidden" name="id" value="'.$object->id.'">';
                print '<input type="hidden" name="action" value="addlink">';
                print '<input type="hidden" name="addlink" value="'.$key.'">';
                print '<table class="noborder">';
                print '<tr class="liste_titre">';
                print '<td class="nowrap"></td>';
                print '<td align="center">'.$langs->trans("Ref").'</td>';
                print '<td align="left">'.$langs->trans("RefCustomer").'</td>';
                print '<td align="right">'.$langs->trans("AmountHTShort").'</td>';
                print '</tr>';
                while ($i < $num) {
                    $objp = $db->fetch_object($resqlorderlist);

                    $var = !$var;
                    print '<tr '.$bc [$var].'>';
                    print '<td aling="left">';
                    print '<input type="radio" name="idtolinkto" value='.$objp->rowid.'>';
                    print '</td>';
                    print '<td align="center">'.$objp->ref.'</td>';
                    print '<td>'.$objp->label.'</td>';
                    print '<td align="right">'.price($objp->price).'</td>';
                    print '</tr>';
                    $i++;
                }
                print '</table>';
                print '<div class="center"><input type="submit" class="button valignmiddle" value="'.$langs->trans('ToLink').'">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></div>';

                print '</form>';
                $db->free($resqllist);
            } else {
                dol_print_error($db);
            }
            print '</div>';
            if ($num > 0) {

            }

            //$linktoelem.=($linktoelem?' &nbsp; ':'');
            if ($num > 0) $linktoelemlist .= '<li><a href="#linkto'.$key.'" class="linkto dropdowncloseonclick" rel="'.$key.'">'.$langs->trans($possiblelink['label']).' ('.$num.')</a></li>';
            //else $linktoelem.=$langs->trans($possiblelink['label']);
            else $linktoelemlist .= '<li><span class="linktodisabled">'.$langs->trans($possiblelink['label']).' (0)</span></li>';
        }
    }
    if ($linktoelemlist) {
        $linktoelem = '
		<dl class="dropdown" id="linktoobjectname">
		<dt><a href="#linktoobjectname">'.$langs->trans("LinkTo").'...</a></dt>
		<dd>
		<div class="multiselectlinkto">
		<ul class="ulselectedfields">'.$linktoelemlist.'
		</ul>
		</div>
		</dd>
		</dl>';
    } else {
        $linktoelem = '';
    }

    print '<!-- Add js to show linkto box -->
				<script type="text/javascript" language="javascript">
				jQuery(document).ready(function() {
					jQuery(".linkto").click(function() {
						console.log("We choose to show/hide link for rel="+jQuery(this).attr(\'rel\'));
					    jQuery("#"+jQuery(this).attr(\'rel\')+"list").toggle();
						jQuery(this).toggle();
					});
				});
				</script>
		';

    return $linktoelem;
}

function hackDefaultWarehouse()
{
    global $db;
    if (strpos($_SERVER['REQUEST_URI'], '/product/stock/product.php') !== false) {
        $prod_id = GETPOST('id', 'int');
        if ($prod_id && GETPOST('action', 'alpha') == 'transfert') {
            $sql    = "SELECT `fk_target` FROM `".MAIN_DB_PREFIX."element_element` WHERE `fk_source` = $prod_id AND `sourcetype` = 'product' AND `targettype` = 'stock' ";
            $result = $db->query($sql);
            if ($result) {
                $obj = $db->fetch_object($result);
                if (!isset($_GET['id_entrepot_destination'])) {
                    $_GET['id_entrepot_destination'] = (int) $obj->fk_target;
                }
                $db->free($result);
            }
        }
    }

    if (strpos($_SERVER['REQUEST_URI'], '/fourn/commande/dispatch.php') !== false) {
        $shipment = GETPOST('id', 'int');
        if ($shipment) {
            $sql = "SELECT  l.fk_product";
            $sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as l";
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON l.fk_product=p.rowid";
            $sql .= " WHERE l.fk_commande = ".$shipment;
            if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) $sql .= " AND l.product_type = 0";
            $sql .= " GROUP BY p.ref, p.label, p.tobatch, l.rowid, l.fk_product, l.subprice, l.remise_percent"; // Calculation of amount dispatched is done per fk_product so we must group by fk_product
            $sql .= " ORDER BY p.ref, p.label";

            $resql = $db->query($sql);
            if ($resql) {
                $i   = 0;
                while ($res = $db->fetch_object($resql)) {
                    $id_prod = $res->fk_product;

                    $sql    = "SELECT `fk_target` FROM `".MAIN_DB_PREFIX."element_element` WHERE `fk_source` = $id_prod AND `sourcetype` = 'product' AND `targettype` = 'stock' ";
                    $$result2 = $db->query($sql);
                    if ($$result2) {
                        $obj = $db->fetch_object($$result2);
                        //var_dump($sql);
                        $obj->fk_target;
                        if (!isset($_GET['id_entrepot_0_'.$i])) {
                            $_GET['entrepot_0_'.$i] = 1;
                        }
                        $db->free($$result2);
                    }
                    $i++;
                }
            }
        }
    }
}
