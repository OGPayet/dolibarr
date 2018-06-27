<?php
/* Copyright (C) 2018 		Netlogic			<info@netlogic.fr>
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
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
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

		if (in_array($parameters['currentcontext'], array('propalcard','interventioncard')))		// do something only for the context 'somecontext1' or 'somecontext2'
		{
			if (!empty($object->id) && !empty($object->element)) {
				dol_include_once('/doliesign/lib/doliesign.lib.php');
				if ($action == 'confirm_doliesign') {
					$authmode = GETPOST('authmode','alpha');
					if ($parameters['currentcontext'] == 'propalcard')	    // sign propals
					{
						$filename=dol_sanitizeFileName($object->ref);
						$dir = $conf->propal->dir_output . "/" . $filename;
						$errors = ysInit($user, $object, $dir, 'doliesign_init_propal', 'doliesign_end_propal', $authmode);
					} elseif ($parameters['currentcontext'] == 'interventioncard')	    // sign intervention
					{
						$filename=dol_sanitizeFileName($object->ref);
						$dir = $conf->ficheinter->dir_output . "/" . $filename;
						$errors = ysInit($user, $object, $dir, 'doliesign_init_fichinter', 'doliesign_end_fichinter', $authmode);
					}
					elseif ($parameters['currentcontext'] == 'somecontext2')  // sign something
					{

					}
					if ($errors == 0) {
						setEventMessages("SignRequestSuccessful",null, 'mesgs');
					} else {
						$error--;
					}
				} elseif ($action == 'confirm_doliesignfetch') {
					$ysResult = ysFetch($user, $object);
					if (is_array($ysResult)) {
						$errors[]=$ysResult;
						$error--;
					} elseif ($ysResult > 0) {
						setEventMessages($langs->trans('DoliEsignDocumentFetched'), null , 'mesgs');
					}
				} elseif ($action == 'doliesignsync') {
					$errors = ysInfo($user, $object, 'sync');
					if (is_array($ysResult)) {
						$errors[]=$ysResult;
						$error--;
					} else {
						// for updating status in view
						$object->fetch($object->id);
					}
				} elseif ($action == 'modif' || $action == 'modify'|| $action == 'confirm_modify' || $action == 'confirm_reopen') {
					$doliEsign = new DoliEsign($this->db);
					$result = $doliEsign->fetch(null, null, $object->id, $object->element);
					if ($result > 0) {
						$signStatus = $doliEsign->status;
						if ($signStatus == DoliEsign::STATUS_SIGNED) {
							$errors[]="DoliEsignSignedNoModify";
							$error--;
						}
					}
				} elseif ($action == 'confirm_delete') {
					$doliEsign = new DoliEsign($this->db);
					$result = $doliEsign->fetch(null, null, $object->id, $object->element);
					if ($result > 0) {
						$signStatus = $doliEsign->status;
						if ($signStatus == DoliEsign::STATUS_SIGNED) {
							$errors[]="DoliEsignSignedNoDelete";
							$error--;
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
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
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
			$authMode="sms";
			if (! empty($conf->global->DOLIESIGN_AUTHENTICATION_MODE)) $authMode=$conf->global->DOLIESIGN_AUTHENTICATION_MODE;
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
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DoliEsign'), $langs->trans('ConfirmDoliEsign', $object->ref), 'confirm_doliesign', $formquestion, 0, 1);
		} elseif ($action == 'doliesignfetch') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DoliEsign'), $langs->trans('ConfirmDoliEsignFetch', $object->ref), 'confirm_doliesignfetch', '', 0, 1);
		}



		if (! $error) {
			$this->resprints = $formconfirm;
			return 0;                                    // or return 1 to replace standard code
		} else {
			$this->errors[] = $errors;
			return -1;
		}
	}

	/**
	 * Overloading the emailElementlist function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
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
				$this->results['doliesign_init_fichinter'] = $langs->trans('DoliEsignInitInterventionTemplate');
				$this->results['doliesign_end_fichinter'] = $langs->trans('DoliEsignEndInterventionTemplate');
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
	 * Overloading the printFieldListFooter function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code

	public function printFieldListFooter($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

	    $error = 0; // Error counter

	    if (in_array($parameters['currentcontext'], array('propallist','somecontext2')))		// do something only for the context 'somecontext1' or 'somecontext2'
	    {
			if ($user->rights->doliesign->read) {
				$this->resprints = '<tr><td><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=doliesignsync">' . $langs->trans('DoliEsignSync') . '</a></td></tr>';
			} else {
				$this->resprints = '<tr><td><a class="butActionRefused" href="#">' . $langs->trans('DoliEsignSync') . '</a></td></tr>';
			}
	    }

	    if (! $error) {
	        return 0;                                    // or return 1 to replace standard code
	    } else {
	        $this->errors[] = 'Error message';
	        return -1;
	    }
	}*/


	/**
	 * Overloading the printFieldListOption function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
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
	    if (in_array($parameters['currentcontext'], array('propallist','interventionlist')))		// do something only for the context 'somecontext1' or 'somecontext2'
	    {
			dol_include_once('/doliesign/class/config.class.php');
			$config = new DoliEsignConfig($this->db);
			if ($parameters['currentcontext'] == 'propallist' && count($config->fetchListId('propal')) > 0) $active = true;
			elseif ($parameters['currentcontext'] == 'interventionlist' && count($config->fetchListId('fichinter')) > 0) $active = true;
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
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
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
	    if (in_array($parameters['currentcontext'], array('propallist','interventionlist')))		// do something only for the context 'somecontext1' or 'somecontext2'
	    {
			dol_include_once('/doliesign/class/config.class.php');
			$config = new DoliEsignConfig($this->db);
			if ($parameters['currentcontext'] == 'propallist' && count($config->fetchListId('propal')) > 0) $active = true;
			elseif ($parameters['currentcontext'] == 'interventionlist' && count($config->fetchListId('fichinter')) > 0) $active = true;
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
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
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
	    if (in_array($parameters['currentcontext'], array('propallist','interventionlist')))		// do something only for the context 'somecontext1' or 'somecontext2'
	    {
			dol_include_once('/doliesign/lib/doliesign.lib.php');
			dol_include_once('/doliesign/class/config.class.php');
			$config = new DoliEsignConfig($this->db);
			if ($parameters['currentcontext'] == 'propallist' && count($config->fetchListId('propal')) > 0) $dolObject->element = 'propal';
			elseif ($parameters['currentcontext'] == 'interventionlist' && count($config->fetchListId('fichinter')) > 0) $dolObject->element = 'fichinter';
			else $dolObject->element = '';
			$this->resprints = '';
			$dolObject->id = $parameters['obj']->rowid;
			if ($user->rights->doliesign->read && !empty($dolObject->id) && !empty($dolObject->element)) {
				$ysResult = ysInfo($user, $dolObject, 'sync');
				if (is_array($ysResult)) {
					$this->errors[]=$ysResult;
					$signStatus =  DoliEsign::STATUS_ERROR;
				} else {
					$signStatus = $ysResult;
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
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
	    global $conf, $user, $langs;

	    $error = 0; // Error counter

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
	    if (in_array($parameters['currentcontext'], array('somecontext1','somecontext2')))		// do something only for the context 'somecontext1' or 'somecontext2'
	    {
	        foreach($parameters['toselect'] as $objectid)
	        {
	            // Do action on each object id

	        }
	    }

	    if (! $error) {
	        $this->results = array('myreturn' => 999);
	        $this->resprints = 'A text to show';
	        return 0;                                    // or return 1 to replace standard code
	    } else {
	        $this->errors[] = 'Error message';
	        return -1;
	    }
	}


	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
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
	    if (in_array($parameters['currentcontext'], array('propalcard','interventioncard')))		// do something only for the context 'somecontext1' or 'somecontext2'
	    {
			dol_include_once('/doliesign/lib/doliesign.lib.php');
			dol_include_once('/doliesign/class/config.class.php');
			$config = new DoliEsignConfig($this->db);
			if ($parameters['currentcontext'] == 'propalcard' && count($config->fetchListId('propal')) > 0) {
				$active = true;
				$minStatus = Propal::STATUS_VALIDATED;
				$maxStatus = Propal::STATUS_SIGNED;
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
			else $active = false;
			if ($active && $object->statut >= $minStatus) {
				if (!empty($object->id)) {
					$doliEsign = new DoliEsign($this->db);
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
								print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=doliesignfetch">' . $langs->trans('DoliEsignFetch') . '</a></div>';
							} else {
								print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('DoliEsignFetch') . '</a></div>';
							}
						} elseif ($signStatus == DoliEsign::STATUS_CANCELED  && $object->statut <= $maxStatus) {
							if ($user->rights->doliesign->create) {
								print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=doliesign">' . $langs->trans('DoliEsignAgain') . '</a></div>';
							} else {
								print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('DoliEsign') . '</a></div>';
							}
						} elseif ($signStatus == DoliEsign::STATUS_ERROR) {
							print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('DoliEsignError') . '</a></div>';
						}
					} else {
						if ($user->rights->doliesign->create) {
							print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=doliesign">' . $langs->trans('DoliEsign') . '</a></div>';
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
	 * Execute action
	 *
	 * @param	array	$parameters		Array of parameters
	 * @param   Object	$object		   	Object output on PDF
	 * @param   string	$action     	'add', 'update', 'view'
	 * @return  int 		        	<0 if KO,
	 *                          		=0 if OK but we want to process standard actions too,
	 *  	                            >0 if OK and we want to replace standard actions.
	 */
	function beforePDFCreation($parameters, &$object, &$action)
	{
		global $langs,$conf;
		global $hookmanager;

		$outputlangs=$langs;

		$ret=0; $deltemp=array();
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1','somecontext2')))		// do something only for the context 'somecontext1' or 'somecontext2'
		{

		}

		return $ret;
	}

	/**
	 * Execute action
	 *
	 * @param	array	$parameters		Array of parameters
	 * @param   Object	$pdfhandler   	PDF builder handler
	 * @param   string	$action     	'add', 'update', 'view'
	 * @return  int 		        	<0 if KO,
	 *                          		=0 if OK but we want to process standard actions too,
	 *  	                            >0 if OK and we want to replace standard actions.
	 */
	function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
		global $langs,$conf;
		global $hookmanager;

		$outputlangs=$langs;

		$ret=0; $deltemp=array();
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1','somecontext2')))		// do something only for the context 'somecontext1' or 'somecontext2'
		{

		}

		return $ret;
	}

	/* Add here any other hooked methods... */

}
