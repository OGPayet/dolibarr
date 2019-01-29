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

ALTER TABLE llx_extendedintervention_survey_bloc ADD UNIQUE INDEX idx_ei_sb_unique (fk_fichinter, fk_equipment);
ALTER TABLE llx_extendedintervention_survey_bloc ADD INDEX idx_ei_sb_fk_fichinter (fk_fichinter);
ALTER TABLE llx_extendedintervention_survey_bloc ADD INDEX idx_ei_sb_fk_equipment (fk_equipment);
ALTER TABLE llx_extendedintervention_survey_bloc ADD INDEX idx_ei_sb_fk_product (fk_product);

ALTER TABLE llx_extendedintervention_survey_bloc ADD CONSTRAINT fk_ei_qb_fk_fichinter FOREIGN KEY (fk_fichinter) REFERENCES llx_fichinter (rowid); -- Trigger in bad location in v6 OK in v7+
