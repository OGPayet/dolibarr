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
--
-- ===========================================================================

ALTER TABLE llx_companyrelationships_availability ADD UNIQUE INDEX uk_companyrelationships_availability (fk_companyrelationships, fk_c_companyrelationships_availability);

ALTER TABLE llx_companyrelationships_availability ADD INDEX idx_companyrelationships_availability_fk_companyrelationships (fk_companyrelationships);

ALTER TABLE llx_companyrelationships_availability ADD CONSTRAINT fk_companyrelationships_availability_fk_companyrelationships FOREIGN KEY (fk_companyrelationships) REFERENCES llx_companyrelationships (rowid);
