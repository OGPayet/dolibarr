<?php
 /* Copyright (C) 2020 Alexis LAURIER - <contact@alexislaurier.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/universign.class.php
 * \ingroup     digitalsignaturemanager
 */

Class DigitalSignatureManagerUniversign {
	/**
	 * @var string username to connect to the api
	 */
	public $username;

	/**
	 * @var string password to connect to the api
	 */
	public $password;

	/**
	 * @var string endpoint user to use in order to connect to the api
	 */
	public $endpointUrl;

	/**
	 * @var DigitalSignatureRequest Object containing information of the signature request
	 */
	public $digitalSignatureManager;

	/**
	 * @var string[] Array of errors
	 */
	public $errors = array();

	/**
	 * Create a signature request on universign
	 *
	 * @return bool|string   return the signature ID or false if some error appends
	 */
	public function create()
	{
		return 0;
	}

	/**
	 * Get information about a signature request on universign
	 *
	 * @return bool|string   return the signature ID or false if some error appends
	 */
	public function getAndUpdateData()
	{
		return 0;
	}

		/**
	 * Get information about a signature request on universign
	 *
	 * @return bool|string   return the signature ID or false if some error appends
	 */
	public function cancel()
	{
		return 0;
	}
}
