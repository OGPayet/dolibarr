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

INSERT INTO `llx_c_type_contact` (`rowid`, `element`, `source`, `code`, `libelle`, `active`, `module`) VALUES
(163018, 'requestmanager', 'external', 'REQUESTER', 'Requester', 1, 'requestmanager'),
(163019, 'requestmanager', 'external', 'WATCHER', 'Watcher', 1, 'requestmanager');

INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `color`, `picto`, `position`) VALUES
(163018, 'AC_RM_IN', 'systemauto', 'Input message (automatically inserted events)', 'requestmanager', 1, NULL, NULL, NULL, 20),
(163019, 'AC_RM_OUT', 'systemauto', 'Output message (automatically inserted events)', 'requestmanager', 1, NULL, NULL, NULL, 20),
(163020, 'AC_RM_PRIV', 'systemauto', 'Private message (automatically inserted events)', 'requestmanager', 1, NULL, NULL, NULL, 20);

INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `color`, `picto`, `position`) VALUES
(163021, 'AC_RM_STAT', 'systemauto', 'Status message (automatically inserted events)', 'requestmanager', 1, NULL, NULL, NULL, 20);
INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `color`, `picto`, `position`) VALUES
(163022, 'AC_RM_ASSUSR', 'systemauto', 'Assigned users message (automatically inserted)', 'requestmanager', 1, NULL, NULL, NULL, 20);