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

CREATE VIEW llx_requestmanager_soc_contact_phone_book AS
  SELECT DISTINCT s.rowid AS socid, sc.rowid AS contactid, s.entity,
    RM_GLOBAL_TRIM(s.phone, '0123456789') COLLATE utf8_general_ci AS soc_phone,
    RM_GLOBAL_TRIM(sc.phone, '0123456789') COLLATE utf8_general_ci AS contact_phone,
    RM_GLOBAL_TRIM(sc.phone_perso, '0123456789') COLLATE utf8_general_ci AS contact_phone_perso,
    RM_GLOBAL_TRIM(sc.phone_mobile, '0123456789') COLLATE utf8_general_ci AS contact_phone_mobile
  FROM llx_societe AS s
  LEFT JOIN llx_socpeople AS sc ON sc.fk_soc = s.rowid;
