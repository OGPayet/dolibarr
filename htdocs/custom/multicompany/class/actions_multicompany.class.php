<?php
/* Copyright (C) 2009-2017 Regis Houssin  <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Herve Prot     <herve.prot@symeos.com>
 * Copyright (C) 2014      Philippe Grand <philippe.grand@atoo-net.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/multicompany/actions_multicompany.class.php
 *	\ingroup    multicompany
 *	\brief      File Class multicompany
 */

require 'dao_multicompany.class.php';
dol_include_once('/multicompany/lib/multicompany.lib.php');


/**
 *	\class      ActionsMulticompany
 *	\brief      Class Actions of the module multicompany
 */
class ActionsMulticompany
{
	/** @var DoliDB */
	var $db;
	/** @var DaoMulticompany */
	var $dao;

	var $mesg;
	var $error;
	var $errors=array();
	//! Numero de l'erreur
	var $errno = 0;

	var $template_dir;
	var $template;

	var $label;
	var $description;

	var $referent;

	var $sharings=array();
	var $options=array();
	var $entities=array();
	var $dict=array();
	var $tpl=array();

	var $sharingelements=array();
	var $sharingdicts=array();

	private $config=array();

	// For Hookmanager return
	var $resprints;
	var $results=array();


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		$this->sharingelements = array(
				'thirdparty',
				'product',
				'productprice',
				'stock',
				'invoicenumber',
				'category',
				'agenda',
				'bankaccount',
				'expensereport',
				'project',
				'member',
				'equipement',
				'contract'
		);

