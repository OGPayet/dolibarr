<?php
class ActionsPropalehistory
{
     /** Overloading the doActions function : replacing the parent's function with the one below
      *  @param      parameters  meta datas of the hook (context, etc...)
      *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
      *  @param      action             current action (if set). Generally create or edit or null
      *  @return       void
      */

    function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
      	global $conf,$langs,$db;
		define('INC_FROM_DOLIBARR', true);
		dol_include_once("/propalehistory/config.php");
		dol_include_once("/comm/propal/class/propal.class.php");

		if (in_array('propalcard',explode(':',$parameters['context'])))
        {

	        if($action != 'create' && $action != 'statut' && $action != 'presend') {
	    		dol_include_once("/propalehistory/class/propaleHist.class.php");
				$ATMdb = new TPDOdb;


		    	$actionATM = GETPOST('actionATM');
		    	$url=DOL_URL_ROOT.'/comm/propal.php';
                if ((float) DOL_VERSION >= 4.0) {
                    $url=DOL_URL_ROOT.'/comm/propal/card.php';
                }
				if($actionATM == 'viewVersion') {
					?>
						<script type="text/javascript">
							$(document).ready(function() {
								$('div.tabsAction').html('<?php echo '<div class="inline-block divButAction"><a id="returnCurrent" href="'.$_SERVER['PHP_SELF'].'?id='.$_REQUEST['id'].'">'.$langs->trans('ReturnInitialVersion').'</a> <a id="butRestaurer" class="butAction" href="'.$url.'?id='.$_REQUEST['id'].'&actionATM=restaurer&idVersion='.$_REQUEST['idVersion'].'">'.$langs->trans('Restaurer').'</a><a id="butSupprimer" class="butActionDelete" href="'.$url.'?id='.$_REQUEST['id'].'&actionATM=supprimer&idVersion='.$_REQUEST['idVersion'].'">'.$langs->trans('Delete').'</a></div>'?>');
								$('#butRestaurer').insertAfter('#voir');
								$('#butSupprimer').insertBefore('#voir');
								$('#builddoc_form').hide();
							})
						</script>

					<?php

					TPropaleHist::listeVersions($db, $object);
				} elseif($actionATM == 'createVersion') {
					TPropaleHist::listeVersions($db, $object);
				} elseif($actionATM == '' && $object->statut == 1) {
                    // TODO Pourquoi c'est ici et pas dans un addMoreActionsButtons ?
					print '<div id="butNewVersion" class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$_REQUEST['id'].'&actionATM=createVersion">'.$langs->trans('PropaleHistoryArchiver').'</a></div>';
					?>
						<script type="text/javascript">
							$(document).ready(function() {
								$("#butNewVersion").appendTo('div.tabsAction');
							})
						</script>
					<?php
					$num = TPropaleHist::listeVersions($db, $object);
				}
				else {

					$num = TPropaleHist::listeVersions($db, $object);


				}
				if(!empty($num) && ! $conf->global->PROPALEHISTORY_HIDE_VERSION_ON_TABS) {
					?>
						<script type="text/javascript">
							$("a#comm").first().append(" / v. <?php echo $num +1 ?>");
						console.log($("a#comm").first());
						</script>
					<?php

				}

			}

		}

