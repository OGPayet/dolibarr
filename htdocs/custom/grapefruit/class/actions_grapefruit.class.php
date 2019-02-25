<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    class/actions_grapefruit.class.php
 * \ingroup grapefruit
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsGrapeFruit
 */
class ActionsGrapeFruit
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $db, $user, $langs;

		dol_include_once('/grapefruit/class/grapefruit.class.php');
		require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

		$TContext = explode(':', $parameters['context']);

		$actionATM = GETPOST('actionATM');
		if ($parameters['currentcontext'] == 'ordercard' && $object->statut >= 1 && !empty($conf->global->GRAPEFRUIT_ALLOW_CREATE_BILL_EXPRESS))
		{

			if($actionATM === 'create_bill_express'
				&& !empty($conf->global->GRAPEFRUIT_ALLOW_CREATE_BILL_EXPRESS)
				&& $object->statut > Commande::STATUS_DRAFT
				&& !$object->billed
				&& !empty($conf->facture->enabled)
				&& $user->rights->facture->creer
				&& empty($conf->global->WORKFLOW_DISABLE_CREATE_INVOICE_FROM_ORDER)) {

				TGrappeFruit::createFactureFromObject($object);

			}
		}
		if ($parameters['currentcontext'] == 'ordersuppliercard')
		{
			if ($action == 'builddoc' && !empty($conf->global->GRAPEFRUIT_FORCE_VAR_HIDEREF_ON_SUPPLIER_ORDER))
			{
				global $hideref;
				$hideref = 0;
			}
		}

		// Bypass des confirmation
		if (in_array('globalcard', $TContext))
		{

			if(!empty($conf->global->GRAPEFRUIT_SHOW_THIRDPARTY_INTO_LINKED_ELEMENT)) {

				$conf->modules_parts['tpl']=array_merge($conf->modules_parts['tpl'],array('/grapefruit/core/tpl'));

			}

			$actionList = explode(',', $conf->global->GRAPEFRUIT_BYPASS_CONFIRM_ACTIONS);
			if (!empty($action) && !empty($conf->global->GRAPEFRUIT_BYPASS_CONFIRM_ACTIONS) && in_array($action, $actionList))
			{
				global $confirm;
				$confirm = 'yes';
				if (in_array('propalcard', $TContext) && ($action=='modif')) {
					$action = 'modif';
				} else {
					$action = 'confirm_'.$action;
				}
			}
		}

		if (in_array('invoicecard', $TContext) && defined('Facture::TYPE_SITUATION'))
		{
			if ($object->type == Facture::TYPE_SITUATION) $object->setValueFrom('ishidden', 0, 'extrafields', '"grapefruit_default_situation_progress_line"', '', 'name');
			else $object->setValueFrom('ishidden', 1, 'extrafields', '"grapefruit_default_situation_progress_line"', '', 'name');
		}

		if (in_array('ordercard', $TContext))
		{
			if (!empty($conf->global->GRAPEFRUIT_ORDER_EXPRESS_FROM_PROPAL) && GETPOST('origin') === 'propal' && GETPOST('originid') > 0 && $action == 'create' && GETPOST('socid', 'int') > 0)
			{
				require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

				$propal = new Propal($db);
				if ($propal->fetch(GETPOST('originid')) > 0)
				{
					if ($object->createFromProposal($propal) > 0)
					{
						header('Location: '.dol_buildpath('/commande/card.php', 1).'?id='.$object->id);
						exit;
					}
					else
					{
						dol_print_error($db);
					}
				}
				else
				{
					dol_print_error($db);
				}
			}
		}

		if(!empty($conf->global->GRAPEFRUIT_ALLOW_RESTOCK_ON_CREDIT_NOTES) && empty($conf->global->STOCK_CALCULATE_ON_BILL) && $object->element === 'facture' && $object->type == Facture::TYPE_CREDIT_NOTE) {
			// Pour empêcher de remplir le form confirm de manière à exécuter le notre
			if($action === 'valid') $action = 'validATM';
		}

		return 0;
	}

	function formEditProductOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;

		$TContext = explode(':', $parameters['context']);

		if (!empty($conf->global->GRAPEFRUIT_FAST_UPDATE_ON_HREF) && count(array_intersect(array('invoicecard','propalcard','invoicesuppliercard','ordercard','ordersuppliercard'), $TContext)) > 0)
		{
			?>
			<script type="text/javascript">
				$(function() {
					console.log($('#addproduct '));
					if ($('#addproduct > input[name=action]').val() === 'updateline' || $('#addproduct > input[name=action]').val() === 'updateligne')
					{
						console.log('HGHEHY', $('#tablelines a'));
						// Sur clic d'un lien du tableau, on déclanche la sauvegarde
						$('#tablelines a').click(function(event) {
							var link = $(this).attr('href');
							$.post($('#addproduct').attr('action'), $('#addproduct').serialize()+'&save=fromGrapfruit', function() { window.location.href = link; } );

							return false;
						});
					}
				});
			</script>
			<?php
		}

	}

	function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $user;
		//var_dump($action, $parameters);exit;
		//Context : frm creation propal
		dol_include_once('/grapefruit/lib/grapefruit.lib.php');
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
		$langs->load('bills');
		$langs->load('grapefruit@grapefruit');

		$form = new Form($db);

		// Script pour gérer les champs obligatoires sur une fiche contact
		if(in_array('contactcard',explode(':',$parameters['context'])) && !empty($conf->global->GRAPEFRUIT_CONTACT_FORCE_FIELDS) && ($action == 'edit' || $action == 'create')) {
			$TChamps = explode(',',$conf->global->GRAPEFRUIT_CONTACT_FORCE_FIELDS);
			$first = true;
			$match1 = '';
			$match2 = '#lastname';
			foreach($TChamps as $champ) {
				if(!$first) {
					$match1 .=', ';
				}
				$match2 .=', ';
				$match1 .= 'td > label[for="'.$champ.'"]';
				$match2 .= '#'.$champ;
				$first=false;
			}
			?>
			<script type="text/javascript">
				$(document).ready(function(){
					$('<?php echo $match1; ?>').addClass('fieldrequired');
					$('<?php echo $match2; ?>').attr('required','required');
				})
			</script>
			<?php
		}

		if (in_array('propalcard',explode(':',$parameters['context'])))
		{
			if($action === 'create') {

				if ($conf->grapefruit->enabled && $conf->global->GRAPEFRUIT_PROPAL_DEFAULT_BANK_ACOUNT > 0)
				{
					?>
					<script type="text/javascript">
						$(function() {
							$("select[name=fk_account] option[value=<?php echo $conf->global->GRAPEFRUIT_PROPAL_DEFAULT_BANK_ACOUNT; ?>]").attr('selected', true);
						});
					</script>
					<?php
				}

			} elseif(!empty($conf->global->GRAPEFRUIT_ALLOW_CREATE_ORDER_AND_BILL_ON_UNSIGNED_PROPAL) && $object->statut == 1) {

				?>
					<script type="text/javascript">
						$(document).ready(function() {

							var bt_cmd = $('<a class="butAction" href="<?php echo dol_buildpath('/commande/card.php', 2); ?>?action=create&amp;origin=<?php echo $object->element; ?>&amp;originid=<?php echo $object->id; ?>&amp;socid=<?php echo $object->socid; ?>"><?php echo $langs->trans('AddOrder'); ?></a>');
							var bt_bill = $('<a class="butAction" href="<?php echo dol_buildpath('/compta/facture.php', 2); ?>?action=create&amp;origin=<?php echo $object->element; ?>&amp;originid=<?php echo $object->id; ?>&amp;socid=<?php echo $object->socid; ?>"><?php echo $langs->trans('AddBill'); ?></a>');

							if ($('div.tabsAction a.butAction:contains("<?php print $langs->trans('SendByMail'); ?>")').length > 0) {
								$('div.tabsAction a.butAction:contains("<?php print $langs->trans('SendByMail'); ?>")').after(bt_bill);
								$('div.tabsAction a.butAction:contains("<?php print $langs->trans('SendByMail'); ?>")').after(bt_cmd);
							} else {
								$('div.tabsAction').append(bt_bill);
								$('div.tabsAction').append(bt_cmd);
							}

						});
					</script>
				<?php

			}
			if($conf->global->GRAPEFRUIT_PROPAL_ADD_DISCOUNT_COLUMN){
					addPuHtRemise(5,$object);
			}
		}
		elseif (in_array('thirdpartycard',explode(':',$parameters['context'])))
		{
			if (!empty($conf->global->GRAPEFRUIT_DISABLE_PROSPECTCUSTOMER_CHOICE) && ($action == 'create' || $action == 'edit'))
			{
				?>
				<script type="text/javascript">
					$(function() {
						$('#customerprospect option[value=3]').remove();
					});
				</script>
				<?php
			}

		}

		if (in_array('ordercard',explode(':',$parameters['context']))) {
			if(!empty($conf->global->GRAPEFRUIT_ALLOW_CREATE_BILL_EXPRESS)
				&& $object->statut > Commande::STATUS_DRAFT
				&& !$object->billed
				&& !empty($conf->facture->enabled)
				&& $user->rights->facture->creer
				&& empty($conf->global->WORKFLOW_DISABLE_CREATE_INVOICE_FROM_ORDER)) {

				?>

				<script type="text/javascript">
					var bt_create_fact_express = $('<a class="butAction" href="<?php echo dol_buildpath('/commande/card.php?actionATM=create_bill_express&id='.GETPOST('id'), 2); ?>"><?php echo $langs->trans('GrapefruitCreateBillExpress'); ?></a>');
					$(document).ready(function() {

						if ($('div.tabsAction a.butAction:contains("<?php print $langs->transnoentities('CreateBill'); ?>")').length > 0) {

							$('div.tabsAction a.butAction:contains("<?php print $langs->transnoentities('CreateBill'); ?>")').after(bt_create_fact_express);
						} else {
							$('div.tabsAction').append(bt_create_fact_express);
						}

						// Pour éviter le double clic
						bt_create_fact_express.click(function() {
							this.remove();
						});

					});
				</script>

				<?php

			}
				if($conf->global->GRAPEFRUIT_ORDER_ADD_DISCOUNT_COLUMN){
					addPuHtRemise(5,$object);

			}

		}


		/*else if ($parameters['currentcontext'] === 'invoicecard' && $action === 'confirm_valid') {

				?>
				<script type="text/javascript">
					$(document).ready(function() {

						$a = $('a.butAction[href*=presend]');

						document.location.href = $a.attr('href');

					});
				</script>
				<?php
		}
		else if ($parameters['currentcontext'] === 'invoicecard' && $action === 'presend') {

				?>
				<script type="text/javascript">
					$(document).ready(function() {


						$('')

					});
				</script>
				<?php
		}*/
		if (in_array('invoicecard',explode(':',$parameters['context'])))
		{
			if($conf->global->GRAPEFRUIT_BILL_ADD_DISCOUNT_COLUMN){

				addPuHtRemise(5,$object);
			}
		}

		if(in_array('invoicesuppliercard',explode(':',$parameters['context'])) && !empty($conf->global->GRAPEFRUIT_ALLOW_UPDATE_SUPPLIER_INVOICE_DATE) && empty($object->paye) && $action !== 'editdatef'
			&& (empty($conf->exportcompta->enabled) || (!empty($conf->exportcompta->enabled) && empty($object->array_options['options_date_compta'])))) {

			?>

			<script type="text/javascript">
				$(document).ready(function () {
					var link_edit_datef = $('[href*="action=editdatef"]').attr('href');
					// On ajoute le lien dans les cas où il n'y est pas
					if(typeof link_edit_datef === 'undefined') {
						$('[href*="action=editlabel"]').closest('table').closest('tr').next('tr').html('<td><?php echo $form->editfieldkey((float)DOL_VERSION >= 5 ? 'DateInvoice' : 'Date','datef',$object->datep,$object,1,'datepicker'); ?></td><td colspan="3"><?php echo $form->editfieldval("Date",'datef',$object->datep,$object,1,'datepicker'); ?></td>');
					}
				});
			</script>

			<?php

		}

	}



	function createFrom($parameters, &$object, &$action, $hookmanager) {

		global $conf,$user,$langs;

		if (in_array('invoicecard',explode(':',$parameters['context'])))
		{
			dol_include_once('/grapefruit/class/grapefruit.class.php');
			$langs->load('grapefruit@grapefruit');

			TGrappeFruit::billCloneLink($object,$parameters['objFrom']);


			TGrappeFruit::autoValidateIfFrom($object,$parameters['objFrom']);
		}
	}

	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $conf,$user,$langs,$db,$mysoc;

		$context = explode(':', $parameters['context']);

		if (in_array('suppliercard',explode(':',$parameters['context'])) && !empty($conf->global->GRAPEFRUIT_SUPPLIER_FORCE_BT_ORDER_TO_INVOICE))
		{
			if ($user->rights->fournisseur->facture->creer)
			{
				?>
				<script type="text/javascript">
					$(function() {
						var bt = $('a.butAction[href*="orderstoinvoice.php?socid="]');
						if (bt.length == 0)
						{
							var btOrder = $('<div class="inline-block divButAction"><a class="butAction" href="<?php echo DOL_URL_ROOT; ?>/fourn/commande/orderstoinvoice.php?socid=<?php echo $object->id; ?>"><?php echo $langs->transnoentitiesnoconv("CreateInvoiceForThisCustomer"); ?></a></div>');
							$('div.tabsAction').append(btOrder);
						}
					});
				</script>
				<?php
			}
		}


		if (in_array('propalcard', $context) || in_array('ordercard', $context) || in_array('invoicecard', $context))
		{
			if (!empty($conf->global->GRAPEFRUIT_DEFAULT_TVA_ON_DOCUMENT_CLIENT_ENABLED) && $action != 'editline')
			{
				if (
					($object->element == 'propal' && $object->statut == Propal::STATUS_DRAFT)
					|| ($object->element == 'commande' && $object->statut == Commande::STATUS_DRAFT)
					|| ($object->element == 'facture' && $object->statut == Facture::STATUS_DRAFT)
				)
				{
					dol_include_once('/core/class/html.form.class.php');

					$object->fetch_thirdparty();

					$form = new Form($db);
					$TOption = $form->load_tva('grapefruit_default_tva', '', $mysoc, $object->thirdparty, 0, 0, '', true);

					$select = '<select class="flat" id="grapefruit_default_tva" name="grapefruit_default_tva"><option selected="selected" value=""></option>'.str_replace('selected', '', $TOption).'</select>';
					?>
					<script type="text/javascript">
						$(function() {
							$('#tablelines td.linecolvat').first().append(<?php echo json_encode($select); ?>);

							$('#grapefruit_default_tva').change(function(event) {
								var default_tva = $(this).val();
								if (default_tva != '')
								{
									$.ajax({
										url: '<?php echo dol_buildpath('/grapefruit/script/interface.php', 1); ?>'
										,type: 'POST'
										,data: {
											set: 'defaultTVA'
											,element: '<?php echo $object->element; ?>'
											,element_id: '<?php echo $object->id; ?>'
											,default_tva: default_tva
										}
									}).done(function() {
										var idvar = '<?php echo ($object->element == 'facture') ? 'facid' : 'id'; ?>';
										document.location.href = '?'+idvar+'=<?php echo $object->id; ?>';
									});
								}
							});
						});
					</script>
					<?php
				}

			}

		}

		if (!empty($conf->global->GRAPEFRUIT_SITUATION_INVOICE_DEFAULT_PROGRESS) && in_array('invoicecard', $context) && $object->type == Facture::TYPE_SITUATION && $object->statut == Facture::STATUS_DRAFT)
		{
			?>
			<script type="text/javascript">
				$(function() {
					$('#tablelines td.linecolcycleref').first().append('<br /><input type="text" id="grapefruit_default_progress" name="grapefruit_default_progress" value="" size="2" />');

					$('#grapefruit_default_progress').blur(function() {
						var default_progress = $(this).val();
						if ($.isNumeric(default_progress))
						{
							$.ajax({
								url: '<?php echo dol_buildpath('/grapefruit/script/interface.php', 1); ?>'
								,type: 'POST'
								,data: {
									set: 'defaultProgress'
									,element_id: '<?php echo $object->id; ?>'
									,default_progress: default_progress
								}
							}).done(function() {
								document.location.href = '?facid=<?php echo $object->id; ?>';
							});
						}
					});
				});
			</script>
			<?php
		}

	}


	function pdf_getLinkedObjects(&$parameters, &$object, &$action, $hookmanager) {

		global $conf,$user,$langs,$db,$mysoc,$outputlangs;



		if (in_array( 'pdfgeneration', explode(':',$parameters['context']) ) && !empty($conf->global->GRAPEFRUIT_ADD_PROJECT_TO_PDF))
		{
			if (empty($object->project->ref)) $object->fetch_projet();

			if (! empty($object->project->ref) && !empty($outputlangs))
			{
				$outputlangs->load('grapefruit@grapefruit');
				$outputlangs->load('projects');

				$linkedobjects = $parameters['linkedobjects'];

				$objecttype = 'projet';
				$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("Project");
				$linkedobjects[$objecttype]['ref_value'] = $outputlangs->transnoentities(empty($object->project->ref)?'':$object->projet->ref);
/*				$linkedobjects[$objecttype]['date_title'] = $outputlangs->transnoentities("ProjectDate");
				$linkedobjects[$objecttype]['date_value'] = dol_print_date($object->project->date_start,'day','',$outputlangs);
*/

				$this->results = $linkedobjects;


				return 1;
			}

		}

	}


	function beforePDFCreation(&$parameters, &$object, &$action, $hookmanager)
	{
		global $conf,$user,$langs,$db,$mysoc;

		// Sur version 5.0 le $parameters['currentcontext'] == ordersuppliercard et le "pdfgeneration" est dans $parameters['context']
		$TContext = explode(':', $parameters['context']);

		if (empty($object->_pdfGenerated) && ($parameters['currentcontext'] === 'pdfgeneration' || in_array('pdfgeneration', $TContext)))
		{
			$base_object = $parameters['object'];

			if(isset($base_object) && in_array($base_object->element, array('order_supplier','commande')))
			{
				require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
				$parameters['outputlangs']->load('deliveries');
				$parameters['outputlangs']->load('orders');
				$usecommande=$usecontact=false;
				// Load des contacts livraison
				$arrayidcontact=$base_object->getIdContact('external','SHIPPING');

				if (count($arrayidcontact) > 0)
				{
					$usecontact=true;
					$base_object->fetch_contact($arrayidcontact[0]);
				}
				$base_object->fetchObjectLinked();
				$Qwrite = false;
				if(isset($base_object->linkedObjects['commande']) && !empty($conf->global->GRAPEFRUIT_SUPPLIER_CONTACT_SHIP_ADDRESS))
				{
					// On récupère la donnée de la commande initiale
					// C'est un tableau basé sur des ID donc on boucle pour sortir le premier item
					$commande = reset($base_object->linkedObjects['commande']);
					$date_affiche = date("Y-m-d", $commande->date);
					$ref = $commande->ref;
					$ref_client = $commande->ref_client;
					$usecommande=$Qwrite=true;
				}
				elseif($base_object->element === 'commande' && !empty($conf->global->GRAPEFRUIT_ORDER_CONTACT_SHIP_ADDRESS))
				{
					$date_affiche = date("Y-m-d", $base_object->date);
					$ref = $base_object->ref;
					$ref_client = $base_object->ref_client;
					$usecommande=$Qwrite=true;
				}
				elseif(!empty($conf->global->GRAPEFRUIT_SUPPLIER_CONTACT_SHIP_ADDRESS)){
					$Qwrite=true;
				}

				if($usecontact && $Qwrite)
				{
					//Recipient name
					// On peut utiliser le nom de la societe du contact
					$thirdparty=$base_object->thirdparty;
					if (!empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) $thirdparty = $base_object->contact;

					$thecontact = $base_object->contact;
					if(empty($thecontact->client) && empty($thecontact->thirdparty) && method_exists($thecontact, 'fetch_thirdparty')) $thecontact->fetch_thirdparty();

					if((float) DOL_VERSION < 3.8)
						$objclient = $thecontact->client;
					else
						$objclient = $thecontact->thirdparty;

					$carac_client_name= pdfBuildThirdpartyName($objclient, $parameters['outputlangs']);

					// SI un élément manquant ou qu'on veuille envoyé à la société du contact alors on change
					if(empty($thecontact->address) || empty($thecontact->zip) || empty($thecontact->town))
					{
						$thecontact->address = $objclient->address;
						$thecontact->zip = $objclient->zip;
						$thecontact->town = $objclient->town;
					}

					$carac_client=pdf_build_address($parameters['outputlangs'],$object->emetteur,$objclient,$thecontact,$usecontact,'target');

					/*echo '<pre>';
					var_dump($base_object->contact,true);exit;*/

					if($conf->global->GRAPEFRUIT_SUPPLIER_CONTACT_SHIP_ADDRESS_SHOW_DETAILS){

						$carac_client .= "\n".'email : '.$thecontact->email." \ntel : ".$thecontact->phone_pro;
					}

					$newcontent = $parameters['outputlangs']->trans('DeliveryAddress').' :'."\n".'<strong>'.$carac_client_name.'</strong>'."\n".$carac_client;
					if($usecommande && $conf->global->GRAPEFRUIT_SHOW_SUPPLIER_ORDER_REFS)
					{
						if(isset($ref_client))
							$newcontent .= "\n"."<strong>".$parameters['outputlangs']->trans('RefOrder').' client : </strong>'.$ref_client;
						$newcontent .= "\n"."<strong>".$parameters['outputlangs']->trans('RefOrder').' '.$mysoc->name.' : </strong>'.$ref;
						$newcontent .= "\n"."<strong>".$parameters['outputlangs']->trans('OrderDate').' : </strong>'.$date_affiche;
					}
					if(!empty($parameters['object']->note_public))
						$parameters['object']->note_public = dol_nl2br($newcontent."\n\n".$parameters['object']->note_public);
					else
						$parameters['object']->note_public = dol_nl2br($newcontent);

					$object->_pdfGenerated = true;
				}
			} // Fin order / order_supplier
		}
	}

	function addOptionCalendarEvents($parameters, &$object, &$action, $hookmanager)
	{
		global $db,$conf,$langs;

		if (!empty($conf->global->GRAPEFRUIT_CAN_ASSOCIATE_TASK_TO_ACTIONCOMM) && $parameters['currentcontext']=='fullcalendardao')
		{
			dol_include_once('/core/class/html.form.class.php');

			$form = new Form($db);

			ob_start();
			print '<div id="contentTasks" style="display:inline-block">';
			print $form->selectarray('fk_task', array(), '', 1, 0, 0, '', 0, 0, 0, '', 'minwidth100 maxwidth300', 1);
			print '<div>';
			?>
				<span rel="task"></span>
				<script type="text/javascript">
						$div = $('#pop-new-event');
					$div.find('select#fk_project').on("change", function(e) {
						var fk_project = $(this).val();

						$.ajax({
							url: "<?php echo dol_buildpath('/grapefruit/script/interface.php',1); ?>"
							,dataType:'json'
							,data: {
								get: 'fullcalandar_tasks'
								,projectid: fk_project
							}
						}).done(function(data) {
							/*$('#pop-new-event span[rel=tasks]').html(data.value);*/
							$('#pop-new-event div#contentTasks').text("").append(data.value);
							$div.find('select#fk_task').change();
						});
					});
			</script>
			<?php
			$langs->load('projects');
			$option = $langs->trans('Task').' : '.ob_get_clean();
			$this->resprints = json_encode(array('fk_task' => $option));

			return 1;
		}
		return 0;
	}
	function addStatisticLine($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $conf, $db, $langs;

		if ($parameters['currentcontext'] == 'index' && !empty($conf->global->GRAPEFRUIT_FILTER_HOMEPAGE_BY_USER) && !empty($user->rights->societe->client->voir))
		{
			$langs->load('grapefruit@grapefruit');
			dol_include_once('/core/class/html.form.class.php');
			$form = new Form($db);

			$mode = GETPOST('homepagemode');

			if ($mode == 'filtered')
			{
				unset($user->rights->societe->client->voir);

				print '<p id="homepagemode" align="right">';
				print '<a href="'.$_SERVER["PHP_SELF"].'?homepagemode=notfiltered">'.$langs->trans('WorkingBoardNotFiltered').'</a>';
				print '  /  ';
				print '<strong>'.$langs->trans('WorkingBoardFilterByUser').'</strong>';
				print '</p>';
			}
			else
			{
				print '<p id="homepagemode" align="right">';
				print '<strong>'.$langs->trans('WorkingBoardNotFiltered').'</strong>';
				print '  /  ';
				print '<a href="'.$_SERVER["PHP_SELF"].'?homepagemode=filtered">'.$langs->trans('WorkingBoardFilterByUser').'</a>';

				print '</p>';
			}

			?>
				<script type="text/javascript">
					$(document).ready(function(){
						$("#homepagemode").appendTo(".fichetwothirdright");

						<?php if ($mode == 'filtered') { ?>

                        $("div.boxstatsindicator a, a.boxstatsindicator").attr('href',function(i, href) {
				if(href)
				{
					if(href.indexOf('projet/list') == -1) {
						filtert=1
						var appendUrl = 'search_sale=<?php echo $user->id; ?>';

								if (href.indexOf("comm/action/listactions.php") >= 0){
									appendUrl = 'filtert=<?php echo $user->id; ?>';
								}

						if (href.indexOf("?") >= 0){
							return href + '&' + appendUrl;
						}else{
							return href + '?' + appendUrl;
						}
					}
				}
                        });

						<?php  } ?>



					});

				</script>

			<?php
		}
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function formConfirm($parameters, &$object, &$action, $hookmanager)
	{
		global $conf,$langs;

		//--------------------------------
		// Correction - Open-DSI - Begin
		if (!($object->id > 0)) return 0;
    // Correction - Open-DSI - End
    //--------------------------------

		$TContext = explode(':', $parameters['context']);
		$object->fetchObjectLinked();

		require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

		if ( (in_array('ordercard', $TContext) && !empty($conf->global->GRAPEFRUIT_CONFIRM_ON_CREATE_INVOICE_FROM_ORDER) && !empty($object->linkedObjects['facture']))
			|| (in_array('ordersuppliercard', $TContext) && !empty($conf->global->GRAPEFRUIT_CONFIRM_ON_CREATE_INVOICE_FROM_SUPPLIER_ORDER) && !empty($object->linkedObjects['invoice_supplier']))
		)
		{
			$langs->load('grapefruit@grapefruit');

			if (in_array('ordercard', $TContext))
			{
				$amount = 0;
				foreach ($object->linkedObjects['facture'] as $fk_facture => $facture)
				{
					$amount += $facture->total_ttc;
				}

				if ($amount < $object->total_ttc) return 0;

				$content = $langs->transnoentities('grapefruite_dialog_confirm_create_invoice_content', price($amount, 0, $langs, 1, -1, -1, $conf->currency));
				$formconfirm = '<div id="grapefruit_confirm" title="'.dol_escape_htmltag($langs->transnoentities('grapefruite_dialog_confirm_create_invoice_title')).'" style="display: none;">';
				$formconfirm.= '<div class="confirmmessage">'.img_help('','').' '.$content. '</div>';
				$formconfirm.= '</div>'."\n";

				$selector = '.tabsAction a.butAction[href*="/compta/facture.php?action=create"]';
			}
			else
			{
				$content = $langs->transnoentities('grapefruite_dialog_confirm_create_supplier_invoice_content');
				$formconfirm = '<div id="grapefruit_confirm" title="'.dol_escape_htmltag($langs->transnoentities('grapefruite_dialog_confirm_create_invoice_title')).'" style="display: none;">';
				$formconfirm.= '<div class="confirmmessage">'.img_help('','').' '.$content. '</div>';
				$formconfirm.= '</div>'."\n";

				$selector = '.tabsAction a.butAction[href*="/fourn/facture/card.php?action=create"]';
			}

			?>
			<script type="text/javascript">
				$(function() {
					$('<?php echo $selector; ?>').bind('click', function(event) {
						var self = this;
						event.preventDefault();

						<?php if (!empty($conf->use_javascript_ajax)) { ?>
							var grapefruit_confirm_dialog = <?php echo json_encode($formconfirm); ?>;
							$(grapefruit_confirm_dialog).dialog({
								open: function() {
									$(this).parent().find("button.ui-button:eq(2)").focus();
								}
								,resizable: false
								,height: 200
								,width: 500
								,modal: true
								,buttons: {
									"<?php echo dol_escape_js($langs->transnoentities("Yes")); ?>" : function() {
										$(this).dialog("close");
										window.location = $(self).attr('href');
									}
									,"<?php echo dol_escape_js($langs->transnoentities("No")); ?>": function() {
										$(this).dialog("close");
									}
								}
							});
						<?php } else { ?>
							if (confirm("<?php echo strip_tags($content); ?>"))
							{
								window.location = $(self).attr('href');
							}
						<?php } ?>
					});
				});
			</script>
			<?php
		}

		if(!empty($conf->global->GRAPEFRUIT_ALLOW_RESTOCK_ON_CREDIT_NOTES) && empty($conf->global->STOCK_CALCULATE_ON_BILL) && $object->element === 'facture' && $object->type == Facture::TYPE_CREDIT_NOTE) {
			if($action === 'validATM') {
				print TGrappeFruit::getFormConfirmValidFacture($object);
				TGrappeFruit::printJSFillQtyToRestock();
			}
		}

		return 0;
	}

}
