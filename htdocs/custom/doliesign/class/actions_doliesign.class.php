<?php
/* Copyright (C) 2018 		Netlogic			<info@netlogic.fr>
 * Copyright (C) 2018      Alexis LAURIER             <alexis@alexislaurier.fr>
 * Copyright (C) 2018      Synergies-Tech             <infra@synergies-france.fr>
 *
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
 * \file    doliesign/class/actions_doliesign.class.php
 * \ingroup doliesign
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsDoliEsign
 */
class ActionsDoliEsign
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
     */
    public $error = '';
    /**
     * @var array Errors
     */
    public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
	    $this->db = $db;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$errors = array(); // error mesgs

		if (in_array($parameters['currentcontext'], array('propalcard','interventioncard','ordercard', 'contractcard')))		// do something only for the context 'somecontext1' or 'somecontext2'
		{
			if (!empty($object->id) && !empty($object->element)) {
				dol_include_once('/doliesign/lib/doliesign.lib.php');
				if (! empty($conf->global->DOLIESIGN_ENVIRONMENT)) $environment = $conf->global->DOLIESIGN_ENVIRONMENT;
				if ($environment == 'yousign-staging-api' || $environment == 'yousign-api') {
					dol_include_once('/doliesign/class/yousignrest.class.php');
					$doliEsign = new YousignRest($this->db);
				} elseif ($environment == 'universign-prod' || $environment == 'universign-demo'){
					dol_include_once('/doliesign/class/universign.class.php');
					$doliEsign = new Universign($this->db);
				} else {
					dol_include_once('/doliesign/class/yousignsoap.class.php');
					$doliEsign = new YousignSoap($this->db);
				}

				if ($action == 'confirm_doliesign') {
					$authmode = GETPOST('authmode','alpha');
					if ($parameters['currentcontext'] == 'propalcard')	    // sign propals
					{
						$filename=dol_sanitizeFileName($object->ref);
						$dir = $conf->propal->dir_output . "/" . $filename;
						$res = $doliEsign->signInit($user, $object, $dir, 'doliesign_init_propal', 'doliesign_end_propal', $authmode);
					} elseif ($parameters['currentcontext'] == 'interventioncard')	    // sign intervention
					{
						$filename=dol_sanitizeFileName($object->ref);
						$dir = $conf->ficheinter->dir_output . "/" . $filename;
						$res = $doliEsign->signInit($user, $object, $dir, 'doliesign_init_fichinter', 'doliesign_end_fichinter', $authmode);
					}
					elseif ($parameters['currentcontext'] == 'ordercard')  // sign order
					{
						$filename=dol_sanitizeFileName($object->ref);
						$dir = $conf->commande->dir_output . "/" . $filename;
						$res = $doliEsign->signInit($user, $object, $dir, 'doliesign_init_commande', 'doliesign_end_commande', $authmode);
					}
					elseif ($parameters['currentcontext'] == 'contractcard')
					{
						$filename=dol_sanitizeFileName($object->ref);
						$dir = $conf->contrat->dir_output . "/" . $filename;
						$res = $doliEsign->signInit($user, $object, $dir, 'doliesign_init_contrat', 'doliesign_end_contrat', $authmode);
					}
					if ($res == 0) {
						setEventMessages("SignRequestSuccessful",null, 'mesgs');
					} else {
						$errors = $doliEsign->errors;
						$error--;
					}
				} elseif ($action == 'confirm_doliesignfetch') {
					$res = $doliEsign->signFetch($user, $object);
					if ($res < 0) {
						$errors=$doliEsign->errors;
						$error--;
					} else {
						setEventMessages($langs->trans('DoliEsignDocumentFetched'), null , 'mesgs');
					}
				} elseif ($action == 'doliesignsync') {
					$res = $doliEsign->signInfo($user, $object, 'sync');
					if ($res < 0) {
						$errors=$doliEsign->errors;
						$error--;
					} else {
						$signStatus = $res;
						// for updating status in view
						$object->fetch($object->id);
						if ($signStatus == DoliEsign::STATUS_WAITING) {
							setEventMessages('DoliEsign: ' . $langs->trans('WaitingDoliEsign'), null , 'mesgs');
						} elseif ($signStatus == DoliEsign::STATUS_SIGNED) {
							setEventMessages('DoliEsign: ' . $langs->trans('SignedDoliEsign'), null , 'mesgs');
							$this->resprints = '<td>'.$langs->trans('SignedDoliEsign').'</td>';
							header("Location: ".$_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=confirm_doliesignfetch');
						} elseif ($signStatus == DoliEsign::STATUS_CANCELED) {
							setEventMessages('DoliEsign: ' . $langs->trans('CancelledDoliEsign'), null , 'mesgs');
							$this->resprints = '<td>'.$langs->trans('CancelledDoliEsign').'</td>';
						} elseif ($signStatus == DoliEsign::STATUS_ERROR) {
							setEventMessages('DoliEsign: ' . $langs->trans('ErrorDoliEsign'), null , 'mesgs');
							$this->resprints = '<td>'.$langs->trans('ErrorDoliEsign').'</td>';
						}
					}
				} elseif ($action == 'modif' ||
						$action == 'modify' ||
						$action == 'confirm_modify' ||
						$action == 'confirm_modif' ||
						$action == 'confirm_reopen') {
					$result = $doliEsign->fetch(null, null, $object->id, $object->element);
					if ($result > 0) {
						$signStatus = $doliEsign->status;
						if ($signStatus == DoliEsign::STATUS_SIGNED || $signStatus == DoliEsign::STATUS_FILE_FETCHED) {
							$errors="DoliEsignSignedNoModify";
							$error--;
						}
					}
				} elseif ($action == 'confirm_delete') {
					$result = $doliEsign->fetch(null, null, $object->id, $object->element);
					if ($result > 0) {
						$signStatus = $doliEsign->status;
						if ($signStatus == DoliEsign::STATUS_SIGNED) {
							$errors="DoliEsignSignedNoDelete";
							$error--;
						}
					}
				} else if ($object->element == 'propal' && $object->statut == Propal::STATUS_VALIDATED) {
					// make sure doliesign status and propal status are aligned
					$result = $doliEsign->fetch(null, null, $object->id, $object->element);
					if ($result > 0) {
						$signStatus = $doliEsign->status;
						if ($signStatus == DoliEsign::STATUS_SIGNED) {
							$object->cloture($user, Propal::STATUS_SIGNED, $langs->trans('SignedByDoliEsign'));
						}
					}
				}
			}
		}

		if (! $error) {
			return 0;                                    // or return 1 to replace standard code
		} else {
			$this->errors = $errors;
			return -1;
		}
	}

	/**
	 * Overloading the formConfirm function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function formConfirm($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs;

		$error = 0; // Error counter
		$form = new Form($object->db);
		if ($action == 'doliesign') {
			if (isset($conf->global->DOLIESIGN_ENVIRONMENT) && ($conf->global->DOLIESIGN_ENVIRONMENT == "yousign-demo" || $conf->global->DOLIESIGN_ENVIRONMENT == "yousign-prod")) {
				$environment="yousign-demo";
				$authMode="sms";
				if (! empty($conf->global->DOLIESIGN_ENVIRONMENT)) $environment = $conf->global->DOLIESIGN_ENVIRONMENT;
				if ($environment == 'yousign-prod') {
					if (! empty($conf->global->DOLIESIGN_AUTHENTICATION_MODE_PROD)) $authMode=$conf->global->DOLIESIGN_AUTHENTICATION_MODE_PROD;
				} else if ($environment == 'yousign-demo') {
					if (! empty($conf->global->DOLIESIGN_AUTHENTICATION_MODE)) $authMode=$conf->global->DOLIESIGN_AUTHENTICATION_MODE;
				} else {
					$authMode="";
				}
				if (!empty($authMode)) {
					$formquestion = [
						[
							'type' => 'select',
							'label' => $langs->trans('AuthentificationMode'),
							'name' => 'authmode',
							'values' => [
								'sms' => $langs->trans('AuthentificationSms'),
								'mail' => $langs->trans('AuthentificationEmail')],
							'default' => $authMode
						]
					];
				} else {
					$formquestion = '';
				}
			} else if ($conf->global->DOLIESIGN_ENVIRONMENT == "universign-prod" || $conf->global->DOLIESIGN_ENVIRONMENT == "universign-demo"){
				$redirection = "false";
				$formquestion = [
					[
						'type' => 'select',
						'label' => $langs->trans('RedirectionMode'),
						'name' => 'authmode',
						'values' => [
							'false' => $langs->trans('RedirectionFalse'),
							'true' => $langs->trans('RedirectionTrue')],
						'default' => $redirection
					]
				];
			}


			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DoliEsign'), $langs->trans('ConfirmDoliEsign', $object->ref), 'confirm_doliesign', $formquestion, 0, 1);
		} elseif ($action == 'doliesignfetch') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DoliEsign'), $langs->trans('ConfirmDoliEsignFetch', $object->ref), 'confirm_doliesignfetch', '', 0, 1);
		}



		if (! $error) {
            print $formconfirm;
			return 0;                                    // or return 1 to replace standard code
		} else {
			$this->errors[] = $errors;
			return -1;
		}
	}

	/**
	 * Overloading the emailElementlist function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function emailElementlist($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

	    $error = 0; // Error counter

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
	    if (in_array($parameters['currentcontext'], array('emailtemplates','somecontext2')))		// do something only for the context 'somecontext1' or 'somecontext2'
	    {
			if ($user->rights->doliesign->create) {
				$this->results['doliesign_init_propal'] = $langs->trans('DoliEsignInitPropalTemplate');
				$this->results['doliesign_end_propal'] = $langs->trans('DoliEsignEndPropalTemplate');
				$this->results['doliesign_init_commande'] = $langs->trans('DoliEsignInitCommandeTemplate');
				$this->results['doliesign_end_commande'] = $langs->trans('DoliEsignEndCommandeTemplate');
				$this->results['doliesign_init_fichinter'] = $langs->trans('DoliEsignInitInterventionTemplate');
				$this->results['doliesign_end_fichinter'] = $langs->trans('DoliEsignEndInterventionTemplate');
				$this->results['doliesign_init_contrat'] = $langs->trans('DoliEsignInitContratTemplate');
				$this->results['doliesign_end_contrat'] = $langs->trans('DoliEsignEndContratTemplate');
			} else {
				$this->resArray = array();
			}
	    }

	    if (! $error) {
	        return 0;                                    // or return 1 to replace standard code
	    } else {
	        $this->errors[] = 'Error message';
	        return -1;
	    }
	}

	/**
	 * Overloading the printFieldListOption function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListOption($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

	    $error = 0; // Error counter

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
	    if (in_array($parameters['currentcontext'], array('propallist','orderlist','interventionlist','contractlist')))		// do something only for the context 'somecontext1' or 'somecontext2'
	    {
			dol_include_once('/doliesign/class/config.class.php');
			$config = new DoliEsignConfig($this->db);
			if ($parameters['currentcontext'] == 'propallist' && count($config->fetchListId('propal')) > 0) $active = true;
			elseif ($parameters['currentcontext'] == 'orderlist' && count($config->fetchListId('commande')) > 0) $active = true;
			elseif ($parameters['currentcontext'] == 'interventionlist' && count($config->fetchListId('fichinter')) > 0) $active = true;
			elseif ($parameters['currentcontext'] == 'contractlist' && count($config->fetchListId('contrat')) > 0) $active = true;
			else $active = false;
			if ($active && $user->rights->doliesign->read) {
				$this->resprints = '<td class="liste_titre">'.''.'</td>';
			} else {
				$this->resprints = '';
			}
	    }

	    if (! $error) {
	        return 0;                                    // or return 1 to replace standard code
	    } else {
	        $this->errors[] = 'Error message';
	        return -1;
	    }
	}

	/**
	 * Overloading the printFieldListTitle function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListTitle($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

	    $error = 0; // Error counter

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
	    if (in_array($parameters['currentcontext'], array('propallist','orderlist','interventionlist','contractlist')))		// do something only for the context 'somecontext1' or 'somecontext2'
	    {
			dol_include_once('/doliesign/class/config.class.php');
			$config = new DoliEsignConfig($this->db);
			if ($parameters['currentcontext'] == 'propallist' && count($config->fetchListId('propal')) > 0) $active = true;
			elseif ($parameters['currentcontext'] == 'orderlist' && count($config->fetchListId('commande')) > 0) $active = true;
			elseif ($parameters['currentcontext'] == 'interventionlist' && count($config->fetchListId('fichinter')) > 0) $active = true;
			elseif ($parameters['currentcontext'] == 'contractlist' && count($config->fetchListId('contrat')) > 0) $active = true;
			else $active = false;
			if ($active && $user->rights->doliesign->read) {
				$this->resprints = '<th class="liste_titre">'.$langs->trans('DoliEsignStatus').'</th>';
			} else {
				$this->resprints = '';
			}
	    }

	    if (! $error) {
	        return 0;                                    // or return 1 to replace standard code
	    } else {
	        $this->errors[] = 'Error message';
	        return -1;
	    }
	}

	/**
	 * Overloading the printFieldListValue function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListValue($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;



		$error = 0; // Error counter
		$errors = array();
		$signStatus = null;
		$dolObject = new stdClass;
		$dolObject->db = $this->db;

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
	    if (in_array($parameters['currentcontext'], array('propallist','orderlist','interventionlist','contractlist')))		// do something only for the context 'somecontext1' or 'somecontext2'
	    {
			dol_include_once('/doliesign/lib/doliesign.lib.php');
			dol_include_once('/doliesign/class/config.class.php');
			$config = new DoliEsignConfig($this->db);
			if (! empty($conf->global->DOLIESIGN_ENVIRONMENT)) $environment = $conf->global->DOLIESIGN_ENVIRONMENT;
			if ($environment == 'yousign-staging-api' || $environment == 'yousign-api') {
				dol_include_once('/doliesign/class/yousignrest.class.php');
				$doliEsign = new YousignRest($this->db);
			} elseif ($environment == 'universign-prod' || $environment == 'universign-demo'){
				dol_include_once('/doliesign/class/universign.class.php');
				$doliEsign = new Universign($this->db);
			} else {
				dol_include_once('/doliesign/class/yousignsoap.class.php');
				$doliEsign = new YousignSoap($this->db);
			}
			if ($parameters['currentcontext'] == 'propallist' && count($config->fetchListId('propal')) > 0) $dolObject->element = 'propal';
			elseif ($parameters['currentcontext'] == 'orderlist' && count($config->fetchListId('commande')) > 0)$dolObject->element = 'commande';
			elseif ($parameters['currentcontext'] == 'interventionlist' && count($config->fetchListId('fichinter')) > 0) $dolObject->element = 'fichinter';
			elseif ($parameters['currentcontext'] == 'contractlist' && count($config->fetchListId('contrat')) > 0) $dolObject->element = 'contrat';
			else $dolObject->element = '';
			$this->resprints = '';
			$dolObject->id = $parameters['obj']->rowid;
			if ($user->rights->doliesign->read && !empty($dolObject->id) && !empty($dolObject->element)) {
				$res = $doliEsign->signInfo($user, $dolObject, 'sync');
				if ($res < 0) {
					$this->errors[]=$doliEsign->errors;
					$error = $res;
					$signStatus =  DoliEsign::STATUS_ERROR;
				} else {
					$signStatus = $res;
				}
				if (! isset($signStatus)) {
					$this->resprints = '<td>'.$langs->trans('NoDoliEsign').'</td>';
				} elseif ($signStatus == DoliEsign::STATUS_WAITING) {
					$this->resprints = '<td>'.$langs->trans('WaitingDoliEsign').'</td>';
				} elseif ($signStatus == DoliEsign::STATUS_SIGNED) {
					$this->resprints = '<td>'.$langs->trans('SignedDoliEsign').'</td>';
				} elseif ($signStatus == DoliEsign::STATUS_CANCELED) {
					$this->resprints = '<td>'.$langs->trans('CancelledDoliEsign').'</td>';
				} elseif ($signStatus == DoliEsign::STATUS_ERROR) {
					$this->resprints = '<td>'.$langs->trans('ErrorDoliEsign').'</td>';
				} elseif ($signStatus == DoliEsign::STATUS_FILE_FETCHED) {
					$this->resprints = '<td>'.$langs->trans('SignedDoliEsign').' '.img_picto('file', 'file').'</td>';
				}
			}
	    }

	    if (! $error) {
	        return 0;                                    // or return 1 to replace standard code
	    } else {
	        $this->errors[] = 'Error message';
	        return -1;
	    }
	}


	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
	    global $conf, $user, $langs;

		$error = 0; // Error counter

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
	    if (in_array($parameters['currentcontext'], array('propalcard','ordercard','interventioncard','contractcard')))		// do something only for the context 'somecontext1' or 'somecontext2'
	    {
			dol_include_once('/doliesign/lib/doliesign.lib.php');
			dol_include_once('/doliesign/class/config.class.php');
			$config = new DoliEsignConfig($this->db);
			$doliEsign = new DoliEsign($this->db);
			if ($parameters['currentcontext'] == 'propalcard' && count($config->fetchListId('propal')) > 0) {
				$active = true;
				$minStatus = Propal::STATUS_VALIDATED;
				// OpenDSI & Alexis LAURIER
				// We add Propal::STATUS_AWAIT = 5 which is between Propal::STATUS_DRAFT = 0 && Propal::STATUS_VALIDATED = 1
				// So proposals are draft is status is Propal::STATUS_DRAFT or Propal::STATUS_AWAIT
				if($object->statut == Propal::STATUS_AWAIT) $active = false;
				$maxStatus = Propal::STATUS_SIGNED;
			}
			elseif ($parameters['currentcontext'] == 'ordercard' && count($config->fetchListId('commande')) > 0) {
				$active = true;
				$minStatus = Commande::STATUS_VALIDATED;
				$maxStatus = Commande::STATUS_SHIPMENTONPROCESS;
			}
			elseif ($parameters['currentcontext'] == 'interventioncard' && count($config->fetchListId('fichinter')) > 0) {
				$active = true;
				if (DoliEsign::checkDolVersion('7.0')) {
					$minStatus = FichInter::STATUS_VALIDATED;
					$maxStatus = FichInter::STATUS_VALIDATED;
				} else {
					$minStatus = 1;
					$maxStatus = 1;
				}
			}
			elseif ($parameters['currentcontext'] == 'contractcard' && count($config->fetchListId('contrat')) > 0) {
				$active = true;
				$minStatus = 1;
				$maxStatus = 1;
			}
			else $active = false;
			if ($active && $object->statut >= $minStatus) {
				if (!empty($object->id)) {
					$result = $doliEsign->fetch(null, null, $object->id, $object->element);
					if ($result > 0) {
						$signStatus = $doliEsign->status;
						if ($signStatus == DoliEsign::STATUS_WAITING && $object->statut <= $maxStatus) {
							if ($user->rights->doliesign->read) {
								print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=doliesignsync">' . $langs->trans('DoliEsignSync') . '</a></div>';
							} else {
								print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('DoliEsignSync') . '</a></div>';
							}
						} elseif ($signStatus == DoliEsign::STATUS_SIGNED || $signStatus == DoliEsign::STATUS_FILE_FETCHED) {
							if ($user->rights->doliesign->read) {
								if ($parameters['currentcontext'] == 'contractcard') print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=confirm_doliesignfetch">' . $langs->trans('DoliEsignFetch') . '</a></div>';
								else print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=doliesignfetch">' . $langs->trans('DoliEsignFetch') . '</a></div>';
							} else {
								print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('DoliEsignFetch') . '</a></div>';
							}
						} elseif ($signStatus == DoliEsign::STATUS_CANCELED  && $object->statut <= $maxStatus) {
							if ($user->rights->doliesign->create) {
								if ($parameters['currentcontext'] == 'contractcard') print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=confirm_doliesign">' . $langs->trans('DoliEsignAgain') . '</a></div>';
								else print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=doliesign">' . $langs->trans('DoliEsignAgain') . '</a></div>';
							} else {
								print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('DoliEsign') . '</a></div>';
							}
						} elseif ($signStatus == DoliEsign::STATUS_ERROR) {
							print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('DoliEsignError') . '</a></div>';
						}
					} else {
						if ($user->rights->doliesign->create) {
							if ($parameters['currentcontext'] == 'contractcard') print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=confirm_doliesign">' . $langs->trans('DoliEsign') . '</a></div>';
							else print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=doliesign">' . $langs->trans('DoliEsign') . '</a></div>';
						} else {
							print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('DoliEsign') . '</a></div>';
						}
					}
				}
			}
	    }

	    if (! $error) {
	        return 0;                                    // or return 1 to replace standard code
	    } else {
	        $this->errors[] = 'Error message';
	        return -1;
	    }
	}

	/**
	 * Change the signature area
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int 							< 0 on error, 0 on success, 1 to replace standard code
	 */
	public function changeSignatureArea($parameters, &$object, &$action, $hookmanager)
	{
	    global $conf, $user, $langs;

		$error = 0; // Error counter

		$signCount = 0;

		//Récupération des différentes DoliEsignConfig
		$config = new DoliEsignConfig($object->db);
		$configIds = array_reverse($config->fetchListId($object->element));
		$typeContacts = $config->get_type_contact_code($object->element, $parameters['sourceContact']);
		$sourceContacts = $config->get_source_contact_code($object->element);

		//Parcourt les ID de configuration
		foreach($configIds as $configId) {
			//Récupére les configurations disponible
			$res = $config->fetch($configId);

			//Si le nombre de configuration est en dessous de 0 retourne une erreur
			if ($res < 0) {
				return --$error;
			}

			//Si il est égal à 0 il n'y a aucune configurations
			if ($res == 0) {
				return --$error;
			}

			//Récupère les informations du contact et de l'utilisateur
			$contactCode = $typeContacts[$config->fk_c_type_contact];
			$contactSource = $sourceContacts[$config->fk_c_type_contact];
			if ($object->getIdContact($contactSource, $contactCode)) {
				$signCount += count($object->getIdContact($contactSource, $contactCode));
			}
		}

		$height = $parameters['tab'] * 3;

		for ($i=0; $i < $signCount; $i++) {
			$parameters['pdf']->addEmptySignatureAppearance($parameters['posx'], $parameters['posy'] + $parameters['tab'], $parameters['largcol'], $height, -1, 'sign_'.$parameters['sourceContact']);
			$parameters['pdf']->SetXY($parameters['posx'], $parameters['posy'] + $parameters['tab']);
			$parameters['pdf']->MultiCell($parameters['largcol'], $height, '', 1, 'R');
			$parameters['posy'] += $height + 4;
		}

		return $i;
	}
}
