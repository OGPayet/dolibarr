<?php
/* Copyright (C) 2016-2017	Charlie BENKE		<charlie@patas-monkey.com>
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
 *	\file	   htdocs/xxxxx/class/commonobjectpatas.class.php
 *	\ingroup	générique
 *	\brief	  File of class to manage tabs
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage members type
 */
class CommonObjectPatas extends CommonObject
{
	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	function element_setting()
	{
//		global $head;
//		global $title;
//		global $langs;

		// selon l'onglet on affiche les données de l'onglet
		switch($this->element) {
			case 'dictionary' :
				dol_include_once('/customtabs/class/dictionary.class.php');
				dol_include_once('/customtabs/core/lib/customtabs.lib.php');
				$object = new Dictionary($this->db);
				break;

			case 'thirdparty' :
				require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
				$object = new Societe($this->db);
				break;

			case 'stock' :
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';
				$object = new Entrepot($this->db);
				break;

			case 'member' :
				require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
				$object = new Adherent($this->db);
				break;

			case 'contract' :
				require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
				$object = new Contrat($this->db);
				break;

			case 'intervention' :
				require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';
				$object = new Fichinter($this->db);
				break;

			case 'delivery' :
				require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
				$object = new Expedition($this->db);
				break;

			case 'user' :
				require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
				$object = new User($this->db);
				break;

			case 'commande' :
				require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
				$object = new Commande($this->db);
				break;

			case 'invoice' :
				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
				$object = new Facture($this->db);
				break;

			case 'propal' :
				require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';
				$object = new Propal($this->db);
				break;

			case 'supplier_invoice' :
				require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
				$object = new FactureFournisseur($this->db);
				break;

			case 'supplier_order' :
				require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
				$object = new CommandeFournisseur($this->db);
				break;

			case 'project' :
				require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
				require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				$object = new Project($this->db);
				break;

			case 'bank' :
				require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
				require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
				$object = new Account($this->db);
				break;

			case 'payment_salaries' :
				require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
				require_once DOL_DOCUMENT_ROOT.'/compta/salaries/class/paymentsalary.class.php';
				$object = new PaymentSalary($this->db);
				break;

			case 'tax' :
				require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
				require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
				$object = new ChargeSociales($this->db);
				break;

			case 'payment_vat' :
				require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
				require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
				$object = new Tva($this->db);
				break;

			// AGEFODD module definition
			case 'agefodd_session' :
				dol_include_once('/agefodd/lib/agefodd.lib.php');
				dol_include_once('/agefodd/class/agsession.class.php');
				$object = new Agsession($this->db);
				break;

			// case 'usergroup' :	standard naming
			// case 'product' :		standard naming
			// case 'project' :		standard naming
			// case 'fichinter' :	standard naming
			// case 'contact' :		standard naming

			// specific module with standard naming
			default :
				if ($this->element) {
					if (file_exists(DOL_DOCUMENT_ROOT.'/'.$this->element.'/class/'.$this->element.'.class.php'))
						require_once DOL_DOCUMENT_ROOT.'/'.$this->element.'/class/'.$this->element.'.class.php';
					else
						//require_once DOL_DOCUMENT_ROOT.'/custom/'.$this->element.'/class/'.$this->element.'.class.php';

					// gère le cas des modules internes posé dans le /core ou pas
					if (file_exists(DOL_DOCUMENT_ROOT.'/'.$this->element.'/lib/'.$this->element.'.lib.php'))
						require_once DOL_DOCUMENT_ROOT.'/'.$this->element.'/lib/'.$this->element.'.lib.php';
					elseif (file_exists(DOL_DOCUMENT_ROOT.'/core/lib/'.$this->element.'.lib.php'))
						require_once DOL_DOCUMENT_ROOT.'/core/lib/'.$this->element.'.lib.php';
					elseif (file_exists(DOL_DOCUMENT_ROOT.'/'.$this->element.'/core/lib/'.$this->element.'.lib.php'))
						require_once DOL_DOCUMENT_ROOT.'/'.$this->element.'/core/lib/'.$this->element.'.lib.php';
					elseif (file_exists(DOL_DOCUMENT_ROOT.'/custom/'.$this->element.'/core/lib/'.$this->element.'.lib.php'))
						require_once DOL_DOCUMENT_ROOT.'/custom/'.$this->element.'/core/lib/'.$this->element.'.lib.php';
					elseif (file_exists(DOL_DOCUMENT_ROOT.'/custom/'.$this->element.'/lib/'.$this->element.'.lib.php'))
						require_once DOL_DOCUMENT_ROOT.'/custom/'.$this->element.'/lib/'.$this->element.'.lib.php';

					$classname = ucfirst($this->element);
					$object = new $classname($this->db);

				} else {
					print "pb sur element inexistant pour ".get_class($this);
					exit;
				}
				break;
		}
		return $object;
	}

	function tabs_head_element($tabsid, $headername="patastabs", $help_url='', $target='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='', $morequerystring='')
	{
		global $langs;
		global $object;
		global $form;
		global $user;
		global $conf;

		llxHeader(
						'', $langs->trans($headername), $help_url, $target,
						$disablejs, $disablehead, $arrayofjs, $arrayofcss, $morequerystring
		);

		// Add hook object if not present
		if (! is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager=new HookManager($this->db);
		}

		include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

		$hookmanager->initHooks(array($headername.'card', 'globalcard'));

		if (DOL_VERSION >= "5.0.0") {
			// selon l'onglet on affiche les données de l'onglet
			switch($this->element) {
				case 'thirdparty' :
					$head = societe_prepare_head($object);

					dol_fiche_head($head, $headername."_".$tabsid, $langs->trans("ThirdParty"), 0, 'company');
					$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php">'.$langs->trans("BackToList").'</a>';
					dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

					print '<div class="fichecenter">';
					print '<div class="underbanner clearboth"></div>';
					print '<table class="border centpercent">';
					break;

				case 'contact' :
					$head = contact_prepare_head($object);
					if (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT))
						$title = $langs->trans("Contacts");
					else
						$title = $langs->trans("ContactsAddresses");
					dol_fiche_head($head, $headername."_".$tabsid, $title, 0, 'contact');

					$linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php">'.$langs->trans("BackToList").'</a>';
					dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', '', '&tabsid='.$tabsid);

					print '<div class="fichecenter">';
					print '<div class="underbanner clearboth"></div>';
					print '<table class="border centpercent">';
					break;

				case 'product':
					$showbarcode=empty($conf->barcode->enabled)?0:1;
					if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS)
						&& empty($user->rights->barcode->lire_advance))
						$showbarcode=0;

					$head=product_prepare_head($object);
					$titre=$langs->trans("CardProduct".$object->type);
					$picto=($object->type== Product::TYPE_SERVICE?'service':'product');
					dol_fiche_head($head, $headername."_".$tabsid, $titre, 0, $picto);

					$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?type='.$object->type.'">';
					$linkback.= $langs->trans("BackToList").'</a>';
					$object->next_prev_filter=" fk_product_type = ".$object->type;
					dol_banner_tab($object, 'ref', $linkback, ($user->societe_id?0:1), 'ref', '', '', '&tabsid='.$tabsid);

					print '<div class="fichecenter">';
					print '<div class="underbanner clearboth"></div>';
					print '<table class="border centpercent" >';
					break;

				case 'stock' :
					$head = stock_prepare_head($object);

					dol_fiche_head($head, $headername."_".$tabsid, $langs->trans("Warehouse"), 0, 'stock');

					$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/list.php">'.$langs->trans("BackToList").'</a>';

					$morehtmlref='<div class="refidno">';
					$morehtmlref.=$langs->trans("LocationSummary").' : '.$object->lieu;
					$morehtmlref.='</div>';

					dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'libelle', $morehtmlref, '&tabsid='.$tabsid);

