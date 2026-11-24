-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 24 Nov 2026 pada 01.39
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wadahngopi_testing`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `badges`
--

CREATE TABLE `badges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(10) NOT NULL,
  `requirement_type` varchar(30) NOT NULL,
  `requirement_value` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `bookmarks`
--

CREATE TABLE `bookmarks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `bookmarkable_id` bigint(20) UNSIGNED NOT NULL,
  `bookmarkable_type` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumblob DEFAULT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cafes`
--

CREATE TABLE `cafes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner_id` bigint(20) UNSIGNED DEFAULT NULL,
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
  `wfc_review_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `current_vibe` varchar(10) DEFAULT NULL,
  `vibe_updated_at` timestamp NULL DEFAULT NULL,
  `operating_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`operating_hours`)),
  `official_is_open` tinyint(1) DEFAULT NULL,
  `official_status_text` varchar(32) DEFAULT NULL,
  `official_status_checked_at` timestamp NULL DEFAULT NULL,
  `city_id` bigint(20) UNSIGNED DEFAULT NULL,
  `weekday_open` time DEFAULT NULL,
  `weekday_close` time DEFAULT NULL,
  `weekend_open` time DEFAULT NULL,
  `weekend_close` time DEFAULT NULL,
  `payment_qr_path` varchar(255) DEFAULT NULL,
  `payment_instructions` text DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_account_name` varchar(120) DEFAULT NULL,
  `delivery_base_fee` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_fee_per_km` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cafe_location_points`
--

CREATE TABLE `cafe_location_points` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `google_maps_url` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `check_ins`
--

CREATE TABLE `check_ins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `cafe_id` bigint(20) UNSIGNED NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `user_lat` decimal(10,8) DEFAULT NULL,
  `user_lng` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cities`
--

CREATE TABLE `cities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `customer_feedback`
--

CREATE TABLE `customer_feedback` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cafe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `roastery_id` bigint(20) UNSIGNED DEFAULT NULL,
  `category` varchar(24) NOT NULL,
  `customer_name` varchar(120) NOT NULL,
  `customer_contact` varchar(120) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `facilities`
--

CREATE TABLE `facilities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cafe_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `information`
--

CREATE TABLE `information` (
  `id` bigint(20) UNSIGNED NOT NULL,
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
  `views` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `job_batches`
--

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
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `merchant_applications`
--

CREATE TABLE `merchant_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `business_type` varchar(20) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `city_id` bigint(20) UNSIGNED NOT NULL,
  `address` text NOT NULL,
  `whatsapp_number` varchar(32) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `review_notes` text DEFAULT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_01_20_044111_create_cafes_table', 1),
