CREATE TABLE IF NOT EXISTS `wa_admin_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `role_id` int(11) NOT NULL COMMENT 'Role ID',
  `admin_id` int(11) NOT NULL COMMENT 'Admin ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_admin_id` (`role_id`,`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Administrator–Role mapping table';

CREATE TABLE IF NOT EXISTS `wa_admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(32) NOT NULL COMMENT 'Username',
  `nickname` varchar(40) NOT NULL COMMENT 'Nickname',
  `password` varchar(255) NOT NULL COMMENT 'Password',
  `avatar` varchar(255) DEFAULT '/app/admin/avatar.png' COMMENT 'Avatar',
  `email` varchar(100) DEFAULT NULL COMMENT 'Email',
  `mobile` varchar(16) DEFAULT NULL COMMENT 'Mobile phone',
  `created_at` datetime DEFAULT NULL COMMENT 'Created at',
  `updated_at` datetime DEFAULT NULL COMMENT 'Updated at',
  `login_at` datetime DEFAULT NULL COMMENT 'Last login time',
  `status` tinyint(4) DEFAULT NULL COMMENT 'Disabled flag',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Administrators table';

CREATE TABLE IF NOT EXISTS `wa_options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Key',
  `value` longtext NOT NULL COMMENT 'Value',
  `created_at` datetime NOT NULL DEFAULT '2022-08-15 00:00:00' COMMENT 'Created at',
  `updated_at` datetime NOT NULL DEFAULT '2022-08-15 00:00:00' COMMENT 'Updated at',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Options table';

CREATE TABLE IF NOT EXISTS `wa_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `name` varchar(80) NOT NULL COMMENT 'Role name',
  `rules` text COMMENT 'Permissions',
  `created_at` datetime NOT NULL COMMENT 'Created at',
  `updated_at` datetime NOT NULL COMMENT 'Updated at',
  `pid` int(10) unsigned DEFAULT NULL COMMENT 'Parent ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Roles table';

CREATE TABLE IF NOT EXISTS `wa_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `title` varchar(255) NOT NULL COMMENT 'Title',
  `icon` varchar(255) DEFAULT NULL COMMENT 'Icon',
  `key` varchar(255) NOT NULL COMMENT 'Identifier',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Parent menu ID',
  `created_at` datetime NOT NULL COMMENT 'Created at',
  `updated_at` datetime NOT NULL COMMENT 'Updated at',
  `href` varchar(255) DEFAULT NULL COMMENT 'URL',
  `type` int(11) NOT NULL DEFAULT '1' COMMENT 'Type',
  `weight` int(11) DEFAULT '0' COMMENT 'Sort order',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Permissions rules table';

CREATE TABLE IF NOT EXISTS `wa_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `name` varchar(128) NOT NULL COMMENT 'File name',
  `url` varchar(255) NOT NULL COMMENT 'File URL',
  `admin_id` int(11) DEFAULT NULL COMMENT 'Admin ID',
  `file_size` int(11) NOT NULL COMMENT 'File size',
  `mime_type` varchar(255) NOT NULL COMMENT 'MIME type',
  `image_width` int(11) DEFAULT NULL COMMENT 'Image width',
  `image_height` int(11) DEFAULT NULL COMMENT 'Image height',
  `ext` varchar(128) NOT NULL COMMENT 'File extension',
  `storage` varchar(255) NOT NULL DEFAULT 'local' COMMENT 'Storage location',
  `created_at` date DEFAULT NULL COMMENT 'Upload date',
  `category` varchar(128) DEFAULT NULL COMMENT 'Category',
  `updated_at` date DEFAULT NULL COMMENT 'Updated date',
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `admin_id` (`admin_id`),
  KEY `name` (`name`),
  KEY `ext` (`ext`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='File uploads table';

CREATE TABLE IF NOT EXISTS `wa_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `username` varchar(32) NOT NULL COMMENT 'Username',
  `nickname` varchar(40) NOT NULL COMMENT 'Nickname',
  `password` varchar(255) NOT NULL COMMENT 'Password',
  `sex` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'Gender',
  `avatar` varchar(255) DEFAULT NULL COMMENT 'Avatar',
  `email` varchar(128) DEFAULT NULL COMMENT 'Email',
  `mobile` varchar(16) DEFAULT NULL COMMENT 'Mobile phone',
  `level` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Level',
  `birthday` date DEFAULT NULL COMMENT 'Birthday',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Balance (CNY)',
  `score` int(11) NOT NULL DEFAULT '0' COMMENT 'Points',
  `last_time` datetime DEFAULT NULL COMMENT 'Last login time',
  `last_ip` varchar(50) DEFAULT NULL COMMENT 'Last login IP',
  `join_time` datetime DEFAULT NULL COMMENT 'Registration time',
  `join_ip` varchar(50) DEFAULT NULL COMMENT 'Registration IP',
  `token` varchar(50) DEFAULT NULL COMMENT 'Token',
  `created_at` datetime DEFAULT NULL COMMENT 'Created at',
  `updated_at` datetime DEFAULT NULL COMMENT 'Updated at',
  `role` int(11) NOT NULL DEFAULT '1' COMMENT 'Role',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Disabled',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `join_time` (`join_time`),
  KEY `mobile` (`mobile`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Users table';

-- Insert data into wa_options
LOCK TABLES `wa_options` WRITE;
INSERT INTO `wa_options` (`id`, `name`, `value`, `created_at`, `updated_at`)
VALUES
  (1, 'system_config',
    '{"logo":{"title":"Webman Admin","image":"\/app\/admin\/admin\/images\/logo.png"},"menu":{"data":"\/app\/admin\/rule\/get","method":"GET","accordion":true,"collapse":false,"control":false,"controlWidth":500,"select":"0","async":true},"tab":{"enable":true,"keepState":true,"preload":false,"session":true,"max":"30","index":{"id":"0","href":"\/app\/admin\/index\/dashboard","title":"Dashboard"}},"theme":{"defaultColor":"2","defaultMenu":"light-theme","defaultHeader":"light-theme","allowCustom":true,"banner":false},"colors":[{"id":"1","color":"#36b368","second":"#f0f9eb"},{"id":"2","color":"#2d8cf0","second":"#ecf5ff"},{"id":"3","color":"#f6ad55","second":"#fdf6ec"},{"id":"4","color":"#f56c6c","second":"#fef0f0"},{"id":"5","color":"#3963bc","second":"#ecf5ff"}],"other":{"keepLoad":"500","autoHead":false,"footer":false},"header":{"message":false}}',
    '2022-12-05 14:49:01', '2022-12-08 20:20:28'),
  (2, 'table_form_schema_wa_users',
    '{"id":{"field":"id","_field_id":"0","comment":"Primary Key","control":"inputNumber","control_args":"","list_show":true,"enable_sort":true,"searchable":true,"search_type":"normal","form_show":false},"username":{"field":"username","_field_id":"1","comment":"username","control":"input","control_args":"","form_show":true,"list_show":true,"searchable":true,"search_type":"normal","enable_sort":false}, /* …giá trị JSON đầy đủ… */}',
    '2022-08-15 00:00:00', '2022-12-23 15:28:13'),
  (3, 'table_form_schema_wa_roles',
    '{"id":{"field":"id","_field_id":"0","comment":"Primary Key","control":"inputNumber","control_args":"","list_show":true,"search_type":"normal","form_show":false,"enable_sort":false,"searchable":false},"name":{"field":"name","_field_id":"1","comment":"Role Group","control":"input","control_args":"","form_show":true,"list_show":true,"search_type":"normal","enable_sort":false,"searchable":false}, /* … */}',
    '2022-08-15 00:00:00', '2022-12-19 14:24:25'),
  /* Các bản ghi INSERT cho table_form_schema_wa_rules, table_form_schema_wa_admins, table_form_schema_wa_options, table_form_schema_wa_uploads, dict_upload, dict_sex, dict_status, table_form_schema_wa_admin_roles, dict_dict_name tương tự như trên */
UNLOCK TABLES;

-- Insert data into wa_roles
LOCK TABLES `wa_roles` WRITE;
INSERT INTO `wa_roles` (`id`, `name`, `rules`, `created_at`, `updated_at`, `pid`)
VALUES
  (1, 'Super Administrator', '*', '2022-08-13 16:15:01', '2022-12-23 12:05:07', NULL);
UNLOCK TABLES;
