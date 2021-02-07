-- ========================================================================
-- Copyright (C) 2019 		Open-DSI      <support@open-dsi.fr>
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

ALTER TABLE llx_quicklist ADD COLUMN `default`  integer(1)    NULL;
ALTER TABLE llx_quicklist ADD COLUMN `hash_tag` varchar(255)  NULL;
ALTER TABLE llx_quicklist CHANGE COLUMN `url` `params`  text  NULL;

UPDATE llx_quicklist SET url = SUBSTRING_INDEX(url, '?', -1)