(5, '2026_01_20_044112_add_soft_deletes_to_cafes', 1),
(6, '2026_01_20_044133_create_reviews_table', 1),
(7, '2026_01_20_070130_add_coordinates_to_cafes_table', 1),
(8, '2026_01_21_031753_add_role_to_users_table', 1),
(9, '2026_01_21_031756_add_details_to_cafes_table', 1),
(10, '2026_01_21_031809_create_facilities_table', 1),
(11, '2026_01_21_071830_add_images_to_cafes_table', 1),
(12, '2026_01_22_024323_add_owner_and_status_to_cafes_table', 1),
(13, '2026_01_22_032108_add_cafe_id_to_facilities_table', 1),
(14, '2026_01_23_050846_add_total_energy_to_cafes_table', 1),
(15, '2026_01_23_050847_create_cafe_reactions_table', 1),
(16, '2026_01_26_034548_remove_energy_feature_from_project', 1),
(17, '2026_01_26_035812_create_information_table', 1),
(18, '2026_01_26_035813_add_soft_deletes_to_information', 1),
(19, '2026_01_28_110511_add_source_fields_to_information_table', 1),
(20, '2026_01_28_130300_increase_image_path_length_in_information_table', 1),
(21, '2026_01_28_141915_add_social_links_to_cafes_table', 1),
(22, '2026_01_28_145646_add_menu_images_to_cafes_table', 1),
(23, '2026_01_29_081515_add_indexes_to_performance_critical_tables', 1),
(24, '2026_01_29_141248_drop_menus_table', 1),
(25, '2026_01_29_152607_remove_rating_from_cafes_table', 1),
(26, '2026_01_29_153203_make_address_nullable_on_cafes_table', 1),
(27, '2026_01_30_160646_add_name_index_to_cafes_table', 1),
(28, '2026_02_02_034727_create_cities_table', 1),
(29, '2026_02_02_034728_add_city_id_to_cafes_table', 1),
(30, '2026_02_03_072803_add_views_to_information_table', 1),
(31, '2026_02_06_170000_add_flexible_operating_hours_to_cafes_table', 1),
(32, '2026_02_07_090341_add_slug_to_cafes_table', 1),
(33, '2026_02_07_092216_delete_reviews_table_and_update_user_role', 1),
(34, '2026_02_07_174123_add_virtual_hours_to_cafes_table', 1),
(35, '2026_02_08_043027_create_roasteries_table', 1),
(36, '2026_02_08_043028_add_soft_deletes_to_roasteries', 1),
(37, '2026_02_08_172554_add_opening_hours_to_roasteries_table', 1),
(38, '2026_02_09_163812_add_menu_images_to_roasteries_table', 1),
(39, '2026_02_11_130356_fix_virtual_hours_expression_in_cafes_table', 1),
(40, '2026_02_12_121021_migrate_legacy_opening_hours', 1),
(41, '2026_02_16_175051_add_fulltext_indexes_to_searchable_tables', 1),
(42, '2026_02_23_131800_add_spatial_coordinates_to_cafes_and_roasteries', 1),
(43, '2026_02_23_135656_add_status_index_to_roasteries_table', 1),
(44, '2026_02_23_174249_upgrade_cache_and_sessions_for_binary_data', 1),
(45, '2026_03_05_132057_add_performance_indices_v2_to_tables', 1),
(46, '2026_04_16_002901_add_indexes_to_information_table', 1),
(47, '2026_05_06_165723_create_wfc_scores_table', 1),
(48, '2026_05_06_165731_add_wfc_columns_to_cafes_table', 1),
(49, '2026_05_06_181758_create_vibe_votes_table', 1),
(50, '2026_05_06_181831_add_vibe_columns_to_cafes_table', 1),
(51, '2026_05_06_181839_create_bookmarks_table', 1),
(52, '2026_05_06_181852_create_tongkrongans_table', 1),
(53, '2026_05_06_181912_create_tongkrongan_items_table', 1),
(54, '2026_05_06_181945_create_tongkrongan_votes_table', 1),
(55, '2026_05_06_181947_create_badges_table', 1),
(56, '2026_05_06_181957_create_user_badges_table', 1),
(57, '2026_05_06_182012_create_check_ins_table', 1),
(58, '2026_10_09_120001_create_menu_items_table', 1),
(59, '2026_10_09_120002_add_payment_qr_to_cafes_table', 1),
(60, '2026_10_09_120003_create_orders_table', 1),
(61, '2026_10_09_120004_create_order_items_table', 1),
(62, '2026_10_09_120005_create_order_payments_table', 1),
(63, '2026_10_09_120006_create_order_status_histories_table', 1),
(64, '2026_10_09_120007_create_receipt_print_jobs_table', 1),
(65, '2026_10_09_120008_align_products_with_capstone_erd', 1),
(66, '2026_10_09_120009_add_print_result_audit_to_receipt_print_jobs', 1),
(67, '2026_10_13_160000_add_category_to_products_table', 1),
(68, '2026_10_24_000000_correct_roastery_listing_names', 1),
(69, '2026_10_24_000001_fill_missing_cafe_listing_images', 1),
(70, '2026_10_24_000002_add_official_status_to_cafes_table', 1),
(71, '2026_10_27_000001_add_official_status_to_roasteries_table', 1),
(72, '2026_10_27_000003_add_roastery_ordering_and_payment_methods', 1),
(73, '2026_10_27_000004_create_customer_feedback_table', 1),
(74, '2026_11_01_000000_create_app_settings_table', 1),
(75, '2026_11_01_000100_remove_unused_app_settings_table', 1),
(76, '2026_11_01_000200_add_owner_profiles_and_customer_notifications', 1),
(77, '2026_11_01_000300_create_merchant_applications_table', 1),
(78, '2026_11_06_000000_add_bank_transfer_details_to_shops', 2),
(79, '2026_11_08_000001_add_order_id_to_customer_feedback_table', 2),
(80, '2026_11_09_000001_add_sender_bank_to_orders_table', 2),
(81, '2026_11_12_000001_add_payment_expiration_to_orders_table', 2),
(82, '2026_11_19_000001_backfill_cash_order_reservation_expiration', 2),
(83, '2026_11_20_000001_add_google_and_whatsapp_authentication_to_users_table', 2),
(84, '2026_11_20_000003_create_cafe_location_points_table', 2),
(85, '2026_11_20_000004_add_address_to_cafe_location_points_table', 2),
(86, '2026_11_21_000001_create_product_addons_table', 2),
(87, '2026_11_23_000001_add_fulfillment_and_receipt_confirmation_to_orders', 2),
(88, '2026_11_23_000002_add_delivery_tariffs_and_distance_to_orders', 2),
(89, '2026_11_23_000003_add_display_codes_to_orders', 2);

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cafe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `roastery_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `public_code` varchar(20) NOT NULL,
  `idempotency_key` varchar(36) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'awaiting_payment',
  `payment_status` varchar(32) NOT NULL DEFAULT 'unpaid',
  `payment_method` varchar(20) NOT NULL DEFAULT 'qris',
  `fulfillment_method` varchar(16) NOT NULL DEFAULT 'pickup',
  `delivery_address` text DEFAULT NULL,
  `delivery_distance_km` decimal(8,2) DEFAULT NULL,
  `delivery_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `delivery_fee_confirmed_at` timestamp NULL DEFAULT NULL,
  `payment_expires_at` timestamp NULL DEFAULT NULL,
  `sender_bank` varchar(40) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_whatsapp` varchar(32) DEFAULT NULL,
  `customer_note` text DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `preparing_at` timestamp NULL DEFAULT NULL,
  `ready_at` timestamp NULL DEFAULT NULL,
  `delivery_started_at` timestamp NULL DEFAULT NULL,
  `receipt_confirmation_deadline_started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `display_code` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_display_code_sequences`
--

CREATE TABLE `order_display_code_sequences` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `next_value` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `order_display_code_sequences`
--

INSERT INTO `order_display_code_sequences` (`id`, `next_value`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `parent_order_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name_snapshot` varchar(255) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_payments`
--

