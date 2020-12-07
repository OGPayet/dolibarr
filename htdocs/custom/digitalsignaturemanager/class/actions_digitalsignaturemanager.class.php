<?php
/* Copyright (C) 2020 Alexis LAURIER
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    digitalsignaturemanager/class/actions_digitalsignaturemanager.class.php
 * \ingroup digitalsignaturemanager
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

 dol_include_once('/digitalsignaturemanager/class/extendedEcm.class.php');
/**
 * Class ActionsDigitalSignatureManager
 */
class ActionsDigitalSignatureManager
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
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
	 * @var FormDigitalSignatureRequestTemplate;
	 */
	public $formDigitalSignatureRequestTemplate;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		dol_include_once("/digitalsignaturemanager/class/html.formdigitalsignaturerequesttemplate.class.php");
		$this->formDigitalSignatureRequestTemplate = new FormDigitalSignatureRequestTemplate($this->db);
	}

	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$pdfhandler     PDF builder handler
	 * @param   string	$action         'add', 'update', 'view'
	 * @return  int 		            <0 if KO,
	 *                                  =0 if OK but we want to process standard actions too,
	 *                                  >0 if OK and we want to replace standard actions.
	 */
	public function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
		$maskName = $pdfhandler->name;
		$fileFullPath = $parameters['file'];
		$this->updateMaskNameInEcm($fileFullPath, $maskName);
		return 0;
	}


	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$pdfhandler     PDF builder handler
	 * @param   string	$action         'add', 'update', 'view'
	 * @return  int 		            <0 if KO,
	 *                                  =0 if OK but we want to process standard actions too,
	 *                                  >0 if OK and we want to replace standard actions.
	 */
	public function afterODTCreation($parameters, &$pdfhandler, &$action)
	{
		$fileFullPath = $parameters['file'];
		$object = $parameters['object'];
		$objectRef = $object->ref;
		$fileName = basename($fileFullPath);
		$fileFormat=substr($fileName, strrpos($fileName, '.')+1);
		$startStringToRemoveOnFileName = $objectRef . '_';
		$endStringToRemoveOnFileName = '.' . $fileFormat;
		$subtring_start = strpos($fileName, $startStringToRemoveOnFileName);
		//Adding the strating index of the strating word to
		//its length would give its ending index
		$subtring_start += strlen($startStringToRemoveOnFileName);
		//Length of our required sub string
		$size = strpos($fileName, $endStringToRemoveOnFileName, $subtring_start) - $subtring_start;
		// Return the substring from the index substring_start of length size
		$maskName = substr($fileName, $subtring_start, $size);
		$this->updateMaskNameInEcm($fileFullPath, $maskName);
		return 0;
	}

	/**
	 * Overloading the restrictedArea function : check permission on an object
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int 		      			  	<0 if KO,
	 *                          				=0 if OK but we want to process standard actions too,
	 *  	                            		>0 if OK and we want to replace standard actions.
	 */
	public function restrictedArea($parameters, &$action, $hookmanager)
	{
		global $user;

		if ($parameters['features'] == 'myobject') {
			if ($user->rights->digitalsignaturemanager->myobject->read) {
				$this->results['result'] = 1;
				return 1;
			} else {
				$this->results['result'] = 0;
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Function to update or create ecm instance for a file and set maskName of it
	 * @param string $fileFullPath full path of the generated file
	 * @param string $maskName mask name used for the file to be generated
	 * @return void
	 */
	public function updateMaskNameInEcm($fileFullPath, $maskName)
	{
		global $user, $conf;

		if (!empty($fileFullPath))
		{
			$destfull = $fileFullPath;
			$upload_dir = dirname($destfull);
			$destfile = basename($destfull);
			$rel_dir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $upload_dir);

			if (!preg_match('/[\\/]temp[\\/]|[\\/]thumbs|\.meta$/', $rel_dir))     // If not a tmp dir
						{
				$filename = basename($destfile);
				$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
				$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

				$ecmfile = new ExtendedEcm($this->db);
				$result = $ecmfile->fetch(0, '', ($rel_dir ? $rel_dir.'/' : '').$filename);


				if ($result > 0)
				 {
					$ecmfile->label = md5_file(dol_osencode($destfull)); // hash of file content
					$ecmfile->fullpath_orig = '';
					$ecmfile->gen_or_uploaded = 'generated';
					$ecmfile->description = ''; // indexed content
					$ecmfile->keyword = ''; // keyword content
					$ecmfile->mask = $maskName;
					$result = $ecmfile->update($user);
					if ($result < 0) {
						setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
					}
				} else {
					$ecmfile->entity = $conf->entity;
					$ecmfile->filepath = $rel_dir;
					$ecmfile->filename = $filename;
					$ecmfile->label = md5_file(dol_osencode($destfull)); // hash of file content
					$ecmfile->fullpath_orig = '';
					$ecmfile->gen_or_uploaded = 'generated';
					$ecmfile->description = ''; // indexed content
					$ecmfile->keyword = ''; // keyword content
					$ecmfile->src_object_type = $this->table_element;
					$ecmfile->src_object_id   = $this->id;
					$ecmfile->mask = $maskName;

					$result = $ecmfile->create($user);
					if ($result < 0) {
						setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
					}
				}
			}
		}
	}

    /**
     * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		$contexts = explode(':', $parameters['context']);
		if(in_array('propalcard', $contexts) && $object->statut == $object::STATUS_VALIDATED) {
			print $this->formDigitalSignatureRequestTemplate->getCreateFromObjectButton($object->id);
		}
		return 0;
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
		$contexts = explode(':', $parameters['context']);
		if(in_array('propalcard', $contexts)) {
			$selectFiles = $this->formDigitalSignatureRequestTemplate->manageCreateFromAction($action, $object);
			$selectSigners = $this->formDigitalSignatureRequestTemplate->manageCreateFromSelectedFiles($action, $object);
			if($selectFiles) {
				$result = $selectFiles;
			} elseif($selectSigners) {
				$result = $selectSigners;
			}
			if($result) {
				$this->resprints = $result;
				return 1;
			}
		}
		return 0;
	}
}
