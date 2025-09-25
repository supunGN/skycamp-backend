-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2025 at 01:52 AM
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
  `admin_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('Active','Suspended','Deleted') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `email`, `password_hash`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin@skycamp.com', '$2y$10$moNM6/oxHH0zNYIaN0Ee0uJFyR5DHBwAy60Pl1cvgVsE89MdlBp6K', 'Active', '2025-09-11 04:50:00', '2025-09-11 05:15:10');

-- --------------------------------------------------------

--
-- Table structure for table `admin_deletions`
--

CREATE TABLE `admin_deletions` (
  `deletion_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_suspensions`
--

CREATE TABLE `admin_suspensions` (
  `suspension_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `reason` text DEFAULT 'Replaced by a new admin',
  `suspended_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `suspended_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookingitems`
--

CREATE TABLE `bookingitems` (
  `booking_item_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `renter_equipment_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_per_day` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `renter_id` int(11) DEFAULT NULL,
  `guide_id` int(11) DEFAULT NULL,
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
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `renter_equipment_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `is_reserved` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cartitems`
--

INSERT INTO `cartitems` (`cart_item_id`, `cart_id`, `renter_equipment_id`, `quantity`, `price_per_day`, `is_reserved`) VALUES
(10, 9, 13, 1, 2000.00, 1),
(11, 9, 6, 3, 550.00, 1),
(12, 10, 25, 1, 100.00, 1),
(13, 10, 23, 1, 1000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `cart_id` int(11) NOT NULL,
  `order_id` varchar(100) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `renter_id` int(11) DEFAULT NULL,
  `renter_name` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` enum('Active','CheckedOut','Abandoned','Expired') DEFAULT 'Active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`cart_id`, `order_id`, `customer_id`, `renter_id`, `renter_name`, `created_at`, `expires_at`, `status`, `start_date`, `end_date`) VALUES
(3, NULL, 1, 4, 'Sanduni Jayawardena', '2025-09-24 21:06:12', '2025-09-24 21:36:12', 'Expired', '0000-00-00', '0000-00-00'),
(4, NULL, 1, 4, 'Sanduni Jayawardena', '2025-09-24 21:21:21', '2025-09-24 21:51:21', 'Expired', '0000-00-00', '0000-00-00'),
(5, NULL, 1, 3, 'Ruwan Hettiarachchi', '2025-09-24 21:21:43', '2025-09-24 21:51:43', 'Expired', '0000-00-00', '0000-00-00'),
(6, NULL, 1, 3, 'Ruwan Hettiarachchi', '2025-09-24 21:35:27', '2025-09-24 22:05:27', 'Expired', '0000-00-00', '0000-00-00'),
(7, NULL, 1, 3, 'Ruwan Hettiarachchi', '2025-09-24 21:35:57', '2025-09-24 22:05:57', 'Expired', '0000-00-00', '0000-00-00'),
(9, NULL, 2, 3, 'Ruwan Hettiarachchi', '2025-09-24 23:17:14', '2025-09-24 23:47:14', 'Active', '0000-00-00', '0000-00-00'),
(10, NULL, 23, 5, 'Mohamed Fazil', '2025-09-24 23:43:28', '2025-09-25 00:13:28', 'Active', '0000-00-00', '0000-00-00');

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
  `replied_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_logs`
--

CREATE TABLE `content_logs` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
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
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
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
  `verification_status` enum('Yes','No','Pending') DEFAULT 'No',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `user_id`, `first_name`, `last_name`, `dob`, `phone_number`, `home_address`, `location`, `latitude`, `longitude`, `gender`, `profile_picture`, `nic_number`, `nic_front_image`, `nic_back_image`, `travel_buddy_status`, `verification_status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Nandana', 'Gunathilaka', '2001-10-08', '0774005021', 'hasalaka, kandy.', 'Hasalaka, Kandy District', 7.35160590, 80.95009700, 'Male', 'users/1/profile.jpg', '123456789V', 'users/1/nic_front.jpg', 'users/1/nic_back.jpg', 'Active', 'Yes', '2025-09-12 11:34:55', '2025-09-20 10:14:11'),
(2, 51, 'Amal', 'Jayawardena', '1995-05-14', '0771234567', 'No. 12, Kandy Road, Gampaha', 'Gampaha District', 7.09190000, 79.99460000, 'Male', 'users/51/profile.jpg', '951234590V', 'users/51/nic_front.jpg', 'users/51/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(3, 52, 'Nadeesha', 'Silva', '1998-03-21', '0712345678', 'No. 45, Station Road, Matara', 'Matara District', 5.94960000, 80.54690000, 'Female', 'users/52/profile.jpg', '981234591V', 'users/52/nic_front.jpg', 'users/52/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(4, 53, 'Ruwan', 'Fernando', '1992-11-10', '0756789123', 'No. 7, Negombo Road, Negombo', 'Negombo, Gampaha District', 7.20830000, 79.83580000, 'Male', 'users/53/profile.jpg', '921234592V', 'users/53/nic_front.jpg', 'users/53/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(5, 54, 'Shanika', 'Perera', '1996-08-04', '0724567890', 'No. 15, Hill Street, Kandy', 'Kandy District', 7.29060000, 80.63370000, 'Female', 'users/54/profile.jpg', '961234593V', 'users/54/nic_front.jpg', 'users/54/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(6, 55, 'Chathura', 'Abeysekara', '1993-02-19', '0709876543', 'No. 32, Beach Road, Galle', 'Galle District', 6.05350000, 80.22000000, 'Male', 'users/55/profile.jpg', '931234594V', 'users/55/nic_front.jpg', 'users/55/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(7, 56, 'Thilini', 'Ranathunga', '1997-06-12', '0743210987', 'No. 89, Temple Road, Kurunegala', 'Kurunegala District', 7.48330000, 80.36670000, 'Female', 'users/56/profile.jpg', '971234595V', 'users/56/nic_front.jpg', 'users/56/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(8, 57, 'Sampath', 'Madushanka', '1991-09-28', '0765432198', 'No. 8, Market Street, Anuradhapura', 'Anuradhapura District', 8.31140000, 80.40370000, 'Male', 'users/57/profile.jpg', '911234596V', 'users/57/nic_front.jpg', 'users/57/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(9, 58, 'Harshani', 'Gunawardena', '1999-12-01', '0787654321', 'No. 22, Lake Road, Polonnaruwa', 'Polonnaruwa District', 7.93360000, 81.00040000, 'Female', 'users/58/profile.jpg', '991234597V', 'users/58/nic_front.jpg', 'users/58/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(10, 59, 'Pradeep', 'Ekanayake', '1990-04-07', '0711122334', 'No. 14, Bazaar Street, Ratnapura', 'Ratnapura District', 6.70560000, 80.38470000, 'Male', 'users/59/profile.jpg', '901234598V', 'users/59/nic_front.jpg', 'users/59/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(11, 60, 'Dulmini', 'Fernando', '1994-07-18', '0752233445', 'No. 55, River Road, Trincomalee', 'Trincomalee District', 8.57110000, 81.23350000, 'Female', 'users/60/profile.jpg', '941234599V', 'users/60/nic_front.jpg', 'users/60/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(12, 61, 'Nimal', 'Karunaratne', '1992-05-15', '0773344556', 'No. 9, Main Street, Jaffna', 'Jaffna District', 9.66850000, 80.00740000, 'Male', 'users/61/profile.jpg', '921234600V', 'users/61/nic_front.jpg', 'users/61/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(13, 62, 'Sajini', 'Rajapaksha', '1997-11-23', '0724455667', 'No. 19, Temple Road, Badulla', 'Badulla District', 6.98960000, 81.05500000, 'Female', 'users/62/profile.jpg', '971234601V', 'users/62/nic_front.jpg', 'users/62/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(14, 63, 'Chamika', 'Jayasinghe', '1995-01-30', '0705566778', 'No. 11, Station Road, Monaragala', 'Monaragala District', 6.86670000, 81.35000000, 'Male', 'users/63/profile.jpg', '951234602V', 'users/63/nic_front.jpg', 'users/63/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(15, 64, 'Gayani', 'Perera', '1998-08-09', '0746677889', 'No. 27, Hill View, Hambantota', 'Hambantota District', 6.12450000, 81.11850000, 'Female', 'users/64/profile.jpg', '981234603V', 'users/64/nic_front.jpg', 'users/64/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(16, 65, 'Ravindu', 'Senanayake', '1993-03-25', '0767788990', 'No. 88, Church Road, Puttalam', 'Puttalam District', 8.03620000, 79.82830000, 'Male', 'users/65/profile.jpg', '931234604V', 'users/65/nic_front.jpg', 'users/65/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(17, 66, 'Ishara', 'Dissanayake', '1996-02-17', '0788899001', 'No. 5, Sea Road, Kalpitiya', 'Kalpitiya, Puttalam District', 8.23610000, 79.75960000, 'Female', 'users/66/profile.jpg', '961234605V', 'users/66/nic_front.jpg', 'users/66/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(18, 67, 'Asela', 'Kumara', '1992-09-11', '0719900112', 'No. 40, Park Road, Colombo', 'Colombo District', 6.92710000, 79.86120000, 'Male', 'users/67/profile.jpg', '921234606V', 'users/67/nic_front.jpg', 'users/67/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(19, 68, 'Pavithra', 'Wijesinghe', '1999-10-29', '0751122334', 'No. 18, New Road, Chilaw', 'Chilaw, Puttalam District', 7.57580000, 79.79530000, 'Female', 'users/68/profile.jpg', '991234607V', 'users/68/nic_front.jpg', 'users/68/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(20, 69, 'Sunil', 'Herath', '1991-06-05', '0772233445', 'No. 21, Temple Lane, Kegalle', 'Kegalle District', 7.25000000, 80.35000000, 'Male', 'users/69/profile.jpg', '911234608V', 'users/69/nic_front.jpg', 'users/69/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(21, 70, 'Rashmi', 'Dias', '1997-04-02', '0723344556', 'No. 77, Lake Road, Kilinochchi', 'Kilinochchi District', 9.40000000, 80.40000000, 'Female', 'users/70/profile.jpg', '971234609V', 'users/70/nic_front.jpg', 'users/70/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(22, 71, 'Chanuka', 'Bandara', '1995-12-12', '0744455667', 'No. 34, Station Road, Matale', 'Matale District', 7.46670000, 80.63330000, 'Male', 'users/71/profile.jpg', '951234610V', 'users/71/nic_front.jpg', 'users/71/nic_back.jpg', 'Active', 'Yes', '2025-09-20 12:20:08', '2025-09-20 12:20:08'),
(23, 235, 'Chamandi', 'Sanjula', '2001-10-31', '0776389515', 'Kalutara', 'Dodangoda, Dodangoda DS Division, Kalutara District', 6.57708995, 80.01767135, 'Female', 'users/235/profile.jpg', '895623788V', 'users/235/nic_front.jpg', 'users/235/nic_back.jpg', 'Active', 'Yes', '2025-09-20 08:53:31', '2025-09-20 14:46:34');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Deleted') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `category_id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '1-person tent', 'Compact tent for one person', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(2, 1, '2-person tent', 'Tent suitable for two people', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(3, 1, '3 or more person tent', 'Large tent for groups', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(4, 2, 'Sleeping bags', 'Warm sleeping bags for camping', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(5, 2, 'Air mattress', 'Inflatable mattress for camping comfort', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(6, 2, 'Camping pillow', 'Portable pillow for camping', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(7, 2, 'Emergency blanket', 'Compact emergency blanket', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(8, 3, 'Single gas stove', 'Portable single burner stove', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(9, 3, 'Double gas stove', 'Two-burner portable gas stove', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(10, 3, 'Gas BBQ grill', 'Portable barbecue grill', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(11, 3, 'Cooking pot and pan set', 'Camping cookware set', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(12, 3, 'Kettle for boiling water', 'Portable camping kettle', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(13, 3, 'Fork, spoon, knife set', 'Reusable cutlery set', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(14, 3, 'Chopping board', 'Compact camping chopping board', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(15, 3, 'Reusable plates and bowls', 'Eco-friendly camping plates and bowls', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(16, 3, 'Food storage containers', 'Containers for storing food', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(17, 3, 'Cooler box', 'Cooler for food and drinks', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(18, 4, 'Camping chair', 'Foldable camping chair', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(19, 4, 'Folding table', 'Portable camping table', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(20, 4, 'Hammock', 'Relaxing hammock', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(21, 5, 'Camping lanterns', 'Lanterns for lighting campsites', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(22, 5, 'Torch', 'Handheld torch', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(23, 5, 'Tent hanging light', 'Light for hanging inside tent', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(24, 6, 'Compass & Map', 'Navigation essentials', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(25, 6, 'Emergency whistle', 'Safety whistle', 'Active', '2025-09-13 14:37:18', '2025-09-13 17:35:32'),
(26, 6, 'First-aid kit', 'Basic medical kit', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(27, 6, 'Walkie-talkies', 'Two-way radios', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(28, 7, 'Water bottles', 'Reusable water bottles', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(29, 7, 'Water jugs', 'Large jugs for water storage', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(30, 8, 'Hiking backpacks', 'Backpacks for hiking', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(31, 8, 'Dry bags', 'Waterproof dry bags', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(32, 8, 'Waterproof pouches', 'Small waterproof pouches', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(33, 8, 'Gear organizer bag', 'Organizer for camping gear', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(34, 9, 'Raincoat', 'Waterproof raincoat', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(35, 9, 'Warm jacket', 'Insulated jacket for cold weather', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(36, 9, 'Waterproof shoes', 'Durable waterproof shoes', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(37, 10, 'Card games / Board games', 'Games for fun at camp', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(38, 10, 'Travel guitar', 'Portable guitar for travel', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(39, 11, 'Power bank & Cables', 'Portable charger and cables', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(40, 12, 'Small binoculars', 'Compact binoculars', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(41, 12, 'Stargazing binoculars', 'High-powered binoculars for stars', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(42, 13, 'Beginner telescope', 'Easy-to-use telescope for beginners', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(43, 13, 'Big telescope', 'Large telescope for stargazing', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(44, 14, 'Tripod stands for telescope or binoculars', 'Sturdy tripods', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(45, 15, 'Star maps or books', 'Guides for stargazing', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(46, 15, 'Power bank for telescope', 'Portable power for telescopes', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(47, 15, 'Laser pointer for pointing at stars', 'Laser pointer for sky mapping', 'Active', '2025-09-13 14:37:18', '2025-09-13 14:37:18'),
(48, 1, '4-person tent', 'Spacious tent suitable for four people', 'Active', '2025-09-13 14:52:32', '2025-09-13 14:52:32');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_categories`
--

CREATE TABLE `equipment_categories` (
  `category_id` int(11) NOT NULL,
  `type` enum('Camping','Stargazing') NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment_categories`
--

INSERT INTO `equipment_categories` (`category_id`, `type`, `name`, `description`, `created_at`) VALUES
(1, 'Camping', 'Tents', 'Different sizes of camping tents', '2025-09-13 14:37:17'),
(2, 'Camping', 'Sleeping Gear', 'Essential sleeping items for camping', '2025-09-13 14:37:17'),
(3, 'Camping', 'Cooking & Kitchen Items', 'Cooking and food preparation items', '2025-09-13 14:37:17'),
(4, 'Camping', 'Camping Furniture', 'Furniture for outdoor camping', '2025-09-13 14:37:17'),
(5, 'Camping', 'Lights', 'Lighting equipment for camping', '2025-09-13 14:37:17'),
(6, 'Camping', 'Navigation & Safety Tools', 'Navigation and safety tools', '2025-09-13 14:37:17'),
(7, 'Camping', 'Water & Hydration', 'Water bottles and hydration tools', '2025-09-13 14:37:17'),
(8, 'Camping', 'Bags & Storage', 'Bags and storage equipment', '2025-09-13 14:37:17'),
(9, 'Camping', 'Clothing', 'Clothing suitable for camping', '2025-09-13 14:37:17'),
(10, 'Camping', 'Fun & Extras', 'Entertainment and extra items', '2025-09-13 14:37:17'),
(11, 'Camping', 'Power & Charging', 'Power banks and charging accessories', '2025-09-13 14:37:17'),
(12, 'Stargazing', 'Binoculars', 'Different binoculars for stargazing', '2025-09-13 14:37:17'),
(13, 'Stargazing', 'Telescopes', 'Telescopes for beginners and advanced users', '2025-09-13 14:37:17'),
(14, 'Stargazing', 'Tripods & Mounts', 'Tripod stands and mounts', '2025-09-13 14:37:17'),
(15, 'Stargazing', 'Accessories', 'Stargazing accessories like maps and lasers', '2025-09-13 14:37:17');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_log`
--

CREATE TABLE `equipment_log` (
  `log_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `action` enum('Added','Updated','Deleted') NOT NULL,
  `admin_id` int(11) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment_reservations`
--

CREATE TABLE `equipment_reservations` (
  `reservation_id` int(11) NOT NULL,
  `renter_equipment_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `cart_id` int(11) DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `status` enum('Held','Booked','Released','Cancelled','Expired') NOT NULL DEFAULT 'Held',
  `expires_at` timestamp NULL DEFAULT NULL,
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
  `availability_id` int(11) NOT NULL,
  `guide_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guideavailability`
--

INSERT INTO `guideavailability` (`availability_id`, `guide_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(32, 1, 'Monday', '09:00:00', '17:00:00'),
(33, 1, 'Tuesday', '12:00:00', '17:00:00'),
(34, 1, 'Wednesday', '04:55:00', '18:55:00'),
(35, 1, 'Thursday', '09:00:00', '17:00:00'),
(36, 1, 'Friday', '15:00:00', '17:00:00'),
(37, 1, 'Sunday', '09:00:00', '15:00:00'),
(38, 2, 'Monday', '09:00:00', '17:00:00'),
(39, 2, 'Wednesday', '09:00:00', '17:00:00'),
(40, 2, 'Friday', '09:00:00', '17:00:00'),
(41, 3, 'Monday', '09:00:00', '17:00:00'),
(42, 3, 'Wednesday', '09:00:00', '17:00:00'),
(43, 3, 'Saturday', '09:00:00', '17:00:00'),
(44, 4, 'Monday', '08:00:00', '18:00:00'),
(45, 4, 'Wednesday', '09:00:00', '17:00:00'),
(46, 4, 'Friday', '09:00:00', '17:00:00'),
(47, 4, 'Tuesday', '09:00:00', '17:00:00'),
(48, 5, 'Monday', '10:00:00', '17:00:00'),
(49, 5, 'Tuesday', '04:00:00', '17:00:00'),
(50, 5, 'Wednesday', '03:00:00', '17:00:00'),
(51, 5, 'Thursday', '00:00:00', '15:00:00'),
(52, 5, 'Saturday', '00:00:00', '12:00:00'),
(53, 5, 'Sunday', '04:00:00', '17:00:00'),
(54, 6, 'Monday', '01:00:00', '17:00:00'),
(55, 6, 'Tuesday', '09:00:00', '17:00:00'),
(56, 6, 'Wednesday', '05:00:00', '17:00:00'),
(57, 6, 'Thursday', '04:00:00', '17:00:00'),
(58, 6, 'Friday', '06:00:00', '17:00:00'),
(59, 7, 'Monday', '09:00:00', '17:00:00'),
(60, 7, 'Tuesday', '01:00:00', '17:00:00'),
(61, 7, 'Wednesday', '03:00:00', '17:00:00'),
(62, 7, 'Thursday', '08:00:00', '17:00:00'),
(63, 7, 'Friday', '09:00:00', '17:00:00'),
(64, 7, 'Saturday', '01:00:00', '18:00:00'),
(65, 8, 'Monday', '09:00:00', '17:01:00'),
(66, 8, 'Tuesday', '01:00:00', '16:00:00'),
(67, 8, 'Thursday', '03:00:00', '17:00:00'),
(68, 8, 'Friday', '04:00:00', '17:00:00'),
(69, 8, 'Saturday', '03:06:00', '17:00:00'),
(70, 8, 'Sunday', '09:00:00', '17:00:00'),
(71, 9, 'Monday', '09:00:00', '17:00:00'),
(72, 9, 'Tuesday', '01:00:00', '17:00:00'),
(73, 9, 'Wednesday', '02:00:00', '17:00:00'),
(74, 9, 'Thursday', '06:00:00', '17:00:00'),
(75, 9, 'Friday', '09:00:00', '18:00:00'),
(76, 9, 'Saturday', '09:00:00', '17:00:00'),
(77, 9, 'Sunday', '04:00:00', '17:00:00'),
(78, 10, 'Monday', '09:00:00', '17:00:00'),
(79, 10, 'Tuesday', '01:00:00', '21:00:00'),
(80, 10, 'Wednesday', '09:00:00', '19:00:00'),
(81, 10, 'Thursday', '09:00:00', '18:00:00'),
(82, 10, 'Friday', '09:00:00', '17:00:00'),
(83, 10, 'Saturday', '06:00:00', '17:00:00'),
(84, 10, 'Sunday', '09:06:00', '17:00:00'),
(85, 11, 'Monday', '01:00:00', '17:00:00'),
(86, 11, 'Wednesday', '09:00:00', '17:00:00'),
(87, 11, 'Thursday', '09:55:00', '17:00:00'),
(88, 11, 'Friday', '01:01:00', '15:00:00'),
(89, 11, 'Saturday', '03:00:00', '19:00:00'),
(90, 12, 'Monday', '04:00:00', '17:00:00'),
(91, 12, 'Tuesday', '09:00:00', '17:00:00'),
(92, 12, 'Wednesday', '07:00:00', '17:00:00'),
(93, 12, 'Thursday', '09:08:00', '17:00:00'),
(94, 12, 'Friday', '06:00:00', '17:00:00'),
(95, 12, 'Saturday', '03:00:00', '17:00:00'),
(96, 12, 'Sunday', '00:07:00', '17:00:00'),
(97, 13, 'Monday', '09:00:00', '17:00:00'),
(98, 13, 'Tuesday', '05:00:00', '17:00:00'),
(99, 13, 'Wednesday', '04:00:00', '17:00:00'),
(100, 13, 'Thursday', '09:00:00', '18:00:00'),
(101, 13, 'Friday', '09:00:00', '17:00:00'),
(102, 13, 'Saturday', '07:00:00', '17:00:00'),
(103, 13, 'Sunday', '08:00:00', '17:00:00'),
(104, 14, 'Monday', '08:00:00', '17:00:00'),
(105, 14, 'Tuesday', '09:00:00', '17:00:00'),
(106, 14, 'Wednesday', '04:00:00', '17:00:00'),
(107, 14, 'Thursday', '09:00:00', '16:00:00'),
(108, 14, 'Friday', '09:00:00', '18:00:00'),
(109, 14, 'Saturday', '07:00:00', '17:00:00'),
(110, 14, 'Sunday', '05:00:00', '17:00:00'),
(111, 15, 'Monday', '09:00:00', '17:00:00'),
(112, 15, 'Tuesday', '09:00:00', '17:00:00'),
(113, 15, 'Wednesday', '04:00:00', '17:00:00'),
(114, 15, 'Thursday', '06:00:00', '17:00:00'),
(115, 15, 'Friday', '04:00:00', '17:00:00'),
(116, 16, 'Monday', '01:00:00', '17:00:00'),
(117, 16, 'Tuesday', '04:00:00', '17:00:00'),
(118, 16, 'Wednesday', '06:00:00', '17:00:00'),
(119, 16, 'Thursday', '07:00:00', '17:00:00'),
(120, 16, 'Friday', '06:00:00', '17:00:00'),
(121, 16, 'Saturday', '02:10:00', '13:00:00'),
(122, 17, 'Monday', '01:00:00', '17:00:00'),
(123, 17, 'Tuesday', '02:00:00', '18:00:00'),
(124, 17, 'Wednesday', '01:00:00', '17:00:00'),
(125, 17, 'Thursday', '03:00:00', '20:00:00'),
(126, 17, 'Friday', '09:00:00', '17:00:00'),
(127, 17, 'Saturday', '04:00:00', '17:00:00'),
(128, 17, 'Sunday', '06:00:00', '19:00:00'),
(129, 18, 'Monday', '06:00:00', '17:00:00'),
(130, 18, 'Tuesday', '07:00:00', '17:00:00'),
(131, 18, 'Wednesday', '06:00:00', '17:00:00'),
(132, 18, 'Thursday', '00:06:00', '17:00:00'),
(133, 18, 'Friday', '06:00:00', '17:00:00'),
(134, 19, 'Monday', '06:00:00', '17:00:00'),
(135, 19, 'Tuesday', '07:00:00', '17:00:00'),
(136, 19, 'Wednesday', '08:00:00', '17:00:00'),
(137, 19, 'Thursday', '00:04:00', '17:00:00'),
(138, 19, 'Friday', '06:00:00', '17:00:00'),
(139, 19, 'Saturday', '07:00:00', '17:00:00'),
(140, 21, 'Monday', '01:00:00', '17:00:00'),
(141, 21, 'Tuesday', '09:00:00', '17:00:00'),
(142, 21, 'Wednesday', '09:00:00', '17:00:00'),
(143, 21, 'Thursday', '00:00:00', '17:00:00'),
(144, 25, 'Monday', '04:00:00', '17:00:00'),
(145, 25, 'Tuesday', '00:00:00', '16:00:00'),
(146, 25, 'Wednesday', '07:07:00', '17:00:00'),
(147, 25, 'Sunday', '06:06:00', '17:00:00'),
(148, 25, 'Saturday', '04:04:00', '17:00:00'),
(149, 29, 'Monday', '09:00:00', '17:00:00'),
(150, 29, 'Tuesday', '09:00:00', '17:00:00'),
(151, 29, 'Wednesday', '09:00:00', '17:00:00'),
(152, 29, 'Thursday', '09:00:00', '17:00:00'),
(153, 29, 'Friday', '09:00:00', '17:00:00'),
(154, 29, 'Saturday', '09:00:00', '17:00:00'),
(155, 29, 'Sunday', '09:00:00', '17:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `guideimages`
--

CREATE TABLE `guideimages` (
  `image_id` int(11) NOT NULL,
  `guide_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guideimages`
--

INSERT INTO `guideimages` (`image_id`, `guide_id`, `image_path`, `uploaded_at`) VALUES
(4, 1, 'users/201/gallery/gallery_1758725025_68d403a1c736b.jpg', '2025-09-24 14:43:45'),
(5, 1, 'users/201/gallery/gallery_1758725036_68d403aca5a2a.jpg', '2025-09-24 14:43:56'),
(6, 2, 'users/202/gallery/gallery_1758725377_68d40501ab002.jpg', '2025-09-24 14:49:37'),
(7, 2, 'users/202/gallery/gallery_1758725386_68d4050a115f2.jpg', '2025-09-24 14:49:46'),
(8, 3, 'users/203/gallery/gallery_1758725568_68d405c0cca50.jpg', '2025-09-24 14:52:48'),
(9, 3, 'users/203/gallery/gallery_1758725575_68d405c7564f8.jpg', '2025-09-24 14:52:55'),
(10, 4, 'users/204/gallery/gallery_1758725725_68d4065da86ec.jpg', '2025-09-24 14:55:25'),
(11, 4, 'users/204/gallery/gallery_1758725733_68d40665a73ae.jpg', '2025-09-24 14:55:33'),
(12, 5, 'users/205/gallery/gallery_1758725988_68d4076474ab7.jpg', '2025-09-24 14:59:48'),
(13, 5, 'users/205/gallery/gallery_1758725995_68d4076b30598.jpg', '2025-09-24 14:59:55'),
(14, 5, 'users/205/gallery/gallery_1758726000_68d4077031f6c.jpg', '2025-09-24 15:00:00'),
(15, 6, 'users/206/gallery/gallery_1758726060_68d407ac1ad89.jpg', '2025-09-24 15:01:00'),
(16, 6, 'users/206/gallery/gallery_1758726066_68d407b27fca0.jpg', '2025-09-24 15:01:06'),
(17, 6, 'users/206/gallery/gallery_1758726070_68d407b6cbe2c.jpg', '2025-09-24 15:01:10'),
(18, 7, 'users/207/gallery/gallery_1758726212_68d408447ba31.jpg', '2025-09-24 15:03:32'),
(19, 7, 'users/207/gallery/gallery_1758726216_68d4084816207.jpg', '2025-09-24 15:03:36'),
(20, 7, 'users/207/gallery/gallery_1758726219_68d4084b6a893.jpg', '2025-09-24 15:03:39'),
(21, 8, 'users/208/gallery/gallery_1758726275_68d408830be28.jpg', '2025-09-24 15:04:35'),
(22, 8, 'users/208/gallery/gallery_1758726278_68d4088660fef.jpg', '2025-09-24 15:04:38'),
(23, 8, 'users/208/gallery/gallery_1758726281_68d40889e97d9.jpg', '2025-09-24 15:04:41'),
(24, 9, 'users/209/gallery/gallery_1758726337_68d408c1f0c53.jpg', '2025-09-24 15:05:37'),
(25, 9, 'users/209/gallery/gallery_1758726343_68d408c77e345.jpg', '2025-09-24 15:05:43'),
(26, 9, 'users/209/gallery/gallery_1758726347_68d408cb228f8.jpg', '2025-09-24 15:05:47'),
(27, 10, 'users/210/gallery/gallery_1758726402_68d40902ddb6f.jpg', '2025-09-24 15:06:42'),
(28, 1, 'users/201/gallery/gallery_1758726432_68d40920ac923.jpg', '2025-09-24 15:07:12'),
(29, 11, 'users/211/gallery/gallery_1758726491_68d4095b49276.jpg', '2025-09-24 15:08:11'),
(30, 11, 'users/211/gallery/gallery_1758726495_68d4095f7ef71.jpg', '2025-09-24 15:08:15'),
(31, 11, 'users/211/gallery/gallery_1758726499_68d4096301c9c.jpg', '2025-09-24 15:08:19'),
(32, 12, 'users/212/gallery/gallery_1758726561_68d409a1dcf9a.jpg', '2025-09-24 15:09:21'),
(33, 12, 'users/212/gallery/gallery_1758726565_68d409a59799c.jpg', '2025-09-24 15:09:25'),
(34, 12, 'users/212/gallery/gallery_1758726569_68d409a92a0ea.jpg', '2025-09-24 15:09:29'),
(35, 13, 'users/213/gallery/gallery_1758726616_68d409d847612.jpg', '2025-09-24 15:10:16'),
(36, 13, 'users/213/gallery/gallery_1758726620_68d409dc31fac.jpg', '2025-09-24 15:10:20'),
(37, 13, 'users/213/gallery/gallery_1758726623_68d409df55426.jpg', '2025-09-24 15:10:23'),
(38, 14, 'users/214/gallery/gallery_1758726679_68d40a1735cea.jpg', '2025-09-24 15:11:19'),
(39, 14, 'users/214/gallery/gallery_1758726685_68d40a1d2b404.jpg', '2025-09-24 15:11:25'),
(40, 14, 'users/214/gallery/gallery_1758726689_68d40a2103dbc.jpg', '2025-09-24 15:11:29'),
(41, 15, 'users/215/gallery/gallery_1758726735_68d40a4f0b8de.jpg', '2025-09-24 15:12:15'),
(42, 15, 'users/215/gallery/gallery_1758726742_68d40a5649d5f.jpg', '2025-09-24 15:12:22'),
(43, 15, 'users/215/gallery/gallery_1758726745_68d40a596c650.jpg', '2025-09-24 15:12:25'),
(44, 16, 'users/216/gallery/gallery_1758726794_68d40a8acae3c.jpg', '2025-09-24 15:13:14'),
(45, 16, 'users/216/gallery/gallery_1758726798_68d40a8e029a7.jpg', '2025-09-24 15:13:18'),
(46, 17, 'users/217/gallery/gallery_1758726889_68d40ae990165.jpg', '2025-09-24 15:14:49'),
(47, 17, 'users/217/gallery/gallery_1758726892_68d40aec65181.jpg', '2025-09-24 15:14:52'),
(48, 17, 'users/217/gallery/gallery_1758726916_68d40b0473c6f.jpg', '2025-09-24 15:15:16'),
(49, 18, 'users/218/gallery/gallery_1758727046_68d40b866c9c0.jpg', '2025-09-24 15:17:26'),
(50, 18, 'users/218/gallery/gallery_1758727069_68d40b9d30977.jpg', '2025-09-24 15:17:49'),
(51, 19, 'users/219/gallery/gallery_1758727160_68d40bf8a5850.jpg', '2025-09-24 15:19:20'),
(52, 19, 'users/219/gallery/gallery_1758727171_68d40c0359414.jpg', '2025-09-24 15:19:31'),
(53, 19, 'users/219/gallery/gallery_1758727176_68d40c080cbdc.jpg', '2025-09-24 15:19:36'),
(54, 21, 'users/221/gallery/gallery_1758727239_68d40c47eef3c.jpg', '2025-09-24 15:20:39'),
(55, 21, 'users/221/gallery/gallery_1758727243_68d40c4b330f1.jpg', '2025-09-24 15:20:43'),
(56, 21, 'users/221/gallery/gallery_1758727245_68d40c4ddbcf4.jpg', '2025-09-24 15:20:45'),
(57, 22, 'users/222/gallery/gallery_1758727273_68d40c696798b.jpg', '2025-09-24 15:21:13'),
(58, 22, 'users/222/gallery/gallery_1758727276_68d40c6ceab91.jpg', '2025-09-24 15:21:16'),
(59, 22, 'users/222/gallery/gallery_1758727279_68d40c6fb8844.jpg', '2025-09-24 15:21:19'),
(60, 23, 'users/223/gallery/gallery_1758727307_68d40c8b055a6.jpg', '2025-09-24 15:21:47'),
(61, 23, 'users/223/gallery/gallery_1758727310_68d40c8eb826b.jpg', '2025-09-24 15:21:50'),
(62, 23, 'users/223/gallery/gallery_1758727314_68d40c92e0e90.jpg', '2025-09-24 15:21:54'),
(63, 24, 'users/224/gallery/gallery_1758727350_68d40cb612575.jpg', '2025-09-24 15:22:30'),
(64, 24, 'users/224/gallery/gallery_1758727352_68d40cb89c611.jpg', '2025-09-24 15:22:32'),
(65, 24, 'users/224/gallery/gallery_1758727356_68d40cbc4941e.jpg', '2025-09-24 15:22:36'),
(66, 25, 'users/225/gallery/gallery_1758727400_68d40ce86cf12.jpg', '2025-09-24 15:23:20'),
(67, 25, 'users/225/gallery/gallery_1758727403_68d40cebcdde6.jpg', '2025-09-24 15:23:23'),
(68, 25, 'users/225/gallery/gallery_1758727406_68d40ceedcb87.jpg', '2025-09-24 15:23:26'),
(69, 26, 'users/226/gallery/gallery_1758727462_68d40d266c092.jpg', '2025-09-24 15:24:22'),
(70, 26, 'users/226/gallery/gallery_1758727465_68d40d29b74b6.jpg', '2025-09-24 15:24:25'),
(71, 27, 'users/227/gallery/gallery_1758727491_68d40d4335e9a.jpg', '2025-09-24 15:24:51'),
(72, 27, 'users/227/gallery/gallery_1758727494_68d40d46e9992.jpg', '2025-09-24 15:24:54'),
(73, 28, 'users/228/gallery/gallery_1758727539_68d40d7301087.jpg', '2025-09-24 15:25:39'),
(74, 28, 'users/228/gallery/gallery_1758727542_68d40d763877d.jpg', '2025-09-24 15:25:42'),
(75, 28, 'users/228/gallery/gallery_1758727546_68d40d7a38101.jpg', '2025-09-24 15:25:46'),
(76, 29, 'users/229/gallery/gallery_1758727585_68d40da16d6be.jpg', '2025-09-24 15:26:25'),
(77, 29, 'users/229/gallery/gallery_1758727588_68d40da485b8f.jpg', '2025-09-24 15:26:28'),
(78, 29, 'users/229/gallery/gallery_1758727594_68d40daa2dd77.jpg', '2025-09-24 15:26:34'),
(79, 30, 'users/230/gallery/gallery_1758727620_68d40dc4d874f.jpg', '2025-09-24 15:27:00'),
(80, 30, 'users/230/gallery/gallery_1758727623_68d40dc79aee4.jpg', '2025-09-24 15:27:03'),
(81, 30, 'users/230/gallery/gallery_1758727629_68d40dcdd11f1.jpg', '2025-09-24 15:27:09');

-- --------------------------------------------------------

--
-- Table structure for table `guides`
--

CREATE TABLE `guides` (
  `guide_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
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
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `verification_status` enum('Yes','No','Pending') DEFAULT 'No',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guides`
--

INSERT INTO `guides` (`guide_id`, `user_id`, `first_name`, `last_name`, `dob`, `phone_number`, `home_address`, `gender`, `profile_picture`, `nic_number`, `nic_front_image`, `nic_back_image`, `camping_destinations`, `stargazing_spots`, `district`, `description`, `special_note`, `currency`, `languages`, `price_per_day`, `latitude`, `longitude`, `verification_status`, `created_at`) VALUES
(1, 201, 'Nadeesha', 'Perera', '1993-07-12', '0711234567', 'No. 12, Baseline Rd, Borella', 'Female', 'users/201/profile.jpg', '731234561V', 'users/201/nic_front.jpg', 'users/201/nic_back.jpg', 'Diyasaru Park,Wilpattu Camping', 'Knuckles Mountains,Riverston', 'Colombo', 'Urban eco-guide for wetlands, boardwalks, and city-adjacent nature days.', 'Great for families & beginners; permits arranged on request.', 'LKR', 'Sinhala, English, Tamil', 7000.00, 6.91594660, 79.88030484, 'Yes', '2025-09-13 18:01:35'),
(2, 202, 'Chamari', 'Silva', '1992-03-08', '0772345678', 'No. 8, Negombo Rd, Ja-Ela', 'Female', 'users/202/profile.jpeg', '731234562V', NULL, NULL, 'Muthurajawela Marsh', 'Minneriya Area', 'Gampaha', 'Birding & boat-trail specialist around Negombo lagoon and marshlands.', 'Crocodile safety briefing included for marsh tours.', 'LKR', 'Sinhala, English', 6800.00, 7.07126190, 80.00877460, 'Yes', '2025-09-13 18:01:35'),
(3, 203, 'Ishara', 'Fernando', '1991-11-19', '0763456789', 'No. 21, Agalawatta Rd, Horana', 'Female', 'users/203/profile.jpeg', '731234563V', NULL, NULL, 'Thudugala Waterfall', 'Knuckles Mountains', 'Kalutara', 'Waterfall treks with safe swim spots and rainforest walks.', 'Avoid monsoon edges; I monitor rainfall and trail conditions.', 'LKR', 'Sinhala, English', 6500.00, 6.60846860, 80.14285840, 'Yes', '2025-09-13 18:01:35'),
(4, 204, 'Sanduni', 'Jayasinghe', '1994-05-24', '0754567890', 'No. 56, Hantana Rd, Peradeniya', 'Female', 'users/204/profile.jpeg', '731234564V', NULL, NULL, 'Wewathenna Mountain', 'Knuckles Mountains', 'Kandy', 'Highland hikes with sunrise viewpoints and misty ridge walks.', 'Warm layers and rain shells provided on request.', 'LKR', 'Sinhala, English, Tamil', 9000.00, 7.29315880, 80.63501070, 'Yes', '2025-09-13 18:01:35'),
(5, 205, 'Udari', 'Wickramasinghe', '1990-09-02', '0745678901', 'No. 10, Illukkumbura Rd, Rattota', 'Female', 'users/205/profile.jpeg', '731234565V', NULL, NULL, 'Riverston Peak', 'Riverston', 'Matale', 'Mini World’s End treks and windy plateau camping.', 'Tripod tie-downs and wind safety tips for exposed ridges.', 'LKR', 'Sinhala, English', 9500.00, 7.46596460, 80.62342590, 'Yes', '2025-09-13 18:01:35'),
(6, 206, 'Tharushi', 'Gunasekara', '1995-12-14', '0786789012', 'No. 4, Station Rd, Nanu Oya', 'Female', 'users/206/profile.jpeg', '731234566V', NULL, NULL, 'Horton Plains', 'Horton Plains', 'Nuwara Eliya', 'Cloud-forest walks, World’s End loop, and cold-night camping near Ohiya.', 'Hot drinks and thermal layers checklist included.', 'LKR', 'Sinhala, Tamil, English', 12000.00, 6.96065320, 80.76927580, 'Yes', '2025-09-13 18:01:35'),
(7, 207, 'Dilhani', 'Bandara', '1996-08-18', '0727890123', 'No. 5, Dewata Rd, Unawatuna', 'Female', 'users/207/profile.jpeg', '731234567V', NULL, NULL, 'Koggala Lake', 'Koggala Lake', 'Galle', 'Island-hopping, mangrove channels, and lakeside eco-camping.', 'Lifejackets provided for all water activities.', 'LKR', 'Sinhala, English', 7000.00, 6.05774900, 80.21755720, 'Yes', '2025-09-13 18:01:35'),
(8, 208, 'Samadhi', 'Weerasinghe', '1993-10-07', '0708901234', 'No. 9, New Tangalle Rd, Weligama', 'Female', 'users/208/profile.jpeg', '731234568V', NULL, NULL, 'Madiha Beach', 'Koggala Lake', 'Matara', 'Coastal camps with reef-safe practices and surf-friendly itineraries.', 'Sun and reef-safety briefing before swims/snorkels.', 'LKR', 'Sinhala, English', 6000.00, 5.94493480, 80.54879970, 'Yes', '2025-09-13 18:01:35'),
(9, 209, 'Shashini', 'Dissanayake', '1989-01-28', '0719012345', 'No. 3, Kirinda Rd, Tissamaharama', 'Female', 'users/209/profile.jpeg', '731234569V', NULL, NULL, 'Yala Buffer Zone', 'Yala Buffer Zone', 'Hambantota', 'Safari-style camps with strict wildlife protocols near Yala.', 'Authorized camps only; no off-trail night walks.', 'LKR', 'Sinhala, English', 14000.00, 6.15358160, 81.12714900, 'Yes', '2025-09-13 18:01:35'),
(10, 210, 'Bimashi', 'Ranasinghe', '1994-02-12', '0779012234', 'No. 22, Temple Rd, Nallur', 'Female', 'users/210/profile.jpeg', '731234570V', NULL, NULL, 'Casuarina Beach', 'Casuarina Beach', 'Jaffna', 'North-coast beach camps and lagoon sunsets with local cuisine.', 'Respect cultural norms; modest beachwear guidance provided.', 'LKR', 'Tamil, English', 7500.00, 9.69304680, 80.16518540, 'Yes', '2025-09-13 18:01:35'),
(11, 211, 'Hansani', 'Abeysekera', '1992-06-30', '0769023456', 'No. 14, A9 Hwy, Paranthan', 'Female', 'users/211/profile.jpeg', '731234571V', NULL, NULL, 'Iranamadu Tank', 'Casuarina Beach', 'Kilinochchi', 'Reservoir-side birding and dark-sky camping.', 'No swimming in deep areas; strong sun precautions.', 'LKR', 'Tamil, English', 7000.00, 9.36779710, 80.32131060, 'Yes', '2025-09-13 18:01:35'),
(12, 212, 'Kavindya', 'Ekanayake', '1995-04-04', '0759034567', 'No. 7, Beach Rd, Pesalai', 'Female', 'users/212/profile.jpeg', '731234572V', NULL, NULL, 'Adam’s Bridge (Rama’s Bridge)', 'Casuarina Beach', 'Mannar', 'Mythic causeway vistas, salt flats, and lagoon birds.', 'Heat & tide-aware itineraries; ample water carried.', 'LKR', 'Tamil, English', 8000.00, 8.98095310, 79.90439750, 'Yes', '2025-09-13 18:01:35'),
(13, 213, 'Sewwandi', 'Senanayake', '1991-09-09', '0749045678', 'No. 18, Kandy Rd, Thandikulam', 'Female', 'users/213/profile.jpeg', '731234573V', NULL, NULL, 'Madukanda Forest Edge', 'Minneriya Area', 'Vavuniya', 'Quiet forest camps blended with temple heritage.', 'Elephant-aware camping; stay in designated zones.', 'LKR', 'Tamil, Sinhala, English', 7000.00, 8.75947390, 80.50003340, 'Yes', '2025-09-13 18:01:35'),
(14, 214, 'Nimesha', 'de Silva', '1993-01-20', '0789056789', 'No. 2, Coastal Rd, Puthukkudiyiruppu', 'Female', 'users/214/profile.jpeg', '731234574V', NULL, NULL, 'Nayaru Lagoon', 'Nilaveli Beach', 'Mullaitivu', 'Kayak-friendly lagoon and mangrove edges; remote eco-feel.', 'Mosquito protection and tide checks standard.', 'LKR', 'Tamil, English', 6800.00, 9.26753880, 80.81282540, 'Yes', '2025-09-13 18:01:35'),
(15, 215, 'Pabasara', 'Karunaratne', '1997-07-01', '0729157890', 'No. 33, Uppuveli Rd, Trincomalee', 'Female', 'users/215/profile.jpeg', '731234575V', 'users/215/nic_front.jpg', 'users/215/nic_back.jpg', 'Marble Beach', 'Nilaveli Beach', 'Trincomalee', 'Snorkel-friendly bays and family beach camps on the east coast.', 'Work with Navy-managed zones; swim only in flagged areas.', 'LKR', 'Tamil, Sinhala, English', 8000.00, 8.57197000, 81.23340000, 'Yes', '2025-09-13 18:01:35'),
(16, 216, 'Pasindu', 'Rathnayake', '1990-12-11', '0712234567', 'No. 40, Beach Rd, Kallady', 'Male', 'users/216/profile.jpeg', '731234576V', NULL, NULL, 'Pasikudah Beach', 'Nilaveli Beach', 'Batticaloa', 'Shallow-bay camps, safe swims, and coral-friendly practices.', 'Strong sun care and early-morning snorkel starts.', 'LKR', 'Tamil, English', 7000.00, 7.82927810, 81.47183870, 'Yes', '2025-09-13 18:01:35'),
(17, 217, 'Kavindu', 'Herath', '1989-05-16', '0773345678', 'No. 6, Senanayake Mawatha, Uhana', 'Male', 'users/217/profile.jpeg', '731234577V', NULL, NULL, 'Gal Oya National Park', 'Minneriya Area', 'Ampara', 'Boat safaris and elephant crossings on the reservoir islands.', 'Always with rangers; crocodile-aware shoreline rules.', 'LKR', 'Sinhala, Tamil, English', 10500.00, 7.29116850, 81.67237610, 'Yes', '2025-09-13 18:01:35'),
(18, 218, 'Sajith', 'Dasanayake', '1992-02-02', '0764456789', 'No. 15, Depot Rd, Kuliyapitiya', 'Male', 'users/218/profile.jpeg', '731234578V', NULL, NULL, 'Dolukanda Sacred Rock', 'Knuckles Mountains', 'Kurunegala', 'Legend-filled rock hikes with sunrise panoramas.', 'Steep sections managed with rest points and hydration.', 'LKR', 'Sinhala, English', 7500.00, 7.48640000, 80.36230000, 'Yes', '2025-09-13 18:01:35'),
(19, 219, 'Nuwan', 'Peiris', '1991-04-21', '0755567890', 'No. 88, Lagoon Rd, Kalpitiya', 'Male', 'users/219/profile.jpeg', '731234579V', NULL, NULL, 'Kalpitiya Beach', 'Casuarina Beach', 'Puttalam', 'Kite-surf seasons, dolphin watching, and beach camps.', 'High-wind tie-downs provided for tents and gear.', 'LKR', 'Sinhala, Tamil, English', 8500.00, 8.02599150, 79.84712720, 'Yes', '2025-09-13 18:01:35'),
(20, 220, 'Tharindu', 'Suraweera', '1993-03-05', '0746678901', 'No. 12, Temple Rd, Mihintale', 'Male', 'users/220/profile.jpeg', '731234580V', NULL, NULL, 'Wilpattu Camping', 'Ritigala Reserve', 'Anuradhapura', 'Jungle tracks, villus, and heritage-adjacent campouts.', 'Permits & park rules strictly followed; no night walks.', 'LKR', 'Sinhala, English', 12000.00, 8.33183050, 80.40290170, 'Yes', '2025-09-13 18:01:35'),
(21, 221, 'Lahiru', 'Jayawardena', '1994-06-11', '0787789012', 'No. 27, Main St, Hingurakgoda', 'Male', 'users/221/profile.jpeg', '731234581V', NULL, NULL, 'Habarana Jungle', 'Minneriya Area', 'Polonnaruwa', 'Elephant corridor awareness and safari-style camping.', 'Camp only with trained teams; waterholes kept clear.', 'LKR', 'Sinhala, English', 11000.00, 7.93955670, 81.00034030, 'Yes', '2025-09-13 18:01:35'),
(22, 222, 'Supun', 'Jayasuriya', '1992-08-08', '0728890123', 'No. 19, Welimada Rd, Bandarawela', 'Male', 'users/222/profile.jpeg', '731234582V', NULL, NULL, 'Madolsima', 'Namunukula Range', 'Badulla', 'Cliff-edge sunrise hikes and cloud-sea views.', 'Cold-night prep and cliff-edge safety emphasized.', 'LKR', 'Sinhala, Tamil, English', 9500.00, 6.99340090, 81.05498150, 'Yes', '2025-09-13 18:01:35'),
(23, 223, 'Sahan', 'Wijesinghe', '1990-10-10', '0709901234', 'No. 6, Kataragama Rd, Buttala', 'Male', 'users/223/profile.jpeg', '731234583V', NULL, NULL, 'Udawalawe Border', 'Yala Buffer Zone', 'Monaragala', 'Elephant-rich borderlands with ranger-led camps.', 'Food storage protocols for wildlife safety.', 'LKR', 'Sinhala, English', 9000.00, 6.87277810, 81.35068320, 'Yes', '2025-09-13 18:01:35'),
(24, 224, 'Sanjeewa', 'Alwis', '1988-12-22', '0716677889', 'No. 3, Pambahinna Rd, Eheliyagoda', 'Male', 'users/224/profile.jpeg', '731234584V', NULL, NULL, 'Belihuloya', 'Horton Plains', 'Ratnapura', 'Streams, natural pools, and short hikes near Sabaragamuwa.', 'Leech-season prep and river safety covered.', 'LKR', 'Sinhala, English', 8000.00, 6.70551680, 80.38483890, 'Yes', '2025-09-13 18:01:35'),
(25, 225, 'Chathura', 'Priyankara', '1995-09-23', '0777788990', 'No. 44, Kandy Rd, Mawanella', 'Male', 'users/225/profile.jpeg', '731234585V', NULL, NULL, 'Knuckles Foothills', 'Knuckles Mountains', 'Kegalle', 'Foothill waterfalls, rare species, and cool misty camps.', 'Slippery-trail management and biodiversity etiquette.', 'LKR', 'Sinhala, English', 10000.00, 7.12040530, 80.32131060, 'Yes', '2025-09-13 18:01:35'),
(26, 226, 'Isuru', 'Peris', '1991-01-17', '0765566778', 'No. 7, Kandapola Rd, Nuwara Eliya', 'Male', 'users/226/profile.jpeg', '731234586V', NULL, NULL, 'Horton Plains', 'Horton Plains', 'Nuwara Eliya', 'High-country loops and cold-weather camping best-practices.', 'Thermal wear checklist shared pre-trip.', 'LKR', 'Sinhala, Tamil, English', 12500.00, 6.96065320, 80.76927580, 'Yes', '2025-09-13 18:01:35'),
(27, 227, 'Malith', 'Kulatunga', '1992-02-14', '0754455667', 'No. 61, Katugastota Rd, Kandy', 'Male', 'users/227/profile.jpeg', '731234587V', NULL, NULL, 'Wewathenna Mountain', 'Knuckles Mountains', 'Kandy', 'Ridge hikes, tea-estate connectors, and viewpoint camps.', 'Weather shifts fast; I carry spare rain shells.', 'LKR', 'Sinhala, English, Tamil', 9800.00, 7.29315880, 80.63501070, 'Yes', '2025-09-13 18:01:35'),
(28, 228, 'Chamika', 'Withanage', '1993-03-13', '0743344556', 'No. 22, Beliatta Rd, Dikwella', 'Male', 'users/228/profile.jpeg', '731234588V', NULL, NULL, 'Madiha Beach', 'Koggala Lake', 'Matara', 'Chilled surf-culture camps with reef-safe plans.', 'Earliest water sessions scheduled for calm seas.', 'LKR', 'Sinhala, English', 6500.00, 5.94493480, 80.54879970, 'Yes', '2025-09-13 18:01:35'),
(29, 229, 'Madushan', 'Abeynayake', '1990-06-06', '0782233445', 'No. 28, Nochchiyagama Rd, Anuradhapura', 'Male', 'users/229/profile.jpeg', '731234589V', NULL, NULL, 'Wilpattu Camping', 'Ritigala Reserve', 'Anuradhapura', 'Villus, jungle tracks, and ancient-ruin adjacency.', 'Strict no-litter and guided-only night routines.', 'LKR', 'Sinhala, English', 11800.00, 8.33183050, 80.40290170, 'Yes', '2025-09-13 18:01:35'),
(30, 230, 'Pradeep', 'Wanniarachchi', '1989-08-29', '0721122334', 'No. 5, Hali Ela Rd, Badulla', 'Male', 'users/230/profile.jpeg', '731234590V', NULL, NULL, 'Narangala Peak', 'Namunukula Range', 'Badulla', '360° mountain views and starry night camps in Uva.', 'Steep trails; fitness and warm-gear checks beforehand.', 'LKR', 'Sinhala, Tamil, English', 9800.00, 6.99340090, 81.05498150, 'Yes', '2025-09-13 18:01:35');

-- --------------------------------------------------------

--
-- Table structure for table `inactive_users`
--

CREATE TABLE `inactive_users` (
  `inactive_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('Customer','Renter','Guide') NOT NULL,
  `email` varchar(150) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inactive_users`
--

INSERT INTO `inactive_users` (`inactive_id`, `user_id`, `role`, `email`, `first_name`, `last_name`, `phone_number`, `reason`, `deleted_at`, `deleted_by`) VALUES
(1, 232, 'Customer', 'madu@gmail.com', 'Unknown', 'User', '000-000-0000', 'Deleted by admin', '2025-09-14 18:22:02', 1);

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

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`location_id`, `name`, `type`, `district`, `description`, `climate`, `wildlife`, `water_resources`, `safety_tips`, `important_details`, `latitude`, `longitude`, `created_at`) VALUES
(11, 'Diyasaru Park', 'Camping', 'Colombo', 'A unique urban wetland close to Colombo, ideal for campers who want to experience nature without leaving the city. The park features birdwatching towers, walking trails, and lakeside views, making it a calm retreat.', 'Tropical climate with warm temperatures (27–32°C). Afternoon rain showers are common during monsoon seasons (May–July, Oct–Dec).', 'Home to over 100 bird species, including kingfishers and herons. Monkeys, water monitors, and butterflies are also frequently spotted.', 'The wetlands feature a calm lake and marshes, but water is not safe for drinking. Carry bottled or filtered water.', 'Stay on designated trails and boardwalks. Watch out for snakes near marshy areas. Mosquito repellent is strongly advised.', 'Located near Sri Jayewardenepura Kotte.\n\n\nEntry may have small fees.\n\n\nBest for day camping and eco-awareness programs.\n\n\nLimited camping allowed; contact park management for permissions.', 6.87956900, 79.92938000, '2025-09-10 01:46:49'),
(12, 'Muthurajawela Marsh', 'Camping', 'Gampaha', 'One of Sri Lanka’s largest coastal marshlands, famous for boat rides through mangroves and birdwatching. Offers peaceful camping for nature enthusiasts.', 'Warm and humid with year-round tropical weather. Best time: December–April when rainfall is lower.', 'Rich ecosystem with over 200 flora species, birds like cormorants and kingfishers, plus crocodiles and fish in waterways.', 'Lagoons and waterways are brackish; not suitable for drinking. Bring bottled water.', 'Avoid swimming due to crocodiles. Always use guided tours or local rangers for safe access.', 'Managed by the Urban Development Authority and conservation groups.\n\n\nCamping is limited; eco-lodges nearby provide safe alternatives.\n\n\nIdeal for birdwatching, photography, and educational trips.', 7.19758000, 79.83243000, '2025-09-10 01:46:49'),
(13, 'Thudugala Waterfall', 'Camping', 'Kalutara', 'A scenic waterfall surrounded by rainforest and rock pools, perfect for refreshing dips and nature camping.', 'Tropical wet climate. Warm year-round (26–30°C), with heavy rainfall during monsoons (May–July, Oct–Nov).', 'Surrounding rainforest is home to monkeys, lizards, and diverse birds.', 'Natural waterfall and pools. Water can be used for washing but not safe for direct drinking.', 'Rocks around falls can be slippery. Avoid camping too close to the waterline during rainy season.', 'Easy access from Kalutara town.\n\n\nPopular with locals, so weekdays are less crowded.\n\n\nNo formal campsite facilities—bring own gear.', 6.57111530, 80.06087530, '2025-09-10 01:46:49'),
(14, 'Wewathenna Mountain', 'Camping', 'Kandy', 'A highland camping spot offering panoramic views of misty valleys, rolling clouds, and surrounding peaks.', 'Cool mountain climate. Daytime ~18–24°C, nights much colder (~10°C). Best visited Nov–April.', 'Occasional sightings of monkeys, wild boar, and mountain bird species.', 'Limited streams; carry sufficient drinking water.', 'Weather changes quickly—bring warm clothes and rain gear. Ensure proper guidance as trails are rugged.', 'Located about 20 km from Kandy.\n\n\nBest suited for experienced campers.\n\n\nNo permanent facilities—pack essentials.', 7.02990000, 81.07880000, '2025-09-10 01:46:49'),
(15, 'Riverston Peak', 'Camping', 'Matale', 'Known as “Mini World’s End,” Riverston Peak offers dramatic cliffs, misty plains, and cold breezes, making it a favorite for adventure campers.', 'Cool and misty climate. Average 15–22°C, with strong winds and frequent fog. Best visited Dec–March.', 'Birdlife, lizards, and occasional deer. Flora includes montane grasslands and pygmy forests.', 'Streams available, but purify before drinking.', 'Be careful near cliffs and slippery trails. Strong winds make it unsafe to pitch tents on exposed ridges.', 'About 30 km from Matale town.\n\n\nParking available at trailhead.\n\n\nLimited facilities—carry your own camping gear and food.', 7.53039000, 80.73306000, '2025-09-10 01:46:49'),
(16, 'Horton Plains', 'Camping', 'Nuwara Eliya', 'A UNESCO World Heritage site with highland grasslands, cloud forests, and iconic spots like World’s End and Baker’s Falls. A must-visit for hikers and nature lovers.', 'Cool and misty year-round. Daytime ~12–18°C, nights drop below 5°C. Best from Jan–Mar; carry warm layers.', 'Home to sambar deer, purple-faced langurs, wild boar, and rare leopards. Excellent for birdwatching with endemic species.', 'Streams and waterfalls present, but not safe for direct drinking. Carry filtered water.', 'Stay on marked trails (e.g., 9.5 km loop). Fog can reduce visibility suddenly. Mobile coverage is weak. Camping is not allowed inside—nearest options in Ohiya and Pattipola.', 'Open 6 AM–4 PM only.\n\n\nEntry tickets required.\n\n\nEco-friendly travel encouraged; avoid plastics.\n\n\nPublic washrooms at entrance.', 6.80209700, 80.80740500, '2025-09-10 01:46:49'),
(17, 'Koggala Lake', 'Camping', 'Galle', 'A beautiful coastal lake dotted with islands, mangroves, and calm waters. Perfect for lakeside camping and water activities.', 'Warm and humid (28–32°C). Afternoon showers common during monsoon (May–July, Oct–Dec).', 'Mangrove habitats shelter water birds, monitor lizards, and small fish species.', 'Lake water not suitable for drinking. Carry bottled water.', 'Use lifejackets when kayaking. Beware of strong sun; bring sun protection.', 'About 20 km from Galle town.\n\n\nBoat rides available to small islands.\n\n\nIdeal for eco-tourism and kayaking groups.', 5.99149100, 80.32553100, '2025-09-10 01:46:49'),
(18, 'Madiha Beach', 'Camping', 'Matara', 'A serene beachfront spot with palm trees, reefs, and strong surfer culture. Known for relaxed seaside camping.', 'Tropical climate, 27–31°C. Monsoon rains May–July, Oct–Nov.', 'Marine life includes reef fish, turtles, and reef corals. Onshore, expect birds and crabs.', 'Sea water only; bring drinking supplies.', 'Strong reef currents—only swim in safe areas. Keep food secure from stray dogs and monkeys.', 'Surfing hotspot with rentals nearby.\n\n\nSafe, calm vibe for campers.\n\n\nRestaurants within walking distance.', 5.93754000, 80.50767000, '2025-09-10 01:46:49'),
(19, 'Yala Buffer Zone', 'Camping', 'Hambantota', 'Wild camping near Sri Lanka’s most famous national park, with chances to hear elephants and leopards at night.', 'Dry zone, hot climate (28–34°C). Best time Feb–July.', 'Close encounters with elephants, deer, peacocks, and sometimes leopards.', 'Streams and waterholes nearby, not safe for drinking. Always carry own supply.', 'Do not camp without authorized guides. Keep food sealed. Avoid late-night wandering.', 'Camping only allowed in guided safari camps.\n\n\nPermits required for Yala buffer camps.\n\n\nExcellent for wildlife photography.', 6.37278000, 81.51694000, '2025-09-10 01:46:49'),
(20, 'Casuarina Beach', 'Camping', 'Jaffna', 'A peaceful white-sand beach with shallow, safe waters, ideal for family-friendly camping.', 'Hot and dry climate (28–34°C). Best months Dec–Apr.', 'Marine life includes reef fish and occasional turtles. Seabirds common.', 'Sea water only; bring drinking water.', 'Sun can be intense—use shade or tents. Respect local cultural norms.', 'About 20 km from Jaffna town.\n\n\nPopular with locals but less crowded than other northern beaches.\n\n\nCalm waters, safe for swimming and kayaking.', 9.66509300, 80.00930300, '2025-09-10 01:46:49'),
(21, 'Iranamadu Tank', 'Camping', 'Kilinochchi', 'A vast reservoir surrounded by open skies and quiet landscapes, offering peaceful lakeside camping.', 'Hot and dry, 28–34°C most of the year. Best months: Dec–Apr, when skies are clear.', 'Attracts many water birds like pelicans, storks, and herons. Occasional sightings of freshwater fish and reptiles.', 'Tank water is not recommended for drinking. Bring bottled water.', 'Avoid swimming in deep areas. Sun exposure is high—carry shade and hydration.', 'Accessible via A9 road.\n\n\nPopular for birdwatching and stargazing.\n\n\nNo facilities—true wild camping experience.', 9.30038000, 80.45131000, '2025-09-10 01:47:09'),
(22, 'Adam’s Bridge (Rama’s Bridge)', 'Camping', 'Mannar', 'A chain of sandbanks and shallow seas linked to mythology, with stunning views of lagoons and salt flats.', 'Arid and sunny, 28–35°C. Winds can be strong. Best from Dec–Mar.', 'Migratory birds like flamingos and pelicans are major highlights. Shallow waters hold marine life.', 'Only brackish seawater around. Carry your own drinking water.', 'Stay cautious of strong tides and heat. Avoid exploring salt flats without a guide.', 'Historically significant and culturally rich site.\n\n\nCamping spots near coastal stretches.\n\n\nBest combined with birdwatching tours.', 9.08649100, 79.56851900, '2025-09-10 01:47:09'),
(23, 'Madukanda Forest Edge', 'Camping', 'Vavuniya', 'A peaceful camping location at the edge of lush forest and historic Madukanda temple grounds.', 'Dry-zone climate, 27–33°C. Nights are cooler with occasional breezes.', 'Forest birds, monkeys, and reptiles. Occasional wild elephants nearby.', 'Local wells and streams exist, but always purify before drinking.', 'Stay within designated camping zones. Respect cultural sites.', 'Close to Madukanda Raja Maha Viharaya.\n\n\nOffers a blend of culture and nature.\n\n\nIdeal for meditation and peaceful camping.', 8.75980000, 80.54410000, '2025-09-10 01:47:09'),
(24, 'Nayaru Lagoon', 'Camping', 'Mullaitivu', 'A tranquil lagoon surrounded by mangroves, great for kayaking and marine-life exploration.', 'Warm and coastal, 28–32°C. Monsoon rains Oct–Dec.', 'Mangrove habitats support crabs, prawns, and birds like egrets and herons.', 'Lagoon water is saline; not suitable for drinking. Carry own supplies.', 'Be careful with tidal changes. Mosquito repellent is a must.', 'Scenic for kayaking and boating.\n\n\nRemote—minimal tourist activity.\n\n\nPeaceful spot for eco-camping.', 9.13330000, 80.81670000, '2025-09-10 01:47:09'),
(25, 'Marble Beach', 'Camping', 'Trincomalee', 'A pristine white-sand beach with turquoise water, perfect for snorkeling, swimming, and beachfront camping.', 'Hot and sunny, 28–34°C. Best from May–Sep when seas are calmer.', 'Marine species include tropical fish, corals, and occasional turtles.', 'Sea water only; drinking water must be carried.', 'Respect Navy-managed zones. Only swim in safe flagged areas.', 'Maintained by Sri Lanka Air Force.\n\n\nWell-kept, clean environment.\n\n\nIdeal for family-friendly camping.', 8.51228000, 81.21117000, '2025-09-10 01:47:09'),
(26, 'Pasikudah Beach', 'Camping', 'Batticaloa', 'A famous bay with shallow waters stretching hundreds of meters, perfect for safe beachside camping and swimming.', 'Hot and humid, 28–34°C. Best months: May–Sep, when seas are calm.', 'Marine life includes reef fish and corals. Seabirds often seen along the coastline.', 'Sea water only; drinking water must be brought.', 'Shallow water is safe, but avoid venturing far during monsoon. Sun protection is essential.', 'Popular with families and beginner campers.\n\n\nClose to resorts and restaurants.\n\n\nGreat for snorkeling and swimming.', 7.92940000, 81.56900000, '2025-09-10 01:47:09'),
(27, 'Gal Oya National Park', 'Camping', 'Ampara', 'A unique park known for its boat safaris across Senanayake Samudraya, where elephants swim between islands.', 'Dry zone with 27–33°C temperatures. Best months: Mar–Sep.', 'Elephants, crocodiles, deer, and over 150 bird species.', 'Plenty of water from the reservoir, but unsafe to drink untreated.', 'Always camp with ranger support. Be cautious of elephants and crocodiles near water.', 'Entry tickets required.\n\n\nGuided safaris recommended.\n\n\nOne of the best spots for eco-camping.', 7.16667000, 81.41667000, '2025-09-10 01:47:09'),
(28, 'Dolukanda Sacred Rock', 'Camping', 'Kurunegala', 'A legendary site tied to herbal plants of King Ravana, with panoramic views and spiritual vibes.', 'Warm climate, 26–32°C. Cooler breezes at the summit.', 'Birds, lizards, and herbal flora with historic significance.', 'Small seasonal streams; best to carry water.', 'Climb can be steep—wear proper shoes. Avoid during heavy rain.', 'Sacred site with ruins and cultural value.\n\n\nExcellent sunrise and sunset viewpoints.\n\n\nIdeal for history lovers and hikers.', 7.61172000, 80.41130000, '2025-09-10 01:47:09'),
(29, 'Kalpitiya Beach', 'Camping', 'Puttalam', 'A coastal hotspot for kite-surfing, dolphin watching, and beach camping.', 'Dry zone with strong coastal winds. Hot climate (28–35°C).', 'Marine dolphins, reef fish, and sea turtles.', 'Only salty sea water nearby. Drinking water must be carried.', 'High winds—secure tents well. Use certified guides for kite-surfing and dolphin tours.', 'Famous worldwide for kite-surfing.\n\n\nBest season: May–Sep.\n\n\nResorts and eco-lodges nearby for backup.', 8.22953000, 79.75961000, '2025-09-10 01:47:09'),
(30, 'Wilpattu Camping', 'Camping', 'Anuradhapura', 'Sri Lanka’s largest national park, with natural lakes (“villus”), jungle tracks, and historic ruins nearby.', 'Dry zone, hot (27–34°C). Best from Feb–Oct.', 'Leopards, elephants, sloth bears, and deer. Rich birdlife around lakes.', 'Natural villus and tanks, but not safe for drinking.', 'Camping only with park permits and guides. Avoid nighttime walks due to wildlife.', 'Entry tickets required.\n\n\nGuided safaris available.\n\n\nExcellent for wildlife photographers and researchers.', 8.41667000, 80.00000000, '2025-09-10 01:47:09'),
(31, 'Habarana Jungle', 'Camping', 'Polonnaruwa', 'A camping spot within the elephant corridors of Sri Lanka, offering wild nights under starlit skies near historic ruins.', 'Dry zone with hot weather, 28–34°C. Best months: Apr–Sep.', 'Wild elephants are the main highlight. Also deer, monkeys, peacocks, and many birds.', 'Small lakes and waterholes nearby, not suitable for drinking. Bring purified water.', 'Avoid camping alone; elephants may roam at night. Always camp with trained guides.', 'Located close to Sigiriya and Minneriya.\n\n\nBest for safari-style camping.\n\n\nPark permits and ranger supervision required.', 8.03300000, 80.75000000, '2025-09-10 01:47:26'),
(32, 'Madolsima', 'Camping', 'Badulla', 'A breathtaking mountain ridge campsite with dramatic cliff edges and sunrise views above swirling clouds.', 'Cool and breezy, 15–25°C. Mist and rain common during evenings.', 'Birds, butterflies, and small mammals typical of highland ecosystems.', 'Small seasonal streams nearby. Carry sufficient drinking water.', 'Cliffs are dangerous—avoid camping too close to edges. Nights are cold—carry warm clothes.', 'Popular for “Mini World’s End” viewpoint.\n\n\nRemote; no shops or facilities.\n\n\nIdeal for sunrise trekkers.', 7.04670000, 81.15820000, '2025-09-10 01:47:26'),
(33, 'Narangala Peak', 'Camping', 'Badulla', 'One of the most scenic peaks in Uva, offering 360° mountain views and starry night skies for campers.', 'Cool and windy. Day ~18–24°C, nights 8–12°C. Best during dry season (Dec–Apr).', 'Highland birds, lizards, and wild boar occasionally seen.', 'Few water sources; bring enough supplies.', 'Steep trails; only for fit hikers. Nights can be freezing.', 'Trail starts from Keenakele village.\n\n\nPopular among youth hikers.\n\n\nNo facilities—carry camping essentials.', 7.03551000, 81.01039000, '2025-09-10 01:47:26'),
(34, 'Namunukula Range', 'Camping', 'Badulla', 'A mountain range with multiple peaks, hidden trails, and dense forest cover, offering untouched camping experiences.', 'Cool mountain weather, 15–22°C. Frequent mist and occasional rain.', 'Bird species, small reptiles, and wild rabbits.', 'Streams flow in the valleys but must be purified before drinking.', 'Remote area—trek with locals or guides. Weather changes rapidly.', 'Highest peak in Uva Province (~2,035m).\n\n\nCultural significance in local legends.\n\n\nGreat for multi-day hikes.', 6.93265000, 81.11412000, '2025-09-10 01:47:26'),
(35, 'Bogahakumbura Forest', 'Camping', 'Badulla', 'A hidden forest camping site popular with birdwatchers and eco-tourists.', 'Mild highland climate, 18–25°C. Mist common in early mornings.', 'Diverse birdlife, butterflies, and small mammals.', 'Natural springs and streams in the area.', 'Avoid venturing deep alone—dense forest paths can be confusing.', 'Known for eco-friendly experiences.\n\n\nLocal community sometimes offers homestays.\n\n\nPerfect for nature photography.', 6.86194000, 80.87639000, '2025-09-10 01:47:26'),
(36, 'Haputale Ridge', 'Camping', 'Badulla', 'A misty ridge offering spectacular tea plantation views, with iconic spots like Lipton’s Seat nearby.', 'Cool and foggy. Daytime ~15–22°C, nights can drop to ~8°C. Best months: Dec–Apr.', 'Bird species, monkeys, and butterflies are common. Tea estates also shelter small reptiles.', 'Streams flow through the hills, but not always reliable. Carry drinking water.', 'Weather shifts quickly—fog can limit visibility. Avoid camping too close to steep drops.', 'Accessible from Haputale town.\n\n\nFamous for Lipton’s Seat viewpoint.\n\n\nPopular among both hikers and photographers.', 6.76566000, 80.95104000, '2025-09-10 01:47:26'),
(37, 'Mahiyanganaya Riverbank', 'Camping', 'Badulla', 'A calm riverside camping spot with cultural and spiritual surroundings, blending nature with history.', 'Warm and tropical, 26–32°C. Nights cooler by the river.', 'River fish, birds, and occasional monkeys.', 'River provides water, but purification needed.', 'Be cautious of slippery riverbanks. Avoid swimming in strong currents.', 'Close to Mahiyanganaya town and temple.\n\n\nPeaceful setting for cultural and eco-camping.\n\n\nGreat for meditation and riverside relaxation.', 7.33161000, 81.00368000, '2025-09-10 01:47:26'),
(38, 'Udawalawe Border', 'Camping', 'Monaragala', 'Wild camping at the edge of Udawalawe National Park, famous for elephants and scenic landscapes.', 'Hot and dry, 28–35°C. Best months: Dec–May.', 'Elephants, deer, crocodiles, and many bird species.', 'Nearby tanks and rivers; not suitable for drinking untreated.', 'Only camp with rangers or licensed guides. Never leave food outside tents.', 'Entry permits may be required for border zones.\n\n\nGuided safari camping available.\n\n\nExcellent for elephant photography.', 6.47400000, 80.89870000, '2025-09-10 01:47:26'),
(39, 'Belihuloya', 'Camping', 'Ratnapura', 'A forested riverside destination famous for cool streams, natural pools, and biodiversity.', 'Mild and pleasant, 22–28°C. Rain showers possible year-round.', 'Birdlife, butterflies, and freshwater fish. Surrounded by lush greenery.', 'Streams and natural pools available; filter before drinking.', 'Beware of leeches in rainy season. Rocks near streams can be slippery.', 'Popular for adventure sports like rafting.\n\n\nExcellent for eco-camping and short hikes.\n\n\nClose to Sabaragamuwa University area.', 6.71810000, 80.76710000, '2025-09-10 01:47:26'),
(40, 'Knuckles Foothills', 'Camping', 'Kegalle', 'A remote camping spot near the Knuckles Mountain Range, with waterfalls, rare species, and fresh mountain air.', 'Cool and misty, 16–22°C. Frequent rains, especially May–Nov.', 'Endemic species like purple-faced langurs, deer, and rare birds.', 'Many streams and waterfalls, but purification required.', 'Remote location—travel with experienced guides. Trails can be slippery and leech-prone.', 'UNESCO World Heritage region.\n\n\nExcellent for multi-day treks.\n\n\nIdeal for eco-tourism and biodiversity studies.', 7.45000000, 80.80000000, '2025-09-10 01:47:26'),
(46, 'Horton Plains', 'Stargazing', 'Nuwara Eliya', 'Horton Plains offers one of Sri Lanka’s most breathtaking stargazing experiences thanks to its elevation above 2,000m. The open plateau provides wide horizons and crisp, clear skies, making constellations, the Milky Way, and even faint nebulae visible on moonless nights.', 'Cold and misty at night, often dropping below 5°C. Best skies between January–March with dry, clear air.', 'Sambar deer and nocturnal bird calls can be heard. Occasionally leopards, so caution is advised.', 'Streams and waterfalls exist but not potable. Carry your own drinking water.', 'Park entry closes at 4 PM, so stargazing is only possible at nearby camping lodges in Ohiya or Pattipola. Dress warmly.', 'Restricted entry at night.\nBest experience from nearby eco-lodges or viewpoints.\nIdeal for astrophotography with wide horizons.', 6.80209750, 80.80740460, '2025-09-10 02:46:26'),
(47, 'Namunukula Range', 'Stargazing', 'Badulla', 'The Namunukula Range offers 360° panoramic views, making it a dream location for stargazers. Its dark skies reveal both dazzling sunsets and the Milky Way in a single night, creating unforgettable astro-landscapes for photographers.', 'Cool mountain weather, ~12–20°C at night. Mist common in rainy months; best Mar–Apr and Aug–Sep.', 'Owls, nightjars, and small mammals are often heard at night.', 'Valley streams present but purify before drinking.', 'Steep trails; hike with locals. Nights can be very cold and windy—carry layers.', 'Highest peak in Uva Province.\nRemote, dark-sky location—ideal for astrophotography.\nNo shops—self-sufficient camping required.', 6.93333300, 81.11666700, '2025-09-10 02:46:26'),
(48, 'Ritigala Reserve', 'Stargazing', 'Anuradhapura', 'Ritigala Reserve combines ancient ruins with dark, starlit skies for a mystical stargazing experience. The quiet forest setting provides minimal light pollution, making it perfect for night photography with cultural and natural backdrops.', 'Dry-zone climate, 25–30°C evenings. Best skies May–Sep.', 'Cicadas, owls, and occasional deer.', 'Small forest tanks but not drinkable. Carry bottled water.', 'Reserve access is restricted after dark—best to stargaze from surrounding eco-lodges or village edges.', 'Ancient monastery ruins add unique silhouettes.\nQuiet location with minimal light pollution.\nBest for cultural-astro photography mix.', 8.10920000, 80.65460000, '2025-09-10 02:46:26'),
(49, 'Yala Buffer Zone', 'Stargazing', 'Hambantota', 'The Yala Buffer Zone offers one of the most primal stargazing experiences, with wild sounds echoing under star-filled skies. The absence of city lights makes this one of the darkest skies in southern Sri Lanka, perfect for deep-sky views.', 'Hot and dry (28–32°C evenings). Clear skies from Feb–Jul.', 'Elephants, peacocks, and occasional leopard calls at night.', 'Waterholes exist but unsafe to drink. Always bring your own supply.', 'Only stargaze from secure safari camps. Avoid wandering outside after dark due to wild animals.', 'Guided eco-camps provide safe night experiences.\nOne of the darkest skies in southern Sri Lanka.\nIdeal for star trails and soundscapes.', 6.37277778, 81.51694450, '2025-09-10 02:46:26'),
(50, 'Knuckles Mountains', 'Stargazing', 'Kandy', 'The Knuckles Mountains provide a panoramic natural observatory with minimal light pollution. On clear nights, the Milky Way glows brightly above rolling ridges and misty valleys, creating surreal stargazing conditions.', 'Cool mountain nights, ~15°C. Best Nov–Apr when skies are clearest.', 'Night frogs, owls, and endemic highland species audible in the dark.', 'Streams and small waterfalls present but require filtering.', 'Trails are rugged—hike with guides. Mist can cover paths quickly.', 'UNESCO World Heritage site.\nIdeal for multi-day treks with stargazing camps.\nGreat for astro-landscape shots with mountain silhouettes.', 7.40077100, 80.81061600, '2025-09-10 02:46:26'),
(51, 'Minneriya Area', 'Stargazing', 'Polonnaruwa', 'The Minneriya area offers expansive skies above ancient reservoirs, where stars reflect beautifully on still waters. With little light pollution in the dry season, stargazers can enjoy uninterrupted views of constellations and the Milky Way stretching across the horizon.', 'Dry-zone weather, warm evenings (25–30°C). Best clear skies June–Sep during the dry season.', 'Elephants may roam at dusk. Owls and night birds often heard near the tanks.', 'Large reservoirs present, but not safe for drinking. Bring your own water.', 'Avoid camping directly near water due to elephants. Stargaze from eco-camps or village edges.', 'Very low light pollution compared to urban Sri Lanka.\nWater reflections create excellent foregrounds for astrophotography.\nNew moon nights provide the clearest skies.', 7.97889000, 80.84889000, '2025-09-10 02:46:26'),
(52, 'Koggala Lake', 'Stargazing', 'Galle', 'Koggala Lake transforms into a mirror for the night sky, where constellations ripple gently on its surface. With mangrove islands creating natural silhouettes, the lake becomes a peaceful setting for both stargazing and astrophotography.', 'Humid tropical nights, 27–30°C. Best skies Jan–Apr when monsoons recede.', 'Nighttime calls of herons, owls, and water birds.', 'Lake water unsuitable for drinking—carry bottled supplies.', 'Use safe boat operators if stargazing from the lake. Mosquito protection is a must.', 'Reflections add creative astro-compositions.\nNearby urban lights may slightly reduce sky quality—choose secluded lake edges.\nPlan for long-exposure shots to capture stars mirrored in the water.', 6.00000000, 80.33333300, '2025-09-10 02:46:26'),
(53, 'Riverston', 'Stargazing', 'Matale', 'At Riverston, highland cliffs rise above rolling valleys, providing sweeping night skies free from city glow. The strong winds and crisp mountain air make it a dramatic location for star trails and timelapse astrophotography.', 'Cool mountain climate, ~15–20°C at night. Frequent winds—best Dec–Mar.', 'Night birds and insects dominate the soundscape.', 'Streams flow nearby but treat before drinking.', 'Cliffs are steep—never set up gear too close to edges. Strong winds can topple tripods.', 'Known for dramatic astro-landscapes with mountain silhouettes.\nOne of the darkest sky regions in the central highlands.\nIdeal for capturing Milky Way arcs in early morning hours.', 7.52375000, 80.73708000, '2025-09-10 02:46:26'),
(54, 'Casuarina Beach', 'Stargazing', 'Jaffna', 'Casuarina Beach offers wide-open horizons where the ocean meets the night sky. With minimal artificial lighting, it’s an ideal location to witness meteor showers, star clusters, and bright planetary alignments over the water.', 'Dry coastal nights, 26–30°C. Best skies Dec–Apr when humidity is low.', 'Shore crabs and seabirds active at night.', 'No fresh water nearby; bring sufficient supplies.', 'Avoid camping too close to the shoreline during high tide. Respect cultural norms in the area.', 'Minimal light pollution compared to southern beaches.\nExcellent for long meteor exposures with open skies.\nGreat for observing constellations rising over the horizon.', 9.76308000, 79.88661000, '2025-09-10 02:46:26'),
(55, 'Nilaveli Beach', 'Stargazing', 'Trincomalee', 'Nilaveli Beach is a stargazer’s paradise on Sri Lanka’s east coast, offering wide sandy shores with minimal light intrusion. During dry-season nights, the constellations shine brilliantly, and the Milky Way can be seen stretching across the ocean.', 'Dry-zone coastal climate, ~27–31°C nights. Best skies Apr–Sep.', 'Occasional sea turtles, night crabs, and seabirds.', 'Sea water only; carry fresh water.', 'Stay clear of strong currents and avoid isolated areas alone.', 'Open eastern horizon makes it perfect for capturing moonrises and planetary alignments.\nBest dark-sky conditions during new moon periods.\nPopular among astrophotographers for Milky Way visibility over the ocean.', 8.69273300, 81.18853000, '2025-09-10 02:46:26'),
(56, 'Ella Rock', 'Stargazing', 'Badulla', 'Ella Rock provides one of the most dramatic stargazing backdrops in Sri Lanka, with its high cliff edges overlooking lush valleys. On clear nights, the Milky Way arcs across the sky, creating a breathtaking sight for both campers and astrophotographers. The elevated position ensures minimal light interference for capturing night-sky panoramas.', 'Cool and breezy nights, ~15–20°C. Best stargazing conditions Dec–Apr.', 'Night insects, owls, and occasional small mammals can be heard.', 'No reliable streams at the summit—carry all drinking water.', 'The hike is steep and risky in the dark. Best to camp before sunset and set up gear early.', 'High vantage point gives sweeping horizon views.\nPopular astrophotography site for capturing the Milky Way core.\nAvoid full-moon nights for the clearest skies.', 6.85790000, 81.04680000, '2025-09-10 02:51:01'),
(57, 'Sinharaja Edge', 'Stargazing', 'Ratnapura', 'The edges of the Sinharaja rainforest offer an enchanting combination of dark skies and glowing jungle silhouettes. Mist often drifts through the treetops, making starlit scenes look mystical and dreamlike. For photographers, the mix of forest and star fields creates rare and atmospheric compositions.', 'Humid tropical forest climate, ~22–27°C at night. Clear skies possible Jan–Mar.', 'Nocturnal frogs, insects, and rare owls echo through the forest.', 'Streams available but require filtration.', 'Leeches and snakes may be present at night—use boots and repellent.', 'Dense jungle adds mysterious foregrounds to astro-shots.\nCloud inversions often glow with moonlight, creating surreal scenes.\nBest with guided eco-lodges near forest borders.', 6.41670000, 80.50000000, '2025-09-10 02:51:01'),
(58, 'Wilpattu Vicinity', 'Stargazing', 'Kurunegala', 'The wilderness near Wilpattu is famous for its pristine dark skies and iconic baobab tree silhouettes. With almost no artificial lighting, the stars blaze brightly, making this one of the best spots for long-exposure astrophotography. The silence of the forest adds to the immersive night-sky experience.', 'Dry-zone climate, ~25–30°C evenings. Best skies Feb–Sep.', 'Elephants and deer roam nearby; owls and jackals may be heard.', 'Nearby lakes exist but are unsafe to drink.', 'Do not venture into wild areas without local guides. Stay within secure zones.', 'Very low light pollution, perfect for deep-sky photography.\nUnique baobab tree silhouettes against the Milky Way.\nRemote location ensures minimal disturbance for long exposures.', 8.35000000, 80.10000000, '2025-09-10 02:51:01'),
(59, 'Kalametiya Beach', 'Stargazing', 'Matara', 'Kalametiya Beach combines the sound of waves with expansive starlit skies, creating a tranquil coastal stargazing experience. Its lagoons reflect starlight, offering photographers a chance to capture both celestial and water landscapes in a single frame. Nights here feel untouched and serene, perfect for quiet observation.', 'Warm tropical climate, ~26–30°C nights. Clear skies Jan–Apr.', 'Wetland birds and coastal crabs active at night.', 'Lagoon and sea present, but not drinkable. Carry water.', 'Be mindful of tides and avoid isolated zones at night.', 'Open horizon offers wide Milky Way views.\nLagoon reflections add creative astrophotography options.\nBest during new moon phases for darker skies.', 6.05760000, 80.93370000, '2025-09-10 02:51:01'),
(60, 'Udawalawe Vicinity', 'Stargazing', 'Monaragala', 'The grasslands around Udawalawe open up to vast, unobstructed night skies that seem endless. Star trails and the Milky Way shine vividly here, making it a prime destination for astrophotography. The combination of wilderness and silence creates an unforgettable night under the stars.', 'Hot and dry evenings, ~28–32°C. Best skies Dec–May.', 'Elephants, deer, and night birds often visible from a distance.', 'Nearby rivers and tanks, not safe for drinking untreated.', 'Stay in ranger-approved areas—wildlife is active at night.', 'Wide open grasslands ensure minimal obstructions for astrophotography.\nExcellent for shooting ISS passes and satellite trails.\nLow light pollution makes it one of the best southern dark-sky areas.', 6.42676000, 80.87234000, '2025-09-10 02:51:01'),
(61, 'Kalpitiya', 'Stargazing', 'Puttalam', 'Kalpitiya is one of Sri Lanka’s best coastal dark-sky spots, where meteors, satellites, and even the ISS can be seen cutting across the night sky. The dry winds and wide-open beaches make it ideal for astrophotographers who want clear horizons over the ocean. Its mix of stargazing and adventure, with kite-surf silhouettes by day and Milky Way trails by night, creates a unique experience.', 'Hot and breezy coastal climate, ~27–32°C at night. Best skies May–Sep.', 'Dolphins offshore during day; seabirds and crabs active at night.', 'Sea water only; carry bottled supplies.', 'Strong winds can affect telescopes and cameras—secure your gear.', 'Low light pollution compared to other west-coast beaches.\nExcellent for satellite trails, ISS spotting, and meteor showers.\nCombine stargazing with daytime kite-surfing for a unique experience.', 8.22940000, 79.75960000, '2025-09-10 02:51:01'),
(62, 'Mahiyanganaya Fields', 'Stargazing', 'Badulla', 'Mahiyanganaya’s wide paddy fields open to starry skies unobstructed by trees or city lights. Fireflies often glow among the fields, creating a magical contrast against the Milky Way above. With nearby temples casting a soft glow, the mix of culture and nature enhances the stargazing experience.', 'Warm tropical climate, ~24–28°C evenings. Best skies Dec–Apr.', 'Fireflies glow in fields; owls and bats frequent at night.', 'Nearby rivers available but not safe to drink untreated.', 'Avoid entering fields during growing seasons. Carry mosquito protection.', 'Unique combination of natural fireflies and starry skies.\nGreat for wide-angle astrophotography.\nVillage temples often make striking illuminated foregrounds.', 7.32700000, 81.01600000, '2025-09-10 02:51:01'),
(63, 'Pinnawala Foothills', 'Stargazing', 'Kegalle', 'The Pinnawala foothills are a hidden gem for stargazing, offering crisp mountain air and tea-field backdrops. The relatively low light pollution makes it easy to observe constellations and capture clear astro-landscapes. It’s an accessible dark-sky location close to Colombo yet still immersed in natural beauty.', 'Mild foothill climate, ~18–22°C at night. Clear skies common Dec–Mar.', 'Bats, owls, and night insects fill the soundscape.', 'Small tea streams exist; treat before use.', 'Trails can be slippery—set up gear on stable ground.', 'Great balance of accessibility and dark skies near central hills.\nTea plantations add layered textures for astro compositions.\nExcellent spot for stargazers who want easy access from Kandy/Colombo.', 7.30040000, 80.38510000, '2025-09-10 02:51:01'),
(64, 'Forest Reserve', 'Stargazing', 'Vavuniya', 'The remote forests around Vavuniya provide some of the darkest skies in northern Sri Lanka. With no nearby city glow, the Milky Way appears sharp and bright, ideal for long-exposure photography. The silence of the jungle at night adds to the awe of stargazing in complete wilderness.', 'Dry zone climate, ~25–30°C nights. Best stargazing Jan–Jul.', 'Owls, nightjars, and occasional elephants.', 'Local wells and streams; not potable without purification.', 'Remote area—always visit with guides. Wild animals roam freely.', 'Minimal artificial light, making it a top dark-sky zone.\nGreat for Milky Way panoramas and long star trails.\nIdeal for professional astrophotographers seeking pristine skies.', 8.75000000, 80.50000000, '2025-09-10 02:51:01'),
(65, 'Gal Oya Vicinity', 'Stargazing', 'Ampara', 'The Gal Oya area offers starry skies reflected in calm reservoir waters, surrounded by jungle wilderness. On new moon nights, the Milky Way appears mirrored in the still water, making for dramatic astro-compositions. The combination of stars, reflections, and wildlife sounds makes it one of the most atmospheric stargazing sites in the east.', 'Dry zone, ~26–30°C evenings. Best skies Feb–Jul.', 'Elephants and crocodiles near the reservoir; frogs and birds active at night.', 'Reservoir water not safe for drinking—carry purified water.', 'Never stargaze close to reservoir banks due to wildlife. Stay in designated eco-camps.', 'Excellent for reflection shots of stars over calm waters.\nClear horizons make it suitable for Milky Way core photography.\nLow light pollution enhances deep-sky visibility.', 7.22380000, 81.45910000, '2025-09-10 02:51:01'),
(66, 'Kalametiya Sanctuary', 'Stargazing', 'Hambantota', 'Kalametiya Sanctuary is a lagoon-side paradise where the stars shine undisturbed by city lights. The still waters reflect constellations, while the soft sounds of wetland birds and waves create a peaceful night atmosphere. It’s an excellent choice for stargazers who love nature-based astro experiences.', 'Dry coastal climate, ~27–32°C nights. Best clear skies Feb–Jul.', 'Night herons, crabs, and lagoon fish are active. Fireflies can sometimes be seen.', 'Lagoon water is saline—carry bottled water.', 'Avoid staying too close to water’s edge at night due to crocodiles and tides.', 'Excellent for photographing star reflections over lagoon surfaces.\nVery low light pollution in this southern coastal area.\nBest during new moon nights for astrophotography.', 6.07530000, 80.93080000, '2025-09-10 02:51:27'),
(67, 'Diyatha Uyana', 'Stargazing', 'Colombo', 'Diyatha Uyana is one of the few urban spots in Colombo where stargazing is possible. While deep-sky views are limited, it’s a great place to observe planets, lunar phases, and special celestial events with minimal setup. Families and beginner astrophotographers often use this site for practice.', 'Hot and humid nights, ~28–30°C. Visibility better Dec–Apr with less rain.', 'Urban birds, bats, and fish in the artificial lake.', 'Artificial lake present, not suitable for drinking.', 'Stay in well-lit areas; urban stargazing may attract crowds.', 'Light pollution limits deep-sky viewing, but bright planets are visible.\nGood for beginner astrophotographers practicing alignment shots.\nSpecial events like eclipses or conjunctions are easier to observe here.', 6.91820000, 79.95860000, '2025-09-10 02:51:27'),
(68, 'Udugampola Forest', 'Stargazing', 'Gampaha', 'Just outside Colombo, the Udugampola Forest provides a surprisingly dark sky environment. Its proximity to the city makes it convenient for quick night sessions while still giving clear views of constellations. The natural forest setting adds a peaceful backdrop for stargazing.', 'Warm tropical climate, ~27–30°C. Best skies Jan–Apr.', 'Night birds, owls, and bats commonly found.', 'Small forest streams; not reliable for drinking.', 'Remote after dark—go in groups. Use mosquito protection.', 'Provides dark-sky conditions without long travel from Colombo.\nExcellent for short astrophotography trips.\nSuitable for capturing constellations and star clusters.', 7.12900000, 79.97000000, '2025-09-10 02:51:27'),
(69, 'Bentota Beach', 'Stargazing', 'Kalutara', 'Bentota Beach offers stargazers a mix of gentle ocean breezes and wide-open skies. After sunset, stars begin to sparkle above the western horizon, creating dreamy views with the waves below. Its accessibility makes it popular for casual observers as well as night photographers.', 'Warm coastal climate, ~27–31°C evenings. Best skies Dec–Apr.', 'Crabs and seabirds active at night.', 'Sea water only; bring fresh supplies.', 'Beware of tides and currents. Stay in safe, known beach zones.', 'Wide horizons make it ideal for sunset-to-starlight transitions.\nWest-facing beach is great for twilight and early-night astro shots.\nSlight light pollution from resorts—choose less crowded spots.', 6.42270000, 79.99730000, '2025-09-10 02:51:27'),
(70, 'Hakgala Edge', 'Stargazing', 'Nuwara Eliya', 'Hakgala Edge is a high-altitude stargazing point with crisp, clear air that reveals countless stars. The elevation reduces haze, giving sharper views of constellations, nebulae, and even faint star clusters. This location is a favorite among serious astrophotographers who want deep-sky clarity.', 'Cold nights, ~8–15°C. Best clear skies Jan–Apr.', 'Mountain owls, night insects, and occasional sambar deer nearby.', 'Streams and springs in lower valleys; carry treated water.', 'Nights are freezing—carry thermal clothing. Mist can reduce visibility suddenly.', 'High elevation reduces haze, improving star clarity.\nExcellent for deep-sky astrophotography.\nAmong the darkest skies in Nuwara Eliya district.', 6.93890000, 80.81860000, '2025-09-10 02:51:27'),
(71, 'Belihuloya Valley', 'Stargazing', 'Ratnapura', 'Belihuloya Valley offers wide-open skies framed by river valleys and misty ridges. The combination of flowing water and star-filled skies creates a calm, immersive environment for stargazing. Long-exposure photography captures spectacular star arcs over the valley landscape.', 'Mild and cool, ~20–25°C at night. Best skies Jan–Apr.', 'Night birds, frogs, and river fish add natural ambience.', 'Streams and river water available—filter before drinking.', 'Be mindful of slippery rocks near rivers. Leeches may be present in damp months.', 'Excellent valley perspective for capturing long star trails.\nGreat balance of dark skies and accessibility.\nIdeal for astrophotographers seeking river-reflection compositions.', 6.71670000, 80.78330000, '2025-09-10 02:51:27'),
(72, 'Dambulla Cave Area', 'Stargazing', 'Matale', 'The Dambulla cave area combines cultural heritage with celestial wonders. At night, the dark skies above the cave temples form dramatic silhouettes, creating a unique foreground for astro-photography. It’s a location where history, spirituality, and astronomy beautifully intersect.', 'Warm tropical climate, ~25–28°C at night. Clear skies Mar–Sep.', 'Bats from nearby caves, plus owls and insects.', 'Small tanks nearby but not potable.', 'Respect the sacred area—avoid entering temple grounds at night.', 'Stunning contrasts of temple outlines against the Milky Way.\nVery little artificial lighting near forested edges.\nGreat for astro–cultural landscape photography.', 7.85610000, 80.64920000, '2025-09-10 02:51:27'),
(73, 'Kaudulla Vicinity', 'Stargazing', 'Polonnaruwa', 'The Kaudulla vicinity provides a quiet escape for stargazers, with its reservoirs mirroring the stars. On clear nights, the Milky Way arches above elephant pathways, adding a wilderness touch to the experience. The tranquil setting ensures minimal distractions, perfect for long observation sessions.', 'Dry zone, ~26–30°C nights. Best clear skies May–Sep.', 'Elephants often cross at dusk; owls and nightjars audible.', 'Reservoir water nearby—unsafe for drinking.', 'Stay a safe distance from elephant corridors. Use ranger-approved zones.', 'Reservoir reflections enhance astro-compositions.\nRemote location ensures dark-sky quality.\nPerfect for Milky Way photography during new moon.', 8.10000000, 80.93330000, '2025-09-10 02:51:27'),
(74, 'Marble Beach', 'Stargazing', 'Trincomalee', 'Marble Beach offers a pristine shoreline where stargazing is paired with the gentle rhythm of waves. The white sand enhances the brightness of the scene, creating a serene setting for barefoot observation. With little light pollution, it’s perfect for relaxed skywatching or astrophotography.', 'Hot coastal nights, ~27–31°C. Best skies Apr–Sep.', 'Crabs and seabirds active on the shoreline.', 'Sea water only; bring fresh water.', 'Stay in Navy-approved visitor zones. Watch for tides.', 'Clean, open beach with minimal light pollution.\nIdeal for combining stargazing with camping.\nPerfect site for capturing constellations over the Indian Ocean.', 8.51230000, 81.21060000, '2025-09-10 02:51:27'),
(75, 'Kallady Beach', 'Stargazing', 'Batticaloa', 'Kallady Beach is a serene eastern coast location where moonrises and planets shine vividly above the horizon. The gentle sound of waves adds tranquility while stargazers enjoy uninterrupted views of the night sky. It’s an excellent place for spotting planetary alignments and shooting coastal astro-scenes.', 'Warm coastal climate, ~27–30°C evenings. Best skies Apr–Sep.', 'Night crabs, sea turtles, and seabirds occasionally spotted.', 'Sea water only; bring own supply.', 'Stay mindful of currents and avoid isolated sections alone.', 'Excellent for photographing moonrises and planetary conjunctions.\nWide eastern horizon ideal for astrophotography.\nRemote beaches provide naturally dark-sky conditions.', 7.71680000, 81.71600000, '2025-09-10 02:51:27');

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

--
-- Dumping data for table `location_images`
--

INSERT INTO `location_images` (`image_id`, `location_id`, `image_path`, `uploaded_at`) VALUES
(1, 11, '/skycamp-backend/storage/uploads/locations/camping_destinations/diyasaru_park1.jpg', '2025-09-12 17:46:39'),
(2, 11, '/skycamp-backend/storage/uploads/locations/camping_destinations/diyasaru_park2.jpg', '2025-09-12 17:46:39'),
(3, 11, '/skycamp-backend/storage/uploads/locations/camping_destinations/diyasaru_park3.jpg', '2025-09-12 17:46:39'),
(4, 12, '/skycamp-backend/storage/uploads/locations/camping_destinations/muthurajawela_marsh1.jpg', '2025-09-12 17:46:39'),
(5, 12, '/skycamp-backend/storage/uploads/locations/camping_destinations/muthurajawela_marsh2.jpg', '2025-09-12 17:46:39'),
(6, 12, '/skycamp-backend/storage/uploads/locations/camping_destinations/muthurajawela_marsh3.jpg', '2025-09-12 17:46:39'),
(7, 13, '/skycamp-backend/storage/uploads/locations/camping_destinations/thudugala_waterfall1.jpg', '2025-09-12 17:46:39'),
(8, 13, '/skycamp-backend/storage/uploads/locations/camping_destinations/thudugala_waterfall2.jpg', '2025-09-12 17:46:39'),
(9, 13, '/skycamp-backend/storage/uploads/locations/camping_destinations/thudugala_waterfall3.jpg', '2025-09-12 17:46:39'),
(10, 14, '/skycamp-backend/storage/uploads/locations/camping_destinations/wewathenna_mountain1.jpg', '2025-09-12 17:46:39'),
(11, 14, '/skycamp-backend/storage/uploads/locations/camping_destinations/wewathenna_mountain2.jpg', '2025-09-12 17:46:39'),
(12, 14, '/skycamp-backend/storage/uploads/locations/camping_destinations/wewathenna_mountain3.jpg', '2025-09-12 17:46:39'),
(13, 15, '/skycamp-backend/storage/uploads/locations/camping_destinations/riverston_peak1.jpg', '2025-09-12 17:46:39'),
(14, 15, '/skycamp-backend/storage/uploads/locations/camping_destinations/riverston_peak2.jpg', '2025-09-12 17:46:39'),
(15, 15, '/skycamp-backend/storage/uploads/locations/camping_destinations/riverston_peak3.jpg', '2025-09-12 17:46:39'),
(16, 16, '/skycamp-backend/storage/uploads/locations/camping_destinations/horton_plains1.jpg', '2025-09-12 17:46:39'),
(17, 16, '/skycamp-backend/storage/uploads/locations/camping_destinations/horton_plains2.jpg', '2025-09-12 17:46:39'),
(18, 16, '/skycamp-backend/storage/uploads/locations/camping_destinations/horton_plains3.jpg', '2025-09-12 17:46:39'),
(19, 17, '/skycamp-backend/storage/uploads/locations/camping_destinations/koggala_lake1.jpg', '2025-09-12 17:46:39'),
(20, 17, '/skycamp-backend/storage/uploads/locations/camping_destinations/koggala_lake2.jpg', '2025-09-12 17:46:39'),
(21, 17, '/skycamp-backend/storage/uploads/locations/camping_destinations/koggala_lake3.jpg', '2025-09-12 17:46:39'),
(22, 18, '/skycamp-backend/storage/uploads/locations/camping_destinations/madiha_beach1.jpg', '2025-09-12 17:46:39'),
(23, 18, '/skycamp-backend/storage/uploads/locations/camping_destinations/madiha_beach2.jpg', '2025-09-12 17:46:39'),
(24, 18, '/skycamp-backend/storage/uploads/locations/camping_destinations/madiha_beach3.jpg', '2025-09-12 17:46:39'),
(25, 19, '/skycamp-backend/storage/uploads/locations/camping_destinations/yala_buffer_zone1.jpg', '2025-09-12 17:46:39'),
(26, 19, '/skycamp-backend/storage/uploads/locations/camping_destinations/yala_buffer_zone2.jpg', '2025-09-12 17:46:39'),
(27, 19, '/skycamp-backend/storage/uploads/locations/camping_destinations/yala_buffer_zone3.jpg', '2025-09-12 17:46:39'),
(28, 20, '/skycamp-backend/storage/uploads/locations/camping_destinations/casuarina_beach1.jpg', '2025-09-12 17:46:39'),
(29, 20, '/skycamp-backend/storage/uploads/locations/camping_destinations/casuarina_beach2.jpg', '2025-09-12 17:46:39'),
(30, 20, '/skycamp-backend/storage/uploads/locations/camping_destinations/casuarina_beach3.jpg', '2025-09-12 17:46:39'),
(31, 21, '/skycamp-backend/storage/uploads/locations/camping_destinations/iranamadu_tank1.jpg', '2025-09-12 17:46:39'),
(32, 21, '/skycamp-backend/storage/uploads/locations/camping_destinations/iranamadu_tank2.jpg', '2025-09-12 17:46:39'),
(33, 21, '/skycamp-backend/storage/uploads/locations/camping_destinations/iranamadu_tank3.jpg', '2025-09-12 17:46:39'),
(34, 22, '/skycamp-backend/storage/uploads/locations/camping_destinations/adams_bridge_ramas_bridge1.jpg', '2025-09-12 17:46:39'),
(35, 22, '/skycamp-backend/storage/uploads/locations/camping_destinations/adams_bridge_ramas_bridge2.jpg', '2025-09-12 17:46:39'),
(36, 22, '/skycamp-backend/storage/uploads/locations/camping_destinations/adams_bridge_ramas_bridge3.jpg', '2025-09-12 17:46:39'),
(37, 23, '/skycamp-backend/storage/uploads/locations/camping_destinations/madukanda_forest_edge1.jpg', '2025-09-12 17:46:39'),
(38, 23, '/skycamp-backend/storage/uploads/locations/camping_destinations/madukanda_forest_edge2.jpg', '2025-09-12 17:46:39'),
(39, 23, '/skycamp-backend/storage/uploads/locations/camping_destinations/madukanda_forest_edge3.jpg', '2025-09-12 17:46:39'),
(40, 24, '/skycamp-backend/storage/uploads/locations/camping_destinations/nayaru_lagoon1.jpg', '2025-09-12 17:46:39'),
(41, 24, '/skycamp-backend/storage/uploads/locations/camping_destinations/nayaru_lagoon2.jpg', '2025-09-12 17:46:39'),
(42, 24, '/skycamp-backend/storage/uploads/locations/camping_destinations/nayaru_lagoon3.jpg', '2025-09-12 17:46:39'),
(43, 25, '/skycamp-backend/storage/uploads/locations/camping_destinations/marble_beach1.jpg', '2025-09-12 17:46:39'),
(44, 25, '/skycamp-backend/storage/uploads/locations/camping_destinations/marble_beach2.jpg', '2025-09-12 17:46:39'),
(45, 25, '/skycamp-backend/storage/uploads/locations/camping_destinations/marble_beach3.jpg', '2025-09-12 17:46:39'),
(46, 26, '/skycamp-backend/storage/uploads/locations/camping_destinations/pasikudah_beach1.jpg', '2025-09-12 17:46:39'),
(47, 26, '/skycamp-backend/storage/uploads/locations/camping_destinations/pasikudah_beach2.jpg', '2025-09-12 17:46:39'),
(48, 26, '/skycamp-backend/storage/uploads/locations/camping_destinations/pasikudah_beach3.jpg', '2025-09-12 17:46:39'),
(49, 27, '/skycamp-backend/storage/uploads/locations/camping_destinations/gal_oya_national_park1.jpg', '2025-09-12 17:46:39'),
(50, 27, '/skycamp-backend/storage/uploads/locations/camping_destinations/gal_oya_national_park2.jpg', '2025-09-12 17:46:39'),
(51, 27, '/skycamp-backend/storage/uploads/locations/camping_destinations/gal_oya_national_park3.jpg', '2025-09-12 17:46:39'),
(52, 28, '/skycamp-backend/storage/uploads/locations/camping_destinations/dolukanda_sacred_rock1.jpg', '2025-09-12 17:46:39'),
(53, 28, '/skycamp-backend/storage/uploads/locations/camping_destinations/dolukanda_sacred_rock2.jpg', '2025-09-12 17:46:39'),
(54, 28, '/skycamp-backend/storage/uploads/locations/camping_destinations/dolukanda_sacred_rock3.jpg', '2025-09-12 17:46:39'),
(55, 29, '/skycamp-backend/storage/uploads/locations/camping_destinations/kalpitiya_beach1.jpg', '2025-09-12 17:46:39'),
(56, 29, '/skycamp-backend/storage/uploads/locations/camping_destinations/kalpitiya_beach2.jpg', '2025-09-12 17:46:39'),
(57, 29, '/skycamp-backend/storage/uploads/locations/camping_destinations/kalpitiya_beach3.jpg', '2025-09-12 17:46:39'),
(58, 30, '/skycamp-backend/storage/uploads/locations/camping_destinations/wilpattu_camping1.jpg', '2025-09-12 17:46:39'),
(59, 30, '/skycamp-backend/storage/uploads/locations/camping_destinations/wilpattu_camping2.jpg', '2025-09-12 17:46:39'),
(60, 30, '/skycamp-backend/storage/uploads/locations/camping_destinations/wilpattu_camping3.jpg', '2025-09-12 17:46:39'),
(61, 31, '/skycamp-backend/storage/uploads/locations/camping_destinations/habarana_jungle1.jpg', '2025-09-12 17:46:39'),
(62, 31, '/skycamp-backend/storage/uploads/locations/camping_destinations/habarana_jungle2.jpg', '2025-09-12 17:46:39'),
(63, 31, '/skycamp-backend/storage/uploads/locations/camping_destinations/habarana_jungle3.jpg', '2025-09-12 17:46:39'),
(64, 32, '/skycamp-backend/storage/uploads/locations/camping_destinations/madolsima1.jpg', '2025-09-12 17:46:39'),
(65, 32, '/skycamp-backend/storage/uploads/locations/camping_destinations/madolsima2.jpg', '2025-09-12 17:46:39'),
(66, 32, '/skycamp-backend/storage/uploads/locations/camping_destinations/madolsima3.jpg', '2025-09-12 17:46:39'),
(67, 33, '/skycamp-backend/storage/uploads/locations/camping_destinations/narangala_peak1.jpg', '2025-09-12 17:46:39'),
(68, 33, '/skycamp-backend/storage/uploads/locations/camping_destinations/narangala_peak2.jpg', '2025-09-12 17:46:39'),
(69, 33, '/skycamp-backend/storage/uploads/locations/camping_destinations/narangala_peak3.jpg', '2025-09-12 17:46:39'),
(70, 34, '/skycamp-backend/storage/uploads/locations/camping_destinations/namunukula_range1.jpg', '2025-09-12 17:46:39'),
(71, 34, '/skycamp-backend/storage/uploads/locations/camping_destinations/namunukula_range2.jpg', '2025-09-12 17:46:39'),
(72, 34, '/skycamp-backend/storage/uploads/locations/camping_destinations/namunukula_range3.jpg', '2025-09-12 17:46:39'),
(73, 35, '/skycamp-backend/storage/uploads/locations/camping_destinations/bogahakumbura_forest1.jpg', '2025-09-12 17:46:39'),
(74, 35, '/skycamp-backend/storage/uploads/locations/camping_destinations/bogahakumbura_forest2.jpg', '2025-09-12 17:46:39'),
(75, 35, '/skycamp-backend/storage/uploads/locations/camping_destinations/bogahakumbura_forest3.jpg', '2025-09-12 17:46:39'),
(76, 36, '/skycamp-backend/storage/uploads/locations/camping_destinations/haputale_ridge1.jpg', '2025-09-12 17:46:39'),
(77, 36, '/skycamp-backend/storage/uploads/locations/camping_destinations/haputale_ridge2.jpg', '2025-09-12 17:46:39'),
(78, 36, '/skycamp-backend/storage/uploads/locations/camping_destinations/haputale_ridge3.jpg', '2025-09-12 17:46:39'),
(79, 37, '/skycamp-backend/storage/uploads/locations/camping_destinations/mahiyanganaya_riverbank1.jpg', '2025-09-12 17:46:39'),
(80, 37, '/skycamp-backend/storage/uploads/locations/camping_destinations/mahiyanganaya_riverbank2.jpg', '2025-09-12 17:46:39'),
(81, 37, '/skycamp-backend/storage/uploads/locations/camping_destinations/mahiyanganaya_riverbank3.jpg', '2025-09-12 17:46:39'),
(82, 38, '/skycamp-backend/storage/uploads/locations/camping_destinations/udawalawe_border1.jpg', '2025-09-12 17:46:39'),
(83, 38, '/skycamp-backend/storage/uploads/locations/camping_destinations/udawalawe_border2.jpg', '2025-09-12 17:46:39'),
(84, 38, '/skycamp-backend/storage/uploads/locations/camping_destinations/udawalawe_border3.jpg', '2025-09-12 17:46:39'),
(85, 39, '/skycamp-backend/storage/uploads/locations/camping_destinations/belihuloya1.jpg', '2025-09-12 17:46:39'),
(86, 39, '/skycamp-backend/storage/uploads/locations/camping_destinations/belihuloya2.jpg', '2025-09-12 17:46:39'),
(87, 39, '/skycamp-backend/storage/uploads/locations/camping_destinations/belihuloya3.jpg', '2025-09-12 17:46:39'),
(88, 40, '/skycamp-backend/storage/uploads/locations/camping_destinations/knuckles_foothills1.jpg', '2025-09-12 17:46:39'),
(89, 40, '/skycamp-backend/storage/uploads/locations/camping_destinations/knuckles_foothills2.jpg', '2025-09-12 17:46:39'),
(90, 40, '/skycamp-backend/storage/uploads/locations/camping_destinations/knuckles_foothills3.jpg', '2025-09-12 17:46:39'),
(91, 46, '/skycamp-backend/storage/uploads/locations/stargazing_spots/horton_plains1.jpg', '2025-09-12 17:46:39'),
(92, 46, '/skycamp-backend/storage/uploads/locations/stargazing_spots/horton_plains2.jpg', '2025-09-12 17:46:39'),
(93, 46, '/skycamp-backend/storage/uploads/locations/stargazing_spots/horton_plains3.jpg', '2025-09-12 17:46:39'),
(94, 47, '/skycamp-backend/storage/uploads/locations/stargazing_spots/namunukula_range1.jpg', '2025-09-12 17:46:39'),
(95, 47, '/skycamp-backend/storage/uploads/locations/stargazing_spots/namunukula_range2.jpg', '2025-09-12 17:46:39'),
(96, 47, '/skycamp-backend/storage/uploads/locations/stargazing_spots/namunukula_range3.jpg', '2025-09-12 17:46:39'),
(97, 48, '/skycamp-backend/storage/uploads/locations/stargazing_spots/ritigala_reserve1.jpg', '2025-09-12 17:46:39'),
(98, 48, '/skycamp-backend/storage/uploads/locations/stargazing_spots/ritigala_reserve2.jpg', '2025-09-12 17:46:39'),
(99, 48, '/skycamp-backend/storage/uploads/locations/stargazing_spots/ritigala_reserve3.jpg', '2025-09-12 17:46:39'),
(100, 49, '/skycamp-backend/storage/uploads/locations/stargazing_spots/yala_buffer_zone1.jpg', '2025-09-12 17:46:39'),
(101, 49, '/skycamp-backend/storage/uploads/locations/stargazing_spots/yala_buffer_zone2.jpg', '2025-09-12 17:46:39'),
(102, 49, '/skycamp-backend/storage/uploads/locations/stargazing_spots/yala_buffer_zone3.jpg', '2025-09-12 17:46:39'),
(103, 50, '/skycamp-backend/storage/uploads/locations/stargazing_spots/knuckles_mountains1.jpg', '2025-09-12 17:46:39'),
(104, 50, '/skycamp-backend/storage/uploads/locations/stargazing_spots/knuckles_mountains2.jpg', '2025-09-12 17:46:39'),
(105, 50, '/skycamp-backend/storage/uploads/locations/stargazing_spots/knuckles_mountains3.jpg', '2025-09-12 17:46:39'),
(106, 51, '/skycamp-backend/storage/uploads/locations/stargazing_spots/minneriya_area1.jpg', '2025-09-12 17:46:39'),
(107, 51, '/skycamp-backend/storage/uploads/locations/stargazing_spots/minneriya_area2.jpg', '2025-09-12 17:46:39'),
(108, 51, '/skycamp-backend/storage/uploads/locations/stargazing_spots/minneriya_area3.jpg', '2025-09-12 17:46:39'),
(109, 52, '/skycamp-backend/storage/uploads/locations/stargazing_spots/koggala_lake1.jpg', '2025-09-12 17:46:39'),
(110, 52, '/skycamp-backend/storage/uploads/locations/stargazing_spots/koggala_lake2.jpg', '2025-09-12 17:46:39'),
(111, 52, '/skycamp-backend/storage/uploads/locations/stargazing_spots/koggala_lake3.jpg', '2025-09-12 17:46:39'),
(112, 53, '/skycamp-backend/storage/uploads/locations/stargazing_spots/riverston1.jpg', '2025-09-12 17:46:39'),
(113, 53, '/skycamp-backend/storage/uploads/locations/stargazing_spots/riverston2.jpg', '2025-09-12 17:46:39'),
(114, 53, '/skycamp-backend/storage/uploads/locations/stargazing_spots/riverston3.jpg', '2025-09-12 17:46:39'),
(115, 54, '/skycamp-backend/storage/uploads/locations/stargazing_spots/casuarina_beach1.jpg', '2025-09-12 17:46:39'),
(116, 54, '/skycamp-backend/storage/uploads/locations/stargazing_spots/casuarina_beach2.jpg', '2025-09-12 17:46:39'),
(117, 54, '/skycamp-backend/storage/uploads/locations/stargazing_spots/casuarina_beach3.jpg', '2025-09-12 17:46:39'),
(118, 55, '/skycamp-backend/storage/uploads/locations/stargazing_spots/nilaveli_beach1.jpg', '2025-09-12 17:46:39'),
(119, 55, '/skycamp-backend/storage/uploads/locations/stargazing_spots/nilaveli_beach2.jpg', '2025-09-12 17:46:39'),
(120, 55, '/skycamp-backend/storage/uploads/locations/stargazing_spots/nilaveli_beach3.jpg', '2025-09-12 17:46:39'),
(121, 56, '/skycamp-backend/storage/uploads/locations/stargazing_spots/ella_rock1.jpg', '2025-09-12 17:46:39'),
(122, 56, '/skycamp-backend/storage/uploads/locations/stargazing_spots/ella_rock2.jpg', '2025-09-12 17:46:39'),
(123, 56, '/skycamp-backend/storage/uploads/locations/stargazing_spots/ella_rock3.jpg', '2025-09-12 17:46:39'),
(124, 57, '/skycamp-backend/storage/uploads/locations/stargazing_spots/sinharaja_edge1.jpg', '2025-09-12 17:46:39'),
(125, 57, '/skycamp-backend/storage/uploads/locations/stargazing_spots/sinharaja_edge2.jpg', '2025-09-12 17:46:39'),
(126, 57, '/skycamp-backend/storage/uploads/locations/stargazing_spots/sinharaja_edge3.jpg', '2025-09-12 17:46:39'),
(127, 58, '/skycamp-backend/storage/uploads/locations/stargazing_spots/wilpattu_vicinity1.jpg', '2025-09-12 17:46:39'),
(128, 58, '/skycamp-backend/storage/uploads/locations/stargazing_spots/wilpattu_vicinity2.jpg', '2025-09-12 17:46:39'),
(129, 58, '/skycamp-backend/storage/uploads/locations/stargazing_spots/wilpattu_vicinity3.jpg', '2025-09-12 17:46:39'),
(130, 59, '/skycamp-backend/storage/uploads/locations/stargazing_spots/kalametiya_beach1.jpg', '2025-09-12 17:46:39'),
(131, 59, '/skycamp-backend/storage/uploads/locations/stargazing_spots/kalametiya_beach2.jpg', '2025-09-12 17:46:39'),
(132, 59, '/skycamp-backend/storage/uploads/locations/stargazing_spots/kalametiya_beach3.jpg', '2025-09-12 17:46:39'),
(133, 60, '/skycamp-backend/storage/uploads/locations/stargazing_spots/udawalawe_vicinity1.jpg', '2025-09-12 17:46:39'),
(134, 60, '/skycamp-backend/storage/uploads/locations/stargazing_spots/udawalawe_vicinity2.jpg', '2025-09-12 17:46:39'),
(135, 60, '/skycamp-backend/storage/uploads/locations/stargazing_spots/udawalawe_vicinity3.jpg', '2025-09-12 17:46:39'),
(136, 61, '/skycamp-backend/storage/uploads/locations/stargazing_spots/kalpitiya1.jpg', '2025-09-12 17:46:39'),
(137, 61, '/skycamp-backend/storage/uploads/locations/stargazing_spots/kalpitiya2.jpg', '2025-09-12 17:46:39'),
(138, 61, '/skycamp-backend/storage/uploads/locations/stargazing_spots/kalpitiya3.jpg', '2025-09-12 17:46:39'),
(139, 62, '/skycamp-backend/storage/uploads/locations/stargazing_spots/mahiyanganaya_fields1.jpg', '2025-09-12 17:46:39'),
(140, 62, '/skycamp-backend/storage/uploads/locations/stargazing_spots/mahiyanganaya_fields2.jpg', '2025-09-12 17:46:39'),
(141, 62, '/skycamp-backend/storage/uploads/locations/stargazing_spots/mahiyanganaya_fields3.jpg', '2025-09-12 17:46:39'),
(142, 63, '/skycamp-backend/storage/uploads/locations/stargazing_spots/pinnawala_foothills1.jpg', '2025-09-12 17:46:39'),
(143, 63, '/skycamp-backend/storage/uploads/locations/stargazing_spots/pinnawala_foothills2.jpg', '2025-09-12 17:46:39'),
(144, 63, '/skycamp-backend/storage/uploads/locations/stargazing_spots/pinnawala_foothills3.jpg', '2025-09-12 17:46:39'),
(145, 64, '/skycamp-backend/storage/uploads/locations/stargazing_spots/forest_reserve1.jpg', '2025-09-12 17:46:39'),
(146, 64, '/skycamp-backend/storage/uploads/locations/stargazing_spots/forest_reserve2.jpg', '2025-09-12 17:46:39'),
(147, 64, '/skycamp-backend/storage/uploads/locations/stargazing_spots/forest_reserve3.jpg', '2025-09-12 17:46:39'),
(148, 65, '/skycamp-backend/storage/uploads/locations/stargazing_spots/gal_oya_vicinity1.jpg', '2025-09-12 17:46:39'),
(149, 65, '/skycamp-backend/storage/uploads/locations/stargazing_spots/gal_oya_vicinity2.jpg', '2025-09-12 17:46:39'),
(150, 65, '/skycamp-backend/storage/uploads/locations/stargazing_spots/gal_oya_vicinity3.jpg', '2025-09-12 17:46:39'),
(151, 66, '/skycamp-backend/storage/uploads/locations/stargazing_spots/kalametiya_sanctuary1.jpg', '2025-09-12 17:46:39'),
(152, 66, '/skycamp-backend/storage/uploads/locations/stargazing_spots/kalametiya_sanctuary2.jpg', '2025-09-12 17:46:39'),
(153, 66, '/skycamp-backend/storage/uploads/locations/stargazing_spots/kalametiya_sanctuary3.jpg', '2025-09-12 17:46:39'),
(154, 67, '/skycamp-backend/storage/uploads/locations/stargazing_spots/diyatha_uyana1.jpg', '2025-09-12 17:46:39'),
(155, 67, '/skycamp-backend/storage/uploads/locations/stargazing_spots/diyatha_uyana2.jpg', '2025-09-12 17:46:39'),
(156, 67, '/skycamp-backend/storage/uploads/locations/stargazing_spots/diyatha_uyana3.jpg', '2025-09-12 17:46:39'),
(157, 68, '/skycamp-backend/storage/uploads/locations/stargazing_spots/udugampola_forest1.jpg', '2025-09-12 17:46:39'),
(158, 68, '/skycamp-backend/storage/uploads/locations/stargazing_spots/udugampola_forest2.jpg', '2025-09-12 17:46:39'),
(159, 68, '/skycamp-backend/storage/uploads/locations/stargazing_spots/udugampola_forest3.jpg', '2025-09-12 17:46:39'),
(160, 69, '/skycamp-backend/storage/uploads/locations/stargazing_spots/bentota_beach1.jpg', '2025-09-12 17:46:39'),
(161, 69, '/skycamp-backend/storage/uploads/locations/stargazing_spots/bentota_beach2.jpg', '2025-09-12 17:46:39'),
(162, 69, '/skycamp-backend/storage/uploads/locations/stargazing_spots/bentota_beach3.jpg', '2025-09-12 17:46:39'),
(163, 70, '/skycamp-backend/storage/uploads/locations/stargazing_spots/hakgala_edge1.jpg', '2025-09-12 17:46:39'),
(164, 70, '/skycamp-backend/storage/uploads/locations/stargazing_spots/hakgala_edge2.jpg', '2025-09-12 17:46:39'),
(165, 70, '/skycamp-backend/storage/uploads/locations/stargazing_spots/hakgala_edge3.jpg', '2025-09-12 17:46:39'),
(166, 71, '/skycamp-backend/storage/uploads/locations/stargazing_spots/belihuloya_valley1.jpg', '2025-09-12 17:46:39'),
(167, 71, '/skycamp-backend/storage/uploads/locations/stargazing_spots/belihuloya_valley2.jpg', '2025-09-12 17:46:39'),
(168, 71, '/skycamp-backend/storage/uploads/locations/stargazing_spots/belihuloya_valley3.jpg', '2025-09-12 17:46:39'),
(169, 72, '/skycamp-backend/storage/uploads/locations/stargazing_spots/dambulla_cave_area1.jpg', '2025-09-12 17:46:39'),
(170, 72, '/skycamp-backend/storage/uploads/locations/stargazing_spots/dambulla_cave_area2.jpg', '2025-09-12 17:46:39'),
(171, 72, '/skycamp-backend/storage/uploads/locations/stargazing_spots/dambulla_cave_area3.jpg', '2025-09-12 17:46:39'),
(172, 73, '/skycamp-backend/storage/uploads/locations/stargazing_spots/kaudulla_vicinity1.jpg', '2025-09-12 17:46:39'),
(173, 73, '/skycamp-backend/storage/uploads/locations/stargazing_spots/kaudulla_vicinity2.jpg', '2025-09-12 17:46:39'),
(174, 73, '/skycamp-backend/storage/uploads/locations/stargazing_spots/kaudulla_vicinity3.jpg', '2025-09-12 17:46:39'),
(175, 74, '/skycamp-backend/storage/uploads/locations/stargazing_spots/marble_beach1.jpg', '2025-09-12 17:46:39'),
(176, 74, '/skycamp-backend/storage/uploads/locations/stargazing_spots/marble_beach2.jpg', '2025-09-12 17:46:39'),
(177, 74, '/skycamp-backend/storage/uploads/locations/stargazing_spots/marble_beach3.jpg', '2025-09-12 17:46:39'),
(178, 75, '/skycamp-backend/storage/uploads/locations/stargazing_spots/kallady_beach1.jpg', '2025-09-12 17:46:39'),
(179, 75, '/skycamp-backend/storage/uploads/locations/stargazing_spots/kallady_beach2.jpg', '2025-09-12 17:46:39'),
(180, 75, '/skycamp-backend/storage/uploads/locations/stargazing_spots/kallady_beach3.jpg', '2025-09-12 17:46:39');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('LowRatingWarning','PolicyViolation','CartUpdate','PaymentSuccess','Verification','TravelBuddyRequest','TravelBuddyResponse','EquipmentAddedToCart','BookingCreated','BookingCompleted') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'Verification', 'Your identity verification request has been rejected. Reason: NIC images do not match the provided personal information. Please review your documents and resubmit for verification.', 1, '2025-09-19 13:53:43'),
(2, 1, 'Verification', '⏳ Your identity verification request has been submitted and is under review. This usually takes 24-48 hours.', 1, '2025-09-20 09:01:32'),
(3, 1, 'Verification', '🎉 Congratulations! Your identity verification has been approved. You now have access to all verified user features.', 1, '2025-09-20 09:02:08'),
(13, 235, 'Verification', '⏳ Your identity verification request has been submitted and is under review. This usually takes 24-48 hours.', 1, '2025-09-20 14:23:52'),
(14, 235, 'Verification', '⏳ Your identity verification request has been submitted and is under review. This usually takes 24-48 hours.', 1, '2025-09-20 14:43:32'),
(15, 235, 'Verification', '❌ Your identity verification request has been rejected. Reason: Incomplete document submission. Both front and back NIC images required. Please review your documents and resubmit for verification.', 1, '2025-09-20 14:45:10'),
(16, 235, 'Verification', '🎉 Congratulations! Your identity verification has been approved. You now have access to all verified user features.', 0, '2025-09-20 14:46:34'),
(17, 236, 'Verification', '❌ Your identity verification request has been rejected. Reason: NIC documents are unclear or illegible. Please resubmit with better quality images. Please review your documents and resubmit for verification.', 0, '2025-09-20 18:36:52'),
(18, 101, 'Verification', '❌ Your identity verification request has been rejected. Reason: Incomplete document submission. Both front and back NIC images required. Please review your documents and resubmit for verification.', 1, '2025-09-20 18:37:02'),
(19, 102, 'Verification', '❌ Your identity verification request has been rejected. Reason: NIC images do not match the provided personal information. Please review your documents and resubmit for verification.', 0, '2025-09-20 18:47:08'),
(20, 101, 'Verification', '🎉 Congratulations! Your identity verification has been approved. You now have access to all verified user features.', 1, '2025-09-20 19:11:02'),
(21, 101, 'EquipmentAddedToCart', '🛒 John Doe added 2x Camping Tent to their cart. Quantity reserved until checkout.', 1, '2025-09-20 23:33:33'),
(22, 101, 'BookingCreated', '📋 New booking #12345 from Jane Smith for Sleeping Bag. Total: Rs. 5000', 1, '2025-09-20 23:33:33'),
(23, 101, 'BookingCompleted', '✅ Booking #12344 completed! Mike Johnson has marked Camping Chair as received.', 1, '2025-09-20 23:33:33'),
(24, 101, 'Verification', '🎉 Congratulations! Your identity verification has been approved. You now have access to all verified user features.', 1, '2025-09-20 23:33:33'),
(25, 201, 'Verification', '🎉 Congratulations! Your identity verification has been approved. You now have access to all verified user features.', 1, '2025-09-23 13:23:02'),
(26, 117, 'Verification', '🎉 Congratulations! Your identity verification has been approved. You now have access to all verified user features.', 1, '2025-09-24 06:22:48'),
(27, 215, 'Verification', '🎉 Congratulations! Your identity verification has been approved. You now have access to all verified user features.', 1, '2025-09-24 06:54:11'),
(28, 215, 'Verification', 'Your NIC verification has been approved! You can now receive bookings.', 1, '2025-09-24 07:01:06'),
(29, 215, 'BookingCreated', 'New booking received! John Doe has booked your guide service for December 15, 2024.', 1, '2025-09-24 07:01:06'),
(30, 215, 'PaymentSuccess', 'Payment received! LKR 7,000 has been credited to your account for booking #123.', 1, '2025-09-24 07:01:06'),
(31, 215, 'BookingCompleted', 'Booking #123 has been completed. Thank you for providing excellent service!', 1, '2025-09-24 07:01:06'),
(32, 215, 'Verification', 'Your NIC verification has been approved! You can now receive bookings.', 1, '2025-09-24 07:01:09'),
(33, 215, 'BookingCreated', 'New booking received! John Doe has booked your guide service for December 15, 2024.', 1, '2025-09-24 07:01:09'),
(34, 215, 'PaymentSuccess', 'Payment received! LKR 7,000 has been credited to your account for booking #123.', 1, '2025-09-24 07:01:09'),
(35, 215, 'BookingCompleted', 'Booking #123 has been completed. Thank you for providing excellent service!', 1, '2025-09-24 07:01:09'),
(36, 215, 'Verification', 'Your NIC verification has been approved! You can now receive bookings.', 0, '2025-09-24 07:06:25'),
(37, 215, 'BookingCreated', 'New booking received! John Doe has booked your guide service for December 15, 2024.', 0, '2025-09-24 07:06:25'),
(38, 215, 'PaymentSuccess', 'Payment received! LKR 7,000 has been credited to your account for booking #123.', 0, '2025-09-24 07:06:25'),
(39, 215, 'BookingCompleted', 'Booking #123 has been completed. Thank you for providing excellent service!', 0, '2025-09-24 07:06:25'),
(40, 117, 'EquipmentAddedToCart', '🛒 John Doe added 2x Camping Tent to their cart. Quantity reserved until checkout.', 1, '2025-09-24 10:07:48'),
(41, 117, 'BookingCreated', '📋 New booking #12345 from Jane Smith for Sleeping Bag. Total: Rs. 5000', 1, '2025-09-24 10:07:48'),
(42, 117, 'BookingCompleted', '✅ Booking #12344 completed! Mike Johnson has marked Camping Chair as received.', 1, '2025-09-24 10:07:48'),
(43, 117, 'Verification', '🎉 Congratulations! Your identity verification has been approved. You now have access to all verified user features.', 1, '2025-09-24 10:07:48'),
(44, 117, 'EquipmentAddedToCart', '🛒 John Doe added 2x Camping Tent to their cart. Quantity reserved until checkout.', 1, '2025-09-24 10:08:20'),
(45, 117, 'BookingCreated', '📋 New booking #12345 from Jane Smith for Sleeping Bag. Total: Rs. 5000', 1, '2025-09-24 10:08:20'),
(46, 117, 'BookingCompleted', '✅ Booking #12344 completed! Mike Johnson has marked Camping Chair as received.', 1, '2025-09-24 10:08:20'),
(47, 117, 'Verification', '🎉 Congratulations! Your identity verification has been approved. You now have access to all verified user features.', 1, '2025-09-24 10:08:20'),
(48, 117, 'EquipmentAddedToCart', '🛒 John Doe added 2x Camping Tent to their cart. Quantity reserved until checkout.', 1, '2025-09-24 10:20:26'),
(49, 117, 'BookingCreated', '📋 New booking #12345 from Jane Smith for Sleeping Bag. Total: Rs. 5000', 1, '2025-09-24 10:20:26'),
(50, 117, 'BookingCompleted', '✅ Booking #12344 completed! Mike Johnson has marked Camping Chair as received.', 1, '2025-09-24 10:20:26'),
(51, 117, 'Verification', '🎉 Congratulations! Your identity verification has been approved. You now have access to all verified user features.', 1, '2025-09-24 10:20:26'),
(52, 117, 'EquipmentAddedToCart', '🛒 John Doe added 2x Camping Tent to their cart. Quantity reserved until checkout.', 1, '2025-09-24 10:22:54'),
(53, 117, 'BookingCreated', '📋 New booking #12345 from Jane Smith for Sleeping Bag. Total: Rs. 5000', 1, '2025-09-24 10:22:54'),
(54, 117, 'BookingCompleted', '✅ Booking #12344 completed! Mike Johnson has marked Camping Chair as received.', 1, '2025-09-24 10:22:54'),
(55, 117, 'Verification', '🎉 Congratulations! Your identity verification has been approved. You now have access to all verified user features.', 1, '2025-09-24 10:22:54'),
(56, 117, 'EquipmentAddedToCart', '🛒 John Doe added 2x Camping Tent to their cart. Quantity reserved until checkout.', 1, '2025-09-24 10:26:51'),
(57, 117, 'BookingCreated', '📋 New booking #12345 from Jane Smith for Sleeping Bag. Total: Rs. 5000', 1, '2025-09-24 10:26:51'),
(58, 117, 'BookingCompleted', '✅ Booking #12344 completed! Mike Johnson has marked Camping Chair as received.', 1, '2025-09-24 10:26:51'),
(59, 117, 'Verification', '🎉 Congratulations! Your identity verification has been approved. You now have access to all verified user features.', 1, '2025-09-24 10:26:51'),
(60, 102, 'Verification', '🎉 Congratulations! Your identity verification has been approved. You now have access to all verified user features.', 0, '2025-09-24 10:48:03');

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
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
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
  `rating_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `entity_type` enum('Renter','Guide','Location','Customer') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `reminder_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `severity` enum('Info','Warning','Critical') DEFAULT 'Info',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `renterequipment`
--

CREATE TABLE `renterequipment` (
  `renter_equipment_id` int(11) NOT NULL,
  `renter_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `item_condition` varchar(100) DEFAULT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 1,
  `status` enum('Active','Archived') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `renterequipment`
--

INSERT INTO `renterequipment` (`renter_equipment_id`, `renter_id`, `equipment_id`, `item_condition`, `price_per_day`, `stock_quantity`, `status`) VALUES
(5, 3, 1, 'Good', 400.00, 2, 'Active'),
(6, 3, 20, 'fair', 550.00, 3, 'Active'),
(7, 3, 28, 'Good', 280.00, 5, 'Active'),
(8, 19, 1, 'Good', 500.00, 2, 'Active'),
(9, 19, 5, 'Excellent', 600.00, 2, 'Active'),
(10, 19, 8, 'Fair', 700.00, 2, 'Active'),
(11, 19, 40, 'Excellent', 1000.00, 1, 'Active'),
(12, 19, 30, 'Good', 500.00, 3, 'Active'),
(13, 3, 10, 'Good', 2000.00, 1, 'Active'),
(14, 3, 29, 'Excellent', 200.00, 9, 'Active'),
(15, 3, 45, 'Fair', 200.00, 20, 'Active'),
(16, 4, 3, 'Good', 1500.00, 2, 'Active'),
(17, 4, 11, 'Fair', 500.00, 2, 'Active'),
(18, 4, 21, 'Fair', 300.00, 5, 'Active'),
(19, 4, 6, 'Good', 300.00, 5, 'Active'),
(20, 5, 43, 'Good', 4999.98, 4, 'Active'),
(21, 5, 44, 'Fair', 600.00, 10, 'Active'),
(22, 5, 45, 'Good', 300.00, 9, 'Active'),
(23, 5, 9, 'Good', 1000.00, 6, 'Active'),
(24, 5, 26, 'Fair', 100.00, 3, 'Active'),
(25, 5, 14, 'Good', 100.00, 10, 'Active'),
(26, 5, 19, 'good', 200.00, 2, 'Active'),
(27, 6, 7, 'Good', 299.99, 9, 'Active'),
(28, 6, 6, 'Good', 300.00, 8, 'Active'),
(29, 6, 12, 'good', 200.00, 3, 'Active'),
(30, 6, 15, 'good', 200.00, 6, 'Active'),
(31, 6, 22, 'good', 300.00, 2, 'Active'),
(32, 6, 20, 'good', 300.00, 4, 'Active'),
(33, 7, 46, 'good', 1000.00, 1, 'Active'),
(34, 7, 16, 'good', 200.00, 69, 'Active'),
(35, 7, 13, 'good', 200.00, 10, 'Active'),
(36, 7, 17, 'good', 300.00, 4, 'Active'),
(37, 7, 23, 'good', 200.00, 2, 'Active'),
(38, 8, 1, 'Good', 300.00, 3, 'Active'),
(39, 8, 2, 'Good', 400.00, 3, 'Active'),
(40, 8, 3, 'Good', 600.00, 3, 'Active'),
(41, 8, 4, 'Good', 600.00, 2, 'Active'),
(42, 8, 6, 'Good', 500.00, 3, 'Active'),
(43, 9, 8, 'Good', 1000.00, 2, 'Active'),
(44, 9, 10, 'Good', 1200.00, 3, 'Active'),
(45, 9, 25, 'Good', 50.00, 9, 'Active'),
(46, 9, 12, 'Fair', 200.00, 2, 'Active'),
(47, 9, 27, 'Good', 500.00, 2, 'Active'),
(48, 10, 28, 'Good', 300.00, 2, 'Active'),
(49, 10, 9, 'Good', 2000.00, 2, 'Active'),
(50, 10, 18, 'Good', 200.00, 7, 'Active'),
(51, 10, 31, 'Good', 100.00, 1, 'Active'),
(52, 10, 34, 'Good', 300.00, 6, 'Active'),
(53, 11, 32, 'Good', 100.00, 3, 'Active'),
(54, 11, 36, 'Good', 600.00, 8, 'Active'),
(55, 11, 37, 'Good', 200.00, 7, 'Active'),
(56, 11, 46, 'Good', 600.00, 2, 'Active'),
(57, 11, 47, 'Good', 300.00, 4, 'Active'),
(58, 12, 13, 'Good', 200.00, 3, 'Active'),
(59, 12, 39, 'Good', 600.00, 4, 'Active'),
(60, 12, 35, 'Good', 400.00, 4, 'Active'),
(61, 12, 41, 'Good', 300.00, 3, 'Active'),
(62, 12, 28, 'Good', 500.00, 16, 'Active'),
(63, 13, 3, 'Good', 600.00, 5, 'Active'),
(64, 13, 34, 'Good', 300.00, 8, 'Active'),
(65, 13, 38, 'Good', 600.00, 7, 'Active'),
(66, 13, 42, 'Awesome', 560.00, 5, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `renterequipmentphotos`
--

CREATE TABLE `renterequipmentphotos` (
  `photo_id` int(11) NOT NULL,
  `renter_equipment_id` int(11) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `renterequipmentphotos`
--

INSERT INTO `renterequipmentphotos` (`photo_id`, `renter_equipment_id`, `photo_path`, `display_order`, `uploaded_at`) VALUES
(10, 5, 'users/101/equipment/5_2.jpg', 11, '2025-09-20 21:35:42'),
(11, 5, 'users/101/equipment/5_3.jpg', 1, '2025-09-20 21:35:53'),
(12, 6, 'users/101/equipment/6_1.jpg', 12, '2025-09-20 21:54:28'),
(13, 6, 'users/101/equipment/6_2.jpg', 13, '2025-09-20 21:54:28'),
(14, 7, 'users/101/equipment/7_1.jpg', 1, '2025-09-20 21:57:01'),
(15, 7, 'users/101/equipment/7_2.jpg', 2, '2025-09-20 21:57:01'),
(16, 8, 'users/117/equipment/8_1.jpg', NULL, '2025-09-24 06:15:28'),
(17, 9, 'users/117/equipment/9_1.jpg', NULL, '2025-09-24 06:16:36'),
(18, 10, 'users/117/equipment/10_1.jpg', NULL, '2025-09-24 06:17:46'),
(19, 11, 'users/117/equipment/11_1.jpg', NULL, '2025-09-24 06:20:03'),
(20, 12, 'users/117/equipment/12_1.jpg', NULL, '2025-09-24 06:20:43'),
(21, 13, 'users/101/equipment/13_1.jpg', NULL, '2025-09-24 10:31:41'),
(22, 14, 'users/101/equipment/14_1.jpg', NULL, '2025-09-24 10:32:16'),
(23, 15, 'users/101/equipment/15_1.jpg', NULL, '2025-09-24 10:32:50'),
(24, 16, 'users/102/equipment/16_1.jpg', NULL, '2025-09-24 10:49:35'),
(25, 17, 'users/102/equipment/17_1.jpg', NULL, '2025-09-24 10:50:02'),
(26, 18, 'users/102/equipment/18_1.jpg', NULL, '2025-09-24 10:50:30'),
(27, 19, 'users/102/equipment/19_1.jpg', NULL, '2025-09-24 10:51:57'),
(28, 20, 'users/103/equipment/20_1.jpg', NULL, '2025-09-24 10:53:39'),
(29, 21, 'users/103/equipment/21_1.jpg', NULL, '2025-09-24 10:54:16'),
(30, 22, 'users/103/equipment/22_1.jpg', NULL, '2025-09-24 10:56:06'),
(31, 23, 'users/103/equipment/23_1.jpg', NULL, '2025-09-24 10:57:18'),
(32, 24, 'users/103/equipment/24_1.jpg', NULL, '2025-09-24 10:57:44'),
(33, 25, 'users/103/equipment/25_1.jpg', NULL, '2025-09-24 10:58:12'),
(34, 26, 'users/103/equipment/26_1.jpg', NULL, '2025-09-24 10:59:10'),
(35, 27, 'users/104/equipment/27_1.jpg', NULL, '2025-09-24 11:00:14'),
(36, 28, 'users/104/equipment/28_1.jpg', NULL, '2025-09-24 11:00:40'),
(37, 29, 'users/104/equipment/29_1.jpg', NULL, '2025-09-24 11:01:14'),
(38, 30, 'users/104/equipment/30_1.jpg', NULL, '2025-09-24 11:01:41'),
(39, 31, 'users/104/equipment/31_1.jpg', NULL, '2025-09-24 11:02:12'),
(40, 32, 'users/104/equipment/32_1.jpg', NULL, '2025-09-24 11:02:41'),
(41, 33, 'users/105/equipment/33_1.jpg', NULL, '2025-09-24 11:03:30'),
(42, 34, 'users/105/equipment/34_1.jpg', NULL, '2025-09-24 11:03:58'),
(43, 35, 'users/105/equipment/35_1.jpg', NULL, '2025-09-24 11:04:23'),
(44, 36, 'users/105/equipment/36_1.jpg', NULL, '2025-09-24 11:04:57'),
(45, 37, 'users/105/equipment/37_1.jpg', NULL, '2025-09-24 11:05:29'),
(46, 38, 'users/106/equipment/38_1.jpg', NULL, '2025-09-24 11:06:18'),
(47, 39, 'users/106/equipment/39_1.jpg', NULL, '2025-09-24 11:06:44'),
(48, 40, 'users/106/equipment/40_1.jpg', NULL, '2025-09-24 11:07:08'),
(49, 41, 'users/106/equipment/41_1.jpg', NULL, '2025-09-24 11:07:44'),
(50, 42, 'users/106/equipment/42_1.jpg', NULL, '2025-09-24 11:08:18'),
(51, 43, 'users/107/equipment/43_1.jpg', NULL, '2025-09-24 11:09:16'),
(52, 44, 'users/107/equipment/44_1.jpg', NULL, '2025-09-24 11:09:47'),
(53, 45, 'users/107/equipment/45_1.jpg', NULL, '2025-09-24 11:10:33'),
(54, 46, 'users/107/equipment/46_1.jpg', NULL, '2025-09-24 11:11:03'),
(55, 47, 'users/107/equipment/47_1.jpg', NULL, '2025-09-24 11:11:47'),
(56, 48, 'users/108/equipment/48_1.jpg', NULL, '2025-09-24 11:12:44'),
(57, 49, 'users/108/equipment/49_1.jpg', NULL, '2025-09-24 11:13:36'),
(58, 50, 'users/108/equipment/50_1.jpg', NULL, '2025-09-24 11:14:02'),
(59, 51, 'users/108/equipment/51_1.jpg', NULL, '2025-09-24 11:14:35'),
(60, 52, 'users/108/equipment/52_1.jpg', NULL, '2025-09-24 11:15:01'),
(61, 53, 'users/109/equipment/53_1.jpg', NULL, '2025-09-24 11:16:05'),
(62, 54, 'users/109/equipment/54_1.jpg', NULL, '2025-09-24 11:16:38'),
(63, 55, 'users/109/equipment/55_1.jpg', NULL, '2025-09-24 11:17:06'),
(64, 56, 'users/109/equipment/56_1.jpg', NULL, '2025-09-24 11:17:38'),
(65, 57, 'users/109/equipment/57_1.jpg', NULL, '2025-09-24 11:18:14'),
(66, 58, 'users/110/equipment/58_1.jpg', NULL, '2025-09-24 11:27:00'),
(67, 59, 'users/110/equipment/59_1.jpg', NULL, '2025-09-24 11:27:37'),
(68, 60, 'users/110/equipment/60_1.jpg', NULL, '2025-09-24 11:28:13'),
(69, 61, 'users/110/equipment/61_1.jpg', NULL, '2025-09-24 11:29:01'),
(70, 62, 'users/110/equipment/62_1.jpg', NULL, '2025-09-24 11:29:39'),
(71, 63, 'users/111/equipment/63_1.jpg', NULL, '2025-09-24 11:30:45'),
(72, 64, 'users/111/equipment/64_1.jpg', NULL, '2025-09-24 11:31:10'),
(73, 65, 'users/111/equipment/65_1.jpg', NULL, '2025-09-24 11:32:25'),
(74, 66, 'users/111/equipment/66_1.jpg', NULL, '2025-09-24 11:34:49');

-- --------------------------------------------------------

--
-- Table structure for table `renters`
--

CREATE TABLE `renters` (
  `renter_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
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
  `verification_status` enum('Yes','No','Pending') DEFAULT 'No',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `renters`
--

INSERT INTO `renters` (`renter_id`, `user_id`, `first_name`, `last_name`, `dob`, `phone_number`, `home_address`, `gender`, `profile_picture`, `nic_number`, `nic_front_image`, `nic_back_image`, `camping_destinations`, `stargazing_spots`, `district`, `verification_status`, `latitude`, `longitude`, `created_at`) VALUES
(3, 101, 'Ruwan', 'Hettiarachchi', '1987-06-15', '0771234567', '15, Lake Road, Colombo', 'Male', 'users/101/profile.jpg', '873456789V', 'users/101/nic_front.jpg', 'users/101/nic_back.jpg', 'Muthurajawela Marsh,Adam’s Bridge (Rama’s Bridge)', 'Horton Plains', 'Colombo', 'Yes', 6.87956900, 79.92938000, '2025-09-13 10:45:50'),
(4, 102, 'Sanduni', 'Jayawardena', '1992-09-21', '0712345678', '42, Hill Side, Colombo', 'Female', 'users/102/profile.jpeg', '923456789V', 'users/102/nic_front.jpg', 'users/102/nic_back.jpg', 'Muthurajawela Marsh', 'Knuckles Mountains', 'Colombo', 'Yes', 6.95000000, 79.92000000, '2025-09-13 10:45:50'),
(5, 103, 'Mohamed', 'Fazil', '1985-12-05', '0753456789', '10, Mosque Lane, Colombo', 'Male', 'users/103/profile.jpeg', '853456789V', NULL, NULL, 'Diyasaru Park', 'Ambewela Hills', 'Colombo', 'Yes', 6.94000000, 79.91000000, '2025-09-13 10:45:50'),
(6, 104, 'Dilani', 'Perera', '1994-07-11', '0764567890', '88, Flower Road, Gampaha', 'Female', 'users/104/profile.jpeg', '943456789V', NULL, NULL, 'Muthurajawela Marsh', 'Diyasaru Park', 'Gampaha', 'Yes', 7.19758000, 79.83243000, '2025-09-13 10:45:50'),
(7, 105, 'Gayan', 'Wickramasinghe', '1989-03-28', '0785678901', '101, River Side, Gampaha', 'Male', 'users/105/profile.jpeg', '893456789V', NULL, NULL, 'Muthurajawela Marsh', 'Knuckles Mountains', 'Gampaha', 'Yes', 7.20000000, 79.85000000, '2025-09-13 10:45:50'),
(8, 106, 'Shalini', 'Peiris', '1995-01-09', '0726789012', '77, Mountain View, Gampaha', 'Female', 'users/106/profile.jpeg', '953456789V', NULL, NULL, 'Muthurajawela Marsh,Diyasaru Park', 'Horton Plains', 'Gampaha', 'Yes', 7.21000000, 79.84000000, '2025-09-13 10:45:50'),
(9, 107, 'Nimal', 'Perera', '1986-06-18', '0777890123', '5, Temple Road, Kandy', 'Male', 'users/107/profile.jpeg', '863456789V', NULL, NULL, 'Wewathenna Mountain', 'Knuckles Mountains', 'Kandy', 'Yes', 7.29000000, 80.63000000, '2025-09-13 10:45:50'),
(10, 108, 'Tharindu', 'Silva', '1990-08-27', '0718901234', '22, Main Street, Kandy', 'Male', 'users/108/profile.jpeg', '903456789V', NULL, NULL, 'Knuckles Mountains', 'Horton Plains', 'Kandy', 'Yes', 7.31000000, 80.70000000, '2025-09-13 10:45:50'),
(11, 109, 'Rizwan', 'Mohamed', '1988-02-14', '0769012345', '56, Sea Side, Kandy', 'Male', 'users/109/profile.jpeg', '883456789V', NULL, NULL, 'Knuckles Mountains', 'Ambewela Hills', 'Kandy', 'Yes', 7.30000000, 80.65000000, '2025-09-13 10:45:50'),
(12, 110, 'Harshini', 'Abeywardena', '1993-11-30', '0720123456', '34, Park Avenue, Galle', 'Female', 'users/110/profile.jpeg', '933456789V', NULL, NULL, 'Koggala Lake', 'Galle Fort Beach', 'Galle', 'Yes', 6.02810000, 80.21700000, '2025-09-13 10:45:50'),
(13, 111, 'Kasun', 'Fernando', '1987-08-05', '0772345678', '12, Lake View, Galle', 'Male', 'users/111/profile.jpeg', '873987654V', NULL, NULL, 'Koggala Lake', 'Kanneliya Rainforest', 'Galle', 'Yes', 6.05000000, 80.21000000, '2025-09-13 10:45:50'),
(14, 112, 'Madhavi', 'Kumari', '1991-06-20', '0713456789', '45, Riverside, Galle', 'Female', 'users/112/profile.jpeg', '913567890V', NULL, NULL, 'Koggala Lake', 'Sinharaja Buffer Zone', 'Galle', 'Yes', 6.06000000, 80.23000000, '2025-09-13 10:45:50'),
(15, 113, 'Suresh', 'Kumar', '1984-02-17', '0754561230', '102, Beach Road, Matara', 'Male', 'users/113/profile.jpeg', '842345123V', NULL, NULL, 'Madiha Beach', 'Kalametiya Beach', 'Matara', 'Yes', 5.94850000, 80.53500000, '2025-09-13 10:45:50'),
(16, 114, 'Ishara', 'Senanayake', '1996-05-12', '0786543210', '67, Garden Road, Matara', 'Female', 'users/114/profile.jpeg', '963456123V', NULL, NULL, 'Kalametiya Beach', 'Koggala Lake', 'Matara', 'Yes', 5.96000000, 80.52000000, '2025-09-13 10:45:50'),
(17, 115, 'Pradeep', 'Bandara', '1989-11-23', '0727890123', '21, Hill Road, Matara', 'Male', 'users/115/profile.jpeg', '893456321V', NULL, NULL, 'Madiha Beach', 'Sinharaja Buffer Zone', 'Matara', 'Yes', 5.97000000, 80.51000000, '2025-09-13 10:45:50'),
(18, 116, 'Lakmini', 'Perera', '1992-10-10', '0762345678', '44, Temple Lane, Badulla', 'Female', 'users/116/profile.jpeg', '923987654V', NULL, NULL, 'Madolsima', 'Narangala Peak', 'Badulla', 'Yes', 7.01390000, 81.21650000, '2025-09-13 10:45:50'),
(19, 117, 'Manjula', 'Karunaratne', '1985-08-25', '0715678901', '19, Forest Road, Badulla', 'Male', 'users/117/profile.jpeg', '853456987V', 'users/117/nic_front.jpg', 'users/117/nic_back.jpg', 'Narangala Peak', 'Namunukula Range', 'Badulla', 'Yes', 7.01400000, 81.22000000, '2025-09-13 10:45:50'),
(20, 118, 'Kamal', 'Gunasekara', '1990-01-15', '0778901234', '78, Valley Side, Badulla', 'Male', 'users/118/profile.jpeg', '903456123V', NULL, NULL, 'Namunukula Range', 'Ella Rock', 'Badulla', 'Yes', 7.01800000, 81.20000000, '2025-09-13 10:45:50'),
(21, 119, 'Anusha', 'Wijesinghe', '1993-07-07', '0723456789', '33, Lake Side, Anuradhapura', 'Female', 'users/119/profile.jpeg', '933456123V', NULL, NULL, 'Wilpattu Camping', 'Ritigala Reserve', 'Anuradhapura', 'Yes', 8.36360000, 80.36940000, '2025-09-13 10:45:50'),
(22, 120, 'Ranil', 'Jayasinghe', '1986-04-14', '0716789012', '55, Temple Road, Anuradhapura', 'Male', 'users/120/profile.jpeg', '863987654V', NULL, NULL, 'Wilpattu Camping', 'Mihintale Hills', 'Anuradhapura', 'Yes', 8.35000000, 80.40000000, '2025-09-13 10:45:50'),
(23, 121, 'Deepika', 'Fernando', '1991-09-03', '0757890123', '82, Riverside, Anuradhapura', 'Female', 'users/121/profile.jpeg', '913456789V', NULL, NULL, 'Wilpattu Camping', 'Kalawewa Tank', 'Anuradhapura', 'Yes', 8.37000000, 80.39000000, '2025-09-13 10:45:50'),
(24, 122, 'Mahesh', 'Rathnayake', '1988-11-22', '0775678901', '12, Hill Road, Trincomalee', 'Male', 'users/122/profile.jpeg', '883456987V', NULL, NULL, 'Nilaveli Beach', 'Marble Beach', 'Trincomalee', 'Yes', 8.68860000, 81.18250000, '2025-09-13 10:45:50'),
(25, 123, 'Chamari', 'Perera', '1994-02-16', '0719012345', '77, Garden View, Trincomalee', 'Female', 'users/123/profile.jpeg', '943456321V', NULL, NULL, 'Marble Beach', 'Nilaveli Beach', 'Trincomalee', 'Yes', 8.57000000, 81.22000000, '2025-09-13 10:45:50'),
(26, 124, 'Viraj', 'Hettiarachchi', '1987-12-09', '0760123456', '88, Sea Road, Trincomalee', 'Male', 'users/124/profile.jpeg', '873456321V', NULL, NULL, 'Nilaveli Beach,Marble Beach', 'Pigeon Island', 'Trincomalee', 'Yes', 8.68000000, 81.20000000, '2025-09-13 10:45:50'),
(27, 125, 'Pooja', 'Nirmala', '1995-03-29', '0721234567', '25, Temple Road, Kurunegala', 'Female', 'users/125/profile.jpeg', '953456321V', NULL, NULL, 'Dolukanda Rock', 'Wilpattu Vicinity', 'Kurunegala', 'Yes', 7.47230000, 80.36230000, '2025-09-13 10:45:50'),
(28, 126, 'Saman', 'Perera', '1989-05-18', '0712340987', '47, Riverside, Kurunegala', 'Male', 'users/126/profile.jpeg', '893456654V', NULL, NULL, 'Dolukanda Rock', 'Ritigala Reserve', 'Kurunegala', 'Yes', 7.46000000, 80.35000000, '2025-09-13 10:45:50'),
(29, 127, 'Thushari', 'Lakmali', '1993-01-25', '0758901234', '91, Hill Side, Kurunegala', 'Female', 'users/127/profile.jpeg', '933456654V', NULL, NULL, 'Dolukanda Rock', 'Minneriya Area', 'Kurunegala', 'Yes', 7.48000000, 80.37000000, '2025-09-13 10:45:50'),
(30, 128, 'Dilan', 'Jayakody', '1988-06-14', '0770987654', '67, Lake Road, Jaffna', 'Male', 'users/128/profile.jpeg', '883456654V', NULL, NULL, 'Casuarina Beach', 'Jaffna Fort View', 'Jaffna', 'Yes', 9.78700000, 80.16700000, '2025-09-13 10:45:50'),
(31, 129, 'Gayani', 'Silva', '1992-12-01', '0719876543', '12, Garden Lane, Jaffna', 'Female', 'users/129/profile.jpeg', '923456654V', NULL, NULL, 'Casuarina Beach', 'Point Pedro', 'Jaffna', 'Yes', 9.82000000, 80.23000000, '2025-09-13 10:45:50'),
(32, 130, 'Udaya', 'Abeywardena', '1986-09-30', '0767654321', '44, Park Avenue, Jaffna', 'Male', 'users/130/profile.jpeg', '863456654V', NULL, NULL, 'Casuarina Beach', 'Nainativu Island', 'Jaffna', 'Yes', 9.80000000, 80.20000000, '2025-09-13 10:45:50'),
(35, 236, 'Test', 'Renter', '1990-01-01', '0771234567', 'Test Address', 'Male', NULL, '901234567V', 'test_front.jpg', 'test_back.jpg', NULL, NULL, NULL, 'Yes', NULL, NULL, '2025-09-20 18:35:42');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `entity_type` enum('Renter','Guide','Location','Customer') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `review_text` text NOT NULL,
  `status` enum('Active','Flagged') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_flags`
--

CREATE TABLE `review_flags` (
  `flag_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `flagged_by` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `flagged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suspended_users`
--

CREATE TABLE `suspended_users` (
  `suspension_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('Customer','Renter','Guide') NOT NULL,
  `reason` text DEFAULT NULL,
  `suspended_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `suspended_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `travel_chats`
--

CREATE TABLE `travel_chats` (
  `chat_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `travel_chats`
--

INSERT INTO `travel_chats` (`chat_id`, `plan_id`, `created_at`) VALUES
(1, 1, '2025-09-23 17:01:57'),
(2, 2, '2025-09-24 19:54:45');

-- --------------------------------------------------------

--
-- Table structure for table `travel_chat_members`
--

CREATE TABLE `travel_chat_members` (
  `chat_member_id` int(11) NOT NULL,
  `chat_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `status` enum('Active','Left','Removed') DEFAULT 'Active',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `left_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `travel_chat_members`
--

INSERT INTO `travel_chat_members` (`chat_member_id`, `chat_id`, `customer_id`, `status`, `joined_at`, `left_at`) VALUES
(1, 1, 1, 'Active', '2025-09-23 17:01:57', NULL),
(2, 2, 1, 'Active', '2025-09-24 19:54:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `travel_messages`
--

CREATE TABLE `travel_messages` (
  `message_id` int(11) NOT NULL,
  `chat_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `travel_plans`
--

CREATE TABLE `travel_plans` (
  `plan_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `destination` varchar(255) NOT NULL,
  `adventure_type` enum('Hiking','Camping','Stargazing','Trekking','Safari','Other') NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `visibility` enum('Public','BuddiesOnly','Private') NOT NULL DEFAULT 'Public',
  `travel_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Upcoming','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Upcoming',
  `ended_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `companions_needed` int(11) NOT NULL,
  `companions_joined` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `travel_plans`
--

INSERT INTO `travel_plans` (`plan_id`, `customer_id`, `title`, `destination`, `adventure_type`, `location_id`, `visibility`, `travel_date`, `end_date`, `status`, `ended_at`, `notes`, `companions_needed`, `companions_joined`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Adam’s Bridge (Rama’s Bridge)', 'Hiking', NULL, 'Public', '2025-09-30', NULL, 'Upcoming', NULL, 'hi wanna go trip with me!', 2, 0, '2025-09-23 17:01:57', '2025-09-24 05:49:10'),
(2, 1, NULL, 'Adam’s Bridge (Rama’s Bridge)', 'Hiking', NULL, 'Public', '2025-11-11', NULL, 'Upcoming', NULL, 'hi', 2, 0, '2025-09-24 19:54:45', '2025-09-24 19:54:45');

-- --------------------------------------------------------

--
-- Table structure for table `travel_plan_feedback`
--

CREATE TABLE `travel_plan_feedback` (
  `feedback_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `travel_plan_media`
--

CREATE TABLE `travel_plan_media` (
  `media_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `kind` enum('PreTrip','PostTrip') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `travel_requests`
--

CREATE TABLE `travel_requests` (
  `request_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `responded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
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
(1, 'supungunathilaka123@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$Tk9kYU9Ec3VEVHNsUnZMMw$n+x54AyiddODG/RudXoCuaJeqlcZkdzarJrrLwIzSG8', 'Customer', 1, '2025-09-12 11:34:55'),
(51, 'amal@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(52, 'nadeesha@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(53, 'ruwan@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(54, 'shanika@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(55, 'chathura@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(56, 'thilini@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(57, 'sampath@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(58, 'harshani@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(59, 'pradeep@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(60, 'dulmini@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(61, 'nimal@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(62, 'sajini@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(63, 'chamika@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(64, 'gayani@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(65, 'ravindu@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(66, 'ishara@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(67, 'asela@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(68, 'pavithra@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(69, 'sunil@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(70, 'rashmi@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(71, 'chanuka@customer.sky.com', '$argon2id$v=19$m=65536,t=4,p=3$YWlVeEdtb3dZMkVWSGtOWA$WPrk7mkASDUntuIhgmAdp6QbonNJ0GZbZmFjjL6axkU', 'Customer', 1, '2025-09-20 12:18:39'),
(101, 'ruwantha.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(102, 'sanduni.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(103, 'mohamed.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(104, 'dilani.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(105, 'gayan.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(106, 'shalini.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(107, 'nimal.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(108, 'tharindu.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(109, 'rizwan.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(110, 'harshini.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(111, 'kasun.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(112, 'madhavi.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(113, 'suresh.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(114, 'ishara.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(115, 'pradeep.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(116, 'lakmini.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(117, 'manjula.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(118, 'kamal.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(119, 'anusha.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(120, 'ranil.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(121, 'deepika.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(122, 'mahesh.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(123, 'chamari.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(124, 'viraj.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(125, 'pooja.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(126, 'saman.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(127, 'thushari.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(128, 'dilan.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(129, 'gayani.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(130, 'udaya.r@example.com', '$argon2id$v=19$m=65536,t=4,p=3$akFLVE9Ic3l2S0VvQk5qVQ$qhJt7NqQVvBuzRUoOlBYvAS3q6q3Ov193yKf0skiYx4', 'Renter', 1, '2025-09-13 10:44:55'),
(131, 'isini2001@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$ZmRUYjA3bXI0V0FHLlA0TA$e82HGcBF3qThBAJXJ/9bIyrPnW6NJQTorI902BK8rQk', 'Renter', 1, '2025-09-13 05:22:00'),
(201, 'nadeesha.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(202, 'chamari.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(203, 'ishara.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(204, 'sanduni.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(205, 'udari.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(206, 'tharushi.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(207, 'dilhani.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(208, 'samadhi.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(209, 'shashini.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(210, 'bimashi.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(211, 'hansani.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(212, 'kavindya.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(213, 'sewwandi.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(214, 'nimesha.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(215, 'pabasara.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(216, 'pasindu.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(217, 'kavindu.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(218, 'sajith.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(219, 'nuwan.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(220, 'tharindu.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(221, 'lahiru.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(222, 'supun.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(223, 'sahan.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(224, 'sanjeewa.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(225, 'chathura.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(226, 'isuru.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(227, 'malith.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(228, 'chamika.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(229, 'madushan.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(230, 'pradeep.g@example.com', '$argon2id$v=19$m=65536,t=4,p=3$aGVZSER3R1RXVkh0cTdpVw$3Q0XjKH4aOa9F+BF5PxvHUzbd4gbayYv4zj2ZAdEiTI', 'Guide', 1, '2025-09-13 18:00:54'),
(235, 'chesan966@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$R2MvZTNwdTlEZEdrdThOdw$BgbiSV8RPCgPwbNB1pJYWJCwklxw7SeI5DiTMjnnl6Y', 'Customer', 1, '2025-09-20 08:53:31'),
(236, 'test.renter@example.com', 'password123', 'Renter', 1, '2025-09-20 18:35:41');

-- --------------------------------------------------------

--
-- Table structure for table `user_management_log`
--

CREATE TABLE `user_management_log` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `target_user_id` int(11) NOT NULL,
  `action` enum('Created','Updated','Deleted') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_verifications`
--

CREATE TABLE `user_verifications` (
  `verification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_verifications`
--

INSERT INTO `user_verifications` (`verification_id`, `user_id`, `reviewed_by`, `status`, `note`, `created_at`) VALUES
(2, 1, 1, 'Approved', 'NIC images are legible and match the provided information.', '2025-09-19 13:05:41'),
(3, 1, 1, 'Approved', 'NIC images are legible and match the provided information.', '2025-09-19 13:19:25'),
(4, 1, 1, 'Approved', 'NIC images are legible and match the provided information.', '2025-09-19 13:21:08'),
(6, 1, 1, 'Approved', 'NIC images are legible and match the provided information.', '2025-09-19 13:29:50'),
(7, 1, 1, 'Approved', 'NIC images are legible and match the provided information.', '2025-09-19 13:52:54'),
(8, 1, 1, 'Approved', 'NIC images are legible and match the provided information.', '2025-09-20 09:01:32'),
(14, 235, 1, 'Approved', 'NIC documents are clear and valid. Identity verified successfully.', '2025-09-20 14:23:52'),
(15, 235, 1, 'Approved', 'NIC documents are clear and valid. Identity verified successfully.', '2025-09-20 14:43:32'),
(16, 235, 1, 'Approved', 'NIC documents are clear and valid. Identity verified successfully.', '2025-09-20 14:45:47'),
(17, 102, 1, 'Approved', 'NIC images are legible and match the provided information.', '2025-09-20 18:19:36'),
(18, 101, 1, 'Approved', 'NIC images are legible and match the provided information.', '2025-09-20 18:50:40'),
(19, 201, 1, 'Approved', 'NIC documents are clear and valid. Identity verified successfully.', '2025-09-23 13:22:04'),
(20, 117, 1, 'Approved', 'All required documents submitted correctly. User identity confirmed.', '2025-09-23 15:21:48'),
(21, 215, 1, 'Approved', 'NIC images are legible and match the provided information.', '2025-09-24 06:53:15'),
(22, 102, 1, 'Approved', 'NIC images are legible and match the provided information.', '2025-09-24 10:34:11');

-- --------------------------------------------------------

--
-- Table structure for table `verification_management_log`
--

CREATE TABLE `verification_management_log` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `target_user_id` int(11) NOT NULL,
  `action` enum('Created','Updated','Deleted') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verification_management_log`
--

INSERT INTO `verification_management_log` (`log_id`, `admin_id`, `target_user_id`, `action`, `timestamp`) VALUES
(2, 1, 1, '', '2025-09-19 13:07:18'),
(3, 1, 1, '', '2025-09-19 13:20:17'),
(4, 1, 1, '', '2025-09-19 13:24:11'),
(6, 1, 1, '', '2025-09-19 13:30:39'),
(7, 1, 1, '', '2025-09-19 13:53:43'),
(8, 1, 1, '', '2025-09-20 09:02:08'),
(13, 1, 235, '', '2025-09-20 14:45:10'),
(14, 1, 235, '', '2025-09-20 14:46:34'),
(15, 1, 236, '', '2025-09-20 18:36:52'),
(16, 1, 101, '', '2025-09-20 18:37:02'),
(17, 1, 102, '', '2025-09-20 18:47:08'),
(18, 1, 101, '', '2025-09-20 19:11:02'),
(19, 1, 201, '', '2025-09-23 13:23:02'),
(20, 1, 117, '', '2025-09-24 06:22:48'),
(21, 1, 215, '', '2025-09-24 06:54:11'),
(22, 1, 102, '', '2025-09-24 10:48:03');

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `wishlist_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlists`
--

INSERT INTO `wishlists` (`wishlist_id`, `customer_id`, `created_at`) VALUES
(1, 1, '2025-09-19 14:12:11'),
(5, 2, '2025-09-20 13:52:20'),
(6, 3, '2025-09-20 13:53:17'),
(7, 17, '2025-09-20 14:18:17'),
(8, 22, '2025-09-20 14:20:00'),
(9, 23, '2025-09-20 14:23:33');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist_items`
--

CREATE TABLE `wishlist_items` (
  `wishlist_item_id` int(11) NOT NULL,
  `wishlist_id` int(11) NOT NULL,
  `item_type` enum('equipment','location','guide') NOT NULL,
  `item_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist_items`
--

INSERT INTO `wishlist_items` (`wishlist_item_id`, `wishlist_id`, `item_type`, `item_id`, `name`, `description`, `image_url`, `price`, `created_at`) VALUES
(67, 1, 'location', 39, 'Belihuloya', 'A forested riverside destination famous for cool streams, natural pools, and biodiversity.', 'http://localhost/skycamp/skycamp-backend/storage/uploads/locations/camping_destinations/belihuloya1.jpg', NULL, '2025-09-20 07:49:42'),
(68, 1, 'location', 35, 'Bogahakumbura Forest', 'A hidden forest camping site popular with birdwatchers and eco-tourists.', 'http://localhost/skycamp/skycamp-backend/storage/uploads/locations/camping_destinations/bogahakumbura_forest1.jpg', NULL, '2025-09-20 07:49:44'),
(82, 9, 'equipment', 3, 'Ruwantha Hettiarachchi', 'Equipment rental in Colombo', 'http://localhost/skycamp/skycamp-backend/storage/uploads/users/101/profile.jpeg', NULL, '2025-09-20 14:48:06'),
(84, 1, 'equipment', 3, 'Ruwan Hettiarachchi', 'Equipment rental in Colombo', 'http://localhost/skycamp/skycamp-backend/storage/uploads/users/101/profile.jpg', NULL, '2025-09-24 16:59:04'),
(86, 1, 'guide', 2, 'Chamari Silva', 'Guide in Gampaha', 'http://localhost/skycamp/skycamp-backend/storage/uploads/users/202/profile.jpeg', 6.00, '2025-09-24 17:16:23'),
(87, 1, 'location', 69, 'Bentota Beach', 'Bentota Beach offers stargazers a mix of gentle ocean breezes and wide-open skies. After sunset, stars begin to sparkle above the western horizon, creating dreamy views with the waves below. Its accessibility makes it popular for casual observers as well as night photographers.', 'http://localhost/skycamp/skycamp-backend/storage/uploads/locations/stargazing_spots/bentota_beach1.jpg', NULL, '2025-09-24 17:23:17'),
(88, 5, 'guide', 1, 'Nadeesha Perera', 'Guide in Colombo', 'http://localhost/skycamp/skycamp-backend/storage/uploads/users/201/profile.jpg', 7.00, '2025-09-24 23:05:32');

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
  ADD KEY `admin_deletions_ibfk_1` (`admin_id`);

--
-- Indexes for table `admin_suspensions`
--
ALTER TABLE `admin_suspensions`
  ADD PRIMARY KEY (`suspension_id`),
  ADD KEY `admin_suspensions_ibfk_1` (`admin_id`);

--
-- Indexes for table `bookingitems`
--
ALTER TABLE `bookingitems`
  ADD PRIMARY KEY (`booking_item_id`),
  ADD KEY `bookingitems_ibfk_1` (`booking_id`),
  ADD KEY `bookingitems_ibfk_2` (`renter_equipment_id`);

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
  ADD KEY `cartitems_ibfk_1` (`cart_id`),
  ADD KEY `cartitems_ibfk_2` (`renter_equipment_id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `carts_ibfk_1` (`customer_id`),
  ADD KEY `idx_carts_order_id` (`order_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `contact_messages_ibfk_1` (`replied_by`);

--
-- Indexes for table `content_logs`
--
ALTER TABLE `content_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `content_logs_ibfk_1` (`admin_id`);

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
  ADD KEY `equipment_ibfk_1` (`category_id`);

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
  ADD KEY `equipment_log_ibfk_1` (`equipment_id`),
  ADD KEY `equipment_log_ibfk_2` (`admin_id`);

--
-- Indexes for table `equipment_reservations`
--
ALTER TABLE `equipment_reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `equipment_reservations_ibfk_1` (`renter_equipment_id`),
  ADD KEY `equipment_reservations_ibfk_2` (`customer_id`),
  ADD KEY `equipment_reservations_ibfk_3` (`cart_id`),
  ADD KEY `equipment_reservations_ibfk_4` (`booking_id`);

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
  ADD KEY `guideavailability_ibfk_1` (`guide_id`);

--
-- Indexes for table `guideimages`
--
ALTER TABLE `guideimages`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `guideimages_ibfk_1` (`guide_id`);

--
-- Indexes for table `guides`
--
ALTER TABLE `guides`
  ADD PRIMARY KEY (`guide_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `nic_number` (`nic_number`),
  ADD KEY `idx_guides_location` (`latitude`,`longitude`);

--
-- Indexes for table `inactive_users`
--
ALTER TABLE `inactive_users`
  ADD PRIMARY KEY (`inactive_id`),
  ADD KEY `inactive_users_ibfk_1` (`deleted_by`);

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
  ADD KEY `location_images_ibfk_1` (`location_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `notifications_ibfk_1` (`user_id`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `email` (`email`),
  ADD KEY `token` (`token`),
  ADD KEY `expires_at` (`expires_at`);

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
  ADD KEY `idx_ratings_entity` (`entity_type`,`entity_id`),
  ADD KEY `ratings_ibfk_1` (`user_id`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`reminder_id`),
  ADD KEY `reminders_ibfk_1` (`user_id`);

--
-- Indexes for table `renterequipment`
--
ALTER TABLE `renterequipment`
  ADD PRIMARY KEY (`renter_equipment_id`),
  ADD KEY `renterequipment_ibfk_1` (`renter_id`),
  ADD KEY `renterequipment_ibfk_2` (`equipment_id`);

--
-- Indexes for table `renterequipmentphotos`
--
ALTER TABLE `renterequipmentphotos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `renterequipmentphotos_ibfk_1` (`renter_equipment_id`);

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
  ADD KEY `reviews_ibfk_1` (`user_id`);

--
-- Indexes for table `review_flags`
--
ALTER TABLE `review_flags`
  ADD PRIMARY KEY (`flag_id`),
  ADD KEY `review_flags_ibfk_1` (`review_id`),
  ADD KEY `review_flags_ibfk_2` (`flagged_by`);

--
-- Indexes for table `suspended_users`
--
ALTER TABLE `suspended_users`
  ADD PRIMARY KEY (`suspension_id`),
  ADD KEY `suspended_users_ibfk_1` (`user_id`);

--
-- Indexes for table `travel_chats`
--
ALTER TABLE `travel_chats`
  ADD PRIMARY KEY (`chat_id`),
  ADD KEY `idx_plan_id` (`plan_id`);

--
-- Indexes for table `travel_chat_members`
--
ALTER TABLE `travel_chat_members`
  ADD PRIMARY KEY (`chat_member_id`),
  ADD UNIQUE KEY `uniq_chat_user` (`chat_id`,`customer_id`),
  ADD KEY `idx_member` (`customer_id`),
  ADD KEY `idx_chat_status` (`chat_id`,`status`);

--
-- Indexes for table `travel_messages`
--
ALTER TABLE `travel_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `travel_messages_ibfk_1` (`chat_id`),
  ADD KEY `travel_messages_ibfk_2` (`sender_id`);

--
-- Indexes for table `travel_plans`
--
ALTER TABLE `travel_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `idx_plan_owner` (`customer_id`),
  ADD KEY `idx_plan_dates` (`travel_date`,`end_date`),
  ADD KEY `idx_plan_status` (`status`);

--
-- Indexes for table `travel_plan_feedback`
--
ALTER TABLE `travel_plan_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `fk_feedback_plan` (`plan_id`),
  ADD KEY `fk_feedback_owner` (`author_id`);

--
-- Indexes for table `travel_plan_media`
--
ALTER TABLE `travel_plan_media`
  ADD PRIMARY KEY (`media_id`),
  ADD KEY `fk_media_plan` (`plan_id`),
  ADD KEY `fk_media_uploader` (`uploaded_by`);

--
-- Indexes for table `travel_requests`
--
ALTER TABLE `travel_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `uq_plan_requester` (`plan_id`,`requester_id`),
  ADD KEY `travel_requests_ibfk_2` (`requester_id`);

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
  ADD KEY `user_management_log_ibfk_1` (`admin_id`),
  ADD KEY `user_management_log_ibfk_2` (`target_user_id`);

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
  ADD KEY `verification_management_log_ibfk_1` (`admin_id`),
  ADD KEY `verification_management_log_ibfk_2` (`target_user_id`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD KEY `wishlists_ibfk_1` (`customer_id`);

--
-- Indexes for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  ADD PRIMARY KEY (`wishlist_item_id`),
  ADD KEY `idx_item_type_id` (`item_type`,`item_id`),
  ADD KEY `idx_wishlist_id` (`wishlist_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_deletions`
--
ALTER TABLE `admin_deletions`
  MODIFY `deletion_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_suspensions`
--
ALTER TABLE `admin_suspensions`
  MODIFY `suspension_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookingitems`
--
ALTER TABLE `bookingitems`
  MODIFY `booking_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cartitems`
--
ALTER TABLE `cartitems`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `equipment_categories`
--
ALTER TABLE `equipment_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `equipment_log`
--
ALTER TABLE `equipment_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipment_reservations`
--
ALTER TABLE `equipment_reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `faq_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guideavailability`
--
ALTER TABLE `guideavailability`
  MODIFY `availability_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT for table `guideimages`
--
ALTER TABLE `guideimages`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `guides`
--
ALTER TABLE `guides`
  MODIFY `guide_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `inactive_users`
--
ALTER TABLE `inactive_users`
  MODIFY `inactive_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `location_images`
--
ALTER TABLE `location_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `page_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `reminder_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `renterequipment`
--
ALTER TABLE `renterequipment`
  MODIFY `renter_equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `renterequipmentphotos`
--
ALTER TABLE `renterequipmentphotos`
  MODIFY `photo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `renters`
--
ALTER TABLE `renters`
  MODIFY `renter_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_flags`
--
ALTER TABLE `review_flags`
  MODIFY `flag_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suspended_users`
--
ALTER TABLE `suspended_users`
  MODIFY `suspension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `travel_chats`
--
ALTER TABLE `travel_chats`
  MODIFY `chat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `travel_chat_members`
--
ALTER TABLE `travel_chat_members`
  MODIFY `chat_member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `travel_messages`
--
ALTER TABLE `travel_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `travel_plans`
--
ALTER TABLE `travel_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `travel_plan_feedback`
--
ALTER TABLE `travel_plan_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `travel_plan_media`
--
ALTER TABLE `travel_plan_media`
  MODIFY `media_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `travel_requests`
--
ALTER TABLE `travel_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=237;

--
-- AUTO_INCREMENT for table `user_management_log`
--
ALTER TABLE `user_management_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_verifications`
--
ALTER TABLE `user_verifications`
  MODIFY `verification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `verification_management_log`
--
ALTER TABLE `verification_management_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  MODIFY `wishlist_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

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
-- Constraints for table `travel_plan_feedback`
--
ALTER TABLE `travel_plan_feedback`
  ADD CONSTRAINT `fk_feedback_owner` FOREIGN KEY (`author_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_feedback_plan` FOREIGN KEY (`plan_id`) REFERENCES `travel_plans` (`plan_id`) ON DELETE CASCADE;

--
-- Constraints for table `travel_plan_media`
--
ALTER TABLE `travel_plan_media`
  ADD CONSTRAINT `fk_media_plan` FOREIGN KEY (`plan_id`) REFERENCES `travel_plans` (`plan_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_media_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

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
