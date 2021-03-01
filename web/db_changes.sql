ALTER TABLE `tbl_feasibility_report` ADD `existing_built_up_area` FLOAT NULL AFTER `plot_size`;
ALTER TABLE `tbl_feasibility_report` CHANGE `plot_size` `plot_size` FLOAT NULL DEFAULT NULL;
