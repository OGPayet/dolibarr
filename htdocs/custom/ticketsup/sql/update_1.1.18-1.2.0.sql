ALTER TABLE llx_ticketsup ADD ref varchar(128) NOT NULL AFTER entity , ADD INDEX (ref);
