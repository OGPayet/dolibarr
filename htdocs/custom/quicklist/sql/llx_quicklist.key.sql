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

ALTER TABLE llx_quicklist ADD INDEX idx_quicklist_fk_user_creat(fk_user_creat);

ALTER TABLE llx_quicklist ADD CONSTRAINT fk_quicklist_fk_user_creat_user FOREIGN KEY (fk_user_creat)    REFERENCES llx_user (rowid);
ALTER TABLE llx_quicklist ADD CONSTRAINT fk_quicklist_fk_menu_menu       FOREIGN KEY (fk_menu)          REFERENCES llx_menu (rowid);