		$this->sharingdicts = array(
				'c_paiement',
				'c_payment_term'
		);
	}

	/**
	 * Instantiation of DAO class
	 *
	 * @return	void
	 */
	private function getInstanceDao()
	{
		if (! is_object($this->dao))
		{
			$this->dao = new DaoMulticompany($this->db);
		}
	}

	/**
	 * 	Enter description here ...
	 *
	 * 	@param	string	$action		Action type
	 */
	function doActions(&$action='')
	{
		global $conf,$user,$langs;

		$this->getInstanceDao();

		$id=GETPOST('id','int');
		$label=GETPOST('label','alpha');
		$name=GETPOST('name','alpha');
		$description=GETPOST('description','alpha');
		$value=GETPOST('value','int');
		$cancel=GETPOST('cancel');

		if ($action == 'add' && empty($cancel) && $user->admin && ! $user->entity)
		{
			$error=0;

			if (empty($label))
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")), 'errors');
				$action = 'create';
			}
			else if (empty($name))
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("CompanyName")), 'errors');
				$action = 'create';
			}

			// Verify if label already exist in database
			if (! $error)
			{
				$this->dao->getEntities();
				if (! empty($this->dao->entities))
				{
					foreach($this->dao->entities as $entity)
					{
						if (strtolower($entity->label) == strtolower($label)) $error++;
					}
					if ($error)
					{
						setEventMessage($langs->trans("ErrorEntityLabelAlreadyExist"), 'errors');
						$action = 'create';
					}
				}
			}

			if (! $error)
		{
			$this->db->begin();

			$this->dao->label = $label;
			$this->dao->description = $description;

			$this->dao->options['referent']	= (GETPOST('referring_entity') ? GETPOST('referring_entity') : null);

			foreach ($this->sharingelements as $element)
			{
				$this->dao->options['sharings'][$element]	= (GETPOST($element) ? GETPOST($element) : null);
			}

				$extrafields = new ExtraFields($this->db);
				$extralabels = $extrafields->fetch_name_optionals_label($this->dao->table_element, true);
				$extrafields->setOptionalsFromPost($extralabels, $this->dao);

			$id = $this->dao->create($user);
			if ($id <= 0)
			{
				$error++;
				$errors=($this->dao->error ? array($this->dao->error) : $this->dao->errors);
				setEventMessage($errors, 'errors');
				$action = 'create';
			}

			if (! $error && $id > 0)
			{
				$country_id=GETPOST("country_id");
				$country_code=getCountry($country_id,2);
				$country_label=getCountry($country_id,0);

				dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_COUNTRY", $country_id.':'.$country_code.':'.$country_label,'chaine',0,'',$id);
				dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_NOM",$name,'chaine',0,'',$id);
				dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_ADDRESS",GETPOST("address"),'chaine',0,'',$id);
				dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_TOWN",GETPOST("town"),'chaine',0,'',$id);
				dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_ZIP",GETPOST("zipcode"),'chaine',0,'',$id);
				dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_STATE",GETPOST("departement_id"),'chaine',0,'',$id);
				dolibarr_set_const($this->db, "MAIN_MONNAIE",GETPOST("currency"),'chaine',0,'',$id);
				dolibarr_set_const($this->db, "MAIN_LANG_DEFAULT",GETPOST("main_lang_default"),'chaine',0,'',$id);

				$dir	= "/multicompany/sql/";

				// Load sql init_new_entity.sql file
				$file 	= 'init_new_entity_nocrypt.sql';
				if (! empty($conf->db->dolibarr_main_db_encryption) && ! empty($conf->db->dolibarr_main_db_cryptkey))
				{
					$file = 'init_new_entity.sql';
				}
				$fullpath = dol_buildpath($dir.$file);

				if (file_exists($fullpath))
				{
					$result=run_sql($fullpath,1,$id);
				}

				$dir	= "/multicompany/sql/dict/";

				foreach($this->sharingdicts as $dict)
				{
					// Load sql init_new_entity_dict.sql file
					$file 	= 'init_new_entity_'.$dict.'.sql';
					$fullpath = dol_buildpath($dir.$file);

					if (file_exists($fullpath))
					{
						$result=run_sql($fullpath,1,$id);
					}
				}

				$this->db->commit();
			}
			else
			{
				$this->db->rollback();
			}
		}
		}

		if ($action == 'edit' && $user->admin && ! $user->entity)
		{
			$error=0;

			if ($this->dao->fetch($id) < 0)
			{
				$error++;
				setEventMessage($langs->trans("ErrorEntityIsNotValid"), 'errors');
				$action = '';
			}
		}

		if ($action == 'update' && empty($cancel) && $id > 0 && $user->admin && ! $user->entity)
		{
			$error=0;

			$ret = $this->dao->fetch($id);
			if ($ret < 0)
			{
				$error++;
				setEventMessage($langs->trans("ErrorEntityIsNotValid"), 'errors');
				$action = '';
			}
			else if (empty($label))
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")), 'errors');
				$action = 'edit';
			}
			else if (empty($name))
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("CompanyName")), 'errors');
				$action = 'edit';
			}

			// Verify if label already exist in database
			if (! $error)
			{
				$this->dao->getEntities();
				if (! empty($this->dao->entities))
				{
					foreach($this->dao->entities as $entity)
					{
						if ($entity->id == $id) continue;
						if (strtolower($entity->label) == strtolower($label)) $error++;
					}
					if ($error)
					{
						setEventMessage($langs->trans("ErrorEntityLabelAlreadyExist"), 'errors');
						$action = 'edit';
					}
				}
			}

			if (! $error)
		{
			$this->db->begin();

			$this->dao->label = $label;
			$this->dao->description	= $description;

			$this->dao->options['referent']	= (GETPOST('referring_entity') ? GETPOST('referring_entity') : null);

			foreach ($this->sharingelements as $element)
			{
				$this->dao->options['sharings'][$element]	= (GETPOST($element) ? GETPOST($element) : null);
			}

				$extrafields = new ExtraFields($this->db);
				$extralabels = $extrafields->fetch_name_optionals_label($this->dao->table_element, true);
				$extrafields->setOptionalsFromPost($extralabels, $this->dao);

			$ret = $this->dao->update($id,$user);
			if ($ret <= 0)
			{
				$error++;
				$errors=($this->dao->error ? array($this->dao->error) : $this->dao->errors);
				setEventMessage($errors, 'errors');
				$action = 'edit';
			}

			if (! $error && $ret > 0)
			{
				$country_id=GETPOST("country_id");
				$country_code=getCountry($country_id,2);
				$country_label=getCountry($country_id,0);

				dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_COUNTRY", $country_id.':'.$country_code.':'.$country_label,'chaine',0,'',$this->dao->id);
				dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_NOM",$name,'chaine',0,'',$this->dao->id);
				dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_ADDRESS",GETPOST("address"),'chaine',0,'',$this->dao->id);
				dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_TOWN",GETPOST("town"),'chaine',0,'',$this->dao->id);
				dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_ZIP",GETPOST("zipcode"),'chaine',0,'',$this->dao->id);
				dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_STATE",GETPOST("departement_id"),'chaine',0,'',$this->dao->id);
				dolibarr_set_const($this->db, "MAIN_MONNAIE",GETPOST("currency"),'chaine',0,'',$this->dao->id);
				dolibarr_set_const($this->db, "MAIN_LANG_DEFAULT",GETPOST("main_lang_default"),'chaine',0,'',$this->dao->id);

				$this->db->commit();
			}
			else
			{
				$this->db->rollback();
			}
		}
		}

		if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' && $user->admin && ! $user->entity)
		{
			$error=0;

			if ($id == 1)
			{
				$error++;
				setEventMessage($langs->trans("ErrorNotDeleteMasterEntity"), 'errors');
				$action = '';
			}

			if (! $error && $id > 0)
			{
				if ($this->dao->fetch($id) > 0)
				{
					if ($this->dao->delete($id) > 0)
					{
						setEventMessage($langs->trans('ConfirmedEntityDeleted'));
					}
					else
					{
						setEventMessage($this->dao->error, 'errors');
						$action = '';
					}
				}
			}
		}

		if ($action == 'setactive' && $id > 0 && $user->admin && ! $user->entity)
		{
			$this->dao->setEntity($id,'active',$value);
			if ($value == 0) $this->dao->setEntity($id,'visible',$value);
		}

		if ($action == 'setvisible' && $id > 0 && $user->admin && ! $user->entity)
		{
			$this->dao->setEntity($id,'visible',$value);
		}
	}

	/**
	 *
	 */
	function formObjectOptions($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;
		global $form;

		$langs->load('multicompany@multicompany');

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$this->resprints = '';

		if (1 == 2 && $object->element == 'societe') // TODO to develop
		{
			if (! empty($user->admin) && empty($user->entity))
			{
				$this->resprints = "\n".'<!-- BEGIN multicompany formObjectOptions -->'."\n";

				if ($action == 'create' || $action == 'edit')
				{
					$this->resprints.= '<tr><td>'.fieldLabel('LinkedEntity','linked_entity').'</td><td colspan="3" class="maxwidthonsmartphone">';
					$s = $this->select_entities('', 'linked_entity', '', false, array($conf->entity), true);
					$this->resprints.= $form->textwithpicto($s,$langs->trans("LinkedEntityDesc"),1);
					$this->resprints.= '</td></tr>'."\n";
				}
				else
				{
					$this->resprints.= '<tr><td>';
					$this->resprints.= '<table width="100%" class="nobordernopadding"><tr><td>';
					$this->resprints.= $form->textwithpicto($langs->trans('LinkedToEntity'),$langs->trans("LinkedEntityDesc"),1);
					$this->resprints.= '<td><td align="right">';
					$this->resprints.= '<a href="">'.img_edit().'</a>';
					$this->resprints.= '</td></tr></table>';
					$this->resprints.= '</td>';
					$this->resprints.= '<td colspan="3">';
					$this->resprints.= '';
					$this->resprints.= '</td></tr>'."\n";
				}

				$this->resprints.= '<!-- END multicompany formObjectOptions -->'."\n";
			}
		}
		else if ($object->element == 'user')
		{
			$this->resprints = "\n".'<!-- BEGIN multicompany formObjectOptions -->'."\n";

			if ($action == 'edit')
			{
				// TODO check if user not linked with the current entity before change entity (thirdparty, invoice, etc.) !!
				if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && ! empty($user->admin) && empty($user->entity))
				{
					$this->resprints.= '<tr><td>'.$langs->trans("Entity").'</td>';
					$this->resprints.= '<td>'.$this->select_entities($object->entity, 'entity');
					$this->resprints.= "</td></tr>\n";
				}
				else
				{
					$this->resprints.= '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
				}
			}
			else if ($action == 'create')
			{
				if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && ! empty($user->admin) && empty($user->entity))
				{
					$this->resprints.= '<tr><td>'.$langs->trans("Entity").'</td>';
					$this->resprints.= '<td>'.$this->select_entities($conf->entity, 'entity');
					$this->resprints.= "</td></tr>\n";
				}
				else
				{
					$this->resprints.= '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
				}
			}
			else if ($action != 'adduserldap')
			{
				if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && ! empty($user->admin) && empty($user->entity))
				{
					$this->resprints.= '<tr><td>'.$langs->trans("Entity").'</td><td>';
					if (empty($object->entity))
					{
						$this->resprints.= $langs->trans("AllEntities");
					}
					else
					{
						$this->getInfo($object->entity);
						$this->resprints.= $this->label;
					}
					$this->resprints.= "</td></tr>\n";
				}
			}

			$this->resprints.= '<!-- END multicompany formObjectOptions -->'."\n";
		}

		return 0;
	}

	/**
	 *
	 */
	function insertExtraHeader($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		if (!empty($user->admin) && empty($user->entity) && ! empty($conf->multicompany->enabled) && ! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
		{
			if ($object->element == 'user')
			{
				$aEntities=array_keys($permsgroupbyentity);
				sort($aEntities);
				$entity = (GETPOST('entity', 'int')?GETPOST('entity', 'int'):$conf->entity);
				$head = entity_prepare_head($object, $aEntities);
				$title = $langs->trans("Entities");
				dol_fiche_head($head, $entity, $title, 1, 'multicompany@multicompany');
			}
			else if ($object->element == 'usergroup')
			{
				$this->getInstanceDao();
				$this->dao->getEntities();

				$aEntities=array();

				foreach ($this->dao->entities as $objEntity)
				{
					$aEntities[] = $objEntity->id;
				}

				$entity = (GETPOST('entity', 'int')?GETPOST('entity', 'int'):$conf->entity);
				$head = entity_prepare_head($object, $aEntities);
				$title = $langs->trans("Entities");
				dol_fiche_head($head, $entity, $title, 1, 'multicompany@multicompany');
			}
		}

		return 0;
	}

	/**
	 *
	 */
	function insertExtraFooter($parameters=false, &$object, &$action='')
	{
		global $conf;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		if (empty($user->admin) && empty($user->entity) && ! empty($conf->multicompany->enabled) && ! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
		{
			if ($object->element == 'user' || $object->element == 'usergroup')
			{
				dol_fiche_end();
			}
		}

		return 0;
	}

	/**
	 *	Return combo list of entities.
	 *
	 *	@param	int		$selected	Preselected entity
	 *	@param	int		$htmlname	Name
	 *	@param	string	$option		Option
	 *	@param	int		$login		If use in login page or not
	 *	@return	string
	 */
	function select_entities($selected='', $htmlname='entity', $option='', $login=false, $exclude=false, $emptyvalue=false)
	{
		global $user,$langs;

		$this->getInstanceDao();

		$this->dao->getEntities($login, $exclude);

		$return = '';

		if (is_array($this->dao->entities))
		{
			$return.= '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'"'.$option.'>';

			if ($emptyvalue)
				$return.= '<option value="-1">&nbsp;</option>';

			foreach ($this->dao->entities as $entity)
			{
				if ($entity->active == 1 && ($entity->visible == 1 || ($user->admin && ! $user->entity)))
				{
					if (! empty($user->login) && ! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && ! empty($user->entity) && $this->checkRight($user->id, $entity->id) < 0) continue;

					$return.= '<option value="'.$entity->id.'"';
					if ($selected == $entity->id)	$return.= ' selected="selected"';
					$return.= '>';
					$return.= $entity->label;
					if (empty($entity->visible)) $return.= ' ('.$langs->trans('Hidden').')';
					$return.= '</option>';
				}
			}

			$return.= '</select>';
		}
		else
			$return.= $langs->trans('NoEntityAvailable');

		return $return;
	}

	/**
	 *	Return multiselect list of entities.
	 *
	 *	@param	string	$htmlname	Name of select
	 *	@param	DaoMulticompany	$current	Current entity to manage
	 *	@param	string	$option		Option
	 *	@return	string
	 */
	function multiselect_entities($htmlname, $current, $option='')
	{
		global $conf, $langs;

		$this->getInstanceDao();
		$this->dao->getEntities();

		$return = '<select id="'.$htmlname.'" class="multiselect" multiple="multiple" name="'.$htmlname.'[]" '.$option.'>';
		if (is_array($this->dao->entities))
		{
			foreach ($this->dao->entities as $entity)
			{
				if (is_object($current) && $current->id != $entity->id && $entity->active == 1)
				{
					$return.= '<option value="'.$entity->id.'" ';
					if (is_array($current->options['sharings'][$htmlname]) && in_array($entity->id, $current->options['sharings'][$htmlname]))
					{
						$return.= 'selected="selected"';
					}
					$return.= '>';
					$return.= $entity->label;
					if (empty($entity->visible))
					{
						$return.= ' ('.$langs->trans('Hidden').')';
					}
					$return.= '</option>';
				}
			}
		}
		$return.= '</select>';

		return $return;
	}

	/**
	 *    Switch to another entity.
	 *
	 *    @param int $id        User id
	 *    @param int $entity    Entity id
	 *    @return int
	 */
	function checkRight($id, $entity)
	{
		global $conf;

		$this->getInstanceDao();

		if ($this->dao->fetch($entity) > 0)
		{
			// Controle des droits sur le changement
			if ($this->dao->verifyRight($entity, $id) || $user->admin)
			{
				return 1;
			}
			else
			{
				return -2;
			}
		}
		else
		{
			return -1;
		}
	}

	/**
	 *    Switch to another entity.
	 *    @param    int $id Id of the destination entity
	 *    @param    int $userid
	 *    @return int
	 */
	function switchEntity($id, $userid=null)
	{
		global $conf, $user;

		$this->getInstanceDao();

		if (!empty($userid))
		{
			$user=new User($this->db);
			$user->fetch($userid);
		}

		if ($this->dao->fetch($id) > 0)
		{
			// Controle des droits sur le changement
			if (!empty($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX)
			|| (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $this->dao->verifyRight($id, $user->id))
			|| $user->admin)
			{
				$_SESSION['dol_entity'] = $id;
				$conf->entity = $id;
				$conf->setValues($this->db);
				return 1;
			}
			else
			{
				//var_dump($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX);
				//var_dump($conf->global->MULTICOMPANY_TRANSVERSE_MODE);
				//var_dump($this->dao->verifyRight($id, $user->id));
				return -2;
			}
		}
		else
		{
			return -1;
		}
	}

	/**
	 * 	Get entity info
	 * 	@param	int $id	Object id
	 */
	function getInfo($id)
	{
		$this->getInstanceDao();
		$this->dao->fetch($id);

		$this->label		= $this->dao->label;
		$this->description	= $this->dao->description;
	}

	/**
	 *    Get action title
	 *    @param string $action Type of action
	 *    @return string
	 */
	function getTitle($action='')
	{
		global $langs;

		if ($action == 'create') return $langs->trans("AddEntity");
		else if ($action == 'edit') return $langs->trans("EditEntity");
		else return $langs->trans("EntitiesManagement");
	}


	/**
	 *    Assigne les valeurs pour les templates
	 *    @param string $action     Type of action
	 */
	function assign_values($action='view')
	{
		global $conf,$langs,$user;
		global $form,$formcompany,$formadmin;

		require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');

		$this->tpl['extrafields'] = new ExtraFields($this->db);
		// fetch optionals attributes and labels
		$this->tpl['extralabels'] = $this->tpl['extrafields']->fetch_name_optionals_label('entity');

		$this->getInstanceDao();

		$this->template_dir = dol_buildpath('/multicompany/tpl/');

		if ($action == 'create')
		{
			$this->template = 'entity_create.tpl.php';
		}
		else if ($action == 'edit')
		{
			$this->template = 'entity_edit.tpl.php';

			if (GETPOST('id')) $ret = $this->dao->fetch(GETPOST('id', 'int'));
		}

		if ($action == 'create' || $action == 'edit')
		{
			// Label
			$this->tpl['label'] = (GETPOST('label')?GETPOST('label'):$this->dao->label);

			// Description
			$this->tpl['description'] = (GETPOST('description')?GETPOST('description'):$this->dao->description);

			// Company name
			$this->tpl['name'] = (GETPOST('name')?GETPOST('name'):$this->dao->MAIN_INFO_SOCIETE_NOM);

			// Address
			$this->tpl['address'] = (GETPOST('address')?GETPOST('address'):$this->dao->MAIN_INFO_SOCIETE_ADDRESS);

			// Zip
            $this->tpl['select_zip'] = $formcompany->select_ziptown((GETPOST('zipcode')?GETPOST('zipcode'):$this->dao->MAIN_INFO_SOCIETE_ZIP),'zipcode',array('town','selectcountry_id','departement_id'),6);

            // Town
            $this->tpl['select_town'] = $formcompany->select_ziptown((GETPOST('town')?GETPOST('town'):$this->dao->MAIN_INFO_SOCIETE_TOWN),'town',array('zipcode','selectcountry_id','departement_id'));

            if ($user->admin) $this->tpl['info_admin'] = info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);


			// We define country_id, country_code and country_label
			$country = (! empty($this->dao->MAIN_INFO_SOCIETE_COUNTRY)?$this->dao->MAIN_INFO_SOCIETE_COUNTRY:$conf->global->MAIN_INFO_SOCIETE_COUNTRY);
			if (GETPOST('country_id'))
			{
				$country_id=GETPOST('country_id');
			}
			else if (! empty($country) && ! GETPOST('country_id', 'int'))
			{
				$tmp=explode(':',$country);
				$country_id=$tmp[0];
				if (! empty($tmp[1]))   // If $conf->global->MAIN_INFO_SOCIETE_COUNTRY is "id:code:label"
				{
					$country_code=$tmp[1];
					$country_label=$tmp[2];
				}
				else
				{
					$country_code=getCountry($country_id,2);
					$country_label=getCountry($country_id,0);
				}
			}
			else
			{
				$country_id=0;
				$country_code='';
				$country_label='';
			}

			$this->tpl['select_country'] = $form->select_country($country_id,'country_id');
			$this->tpl['select_state'] = $formcompany->select_state((GETPOST('departement_id')?GETPOST('departement_id', 'int'):$this->dao->MAIN_INFO_SOCIETE_STATE),($country_code?$country_code:$country_id),'departement_id');
			$this->tpl['select_currency'] = $form->selectcurrency((GETPOST('currency')?GETPOST('currency'):($this->dao->MAIN_MONNAIE?$this->dao->MAIN_MONNAIE:$conf->currency)),"currency");
			$this->tpl['select_language'] = $formadmin->select_language((GETPOST('main_lang_default')?GETPOST('main_lang_default'):($this->dao->MAIN_LANG_DEFAULT?$this->dao->MAIN_LANG_DEFAULT:$conf->global->MAIN_LANG_DEFAULT)),'main_lang_default',1);

			$this->tpl['select_entity']	= $this->select_entities($this->dao->options['referent'], 'referring_entity');

			foreach ($this->sharingelements as $element)
			{
				$this->tpl['multiselect_shared_' . $element]	= $this->multiselect_entities($element, $this->dao);
			}
		}
		else
		{

			$this->dao->getEntities();

			$this->tpl['entities']		= $this->dao->entities;
			$this->tpl['img_on'] 		= img_picto($langs->trans("Activated"),'on');
			$this->tpl['img_off'] 		= img_picto($langs->trans("Disabled"),'off');
			$this->tpl['img_modify'] 	= img_edit();
			$this->tpl['img_delete'] 	= img_delete();

			// Confirm delete
			if ($action == 'delete')
			{
				$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".GETPOST('id', 'int'),$langs->trans("DeleteAnEntity"),$langs->trans("ConfirmDeleteEntity"),"confirm_delete",'',0,1);
			}

			$this->template = 'entity_view.tpl.php';
		}
	}

	/**
	 *    Display the template
	 */
	function display()
	{
		global $conf, $langs;
		global $bc;

		include($this->template_dir.$this->template);
	}

	/**
	 * 	Set values of global conf for multicompany
	 *
	 * 	@param	Conf	$conf	Object conf
	 * 	@return void
	 */
	function setValues(&$conf)
	{
		$this->getInstanceDao();

		$this->dao->fetch($conf->entity);

		if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
		{
			$this->sharings = $this->dao->options['sharings'];
			$this->referent = $this->dao->options['referent'];

			foreach($this->sharingdicts as $dict)
			{
				$constname = 'MULTICOMPANY_'.strtoupper($dict).'_SHARING_DISABLED';
				if (! empty($conf->global->$constname))
					$this->dict[$dict] = true;
			}

			// Load shared elements
			$this->loadSharedElements();

			// Define output dir for others entities
			$this->setMultiOutputDir($conf);
		}
	}

	/**
	 * 	Get entity to use
	 *
	 * 	@param	string	$element		Current element
	 * 	@param	int		$shared			0=Return id of current entity only,
	 * 									1=Return id of current entity + shared entities (default),
	 * 									2=Return id of current entity OR value 1 for master entity (eg. dictionnary sharing)
	 *  @param	int		$forceentity	Entity id to force
	 * 	@return	int						Entity id to use
	 */
	function getEntity($element=false, $shared=1, $forceentity=null)
	{
		global $conf;

		$addzero = array('user', 'usergroup', 'email_template', 'default_values');
		if (in_array($element, $addzero))
		{
			return '0,'.$conf->entity;
		}

		if ($shared == 2)
		{
			$element = str_replace(MAIN_DB_PREFIX, '', $element);

			if (! empty($this->dict[$element]))
				return $conf->entity;
			else
				return 1; // Master entity
		}

		$elementkey = $element;
		if ($element == 'societe')		$elementkey = 'thirdparty';
		if ($element == 'facture')		$elementkey = 'invoicenumber';
		if ($element == 'adherent')		$elementkey = 'member';
		if ($element == 'bank_account')	$elementkey = 'bankaccount';
		if ($element == 'contrat')      $elementkey= 'contract';

		if (! empty($element) && ! empty($this->entities[$elementkey]))
		{
			if (! empty($shared))
			{
				return $this->entities[$elementkey];
			}
			else if (! empty($this->sharings['referent']))
			{
				if ($element == 'societe') return $this->sharings['referent'];
			}
		}

		return $conf->entity;
	}

	/**
	 * 	Set object documents directory to use
	 *
	 *	@param	Conf	$conf		Object Conf
	 * 	@return	void
	 */
	function setMultiOutputDir(&$conf)
	{
		if (! empty($this->entities))
		{
			foreach($this->entities as $element => $shares)
			{
				if ($element == 'thirdparty')	$element = 'societe';
				if ($element == 'member')		$element = 'adherent';
				if($element == 'contract')      $element = 'contrat';

				if (!empty($conf->$element->enabled) && isset($conf->$element->multidir_output) && isset($conf->$element->multidir_temp))
				{
					$elementpath=$element;
					if ($element == 'product') $elementpath='produit';
					if ($element == 'category') $elementpath='categorie';

					$entities = explode(",", $shares);
					$dir_output = array();
					$dir_temp = array();
					foreach($entities as $entity)
					{
						if (!array_key_exists($entity, $conf->$element->multidir_output))
						{
							$path = ($entity > 1 ? "/".$entity : '');

							$dir_output[$entity] 	= DOL_DATA_ROOT.$path."/".$elementpath;
							$dir_temp[$entity] 		= DOL_DATA_ROOT.$path."/".$elementpath."/temp";

							$conf->$element->multidir_output += $dir_output;
							$conf->$element->multidir_temp += $dir_temp;
						}
					}
				}
			}
		}
	}

	/**
	 * @param bool $parameters
	 * @return int
	 */
	function printTopRightMenu($parameters=false)
	{
		echo $this->getTopRightMenu();

		return 0;
	}

	/**
	 * @param bool $parameters
	 * @return int
	 */
	function afterLogin($parameters=false)
	{
		global $conf;

		return 0;
	}

	/**
	 * @param bool $parameters
	 * @return int
	 */
	function updateSession($parameters=false)
	{
		global $conf;

		// Switch to another entity
		if (! empty($conf->multicompany->enabled) && GETPOST('action') == 'switchentity')
		{
			if ($this->switchEntity(GETPOST('entity','int')) > 0)
			{
				header("Location: ".DOL_URL_ROOT.'/');
				exit;
			}
		}

		return 0;
	}

	/**
	 *
	 */
	function getLoginPageOptions($parameters=false)
	{
		global $conf, $langs;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		if (empty($entity)) $entity=1;
		$lastentity=(! empty($conf->global->MULTICOMPANY_FORCE_ENTITY)?$conf->global->MULTICOMPANY_FORCE_ENTITY:$entity);

		// Entity combobox
		if (empty($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX))
		{
			$select_entity = $this->select_entities($lastentity, 'entity', ' tabindex="3"', true);
/*
			$divformat = '<div class="entityBox">';
			$divformat.= $select_entity;
			$divformat.= '</div>';

			$this->results['options']['div'] = $divformat;
*/
			$tableformat = '<tr id="entityBox"><td class="nowrap center valignmiddle">';
			$tableformat.= '<div class="span-icon-multicompany">';
			$tableformat.= $select_entity;
			$tableformat.= '</div>';
			$tableformat.= '</td></tr>';

			//$this->results['options']['table'] = $tableformat;
			$this->resprints = $tableformat;
		}

		return 0;
	}

	/**
	 *
	 */
	function getPasswordForgottenPageOptions($parameters=false)
	{
		return $this->getLoginPageOptions($parameters);
	}

	/**
	 * Add all entities default dictionnaries in database
	 */
	function addAllEntitiesDefaultDicts()
	{
		if (! empty($this->sharingdicts))
		{
			$this->getInstanceDao();
			$this->dao->getEntities();

			$dir	= "/multicompany/sql/dict/";

			foreach($this->sharingdicts as $dict)
			{
				// Load sql init_new_entity_dict.sql file
				$file 	= 'init_new_entity_'.$dict.'.sql';
				$fullpath = dol_buildpath($dir.$file);

				if (file_exists($fullpath))
				{
					foreach ($this->dao->entities as $entity)
					{
						if ($entity->id == 1) continue;

						$result=run_sql($fullpath,1,$entity->id);
					}
				}
			}
		}
	}

	/**
	 *  Load shared elements
	 *
	 *  @return void
	 */
	private function loadSharedElements()
	{
		global $conf;

		$this->getInstanceDao();

		if (! empty($this->sharings))
		{
			foreach($this->sharings as $element => $ids)
			{
				$moduleSharingEnabled = 'MULTICOMPANY_'.strtoupper($element).'_SHARING_ENABLED';
				$module = $element;


				if ($element == 'thirdparty') {
					$module = 'societe';
				} else if ($element == 'productprice') {
					$module = 'product';
				} else if ($element == 'bank_account' || $element == 'bankaccount') {
					$module = 'banque';
				} else if ($element == 'product' && empty($conf->product->enabled) && !empty($conf->service->enabled)) {
					$module = 'service';
				} else if ($element == 'invoicenumber') {
					$module = 'facture';
				} else if ($element == 'project') {
					$module = 'projet';
				} else if ($element == 'member') {
					$module = 'adherent';
				} else if ($element == 'contract') {
					$module = 'contrat';
				}

				if (! empty($conf->$module->enabled) && ! empty($conf->global->$moduleSharingEnabled))
				{
					$entities=array();

					if (! empty($this->referent))
					{
						// Load configuration of referent entity
						$this->config = $this->dao->getEntityConfig($this->referent);
						$this->setConstant($conf, $element);
					}

					if (! empty($ids))
					{
						foreach ($ids as $id)
						{
							$ret=$this->dao->fetch($id);
							if ($ret > 0 && $this->dao->active)
							{
								$entities[] = $id;
							}
						}

						$this->entities[$element] = (! empty($entities) ? implode(",", $entities) : 0);
						$this->entities[$element].= ','.$conf->entity;
					}
				}
			}
		}
		//var_dump($this->entities);
	}

	/**
	 * 	Show entity info
	 */
	private function getTopRightMenu()
	{
		global $conf,$user,$langs;

		$langs->load('multicompany@multicompany');

		$out='';

		if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) || !empty($user->admin))
		{
			$form=new Form($this->db);

			$this->getInfo($conf->entity);

			$text = img_picto('', 'object_multicompany@multicompany','id="switchentity" class="entity linkobject"');

			$htmltext ='<u>'.$langs->trans("Entity").'</u>'."\n";
			$htmltext.='<br><b>'.$langs->trans("Label").'</b>: '.$this->label."\n";
			$htmltext.='<br><b>'.$langs->trans("Description").'</b>: '.$this->description."\n";

			$out.= $form->textwithtooltip('',$htmltext,2,1,$text,'login_block_elem',2);

			$out.= '
			<script type="text/javascript">
			$(document).ready(function() {
				$( "#switchentity" ).click(function() {
					$( "#dialog-switchentity" ).dialog({
						modal: true,
						width: 400,
						buttons: {
							\''.$langs->trans('Ok').'\': function() {
								choice=\'ok\';
								$.get( "'.dol_buildpath('/multicompany/ajaxswitchentity.php',1).'", {
									action: \'switchentity\',
									entity: $( "#entity" ).val()
								},
								function(content) {
									$( "#dialog-switchentity" ).dialog( "close" );
								});
							},
							\''.$langs->trans('Cancel').'\': function() {
								choice=\'ko\';
								$(this).dialog( "close" );
							}
						},
						close: function(event, ui) {
							if (choice == \'ok\') {
								location.href=\''.DOL_URL_ROOT.'\';
							}
						}
					});
				});
			});
			</script>';

			$out.= '<div id="dialog-switchentity" class="hideobject" title="'.$langs->trans('SwitchToAnotherEntity').'">'."\n";
			$out.= '<br>'.$langs->trans('SelectAnEntity').': ';
			$out.= ajax_combobox('entity');
			$out.= $this->select_entities($conf->entity)."\n";
			$out.= '</div>'."\n";
		}

		if (!checkMultiCompanyVersion())
		{
			$msg = get_htmloutput_mesg(img_warning('default') . ' ' . $langs->trans("MultiCompanyUpgradeIsNeeded"), '', 'mc-upgrade-alert', 1);
			$out.= '
			<script type="text/javascript">
			$(document).ready(function() {
				$( "#id-right .fiche" ).before( \'' . $msg . '\' );
			});
			</script>';
		}

		$this->resprints = $out;
	}

	/**
	 *	Set parameters with referent entity
	 *
	 * @param Conf $conf
	 * @param string $element
	 */
	function setConstant(&$conf, $element)
	{
		if (! empty($this->config))
		{
			$constants=array();

			if ($element == 'thirdparty')
			{
				$constants = array(
						'SOCIETE_CODECLIENT_ADDON',
						'COMPANY_ELEPHANT_MASK_CUSTOMER',
						'COMPANY_ELEPHANT_MASK_SUPPLIER',
						'SOCIETE_IDPROF1_UNIQUE',
						'SOCIETE_IDPROF2_UNIQUE',
						'SOCIETE_IDPROF3_UNIQUE',
						'SOCIETE_IDPROF4_UNIQUE'
				);
			}

			if (! empty($constants))
			{
				foreach($constants as $name)
				{
					if (! empty($this->config[$name])) $conf->global->$name = $this->config[$name];
				}
			}
		}
	}

}