		return 0;
	}

	function afterPDFCreation($parameters, &$object, &$action, $hookmanager) {
		global $langs,$db, $user,$conf, $old_propal_ref;

		if(!empty($conf->global->PROPALEHISTORY_SHOW_VERSION_PDF) && !empty($old_propal_ref)) {
			$object_src = $parameters['object'];
			if ($object_src->element == 'propal') $object_src->ref = $old_propal_ref;
			else $object->ref = $old_propal_ref;
		}

        return 0;
	}

	function beforePDFCreation($parameters, &$object, &$action, $hookmanager) {
      	global $langs,$db, $user,$conf, $old_propal_ref;

      	if(!empty($conf->global->PROPALEHISTORY_SHOW_VERSION_PDF) && in_array('propalcard',explode(':',$parameters['context'])) && empty($object->context['propale_history']['original_ref'])) {
			define('INC_FROM_DOLIBARR', true);
			dol_include_once("/propalehistory/config.php");
			dol_include_once("/comm/propal/class/propal.class.php");
			dol_include_once('/propalehistory/class/propaleHist.class.php');

			$TVersion = TPropaleHist::getVersions($db, $object->id);
			$num = count($TVersion);

			if($num>0) {
                $object->context['propale_history'] = array('original_ref' => $object->ref);
                $object->ref .= '/' . ($num+1);
			}

		}

        return 0;

	}

	function formConfirm($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $db, $user;

		if (in_array('propalcard', explode(':', $parameters['context'])) && ! empty($conf->global->PROPALEHISTORY_ARCHIVE_ON_MODIFY))
		{
			// Ask if proposal archive wanted
			if ($_REQUEST['action'] == 'modif') { // $action peut être changé à 'modif' dans doActions() après l'affichage de la pop-in : on teste $_REQUEST['action'] à la place

				$formquestion = array(
					array('type' => 'checkbox', 'name' => 'archive_proposal', 'label' => $langs->trans("ArchiveProposalCheckboxLabel"), 'value' => 1),
				);
				$form = new Form($this->db);
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ArchiveProposal'), $langs->trans('ConfirmModifyProposal', $object->ref), 'propalhistory_confirm_modify', $formquestion, 'yes', 1);

				$this->results = array();
				$this->resprints = $formconfirm;

				return 1; // replace standard code
			}
		}

        return 0;
	}


	function doActions($parameters, &$object, &$action, $hookmanager) {
		global $conf, $langs, $db, $user;

		define('INC_FROM_DOLIBARR', true);
		dol_include_once("/propalehistory/config.php");
		dol_include_once("/comm/propal/class/propal.class.php");
		dol_include_once('/propalehistory/class/propaleHist.class.php');


		if(isset($_REQUEST['mesg'])) {

			setEventMessage($_REQUEST['mesg']);

		}
		$ATMdb = new TPDOdb;

		if (in_array('propalcard', explode(':', $parameters['context'])) && ! empty($conf->global->PROPALEHISTORY_ARCHIVE_ON_MODIFY))
		{

			if ($action == 'modif') {
				return 1; // on saute l'action par défaut en retournant 1, puis on affiche la pop-in dans formConfirm()
			}

			// Ask if proposal archive wanted
			if ($action == 'propalhistory_confirm_modify') {

				// New version if wanted
				$archive_proposal = GETPOST('archive_proposal', 'alpha');
				if ($archive_proposal == 'on') {
					TPropaleHist::archiverPropale($ATMdb, $object);
				}
				$action = 'modif'; // On provoque le repassage-en brouillon

				return 0; // Do standard code
			}
		}

		if($_REQUEST['action'] == 'delete'){

			global $db;

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."propale_history";
			$sql.= " WHERE fk_propale = ".$_REQUEST['id'];

			$resql = $db->query($sql);

		}

		if(isset($_REQUEST['actionATM'])) {
			$actionATM = $_REQUEST['actionATM'];
		} else {
			$actionATM = '';
		}

		if($actionATM == 'viewVersion') {

			$version = new TPropaleHist;
			$version->load($ATMdb, $_REQUEST['idVersion']);

			$propal = $version->getObject();
			//pre($propal,true);

			$object = new PropalHist($db, $object->socid);
			foreach($propal as $k=>$v) $object->{$k} = $v;

			foreach($object->lines as &$line) {
				$line->description  = $line->desc;
				$line->db =  $db;
				//$line->fetch_optionals();
			}

			//pre($object,true);
			$object->id = $_REQUEST['id'];
			$object->db = $db;
		} elseif($actionATM == 'createVersion') {

			TPropaleHist::archiverPropale($ATMdb, $object);

		} elseif($actionATM == 'restaurer') {

			TPropaleHist::restaurerPropale($ATMdb, $object);

		} elseif($actionATM == 'supprimer') {

			$version = new TPropaleHist;
			$version->load($ATMdb, $_REQUEST['idVersion']);
			$version->delete($ATMdb);

			?>
				<script language="javascript">
					document.location.href="<?php echo $_SERVER['PHP_SELF'] ?>?id=<?php echo $_REQUEST['id']?>&mesg=<?php echo $langs->transnoentities('HistoryVersionSuccessfullDelete') ?>";
				</script>
			<?php

            /* TODO J'ai essayé de rajouter un exit ici, ce qui serait complètement logique, mais ça a tout cassé...
             * Visiblement, le module est conçu pour que le script continue de s'exécuter. Dont acte, mais entre ça, les
             * redirections en JS plutôt que via header(), et les messages de retour utilisateur passés en paramètre
             * lors de la redirection, on est dans une méthodologie bien dégueulasse, il y a donc du refaisage à
             * entreprendre à mon sens - MdLL, 07/04/2020
             */
		}

		return 0;
	}

	/**
	 * Enables modules that use $object->ref to build a file path to get the original ref (without the trailing /[DIGITS])
	 *
	 * @param array        $parameters
	 * @param CommonObject $object  The object that holds the ref which PropaleHistory has modified
	 * @param string       $action
	 * @param HookManager  $hookmanager
	 * @return int
	 */
	function overrideRefForFileName($parameters, &$object, &$action, $hookmanager) {
		if (!isset($object->context['propale_history']['original_ref'])) {
			// the specified proposal doesn't have any history entries in llx_propale_history so we don't override ref
			return 0;
		} else {
			// override default
			$this->resprints = $object->context['propale_history']['original_ref'];
			return 1;
		}
	}
}
