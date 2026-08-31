-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 24, 2026 at 10:22 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `poultry_market`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'login', 'User logged in from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-08-16 15:33:50'),
(2, NULL, 'login', 'User logged in from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-08-16 15:34:54'),
(3, 1, 'login', 'User logged in from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:37:32'),
(4, 1, 'payment_success', 'Order #15: Broiler x 3 paid via airtel', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:45:37'),
(5, 1, 'payment_success', 'Order #16: broiler x 1 paid via mpesa', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:46:10'),
(6, 1, 'payment_success', 'Order #17: Broiler x 1 paid via mpesa', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:46:50'),
(7, 1, 'logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:49:08'),
(8, 3, 'login', 'User logged in from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:49:17'),
(9, 3, 'logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:49:50'),
(10, 1, 'login', 'User logged in from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:49:56'),
(11, 1, 'logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:50:08'),
(12, 2, 'login', 'User logged in from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:50:14'),
(13, 2, 'order_status_update', 'Order #17: pending → confirmed', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:50:23'),
(14, 2, 'order_status_update', 'Order #17: confirmed → delivered', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:50:29'),
(15, 2, 'order_status_update', 'Order #15: pending → delivered', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:50:34'),
(16, 2, 'order_status_update', 'Order #14: pending → delivered', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:50:38'),
(17, 2, 'edit_batch', 'Edited batch ID: 13 - Broiler (Qty: 193)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:54:43'),
(18, 2, 'logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:54:48'),
(19, 3, 'login', 'User logged in from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 10:54:56'),
(20, 3, 'logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 11:00:31'),
(21, 3, 'login', 'User logged in from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 11:02:51'),
(22, 3, 'logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 11:09:07'),
(23, 2, 'login', 'User logged in from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 11:10:03'),
(24, 2, 'logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 11:11:00'),
(25, 2, 'login', 'User logged in from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 11:12:13'),
(26, 2, 'edit_batch', 'Edited batch ID: 13 - Broiler (Qty: 193)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 11:53:24'),
(27, 2, 'edit_batch', 'Edited batch ID: 13 - Broiler (Qty: 193)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 11:57:57'),
(28, 2, 'edit_batch', 'Edited batch ID: 13 - Broiler (Qty: 193)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 11:58:53'),
(29, 2, 'edit_batch', 'Edited batch ID: 13 - Broiler (Qty: 193)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 11:59:13'),
(30, 2, 'edit_batch', 'Edited batch ID: 13 - Broiler (Qty: 193)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 11:59:55'),
(31, 2, 'edit_batch', 'Edited batch ID: 13 - Broiler (Qty: 193)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 12:00:15'),
(32, 2, 'edit_batch', 'Edited batch ID: 13 - Broiler (Qty: 193)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 12:00:32'),
(33, 2, 'logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-22 12:01:34'),
(34, 7, 'login', 'User logged in from IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-22 18:16:09'),
(35, 7, 'order_status_update', 'Order #16: pending → confirmed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-22 18:16:19'),
(36, 2, 'login', 'User logged in from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-23 19:04:44'),
(37, 2, 'logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-23 19:05:33'),
(38, 1, 'login', 'User logged in from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-23 19:05:54'),
(39, 1, 'logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-23 19:07:45'),
(40, 3, 'login', 'User logged in from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-23 19:07:50'),
(41, 3, 'logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-24 07:30:56'),
(42, NULL, 'register', 'New user registered: Taison1 (taison1@mail.com) as buyer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-24 07:32:56'),
(43, 10, 'login', 'User logged in from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-24 07:33:11'),
(44, 10, 'payment_success', 'Order #18: Broiler x 1 paid via mpesa', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-24 07:34:23'),
(45, 10, 'logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-24 07:36:02'),
(46, 3, 'login', 'User logged in from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-24 07:36:13'),
(47, 3, 'logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-24 07:37:29');

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `breed` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` >= 0),
  `price_per_bird` decimal(10,2) NOT NULL CHECK (`price_per_bird` >= 0),
  `hatch_date` date NOT NULL,
  `location` varchar(255) NOT NULL,
  `status` enum('available','reserved','sold') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`id`, `farmer_id`, `breed`, `quantity`, `price_per_bird`, `hatch_date`, `location`, `status`, `created_at`, `updated_at`, `image`) VALUES
(3, 5, 'Broiler', 200, 5500.00, '2026-08-19', 'Morogoro', 'available', '2026-08-19 09:22:28', '2026-08-19 09:29:07', 'assets/uploads/batches/6a8577636520d.jpeg'),
(4, 5, 'Broiler', 250, 5500.00, '2026-08-10', 'Morogoro', 'available', '2026-08-19 09:23:09', '2026-08-20 09:32:29', 'assets/uploads/batches/6a85774ccde65.jpeg'),
(5, 5, 'Broiler', 300, 6000.00, '2026-08-13', 'Kilosa', 'available', '2026-08-19 09:30:10', '2026-08-19 09:30:10', 'assets/uploads/batches/6a8577a2719a9.jpeg'),
(6, 5, 'Broiler', 150, 6000.00, '2026-08-19', 'Kilombero', 'available', '2026-08-19 09:31:16', '2026-08-19 09:31:16', 'assets/uploads/batches/6a8577e4d4b5f.jpeg'),
(7, 5, 'Broiler', 600, 8000.00, '2026-08-19', 'Mikumi', 'available', '2026-08-19 09:32:20', '2026-08-19 09:32:20', 'assets/uploads/batches/6a85782496cea.jpg'),
(8, 6, 'Broiler', 300, 7500.00, '2026-08-19', 'Mbagala', 'available', '2026-08-19 09:35:08', '2026-08-19 09:35:08', 'assets/uploads/batches/6a8578cc77c52.jpg'),
(9, 6, 'Broiler', 200, 7500.00, '2026-08-19', 'Kigamboni', 'available', '2026-08-19 09:35:45', '2026-08-19 09:35:45', 'assets/uploads/batches/6a8578f1a1b02.jpg'),
(10, 6, 'Broiler', 250, 7500.00, '2026-08-19', 'Gongo La Mboto', 'available', '2026-08-19 09:36:31', '2026-08-19 15:55:52', 'assets/uploads/batches/6a85791f8cf1f.jpg'),
(11, 7, 'broiler', 100, 5000.00, '2026-08-19', 'Dar es salaam', 'available', '2026-08-19 11:48:40', '2026-08-19 11:48:40', 'assets/uploads/batches/6a85981809eeb.jpg'),
(12, 7, 'broiler', 217, 5000.00, '2026-08-19', 'Dar es salaam', 'available', '2026-08-19 11:49:21', '2026-08-22 10:46:08', 'assets/uploads/batches/6a85984114c8d.jpg'),
(13, 2, 'Broiler', 192, 6500.00, '2026-06-16', 'ilala', 'available', '2026-08-20 10:51:50', '2026-08-24 07:34:05', 'assets/uploads/batches/6a86dc468c4d6.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `farmer_profiles`
--

CREATE TABLE `farmer_profiles` (
  `user_id` int(11) NOT NULL,
  `farm_name` varchar(100) DEFAULT NULL,
  `farm_address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer_profiles`
--

INSERT INTO `farmer_profiles` (`user_id`, `farm_name`, `farm_address`) VALUES
(2, '', ''),
(5, '', ''),
(7, '', '');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `total_price` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `delivery_method` enum('seller_delivery','self_pickup') NOT NULL,
  `delivery_address` text DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','in_transit','delivered','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `buyer_id`, `batch_id`, `quantity`, `total_price`, `delivery_fee`, `delivery_method`, `delivery_address`, `order_date`, `status`, `payment_status`, `notes`) VALUES
(10, 8, 12, 76, 380000.00, 0.00, 'self_pickup', '', '2026-08-19 15:52:30', 'delivered', 'paid', 'Jumapili mchana'),
(11, 1, 10, 100, 760000.00, 10000.00, 'seller_delivery', 'Ilala', '2026-08-19 15:55:52', 'delivered', 'paid', 'Jumamosi mchana nahitaji'),
(12, 1, 4, 150, 825000.00, 0.00, 'self_pickup', '', '2026-08-20 09:32:29', 'delivered', 'paid', ''),
(13, 9, 12, 6, 40000.00, 10000.00, 'seller_delivery', 'goba', '2026-08-20 09:45:47', 'delivered', 'paid', ''),
(14, 1, 13, 3, 39000.00, 0.00, 'self_pickup', '', '2026-08-22 10:38:05', 'delivered', 'paid', 'Next Monday'),
(15, 1, 13, 3, 39000.00, 0.00, 'self_pickup', '', '2026-08-22 10:45:28', 'delivered', 'paid', ''),
(16, 1, 12, 1, 5000.00, 0.00, 'self_pickup', '', '2026-08-22 10:46:08', 'confirmed', 'paid', ''),
(17, 1, 13, 1, 13000.00, 0.00, 'self_pickup', '', '2026-08-22 10:46:47', 'delivered', 'paid', ''),
(18, 10, 13, 1, 13000.00, 0.00, 'self_pickup', '', '2026-08-24 07:34:05', 'pending', 'paid', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `role` enum('admin','farmer','buyer') NOT NULL DEFAULT 'buyer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `location`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Dismas Nkomalago', 'abc@gmail.com', '$2y$10$Yl8g1a0uX1waZc223ECnburo3KiHE1urmixb8qDTLaiXx7MfmaHs6', '0785029004', 'Ilala', 'buyer', '2026-07-15 08:58:05', '2026-07-15 08:58:05'),
(2, 'Juma Mahadhi', 'cbd@gmail.com', '$2y$10$2Gp/In14sgqnKhQFnB3boOQ3XF/lnajIJ2QtnUmacwVuHY0LersYy', '0712345678', 'Chanika', 'farmer', '2026-07-15 09:32:14', '2026-07-15 09:32:14'),
(3, 'Admin', 'admin@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'admin', '2026-07-15 15:29:11', '2026-07-15 15:29:11'),
(4, 'Taison', 'taison@gmail.com', '$2y$10$FV6Wz9a/CMtG3Pn9EZ1Cd.zoMDzHF/67cGz8TDIxKhu8cRGFzuLBy', '0617925930', 'KIGOMA', 'buyer', '2026-07-20 09:00:02', '2026-07-20 09:00:02'),
(5, 'John Maganga', 'johnmaganga@gmail.com', '$2y$10$8tmRdbYH8M9B6TcLkFsfB.Vz5F4egGv/HV2hPVls51dx3C0SIAMo6', '+255716056928', 'Morogoro', 'farmer', '2026-08-19 09:14:54', '2026-08-19 09:14:54'),
(6, 'Taison Nelson', 'taisonn@gmail.com', '$2y$10$uuRER9/hhDkvQZDhTGXpcOG3oLA5BGMj0YPvPHFFn6RecB0Ll7WVC', '+255678987987', 'Dar es salaam', 'farmer', '2026-08-19 09:34:17', '2026-08-19 09:34:17'),
(7, 'Darius', 'darius@gmail.com', '$2y$10$XlOtmZviny7/yHAfP0hi7Okqoke5e381IED4PTFRPgQEQZdf6oYL.', '+255 624225930', 'Dar es salaam', 'farmer', '2026-08-19 11:46:43', '2026-08-19 11:46:43'),
(8, 'Darius', 'dariusi1@gmail.com', '$2y$10$5TdhNUIjm2cNxrngB6k2NOeBYvKhIqe0HZweSVhDhlxPefB0vv6tG', '+255 624225930', 'Dar es salaam', 'buyer', '2026-08-19 12:08:43', '2026-08-19 12:08:43'),
(9, 'John Mathew', 'user@user.com', '$2y$10$3P5zuIjOiImT4BCNzgk70.30.ksWgfye0Dto/whn0h.DyNH4WDeY2', '0767337475', 'dodoma', 'buyer', '2026-08-20 09:36:56', '2026-08-20 09:36:56'),
(10, 'Taison1', 'taison1@mail.com', '$2y$10$x/VZIw9YC/zmdrSiCkVaL.106DaPaHumQj4iiBpRVH7HxV3POmpiO', '0624225930', 'Kigamboni', 'buyer', '2026-08-24 07:32:56', '2026-08-24 07:32:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `idx_batches_status` (`status`);

--
-- Indexes for table `farmer_profiles`
--
ALTER TABLE `farmer_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `idx_orders_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `batches`
--
ALTER TABLE `batches`
  ADD CONSTRAINT `batches_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `farmer_profiles`
--
ALTER TABLE `farmer_profiles`
  ADD CONSTRAINT `farmer_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
