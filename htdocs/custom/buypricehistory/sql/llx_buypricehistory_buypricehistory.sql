-- Copyright (C) ---Put here your own copyright and developer email---
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
-- along with this program.  If not, see https://www.gnu.org/licenses/.


CREATE TABLE llx_buypricehistory_buypricehistory(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_object integer NOT NULL, 
	entity integer DEFAULT 1 NOT NULL, 
	datec datetime, 
	original_datec datetime, 
	begin_date datetime, 
	end_date datetime NULL DEFAULT NULL,
	tms timestamp NOT NULL,
	original_tms timestamp NULL DEFAULT NULL,  
	fk_product integer, 
	fk_soc integer, 
	ref_fourn varchar(255), 
	desc_fourn text, 
	fk_availability integer, 
	price double(24,8), 
	quantity double, 
	remise_percent double NOT NULL, 
	remise double NOT NULL, 
	unitprice double(24,8), 
	charges double(24,8), 
	default_vat_code varchar(10), 
	tva_tx double(6,3) NOT NULL, 
	info_bits integer NOT NULL, 
	fk_user integer,
	original_fk_user integer,  
	fk_supplier_price_expression integer, 
	import_key varchar(14), 
	delivery_time_days integer, 
	supplier_reputation varchar(10), 
	fk_multicurrency integer, 
	multicurrency_code varchar(255), 
	multicurrency_tx double(24,8), 
	multicurrency_price double(24,8), 
	multicurrency_unitprice double(24,8), 
	localtax1_tx double(6,3), 
	localtax1_type varchar(10) NOT NULL, 
	localtax2_tx double(6,3), 
	localtax2_type varchar(10) NOT NULL, 
	barcode varchar(180), 
	fk_barcode_type integer, 
	packaging varchar(64)
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
