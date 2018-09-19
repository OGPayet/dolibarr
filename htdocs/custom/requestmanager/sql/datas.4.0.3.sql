-- ============================================================================
-- Copyright (C) 2017	 Open-DSI 	 <support@open-dsi.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===========================================================================

INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `color`, `picto`, `position`) VALUES
(163023, 'AC_RM_FPC', 'systemauto', 'Forcing principal company (auto inserted events)', 'requestmanager', 1, NULL, NULL, NULL, 20);

--INSERT INTO `llx_c_action_trigger` (`code`, `label`, `description`, `elementtype`, `rang`) VALUES
--('PRICEREQUEST_CREATE', 'Price request created', 'Executed when a price request is created', 'pricerequest', 41),
--('PRICEREQUEST_MODIFY', 'Price request modified', 'Executed when a price request is modified', 'pricerequest', 42),
--('PRICEREQUEST_DELETE', 'Price request deleted', 'Executed when a price request is deleted', 'pricerequest', 43),
--('PRICEREQUEST_NOTIFY', 'Price request notified', 'Executed when a price request is notified', 'pricerequest', 44),
--('PRICEREQUEST_CLOSE_REFUSED', 'Price request refused', 'Executed when a price request is refused', 'pricerequest', 45),
--('PRICEREQUEST_CLOSE_CONVERTED', 'Price request converted', 'Executed when a price request is converted', 'pricerequest', 46),
--('PRICEREQUEST_NOTIFY_SENTBYMAIL', 'Price request notify sent by mail', 'Executed when you send notify email from price request card', 'pricerequest', 47),
--('PRICEREQUEST_REFUSAL_SENTBYMAIL', 'Price request refusal sent by mail', 'Executed when you send refusal email from price request card', 'pricerequest', 48);
