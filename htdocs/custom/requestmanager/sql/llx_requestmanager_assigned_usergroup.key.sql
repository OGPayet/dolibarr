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

ALTER TABLE llx_requestmanager_assigned_usergroup ADD UNIQUE INDEX uk_requestmanager_a_ug (fk_requestmanager, fk_usergroup);

ALTER TABLE llx_requestmanager_assigned_usergroup ADD INDEX idx_requestmanager_a_ug_fk_requestmanager (fk_requestmanager);
ALTER TABLE llx_requestmanager_assigned_usergroup ADD INDEX idx_requestmanager_a_ug_fk_usergroup (fk_usergroup);

ALTER TABLE llx_requestmanager_assigned_usergroup ADD CONSTRAINT fk_requestmanager_a_ug_fk_requestmanager     FOREIGN KEY (fk_requestmanager) REFERENCES llx_requestmanager (rowid);
ALTER TABLE llx_requestmanager_assigned_usergroup ADD CONSTRAINT fk_requestmanager_a_ug_fk_usergroup          FOREIGN KEY (fk_usergroup) REFERENCES llx_usergroup (rowid);
