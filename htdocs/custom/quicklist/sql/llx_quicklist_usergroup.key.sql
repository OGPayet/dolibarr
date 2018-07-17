-- ========================================================================
-- Copyright (C) 2017 		Open-DSI      <support@open-dsi.fr>
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
-- ========================================================================

ALTER TABLE llx_quicklist_usergroup ADD INDEX idx_quicklist_usergroup_fk_quicklist(fk_quicklist);
ALTER TABLE llx_quicklist_usergroup ADD INDEX idx_quicklist_usergroup_fk_usergroup(fk_usergroup);

ALTER TABLE llx_quicklist_usergroup ADD CONSTRAINT fk_quicklist_usergroup_fk_quicklist_quicklist FOREIGN KEY (fk_quicklist) REFERENCES llx_quicklist (rowid);
ALTER TABLE llx_quicklist_usergroup ADD CONSTRAINT fk_quicklist_usergroup_fk_usergroup_usergroup FOREIGN KEY (fk_usergroup) REFERENCES llx_usergroup (rowid);
