<?php
/* Copyright (C) 2005-2017	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2017	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013		Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2015		Bahfir Abbes			<bafbes@gmail.com>
 * Copyright (C) 2019		Open-Dsi			    <support@open-dsi.fr>
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
 *	\file       htdocs/synergiestech/core/triggers/interface_51_modSynergiesTech_SynergiesTechAfterActionsAuto.class.php
 *  \ingroup    agenda
 *  \brief      Trigger file for agenda module
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggered functions for agenda module
 */
class InterfaceSynergiesTechAfterActionsAuto extends DolibarrTriggers
{
	public $family = 'agenda';
	public $description = "Triggers of this module reaffect option of add action in agenda after standard trigger actions.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'action';

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * Following properties may be set before calling trigger. The may be completed by this trigger to be used for writing the event into database:
	 *      $object->actiontypecode (translation action code: AC_OTH, ...)
	 *      $object->actionmsg (note, long text)
	 *      $object->actionmsg2 (label, short text)
	 *      $object->sendtoid (id of contact or array of ids)
	 *      $object->socid (id of thirdparty)
	 *      $object->fk_project
	 *      $object->fk_element
	 *      $object->elementtype
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
        if (empty($conf->agenda->enabled)) return 0;     // Module not active, we do nothing

		$key = 'MAIN_AGENDA_ACTIONAUTO_'.$action;

		if ($object->skipstandardaction) {
		    unset($object->skipstandardaction);
            $conf->global->$key = 1;
		}

        return 0;
    }
}
