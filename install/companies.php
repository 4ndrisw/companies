<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->table_exists(db_prefix() . 'companies')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "companies` (
      `userid` int NOT NULL,
      `company` varchar(191) DEFAULT NULL,
      `perusahaan` varchar(191) DEFAULT NULL,
      `upt_id` int DEFAULT NULL,
      `number_format` varchar(4) DEFAULT NULL,
      `prefix` varchar(10) DEFAULT NULL,
      `number` int NOT NULL DEFAULT '0',
      `is_preffered` tinyint(1) NOT NULL DEFAULT '0',
      `vat` varchar(50) DEFAULT NULL,
      `phonenumber` varchar(30) DEFAULT NULL,
      `phone` varchar(30) DEFAULT NULL,
      `country` int NOT NULL DEFAULT '0',
      `city` varchar(100) DEFAULT NULL,
      `zip` varchar(15) DEFAULT NULL,
      `state` varchar(50) DEFAULT NULL,
      `address` varchar(191) DEFAULT NULL,
      `website` varchar(150) DEFAULT NULL,
      `datecreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `dateactivated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `last_status_change` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `active` int NOT NULL DEFAULT '1',
      `status1` tinyint(1) DEFAULT NULL,
      `is_government` tinyint(1) NOT NULL DEFAULT '0',
      `is_institution` tinyint(1) NOT NULL DEFAULT '0',
      `is_inspector` tinyint(1) NOT NULL DEFAULT '0',
      `billing_street` varchar(200) DEFAULT NULL,
      `is_surveyor` tinyint(1) NOT NULL DEFAULT '0',
      `is_company` tinyint(1) DEFAULT '0',
      `is_ready` tinyint(1) NOT NULL DEFAULT '0',
      `institution_id` int DEFAULT NULL,
      `head_id` int DEFAULT NULL,
      `inspector_id` int DEFAULT NULL,
      `inspector_staff_id` int DEFAULT NULL,
      `bpjs_kesehatan` varchar(60) DEFAULT NULL,
      `bpjs_ketenagakerjaan` varchar(60) DEFAULT NULL,
      `siup` varchar(30) DEFAULT NULL,
      `hash` varchar(32) DEFAULT NULL,
      `addedfrom` int NOT NULL DEFAULT '0',
      `isedit` tinyint(1) NOT NULL DEFAULT '0',
      `leadid` int DEFAULT NULL,
      `billing_city` varchar(100) DEFAULT NULL,
      `billing_state` varchar(100) DEFAULT NULL,
      `billing_zip` varchar(100) DEFAULT NULL,
      `billing_country` int DEFAULT '0',
      `shipping_street` varchar(200) DEFAULT NULL,
      `shipping_city` varchar(100) DEFAULT NULL,
      `shipping_state` varchar(100) DEFAULT NULL,
      `shipping_zip` varchar(100) DEFAULT NULL,
      `shipping_country` int DEFAULT '0',
      `longitude` varchar(191) DEFAULT NULL,
      `latitude` varchar(191) DEFAULT NULL,
      `default_language` varchar(40) DEFAULT NULL,
      `default_currency` int NOT NULL DEFAULT '0',
      `show_primary_contact` int NOT NULL DEFAULT '0',
      `iis_perusahaan` tinyint(1) NOT NULL DEFAULT '0',
      `stripe_id` varchar(40) DEFAULT NULL,
      `registration_confirmed` int NOT NULL DEFAULT '1'
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'companies`
      ADD PRIMARY KEY (`userid`),
      ADD UNIQUE KEY `prefix_number` (`prefix`,`number`) USING BTREE,
      ADD UNIQUE KEY `userid_institution_id` (`userid`,`institution_id`) USING BTREE,
      ADD UNIQUE KEY `company` (`company`) USING BTREE,
      ADD KEY `active` (`active`),
      ADD KEY `is_company` (`is_company`),
      ADD KEY `is_institution` (`is_institution`),
      ADD KEY `is_inspector` (`is_inspector`),
      ADD KEY `institution_id` (`institution_id`),
      ADD KEY `inspector_staff_id` (`inspector_staff_id`),
      ADD KEY `is_government` (`is_government`),
      ADD KEY `head_id` (`head_id`),
      ADD KEY `inspector_id` (`inspector_id`),
      ADD KEY `is_surveyor` (`is_surveyor`)
      ;');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'companies`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'staff` ADD `is_primary` TINYINT(1) DEFAULT NULL, ADD INDEX `is_primary` (`is_primary`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'staff` ADD `title` VARCHAR(60) DEFAULT NULL;');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'staff` ADD `kelompok_pegawai_id` int(11) DEFAULT NULL;');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'staff` ADD `addedfrom` int(11) DEFAULT NULL;');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `title` DATETIME NULL DEFAULT NULL;');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `inspector_staff_id` int(11) DEFAULT NULL;');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `bpjs_kesehatan` VARCHAR(60) DEFAULT NULL;');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `bpjs_ketenagakerjaan` VARCHAR(60) DEFAULT NULL;');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'staff` CHANGE `active` `active` TINYINT NOT NULL DEFAULT "0";');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'staff` CHANGE `active` `active` TINYINT NOT NULL DEFAULT "0";');



}
