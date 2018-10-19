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
-- ===========================================================================

ALTER TABLE llx_requestmanager ADD UNIQUE INDEX uk_requestmanager_ref (ref, entity);
ALTER TABLE llx_requestmanager ADD UNIQUE INDEX uk_requestmanager_ref_ext (ref_ext, entity);

ALTER TABLE llx_requestmanager ADD INDEX idx_requestmanager_fk_soc (fk_soc);
ALTER TABLE llx_requestmanager ADD INDEX idx_requestmanager_fk_type (fk_type);
ALTER TABLE llx_requestmanager ADD INDEX idx_requestmanager_fk_category (fk_category);
ALTER TABLE llx_requestmanager ADD INDEX idx_requestmanager_fk_source (fk_source);
ALTER TABLE llx_requestmanager ADD INDEX idx_requestmanager_fk_urgency (fk_urgency);
ALTER TABLE llx_requestmanager ADD INDEX idx_requestmanager_fk_impact (fk_impact);
ALTER TABLE llx_requestmanager ADD INDEX idx_requestmanager_fk_priority (fk_priority);
ALTER TABLE llx_requestmanager ADD INDEX idx_requestmanager_fk_status (fk_status);
ALTER TABLE llx_requestmanager ADD INDEX idx_requestmanager_fk_user_resolved (fk_user_resolved);
ALTER TABLE llx_requestmanager ADD INDEX idx_requestmanager_fk_user_closed (fk_user_closed);
ALTER TABLE llx_requestmanager ADD INDEX idx_requestmanager_fk_user_author (fk_user_author);
ALTER TABLE llx_requestmanager ADD INDEX idx_requestmanager_fk_user_modif (fk_user_modif);

ALTER TABLE llx_requestmanager ADD CONSTRAINT fk_requestmanager_fk_soc                FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_requestmanager ADD CONSTRAINT fk_requestmanager_fk_type               FOREIGN KEY (fk_type) REFERENCES llx_c_requestmanager_type (rowid);
ALTER TABLE llx_requestmanager ADD CONSTRAINT fk_requestmanager_fk_category           FOREIGN KEY (fk_category) REFERENCES llx_c_requestmanager_category (rowid);
ALTER TABLE llx_requestmanager ADD CONSTRAINT fk_requestmanager_fk_source             FOREIGN KEY (fk_source) REFERENCES llx_c_requestmanager_source (rowid);
ALTER TABLE llx_requestmanager ADD CONSTRAINT fk_requestmanager_fk_urgency            FOREIGN KEY (fk_urgency) REFERENCES llx_c_requestmanager_urgency (rowid);
ALTER TABLE llx_requestmanager ADD CONSTRAINT fk_requestmanager_fk_impact             FOREIGN KEY (fk_impact) REFERENCES llx_c_requestmanager_impact (rowid);
ALTER TABLE llx_requestmanager ADD CONSTRAINT fk_requestmanager_fk_priority           FOREIGN KEY (fk_priority) REFERENCES llx_c_requestmanager_priority (rowid);
ALTER TABLE llx_requestmanager ADD CONSTRAINT fk_requestmanager_fk_status             FOREIGN KEY (fk_status) REFERENCES llx_c_requestmanager_status (rowid);
ALTER TABLE llx_requestmanager ADD CONSTRAINT fk_requestmanager_fk_user_resolved      FOREIGN KEY (fk_user_resolved) REFERENCES llx_user (rowid);
ALTER TABLE llx_requestmanager ADD CONSTRAINT fk_requestmanager_fk_user_closed        FOREIGN KEY (fk_user_closed) REFERENCES llx_user (rowid);
ALTER TABLE llx_requestmanager ADD CONSTRAINT fk_requestmanager_fk_user_author        FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);
ALTER TABLE llx_requestmanager ADD CONSTRAINT fk_requestmanager_fk_user_modif         FOREIGN KEY (fk_user_modif) REFERENCES llx_user (rowid);
