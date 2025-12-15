-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 15, 2025 at 10:03 AM
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
-- Database: `canteen`
--

-- --------------------------------------------------------

--
-- Table structure for table `cartitems`
--

CREATE TABLE `cartitems` (
  `CartItemId` int(11) NOT NULL,
  `CartId` int(11) NOT NULL,
  `ProductId` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Note` text DEFAULT NULL,
  `PickupTime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cartitems`
--

INSERT INTO `cartitems` (`CartItemId`, `CartId`, `ProductId`, `Quantity`, `Note`, `PickupTime`) VALUES
(1, 1, 1, 1, NULL, NULL),
(2, 1, 2, 2, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `CartId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`CartId`, `UserId`) VALUES
(1, 4),
(5, 6),
(6, 90807),
(4, 2407123),
(3, 2407502),
(2, 2410038);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `CategoryId` int(11) NOT NULL,
  `CategoryName` varchar(100) NOT NULL,
  `CategoryLogo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`CategoryId`, `CategoryName`, `CategoryLogo`) VALUES
(1, 'Rice', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBmaWxsPSJjdXJyZW50Q29sb3IiIGQ9Ik0yMiAxMWgtMi4zYy0uMy0xLjEtLjgtMi4yLTEuNS0zbDMuNC01LjRsLTEuNy0xLjFsLTMuMiA1LjFjLS40LS4zLS43LS41LTEuMi0uN2wuOS0zLjZsLTEuOS0uNWwtLjggMy40Yy0uNi0uMS0xLjEtLjItMS43LS4yYy0zLjcgMC02LjggMi42LTcuNyA2SDJjMCA0LjEgMi41IDcuNiA2IDkuMlYyMmg4di0xLjhjMy41LTEuNiA2LTUuMSA2LTkuMk0xMiA3YzIuNiAwIDQuOCAxLjcgNS42IDRINi40Yy44LTIuMyAzLTQgNS42LTQiLz48L3N2Zz4='),
(2, 'Drinks', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBmaWxsPSIjMDAwIiBkPSJNMjAgMWgtNS41MmwtMS4yMzUgNC4yMjJoLTIuOTA0Yy0uNDU3LTEuODI5LTIuMTM3LTMuMTY2LTQuMTEtMy4xNjZDMy45MTQgMi4wNTYgMiAzLjkwMiAyIDYuMjIyYzAgMi4wMTggMS40NzUgMy42NDIgMy4zNDkgNC4wNTZMNi4yMTcgMjNoMTIuNjRMMjAuMDcgNS4yMjJoLTQuNzRMMTUuOTc5IDNIMjB6TTcuMzggMTAuNzA0bC0uMjM2LTMuNDgyaDUuNTE1bC0xLjIxMyA0LjE0M3ptMTAuNTUtMy40ODJsLS4zNTIgNS4xNTdsLTQuMTQxLS42ODdsMS4zMDgtNC40N3oiLz48L3N2Zz4='),
(3, 'Dessert', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBmaWxsPSIjMDAwIiBkPSJNMyAxMHEwLTEuMjc1LjczOC0yLjNUNS42IDYuMjVxLjQ1LTIuMjc1IDIuMjM4LTMuNzYzVDEyIDF0NC4xNjMgMS40ODhUMTguNCA2LjI1cTEuMTI1LjQyNSAxLjg2MyAxLjQ1VDIxIDEwcTAgMS44NzUtMS4zMjUgMi45NzVUMTYuNyAxNGwtMy43NzUgNy4zcS0uMTI1LjI3NS0uMzYzLjR0LS41MTIuMTI1dC0uNTI1LS4xMjV0LS4zNzUtLjRMNy4zNSAxNHEtMS43NzUuMDc1LTMuMDYyLTEuMDI1VDMgMTBtOS4wNSA4LjY1bDIuNy01LjI1cS0uNi4zLTEuMy40NVQxMiAxNHEtLjY3NSAwLTEuMzYyLS4xNVQ5LjMgMTMuNHoiLz48L3N2Zz4='),
(4, 'Noodle', NULL),
(5, 'Printer', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orderitems`
--