					print '<div class="fichecenter">';
					print '<div class="underbanner clearboth"></div>';
					print '<table class="border centpercent" >';
					break;

				case 'member' :
					$head = member_prepare_head($object);

					dol_fiche_head($head, $headername."_".$tabsid, $langs->trans("Member"), 0, 'user');

					//print "<form method='post' action='".$_SERVER['PHP_SELF']."'>";
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

					$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php">'.$langs->trans("BackToList").'</a>';

					dol_banner_tab($object, 'rowid', $linkback);

					print '<div class="fichecenter">';
					print '<div class="underbanner clearboth"></div>';
					print '<table class="border centpercent">';
					break;

				case 'project' :
					$head = project_prepare_head($object);
					dol_fiche_head(
									$head, $headername."_".$tabsid,
									$langs->trans("Project"), 0,
									($object->public?'projectpub':'project')
					);

					$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php">'.$langs->trans("BackToList").'</a>';

					$morehtmlref='<div class="refidno">';
					$morehtmlref.=$object->title;

					if ($object->thirdparty->id > 0)
						$morehtmlref.='<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1, 'project');
					$morehtmlref.='</div>';

					// Define a complementary filter for search of next/prev ref.
					if (! $user->rights->projet->all->lire) {
						$objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
						// on filtre sur les projet ou on bloque l'accès
						if (count($objectsListId) > 1)
							$object->next_prev_filter= " rowid in (".join(',', array_keys($objectsListId)).")";
						else
							$object->next_prev_filter= " rowid = 0";
					}
					dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '&tabsid='.$tabsid);

					print '<div class="fichecenter">';
					print '<div class="fichehalfleft">';
					print '<div class="underbanner clearboth"></div>';


					print '<table class="border" width="100%">';

					// Visibility
					print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
					if ($object->public) print $langs->trans('SharedProject');
					else print $langs->trans('PrivateProject');
					print '</td></tr>';

					if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
						// Opportunity status
						print '<tr><td>'.$langs->trans("OpportunityStatus").'</td><td>';
						$code = dol_getIdFromCode($this->db, $object->opp_status, 'c_lead_status', 'rowid', 'code');
						if ($code) print $langs->trans("OppStatus".$code);
						print '</td></tr>';

						// Opportunity percent
						print '<tr><td>'.$langs->trans("OpportunityProbability").'</td><td>';
						if (strcmp($object->opp_percent, ''))
							print price($object->opp_percent, '', $langs, 1, 0).' %';
						print '</td></tr>';

						// Opportunity Amount
						print '<tr><td>'.$langs->trans("OpportunityAmount").'</td><td>';
						if (strcmp($object->opp_amount, ''))
							print price($object->opp_amount, '', $langs, 1, 0, 0, $conf->currency);
						print '</td></tr>';
					}

					// Date start - end
					print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
					print dol_print_date($object->date_start, 'day');
					$end=dol_print_date($object->date_end, 'day');
					if ($end)
						print ' - '.$end;
					print '</td></tr>';

					// Budget
					print '<tr><td>'.$langs->trans("Budget").'</td><td>';
					if (strcmp($object->budget_amount, ''))
						print price($object->budget_amount, '', $langs, 1, 0, 0, $conf->currency);
					print '</td></tr>';

					// Other attributes
					$cols = 2;
					include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

					print '</table>';

					print '</div>';
					print '<div class="fichehalfright">';
					print '<div class="ficheaddleft">';
					print '<div class="underbanner clearboth"></div>';

					print '<table class="border" width="100%">';

					// Description
					print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
					print nl2br($object->description);
					print '</td></tr>';

					// Categories
					if ($conf->categorie->enabled) {
						print '<tr><td valign="middle">'.$langs->trans("Categories").'</td><td>';
						print $form->showCategories($object->id, 'project', 1);
						print "</td></tr>";
					}

					print '</table>';

					print '</div>';
					print '</div>';
					print '</div>';

					print '<div class="clearboth"></div>';
					break;

				case 'contract' :

					$object->fetch_thirdparty();

					$head = contract_prepare_head($object);
					dol_fiche_head($head, $headername."_".$tabsid, $langs->trans("Contract"), 0, 'contract');

					// Contract card

					$linkback = '<a href="'.DOL_URL_ROOT.'/contrat/list.php'.(! empty($socid)?'?socid='.$socid:'').'">';
					$linkback.= $langs->trans("BackToList").'</a>';

					$morehtmlref='';
					$morehtmlref.=$object->ref;

					$morehtmlref.='<div class="refidno">';
					// Ref customer
					$morehtmlref.=$form->editfieldkey(
									"RefCustomer", 'ref_customer', $object->ref_customer, $object,
									0, 'string', '', 0, 1
					);
					$morehtmlref.=$form->editfieldval(
									"RefCustomer", 'ref_customer', $object->ref_customer, $object,
									0, 'string', '', null, null, '', 1
					);
					// Ref supplier
					$morehtmlref.='<br>';
					$morehtmlref.=$form->editfieldkey(
									"RefSupplier", 'ref_supplier', $object->ref_supplier, $object,
									0, 'string', '', 0, 1
					);
					$morehtmlref.=$form->editfieldval(
									"RefSupplier", 'ref_supplier', $object->ref_supplier, $object,
									0, 'string', '', null, null, '', 1
					);
					// Thirdparty
					$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
					// Project
					if (! empty($conf->projet->enabled)) {
						$langs->load("projects");
						$morehtmlref.='<br>'.$langs->trans('Project') . ' ';

						if (! empty($object->fk_project)) {
							// class project needed?
							$proj = new Project($this->db);
							$proj->fetch($object->fk_project);
							$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'"';
							$morehtmlref.=' title="' . $langs->trans('ShowProject') . '">';
							$morehtmlref.=$proj->ref;
							$morehtmlref.='</a>';
						}
					}
					$morehtmlref.='</div>';
					dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'none', $morehtmlref, '&tabsid='.$tabsid);
					print '<div class="fichecenter">';
					print '<div class="underbanner clearboth"></div>';
					print '<table class="border" width="100%">';
					break;

				case 'user' :
					$head = user_prepare_head($object);
					$title = $langs->trans("User");
					dol_fiche_head($head, $headername."_".$tabsid, $title, 0, 'user');
					$linkback = '<a href="'.DOL_URL_ROOT.'/user/index.php">'.$langs->trans("BackToList").'</a>';

					dol_banner_tab(
									$object, 'id', $linkback, $user->rights->user->user->lire || $user->admin,
									'rowid', 'ref', '', '&tabsid='.$tabsid
					);

					print '<div class="fichecenter">';
					print '<div class="underbanner clearboth"></div>';
					print '<table class="border" width="100%">';
					break;

				case 'propal' :
					$object->fetch_thirdparty();
					$head = propal_prepare_head($object);
					dol_fiche_head($head, $headername."_".$tabsid, $langs->trans('Proposal'), 0, 'propal');

					/// USEFULL????
					$object->info($object->id);

					// Proposal card
					$linkback = '<a href="'.DOL_URL_ROOT.'/comm/propal/list.php';
					$linkback.= (! empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

					$morehtmlref='<div class="refidno">';
					// Ref customer
					$morehtmlref.=$form->editfieldkey(
									"RefCustomer", 'ref_client', $object->ref_client, $object,
									0, 'string', '', 0, 1
					);
					$morehtmlref.=$form->editfieldval(
									"RefCustomer", 'ref_client', $object->ref_client, $object,
									0, 'string', '', null, null, '', 1
					);
					// Thirdparty
					$morehtmlref.='<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
					// Project
					if (! empty($conf->projet->enabled)) {
						$langs->load("projects");
						$morehtmlref.='<br>'.$langs->trans('Project').' ';
						if (! empty($object->fk_project)) {
							$proj = new Project($this->db);
							$proj->fetch($object->fk_project);
							$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'"';
							$morehtmlref.=' title="'.$langs->trans('ShowProject').'">';
							$morehtmlref.=$proj->ref;
							$morehtmlref.='</a>';
						}
					}
					$morehtmlref.='</div>';

					dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '&tabsid='.$tabsid);

					print '<div class="fichecenter">';
					print '<div class="underbanner clearboth"></div>';
					print '<table class="border" width="100%">';
					break;

				case 'commande' :
					$object->fetch_thirdparty();
					$object->info($object->id);

					$head = commande_prepare_head($object);
					dol_fiche_head($head, $headername."_".$tabsid, $langs->trans("CustomerOrder"), 0, 'order');

					// Order card
					$linkback = '<a href="'.DOL_URL_ROOT.'/commande/list.php';
					$linkback.= (! empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
					$morehtmlref='<div class="refidno">';
					// Ref customer
					$morehtmlref.=$form->editfieldkey(
									"RefCustomer", 'ref_client', $object->ref_client, $object,
									0, 'string', '', 0, 1
					);
					$morehtmlref.=$form->editfieldval(
									"RefCustomer", 'ref_client', $object->ref_client, $object,
									0, 'string', '', null, null, '', 1
					);
					// Thirdparty
					$morehtmlref.='<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);

					// Project
					if (! empty($conf->projet->enabled)) {
						$langs->load("projects");
						$morehtmlref.='<br>'.$langs->trans('Project').' ';
						if (! empty($object->fk_project)) {
							$proj = new Project($this->db);
							$proj->fetch($object->fk_project);
							$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=';
							$morehtmlref.=$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
							$morehtmlref.=$proj->ref;
							$morehtmlref.='</a>';
						}
					}
					$morehtmlref.='</div>';
					dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '&tabsid='.$tabsid);

					print '<div class="fichecenter">';
					print '<div class="underbanner clearboth"></div>';
					print '<table class="border" width="100%">';
					break;

				case 'bank' :
					// Onglets
					$head=bank_prepare_head($object);
					dol_fiche_head($head, $headername."_".$tabsid, $langs->trans("FinancialAccount"), 0, 'account');

					$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/index.php">'.$langs->trans("BackToList").'</a>';

					$morehtmlref='';
					dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '&tabsid='.$tabsid);

					print '<div class="fichecenter">';
					print '<div class="underbanner clearboth"></div>';

					print '<table class="border" width="100%">';
					break;

				case 'shipping' :
					$head=shipping_prepare_head($object);
					dol_fiche_head($head, $headername."_".$tabsid, $langs->trans("Shipment"), 0, 'sending');

					// Shipment card
					$linkback = '<a href="'.DOL_URL_ROOT.'/expedition/list.php">'.$langs->trans("BackToList").'</a>';

					$morehtmlref='<div class="refidno">';
					// Ref customer shipment
					$morehtmlref.=$form->editfieldkey(
									"RefCustomer", '', $object->ref_customer, $object,
									$user->rights->expedition->creer, 'string', '', 0, 1
					);
					$morehtmlref.=$form->editfieldval(
									"RefCustomer", '', $object->ref_customer, $object,
									$user->rights->expedition->creer, 'string', '', null, null, '', 1
					);
					// Thirdparty
					$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
					// Project
					if (! empty($conf->projet->enabled)) {
						$langs->load("projects");
						$morehtmlref .= '<br>' . $langs->trans('Project') . ' ';
						// We don't have project on shipment, so we will use the project or source object instead
						// TODO Add project on shipment
						$morehtmlref .= ' : ';
						if (! empty($objectsrc->fk_project)) {
							$proj = new Project($this->db);
							$proj->fetch($objectsrc->fk_project);
							$morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$objectsrc->fk_project.'"';
							$morehtmlref .= ' title="'.$langs->trans('ShowProject').'">';
							$morehtmlref .= $proj->ref;
							$morehtmlref .= '</a>';
						} else {
							$morehtmlref .= '';
						}
					}
					$morehtmlref.='</div>';

					dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '&tabsid='.$tabsid);
					print '<div class="fichecenter">';
					print '<div class="underbanner clearboth"></div>';

					print '<table class="border" width="100%">';
					break;

				case 'supplier_order' :
					$langs->load("suppliers");

					$object->fetch_thirdparty();
					$head = ordersupplier_prepare_head($object);

					dol_fiche_head($head, $headername."_".$tabsid, $langs->trans("SupplierOrder"), 0, 'order');

					// Supplier order card
					$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">';
					$linkback.= $langs->trans("BackToList").'</a>';
					$morehtmlref='<div class="refidno">';
					// Ref supplier
					$morehtmlref.=$form->editfieldkey(
									"RefSupplier", 'ref_supplier', $object->ref_supplier, $object,
									0, 'string', '', 0, 1
					);
					$morehtmlref.=$form->editfieldval(
									"RefSupplier", 'ref_supplier', $object->ref_supplier, $object,
									0, 'string', '', null, null, '', 1
					);
					// Thirdparty
					$morehtmlref.='<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
					// Project
					if (! empty($conf->projet->enabled)) {
						$langs->load("projects");
						$morehtmlref.='<br>'.$langs->trans('Project').' ';
						if (! empty($object->fk_project)) {
							$proj = new Project($this->db);
							$proj->fetch($object->fk_project);
							$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'"';
							$morehtmlref.=' title="' . $langs->trans('ShowProject') . '">'.$proj->ref.'</a>';
						}
					}
					$morehtmlref.='</div>';

					dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '&tabsid='.$tabsid);

					print '<div class="fichecenter">';
					print '<div class="underbanner clearboth"></div>';
					print '<table class="border" width="100%">';
					break;


				case 'supplier_invoice' :
					$langs->load("suppliers");

					$object->fetch_thirdparty();
					$head = facturefourn_prepare_head($object);

					dol_fiche_head($head, $headername."_".$tabsid, $langs->trans("SupplierInvoice"), 0, 'bill');

					// Supplier invoice card
					$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php';
					$linkback.= (! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

					$morehtmlref='<div class="refidno">';
					// Ref supplier
					$morehtmlref.=$form->editfieldkey(
									"RefSupplier", 'ref_supplier', $object->ref_supplier, $object,
									0, 'string', '', 0, 1
					);
					$morehtmlref.=$form->editfieldval(
									"RefSupplier", 'ref_supplier', $object->ref_supplier, $object,
									0, 'string', '', null, null, '', 1
					);
					// Thirdparty
					$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
					// Project
					if (! empty($conf->projet->enabled)) {
						$langs->load("projects");
						$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
						if (! empty($object->fk_project)) {
							$proj = new Project($this->db);
							$proj->fetch($object->fk_project);
							$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'"';
							$morehtmlref.=' title="'.$langs->trans('ShowProject').'">'.$proj->ref.'</a>';
						} else {
							$morehtmlref.='';
						}
					}
					$morehtmlref.='</div>';
					// To give a chance to dol_banner_tab to use already paid amount to show correct status
					$object->totalpaye = $alreadypaid;
					dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '&tabsid='.$tabsid);

					print '<div class="fichecenter">';
					print '<div class="underbanner clearboth"></div>';
					print '</div>';
					print '<table class="border" width="100%">';
					break;

				case 'invoice' :
					$object->fetch_thirdparty();
					$head = facture_prepare_head($object);
					$totalpaye = $object->getSommePaiement();

					dol_fiche_head($head, $headername."_".$tabsid, $langs->trans("InvoiceCustomer"), 0, 'bill');
					// Invoice content

					$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php';
					$linkback.= (! empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
					$morehtmlref='<div class="refidno">';
					// Ref customer
					$morehtmlref.=$form->editfieldkey(
									"RefCustomer", 'ref_client', $object->ref_client, $object,
									0, 'string', '', 0, 1
					);
					$morehtmlref.=$form->editfieldval(
									"RefCustomer", 'ref_client', $object->ref_client, $object,
									0, 'string', '', null, null, '', 1
					);
					// Thirdparty
					$morehtmlref.='<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
					// Project
					if (! empty($conf->projet->enabled)) {
						$langs->load("projects");
						$morehtmlref.='<br>'.$langs->trans('Project').' ';
						if (! empty($object->fk_project)) {
							$proj = new Project($this->db);
							$proj->fetch($object->fk_project);
							$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'"';
							$morehtmlref.=' title="'.$langs->trans('ShowProject').'">';
							$morehtmlref.=$proj->ref;
							$morehtmlref.='</a>';
						} else
							$morehtmlref.='';
					}
					$morehtmlref.='</div>';

					// To give a chance to dol_banner_tab to use already paid amount to show correct status
					$object->totalpaye = $totalpaye;
					dol_banner_tab($object, 'ref', $linkback, 1, 'facnumber', 'ref', $morehtmlref, '', 0);

					print '<div class="fichecenter">';
					print '<div class="underbanner clearboth"></div>';
					print '<table class="border" width="100%">';
					break;

				case 'payment_salaries' :
				case 'payment_vat' :
				case 'tax' :

				// old school yet
				case 'supplier_proposal' :

				case 'usergroup' :

				default :
					$ret = $this->old_tabs_head_element($tabsid, $headername);
					break;

			}
		}
		else
			$ret = $this->old_tabs_head_element($tabsid, $headername);

		// additionnal fields on elements
		$hookmanager->initHooks(array($patastabs."_".$this->element));
		$parameters=array();
		$reshook=$hookmanager->executeHooks('addRowInHead', $parameters, $object, $action);

		print '</table>';
		print '</div>';
	}

	function old_tabs_head_element($tabsid, $headername="patastabs")
	{
		global $langs;
		global $object;
		global $form;
		global $user;
		global $conf;


		// selon l'onglet on affiche les données de l'onglet
		switch($this->element) {
			case 'thirdparty' :
				$head = societe_prepare_head($object);
				$title = $langs->trans("ThirdParty");
				dol_fiche_head($head, "mylist_".$tabsid, $title, 0, 'company');
				print '<table class="border"width="100%">';
				print '<tr><td width="25%">'.$langs->trans("ThirdPartyName").'</td>';
				print '<td colspan="3">';
				print $form->showrefnav($object, 'id', '', ($user->societe_id?0:1), 'rowid', 'nom', '', '&tabsid='.$tabsid);
				print '</td></tr>';

				// Prefix
				if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
					print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';

				if ($object->client) {
					print '<tr><td>';
					print $langs->trans('CustomerCode').'</td><td colspan="3">';
					print $object->code_client;
					if ($object->check_codeclient() <> 0)
						print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
					print '</td></tr>';
				}

				if ($object->fournisseur) {
					print '<tr><td>';
					print $langs->trans('SupplierCode').'</td><td colspan="3">';
					print $object->code_fournisseur;
					if ($object->check_codefournisseur() <> 0)
						print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
					print '</td></tr>';
				}
				break;

			case 'contact' :
				$head = contact_prepare_head($object);
				if (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT))
					$title = $langs->trans("Contacts");
				else
					$title = $langs->trans("ContactsAddresses");
				dol_fiche_head($head, "mylist_".$tabsid, $title, 0, 'contact');
				print '<table class="border" width="100%">';

				$linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php">'.$langs->trans("BackToList").'</a>';

				// Ref
				print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
				print '<td colspan="3">'.$form->showrefnav($object, 'id', $linkback).'</td></tr>';

				// Name
				print '<tr><td width="20%">'.$langs->trans("Lastname").' / '.$langs->trans("Label").'</td>';
				print '<td width="30%">'.$object->lastname.'</td>';
				print '<td width="20%">'.$langs->trans("Firstname").'</td>';
				print '<td width="30%">'.$object->firstname.'</td></tr>';

				// Company
				if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
					if ($object->socid > 0) {
						$objsoc = new Societe($this->db);
						$objsoc->fetch($object->socid);
						print '<tr><td>'.$langs->trans("Company").'</td>';
						print '<td colspan="3">'.$objsoc->getNomUrl(1).'</td></tr>';
					} else {
						print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
						print $langs->trans("ContactNotLinkedToCompany");
						print '</td></tr>';
					}
				}

				// Civility
				print '<tr><td>'.$langs->trans("UserTitle").'</td><td colspan="3">';
				print $object->getCivilityLabel();
				print '</td></tr>';
				break;

			case 'product' :
				$head = product_prepare_head($object, $user);
				$titre=$langs->trans("CardProduct".$object->type);
				$picto=($object->type==1?'service':'product');
				dol_fiche_head($head, "mylist_".$tabsid, $titre, 0, $picto);
				print '<table class="border" width="100%">';

				print '<tr>';
				print '<td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
				print $form->showrefnav($object, 'ref', '', 1, 'ref', '', '', '&tabsid='.$tabsid);
				print '</td>';
				print '</tr>';

				// Label
				print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$object->libelle.'</td></tr>';

				// Status (to sell)
				print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>';
				print $object->getLibStatut(2, 0);
				print '</td></tr>';

				// Status (to buy)
				print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td>';
				print $object->getLibStatut(2, 1);
				print '</td></tr>';
				break;

			case 'stock' :
				$head = stock_prepare_head($object);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans("Warehouse"), 0, 'stock');
				print '<table class="border" width="100%">';
				$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/liste.php">'.$langs->trans("BackToList").'</a>';
				// Ref
				print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
				print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'libelle', '', '&tabsid='.$tabsid);
				print '</td>';

				print '<tr><td>'.$langs->trans("LocationSummary").'</td><td colspan="3">'.$object->lieu.'</td></tr>';
				// Description
				print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
				print '<td colspan="3">'.nl2br($object->description).'</td></tr>';
				// Address
				print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3">'.$object->address.'</td></tr>';

				// Town
				print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$object->zip.'</td>';
				print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$object->town.'</td></tr>';

				// Country
				print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
				if (! empty($object->country_code)) {
					$img=picto_from_langcode($object->country_code);
					print ($img?$img.' ':'');
				}
				print $object->country;
				print '</td></tr>';
				break;

			case 'member' :
				$head = member_prepare_head($object);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans("Member"), 0, 'user');

				$adht = new Adherent($this->db);
				$result=$adht->fetch($object->typeid);

				print '<table class="border" width="100%">';
				$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/liste.php">'.$langs->trans("BackToList").'</a>';
				// Reference
				print '<tr><td width="20%">'.$langs->trans('Ref').'</td>';
				print '<td colspan="3">';
				print $form->showrefnav($object, 'id', $linkback, 1, 'ref', '', '', '&tabsid='.$tabsid);

				print '</td></tr>';

				// Login
				if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
					print '<tr><td>'.$langs->trans("Login").' / '.$langs->trans("Id").'</td>';
					print '<td class="valeur">'.$object->login.'&nbsp;</td></tr>';
				}
				// Morphy
				print '<tr><td>'.$langs->trans("Nature").'</td>';
				print '<td class="valeur" >'.$object->getmorphylib().'</td></tr>';
				// Type
				print '<tr><td>'.$langs->trans("Type").'</td>';
				print '<td class="valeur">'.$adht->getNomUrl(1)."</td></tr>\n";
				// Company
				print '<tr><td>'.$langs->trans("Company").'</td>';
				print '<td class="valeur">'.$object->societe.'</td></tr>';
				// Civility
				print '<tr><td>'.$langs->trans("UserTitle").'</td>';
				print '<td class="valeur">'.$object->getCivilityLabel().'&nbsp;</td></tr>';
				// Lastname
				print '<tr><td>'.$langs->trans("Lastname").'</td>';
				print '<td class="valeur" colspan="3">'.$object->lastname.'&nbsp;</td></tr>';
				// Firstname
				print '<tr><td>'.$langs->trans("Firstname").'</td>';
				print '<td class="valeur" colspan="3">'.$object->firstname.'&nbsp;</td></tr>';
				// Status
				print '<tr><td>'.$langs->trans("Status").'</td>';
				print '<td class="valeur">'.$object->getLibStatut(4).'</td></tr>';
				break;

			case 'project' :
				$head = project_prepare_head($object);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans('Project'), 0, ($object->public?'projectpub':'project'));

				print '<table class="border" width="100%">';

				$linkback = '<a href="'.DOL_URL_ROOT.'/projet/liste.php">'.$langs->trans("BackToList").'</a>';

				// Ref
				print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
				// Define a complementary filter for search of next/prev ref.
				if (! $user->rights->projet->all->lire) {
					$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
					$projectsListId = $object->getProjectsAuthorizedForUser($user, $mine, 0);
					$object->next_prev_filter=" rowid in (".(count($projectsListId)?join(',', array_keys($projectsListId)):'0').")";
				}
				print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref').'</td></tr>';

				// Label
				print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->title.'</td></tr>';

				print '<tr><td>'.$langs->trans("Company").'</td><td>';
				if ($object->socid > 0) {
					$objsoc = new Societe($this->db);
					$objsoc->fetch($object->socid);
					print $objsoc->getNomUrl(1);
				}
				else print'&nbsp;';
				print '</td></tr>';

				// Visibility
				print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
				if ($object->public) print $langs->trans('SharedProject');
				else print $langs->trans('PrivateProject');
				print '</td></tr>';

				// Statut
				print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';
				break;

			case 'contract' :
				$object->fetch_thirdparty();
				$head = contract_prepare_head($object);

				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans("Contract"), 0, 'contract');
				print '<table class="border" width="100%">';

				$linkback = '<a href="'.DOL_URL_ROOT.'/contrat/liste.php';
				$linkback.= (! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
				// Reference
				print '<tr><td width="25%">'.$langs->trans('Ref').'</td>';
				print '<td colspan="5">'.$form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '').'</td></tr>';

				// Societe
				print '<tr><td>'.$langs->trans("Customer").'</td>';
				print '<td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';
				break;

			case 'intervention' :
				$object->fetch_thirdparty();
				$head=fichinter_prepare_head($object, $user);

				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans("InterventionCard"), 0, 'intervention');
				print '<table class="border" width="100%">';

				$linkback = '<a href="'.DOL_URL_ROOT.'/fichinter/list.php';
				$linkback.= (! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
				// Reference
				print '<tr><td width="30%">'.$langs->trans("Ref").'</td>';
				print '<td>'.$form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', '&tabsid='.$tabsid);
				print '</td></tr>';

				// Societe
				print "<tr><td>".$langs->trans("Company")."</td><td>".$object->thirdparty->getNomUrl(1)."</td></tr>";
				break;

			case 'shipping' :
				$soc = new Societe($this->db);
				$soc->fetch($object->socid);

				$head=shipping_prepare_head($object);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans("Shipment"), 0, 'sending');
				print '<table class="border" width="100%">';

				$linkback = '<a href="'.DOL_URL_ROOT.'/expedition/liste.php">'.$langs->trans("BackToList").'</a>';
				// Ref
				print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
				print '<td colspan="3">';
				print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', '&tabsid='.$tabsid).'</td></tr>';

				// Customer
				print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
				print '<td colspan="3">'.$soc->getNomUrl(1).'</td></tr>';

				// Linked documents
				if ($typeobject == 'commande' && $object->$typeobject->id && ! empty($conf->commande->enabled)) {
					print '<tr><td>';
					$objectsrc=new Commande($this->db);
					$objectsrc->fetch($object->$typeobject->id);
					print $langs->trans("RefOrder").'</td>';
					print '<td colspan="3">';
					print $objectsrc->getNomUrl(1, 'commande');
					print "</td>\n";
					print '</tr>';
				}
				if ($typeobject == 'propal' && $object->$typeobject->id && ! empty($conf->propal->enabled)) {
					print '<tr><td>';
					$objectsrc=new Propal($this->db);
					$objectsrc->fetch($object->$typeobject->id);
					print $langs->trans("RefProposal").'</td>';
					print '<td colspan="3">';
					print $objectsrc->getNomUrl(1, 'expedition');
					print "</td>\n";
					print '</tr>';
				}
				// Ref customer
				print '<tr><td>'.$langs->trans("RefCustomer").'</td>';
				print '<td colspan="3">'.$object->ref_customer."</a></td></tr>";
				break;

			case 'user' :
				$head = user_prepare_head($object);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans("User"), 0, 'user', '', '&tabsid='.$tabsid);
				print '<table class="border" width="100%">';

				// Reference
				print '<tr><td width="20%">'.$langs->trans('Ref').'</td>';
				print '<td colspan="3">';
				print $form->showrefnav($object, 'id', '', $user->rights->user->user->lire || $user->admin);
				print '</td></tr>';

				// Lastname
				print '<tr><td>'.$langs->trans("Lastname").'</td>';
				print '<td class="valeur" colspan="3">'.$object->lastname.'&nbsp;</td></tr>';
				// Firstname
				print '<tr><td>'.$langs->trans("Firstname").'</td>';
				print '<td class="valeur" colspan="3">'.$object->firstname.'&nbsp;</td></tr>';
				// Login
				print '<tr><td>'.$langs->trans("Login").'</td>';
				print '<td class="valeur" colspan="3">'.$object->login.'&nbsp;</td></tr>';
				break;

			case 'usergroup' :
				$head = group_prepare_head($object);

				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans("Group"), 0, 'group');

				print '<table class="border" width="100%">';

				// Ref
				print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
				print '<td colspan="2">'.$form->showrefnav($object, 'id', '', $canreadperms);
				print '</td></tr>';

				// Name
				print '<tr><td width="25%" valign="top">'.$langs->trans("Name").'</td>';
				print '<td width="75%" class="valeur">'.$object->nom;
				if (!$object->entity)
					print img_picto($langs->trans("GlobalGroup"), 'redstar');
				print "</td></tr>\n";
				break;

			case 'payment_salaries' :
				$head = payment_salaries_prepare_head($object);

				dol_fiche_head($head, "myslist_".$tabsid, $langs->trans("SalaryPayment"), 0, 'payment');

				print '<table class="border" width="100%">';

				// Ref
				print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
				print '<td colspan="2">'.$form->showrefnav($object, 'id', '', $canreadperms);
				print '</td></tr>';

				// Person
				print '<tr><td>'.$langs->trans("Person").'</td><td>';
				$usersal=new User($this->db);
				$usersal->fetch($object->fk_user);
				print $usersal->getNomUrl(1);
				print '</td></tr>';

				// Label
				print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->label.'</td></tr>';

				print "<tr>";
				print '<td>'.$langs->trans("DateStartPeriod").'</td><td colspan="3">';
				print dol_print_date($object->datesp, 'day');
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("DateEndPeriod").'</td><td colspan="3">';
				print dol_print_date($object->dateep, 'day');
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("Amount").'</td>';
				print '<td colspan="3">';
				print price($object->amount, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';
				break;

			case 'payment_vat' :
				$head = payment_vat_prepare_head($object);

				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans("VATPayment"), 0, 'payment');

				print '<table class="border" width="100%">';

				// Ref
				print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
				print '<td colspan="2">'.$form->showrefnav($object, 'id', '', $canreadperms);
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("Amount").'</td>';
				print '<td colspan="3">';
				print price($object->amount, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';
				break;

			case 'tax' :
				$head = tax_prepare_head($object);

				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans("SocialContribution"), 0, 'bill');

				print '<table class="border" width="100%">';

				// Ref
				print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
				print '<td colspan="2">'.$form->showrefnav($object, 'id', '', $canreadperms);
				print '</td></tr>';

				// Label
				print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$object->lib.'</td></tr>';

				// Type
				print "<tr><td>".$langs->trans("Type")."</td><td>".$object->type_libelle."</td>";

				// Amount
				print '<tr><td>'.$langs->trans("AmountTTC").'</td>';
				print '<td>'.price($object->amount, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';
				break;

			case 'equipement' :
				$soc=new Societe($this->db);
				$head = equipement_prepare_head($object);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans('EquipementCard'), 0, 'equipement@equipement');

				print '<table class="border" width="100%">';
				print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="3">';
				print $form->showrefnav($object, 'ref', '', 1, 'ref', 'ref', '', '&tabsid='.$tabsid);
				print '</td></tr>';
				require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
				$prod=new Product($this->db);
				$prod->fetch($object->fk_product);
				print '<tr><td >'.$langs->trans("Product").'</td>';
				print '<td>'.$prod->getNomUrl(1)." : ".$prod->label.'</td></tr>';

				// fournisseur
				print '<tr><td >'.$langs->trans("Fournisseur").'</td><td>';
				if ($object->fk_soc_fourn > 0) {
					$soc->fetch($object->fk_soc_fourn);
					print $soc->getNomUrl(1);
				}
				print '</td></tr>';

				// client
				print '<tr><td >'.$langs->trans("Client").'</td><td>';
				if ($object->fk_soc_client > 0) {
					$soc->fetch($object->fk_soc_client);
					print $soc->getNomUrl(1);
				}
				print '</td></tr>';
				break;

			case 'factory' :
				$head = factory_prepare_head($object);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans('FactoryCard'), 0, 'factory@factory');

				print '<table class="border" width="100%">';
				print '<tr><td width="25%">' . $langs->trans('Ref') . '</td><td colspan="3">';
				print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'ref', '', '&tabsid='.$tabsid);
				print '</td></tr>';
				require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
				require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
				$prod=new Product($this->db);
				$prod->fetch($object->fk_product);
				print '<tr><td >'.$langs->trans("Product").'</td>';
				print '<td>'.$prod->getNomUrl(1)." : ".$prod->label.'</td></tr>';

				// Lieu de stockage
				print '<tr><td>'.$langs->trans("EntrepotStock").'</td><td>';
				if ($object->fk_entrepot>0) {
					$entrepotStatic=new Entrepot($this->db);
					$entrepotStatic->fetch($object->fk_entrepot);
					print $entrepotStatic->getNomUrl(1)." - ".$entrepotStatic->lieu." (".$entrepotStatic->zip.")" ;
				}
				print '</td></tr>';

				// Date start planned
				print '<tr><td width=20% >'.$langs->trans("DateStartPlanned").'</td><td width=30% valign=top>';
				print dol_print_date($object->date_start_planned, 'day');
				print '</td>';
				// Date start made
				print '<td valign=top  width=20%>'.$langs->trans("DateStartMade").'</td>';
				print '<td width=30% >';
				print dol_print_date($object->date_start_made, 'day');
				print '</td></tr>';
				break;

			case 'lead' :
				$soc=new Societe($this->db);
				$head = lead_prepare_head($object);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans('LeadCard'), 0, 'lead@lead');

				print '<table class="border" width="100%">';
				print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="3">';
				print $form->showrefnav($object, 'ref', '', 1, 'ref', 'ref');
				print '</td></tr>';
				break;

			case 'ticketsup' :
				if ($object->fk_soc > 0) {
					$object->fetch_thirdparty();
					require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
					$head = societe_prepare_head($object->thirdparty);
					dol_fiche_head($head, 'ticketsup', $langs->trans("ThirdParty"), 0, 'company');
					dol_banner_tab($object->thirdparty, 'socid', '', ($user->societe_id ? 0 : 1), 'rowid', 'nom', '&tabsid='.$tabsid);
					dol_fiche_end();
				}
				if (!$user->societe_id && $conf->global->TICKETS_LIMIT_VIEW_ASSIGNED_ONLY)
					$object->next_prev_filter = "te.fk_user_assign = '" . $user->id . "'";
				elseif ($user->societe_id > 0)
					$object->next_prev_filter = "te.fk_soc = '" . $user->societe_id . "'";

				$head = ticketsup_prepare_head($object);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans("Ticket"), 0, 'ticketsup@ticketsup');
				$object->label = $object->ref;
				// Author
				if ($object->fk_user_create > 0) {
					$object->label .= ' - ' . $langs->trans("CreatedBy") . '  ';
					$langs->load("users");
					$fuser = new User($this->db);
					$fuser->fetch($object->fk_user_create);
					$object->label .= $fuser->getNomUrl(0);
				}
				$linkback = '<a href="'.dol_buildpath('/ticketsup/list.php', 1).'"><strong>';
				$linkback.= $langs->trans("BackToList").'</strong></a> ';
				$object->ticketsup_banner_tab(
								'track_id', '', ($user->societe_id ? 0 : 1),
								'track_id', 'subject', '', '', '', $morehtmlleft, $linkback
				);

				//dol_fiche_end();
				print '<br>';
				break;

			case 'dictionary' :
				$head = dictionary_prepare_head($object);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans('Dictionarys'), 0, 'customtabs@customtabs');
				break;

			case 'propal' :
				$head = propal_prepare_head($object);

				$soc = new Societe($this->db);
				$soc->fetch($object->socid);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans('Proposal'), 0, 'propal');

				print '<table class="border" width="100%">';

				$linkback = '<a href="'.DOL_URL_ROOT.'/comm/propal/list.php';
				$linkback.= (! empty($object->socid) ? '?socid='.$object->socid : '').'">';
				$linkback.= $langs->trans("BackToList").'</a>';

				// Ref
				print '<tr><td width="25%">'.$langs->trans('Ref').'</td>';
				print '<td colspan="3">';

				print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', '&tabsid='.$tabsid);
				print '</td></tr>';

				// Ref customer
				print '<tr><td width="20%">';
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('RefCustomer');
				print '</td>';
				print '</tr></table>';
				print '</td>';
				print '<td colspan="5">';
				print $object->ref_client;
				print '</td></tr>';

				// Company
				print '<tr><td>'.$langs->trans("Company").'</td>';
				print '<td colspan="3">'.$soc->getNomUrl(1).'</td></tr>';

				break;

			case 'commande' :
				$head = commande_prepare_head($object);

				$soc = new Societe($this->db);
				$soc->fetch($object->socid);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans('CustomerOrder'), 0, 'order');
				print '<table class="border" width="100%">';

				$linkback = '<a href="'.DOL_URL_ROOT.'/commande/liste.php';
				$linkback.=(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

				// Ref
				print '<tr><td width="25%">'.$langs->trans('Ref').'</td>';
				print '<td colspan="3">';

				print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', '&tabsid='.$tabsid);
				print '</td></tr>';

				// Ref customer
				print '<tr><td width="20%">';
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('RefCustomer');
				print '</td>';
				print '</tr></table>';
				print '</td>';
				print '<td colspan="5">';
				print $object->ref_client;
				print '</td></tr>';

				// Company
				print '<tr><td>'.$langs->trans("Company").'</td>';
				print '<td colspan="3">'.$soc->getNomUrl(1, 'compta').'</td></tr>';

				break;

			case 'invoice' :
				$head = facture_prepare_head($object);

				$soc = new Societe($this->db);
				$soc->fetch($object->socid);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans('InvoiceCustomer'), 0, 'bill');
				print '<table class="border" width="100%">';

				$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php';
				$linkback.=(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

				// Ref
				print '<tr><td width="25%">'.$langs->trans('Ref').'</td>';
				print '<td colspan="3">';

				print $form->showrefnav($object, 'ref', $linkback, 1, 'facnumber', 'ref', '', '&tabsid='.$tabsid);
				print '</td></tr>';

				// Ref customer
				print '<tr><td width="20%">';
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('RefCustomer');
				print '</td>';
				print '</tr></table>';
				print '</td>';
				print '<td colspan="5">';
				print $object->ref_client;
				print '</td></tr>';

				// Company
				print '<tr><td>'.$langs->trans("Company").'</td>';
				print '<td colspan="3">'.$soc->getNomUrl(1, 'compta').'</td></tr>';

				break;

			case 'supplier_invoice' :

				$langs->load("suppliers");
				$object->fetch_thirdparty();
				$head = facturefourn_prepare_head($object);
				$titre=$langs->trans('SupplierInvoice');
				$morehtmlref="";

				dol_fiche_head($head, "mylist_".$tabsid, $titre, 0, 'bill');

				print '<table class="border" width="100%">';

				$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php';
				$linkback.= (! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

				// Ref
				print '<tr><td width="20%" class="nowrap">'.$langs->trans("Ref").'</td><td colspan="3">';
				print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '&tabsid='.$tabsid);
				print '</td>';
				print "</tr>\n";

				// Ref supplier
				print '<tr><td class="nowrap">'.$langs->trans("RefSupplier").'</td>';
				print '<td colspan="3">'.$object->ref_supplier.'</td>';
				print "</tr>\n";

				// Company
				print '<tr><td>'.$langs->trans('Supplier').'</td>';
				print '<td colspan="3">'.$object->thirdparty->getNomUrl(1, 'supplier').'</td></tr>';

				// Type
				print '<tr><td>'.$langs->trans('Type').'</td><td colspan="4">';
				print $object->getLibType();
				if ($object->type == 1) {
					$facreplaced=new FactureFournisseur($this->db);
					$facreplaced->fetch($object->fk_facture_source);
					print ' ('.$langs->transnoentities("ReplaceInvoice", $facreplaced->getNomUrl(1)).')';
				}
				if ($object->type == 2) {
					$facusing=new FactureFournisseur($this->db);
					$facusing->fetch($object->fk_facture_source);
					print ' ('.$langs->transnoentities("CorrectInvoice", $facusing->getNomUrl(1)).')';
				}

				$facidavoir=$object->getListIdAvoirFromInvoice();
				if (count($facidavoir) > 0) {
					print ' ('.$langs->transnoentities("InvoiceHasAvoir");
					$i=0;
					foreach ($facidavoir as $fid) {
						if ($i==0) print ' ';
						else print ',';
						$facavoir=new FactureFournisseur($this->db);
						$facavoir->fetch($fid);
						print $facavoir->getNomUrl(1);
					}
					print ')';
				}
				if ($facidnext > 0) {
					$facthatreplace=new FactureFournisseur($this->db);
					$facthatreplace->fetch($facidnext);
					print ' ('.$langs->transnoentities("ReplacedByInvoice", $facthatreplace->getNomUrl(1)).')';
				}
				print '</td></tr>';
				// Label
				print '<tr><td>'.$langs->transnoentities("Label").'</td><td colspan="3">'.$object->label.'</td></tr>';
				break;

			case 'supplier_order' :
				$object->fetch_thirdparty();
				$head = ordersupplier_prepare_head($object);

				$soc = new Societe($this->db);
				$soc->fetch($object->socid);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans('SupplierOrder'), 0, 'order');
				print '<table class="border" width="100%">';

				$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/liste.php';
				$linkback.=(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

				// Ref
				print '<tr><td width="25%">'.$langs->trans('Ref').'</td>';
				print '<td colspan="3">';

				print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', '&tabsid='.$tabsid);
				print '</td></tr>';

				// Ref customer
				print '<tr><td width="20%">';
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('RefSupplier');
				print '</td>';
				print '</tr></table>';
				print '</td>';
				print '<td colspan="5">';
				print $object->ref_supplier;
				print '</td></tr>';

				// Company
				print '<tr><td>'.$langs->trans("Company").'</td>';
				print '<td colspan="3">'.$soc->getNomUrl(1, 'compta').'</td></tr>';

				break;

			case 'bank' :
				$head=bank_prepare_head($object);

				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans('FinancialAccount'), 0, 'account');

				print '<table class="border" width="100%">';

				$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/index.php">'.$langs->trans("BackToList").'</a>';

				// Ref
				print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
				print '<td colspan="3">';
				print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', '', '&tabsid='.$tabsid);
				print '</td></tr>';

				// Label
				print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
				print '<td colspan="3">'.$object->label.'</td></tr>';

				break;

			case 'agefodd_session' :
				$head=session_prepare_head($object);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans('AgfSessionDetail'), 0, 'calendarday');

				print '<table class="border" width="100%">';

				$linkback = '<a href="'.DOL_URL_ROOT.'/agefodd/session/list.php">'.$langs->trans("BackToList").'</a>';

				// Ref
				print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
				print '<td colspan="3">';
				print $object->id;
				print '</td></tr>';

				print '<tr><td>' . $langs->trans("AgfFormIntitule") . '</td>';
				print '<td>' . $object->formintitule.'</td></tr>';

				print '<tr><td>' . $langs->trans("AgfFormIntituleCust") . '</td>';
				print '<td>'. $object->intitule_custo .'</td></tr>';

				// Label
				print '<tr><td valign="top">'.$langs->trans("AgfFormRef").'</td>';
				print '<td colspan="3">'.$object->formref.'</td></tr>';
				break;

			default :
				$fct_headname =$this->element."_prepare_head";
				$elementname = ucfirst($this->element);
				$head = $fct_headname($object);
				dol_fiche_head($head, "mylist_".$tabsid, $langs->trans($elementname), 0, $this->element.'@'.$this->element);

				print '<table class="border" width="100%">';
				print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="3">';
				print $form->showrefnav($object, 'ref', '', 1, 'ref', '', '&tabsid='.$tabsid);
				print '</td></tr>';
				break;
		}
	}
}