CREATE TABLE `order_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `reference` varchar(120) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(20) NOT NULL DEFAULT 'qris',
  `proof_path` varchar(255) DEFAULT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'submitted',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_status_histories`
--

CREATE TABLE `order_status_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `changed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `from_status` varchar(32) DEFAULT NULL,
  `to_status` varchar(32) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cafe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `roastery_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `reserved_stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `product_addons`
--

CREATE TABLE `product_addons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_product_id` bigint(20) UNSIGNED NOT NULL,
  `addon_product_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `receipt_print_jobs`
--

CREATE TABLE `receipt_print_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `kind` varchar(20) NOT NULL DEFAULT 'automatic',
  `state` varchar(20) NOT NULL DEFAULT 'pending',
  `dedupe_key` varchar(100) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `requested_by` bigint(20) UNSIGNED DEFAULT NULL,
  `printed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reported_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reported_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `roasteries`
--

CREATE TABLE `roasteries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `city_id` bigint(20) UNSIGNED NOT NULL,
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
  `owner_id` bigint(20) UNSIGNED NOT NULL,
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
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_account_name` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` mediumblob DEFAULT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tongkrongans`
--

CREATE TABLE `tongkrongans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `title` varchar(100) NOT NULL,
  `creator_fingerprint` varchar(64) DEFAULT NULL,
  `creator_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tongkrongan_items`
--

CREATE TABLE `tongkrongan_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tongkrongan_id` bigint(20) UNSIGNED NOT NULL,
  `cafe_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tongkrongan_votes`
--

CREATE TABLE `tongkrongan_votes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tongkrongan_item_id` bigint(20) UNSIGNED NOT NULL,
  `voter_fingerprint` varchar(64) NOT NULL,
  `voter_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'admin',
  `password` varchar(255) NOT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_badges`
--

CREATE TABLE `user_badges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `badge_id` bigint(20) UNSIGNED NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `vibe_votes`
--

CREATE TABLE `vibe_votes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cafe_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `level` enum('sepi','lumayan','rame','penuh') NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `user_lat` decimal(10,8) DEFAULT NULL,
  `user_lng` decimal(11,8) DEFAULT NULL,
  `fingerprint` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `wfc_scores`
--

