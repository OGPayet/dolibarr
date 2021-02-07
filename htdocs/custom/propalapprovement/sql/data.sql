-- ============================================================================
-- Copyright (C) 2020   Alexis LAURIER      <contact@alexislaurier.fr>
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

DELETE FROM llx_c_propalst WHERE llx_c_propalst.id = 5;
INSERT INTO llx_c_propalst (id, code, label, active) VALUES ('5', 'PR_AWAIT', 'En attente d\'approbation', '1');
