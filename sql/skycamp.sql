-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 08, 2025 at 09:56 PM
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
  `status` enum('Active','CheckedOut','Abandoned','Expired') DEFAULT 'Active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ;

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
  `content_id` int(11) NOT NULL,
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
  `location` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `nic_number` varchar(20) NOT NULL,
  `nic_front_image` varchar(255) DEFAULT NULL,
  `nic_back_image` varchar(255) DEFAULT NULL,
  `travel_buddy_status` enum('Active','Inactive') DEFAULT 'Inactive',
  `verification_status` enum('Yes','No') DEFAULT 'No',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `user_id`, `first_name`, `last_name`, `dob`, `phone_number`, `home_address`, `location`, `latitude`, `longitude`, `gender`, `profile_picture`, `nic_number`, `nic_front_image`, `nic_back_image`, `travel_buddy_status`, `verification_status`, `created_at`) VALUES
('ecedd337-a12f-40d8-b180-596e98e8a7ff', '27f49d3f-73c5-499e-8b25-4919aec1f338', 'Supun', 'Gunathilaka', '2001-10-08', '0774005021', '\"SISILASA\" 45 Canal, Weragama, Weraganthota', NULL, NULL, NULL, 'Male', NULL, '111111111V', NULL, NULL, 'Inactive', 'No', '2025-09-08 14:07:00');

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
-- Table structure for table `equipment_reservations`
--

CREATE TABLE `equipment_reservations` (
  `reservation_id` varchar(36) NOT NULL,
  `renter_equipment_id` varchar(36) NOT NULL,
  `customer_id` varchar(36) NOT NULL,
  `cart_id` varchar(36) DEFAULT NULL,
  `booking_id` varchar(36) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `status` enum('Held','Booked','Released','Cancelled','Expired') NOT NULL DEFAULT 'Held',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

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
  `nic_front_image` varchar(255) DEFAULT NULL,
  `nic_back_image` varchar(255) DEFAULT NULL,
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

--
-- Dumping data for table `guides`
--

INSERT INTO `guides` (`guide_id`, `user_id`, `first_name`, `last_name`, `dob`, `phone_number`, `home_address`, `gender`, `profile_picture`, `nic_number`, `nic_front_image`, `nic_back_image`, `camping_destinations`, `stargazing_spots`, `district`, `description`, `special_note`, `currency`, `languages`, `price_per_day`, `verification_status`, `created_at`) VALUES
('3cf2c890-bb5b-4e96-bc20-f0b8b186041c', '1e04acbf-4bf4-443e-9c87-b1b840c185a5', 'Local', 'Guide', '2002-02-22', '0772222222', 'Kandy', 'Male', NULL, '333333333V', NULL, NULL, 'Ritigala Reserve,Gal Oya Vicinity', 'Nilgala Reserve,Knuckles Peak', 'Kandy', 'Iam avalable at every Monday!', 'Bring Your Map', 'LKR', 'Sinhala', 2500.00, 'No', '2025-09-08 14:24:17');

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
  `nic_front_image` varchar(255) DEFAULT NULL,
  `nic_back_image` varchar(255) DEFAULT NULL,
  `camping_destinations` text DEFAULT NULL,
  `stargazing_spots` text DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `verification_status` enum('Yes','No') DEFAULT 'No',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `renters`
--

INSERT INTO `renters` (`renter_id`, `user_id`, `first_name`, `last_name`, `dob`, `phone_number`, `home_address`, `gender`, `profile_picture`, `nic_number`, `nic_front_image`, `nic_back_image`, `camping_destinations`, `stargazing_spots`, `district`, `verification_status`, `latitude`, `longitude`, `created_at`) VALUES
('eae3a564-ccb9-4595-bba6-22f05fea92c9', 'eb85da8e-81b9-4aec-8ccc-22e4b1f17261', 'Equipment', 'Renter', '2001-11-11', '0771111111', 'Colombo', 'Male', NULL, '222222222V', NULL, NULL, 'Namunukula Range,Ritigala Reserve', 'Nilgala Reserve,Knuckles Peak', 'Colombo', 'No', 6.93886140, 79.85420050, '2025-09-08 14:12:47');

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
('1e04acbf-4bf4-443e-9c87-b1b840c185a5', 'guide@email.com', '$argon2id$v=19$m=65536,t=4,p=3$U3YyTm44MWxpZkgxVHRObQ$Ty0YxMJjhp18M3BAeiYZcfYM0Ad9CsVVCZiVYj76JMk', 'Guide', 1, '2025-09-08 14:24:17'),
('27f49d3f-73c5-499e-8b25-4919aec1f338', 'supungunathilaka123@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$ME9aamRtWGhBSG4yemRhSA$8hPRvNe9NDJrgQjaQ8Iv4aJm0IlunkfTEzINnPDw4eE', 'Customer', 1, '2025-09-08 14:07:00'),
('eb85da8e-81b9-4aec-8ccc-22e4b1f17261', 'renter@email.com', '$argon2id$v=19$m=65536,t=4,p=3$TGFQLldlMFlXS1h6ZlB1WA$V60aHsGENlk11wB+MU36r/XC0qKRPe7cbGGkzqsmqrw', 'Renter', 1, '2025-09-08 14:12:47');

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
-- Indexes for table `equipment_reservations`
--
ALTER TABLE `equipment_reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `idx_res_equipment_dates` (`renter_equipment_id`,`start_date`,`end_date`,`status`),
  ADD KEY `idx_res_expires` (`expires_at`);

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
-- Constraints for table `equipment_reservations`
--
ALTER TABLE `equipment_reservations`
  ADD CONSTRAINT `equipment_reservations_ibfk_1` FOREIGN KEY (`renter_equipment_id`) REFERENCES `renterequipment` (`renter_equipment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `equipment_reservations_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `equipment_reservations_ibfk_3` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `equipment_reservations_ibfk_4` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL;

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
