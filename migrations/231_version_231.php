<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_231 extends App_module_migration {
    public function up() {
        // Perform database upgrade here
        ALTER TABLE `tblstaff` ADD `client_id` INT(11) NULL DEFAULT NULL, ADD INDEX `client_id` (`client_id`);
        ALTER TABLE `tblstaff` ADD `client_type` VARCHAR(50) NULL DEFAULT NULL AFTER `client_id`, ADD INDEX `client_type` (`client_type`);
        ALTER TABLE `tblclients`  ADD `is_company` tinyint(1) NULL DEFAULT NULL;
    }
}