CREATE TABLE `orderitems` (
  `OrderListId` int(11) NOT NULL,
  `OrderId` int(11) NOT NULL,
  `ProductId` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Subtotal` decimal(10,2) NOT NULL,
  `Note` text DEFAULT NULL,
  `PickupTime` datetime DEFAULT NULL,
  `Status` enum('pending','preparing','ready','complete','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orderitems`
--

INSERT INTO `orderitems` (`OrderListId`, `OrderId`, `ProductId`, `Quantity`, `Subtotal`, `Note`, `PickupTime`, `Status`) VALUES
(49, 49, 10, 1, 4.50, '', '2025-12-14 16:40:00', 'complete'),
(50, 50, 2, 1, 3.50, '', '2025-12-14 17:00:00', 'complete'),
(51, 50, 10, 1, 4.50, '', '2025-12-14 17:00:00', 'complete'),
(52, 51, 2, 1, 3.50, '', '2025-12-14 17:30:00', 'complete'),
(53, 52, 2, 1, 3.50, '', '2025-12-14 17:40:00', 'complete'),
(54, 53, 2, 1, 3.50, '', '2025-12-14 17:55:00', 'complete'),
(55, 54, 2, 1, 3.50, '', '2025-12-14 18:00:00', 'complete'),
(56, 55, 2, 1, 3.50, '', '2025-12-14 18:25:00', 'complete'),
(57, 56, 2, 1, 3.50, '', '2025-12-14 22:05:00', 'complete'),
(58, 57, 3, 1, 5.00, '', '2025-12-14 22:05:00', 'complete'),
(59, 58, 2, 1, 3.50, '', '2025-12-15 00:40:00', 'complete'),
(60, 59, 12, 1, 4.00, '', '2025-12-15 00:45:00', 'pending'),
(61, 60, 1, 1, 6.00, '', '2025-12-15 01:15:00', 'pending'),
(62, 61, 3, 1, 5.00, '', '2025-12-15 01:15:00', 'pending'),
(63, 62, 3, 1, 5.00, '', '2025-12-15 01:20:00', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `OrderId` int(11) NOT NULL,
  `PaymentId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `StallId` int(11) NOT NULL,
  `Status` enum('pending','preparing','ready','complete') DEFAULT 'pending',
  `Notes` text DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`OrderId`, `PaymentId`, `UserId`, `StallId`, `Status`, `Notes`, `CreatedAt`) VALUES
(1, 1, 4, 1, 'pending', 'Chicken Rice x2', '2025-11-17 12:48:15'),
(2, 1, 4, 2, 'ready', 'Waffle x1', '2025-11-17 12:48:15'),
(8, 7, 2410038, 1, 'pending', 'Pickup: ASAP | Method: Ewallet', '2025-11-26 09:19:50'),
(9, 8, 2410038, 2, 'ready', 'Pickup: ASAP | Method: Stripe', '2025-11-26 09:29:28'),
(10, 9, 2410038, 1, 'pending', 'Pickup: ASAP | Method: Ewallet', '2025-11-26 09:44:56'),
(11, 10, 2410038, 1, 'pending', 'Pickup: ASAP | Method: Ewallet', '2025-11-27 17:01:39'),
(12, 10, 2410038, 2, 'ready', 'Pickup: ASAP | Method: Ewallet', '2025-11-27 17:01:39'),
(13, 11, 2407502, 2, 'ready', '', '2025-12-05 13:23:42'),
(14, 11, 2407502, 1, 'pending', '', '2025-12-05 13:23:42'),
(15, 12, 2407502, 2, 'ready', '', '2025-12-05 13:26:52'),
(16, 12, 2407502, 1, 'pending', '', '2025-12-05 13:26:53'),
(17, 13, 2407502, 1, 'pending', '', '2025-12-05 13:27:26'),
(18, 14, 2407502, 2, 'ready', '', '2025-12-05 13:27:56'),
(19, 15, 2407502, 2, 'ready', '', '2025-12-05 13:31:33'),
(20, 15, 2407502, 1, 'pending', '', '2025-12-05 13:31:33'),
(21, 16, 2407502, 2, '', '', '2025-12-05 13:37:18'),
(22, 17, 2407502, 2, '', '', '2025-12-05 13:47:31'),
(23, 18, 2407502, 2, 'ready', '', '2025-12-05 21:23:24'),
(24, 19, 2407502, 1, 'pending', '', '2025-12-05 21:31:59'),
(25, 20, 2407502, 1, 'pending', '', '2025-12-05 21:37:17'),
(26, 20, 2407502, 2, 'ready', '', '2025-12-05 21:37:17'),
(27, 21, 2407502, 1, 'pending', '', '2025-12-05 21:40:01'),
(28, 22, 2407502, 1, 'pending', '', '2025-12-05 22:11:41'),
(29, 22, 2407502, 2, 'ready', '', '2025-12-05 22:11:41'),
(30, 23, 2407502, 2, 'ready', '', '2025-12-05 22:12:56'),
(31, 24, 2407502, 2, 'complete', '', '2025-12-08 13:55:09'),
(32, 25, 2407123, 1, 'pending', '', '2025-12-08 13:58:28'),
(33, 25, 2407123, 2, 'ready', '', '2025-12-08 13:58:28'),
(34, 26, 2407502, 3, 'pending', '', '2025-12-08 14:21:31'),
(35, 27, 2407502, 2, '', '', '2025-12-08 18:39:02'),
(36, 27, 2407502, 3, 'pending', '', '2025-12-08 18:39:02'),
(37, 28, 2407502, 1, 'pending', '', '2025-12-08 18:45:15'),
(38, 28, 2407502, 1, 'pending', '', '2025-12-08 18:45:15'),
(39, 29, 2407502, 2, 'ready', '', '2025-12-08 18:46:57'),
(40, 30, 2407502, 2, 'ready', '', '2025-12-08 18:51:30'),
(41, 30, 2407502, 2, 'complete', '', '2025-12-08 18:51:30'),
(42, 31, 2407502, 1, 'pending', '', '2025-12-10 09:41:34'),
(43, 32, 2407502, 2, 'complete', '', '2025-12-10 09:44:31'),
(44, 33, 2407502, 2, '', '', '2025-12-10 09:46:20'),
(45, 34, 2407502, 1, 'pending', '', '2025-12-11 16:17:56'),
(46, 34, 2407502, 2, '', '', '2025-12-11 16:17:56'),
(47, 35, 2407502, 2, '', '', '2025-12-11 16:22:24'),
(48, 36, 2407502, 2, '', '', '2025-12-13 23:17:54'),
(49, 37, 90807, 2, 'complete', '', '2025-12-14 16:25:00'),
(50, 38, 90807, 2, 'complete', '', '2025-12-14 16:44:53'),
(51, 39, 90807, 2, 'complete', '', '2025-12-14 17:13:55'),
(52, 40, 90807, 2, 'complete', '', '2025-12-14 17:21:33'),
(53, 41, 90807, 2, 'complete', '', '2025-12-14 17:38:29'),
(54, 42, 90807, 2, 'complete', '', '2025-12-14 17:40:37'),
(55, 43, 90807, 2, 'complete', '', '2025-12-14 18:06:31'),
(56, 44, 90807, 2, 'complete', '', '2025-12-14 21:47:19'),
(57, 45, 90807, 2, 'complete', '', '2025-12-14 21:49:16'),
(58, 46, 90807, 2, 'complete', '', '2025-12-15 00:22:02'),
(59, 47, 90807, 2, 'pending', '', '2025-12-15 00:52:29'),
(60, 48, 90807, 1, 'pending', '', '2025-12-15 00:57:39'),
(61, 49, 90807, 2, 'pending', '', '2025-12-15 00:59:52'),
(62, 50, 6, 2, 'pending', '', '2025-12-15 01:03:33');

-- --------------------------------------------------------

--
-- Table structure for table `passwordresets`
--

CREATE TABLE `passwordresets` (
  `ResetId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `Token` varchar(64) NOT NULL,
  `ExpiresAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `passwordresets`
--

INSERT INTO `passwordresets` (`ResetId`, `UserId`, `Token`, `ExpiresAt`) VALUES
(15, 2410038, '34ea7eb2f08d06743b866e4ead5bc981989dedc833db9f990121c2e5a022a133', '2025-11-24 22:36:31');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `PaymentId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `TotalAmount` decimal(10,2) NOT NULL,
  `Status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `PaymentMethod` enum('card','cash','e-wallet','') NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`PaymentId`, `UserId`, `TotalAmount`, `Status`, `PaymentMethod`, `CreatedAt`) VALUES
(1, 4, 17.00, 'paid', 'card', '2025-11-17 12:48:15'),
(7, 2410038, 6.00, 'paid', 'card', '2025-11-26 09:19:50'),
(8, 2410038, 9.00, 'paid', 'card', '2025-11-26 09:29:28'),
(9, 2410038, 6.00, 'paid', 'card', '2025-11-26 09:44:56'),
(10, 2410038, 17.00, 'paid', 'card', '2025-11-27 17:01:39'),
(11, 2407502, 9.50, 'paid', 'card', '2025-12-05 13:23:42'),
(12, 2407502, 9.50, 'paid', 'e-wallet', '2025-12-05 13:26:52'),
(13, 2407502, 6.00, 'pending', 'cash', '2025-12-05 13:27:26'),
(14, 2407502, 3.50, 'paid', 'card', '2025-12-05 13:27:56'),
(15, 2407502, 9.50, 'paid', 'card', '2025-12-05 13:31:33'),
(16, 2407502, 3.50, 'paid', 'cash', '2025-12-05 13:37:18'),
(17, 2407502, 3.50, 'paid', 'card', '2025-12-05 13:47:31'),
(18, 2407502, 3.50, 'paid', 'card', '2025-12-05 21:23:24'),
(19, 2407502, 6.00, 'paid', 'e-wallet', '2025-12-05 21:31:59'),
(20, 2407502, 9.50, 'paid', 'e-wallet', '2025-12-05 21:37:17'),
(21, 2407502, 6.00, 'pending', 'cash', '2025-12-05 21:40:01'),
(22, 2407502, 14.50, 'paid', 'card', '2025-12-05 22:11:41'),
(23, 2407502, 10.00, 'paid', 'e-wallet', '2025-12-05 22:12:56'),
(24, 2407502, 8.50, 'paid', 'e-wallet', '2025-12-08 13:55:09'),
(25, 2407123, 14.50, 'paid', 'card', '2025-12-08 13:58:28'),
(26, 2407502, 8.00, 'paid', 'e-wallet', '2025-12-08 14:21:31'),
(27, 2407502, 11.50, 'paid', 'e-wallet', '2025-12-08 18:39:02'),
(28, 2407502, 12.00, 'paid', 'e-wallet', '2025-12-08 18:45:15'),
(29, 2407502, 3.50, 'paid', 'card', '2025-12-08 18:46:57'),
(30, 2407502, 7.00, 'paid', 'card', '2025-12-08 18:51:30'),
(31, 2407502, 6.00, 'paid', 'e-wallet', '2025-12-10 09:41:34'),
(32, 2407502, 5.00, 'paid', 'card', '2025-12-10 09:44:31'),
(33, 2407502, 3.50, 'paid', 'cash', '2025-12-10 09:46:20'),
(34, 2407502, 9.50, 'paid', 'cash', '2025-12-11 16:17:56'),
(35, 2407502, 3.50, 'paid', 'cash', '2025-12-11 16:22:24'),
(36, 2407502, 3.50, 'paid', 'cash', '2025-12-13 23:17:54'),
(37, 90807, 8.00, 'paid', 'card', '2025-12-14 16:25:00'),
(38, 90807, 8.00, 'paid', 'cash', '2025-12-14 16:44:53'),
(39, 90807, 3.50, 'paid', 'cash', '2025-12-14 17:13:55'),
(40, 90807, 3.50, 'paid', 'cash', '2025-12-14 17:21:33'),
(41, 90807, 3.50, 'paid', 'cash', '2025-12-14 17:38:29'),
(42, 90807, 3.50, 'paid', 'cash', '2025-12-14 17:40:37'),
(43, 90807, 3.50, 'paid', 'cash', '2025-12-14 18:06:31'),
(44, 90807, 3.50, 'paid', 'cash', '2025-12-14 21:47:19'),
(45, 90807, 5.00, 'paid', 'cash', '2025-12-14 21:49:16'),
(46, 90807, 3.50, 'paid', 'cash', '2025-12-15 00:22:02'),
(47, 90807, 4.00, 'pending', 'cash', '2025-12-15 00:52:29'),
(48, 90807, 6.00, 'pending', 'cash', '2025-12-15 00:57:39'),
(49, 90807, 5.00, 'pending', 'cash', '2025-12-15 00:59:52'),
(50, 6, 5.00, 'pending', 'cash', '2025-12-15 01:03:33');

-- --------------------------------------------------------

--
-- Table structure for table `productimages`
--

CREATE TABLE `productimages` (
  `ImageId` int(11) NOT NULL,
  `ProductId` int(11) NOT NULL,
  `ImageURL` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productimages`
--

INSERT INTO `productimages` (`ImageId`, `ProductId`, `ImageURL`) VALUES
(5, 3, 'https://farm2.staticflickr.com/1586/24226428489_bb311bd8e2_o.jpg'),
(6, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSLO0wnKIXD_1tYTp-l7Dh__6efVAB6vSFdAQ&s'),
(7, 4, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSqR5605lTaluEqXRNxjFsbQS-JCpu9hSrjgnPoBRmIIDrNBs-myJozA3Sx3HSx27eGDJVITgg4AuzPrBqURiX_QgkTzLYL3HOsHddm6g&s=10'),
(8, 5, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQLrd2QnolVXwGKwAaqWE81v3K5PBhyOFhRTQ&s'),
(9, 6, 'https://www.cuisinart.com/dw/image/v2/ABAF_PRD/on/demandware.static/-/Sites-us-cuisinart-sfra-Library/default/dw30047d66/images/recipe-Images/cappuccino1-recipe.jpg?sw=1200&sh=630'),
(11, 8, 'https://cdn.shopify.com/s/files/1/0173/8181/8422/files/20240523183203-screenshot-202024-05-10-20at-204.png?v=1716489126&width=1000&height=1000'),
(12, 8, 'https://curlygirlkitchen.com/wp-content/uploads/2024/01/Crispy-Chocolate-Waffles-High-Altitude-007.jpg'),
(13, 9, 'https://www.allthingsmamma.com/wp-content/uploads/2023/02/Strawberry-Waffles-Hero-12-scaled.jpg'),
(14, 9, 'https://littlesunnykitchen.com/wp-content/uploads/2021/04/Strawberry-Waffles-1.jpg'),
(15, 10, 'https://lmld.org/wp-content/uploads/2015/03/Golden-Butter-Waffles-8.jpg'),
(16, 11, 'https://images.getrecipekit.com/20231116072724-c2-a9andy_cooks_thumbnails_nasi_lemak_01.jpg?aspect_ratio=4:3&quality=90&'),
(17, 11, 'https://upload.wikimedia.org/wikipedia/commons/5/55/Nasi_Lemak_dengan_Chili_Nasi_Lemak_dan_Sotong_Pedas%2C_di_Penang_Summer_Restaurant.jpg'),
(18, 12, 'https://i0.wp.com/seonkyounglongest.com/wp-content/uploads/2021/01/Chicken-Porridge-15-mini.jpg?fit=1000%2C667&ssl=1'),
(19, 13, 'https://cicili.tv/wp-content/uploads/2021/10/IMG_0037-scaled-1200x675.jpg'),
(20, 13, 'https://cdn.sanity.io/images/2r0kdewr/production/3ae101eaa383838e0ed5a289dcbfce1a5039f2f3-1000x1000.jpg'),
(21, 14, 'https://kfc.com.my/media/catalog/product/i/c/iced-milo-medium.jpg?quality=80&bg-color=255,255,255&fit=bounds&height=&width='),
(22, 15, 'https://thetiggle.com/wp-content/uploads/2023/04/DSC_8141-min-819x1024.jpg'),
(23, 17, '../assets/images/products/prod_1765723162_2964_0.png'),
(24, 18, '../assets/images/products/prod_1765723457_4539_0.png'),
(25, 2, '../assets/images/products/iced_tea_1.jpg'),
(31, 7, '../assets/images/products/prod_1765725566_8953_0.jpg'),
(32, 1, '../assets/images/products/chicken_rice_1.jpg'),
(33, 1, '../assets/images/products/chicken_rice_2.jpg'),
(34, 16, 'https://cf.shopee.com.my/file/33bf83ce5c3d8e0aff26982faa4e1421');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `ProductId` int(11) NOT NULL,
  `StallId` int(11) NOT NULL,
  `CategoryId` int(11) DEFAULT NULL,
  `ProductName` varchar(150) NOT NULL,
  `Description` text DEFAULT NULL,
  `UnitPrice` decimal(10,2) NOT NULL,
  `IsAvailable` tinyint(1) NOT NULL DEFAULT 1,
  `IsUnlimitedStock` tinyint(1) NOT NULL DEFAULT 0,
  `Stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`ProductId`, `StallId`, `CategoryId`, `ProductName`, `Description`, `UnitPrice`, `IsAvailable`, `IsUnlimitedStock`, `Stock`) VALUES
(1, 1, 1, 'Chicken Rice', 'Poached chicken with fragrant rice', 6.00, 1, 0, 43),
(2, 2, 2, 'Iced Tea', 'Chilled tea (no sugar by default)', 3.50, 1, 1, 50),
(3, 2, 3, 'Waffle', 'Crispy waffle with maple syrup', 5.00, 1, 0, 3),
(4, 2, 2, 'Lemon Tea', 'Freshly brewed lemon tea', 3.00, 0, 1, 0),
(5, 2, 2, 'Hot Coffee', 'Classic hot coffee', 4.00, 0, 0, 20),
(6, 2, 2, 'Cappuccino', 'Foamy cappuccino', 5.50, 1, 0, 1),
(7, 2, 2, 'Latte', 'Smooth and milky latte', 5.00, 0, 0, 15),
(8, 2, 3, 'Chocolate Waffle', 'Waffle with chocolate syrup', 5.50, 1, 0, 12),
(9, 2, 3, 'Strawberry Waffle', 'Waffle with strawberry toppings', 5.80, 1, 0, 8),
(10, 2, 3, 'Butter Waffle', 'Classic butter waffle', 4.50, 1, 0, 26),
(11, 2, 1, 'Nasi Lemak', 'Traditional Malaysian nasi lemak', 6.50, 1, 0, 25),
(12, 2, 1, 'Chicken Porridge', 'Comforting chicken porridge', 4.00, 1, 0, 17),
(13, 2, 1, 'Fried Rice', 'Homestyle fried rice', 5.00, 1, 0, 21),
(14, 2, 2, 'Iced Milo', 'Popular Malaysian drink', 3.80, 1, 0, 10),
(15, 2, 2, 'Iced Chocolate', 'Sweet iced chocolate', 4.20, 1, 0, 12),
(16, 3, 4, 'Chilli Pan Mee', 'delicious pan mee at here', 8.00, 1, 0, 18),
(17, 2, 5, 'bizhub c558 printer', 'Printer that can print A4, both mono and colored', 10.00, 1, 0, 10),
(18, 2, 3, 'Cantarella', 'Cantarella (Chinese: 坎特蕾拉) is a playable Havoc Unclear Resonator in Wuthering Waves. She is the thirty-sixth matriarch of the Fisalia family, and the former Blessed Maiden of Imperator. Her elegant, composed demeanor and captivating beauty conceal a dark and disturbing past, of which she is steadfastly searching for the means to liberate herself and her lineage.', 1000.00, 1, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `stalls`
--

CREATE TABLE `stalls` (
  `StallId` int(11) NOT NULL,
  `StaffId` int(11) NOT NULL,
  `StallName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `IsAvailable` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `LogoUrl` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stalls`
--

INSERT INTO `stalls` (`StallId`, `StaffId`, `StallName`, `Description`, `IsAvailable`, `CreatedAt`, `LogoUrl`) VALUES
(1, 2, 'Hainan Chicken Rice', 'Signature chicken rice & roasted options', 1, '2025-11-17 12:48:15', 'images/stalls/hainanchickenlogo.png'),
(2, 6, 'Waffle & Coffee', 'Fresh waffles and drinks', 1, '2025-11-17 12:48:15', 'images/stalls/wafflencoffeelogo.png'),
(3, 8, 'Best Nasi Lemak', '', 1, '2025-12-05 21:45:59', 'https://cf.shopee.com.my/file/33bf83ce5c3d8e0aff26982faa4e1421'),
(4, 9, 'Zus Coffee', 'A Necessity Not A Luxury', 1, '2025-12-05 21:52:32', 'images/stalls/stall_1764942752.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserId` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `HashedPassword` varchar(255) NOT NULL,
  `Role` enum('customer','admin','vendor') NOT NULL DEFAULT 'customer',
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserId`, `Name`, `Email`, `HashedPassword`, `Role`, `PhoneNumber`, `CreatedAt`) VALUES
(1, 'Admin', 'admin@canteen.test', '$2y$10$abcdefghijklmnopqrstuv', 'admin', '0123456789', '2025-11-17 12:48:15'),
(2, 'Vendor A', 'vendorA@canteen.test', '$2y$10$abcdefghijklmnopqrstuv', 'vendor', '0111111111', '2025-11-17 12:48:15'),
(3, 'Vendor B', 'vendorB@canteen.test', '$2y$10$abcdefghijklmnopqrstuv', 'vendor', '0222222222', '2025-11-17 12:48:15'),
(4, 'Alice Student', 'alice@student.test', '$2y$10$abcdefghijklmnopqrstuv', 'customer', '0333333333', '2025-11-17 12:48:15'),
(5, 'chongkimseng', 'chongkimseng@gmail.com', '$2y$10$zCwgIG232WW/PPuECmuVhuy0uLPGzW8WRu3oLgsO4sCn/bWjUVd2a', 'customer', NULL, '2025-11-17 14:49:51'),
(6, 'chongkimseng2', 'chongkimseng2@gmail.com', '$2y$10$zCwgIG232WW/PPuECmuVhuy0uLPGzW8WRu3oLgsO4sCn/bWjUVd2a', 'vendor', NULL, '2025-11-17 14:49:58'),
(7, 'Admin', 'admin@gmail.com', '$2y$10$8QgWumGxt9aUAF524TSRH.r5aiMO3dBIWghqLNhDqr5nxo5CMfoHe', 'admin', '01116478687', '2025-12-05 13:20:45'),
(8, 'Vendor C', 'vendorC@canteen.test', '$2y$10$zCwgIG232WW/PPuECmuVhuy0uLPGzW8WRu3oLgsO4sCn/bWjUVd2a', 'vendor', '0111223344555', '2025-12-05 21:45:59'),
(9, 'Vendor D', 'vendorD@canteen.test', '$2y$10$K9IsHYNj16QY4KcUPHGNaOfep81TJxckLljouv4wE1MB9ijJbbZJm', 'vendor', '0111223344555', '2025-12-05 21:52:32'),
(90807, 'Sum Ting Wong', 'alexwongfeihong@gmail.com', '$2y$10$x9pwG12w3MM1QGhtput5FumqY0OEZ39pqZADldR3Ij17Gleh3GEyu', 'customer', '', '2025-12-14 16:04:44'),
(2407123, 'LEE ZHEN HONG', 'zhlee-wm24@student.tarc.edu.my', '$2y$10$p8vcPc7j7IYo4IIlwYNcKOUFIXpojZbDD6F.mNfbI9u8cg4cy0aSG', 'customer', '01116478687', '2025-12-08 13:57:32'),
(2407479, 'Damien Goh Kun Xuan', 'damien@gmail.com', '$2y$10$fZbrsBxIUYq/euWmym3E3OnoAjZ6DwPmb2hpQvSd7ry1QeGej3xpe', 'customer', '0123456789', '2025-12-05 22:02:14'),
(2407502, 'LEE ZHEN HONG', 'leezhenhong15@gmail.com', '$2y$10$zCwgIG232WW/PPuECmuVhuy0uLPGzW8WRu3oLgsO4sCn/bWjUVd2a', 'customer', '01116478687', '2025-12-05 13:21:39'),
(2410038, 'Chan Jian Feng', 'chanjf-wm24@student.tarc.edu.my', '$2y$10$6HZnqse.oYMPmTPYK61alOK3PluFmkZ24asAZlLZUjKxvskUB5HW.', 'customer', '01111280282', '2025-11-22 21:05:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cartitems`
--
ALTER TABLE `cartitems`
  ADD PRIMARY KEY (`CartItemId`),
  ADD KEY `fk_cartitems_carts` (`CartId`),
  ADD KEY `fk_cartitems_products` (`ProductId`),
  ADD KEY `idx_cartitems_cartid` (`CartId`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`CartId`),
  ADD UNIQUE KEY `UserId` (`UserId`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`CategoryId`);

--
-- Indexes for table `orderitems`
--
ALTER TABLE `orderitems`
  ADD PRIMARY KEY (`OrderListId`),
  ADD KEY `fk_orderlists_orders` (`OrderId`),
  ADD KEY `fk_orderlists_products` (`ProductId`),
  ADD KEY `idx_orderlists_orderid` (`OrderId`),
  ADD KEY `idx_orderlists_status` (`Status`),
  ADD KEY `idx_orderlists_pickuptime` (`PickupTime`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`OrderId`),
  ADD KEY `fk_orders_payments` (`PaymentId`),
  ADD KEY `fk_orders_users` (`UserId`),
  ADD KEY `fk_orders_stalls` (`StallId`);

--
-- Indexes for table `passwordresets`
--
ALTER TABLE `passwordresets`
  ADD PRIMARY KEY (`ResetId`),
  ADD KEY `UserId` (`UserId`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`PaymentId`),
  ADD KEY `fk_payments_users` (`UserId`);

--
-- Indexes for table `productimages`
--
ALTER TABLE `productimages`
  ADD PRIMARY KEY (`ImageId`),
  ADD KEY `fk_productimages_products` (`ProductId`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`ProductId`),
  ADD KEY `fk_products_stalls` (`StallId`),
  ADD KEY `fk_products_categories` (`CategoryId`);

--
-- Indexes for table `stalls`
--
ALTER TABLE `stalls`
  ADD PRIMARY KEY (`StallId`),
  ADD KEY `fk_stalls_users` (`StaffId`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserId`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cartitems`
--
ALTER TABLE `cartitems`
  MODIFY `CartItemId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `CartId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `CategoryId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orderitems`
--
ALTER TABLE `orderitems`
  MODIFY `OrderListId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `OrderId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `passwordresets`
--
ALTER TABLE `passwordresets`
  MODIFY `ResetId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `PaymentId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `productimages`
--
ALTER TABLE `productimages`
  MODIFY `ImageId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `ProductId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `stalls`
--
ALTER TABLE `stalls`
  MODIFY `StallId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2410039;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cartitems`
--
ALTER TABLE `cartitems`
  ADD CONSTRAINT `fk_cartitems_carts` FOREIGN KEY (`CartId`) REFERENCES `carts` (`CartId`),
  ADD CONSTRAINT `fk_cartitems_products` FOREIGN KEY (`ProductId`) REFERENCES `products` (`ProductId`);

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_carts_users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`);

--
-- Constraints for table `orderitems`
--
ALTER TABLE `orderitems`
  ADD CONSTRAINT `fk_orderlists_orders` FOREIGN KEY (`OrderId`) REFERENCES `orders` (`OrderId`),
  ADD CONSTRAINT `fk_orderlists_products` FOREIGN KEY (`ProductId`) REFERENCES `products` (`ProductId`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_payments` FOREIGN KEY (`PaymentId`) REFERENCES `payments` (`PaymentId`),
  ADD CONSTRAINT `fk_orders_stalls` FOREIGN KEY (`StallId`) REFERENCES `stalls` (`StallId`),
  ADD CONSTRAINT `fk_orders_users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`);

--
-- Constraints for table `passwordresets`
--
ALTER TABLE `passwordresets`
  ADD CONSTRAINT `passwordresets_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`);

--
-- Constraints for table `productimages`
--
ALTER TABLE `productimages`
  ADD CONSTRAINT `fk_productimages_products` FOREIGN KEY (`ProductId`) REFERENCES `products` (`ProductId`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_categories` FOREIGN KEY (`CategoryId`) REFERENCES `categories` (`CategoryId`),
  ADD CONSTRAINT `fk_products_stalls` FOREIGN KEY (`StallId`) REFERENCES `stalls` (`StallId`);

--
-- Constraints for table `stalls`
--
ALTER TABLE `stalls`
  ADD CONSTRAINT `fk_stalls_users` FOREIGN KEY (`StaffId`) REFERENCES `users` (`UserId`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