CREATE TABLE `wfc_scores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cafe_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `wifi_rating` tinyint(3) UNSIGNED NOT NULL,
  `outlet_rating` tinyint(3) UNSIGNED NOT NULL,
  `comfort_rating` tinyint(3) UNSIGNED NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `user_lat` decimal(10,8) DEFAULT NULL,
  `user_lng` decimal(11,8) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `badges_slug_unique` (`slug`);

--
-- Indeks untuk tabel `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bookmark_unique` (`user_id`,`bookmarkable_id`,`bookmarkable_type`),
  ADD KEY `bookmarks_user_id_bookmarkable_type_index` (`user_id`,`bookmarkable_type`);

--
-- Indeks untuk tabel `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indeks untuk tabel `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indeks untuk tabel `cafes`
--
ALTER TABLE `cafes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cafes_slug_unique` (`slug`),
  ADD KEY `cafes_status_index` (`status`),
  ADD KEY `cafes_latitude_longitude_index` (`latitude`,`longitude`),
  ADD KEY `cafes_owner_id_index` (`owner_id`),
  ADD KEY `cafes_name_index` (`name`),
  ADD KEY `cafes_city_id_foreign` (`city_id`),
  ADD KEY `cafes_weekday_open_index` (`weekday_open`),
  ADD KEY `cafes_weekday_close_index` (`weekday_close`),
  ADD KEY `cafes_weekend_open_index` (`weekend_open`),
  ADD KEY `cafes_weekend_close_index` (`weekend_close`);
ALTER TABLE `cafes` ADD FULLTEXT KEY `cafes_name_address_description_fulltext` (`name`,`address`,`description`);

--
-- Indeks untuk tabel `cafe_location_points`
--
ALTER TABLE `cafe_location_points`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cafe_location_points_slug_unique` (`slug`),
  ADD KEY `cafe_location_points_latitude_longitude_index` (`latitude`,`longitude`);

--
-- Indeks untuk tabel `check_ins`
--
ALTER TABLE `check_ins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `check_ins_cafe_id_foreign` (`cafe_id`),
  ADD KEY `check_ins_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `check_ins_user_id_cafe_id_index` (`user_id`,`cafe_id`);

--
-- Indeks untuk tabel `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cities_slug_unique` (`slug`);

--
-- Indeks untuk tabel `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_feedback_order_id_unique` (`order_id`),
  ADD KEY `customer_feedback_cafe_id_foreign` (`cafe_id`),
  ADD KEY `customer_feedback_roastery_id_foreign` (`roastery_id`),
  ADD KEY `customer_feedback_status_created_at_index` (`status`,`created_at`);

--
-- Indeks untuk tabel `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `facilities_cafe_id_foreign` (`cafe_id`);

--
-- Indeks untuk tabel `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indeks untuk tabel `information`
--
ALTER TABLE `information`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `information_slug_unique` (`slug`),
  ADD KEY `info_status_category_index` (`is_published`,`category`),
  ADD KEY `information_is_published_index` (`is_published`),
  ADD KEY `information_category_index` (`category`),
  ADD KEY `information_published_at_index` (`published_at`);

--
-- Indeks untuk tabel `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indeks untuk tabel `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `merchant_applications`
--
ALTER TABLE `merchant_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `merchant_applications_user_id_foreign` (`user_id`),
  ADD KEY `merchant_applications_city_id_foreign` (`city_id`),
  ADD KEY `merchant_applications_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `merchant_applications_status_created_at_index` (`status`,`created_at`);

--
-- Indeks untuk tabel `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_public_code_unique` (`public_code`),
  ADD UNIQUE KEY `orders_idempotency_key_unique` (`idempotency_key`),
  ADD UNIQUE KEY `orders_display_code_unique` (`display_code`),
  ADD KEY `orders_cafe_id_status_created_at_index` (`cafe_id`,`status`,`created_at`),
  ADD KEY `orders_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `orders_roastery_id_foreign` (`roastery_id`),
  ADD KEY `orders_status_payment_expires_at_index` (`status`,`payment_expires_at`),
  ADD KEY `orders_receipt_confirmation_due_idx` (`status`,`receipt_confirmation_deadline_started_at`);

--
-- Indeks untuk tabel `order_display_code_sequences`
--
ALTER TABLE `order_display_code_sequences`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_menu_item_id_index` (`order_id`,`product_id`),
  ADD KEY `order_items_product_id_foreign` (`product_id`),
  ADD KEY `order_items_parent_order_item_id_foreign` (`parent_order_item_id`),
  ADD KEY `order_items_order_id_parent_order_item_id_index` (`order_id`,`parent_order_item_id`);

