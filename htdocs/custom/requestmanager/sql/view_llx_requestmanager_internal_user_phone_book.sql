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
-- ============================================================================

CREATE VIEW llx_requestmanager_internal_user_phone_book AS
  SELECT DISTINCT u.rowid, u.entity,
    RM_GLOBAL_TRIM(u.office_phone, '0123456789') COLLATE utf8_general_ci AS office_phone,
    RM_GLOBAL_TRIM(u.user_mobile, '0123456789') COLLATE utf8_general_ci AS user_mobile
  FROM llx_user AS u
  WHERE u.fk_soc = 0 OR u.fk_soc IS NULL;
