ALTER TABLE  `llx_product_factory`			ADD  `import_key`				VARCHAR( 14 ) NULL DEFAULT NULL;
ALTER TABLE  `llx_product_factory`			ADD  `extraparams`				VARCHAR(255);

ALTER TABLE  `llx_factory`			ADD  `import_key`				VARCHAR( 14 ) NULL DEFAULT NULL;
ALTER TABLE  `llx_factory`			ADD  `extraparams`				VARCHAR(255);

ALTER TABLE  `llx_equipement_factory`  	ADD  `children` 		INTEGER NOT NULL DEFAULT '0';		-- Nouveau champ pour l'ordre dans la liste des composants

--- Factory contact
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (251, 'factory', 'internal', 'FACTORYRESP', 'Responsable Production', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (252, 'factory', 'internal', 'INTERVENING', 'Intervenant', 1);

insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (241, 'factory', 'external', 'FACTORYRESP', 'Responsable Production', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (242, 'factory', 'exterrnal', 'INTERVENING', 'Intervenant', 1);

ALTER TABLE  `llx_factorydet`	  	ADD  `ordercomponent` 		INTEGER NOT NULL DEFAULT '0';		-- Nouveau champ pour l'ordre dans la liste des composants
ALTER TABLE  `llx_product_factory`	ADD  `ordercomponent` 		INTEGER NOT NULL DEFAULT '0';
ALTER TABLE  `llx_factorydet`		ADD  `globalqty` 			INTEGER NOT NULL DEFAULT '0';		-- La quantité est à prendre au détail ou au global
ALTER TABLE  `llx_factorydet`		ADD  `description`			text	;
ALTER TABLE  `llx_product_factory`	ADD  `globalqty` 			INTEGER NOT NULL DEFAULT '0';		-- La quantité est à prendre au détail ou au global
ALTER TABLE  `llx_product_factory`	ADD  `description`			text	;
ALTER TABLE  `llx_factory`			ADD  `entity`				Integer 		NOT NULL	DEFAULT 1;