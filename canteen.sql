-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 21, 2025 at 04:05 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 7.4.29

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`CartId`, `UserId`) VALUES
(1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `CategoryId` int(11) NOT NULL,
  `CategoryName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`CategoryId`, `CategoryName`) VALUES
(1, 'Rice'),
(2, 'Drinks'),
(3, 'Dessert');

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
  `Status` enum('pending','preparing','ready','cancelled') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `orderitems`
--

INSERT INTO `orderitems` (`OrderListId`, `OrderId`, `ProductId`, `Quantity`, `Subtotal`, `Note`, `PickupTime`, `Status`) VALUES
(1, 1, 1, 2, '12.00', NULL, NULL, 'pending'),
(2, 2, 3, 1, '5.00', NULL, NULL, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `OrderId` int(11) NOT NULL,
  `PaymentId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `StallId` int(11) NOT NULL,
  `Status` enum('pending','preparing','ready','cancelled') NOT NULL DEFAULT 'pending',
  `Notes` text DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`OrderId`, `PaymentId`, `UserId`, `StallId`, `Status`, `Notes`, `CreatedAt`) VALUES
(1, 1, 4, 1, 'pending', 'Chicken Rice x2', '2025-11-17 12:48:15'),
(2, 1, 4, 2, 'preparing', 'Waffle x1', '2025-11-17 12:48:15');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `PaymentId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `TotalAmount` decimal(10,2) NOT NULL,
  `Status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`PaymentId`, `UserId`, `TotalAmount`, `Status`, `CreatedAt`) VALUES
(1, 4, '17.00', 'paid', '2025-11-17 12:48:15');

-- --------------------------------------------------------

--
-- Table structure for table `productimages`
--

CREATE TABLE `productimages` (
  `ImageId` int(11) NOT NULL,
  `ProductId` int(11) NOT NULL,
  `ImageURL` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `productimages`
--

INSERT INTO `productimages` (`ImageId`, `ProductId`, `ImageURL`) VALUES
(1, 1, '/images/products/chicken_rice_1.jpg'),
(2, 1, '/images/products/chicken_rice_2.jpg'),
(3, 2, 'images/products/iced_tea_1.jpg'),
(4, 3, 'images/products/waffle_1.jpg');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`ProductId`, `StallId`, `CategoryId`, `ProductName`, `Description`, `UnitPrice`, `IsAvailable`, `IsUnlimitedStock`, `Stock`) VALUES
(1, 1, 1, 'Chicken Rice', 'Poached chicken with fragrant rice', '6.00', 1, 0, 90),
(2, 2, 2, 'Iced Tea', 'Chilled tea (no sugar by default)', '3.50', 0, 1, 0),
(3, 2, 3, 'Waffle', 'Crispy waffle with maple syrup', '5.00', 1, 0, 12),
(4, 2, 2, 'Lemon Tea', 'Freshly brewed lemon tea', '3.00', 0, 1, 0),
(5, 2, 2, 'Hot Coffee', 'Classic hot coffee', '4.00', 0, 0, 20),
(6, 2, 2, 'Cappuccino', 'Foamy cappuccino', '5.50', 1, 0, 1),
(7, 2, 2, 'Latte', 'Smooth and milky latte', '5.00', 0, 0, 15),
(8, 2, 3, 'Chocolate Waffle', 'Waffle with chocolate syrup', '5.50', 1, 0, 12),
(9, 2, 3, 'Strawberry Waffle', 'Waffle with strawberry toppings', '5.80', 1, 0, 8),
(10, 2, 3, 'Butter Waffle', 'Classic butter waffle', '4.50', 1, 0, 30),
(11, 2, 1, 'Nasi Lemak', 'Traditional Malaysian nasi lemak', '6.50', 1, 0, 25),
(12, 2, 1, 'Chicken Porridge', 'Comforting chicken porridge', '4.00', 1, 0, 18),
(13, 2, 1, 'Fried Rice', 'Homestyle fried rice', '5.00', 1, 0, 22),
(14, 2, 2, 'Iced Milo', 'Popular Malaysian drink', '3.80', 1, 0, 10),
(15, 2, 2, 'Iced Chocolate', 'Sweet iced chocolate', '4.20', 1, 0, 12);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `stalls`
--

INSERT INTO `stalls` (`StallId`, `StaffId`, `StallName`, `Description`, `IsAvailable`, `CreatedAt`, `LogoUrl`) VALUES
(1, 2, 'Hainan Chicken Rice', 'Signature chicken rice & roasted options', 1, '2025-11-17 12:48:15', NULL),
(2, 6, 'Waffle & Coffee', 'Fresh waffles and drinks', 1, '2025-11-17 12:48:15', 'images/stalls/coffeshop.jpg\n');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserId`, `Name`, `Email`, `HashedPassword`, `Role`, `PhoneNumber`, `CreatedAt`) VALUES
(1, 'Admin', 'admin@canteen.test', '$2y$10$abcdefghijklmnopqrstuv', 'admin', '0123456789', '2025-11-17 12:48:15'),
(2, 'Vendor A', 'vendorA@canteen.test', '$2y$10$abcdefghijklmnopqrstuv', 'vendor', '0111111111', '2025-11-17 12:48:15'),
(3, 'Vendor B', 'vendorB@canteen.test', '$2y$10$abcdefghijklmnopqrstuv', 'vendor', '0222222222', '2025-11-17 12:48:15'),
(4, 'Alice Student', 'alice@student.test', '$2y$10$abcdefghijklmnopqrstuv', 'customer', '0333333333', '2025-11-17 12:48:15'),
(5, 'chongkimseng', 'chongkimseng@gmail.com', '$2y$10$IUmWevQUiDJ9SXySjEGYVOH3qBCKqyPxt0wCpg3vIUzEU/if2.zOG', 'customer', NULL, '2025-11-17 14:49:51'),
(6, 'chongkimseng2', 'chongkimseng2@gmail.com', '$2y$10$5vNz2bHNxTFbmcKS1kdah.Il.5z8W65MWOGGOztbRdBjMoH9LvA5C', 'vendor', NULL, '2025-11-17 14:49:58');

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
  MODIFY `CartItemId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `CartId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `CategoryId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orderitems`
--
ALTER TABLE `orderitems`
  MODIFY `OrderListId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `OrderId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `PaymentId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `productimages`
--
ALTER TABLE `productimages`
  MODIFY `ImageId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `ProductId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `stalls`
--
ALTER TABLE `stalls`
  MODIFY `StallId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
