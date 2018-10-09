<?php
/*  Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 *	\file       htdocs/eventconfidentiality/core/triggers/interface_99_modEventConfidentiality_ECWorkflow.class.php
 *  \ingroup    eventconfidentiality
 *	\brief      File of class of triggers for workflow in eventconfidentiality module
 */


require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for workflow in eventconfidentiality module
 */
class InterfaceECWorkflow extends DolibarrTriggers
{
	public $family = 'eventconfidentiality';
	public $description = "Triggers of this module catch triggers event for the workflow of EventConfidentiality module.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'eventconfidentiality@eventconfidentiality';


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		dol_include_once('/advancedictionaries/class/dictionary.class.php');
		dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
		dol_include_once('/eventconfidentiality/lib/eventconfidentiality.lib.php');

		if(empty($object->elementtype)) { //Gestion des event action qui n'ont pas de type
			$object->elementtype = 'event';
		}

	    if ($action == 'ACTION_CREATE') {
			$tags_interne = GETPOST('add_tag_interne', 'array');
			$tags_externe = GETPOST('add_tag_externe', 'array');
			$tags = array_merge($tags_interne, $tags_externe);
			if(!empty($tags)) {
				foreach($tags as $tag) {
					$eventconfidentiality = new EventConfidentiality($this->db);
					$eventconfidentiality->getDefaultMode($tag, $object->elementtype, $object->type_id, $object->id);
					$eventconfidentiality->create($user);
				}
			} else {
				$list_event = getDefaultTag($object->elementtype, $object->type_id, $object->id);
				foreach($list_event as $event) {
					$eventconfidentiality = new EventConfidentiality($this->db);
					$eventconfidentiality->fk_object = $event['fk_object'];
					$eventconfidentiality->fk_dict_tag_confid = $event['fk_dict_tag_confid'];
					$eventconfidentiality->externe = $event['externe'];
					$eventconfidentiality->level_confid = $event['level_confid'];
					$eventconfidentiality->create($user);

				}
			}
        }

	    if ($action == 'ACTION_MODIFY') {
			$tags_interne = GETPOST('edit_tag_interne', 'array');
			$tags_externe = GETPOST('edit_tag_externe', 'array');
			$tags = array_merge($tags_interne, $tags_externe);
			if(!empty($tags)) {
				foreach($tags as $tag) {
					$eventconfidentiality = new EventConfidentiality($this->db);
					$eventconfidentiality->getDefaultMode($tag, $object->elementtype, $object->type_id, $object->id);
					$eventconfidentiality->create($user);
				}
			}
        }

        return 0;
    }
}