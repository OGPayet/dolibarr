ALTER TABLE `llx_factorydet` ADD `indice_factory_build` INT NOT NULL DEFAULT 0 COMMENT 'indice of factory to build';

ALTER TABLE `llx_factorydet` DROP INDEX `uk_factorydet`, ADD UNIQUE KEY `uk_factorydet` (`fk_factory`, `fk_product`, `id_dispatched_line`, `indice_factory_build`);