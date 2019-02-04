-- ============================================================================
-- Copyright (C) 2019	 Open-DSI 	 <support@open-dsi.fr>
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
-- ===========================================================================

ALTER TABLE llx_societe_rm_user_in_charge ADD UNIQUE INDEX uk_srmuic_unique (fk_soc, fk_user, fk_request_type);

ALTER TABLE llx_societe_rm_user_in_charge ADD INDEX idx_srmuic_fk_soc (fk_soc);
ALTER TABLE llx_societe_rm_user_in_charge ADD INDEX idx_srmuic_fk_user (fk_user);
ALTER TABLE llx_societe_rm_user_in_charge ADD INDEX idx_srmuic_fk_c_request_type (fk_c_request_type);

ALTER TABLE llx_societe_rm_user_in_charge ADD CONSTRAINT fk_srmuic_fk_soc             FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_societe_rm_user_in_charge ADD CONSTRAINT fk_srmuic_fk_user            FOREIGN KEY (fk_user) REFERENCES llx_user (rowid);
ALTER TABLE llx_societe_rm_user_in_charge ADD CONSTRAINT fk_srmuic_fk_c_request_type  FOREIGN KEY (fk_c_request_type) REFERENCES llx_c_requestmanager_type (rowid);
