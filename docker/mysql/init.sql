CREATE DATABASE IF NOT EXISTS webman_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE webman_db;

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

CREATE TABLE IF NOT EXISTS orders (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id`    VARCHAR(64)    NOT NULL COMMENT 'ID từ client',
  `user_id`       BIGINT UNSIGNED NOT NULL COMMENT 'ID người chơi',
  `symbol`        VARCHAR(16)    NOT NULL,
  `side`          VARCHAR(8)     NOT NULL,
  `type`          VARCHAR(16)    NOT NULL,
  `time_in_force` VARCHAR(8)     NOT NULL,
  `price`         DECIMAL(20,8)  NOT NULL,
  `quantity`      DECIMAL(20,8)  NOT NULL,
  `timestamp`     BIGINT UNSIGNED NOT NULL,
  `pre_hash`      CHAR(64)       NOT NULL COMMENT 'SHA256 của bản ghi trước',
  `hash`          CHAR(64)       NOT NULL COMMENT 'SHA256(pre_hash‖timestamp‖price‖user_id‖quantity)',
  `status`        VARCHAR(16)    NOT NULL DEFAULT 'pending',
  `response`      TEXT           NULL,
  `created_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_request` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE orders 
ADD COLUMN volume DECIMAL(18, 8) COMMENT 'Khối lượng giao dịch';
ADD COLUMN leverage DECIMAL(5, 2) DEFAULT 1.00 COMMENT 'Hệ số đòn bẩy (mặc định 1x)';
ADD COLUMN updated_at timestamp COMMENT 'thời gian update';
ADD COLUMN closed_at timestamp COMMENT 'thời gian cloed';
ADD COLUMN cancelled_at timestamp COMMENT 'thời gian cancel';

ALTER TABLE `orders`
  ADD COLUMN `close_price`     DECIMAL(20,8)   NULL AFTER `response`,
  ADD COLUMN `close_quantity`  DECIMAL(20,8)   NULL AFTER `close_price`,
  ADD COLUMN `close_timestamp` BIGINT UNSIGNED NULL AFTER `close_quantity`;

-- 2) Tạo bản ghi “genesis” để có pre_hash cho các order sau
INSERT INTO `orders`
  (`request_id`,`user_id`,`symbol`,`side`,`type`,`time_in_force`,`price`,`quantity`,`timestamp`,`pre_hash`,`hash`,`status`)
VALUES
  ('genesis','0','GEN','NONE','NONE','NONE','0.00000000','0.00000000',UNIX_TIMESTAMP(), 
   REPEAT('0',64),  -- pre_hash ban đầu là 64 ký tự '0'
   SHA2(CONCAT(REPEAT('0',64), UNIX_TIMESTAMP(), '0.00000000', '0', '0.00000000'), 256),
   'genesis');

-- balance 
CREATE TABLE IF NOT EXISTS wallet (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       BIGINT UNSIGNED NOT NULL COMMENT 'Reference to users.id',
  `currency`      VARCHAR(8)       NOT NULL COMMENT 'e.g. BTC, USDT',
  `amount`        DECIMAL(28,8)    NOT NULL DEFAULT '0.00000000' COMMENT 'Available balance',
  `locked_amount` DECIMAL(28,8)    NOT NULL DEFAULT '0.00000000' COMMENT 'Balance locked in orders',
  `created_at`    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `wallet`
ADD CONSTRAINT `fk_wallet_user`
  FOREIGN KEY (`user_id`)
  REFERENCES `users` (`id`)
  ON UPDATE CASCADE
  ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `create_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation time',
  `creator` INT UNSIGNED NOT NULL COMMENT 'ID of user or system who created this record',
  `modify_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last modification time',
  `modifier` INT UNSIGNED NULL COMMENT 'ID of user or system who last modified this record',
  
  `tx_type` VARCHAR(32) NOT NULL COMMENT 'Transaction type (e.g. deposit, withdrawal, trade)',
  `currency` VARCHAR(16) NOT NULL COMMENT 'Currency code (e.g. BTC, ETH)',
  
  `tx_hash` VARCHAR(128) NOT NULL COMMENT 'Blockchain transaction hash',
  `pre_hash` VARCHAR(128) NULL COMMENT 'Hash of previous related transaction, if any',
  `status` ENUM('pending','confirmed','failed','cancelled') NOT NULL DEFAULT 'pending' COMMENT 'Current status',
  
  `amount` DECIMAL(32,16) NOT NULL COMMENT 'Amount transferred',
  `fee` DECIMAL(32,16) NOT NULL DEFAULT 0 COMMENT 'Network fee paid',
  
  `from_address` VARCHAR(128) NULL COMMENT 'Source blockchain address',
  `to_address` VARCHAR(128) NULL COMMENT 'Destination blockchain address',
  
  `block_height` BIGINT UNSIGNED NULL COMMENT 'Block number where tx was confirmed',
  `confirmations` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Number of confirmations',
  
  `memo` VARCHAR(255) NULL COMMENT 'Optional note or internal reference',
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tx_hash` (`tx_hash`),
  KEY `idx_creator` (`creator`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_status` (`status`),
  KEY `idx_currency` (`currency`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci
  COMMENT='Transaction ledger for crypto exchange';
INSERT INTO `transactions` (
  `create_time`,
  `creator`,
  `modify_time`,
  `modifier`,
  `tx_type`,
  `currency`,
  `tx_hash`,
  `pre_hash`,
  `status`,
  `amount`,
  `fee`,
  `from_address`,
  `to_address`,
  `block_height`,
  `confirmations`,
  `memo`
) VALUES (
  NOW(),           -- create_time
  1,               -- creator (ví dụ user_id = 1 hoặc system)
  NOW(),           -- modify_time
  1,               -- modifier
  'deposit',       -- tx_type
  'BTC',           -- currency
  '0000000000000000000769f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b',  
  NULL,            -- pre_hash (chưa có giao dịch trước)
  'pending',       -- status
  0.12345678,      -- amount
  0.00010000,      -- fee
  '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',  -- from_address
  '3J98t1WpEZ73CNmQviecrnyiWrnqRhWNLy',  -- to_address
  NULL,            -- block_height (chờ xác nhận)
  0,               -- confirmations
  'Initial deposit for user 1'  -- memo
);
