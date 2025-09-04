-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 28, 2025 at 03:33 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `skycamp`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` varchar(36) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('Active','Suspended','Deleted') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_deletions`
--

CREATE TABLE `admin_deletions` (
  `deletion_id` varchar(36) NOT NULL,
  `admin_id` varchar(36) NOT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_by` varchar(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_suspensions`
--

CREATE TABLE `admin_suspensions` (
  `suspension_id` varchar(36) NOT NULL,
  `admin_id` varchar(36) NOT NULL,
  `reason` text DEFAULT 'Replaced by a new admin',
  `suspended_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `suspended_by` varchar(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookingitems`
--

CREATE TABLE `bookingitems` (
  `booking_item_id` varchar(36) NOT NULL,
  `booking_id` varchar(36) NOT NULL,
  `renter_equipment_id` varchar(36) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_per_day` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` varchar(36) NOT NULL,
  `cart_id` varchar(36) NOT NULL,
  `customer_id` varchar(36) NOT NULL,
  `renter_id` varchar(36) DEFAULT NULL,
  `guide_id` varchar(36) DEFAULT NULL,
  `booking_type` enum('Equipment','Guide') NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `advance_paid` decimal(10,2) NOT NULL,
  `status` enum('Confirmed','Cancelled','Completed') NOT NULL DEFAULT 'Confirmed',
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `last_status_updated_by` enum('Customer','Renter','Guide','Admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cartitems`
--

CREATE TABLE `cartitems` (
  `cart_item_id` varchar(36) NOT NULL,
  `cart_id` varchar(36) NOT NULL,
  `renter_equipment_id` varchar(36) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `is_reserved` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `cart_id` varchar(36) NOT NULL,
  `customer_id` varchar(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` enum('Active','CheckedOut','Abandoned','Expired') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('Pending','Replied') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `replied_at` timestamp NULL DEFAULT NULL,
  `replied_by` varchar(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_logs`
--

CREATE TABLE `content_logs` (
  `log_id` int(11) NOT NULL,
  `admin_id` varchar(36) NOT NULL,
  `content_type` enum('Page','FAQ') NOT NULL,
  `content_id` varchar(36) NOT NULL,
  `action` enum('Created','Updated','Deleted') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `dob` date DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `home_address` text DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `nic_number` varchar(20) NOT NULL,
  `nic_image` varchar(255) DEFAULT NULL,
  `travel_buddy_status` enum('Active','Inactive') DEFAULT 'Inactive',
  `verification_status` enum('Yes','No') DEFAULT 'No',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `user_id`, `first_name`, `last_name`, `dob`, `phone_number`, `home_address`, `gender`, `profile_picture`, `nic_number`, `nic_image`, `travel_buddy_status`, `verification_status`, `created_at`, `latitude`, `longitude`) VALUES
('271c8e4f-cf65-499b-94e8-bbd72232c2fe', '082aca97-716e-4ba9-a0a4-52ac22e8b99c', 'Chamandi', 'Sanjula', '2001-10-31', '0774005021', 'Kaluthara', 'Female', NULL, '123412345V', NULL, 'Active', NULL, '2025-08-27 19:19:41', 6.59029745, 80.05609672),
('5e71d4d2-d608-43b3-b596-cc94418744df', '79b5f66d-0d3a-4afc-b704-faf18d1f87cf', 'Isini', 'Sandunika', '2001-04-08', '0771234567', 'Mirigama', 'Female', NULL, '123654789V', NULL, 'Inactive', NULL, '2025-08-27 20:57:54', NULL, NULL),
('c9dde18c-3f84-4c15-a8c9-f564f9de3488', '85ccd64d-4fce-468d-990e-6e6433e0f3d5', 'Banuka', 'Dilshan', '2001-07-07', '0763572895', 'Colombo', 'Male', NULL, '123456788V', NULL, 'Inactive', NULL, '2025-08-27 18:57:20', 6.92104190, 79.92658108);

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` varchar(36) NOT NULL,
  `category_id` varchar(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Deleted') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment_categories`
--

CREATE TABLE `equipment_categories` (
  `category_id` varchar(36) NOT NULL,
  `type` enum('Camping','Stargazing') NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment_log`
--

CREATE TABLE `equipment_log` (
  `log_id` varchar(36) NOT NULL,
  `equipment_id` varchar(36) NOT NULL,
  `action` enum('Added','Updated','Deleted') NOT NULL,
  `admin_id` varchar(36) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `faq_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guideavailability`
--

CREATE TABLE `guideavailability` (
  `availability_id` varchar(36) NOT NULL,
  `guide_id` varchar(36) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guideimages`
--

CREATE TABLE `guideimages` (
  `image_id` varchar(36) NOT NULL,
  `guide_id` varchar(36) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guides`
--

CREATE TABLE `guides` (
  `guide_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `dob` date DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `home_address` text DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `nic_number` varchar(20) NOT NULL,
  `nic_image` varchar(255) DEFAULT NULL,
  `camping_destinations` text DEFAULT NULL,
  `stargazing_spots` text DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `special_note` text DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `languages` text DEFAULT NULL,
  `price_per_day` decimal(10,2) DEFAULT NULL,
  `verification_status` enum('Yes','No') DEFAULT 'No',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inactive_users`
--

CREATE TABLE `inactive_users` (
  `inactive_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `role` enum('Customer','Renter','Guide') NOT NULL,
  `email` varchar(150) NOT NULL,
  `reason` text DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_by` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `location_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` enum('Camping','Stargazing') NOT NULL,
  `district` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `climate` text DEFAULT NULL,
  `wildlife` text DEFAULT NULL,
  `water_resources` text DEFAULT NULL,
  `safety_tips` text DEFAULT NULL,
  `important_details` text DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `location_images`
--

CREATE TABLE `location_images` (
  `image_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `type` enum('LowRatingWarning','PolicyViolation','CartUpdate','PaymentSuccess','Verification','TravelBuddyRequest','TravelBuddyResponse') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `page_id` int(11) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` longtext NOT NULL,
  `status` enum('Draft','Published') DEFAULT 'Published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `token_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`token_id`, `user_id`, `email`, `token`, `otp_code`, `expires_at`, `used`, `created_at`) VALUES
('ee131ab2-c72b-4300-8121-29f92bbe8ebe', '81fa5a12-8b17-4364-a445-f0eb0ab8e64d', 'supungunathilaka123@gmail.com', 'ceb8113f46bbc067aa8ca67bde816bb19e7f4bcc428078b20670789d2201a585', NULL, '2025-08-28 10:27:17', 0, '2025-08-28 12:57:17');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` varchar(36) NOT NULL,
  `booking_id` varchar(36) NOT NULL,
  `gateway_txn_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('Successful','RefundPending','Refunded') NOT NULL DEFAULT 'Successful',
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `refunded_at` timestamp NULL DEFAULT NULL,
  `refund_reason` text DEFAULT NULL,
  `last_status_updated_by` enum('Customer','Guide','Admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `rating_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `entity_type` enum('Renter','Guide','Location','Customer') NOT NULL,
  `entity_id` varchar(36) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `reminder_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `reason` text NOT NULL,
  `severity` enum('Info','Warning','Critical') DEFAULT 'Info',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `renterequipment`
--

CREATE TABLE `renterequipment` (
  `renter_equipment_id` varchar(36) NOT NULL,
  `renter_id` varchar(36) NOT NULL,
  `equipment_id` varchar(36) NOT NULL,
  `item_condition` varchar(100) DEFAULT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 1,
  `status` enum('Active','Archived') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `renterequipmentphotos`
--

CREATE TABLE `renterequipmentphotos` (
  `photo_id` varchar(36) NOT NULL,
  `renter_equipment_id` varchar(36) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `renters`
--

CREATE TABLE `renters` (
  `renter_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `dob` date DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `home_address` text DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `nic_number` varchar(20) NOT NULL,
  `nic_image` varchar(255) DEFAULT NULL,
  `camping_destinations` text DEFAULT NULL,
  `stargazing_spots` text DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `verification_status` enum('Yes','No') DEFAULT 'No',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `renters`
--

INSERT INTO `renters` (`renter_id`, `user_id`, `first_name`, `last_name`, `dob`, `phone_number`, `home_address`, `gender`, `profile_picture`, `nic_number`, `nic_image`, `camping_destinations`, `stargazing_spots`, `district`, `verification_status`, `created_at`, `latitude`, `longitude`) VALUES
('cf91c445-25ad-45af-877f-459d4bffaf5a', '81fa5a12-8b17-4364-a445-f0eb0ab8e64d', 'Supun', 'Gunathilake', '2001-10-08', '0774005021', '\"SISILASA\" 45 Canal, Weragama, Weraganthota', 'Male', NULL, '123456789V', NULL, 'Mahiyanganaya Fields,Gal Oya Vicinity', 'Nilgala Reserve,Anuradhapura Plains', 'Kandy', 'No', '2025-08-28 12:23:22', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `entity_type` enum('Renter','Guide','Location','Customer') NOT NULL,
  `entity_id` varchar(36) NOT NULL,
  `review_text` text NOT NULL,
  `status` enum('Active','Flagged') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_flags`
--

CREATE TABLE `review_flags` (
  `flag_id` varchar(36) NOT NULL,
  `review_id` varchar(36) NOT NULL,
  `flagged_by` varchar(36) NOT NULL,
  `reason` text DEFAULT NULL,
  `flagged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suspended_users`
--

CREATE TABLE `suspended_users` (
  `suspension_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `role` enum('Customer','Renter','Guide') NOT NULL,
  `reason` text DEFAULT NULL,
  `suspended_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `suspended_by` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `travel_chats`
--

CREATE TABLE `travel_chats` (
  `chat_id` varchar(36) NOT NULL,
  `plan_id` varchar(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `travel_chat_members`
--

CREATE TABLE `travel_chat_members` (
  `chat_member_id` varchar(36) NOT NULL,
  `chat_id` varchar(36) NOT NULL,
  `customer_id` varchar(36) NOT NULL,
  `status` enum('Active','Left','Removed') DEFAULT 'Active',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `left_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `travel_messages`
--

CREATE TABLE `travel_messages` (
  `message_id` varchar(36) NOT NULL,
  `chat_id` varchar(36) NOT NULL,
  `sender_id` varchar(36) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `travel_plans`
--

CREATE TABLE `travel_plans` (
  `plan_id` varchar(36) NOT NULL,
  `customer_id` varchar(36) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `travel_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `companions_needed` int(11) NOT NULL,
  `companions_joined` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `travel_requests`
--

CREATE TABLE `travel_requests` (
  `request_id` varchar(36) NOT NULL,
  `plan_id` varchar(36) NOT NULL,
  `requester_id` varchar(36) NOT NULL,
  `status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(36) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Customer','Guide','Renter','Admin') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
('082aca97-716e-4ba9-a0a4-52ac22e8b99c', 'chamandi@gmail.com', '$2y$10$5H9NZh.ZTm.jOhDqAArAAOIQ6yciCjmYl6syRM2Sq69UOWyHiXeXi', 'Customer', 1, '2025-08-27 19:19:41'),
('79b5f66d-0d3a-4afc-b704-faf18d1f87cf', 'isini@gmail.com', '$2y$10$9CCqIRAs2LFC7MilhTj/GeMlJ4AIzqChoO0iwQXm76paIyns/nMjC', 'Customer', 1, '2025-08-27 20:57:54'),
('81fa5a12-8b17-4364-a445-f0eb0ab8e64d', 'supungunathilaka123@gmail.com', '$2y$10$fFIu6b./sYHUM52V3StCue2PSbqUwMbKc8MWCC66spdEAlV1W4sbC', 'Renter', 1, '2025-08-28 12:23:22'),
('85ccd64d-4fce-468d-990e-6e6433e0f3d5', 'banuka@email.com', '$2y$10$kj1hXn2xdOaJ6zqLv3Xp0uwGqbUjYOJ92/ovBYXZ9atWZ1VEfFDpS', 'Customer', 1, '2025-08-27 18:57:20');

-- --------------------------------------------------------

--
-- Table structure for table `user_management_log`
--

CREATE TABLE `user_management_log` (
  `log_id` varchar(36) NOT NULL,
  `admin_id` varchar(36) NOT NULL,
  `action_type` enum('Suspend','Active','Delete') NOT NULL,
  `target_user_id` varchar(36) NOT NULL,
  `role` enum('Customer','Renter','Guide') NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_verifications`
--

CREATE TABLE `user_verifications` (
  `verification_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `role` enum('Customer','Renter','Guide') NOT NULL,
  `nic_number` varchar(20) NOT NULL,
  `nic_image` varchar(255) NOT NULL,
  `status` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `reviewed_by` varchar(36) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `verification_management_log`
--

CREATE TABLE `verification_management_log` (
  `log_id` varchar(36) NOT NULL,
  `admin_id` varchar(36) NOT NULL,
  `action_type` enum('Verify','RejectVerification') NOT NULL,
  `target_user_id` varchar(36) NOT NULL,
  `role` enum('Customer','Renter','Guide') NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `wishlist_id` varchar(36) NOT NULL,
  `customer_id` varchar(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist_items`
--

CREATE TABLE `wishlist_items` (
  `wishlist_item_id` varchar(36) NOT NULL,
  `wishlist_id` varchar(36) NOT NULL,
  `entity_type` enum('Camping','Stargazing','Guide','Renter') NOT NULL,
  `entity_id` varchar(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_deletions`
--
ALTER TABLE `admin_deletions`
  ADD PRIMARY KEY (`deletion_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admin_suspensions`
--
ALTER TABLE `admin_suspensions`
  ADD PRIMARY KEY (`suspension_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `bookingitems`
--
ALTER TABLE `bookingitems`
  ADD PRIMARY KEY (`booking_item_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `renter_equipment_id` (`renter_equipment_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `uq_booking_cart` (`cart_id`),
  ADD KEY `idx_bookings_status` (`status`),
  ADD KEY `idx_bookings_customer` (`customer_id`),
  ADD KEY `idx_bookings_renter` (`renter_id`),
  ADD KEY `idx_bookings_guide` (`guide_id`);

--
-- Indexes for table `cartitems`
--
ALTER TABLE `cartitems`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `renter_equipment_id` (`renter_equipment_id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `replied_by` (`replied_by`);

--
-- Indexes for table `content_logs`
--
ALTER TABLE `content_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `nic_number` (`nic_number`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`equipment_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `equipment_categories`
--
ALTER TABLE `equipment_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `equipment_log`
--
ALTER TABLE `equipment_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `equipment_id` (`equipment_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`faq_id`);

--
-- Indexes for table `guideavailability`
--
ALTER TABLE `guideavailability`
  ADD PRIMARY KEY (`availability_id`),
  ADD KEY `guide_id` (`guide_id`);

--
-- Indexes for table `guideimages`
--
ALTER TABLE `guideimages`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `guide_id` (`guide_id`);

--
-- Indexes for table `guides`
--
ALTER TABLE `guides`
  ADD PRIMARY KEY (`guide_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `nic_number` (`nic_number`);

--
-- Indexes for table `inactive_users`
--
ALTER TABLE `inactive_users`
  ADD PRIMARY KEY (`inactive_id`),
  ADD KEY `deleted_by` (`deleted_by`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `location_images`
--
ALTER TABLE `location_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`page_id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `email` (`email`),
  ADD KEY `expires_at` (`expires_at`),
  ADD KEY `idx_otp_code` (`otp_code`),
  ADD KEY `idx_email_otp` (`email`,`otp_code`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `uq_payment_booking` (`booking_id`),
  ADD KEY `idx_payments_status` (`payment_status`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_ratings_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`reminder_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `renterequipment`
--
ALTER TABLE `renterequipment`
  ADD PRIMARY KEY (`renter_equipment_id`),
  ADD KEY `renter_id` (`renter_id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `renterequipmentphotos`
--
ALTER TABLE `renterequipmentphotos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `renter_equipment_id` (`renter_equipment_id`);

--
-- Indexes for table `renters`
--
ALTER TABLE `renters`
  ADD PRIMARY KEY (`renter_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `nic_number` (`nic_number`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `review_flags`
--
ALTER TABLE `review_flags`
  ADD PRIMARY KEY (`flag_id`),
  ADD KEY `review_id` (`review_id`),
  ADD KEY `flagged_by` (`flagged_by`);

--
-- Indexes for table `suspended_users`
--
ALTER TABLE `suspended_users`
  ADD PRIMARY KEY (`suspension_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `travel_chats`
--
ALTER TABLE `travel_chats`
  ADD PRIMARY KEY (`chat_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `travel_chat_members`
--
ALTER TABLE `travel_chat_members`
  ADD PRIMARY KEY (`chat_member_id`),
  ADD UNIQUE KEY `uniq_chat_user` (`chat_id`,`customer_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `travel_messages`
--
ALTER TABLE `travel_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `chat_id` (`chat_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `travel_plans`
--
ALTER TABLE `travel_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `travel_requests`
--
ALTER TABLE `travel_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `requester_id` (`requester_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_management_log`
--
ALTER TABLE `user_management_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `target_user_id` (`target_user_id`);

--
-- Indexes for table `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD PRIMARY KEY (`verification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `verification_management_log`
--
ALTER TABLE `verification_management_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `target_user_id` (`target_user_id`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  ADD PRIMARY KEY (`wishlist_item_id`),
  ADD KEY `wishlist_id` (`wishlist_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_logs`
--
ALTER TABLE `content_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `faq_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location_images`
--
ALTER TABLE `location_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `page_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_deletions`
--
ALTER TABLE `admin_deletions`
  ADD CONSTRAINT `admin_deletions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_suspensions`
--
ALTER TABLE `admin_suspensions`
  ADD CONSTRAINT `admin_suspensions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `bookingitems`
--
ALTER TABLE `bookingitems`
  ADD CONSTRAINT `bookingitems_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookingitems_ibfk_2` FOREIGN KEY (`renter_equipment_id`) REFERENCES `renterequipment` (`renter_equipment_id`);

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`renter_id`) REFERENCES `renters` (`renter_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_4` FOREIGN KEY (`guide_id`) REFERENCES `guides` (`guide_id`) ON DELETE CASCADE;

--
-- Constraints for table `cartitems`
--
ALTER TABLE `cartitems`
  ADD CONSTRAINT `cartitems_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cartitems_ibfk_2` FOREIGN KEY (`renter_equipment_id`) REFERENCES `renterequipment` (`renter_equipment_id`);

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `contact_messages_ibfk_1` FOREIGN KEY (`replied_by`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `content_logs`
--
ALTER TABLE `content_logs`
  ADD CONSTRAINT `content_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`);

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `equipment`
--
ALTER TABLE `equipment`
  ADD CONSTRAINT `equipment_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `equipment_categories` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `equipment_log`
--
ALTER TABLE `equipment_log`
  ADD CONSTRAINT `equipment_log_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `equipment_log_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `guideavailability`
--
ALTER TABLE `guideavailability`
  ADD CONSTRAINT `guideavailability_ibfk_1` FOREIGN KEY (`guide_id`) REFERENCES `guides` (`guide_id`) ON DELETE CASCADE;

--
-- Constraints for table `guideimages`
--
ALTER TABLE `guideimages`
  ADD CONSTRAINT `guideimages_ibfk_1` FOREIGN KEY (`guide_id`) REFERENCES `guides` (`guide_id`) ON DELETE CASCADE;

--
-- Constraints for table `guides`
--
ALTER TABLE `guides`
  ADD CONSTRAINT `guides_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `inactive_users`
--
ALTER TABLE `inactive_users`
  ADD CONSTRAINT `inactive_users_ibfk_1` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `location_images`
--
ALTER TABLE `location_images`
  ADD CONSTRAINT `location_images_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reminders`
--
ALTER TABLE `reminders`
  ADD CONSTRAINT `reminders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `renterequipment`
--
ALTER TABLE `renterequipment`
  ADD CONSTRAINT `renterequipment_ibfk_1` FOREIGN KEY (`renter_id`) REFERENCES `renters` (`renter_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `renterequipment_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE;

--
-- Constraints for table `renterequipmentphotos`
--
ALTER TABLE `renterequipmentphotos`
  ADD CONSTRAINT `renterequipmentphotos_ibfk_1` FOREIGN KEY (`renter_equipment_id`) REFERENCES `renterequipment` (`renter_equipment_id`) ON DELETE CASCADE;

--
-- Constraints for table `renters`
--
ALTER TABLE `renters`
  ADD CONSTRAINT `renters_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `review_flags`
--
ALTER TABLE `review_flags`
  ADD CONSTRAINT `review_flags_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_flags_ibfk_2` FOREIGN KEY (`flagged_by`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `suspended_users`
--
ALTER TABLE `suspended_users`
  ADD CONSTRAINT `suspended_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `travel_chats`
--
ALTER TABLE `travel_chats`
  ADD CONSTRAINT `travel_chats_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `travel_plans` (`plan_id`) ON DELETE CASCADE;

--
-- Constraints for table `travel_chat_members`
--
ALTER TABLE `travel_chat_members`
  ADD CONSTRAINT `travel_chat_members_ibfk_1` FOREIGN KEY (`chat_id`) REFERENCES `travel_chats` (`chat_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `travel_chat_members_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `travel_messages`
--
ALTER TABLE `travel_messages`
  ADD CONSTRAINT `travel_messages_ibfk_1` FOREIGN KEY (`chat_id`) REFERENCES `travel_chats` (`chat_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `travel_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `travel_plans`
--
ALTER TABLE `travel_plans`
  ADD CONSTRAINT `travel_plans_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `travel_requests`
--
ALTER TABLE `travel_requests`
  ADD CONSTRAINT `travel_requests_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `travel_plans` (`plan_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `travel_requests_ibfk_2` FOREIGN KEY (`requester_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_management_log`
--
ALTER TABLE `user_management_log`
  ADD CONSTRAINT `user_management_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_management_log_ibfk_2` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD CONSTRAINT `user_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_verifications_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `verification_management_log`
--
ALTER TABLE `verification_management_log`
  ADD CONSTRAINT `verification_management_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `verification_management_log_ibfk_2` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  ADD CONSTRAINT `wishlist_items_ibfk_1` FOREIGN KEY (`wishlist_id`) REFERENCES `wishlists` (`wishlist_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
