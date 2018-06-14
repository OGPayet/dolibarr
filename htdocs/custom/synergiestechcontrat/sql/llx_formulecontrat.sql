-- ========================================================================
-- Copyright (C) 2015  Alexandre Spangaro  <aspangaro@zendsi.com>
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
--
-- ========================================================================

CREATE TABLE IF NOT EXISTS llx_formulecontrat (
  rowid int(11) NOT NULL,
  entity int(11) NOT NULL,
  formule varchar(255) NOT NULL,
  frequence_de_facturation int(5) NOT NULL,
  status int(1) NOT NULL,
  date_creation datetime NOT NULL,
  tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  import_key varchar(14) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table llx_formulecontrat
--
ALTER TABLE llx_formulecontrat
  ADD PRIMARY KEY (rowid);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table llx_formulecontrat
--
ALTER TABLE llx_formulecontrat
  MODIFY rowid int(11) NOT NULL AUTO_INCREMENT;
