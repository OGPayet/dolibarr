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

ALTER TABLE llx_extendedintervention_question_bloc ADD COLUMN color_status    varchar(10) NULL AFTER label_status; -- color of the question bloc status
ALTER TABLE llx_extendedintervention_question_blocdet ADD COLUMN color_answer varchar(10) NULL AFTER label_answer; -- color of the answer

ALTER TABLE llx_extendedintervention_question_bloc DROP FOREIGN KEY fk_ei_qb_fk_c_question_bloc;
ALTER TABLE llx_extendedintervention_question_bloc DROP FOREIGN KEY fk_ei_qb_fk_c_question_bloc_status;

ALTER TABLE llx_extendedintervention_question_blocdet DROP FOREIGN KEY fk_ei_qbdet_fk_c_question;
ALTER TABLE llx_extendedintervention_question_blocdet DROP FOREIGN KEY fk_ei_qbdet_fk_c_answer;
