-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 22, 2025 at 05:47 PM
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
-- Database: `coffee_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','completed','abandoned') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `created_at`, `status`) VALUES
(1, 2, '2025-05-22 04:02:17', 'completed'),
(2, 2, '2025-05-22 04:03:47', 'completed'),
(3, 2, '2025-05-22 04:08:56', 'completed'),
(4, 2, '2025-05-22 04:09:22', 'completed'),
(5, 2, '2025-05-22 04:14:45', 'completed'),
(6, 2, '2025-05-22 04:30:00', 'completed'),
(7, 2, '2025-05-22 04:30:12', 'completed'),
(8, 2, '2025-05-22 04:30:16', 'completed'),
(9, 2, '2025-05-22 04:30:57', 'completed'),
(10, 2, '2025-05-22 06:51:01', 'completed'),
(11, 2, '2025-05-22 06:55:24', 'completed'),
(12, 3, '2025-05-22 07:15:07', 'completed'),
(13, 3, '2025-05-22 07:15:25', 'completed'),
(14, 2, '2025-05-22 07:22:27', 'completed'),
(15, 2, '2025-05-22 07:24:31', 'completed'),
(16, 2, '2025-05-22 07:29:05', 'completed'),
(17, 2, '2025-05-22 07:38:01', 'completed'),
(18, 2, '2025-05-22 12:44:59', 'completed'),
(19, 4, '2025-05-22 15:19:13', 'completed'),
(20, 4, '2025-05-22 15:19:24', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `cup_size` varchar(20) DEFAULT NULL,
  `sugar_level` varchar(20) DEFAULT NULL,
  `special_request` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `menu_item_id`, `quantity`, `cup_size`, `sugar_level`, `special_request`, `created_at`) VALUES
(1, 1, 6, 1, NULL, NULL, NULL, '2025-05-22 04:03:47'),
(2, 1, 5, 2, NULL, NULL, NULL, '2025-05-22 04:08:56'),
(3, 1, 2, 2, 'Medium', 'Less sugar', 'less ice', '2025-05-22 04:09:22'),
(4, 1, 3, 2, 'Large', 'Regular', 'no ice', '2025-05-22 04:14:45'),
(5, 6, 5, 1, NULL, NULL, NULL, '2025-05-22 04:30:12'),
(6, 12, 5, 2, NULL, NULL, NULL, '2025-05-22 07:15:07'),
(7, 12, 3, 2, 'Small', 'No sugar', 'no ice', '2025-05-22 07:15:25'),
(11, 9, 2, 2, 'Medium', 'Less sugar', 'no ice', '2025-05-22 07:38:01'),
(12, 18, 5, 2, NULL, NULL, NULL, '2025-05-22 12:44:59'),
(13, 19, 2, 2, 'Medium', 'Regular', 'less ice', '2025-05-22 15:19:13'),
(14, 19, 5, 3, NULL, NULL, NULL, '2025-05-22 15:19:24');

-- --------------------------------------------------------

--
-- Table structure for table `cart_item_addons`
--

CREATE TABLE `cart_item_addons` (
  `id` int(11) NOT NULL,
  `cart_item_id` int(11) NOT NULL,
  `addon_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_item_addons`
--

INSERT INTO `cart_item_addons` (`id`, `cart_item_id`, `addon_name`) VALUES
(1, 3, 'Extra shot'),
(2, 4, 'Whipped cream'),
(3, 4, 'Syrup'),
(4, 7, 'Whipped cream'),
(9, 11, 'Whipped cream'),
(10, 13, 'Extra shot'),
(11, 13, 'Whipped cream');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `item_name`, `description`, `price`, `image_path`, `is_available`, `created_at`, `category`) VALUES
(2, 'Affogato', 'Affogato is a dessert made by pouring hot espresso over a scoop of vanilla ice cream or gelato.', 50.00, 'uploads/menu_items/682af546395a63.16513726.jpg', 1, '2025-05-19 09:09:26', 'Coffee'),
(3, 'Cafe Latte', 'Café latte is a coffee drink made with espresso and steamed milk, topped with a light layer of foam.', 65.00, 'uploads/menu_items/682afa86e133e6.42439620.jpg', 1, '2025-05-19 09:31:50', 'Coffee'),
(4, 'Danish', 'Danish bread is a flaky, buttery pastry with layered dough, often filled with fruit, custard, or cream cheese.', 50.00, 'uploads/menu_items/682afbe0d0bd82.33837040.jpg', 1, '2025-05-19 09:37:36', 'Pastry'),
(5, 'Muffin', 'A soft, moist, and sweet baked treat, often flavored with fruits, nuts, or chocolate chips—perfect for breakfast or a snack.', 35.00, 'uploads/menu_items/682df64473c7d3.93365912.jpg', 1, '2025-05-21 15:50:28', 'Pastry'),
(6, 'Croissant', 'Buttery, flaky, and golden-brown French pastry perfect for any time of the day.', 65.00, 'uploads/menu_items/682df808658a46.09338836.jpg', 1, '2025-05-21 15:58:00', 'Pastry');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `delivery_address` text NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `special_instructions` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_date`, `total_amount`, `payment_method`, `delivery_address`, `contact_number`, `special_instructions`, `status`) VALUES
(1, 2, '2025-05-22 12:45:28', 70.00, 'Cash on Delivery', '17th St, Bacolod City', '09533765949', 'call/text', 'Completed'),
(2, 4, '2025-05-22 15:20:18', 205.00, 'Cash on Delivery', 'Victorias City', '09989498498', 'halong', 'Completed');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `cup_size` varchar(20) DEFAULT NULL,
  `sugar_level` varchar(20) DEFAULT NULL,
  `addons` text DEFAULT NULL,
  `special_request` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `item_name`, `quantity`, `price`, `cup_size`, `sugar_level`, `addons`, `special_request`) VALUES
