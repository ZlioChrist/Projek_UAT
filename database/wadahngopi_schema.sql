-- WadahNgopi schema-only export generated from the current Laravel migrations.
-- No cafe, customer, order, or other application records are included.
-- Import only into an empty database: this dump contains DROP TABLE statements.
-- The migrations table rows are Laravel migration metadata required by the app.
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `badges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(10) NOT NULL,
  `requirement_type` varchar(30) NOT NULL,
  `requirement_value` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `badges_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bookmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookmarks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `bookmarkable_id` bigint(20) unsigned NOT NULL,
  `bookmarkable_type` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookmark_unique` (`user_id`,`bookmarkable_id`,`bookmarkable_type`),
  KEY `bookmarks_user_id_bookmarkable_type_index` (`user_id`,`bookmarkable_type`),
  CONSTRAINT `bookmarks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumblob DEFAULT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cafes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cafes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `description` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `google_maps_url` text DEFAULT NULL,
  `whatsapp_number` varchar(255) DEFAULT NULL,
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `has_wifi` tinyint(1) NOT NULL DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL,
  `menu_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`menu_images`)),
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location` point DEFAULT NULL,
  `opening_time` time DEFAULT NULL,
  `closing_time` time DEFAULT NULL,
  `is_24_hours` tinyint(1) NOT NULL DEFAULT 0,
  `wfc_avg_score` decimal(3,2) NOT NULL DEFAULT 0.00,
  `wfc_review_count` int(10) unsigned NOT NULL DEFAULT 0,
  `current_vibe` varchar(10) DEFAULT NULL,
  `vibe_updated_at` timestamp NULL DEFAULT NULL,
  `operating_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`operating_hours`)),
  `official_is_open` tinyint(1) DEFAULT NULL,
  `official_status_text` varchar(32) DEFAULT NULL,
  `official_status_checked_at` timestamp NULL DEFAULT NULL,
  `city_id` bigint(20) unsigned DEFAULT NULL,
  `weekday_open` time DEFAULT NULL,
  `weekday_close` time DEFAULT NULL,
  `weekend_open` time DEFAULT NULL,
  `weekend_close` time DEFAULT NULL,
  `payment_qr_path` varchar(255) DEFAULT NULL,
  `payment_instructions` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cafes_slug_unique` (`slug`),
  KEY `cafes_status_index` (`status`),
  KEY `cafes_latitude_longitude_index` (`latitude`,`longitude`),
  KEY `cafes_owner_id_index` (`owner_id`),
  KEY `cafes_name_index` (`name`),
  KEY `cafes_city_id_foreign` (`city_id`),
  KEY `cafes_weekday_open_index` (`weekday_open`),
  KEY `cafes_weekday_close_index` (`weekday_close`),
  KEY `cafes_weekend_open_index` (`weekend_open`),
  KEY `cafes_weekend_close_index` (`weekend_close`),
  FULLTEXT KEY `cafes_name_address_description_fulltext` (`name`,`address`,`description`),
  CONSTRAINT `cafes_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cafes_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `check_ins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `check_ins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `cafe_id` bigint(20) unsigned NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `user_lat` decimal(10,8) DEFAULT NULL,
  `user_lng` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `check_ins_cafe_id_foreign` (`cafe_id`),
  KEY `check_ins_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `check_ins_user_id_cafe_id_index` (`user_id`,`cafe_id`),
  CONSTRAINT `check_ins_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `check_ins_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cities_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cafe_id` bigint(20) unsigned DEFAULT NULL,
  `roastery_id` bigint(20) unsigned DEFAULT NULL,
  `category` varchar(24) NOT NULL,
  `customer_name` varchar(120) NOT NULL,
  `customer_contact` varchar(120) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_feedback_cafe_id_foreign` (`cafe_id`),
  KEY `customer_feedback_roastery_id_foreign` (`roastery_id`),
  KEY `customer_feedback_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `customer_feedback_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_feedback_roastery_id_foreign` FOREIGN KEY (`roastery_id`) REFERENCES `roasteries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facilities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cafe_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `facilities_cafe_id_foreign` (`cafe_id`),
  CONSTRAINT `facilities_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `information`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `information` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'Berita',
  `source_name` varchar(255) DEFAULT NULL,
  `image_path` text DEFAULT NULL,
  `source_url` text DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `views` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `information_slug_unique` (`slug`),
  KEY `info_status_category_index` (`is_published`,`category`),
  KEY `information_is_published_index` (`is_published`),
  KEY `information_category_index` (`category`),
  KEY `information_published_at_index` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `name_snapshot` varchar(255) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_menu_item_id_index` (`order_id`,`product_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `reference` varchar(120) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(20) NOT NULL DEFAULT 'qris',
  `proof_path` varchar(255) DEFAULT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'submitted',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_payments_reference_unique` (`reference`),
  KEY `order_payments_verified_by_foreign` (`verified_by`),
  KEY `order_payments_order_id_status_index` (`order_id`,`status`),
  CONSTRAINT `order_payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_payments_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_status_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_status_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `from_status` varchar(32) DEFAULT NULL,
  `to_status` varchar(32) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_status_histories_changed_by_foreign` (`changed_by`),
  KEY `order_status_histories_order_id_created_at_index` (`order_id`,`created_at`),
  CONSTRAINT `order_status_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_status_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cafe_id` bigint(20) unsigned DEFAULT NULL,
  `roastery_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `public_code` varchar(20) NOT NULL,
  `idempotency_key` varchar(36) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'awaiting_payment',
  `payment_status` varchar(32) NOT NULL DEFAULT 'unpaid',
  `payment_method` varchar(20) NOT NULL DEFAULT 'qris',
  `customer_name` varchar(255) NOT NULL,
  `customer_whatsapp` varchar(32) DEFAULT NULL,
  `customer_note` text DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `preparing_at` timestamp NULL DEFAULT NULL,
  `ready_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_public_code_unique` (`public_code`),
  UNIQUE KEY `orders_idempotency_key_unique` (`idempotency_key`),
  KEY `orders_cafe_id_status_created_at_index` (`cafe_id`,`status`,`created_at`),
  KEY `orders_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `orders_roastery_id_foreign` (`roastery_id`),
  CONSTRAINT `orders_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`),
  CONSTRAINT `orders_roastery_id_foreign` FOREIGN KEY (`roastery_id`) REFERENCES `roasteries` (`id`),
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cafe_id` bigint(20) unsigned DEFAULT NULL,
  `roastery_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `stock` int(10) unsigned NOT NULL DEFAULT 0,
  `reserved_stock` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_items_cafe_id_is_active_index` (`cafe_id`,`is_active`),
  KEY `products_cafe_category_index` (`cafe_id`,`category`),
  KEY `products_roastery_id_is_active_index` (`roastery_id`,`is_active`),
  CONSTRAINT `menu_items_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`),
  CONSTRAINT `products_roastery_id_foreign` FOREIGN KEY (`roastery_id`) REFERENCES `roasteries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `receipt_print_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receipt_print_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `kind` varchar(20) NOT NULL DEFAULT 'automatic',
  `state` varchar(20) NOT NULL DEFAULT 'pending',
  `dedupe_key` varchar(100) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `printed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reported_by` bigint(20) unsigned DEFAULT NULL,
  `reported_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_print_jobs_dedupe_key_unique` (`dedupe_key`),
  KEY `receipt_print_jobs_requested_by_foreign` (`requested_by`),
  KEY `receipt_print_jobs_order_id_state_index` (`order_id`,`state`),
  KEY `receipt_print_jobs_reported_by_foreign` (`reported_by`),
  CONSTRAINT `receipt_print_jobs_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `receipt_print_jobs_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `receipt_print_jobs_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roasteries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roasteries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `city_id` bigint(20) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `google_maps_url` text DEFAULT NULL,
  `whatsapp_number` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `menu_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`menu_images`)),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location` point DEFAULT NULL,
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `owner_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_24_hours` tinyint(1) NOT NULL DEFAULT 0,
  `operating_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`operating_hours`)),
  `official_is_open` tinyint(1) DEFAULT NULL,
  `official_status_text` varchar(32) DEFAULT NULL,
  `official_status_checked_at` timestamp NULL DEFAULT NULL,
  `weekday_open` time DEFAULT NULL,
  `weekday_close` time DEFAULT NULL,
  `weekend_open` time DEFAULT NULL,
  `weekend_close` time DEFAULT NULL,
  `payment_qr_path` varchar(255) DEFAULT NULL,
  `payment_instructions` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roasteries_slug_unique` (`slug`),
  KEY `roasteries_city_id_foreign` (`city_id`),
  KEY `roasteries_status_index` (`status`),
  KEY `roasteries_latitude_longitude_index` (`latitude`,`longitude`),
  KEY `roasteries_owner_id_index` (`owner_id`),
  FULLTEXT KEY `roasteries_name_address_description_fulltext` (`name`,`address`,`description`),
  CONSTRAINT `roasteries_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `roasteries_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` mediumblob DEFAULT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tongkrongan_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tongkrongan_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tongkrongan_id` bigint(20) unsigned NOT NULL,
  `cafe_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tongkrongan_items_tongkrongan_id_cafe_id_unique` (`tongkrongan_id`,`cafe_id`),
  KEY `tongkrongan_items_cafe_id_foreign` (`cafe_id`),
  CONSTRAINT `tongkrongan_items_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tongkrongan_items_tongkrongan_id_foreign` FOREIGN KEY (`tongkrongan_id`) REFERENCES `tongkrongans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tongkrongan_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tongkrongan_votes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tongkrongan_item_id` bigint(20) unsigned NOT NULL,
  `voter_fingerprint` varchar(64) NOT NULL,
  `voter_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tongkrongan_vote_unique` (`tongkrongan_item_id`,`voter_fingerprint`),
  KEY `tongkrongan_votes_voter_user_id_foreign` (`voter_user_id`),
  CONSTRAINT `tongkrongan_votes_tongkrongan_item_id_foreign` FOREIGN KEY (`tongkrongan_item_id`) REFERENCES `tongkrongan_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tongkrongan_votes_voter_user_id_foreign` FOREIGN KEY (`voter_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tongkrongans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tongkrongans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `title` varchar(100) NOT NULL,
  `creator_fingerprint` varchar(64) DEFAULT NULL,
  `creator_user_id` bigint(20) unsigned DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tongkrongans_uuid_unique` (`uuid`),
  KEY `tongkrongans_creator_user_id_foreign` (`creator_user_id`),
  KEY `tongkrongans_expires_at_index` (`expires_at`),
  CONSTRAINT `tongkrongans_creator_user_id_foreign` FOREIGN KEY (`creator_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_badges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `badge_id` bigint(20) unsigned NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_badges_user_id_badge_id_unique` (`user_id`,`badge_id`),
  KEY `user_badges_badge_id_foreign` (`badge_id`),
  CONSTRAINT `user_badges_badge_id_foreign` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_badges_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'admin',
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vibe_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vibe_votes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cafe_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `level` enum('sepi','lumayan','rame','penuh') NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `user_lat` decimal(10,8) DEFAULT NULL,
  `user_lng` decimal(11,8) DEFAULT NULL,
  `fingerprint` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vibe_dedup_idx` (`cafe_id`,`fingerprint`,`created_at`),
  KEY `vibe_votes_user_id_foreign` (`user_id`),
  KEY `vibe_votes_cafe_id_created_at_index` (`cafe_id`,`created_at`),
  CONSTRAINT `vibe_votes_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vibe_votes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wfc_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wfc_scores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cafe_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `wifi_rating` tinyint(3) unsigned NOT NULL,
  `outlet_rating` tinyint(3) unsigned NOT NULL,
  `comfort_rating` tinyint(3) unsigned NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `user_lat` decimal(10,8) DEFAULT NULL,
  `user_lng` decimal(11,8) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wfc_scores_cafe_id_foreign` (`cafe_id`),
  KEY `wfc_scores_user_id_foreign` (`user_id`),
  CONSTRAINT `wfc_scores_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wfc_scores_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_01_20_044111_create_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_01_20_044112_add_soft_deletes_to_cafes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_01_20_044133_create_reviews_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_01_20_070130_add_coordinates_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_01_21_031753_add_role_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_01_21_031756_add_details_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_01_21_031809_create_facilities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_01_21_071830_add_images_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_01_22_024323_add_owner_and_status_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_01_22_032108_add_cafe_id_to_facilities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_01_23_050846_add_total_energy_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_01_23_050847_create_cafe_reactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_01_26_034548_remove_energy_feature_from_project',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_01_26_035812_create_information_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_01_26_035813_add_soft_deletes_to_information',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_01_28_110511_add_source_fields_to_information_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_01_28_130300_increase_image_path_length_in_information_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_01_28_141915_add_social_links_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_01_28_145646_add_menu_images_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_01_29_081515_add_indexes_to_performance_critical_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_01_29_141248_drop_menus_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_01_29_152607_remove_rating_from_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_01_29_153203_make_address_nullable_on_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_01_30_160646_add_name_index_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_02_02_034727_create_cities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_02_02_034728_add_city_id_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_02_03_072803_add_views_to_information_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_02_06_170000_add_flexible_operating_hours_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_02_07_090341_add_slug_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_02_07_092216_delete_reviews_table_and_update_user_role',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_02_07_174123_add_virtual_hours_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_02_08_043027_create_roasteries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_02_08_043028_add_soft_deletes_to_roasteries',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_02_08_172554_add_opening_hours_to_roasteries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_02_09_163812_add_menu_images_to_roasteries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_02_11_130356_fix_virtual_hours_expression_in_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_02_12_121021_migrate_legacy_opening_hours',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_02_16_175051_add_fulltext_indexes_to_searchable_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_02_23_131800_add_spatial_coordinates_to_cafes_and_roasteries',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_02_23_135656_add_status_index_to_roasteries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_02_23_174249_upgrade_cache_and_sessions_for_binary_data',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_03_05_132057_add_performance_indices_v2_to_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_04_16_002901_add_indexes_to_information_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_05_06_165723_create_wfc_scores_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_05_06_165731_add_wfc_columns_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_05_06_181758_create_vibe_votes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_05_06_181831_add_vibe_columns_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_05_06_181839_create_bookmarks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_05_06_181852_create_tongkrongans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_05_06_181912_create_tongkrongan_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_05_06_181945_create_tongkrongan_votes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_05_06_181947_create_badges_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_05_06_181957_create_user_badges_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_05_06_182012_create_check_ins_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_10_09_120001_create_menu_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_10_09_120002_add_payment_qr_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_10_09_120003_create_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_10_09_120004_create_order_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_10_09_120005_create_order_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_10_09_120006_create_order_status_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_10_09_120007_create_receipt_print_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_10_09_120008_align_products_with_capstone_erd',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_10_09_120009_add_print_result_audit_to_receipt_print_jobs',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_10_13_160000_add_category_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_10_24_000000_correct_roastery_listing_names',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_10_24_000001_fill_missing_cafe_listing_images',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_10_24_000002_add_official_status_to_cafes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_10_27_000001_add_official_status_to_roasteries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_10_27_000003_add_roastery_ordering_and_payment_methods',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_10_27_000004_create_customer_feedback_table',1);
