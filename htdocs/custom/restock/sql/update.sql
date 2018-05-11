ALTER TABLE llx_commandedet ADD COLUMN fk_commandefourndet	integer DEFAULT NULL after import_key;
-- if something wrong with this value please use following script
-- ALTER TABLE llx_commandedet MODIFY COLUMN fk_commandefourndet integer DEFAULT NULL;