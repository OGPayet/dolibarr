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
		global $conf, $langs, $db;
		global $user;
		if ($action == 'create_return' ) {
			dol_include_once('/retourproduits/class/retourproduits.class.php');

			// ici faire la création si tout est OK
			$commandeid = GETPOST('id', 'int') ;
			$commande = new Commande($db);
			$rpds = new RetourProduits($db);
			$commande->fetch($commandeid);
			$rpds->socid = $commande->socid ;
			$rpds->origin = 'commande';
			$rpds->origin_id = $commandeid ;
			//$retourId = $rpds->create($user);

			// puis crcéation ligne-détail sur chancun des numéro de série

			foreach ($_GET['line'] as $key => $value) {
				$line = new RetourProduitsLigne($this->db);
				$line->fk_product = $_GET['pd'][$key] ;
				if ($_GET['serie'][$key] != '') {
					$line->fk_equipement = $_GET['serie'][$key] ;
				} else {
					$line->fk_equipement = -1 ;
				}
				$line->fk_entrepot_dest = $_GET['wh'][$key] ;
				$line->qty = $_GET['qty'][$key] ;
				$line->fk_origin_line = $_GET['line'][$key] ;
				$rpds->lines[$key] = $line ;
				//$rpds->create_line($_GET['pd'][$key],$value,$_GET['wh'][$key],$_GET['qty'][$key],$_GET['line'][$key]);
			}
			//die() ;
			$retourId = $rpds->create($user);
			header('Location: '.dol_buildpath('/retourproduits/card.php?id=', 1).$retourId);
			exit;
		}
	}


	function formConfirm($parameters, $object, $action)	{
		global $conf, $langs, $db;
		global $user;
		global $formconfirm ;

		$langs->load("retourproduits@retourproduits");
		require_once(dol_buildpath('/retourproduits/form/html.form.class.php');

		$formreturnproducts = new FormRetourProduits($db);
		$form = new Form($db);

		if ($action == 'returnproducts') {
			// liste des produits sur cette commande  / Numéro de série / Quantité / Entrepots
			$formquestion = $formreturnproducts->select_return_products($object->id) ;
			$formconfirm = $formreturnproducts->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CreateReturnProducts'), $langs->trans('SelectProductsToReturn'),'create_return', $formquestion, 0, 2, 400,600);
		}
	}

	function showLinkedObjectBlock($parameters,$object,$action){
		if ($object->element == 'commande') {
			global $conf, $langs, $db;
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