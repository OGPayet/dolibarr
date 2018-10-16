ALTER TABLE `llx_factorydet` ADD `fk_entrepot` INT NULL DEFAULT NULL COMMENT 'origin warehouse';

ALTER TABLE `llx_factorydet` DROP INDEX `uk_factorydet`, ADD UNIQUE KEY `uk_factorydet` (`fk_factory`,`fk_product`, `fk_entrepot`);