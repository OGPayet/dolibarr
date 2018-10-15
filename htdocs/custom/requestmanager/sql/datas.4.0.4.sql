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

DELETE FROM `llx_c_actioncomm` WHERE `id` IN (163021, 163022);
INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `color`, `picto`, `position`) VALUES
(163021, 'AC_RM_STATUS', 'systemauto', 'Status modified (automatically inserted events)', 'requestmanager', 1, NULL, NULL, NULL, 20);
INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `color`, `picto`, `position`) VALUES
(163022, 'AC_RM_ASSMOD', 'systemauto', 'Assigned modified (automatically inserted events)', 'requestmanager', 1, NULL, NULL, NULL, 20);

DELETE FROM `llx_c_action_trigger` WHERE `code` = 'REQUESTMANAGER_CREATE' OR `code` = 'REQUESTMANAGER_MODIFY' OR `code` = 'REQUESTMANAGER_DELETE' OR `code` = 'REQUESTMANAGER_SET_ASSIGNED'
 OR `code` = 'REQUESTMANAGER_STATUS_MODIFY' OR `code` = 'REQUESTMANAGER_SET_ASSIGNED_SENTBYMAIL' OR `code` = 'REQUESTMANAGER_STATUS_MODIFY_SENTBYMAIL' OR `code` = 'REQUESTMANAGERMESSAGE_SENTBYMAIL';
INSERT INTO `llx_c_action_trigger` (`code`, `label`, `description`, `elementtype`, `rang`) VALUES
('REQUESTMANAGER_CREATE', 'Request created', 'Executed when a request is created', 'requestmanager', 50),
('REQUESTMANAGER_MODIFY', 'Request modified', 'Executed when a request is modified', 'requestmanager', 51),
('REQUESTMANAGER_DELETE', 'Request deleted', 'Executed when a request is deleted', 'requestmanager', 52),
('REQUESTMANAGER_SET_ASSIGNED', 'Assigned to request modified', 'Executed when the assigned of the request is modified', 'requestmanager', 53),
('REQUESTMANAGER_STATUS_MODIFY', 'Request status modified', 'Executed when the status of the request is modified', 'requestmanager', 54),
('REQUESTMANAGER_ASSMOD_SENTBYMAIL', 'Assigned to request modified send by email', 'Executed when a assigned to request modified is send by email', 'requestmanager', 55),
('REQUESTMANAGER_STATUS_SENTBYMAIL', 'Request status modified send by email', 'Executed when a request status modified is send by email', 'requestmanager', 56),
('REQUESTMANAGERMESSAGE_SENTBYMAIL', 'Request message send by email', 'Executed when a request message is send by email', 'requestmanager', 57);