--
-- Indeks untuk tabel `order_payments`
--
ALTER TABLE `order_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_payments_reference_unique` (`reference`),
  ADD KEY `order_payments_verified_by_foreign` (`verified_by`),
  ADD KEY `order_payments_order_id_status_index` (`order_id`,`status`);

--
-- Indeks untuk tabel `order_status_histories`
--
ALTER TABLE `order_status_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_status_histories_changed_by_foreign` (`changed_by`),
  ADD KEY `order_status_histories_order_id_created_at_index` (`order_id`,`created_at`);

--
-- Indeks untuk tabel `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_items_cafe_id_is_active_index` (`cafe_id`,`is_active`),
  ADD KEY `products_cafe_category_index` (`cafe_id`,`category`),
  ADD KEY `products_roastery_id_is_active_index` (`roastery_id`,`is_active`);

--
-- Indeks untuk tabel `product_addons`
--
ALTER TABLE `product_addons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_addons_parent_product_id_addon_product_id_unique` (`parent_product_id`,`addon_product_id`),
  ADD KEY `product_addons_addon_product_id_foreign` (`addon_product_id`);

--
-- Indeks untuk tabel `receipt_print_jobs`
--
ALTER TABLE `receipt_print_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_print_jobs_dedupe_key_unique` (`dedupe_key`),
  ADD KEY `receipt_print_jobs_requested_by_foreign` (`requested_by`),
  ADD KEY `receipt_print_jobs_order_id_state_index` (`order_id`,`state`),
  ADD KEY `receipt_print_jobs_reported_by_foreign` (`reported_by`);

--
-- Indeks untuk tabel `roasteries`
--
ALTER TABLE `roasteries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roasteries_slug_unique` (`slug`),
  ADD KEY `roasteries_city_id_foreign` (`city_id`),
  ADD KEY `roasteries_status_index` (`status`),
  ADD KEY `roasteries_latitude_longitude_index` (`latitude`,`longitude`),
  ADD KEY `roasteries_owner_id_index` (`owner_id`);
ALTER TABLE `roasteries` ADD FULLTEXT KEY `roasteries_name_address_description_fulltext` (`name`,`address`,`description`);

--
-- Indeks untuk tabel `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indeks untuk tabel `tongkrongans`
--
ALTER TABLE `tongkrongans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tongkrongans_uuid_unique` (`uuid`),
  ADD KEY `tongkrongans_creator_user_id_foreign` (`creator_user_id`),
  ADD KEY `tongkrongans_expires_at_index` (`expires_at`);

--
-- Indeks untuk tabel `tongkrongan_items`
--
ALTER TABLE `tongkrongan_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tongkrongan_items_tongkrongan_id_cafe_id_unique` (`tongkrongan_id`,`cafe_id`),
  ADD KEY `tongkrongan_items_cafe_id_foreign` (`cafe_id`);

--
-- Indeks untuk tabel `tongkrongan_votes`
--
ALTER TABLE `tongkrongan_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tongkrongan_vote_unique` (`tongkrongan_item_id`,`voter_fingerprint`),
  ADD KEY `tongkrongan_votes_voter_user_id_foreign` (`voter_user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_google_id_unique` (`google_id`),
  ADD UNIQUE KEY `users_phone_number_unique` (`phone_number`);

--
-- Indeks untuk tabel `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_badges_user_id_badge_id_unique` (`user_id`,`badge_id`),
  ADD KEY `user_badges_badge_id_foreign` (`badge_id`);

--
-- Indeks untuk tabel `vibe_votes`
--
ALTER TABLE `vibe_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vibe_dedup_idx` (`cafe_id`,`fingerprint`,`created_at`),
  ADD KEY `vibe_votes_user_id_foreign` (`user_id`),
  ADD KEY `vibe_votes_cafe_id_created_at_index` (`cafe_id`,`created_at`);

--
-- Indeks untuk tabel `wfc_scores`
--
ALTER TABLE `wfc_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wfc_scores_cafe_id_foreign` (`cafe_id`),
  ADD KEY `wfc_scores_user_id_foreign` (`user_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `badges`
