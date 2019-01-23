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

ALTER TABLE llx_extendedintervention_question_blocdet ADD UNIQUE INDEX idx_ei_qbdet_unique (fk_question_bloc, fk_c_question);
ALTER TABLE llx_extendedintervention_question_blocdet ADD INDEX idx_ei_qbdet_fk_question_bloc (fk_question_bloc);
ALTER TABLE llx_extendedintervention_question_blocdet ADD INDEX idx_ei_qbdet_fk_c_question (fk_c_question);
ALTER TABLE llx_extendedintervention_question_blocdet ADD INDEX idx_ei_qbdet_fk_c_answer (fk_c_answer);

ALTER TABLE llx_extendedintervention_question_blocdet ADD CONSTRAINT fk_ei_qbdet_fk_question_bloc FOREIGN KEY (fk_question_bloc) REFERENCES llx_extendedintervention_question_bloc (rowid);
ALTER TABLE llx_extendedintervention_question_blocdet ADD CONSTRAINT fk_ei_qbdet_fk_c_question FOREIGN KEY (fk_c_question) REFERENCES llx_c_extendedintervention_question (rowid);
ALTER TABLE llx_extendedintervention_question_blocdet ADD CONSTRAINT fk_ei_qbdet_fk_c_answer FOREIGN KEY (fk_c_answer) REFERENCES llx_c_extendedintervention_answer (rowid);