(1, 1, 6, 'Croissant', 1, 65.00, NULL, NULL, NULL, NULL),
(2, 1, 5, 'Muffin', 2, 35.00, NULL, NULL, NULL, NULL),
(3, 1, 2, 'Affogato', 2, 50.00, 'Medium', 'Less sugar', 'Extra shot', 'less ice'),
(4, 1, 3, 'Cafe Latte', 2, 65.00, 'Large', 'Regular', 'Whipped cream, Syrup', 'no ice'),
(5, 2, 5, 'Muffin', 1, 35.00, NULL, NULL, NULL, NULL),
(6, 3, 5, 'Muffin', 2, 35.00, NULL, NULL, NULL, NULL),
(7, 3, 3, 'Cafe Latte', 2, 65.00, 'Small', 'No sugar', 'Whipped cream', 'no ice'),
(8, 4, 2, 'Affogato', 2, 50.00, 'Medium', 'Less sugar', 'Whipped cream', 'no ice'),
(9, 1, 5, 'Muffin', 2, 35.00, NULL, NULL, NULL, NULL),
(10, 2, 2, 'Affogato', 2, 50.00, 'Medium', 'Regular', 'Extra shot, Whipped cream', 'less ice'),
(11, 2, 5, 'Muffin', 3, 35.00, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `password`, `reset_token`, `token_expiry`, `created_at`, `updated_at`) VALUES
(1, 'Kafea Kiosk', 'info@kafeakiosk.com', '09123456789', '$2y$10$AjGDMcseTIzH03FQCExQ3.eiKOQ0hAtdSLuE2hCXYzAd5E7bLNCMm', NULL, NULL, '2025-05-10 03:43:45', '2025-05-10 03:43:45'),
(2, 'Mae Pacquiao', 'maepacquiao0405@gmail.com', '09533765949', '$2y$10$Q.vA8bp8NFOzW21PaAIuael6K3q4tukucdfp.xBBim2WXtTNotF3u', NULL, NULL, '2025-05-10 03:57:50', '2025-05-10 03:57:50'),
(3, 'William Berbenzana', 'williamberbenzana@gmail.com', '09123456754', '$2y$10$Euefx41Q3H/dUMAReqvPnu8gQwX.hf3c95hYJnLWCAbuy6znINq.e', NULL, NULL, '2025-05-10 08:58:27', '2025-05-10 08:58:27'),
(4, 'Erich Bonghanoy', 'erichbonghanoy@gmail.com', '09885984978', '$2y$10$eJnx9j1OpZ7QiFbXMyU.YePMKc1K4FCExyjJ6ZBY.vusnIwYtOg56', NULL, NULL, '2025-05-22 15:18:44', '2025-05-22 15:18:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `menu_item_id` (`menu_item_id`);

--
-- Indexes for table `cart_item_addons`
--
ALTER TABLE `cart_item_addons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_item_id` (`cart_item_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `menu_item_id` (`menu_item_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`fullname`),
  ADD KEY `fullname` (`fullname`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `cart_item_addons`
--
ALTER TABLE `cart_item_addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_item_addons`
--
ALTER TABLE `cart_item_addons`
  ADD CONSTRAINT `cart_item_addons_ibfk_1` FOREIGN KEY (`cart_item_id`) REFERENCES `cart_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
