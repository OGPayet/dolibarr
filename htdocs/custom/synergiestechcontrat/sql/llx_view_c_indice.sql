DROP VIEW `llx_view_c_indice`;
CREATE VIEW `llx_view_c_indice` AS
    SELECT
            concat(`i`.`rowid`, '_Insee') AS `rowid`,
            `i`.`year_indice` AS `year_indice`,
            `i`.`month_indice` AS `month_indice`,
            `i`.`indice` AS `indice`,
        CONCAT(`i`.`year_indice`,'/',`i`.`month_indice`,' Insee') AS `label`,
            'Insee' AS `filter`
    FROM
            `llx_c_indice_insee` `i`
    WHERE
            (`i`.`active` = 1)
    UNION
    SELECT
            concat(`s`.`rowid`, '_Syntec') AS `rowid`,
            `s`.`year_indice` AS `year_indice`,
            `s`.`month_indice` AS `month_indice`,
            `s`.`indice` AS `indice`,
        CONCAT(`s`.`year_indice`,'/',`s`.`month_indice`,' Syntec') AS `label`,
            'Syntec' AS `filter`
    FROM
            `llx_c_indice_syntec` `s`
    WHERE
            (`s`.`active` = 1);
