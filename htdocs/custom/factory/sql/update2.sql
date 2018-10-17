ALTER TABLE `llx_factorydet` ADD `fk_entrepot` INT NULL DEFAULT NULL COMMENT 'origin warehouse';
ALTER TABLE `llx_factorydet` ADD `id_dispatched_line` INT NOT NULL DEFAULT 0 COMMENT 'id of dispatched line';

ALTER TABLE `llx_factorydet` DROP INDEX `uk_factorydet`, ADD UNIQUE KEY `uk_factorydet` (`fk_factory`, `fk_product`, `id_dispatched_line`);