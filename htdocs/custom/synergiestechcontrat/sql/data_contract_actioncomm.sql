-- Copyright (C)  SuperAdmin
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

-- INSERT INTO llx_myobject VALUES (
-- 	1, 1, 'mydata'
-- );

--------------------------------
-- Contracts events
--------------------------------
INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `color`, `picto`, `position`) VALUES
(12590, 'AC_STC_REVAL', 'systemauto', 'Contract revaluation (auto inserted events)', 'synergiestechcontrat', 1, NULL, NULL, NULL, 20),
(12591, 'AC_STC_CRENE', 'systemauto', 'Contract renewal (auto inserted events)', 'synergiestechcontrat', 1, NULL, NULL, NULL, 20),
(12592, 'AC_STC_RENEC', 'systemauto', 'Renewal of a contract (auto inserted events)', 'synergiestechcontrat', 1, NULL, NULL, NULL, 20),
(12593, 'AC_STC_TERMI', 'systemauto', 'Terminate contract (auto inserted events)', 'synergiestechcontrat', 1, NULL, NULL, NULL, 20);
