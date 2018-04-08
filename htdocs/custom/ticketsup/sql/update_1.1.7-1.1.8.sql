ALTER TABLE llx_ticketsup ADD fk_project INT NOT NULL DEFAULT 0 AFTER fk_soc , ADD INDEX (fk_project);
