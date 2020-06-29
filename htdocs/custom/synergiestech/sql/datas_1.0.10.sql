-- ============================================================================
-- Copyright (C) 2020	 Alexis LAURIER 	 <contact@alexislaurier.fr>
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
DELETE FROM llx_c_action_trigger WHERE elementtype = 'synergiestech';
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_CREATE','Proposal created','Executed when a proposal is created','synergiestech',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_CREATE','Order created','Executed when an order is created','synergiestech',4);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_CREATE','Shipping created','Executed when a shipping is created','synergiestech',20);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_CREATE','Contract created','Executed when a contract is created','synergiestech',18);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_CREATE','Bill created','Executed when a bill is created','synergiestech',6);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_SUPPLIER_CREATE','Supplier proposal created','Executed when a supplier proposal is created','synergiestech',250);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_CREATE','Supplier proposal created','Executed when a supplier proposal is created','synergiestech',250);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_CREATE','Supplier Bill created','Executed when a supplier bill is created','synergiestech',15);