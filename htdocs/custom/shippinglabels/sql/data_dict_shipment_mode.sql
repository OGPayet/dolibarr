-- add shipping method tnt
UPDATE llx_c_shipment_mode SET libelle = "TNT", description = "Thomas Nationwide Transport", tracking = "http://www.tnt.fr/public/suivi_colis/recherche/visubontransport.do?bonTransport={TRACKID}" WHERE `code`="TNT";
INSERT INTO llx_c_shipment_mode (`code`, `libelle`, `description`, `tracking`, `active`, `module`) values("TNT",'TNT',"Thomas Nationwide Transport","http://www.tnt.fr/public/suivi_colis/recherche/visubontransport.do?bonTransport={TRACKID}",0,NULL);

-- add shipping method MondialRelay
UPDATE llx_c_shipment_mode SET libelle = "Mondial Relay", description = "Mondial Relay", tracking = "http://www.mondialrelay.fr/suivi-de-colis/?numeroExpedition={TRACKID}&codePostal={ZIPCODE}" WHERE `code`="MR";
INSERT INTO llx_c_shipment_mode (`code`, `libelle`, `description`, `tracking`, `active`, `module`) values("MR","Mondial Relay",'Mondial Relay',"http://www.mondialrelay.fr/suivi-de-colis/?numeroExpedition={TRACKID}&codePostal={ZIPCODE}",0,NULL);

-- add tracking colissimo
UPDATE llx_c_shipment_mode SET `tracking`="https://www.laposte.fr/particulier/outils/suivre-vos-envois?code={TRACKID}" WHERE code='COLSUI';
-- Colissimo international
UPDATE llx_c_shipment_mode SET libelle = "Colissimo International", description = "Colissimo International", `tracking`="https://www.laposte.fr/particulier/outils/suivre-vos-envois?code={TRACKID}" WHERE code='COLINT';
INSERT INTO llx_c_shipment_mode (`code`, `libelle`, `description`, `tracking`, `active`, `module`) values("COLINT","Colissimo International",'Colissimo International',"https://www.laposte.fr/particulier/outils/suivre-vos-envois?code={TRACKID}",0,NULL);
-- Colissimo DOMTOM
UPDATE llx_c_shipment_mode SET libelle = "Colissimo DOM/TOM", description = "Colissimo DOM/TOM", `tracking`="https://www.laposte.fr/particulier/outils/suivre-vos-envois?code={TRACKID}" WHERE code='COLDOM';
INSERT INTO llx_c_shipment_mode (`code`, `libelle`, `description`, `tracking`, `active`, `module`) values("COLDOM","Colissimo DOM/TOM",'Colissimo DOM/TOM',"https://www.laposte.fr/particulier/outils/suivre-vos-envois?code={TRACKID}",0,NULL);

-- add shipping method DPD
UPDATE llx_c_shipment_mode SET libelle = "DPD", description = "DPD", `tracking`="http://www.dpd.fr/traces_{TRACKID}" WHERE `code`="DPD";
INSERT INTO llx_c_shipment_mode (`code`, `libelle`, `description`, `tracking`, `active`, `module`) values("DPD","DPD",'Direct Parcel Distribution',"http://www.dpd.fr/traces_{TRACKID}",0,NULL);

-- add shipping method GLS
UPDATE llx_c_shipment_mode SET libelle = "GLS", description = "General Logistics Systems", `tracking`="https://gls-group.eu/EU/en/parcel-tracking?match={TRACKID}" WHERE `code`="GLS";
INSERT INTO llx_c_shipment_mode (`code`, `libelle`, `description`, `tracking`, `active`, `module`) values("GLS","GLS",'General Logistics Systems',"https://gls-group.eu/EU/en/parcel-tracking?match={TRACKID}",0,NULL);

UPDATE llx_c_shipment_mode SET tracking ='http://www.csuivi.courrier.laposte.fr/suivi/index?id={TRACKID}' WHERE `code`="LETTREMAX";
