<?php
/* Copyright (C) 2016-2017	Charlie Benke	<charlie@patas-monkey.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file	   /portofolio/class/actions_portofolio.class.php
 * 	\ingroup	portofolio
 * 	\brief	  Fichier de la classe des actions/hooks de portofolio
 */

class ActionsRetourProduits // extends CommonObject
{
	/** Overloading the formContactTpl function : replacing the parent's function with the one below
	 *  @param	  parameters  meta datas of the hook (context, etc...)
	 *  @param	  object			 the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 *  @param	  action			 current action (if set). Generally create or edit or null
	 *  @return	   void
	 */

	function addMoreActionsButtons($parameters, &$object, &$action)
	{
		global $conf, $langs, $db;

		$langs->load("retourproduits@retourproduits");

		if ($object->element == 'commande') {
			if ($object->statut > Commande::STATUS_DRAFT) {
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=returnproducts">' . $langs->trans('returnProducts') . '</a></div>';
			}
		}
	}

	function doActions ($parameters, &$object, &$action) {
		global $langs, $db, $user;

		if ($action == 'create_return' ) {
            $id = GETPOST('id', 'int');
            if ($id > 0) {
		    $ret = $object->fetch($id);
		    $object->fetch_thirdparty();
            }

			if ($object->id > 0) {
                $langs->load("retourproduits@retourproduits");

                dol_include_once('/retourproduits/lib/retourproduits.lib.php');
                $lines = retourproduits_get_product_list($db, $object->id);

                // Get selected lines
                $selectedLines = array();
                foreach ($lines as $line_id => $line) {
                    if ($line_id == GETPOST('s-' . $line_id, 'int')) {
                        $selectedLines[$line_id] = $line_id;
                    }
                }

                if (!empty($selectedLines)) {
                    $langs->load('errors');
                    $langs->load("equipement@equipement");
                    $error = 0;
                    $db->begin();

                    // Create RetourProduits object
                    dol_include_once('/retourproduits/class/retourproduits.class.php');
                    $rpds = new RetourProduits($db);

                    // Set variables
                    $rpds->socid = $object->socid;
                    $rpds->origin = 'commande';
                    $rpds->origin_id = $object->id;

                    // Add lines
                    $idx = 0;
                    foreach ($selectedLines as $line_id) {

                        $fk_product = GETPOST('p-' . $line_id, 'int');
                        $qty = GETPOST('q-' . $line_id, 'int');
                        $fk_entrepot_dest = GETPOST('w-' . $line_id, 'int');

                        // Test variables
                        if ($qty <= 0) {
                            setEventMessages($lines[$line_id]['product'].': '.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Qty")), null, 'errors');
                            $error++;
                        }
                        if ($qty <= 0 || $qty > $lines[$line_id]['qty_sent']) {
                            setEventMessages($lines[$line_id]['product'].': '.$langs->trans("ErrorBadValueForParameter", $qty, $langs->transnoentitiesnoconv("Qty")), null, 'errors');
                            $error++;
                        }
                        if ($fk_entrepot_dest <= 0) {
                            setEventMessages($lines[$line_id]['product'].': '.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
                            $error++;
                        }

                        $equipments = explode(',', GETPOST('e-' . $line_id, 'alpha'));
                        if (!empty($equipments)) {
                            if (count($equipments) > min($qty, $lines[$line_id]['qty_sent'])) {
                                setEventMessages($lines[$line_id]['product'].': '.$langs->trans("RetourProduitsErrorTooManyEquipmentSelected"), null, 'errors');
                                $error++;
                            }

                            foreach ($equipments as $equipment_id) {
                                $line = new RetourProduitsLigne($db);
                                $line->fk_product = $fk_product;
                                $line->qty = 1;
                                $line->fk_entrepot_dest = $fk_entrepot_dest;
                                $line->fk_origin_line = $line_id;
                                $line->fk_equipement = $equipment_id;

                                $qty--;
                                $rpds->lines[] = $line;
                            }
                        }

                        if ($qty > 0) {
                            $line = new RetourProduitsLigne($db);
                            $line->fk_product = $fk_product;
                            $line->qty = $qty;
                            $line->fk_entrepot_dest = $fk_entrepot_dest;
                            $line->fk_origin_line = $line_id;
                            $line->fk_equipement = -1;
                            $rpds->lines[] = $line;
                        }
                    }

                    if (!$error) {
                        $retourId = $rpds->create($user);
                        if ($retourId < 0) {
                            $error++;
                            setEventMessages($rpds->error, $rpds->errors, 'errors');
                        }
                    }

                    if (!$error) {
                        $db->commit();
                        header('Location: ' . dol_buildpath('/retourproduits/card.php', 1) . '?id=' . $retourId);
                        exit;
                    } else {
                        $db->rollback();
                        $action = "returnproducts";
                    }
                } else {
                    setEventMessage($langs->trans('RetourProduitsErrorNoProductSelected'), 'errors');
                    $action = "returnproducts";
                }
            }
		}
	}

	function formConfirm($parameters, $object, $action)	{
		global $conf, $langs, $db, $user;
		global $form ;

        if ($object->element == 'commande' && $action == 'returnproducts') {
            $langs->load("retourproduits@retourproduits");
            $langs->load("equipement@equipement");

            dol_include_once('/retourproduits/lib/retourproduits.lib.php');
            $lines = retourproduits_get_product_list($db, $object->id);

            if (empty($lines)) {
                setEventMessage($langs->trans('RetourProduitsErrorNoProductSent'), 'errors');
                return 0;
            }

            require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
            $formproduct = new FormProduct($db);

            $formquestion = array();
            foreach ($lines as $line_id => $line) {
                $product_id = GETPOST('p-' . $line_id, 'int');
                $selected = $product_id > 0 && GETPOST('s-' . $line_id, 'int') > 0 ? ' checked' : '';
                $qty = $product_id > 0 ? GETPOST('q-' . $line_id, 'int') : $line['qty_sent'];
                $warehouse = $product_id > 0 ? GETPOST('w-' . $line_id, 'int') : '';
                $equipments = $product_id > 0 ? GETPOST('e-' . $line_id, 'int') : array();

                $formquestion[] = array(
                    'type' => 'other',
                    'label' => '<input type="checkbox" id="s-' . $line_id . '" name="s-' . $line_id . '" value="' . $line_id . '"'.$selected.'> ' . $line['product'],
                    'name' => array('s-' . $line_id, 'p-' . $line_id, 'q-' . $line_id, 'w-' . $line_id, 'e-' . $line_id),
                    'value' => '<input type="hidden" id="p-' . $line_id . '" name="p-' . $line_id . '" value="' . $line['produit_id'] . '">' .
                        $langs->trans('Qty').': <input type="number" id="q-' . $line_id . '" name="q-' . $line_id . '" value="' . $qty . '" min="1" max="' . $line['qty_sent'] . '"> ' .
                        ' '.$langs->trans('Warehouse').': '.$formproduct->selectWarehouses($warehouse, 'w-' . $line_id, 'warehouseopen,warehouseinternal', 1) . ' ' .
                        '<br>'.$langs->trans('Equipements').': '.$form->multiselectarray('e-' . $line_id, $line['equipments'], $equipments, 0, 0, '', 0, 0, 'style="min-width:300px"')
                );
            }

            // Create the confirm form
            $this->resprints = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CreateReturnProducts'), $langs->trans('SelectProductsToReturn'), 'create_return', $formquestion, 'yes', 1, 400, 700);

            return 1;
        }
	}

	function showLinkedObjectBlock($parameters,$object,$action){
		if ($object->element == 'commande') {
			global $conf, $langs, $db;
            $morehtmlright = '';
		$nbofdifferenttypes = count($object->linkedObjects);

		print '<br><!-- showLinkedObjectBlock -->';
	        print load_fiche_titre($langs->trans('RelatedObjects'), $morehtmlright, '');


			print '<div class="div-table-responsive-no-min">';
	        print '<table class="noborder allwidth">';

	        print '<tr class="liste_titre">';
	        print '<td>'.$langs->trans("Type").'</td>';
	        print '<td>'.$langs->trans("Ref").'</td>';
	        print '<td align="center"></td>';
	        print '<td align="center">'.$langs->trans("Date").'</td>';
	        print '<td align="right">'.$langs->trans("AmountHTShort").'</td>';
	        print '<td align="right">'.$langs->trans("Status").'</td>';
	        print '<td></td>';
	        print '</tr>';

	        $nboftypesoutput=0;

		foreach($object->linkedObjects as $objecttype => $objects)
		{
			$tplpath = $element = $subelement = $objecttype;

			if ($objecttype != 'supplier_proposal' && preg_match('/^([^_]+)_([^_]+)/i',$objecttype,$regs))
			{
				$element = $regs[1];
				$subelement = $regs[2];
				$tplpath = $element.'/'.$subelement;
			}
			$tplname='linkedobjectblock';

			// To work with non standard path
			if ($objecttype == 'facture')          {
				$tplpath = 'compta/'.$element;
				if (empty($conf->facture->enabled)) continue;	// Do not show if module disabled
			}
		    else if ($objecttype == 'facturerec')          {
				$tplpath = 'compta/facture';
				$tplname = 'linkedobjectblockForRec';
				if (empty($conf->facture->enabled)) continue;	// Do not show if module disabled
			}
			else if ($objecttype == 'propal')           {
				$tplpath = 'comm/'.$element;
				if (empty($conf->propal->enabled)) continue;	// Do not show if module disabled
			}
			else if ($objecttype == 'supplier_proposal')           {
				if (empty($conf->supplier_proposal->enabled)) continue;	// Do not show if module disabled
			}
			else if ($objecttype == 'shipping' || $objecttype == 'shipment') {
				$tplpath = 'expedition';
				if (empty($conf->expedition->enabled)) continue;	// Do not show if module disabled
			}
			else if ($objecttype == 'delivery')         {
				$tplpath = 'livraison';
				if (empty($conf->expedition->enabled)) continue;	// Do not show if module disabled
			}
			else if ($objecttype == 'invoice_supplier') {
				$tplpath = 'fourn/facture';
			}
			else if ($objecttype == 'order_supplier')   {
				$tplpath = 'fourn/commande';
			}
			else if ($objecttype == 'expensereport')   {
				$tplpath = 'expensereport';
			}
			else if ($objecttype == 'subscription')   {
			    $tplpath = 'adherents';
			} else if ($objecttype == 'retourproduits') {
				$tplpath = 'retourproduits';
			}

	            global $linkedObjectBlock;
			$linkedObjectBlock = $objects;


			// Output template part (modules that overwrite templates must declare this into descriptor)
			$dirtpls=array_merge($conf->modules_parts['tpl'],array('/'.$tplpath.'/tpl'));
			foreach($dirtpls as $reldir)
			{
			    if ($nboftypesoutput == ($nbofdifferenttypes - 1))    // No more type to show after
			    {
			        global $noMoreLinkedObjectBlockAfter;
			        $noMoreLinkedObjectBlockAfter=1;
			    }
	                $res=@include dol_buildpath($reldir.'/'.$tplname.'.tpl.php');
				if ($res)
				{
				    $nboftypesoutput++;
				    break;
				}
			}
		}

		if (! $nboftypesoutput)
		{
		    print '<tr><td class="impair opacitymedium" colspan="7">'.$langs->trans("None").'</td></tr>';
		}

		print '</table>';
			print '</div>';
			return 1 ;
		}
	}
}