--
ALTER TABLE `badges`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `bookmarks`
--
ALTER TABLE `bookmarks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cafes`
--
ALTER TABLE `cafes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `cafe_location_points`
--
ALTER TABLE `cafe_location_points`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `check_ins`
--
ALTER TABLE `check_ins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cities`
--
ALTER TABLE `cities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `customer_feedback`
--
ALTER TABLE `customer_feedback`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `facilities`
--
ALTER TABLE `facilities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `information`
--
ALTER TABLE `information`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `merchant_applications`
--
ALTER TABLE `merchant_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `order_payments`
--
ALTER TABLE `order_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `order_status_histories`
--
ALTER TABLE `order_status_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `product_addons`
--
ALTER TABLE `product_addons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `receipt_print_jobs`
--
ALTER TABLE `receipt_print_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `roasteries`
--
ALTER TABLE `roasteries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `tongkrongans`
--
ALTER TABLE `tongkrongans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tongkrongan_items`
--
ALTER TABLE `tongkrongan_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tongkrongan_votes`
--
ALTER TABLE `tongkrongan_votes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `vibe_votes`
--
ALTER TABLE `vibe_votes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `wfc_scores`
--
ALTER TABLE `wfc_scores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD CONSTRAINT `bookmarks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `cafes`
--
ALTER TABLE `cafes`
  ADD CONSTRAINT `cafes_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cafes_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `check_ins`
--
ALTER TABLE `check_ins`
  ADD CONSTRAINT `check_ins_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `check_ins_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD CONSTRAINT `customer_feedback_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_feedback_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customer_feedback_roastery_id_foreign` FOREIGN KEY (`roastery_id`) REFERENCES `roasteries` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `facilities`
--
ALTER TABLE `facilities`
  ADD CONSTRAINT `facilities_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `merchant_applications`
--
ALTER TABLE `merchant_applications`
  ADD CONSTRAINT `merchant_applications_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`),
  ADD CONSTRAINT `merchant_applications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `merchant_applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`),
  ADD CONSTRAINT `orders_roastery_id_foreign` FOREIGN KEY (`roastery_id`) REFERENCES `roasteries` (`id`),
  ADD CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_parent_order_item_id_foreign` FOREIGN KEY (`parent_order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `order_payments`
--
ALTER TABLE `order_payments`
  ADD CONSTRAINT `order_payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_payments_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `order_status_histories`
--
ALTER TABLE `order_status_histories`
  ADD CONSTRAINT `order_status_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_status_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `menu_items_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`),
  ADD CONSTRAINT `products_roastery_id_foreign` FOREIGN KEY (`roastery_id`) REFERENCES `roasteries` (`id`);

--
-- Ketidakleluasaan untuk tabel `product_addons`
--
ALTER TABLE `product_addons`
  ADD CONSTRAINT `product_addons_addon_product_id_foreign` FOREIGN KEY (`addon_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_addons_parent_product_id_foreign` FOREIGN KEY (`parent_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `receipt_print_jobs`
--
ALTER TABLE `receipt_print_jobs`
  ADD CONSTRAINT `receipt_print_jobs_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `receipt_print_jobs_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `receipt_print_jobs_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `roasteries`
--
ALTER TABLE `roasteries`
  ADD CONSTRAINT `roasteries_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `roasteries_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tongkrongans`
--
ALTER TABLE `tongkrongans`
  ADD CONSTRAINT `tongkrongans_creator_user_id_foreign` FOREIGN KEY (`creator_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `tongkrongan_items`
--
ALTER TABLE `tongkrongan_items`
  ADD CONSTRAINT `tongkrongan_items_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tongkrongan_items_tongkrongan_id_foreign` FOREIGN KEY (`tongkrongan_id`) REFERENCES `tongkrongans` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tongkrongan_votes`
--
ALTER TABLE `tongkrongan_votes`
  ADD CONSTRAINT `tongkrongan_votes_tongkrongan_item_id_foreign` FOREIGN KEY (`tongkrongan_item_id`) REFERENCES `tongkrongan_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tongkrongan_votes_voter_user_id_foreign` FOREIGN KEY (`voter_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `user_badges_badge_id_foreign` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_badges_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `vibe_votes`
--
ALTER TABLE `vibe_votes`
  ADD CONSTRAINT `vibe_votes_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vibe_votes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `wfc_scores`
--
ALTER TABLE `wfc_scores`
  ADD CONSTRAINT `wfc_scores_cafe_id_foreign` FOREIGN KEY (`cafe_id`) REFERENCES `cafes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wfc_scores_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
