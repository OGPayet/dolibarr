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

--
-- Default public space availability to add in dictionnary
--
INSERT INTO llx_c_companyrelationships_publicspaceavailability (element, label, principal_availability, benefactor_availability, watcher_availability, active, entity) VALUES
('propal', 'Propositions Commerciales', 0, 0, 0, 1, 1),
('commande', 'Commandes', 0, 0, 0, 1, 1),
('facture', 'Factures', 0, 0, 0, 1, 1),
('shipping', 'Expeditions', 0, 0, 0, 1, 1),
('fichinter', 'Interventions', 0, 0, 0, 1, 1),
('contrat', 'Contrats', 0, 0, 0, 1, 1);

