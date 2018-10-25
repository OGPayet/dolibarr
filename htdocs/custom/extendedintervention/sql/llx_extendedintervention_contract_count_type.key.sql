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

ALTER TABLE llx_extendedintervention_contract_count_type ADD UNIQUE INDEX uk_extendedintervention_cct (fk_contrat, fk_c_intervention_type);

ALTER TABLE llx_extendedintervention_contract_count_type ADD INDEX idx_extendedintervention_cct_fk_contrat (fk_contrat);
ALTER TABLE llx_extendedintervention_contract_count_type ADD INDEX idx_extendedintervention_cct_fk_c_intervention_type (fk_c_intervention_type);

ALTER TABLE llx_extendedintervention_contract_count_type ADD CONSTRAINT fk_extendedintervention_cct_fk_contrat              FOREIGN KEY (fk_contrat) REFERENCES llx_contrat (rowid);
ALTER TABLE llx_extendedintervention_contract_count_type ADD CONSTRAINT fk_extendedintervention_cct_fk_c_intervention_type  FOREIGN KEY (fk_c_intervention_type) REFERENCES llx_c_extendedintervention_type (rowid);
