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

ALTER TABLE llx_extendedintervention_question_bloc ADD UNIQUE INDEX idx_ei_qb_unique (fk_survey_bloc, fk_c_question_bloc);
ALTER TABLE llx_extendedintervention_question_bloc ADD INDEX idx_ei_qb_fk_survey_bloc (fk_survey_bloc);
ALTER TABLE llx_extendedintervention_question_bloc ADD INDEX idx_ei_qb_fk_c_question_bloc (fk_c_question_bloc);
ALTER TABLE llx_extendedintervention_question_bloc ADD INDEX idx_ei_qb_fk_c_question_bloc_status (fk_c_question_bloc_status);

ALTER TABLE llx_extendedintervention_question_bloc ADD CONSTRAINT fk_ei_qb_fk_survey_bloc FOREIGN KEY (fk_survey_bloc) REFERENCES llx_extendedintervention_survey_bloc (rowid);
ALTER TABLE llx_extendedintervention_question_bloc ADD CONSTRAINT fk_ei_qb_fk_c_question_bloc FOREIGN KEY (fk_c_question_bloc) REFERENCES llx_c_extendedintervention_question_bloc (rowid);
ALTER TABLE llx_extendedintervention_question_bloc ADD CONSTRAINT fk_ei_qb_fk_c_question_bloc_status FOREIGN KEY (fk_c_question_bloc_status) REFERENCES llx_c_extendedintervention_status_qb (rowid);
