<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018       Open-DSI            <support@open-dsi.fr>
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
 * \file    eventconfidentiality/class/eventconfidentiality.class.php
 * \ingroup eventconfidentiality
 * \brief
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class EventConfidentiality
 */
class EventConfidentiality extends CommonObject
{
	public $element = 'eventconfidentiality';
	public $table_element = 'eventconfidentiality';
    public $table_element_line = 'eventconfidentiality';
    public $fk_element = 'fk_eventconfidentiality';
    protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe


    const STATUS_INTERNE = 0;
    const STATUS_EXTERNE = 1;

    const LEVEL_SHOW = 0;
    const LEVEL_BLURRED = 1;
    const LEVEL_HIDDEN = 2;

    /**
	 * Constructor
	 *
	 * @param   DoliDb      $db     Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}
}