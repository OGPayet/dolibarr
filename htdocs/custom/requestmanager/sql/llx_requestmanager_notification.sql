-- ============================================================================
-- Copyright (C) 2018	 Open-DSI 	 <support@open-dsi.fr>
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
-- Table of journals for accountancy
-- ============================================================================

CREATE TABLE IF NOT EXISTS llx_requestmanager_notification
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_actioncomm integer NOT NULL DEFAULT '0',	-- id of the actioncomm for notification
  fk_user       integer NOT NULL DEFAULT '0',	-- id of the user to notify
  status        integer NOT NULL DEFAULT '0' -- status (0=not read, 1=read)
) ENGINE=innodb;
