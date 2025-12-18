-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-12-18 07:29:22
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `canteen`
--

-- --------------------------------------------------------

--
-- 表的结构 `cartitems`
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
-- 转存表中的数据 `cartitems`
--

INSERT INTO `cartitems` (`CartItemId`, `CartId`, `ProductId`, `Quantity`, `Note`, `PickupTime`) VALUES
(1, 1, 1, 1, NULL, NULL),
(2, 1, 2, 2, NULL, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `carts`
--

CREATE TABLE `carts` (
  `CartId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `carts`
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
-- 表的结构 `categories`
--

CREATE TABLE `categories` (
  `CategoryId` int(11) NOT NULL,
  `CategoryName` varchar(100) NOT NULL,
  `CategoryLogo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `categories`
--

INSERT INTO `categories` (`CategoryId`, `CategoryName`, `CategoryLogo`) VALUES
(1, 'Rice', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCIgdmlld0JveD0iMCAwIDQ4IDQ4Ij4KCTxnIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLXdpZHRoPSI0Ij4KCQk8cGF0aCBkPSJNMjQgMzhjOS4zODkgMCAxNy03LjA1OSAxNy0xN0g3YzAgOS45NDEgNy42MTEgMTcgMTcgMTdabTYtMTdjMC01LjUyMy00LjI1My0xMC05LjUtMTBTMTEgMTUuNDc3IDExIDIxIiAvPgoJCTxwYXRoIGQ9Ik0zOSAyMWMwLTMuMzE0LTIuNzY2LTYtNi4xNzgtNmMtMS40NDMgMC0yLjc3LjQ4LTMuODIyIDEuMjg2IiAvPgoJCTxwYXRoIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgZD0ibTMzIDE1bDMtMTBtMiAxM2w0LTciIC8+CgkJPHBhdGggc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBkPSJtMTggMzdsLTIgNmgxNmwtMi02IiAvPgoJPC9nPgo8L3N2Zz4='),
(2, 'Drinks', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij4KCTxnIGZpbGw9Im5vbmUiPgoJCTxwYXRoIGQ9Ik03LjE1MiAyMmgxMC43N2wuNTc4LTguNDU0bC02LjM0LTEuMDUybC01LjcyMi0uOTN6TTMgNi4yMjJjMCAxLjc1IDEuNSAzLjE1OSAzLjI5IDMuMTU5bC0uMjE2LTMuMTU5aDMuMzg5YzAtMS43NDktMS40NDctMy4xNjYtMy4yMzItMy4xNjZDNC40NDcgMy4wNTYgMyA0LjQ3MyAzIDYuMjIyIiAvPgoJCTxwYXRoIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLWxpbmVjYXA9InNxdWFyZSIgc3Ryb2tlLXdpZHRoPSIyIiBkPSJNMyA2LjIyMmMwIDEuNzUgMS41IDMuMTU5IDMuMjkgMy4xNTlsLS4yMTYtMy4xNTloMy4zODljMC0xLjc0OS0xLjQ0Ny0zLjE2Ni0zLjIzMi0zLjE2NkM0LjQ0NyAzLjA1NiAzIDQuNDczIDMgNi4yMjJaIiAvPgoJCTxwYXRoIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLWxpbmVjYXA9InNxdWFyZSIgc3Ryb2tlLXdpZHRoPSIyIiBkPSJNMTkgMmgtMy43N0wxMi41IDEyTTYuMDc0IDYuMjIzSDE5TDE3LjkyMyAyMkg3LjE1ek0xNy45MjMgMjJINy4xNTJsLS43MTUtMTAuNDM2bDUuNzIyLjkzbDYuMzQxIDEuMDUyeiIgLz4KCTwvZz4KPC9zdmc+'),
(3, 'Dessert', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij4KCTxwYXRoIGZpbGw9IiMwMDAiIGQ9Ik0zIDEwcTAtMS4yNzUuNzM4LTIuM1Q1LjYgNi4yNXEuNDUtMi4yNzUgMi4yMzgtMy43NjNUMTIgMXQ0LjE2MyAxLjQ4OFQxOC40IDYuMjVxMS4xMjUuNDI1IDEuODYzIDEuNDVUMjEgMTBxMCAxLjg3NS0xLjMyNSAyLjk3NVQxNi43IDE0bC0zLjc3NSA3LjNxLS4xMjUuMjc1LS4zNjMuNHQtLjUxMi4xMjV0LS41MjUtLjEyNXQtLjM3NS0uNEw3LjM1IDE0cS0xLjc3NS4wNzUtMy4wNjItMS4wMjVUMyAxMG00IDJxLjM3NSAwIC43MzgtLjEyNXQuNjYyLS40MjVsLjU1LS41NWwuNjUuNHEuNTI1LjM1IDEuMTM4LjUyNVQxMiAxMnQxLjI2My0uMTc1VDE0LjQgMTEuM2wuNjUtLjRsLjU1LjU1cS4zLjMuNjYzLjQyNVQxNyAxMnEuODI1IDAgMS40MTMtLjU4N1QxOSAxMHEwLS43NS0uNDc1LTEuMzEyVDE3LjMgOGwtLjc1LS4xbC0uMDUtLjhxLS4xMjUtMS43MjUtMS40MjUtMi45MTJUMTIgM1Q4LjkyNSA0LjE4OFQ3LjUgNy4xbC0uMDUuOGwtLjc1LjE1cS0uNzUuMTUtMS4yMjUuNjc1VDUgMTBxMCAuODI1LjU4OCAxLjQxM1Q3IDEybTUuMDUgNi42NWwyLjctNS4yNXEtLjYuMy0xLjMuNDVUMTIgMTRxLS42NzUgMC0xLjM2Mi0uMTVUOS4zIDEzLjR6TTEyIDcuNSIgLz4KPC9zdmc+'),
(4, 'Noodle', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij4KCTxnIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLXdpZHRoPSIxLjUiPgoJCTxwYXRoIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgZD0iTTE4IDEyYTIuNSAyLjUgMCAwIDAtNSAwIiAvPgoJCTxwYXRoIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIgZD0iTTYgM3Y5bTIuNS05LjV2NU0xMSAydjUuNW0tNy0zbDItLjMxMk0yMCAybC02LjUgMS4wMTZNNCA3bDItLjEyNU0yMCA2bC02LjUuNDA2IiAvPgoJCTxwYXRoIHN0cm9rZS1saW5lam9pbj0icm91bmQiIGQ9Ik00LjkxMSAxMkgxOS4wOWMxLjYwMiAwIDIuMTkuMzcgMS43OSAxLjk4MmMtLjcwNiAyLjg0My0yLjcwMyAzLjU0OS00LjU0OSA1LjQwNGMtLjQ0OC40NS4yNSAxLjExNy4yNSAxLjYxM2MwIC45MzQtLjg4NyAxLjAwMS0xLjU5NSAxLjAwMWgtNS45N2MtLjcwOCAwLTEuNTk2LS4wNjctMS41OTUtMWMwLS40ODYuNjc3LTEuMTg0LjI1LTEuNjE0Yy0xLjg0Ni0xLjg1NS0zLjg0My0yLjU2MS00LjU0OS01LjQwNGMtLjQtMS42MTEuMTg4LTEuOTgyIDEuNzktMS45ODJaIiAvPgoJPC9nPgo8L3N2Zz4='),
(6, 'Bread', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNTYiIGhlaWdodD0iMjU2IiB2aWV3Qm94PSIwIDAgMjU2IDI1NiI+Cgk8cGF0aCBmaWxsPSIjMDAwIiBkPSJNMjQwIDgwYTQwIDQwIDAgMCAwLTQwLTQwSDQ4YTQwIDQwIDAgMCAwLTE2IDc2LjY1VjIwMGExNiAxNiAwIDAgMCAxNiAxNmgxNTJhMTYgMTYgMCAwIDAgMTYtMTZ2LTgzLjM1QTQwLjA2IDQwLjA2IDAgMCAwIDI0MCA4ME00OCAxMjBhOCA4IDAgMCAwIDAtMTZhMjQgMjQgMCAwIDEgMC00OGg5NmEyNCAyNCAwIDAgMSAwIDQ4YTggOCAwIDAgMCAwIDE2djgwSDQ4Wm0xNTItMTZhOCA4IDAgMCAwIDAgMTZ2ODBoLTQwdi04My4zNUE0MCA0MCAwIDAgMCAxNzYgNTZoMjRhMjQgMjQgMCAwIDEgMCA0OCIgLz4KPC9zdmc+'),
(7, 'Soup', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij4KCTxwYXRoIGZpbGw9IiMwMDAiIGQ9Ik01LjEgMTMuNXEuMi0uMjUuMy0uNjEyVDUuNSAxMnEwLS43NS0uNS0xLjl0LS41LTEuNzI1cTAtLjMuMDYzLS42MjVUNC45IDdoMS41cS0uMjc1LjQyNS0uMzM3Ljc1VDYgOC4zNzVxMCAuNTc1LjUgMS43MjVUNyAxMnEwIC41MjUtLjEuODYzdC0uMy42Mzd6bTYuNSAwcS4yLS4yNS4zLS42MTJUMTIgMTJxMC0uNzUtLjUtMS45VDExIDguMzc1cTAtLjMuMDYzLS42MjVUMTEuNCA3aDEuNXEtLjI3NS40MjUtLjMzNy43NXQtLjA2My42MjVxMCAuNTc1LjUgMS43MjV0LjUgMS45cTAgLjUyNS0uMS44NjN0LS4zLjYzN3ptLTMuMjUgMHEuMi0uMjUuMy0uNjEydC4xLS44ODhxMC0uNzUtLjUtMS45dC0uNS0xLjcyNXEwLS4zLjA2My0uNjI1VDguMTUgN2gxLjVxLS4yNzUuNDI1LS4zMzcuNzV0LS4wNjMuNjI1cTAgLjU3NS41IDEuNzI1dC41IDEuOXEwIC41MjUtLjEuODYzdC0uMy42Mzd6bTEuNCA4LjVxLTIuNTI1IDAtNC40NS0xLjY4N1QzIDE2LjEyNXEtLjA3NS0uNDUuMjM4LS43ODhUNCAxNWgxMC41MjVsMS4xLTEwLjM1cS4xMjUtMS4xMjUuOTYzLTEuODg4VDE4LjYgMnExLjI1IDAgMi4xMjUuODc1VDIxLjYgNXEwIC4zNS0uMDYyLjkyNWwtLjA2My41NzVsLTEuOTc1LS4yNWwuMDUtLjUxM3EuMDUtLjUxMi4wNS0uNzM3cTAtLjQyNS0uMjg4LS43MTJUMTguNiA0cS0uNCAwLS42NzUuMjYzVDE3LjYgNC45bC0xLjE1IDEwLjg3NXEtLjI3NSAyLjY1LTIuMTc1IDQuNDM4VDkuNzUgMjJtMC0ycTEuNDc1IDAgMi42NS0uODI1VDE0LjEgMTdINS4zMjVxLjU3NSAxLjM1IDEuNzYyIDIuMTc1VDkuNzUgMjBtMC0zIiAvPgo8L3N2Zz4='),
(8, 'Chicken', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNCIgaGVpZ2h0PSIxNCIgdmlld0JveD0iMCAwIDE0IDE0Ij4KCTxnIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2Utd2lkdGg9IjEiPgoJCTxwYXRoIHN0cm9rZS1saW5lam9pbj0icm91bmQiIGQ9Ik0yLjI0MyAyLjI0NnYxLjU2Mm00LjU2My0xLjU2MnYxLjU2Mk00LjUyNSAxLjA4M3YxLjU2MiIgLz4KCQk8cGF0aCBkPSJNMTEuNTA5IDQuNjAyYS45NTUuOTU1IDAgMSAwIDEuOTEgMGEuOTU1Ljk1NSAwIDEgMC0xLjkxIDAiIC8+CgkJPHBhdGggc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIgZD0ibTEwLjg0MSA4LjY3N2wuMjEyLjA5M2MxLjQ4LjcgMS44ODkgMS42MDYgMS44ODkgMi44NXYuMjk4YTEgMSAwIDAgMS0xIDFIMS42NGMtLjMyNyAwLS42MzQtLjE2MS0uNzYtLjQ2M2MtLjI3Ny0uNjY5LS41OTktMi4wMTMuMjM4LTMuNjg1YzEuMjEtMi40MTYgNC4xODctMi45NDcgNi4wOTQtMS45NDdsLjA5LjA0NiIgLz4KCQk8cGF0aCBkPSJNMTAuMTU4IDEwLjAwNmMuOTI4LTEuMDE4Ljk3Ni0yLjQ4NS4xMDgtMy4yNzVzLTIuMzIzLS42MDUtMy4yNS40MTNjLS44NDMuOTI1LS45NiAyLjIxOS0uMzI1IDMuMDRxLjA5Ny4xMjcuMjE3LjIzNSIgLz4KCQk8cGF0aCBzdHJva2UtbGluZWpvaW49InJvdW5kIiBkPSJNMTEuODA2IDUuMjk2TDEwLjMyIDYuNzgxIiAvPgoJPC9nPgo8L3N2Zz4='),
(9, 'Seafood', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij4KCTxnIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBzdHJva2Utd2lkdGg9IjEiPgoJCTxwYXRoIHN0cm9rZS13aWR0aD0iMS41IiBkPSJNMTAuNSA1YTguNSA4LjUgMCAxIDAgOC41IDguNWMwLS40Ny0uMzg0LS44NTktLjg0NS0uNzY2QTQuMjUgNC4yNSAwIDAgMCAxNC43NSAxNi45TTEwLjUgNWg2LjhjLjkzOSAwIDEuNzIyLjc3MyAxLjQ5IDEuNjgzQTYuOCA2LjggMCAwIDEgMTIuMiAxMS44aC0xLjdhMi41NSAyLjU1IDAgMCAwIDAgNS4xaDQuMjVNMTAuNSA1djMuNG00LjI1IDguNXYuODVNNS40IDIwLjNsMy40LTMuNE0zLjI3NSA5LjI1bDQuNjc1IDMuODI1IiAvPgoJCTxwYXRoIHN0cm9rZS13aWR0aD0iMiIgZD0iTTEzLjUgOGgtLjAwOSIgLz4KCQk8cGF0aCBzdHJva2Utd2lkdGg9IjEuNSIgZD0iTTkgMmgxMS4xNmMxLjE2MSAwIDIuMDMxIDEuMDUxIDEuODA0IDIuMTc4Yy0uMjk1IDEuNDYtMS42MiAyLjUtMy4wNDUgMi44MjIiIC8+Cgk8L2c+Cjwvc3ZnPg=='),
(10, 'Snack', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij4KCTxnIGZpbGw9Im5vbmUiIGZpbGwtcnVsZT0iZXZlbm9kZCI+CgkJPHBhdGggZD0ibTEyLjU5NCAyMy4yNThsLS4wMTIuMDAybC0uMDcxLjAzNWwtLjAyLjAwNGwtLjAxNC0uMDA0bC0uMDcxLS4wMzZxLS4wMTYtLjAwNC0uMDI0LjAwNmwtLjAwNC4wMWwtLjAxNy40MjhsLjAwNS4wMmwuMDEuMDEzbC4xMDQuMDc0bC4wMTUuMDA0bC4wMTItLjAwNGwuMTA0LS4wNzRsLjAxMi0uMDE2bC4wMDQtLjAxN2wtLjAxNy0uNDI3cS0uMDA0LS4wMTYtLjAxNi0uMDE4bS4yNjQtLjExM2wtLjAxNC4wMDJsLS4xODQuMDkzbC0uMDEuMDFsLS4wMDMuMDExbC4wMTguNDNsLjAwNS4wMTJsLjAwOC4wMDhsLjIwMS4wOTJxLjAxOS4wMDUuMDI5LS4wMDhsLjAwNC0uMDE0bC0uMDM0LS42MTRxLS4wMDUtLjAxOS0uMDItLjAyMm0tLjcxNS4wMDJhLjAyLjAyIDAgMCAwLS4wMjcuMDA2bC0uMDA2LjAxNGwtLjAzNC42MTRxLjAwMS4wMTguMDE3LjAyNGwuMDE1LS4wMDJsLjIwMS0uMDkzbC4wMS0uMDA4bC4wMDMtLjAxMWwuMDE4LS40M2wtLjAwMy0uMDEybC0uMDEtLjAxeiIgLz4KCQk8cGF0aCBmaWxsPSIjMDAwIiBkPSJNMTQgMmEyIDIgMCAwIDEgMiAyaDFhMiAyIDAgMCAxIDIgMnYzLjAwM2EyIDIgMCAwIDEgMS44ODUgMi4xOTZsLS43MSA3LjFBMyAzIDAgMCAxIDE3LjE5IDIxSDYuODFhMyAzIDAgMCAxLTIuOTg1LTIuNzAxbC0uNzEtNy4xQTIgMiAwIDAgMSA1IDkuMDAzVjVhMiAyIDAgMCAxIDItMmgxYTIgMiAwIDAgMSAyIDJoMVY0YTIgMiAwIDAgMSAyLTJ6bS03LjI0IDlINS4xMDVsLjcxIDcuMWwuMDE5LjExNWExIDEgMCAwIDAgLjg2Ljc3OEw2LjgxIDE5aDEwLjM4bC4xMTctLjAwN2ExIDEgMCAwIDAgLjg2LS43NzhsLjAxOC0uMTE2bC43MS03LjA5OUgxNy4yNGwtLjAxOS4wMWEuMy4zIDAgMCAwLS4wODEuMDg4QTYgNiAwIDAgMSAxMiAxNGE2IDYgMCAwIDEtNS4xNC0yLjkwMmEuMy4zIDAgMCAwLS4wOC0uMDg5ek0xNCA0aC0xdjcuODc0bC4yNjItLjA3N3EuMjYtLjA4Ni41MDEtLjIwNWwuMjM3LS4xMjd6bS0zIDNoLTF2NC40NjVsLjIzNy4xMjdxLjI0Mi4xMTkuNS4yMDVsLjI2My4wNzd6bTYtMWgtMXYzLjQyNWwuMTQ1LS4xMDFjLjE5OS0uMTI4LjQyMy0uMjI1LjY2OC0uMjc4TDE3IDkuMDE0ek04IDVIN3Y0LjAxNGMuMzE5LjAzOC42MDcuMTUuODU1LjMxTDggOS40MjV6IiAvPgoJPC9nPgo8L3N2Zz4='),
(11, 'Salad', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij4KCTxnIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBzdHJva2Utd2lkdGg9IjIiPgoJCTxwYXRoIGQ9Ik03IDIxaDEwbS01IDBhOSA5IDAgMCAwIDktOUgzYTkgOSAwIDAgMCA5IDkiIC8+CgkJPHBhdGggZD0iTTExLjM4IDEyYTIuNCAyLjQgMCAwIDEtLjQtNC43N2EyLjQgMi40IDAgMCAxIDMuMi0yLjc3YTIuNCAyLjQgMCAwIDEgMy40Ny0uNjNhMi40IDIuNCAwIDAgMSAzLjM3IDMuMzdhMi40IDIuNCAwIDAgMS0xLjEgMy43YTIuNSAyLjUgMCAwIDEgLjAzIDEuMU0xMyAxMmw0LTQiIC8+CgkJPHBhdGggZD0iTTEwLjkgNy4yNUEzLjk5IDMuOTkgMCAwIDAgNCAxMGMwIC43My4yIDEuNDEuNTQgMiIgLz4KCTwvZz4KPC9zdmc+'),
(12, 'Western', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij4KCTxwYXRoIGZpbGw9IiMwMDAiIGQ9Ik0xMS4xNTEgNC40OTNBLjc1Ljc1IDAgMCAxIDExLjg1NiA0aDIuNDFhLjc1Ljc1IDAgMSAwIDAtMS41aC0yLjQxYTIuMjUgMi4yNSAwIDAgMC0yLjExNCAxLjQ4bC0uNzU5IDIuMDgzSDRhLjc1Ljc1IDAgMCAwLS43NDYuODIzbDEuMTY0IDExLjgzNmEyLjI1IDIuMjUgMCAwIDAgMi4yMzkgMi4wM2gyLjY0MWEyLjMgMi4zIDAgMCAxLS4xMjktLjc1NHYtLjc0Nkg2LjY1N2EuNzUuNzUgMCAwIDEtLjc0Ni0uNjc3TDQuODI3IDcuNTYzSDE0LjJsLS4zNTcgMy42MjVoMS41MDdsLjQyMy00LjMwMmEuNzUuNzUgMCAwIDAtLjc0Ni0uODI0SDEwLjU4eiIgLz4KCTxwYXRoIGZpbGw9IiMwMDAiIGQ9Ik0xMi45MTkgMTIuNjg5YTIuMjUgMi4yNSAwIDAgMC0yLjI1IDIuMjV2MS4wMzNhLjc1Ljc1IDAgMCAwIDAgMS40OTJ2Mi41MzVjMCAuNDE0LjMzNi43NS43NS43NWg3LjkxYS43NS43NSAwIDAgMCAuNzUtLjc1di0yLjUzNWEuNzUuNzUgMCAwIDAgMC0xLjQ5MlYxNC45NGEyLjI1IDIuMjUgMCAwIDAtMi4yNS0yLjI1em01LjY2IDMuMjc5aC02LjQxdi0xLjAzYS43NS43NSAwIDAgMSAuNzUtLjc1aDQuOTFhLjc1Ljc1IDAgMCAxIC43NS43NXptLTYuNDEgMS41aDEuMTg5bC43ODEuNzhhLjc1Ljc1IDAgMCAwIDEuMDYgMGwuNzgxLS43OGgyLjZ2MS43OGgtNi40MTF6IiAvPgo8L3N2Zz4='),
(13, 'Japanese', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij4KCTxnIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBzdHJva2Utd2lkdGg9IjEuNSI+CgkJPHBhdGggZD0iTTIwLjI1IDE0LjV2Mi43NWEzIDMgMCAwIDEtMyAzSDYuNzVhMyAzIDAgMCAxLTMtM1YxNC41IiAvPgoJCTxwYXRoIGQ9Ik0xMiAxMS4yNWExMS43NCAxMS43NCAwIDAgMSA4LjY0NyAzLjY1OGExLjUgMS41IDAgMCAwIDIuNi0xLjAxNHYtLjAxOUMyMy4yNSA4LjI4MyAxOC4yMTMgMy43NSAxMiAzLjc1Uy43NSA4LjI4My43NSAxMy44NzV2LjAxOWExLjUgMS41IDAgMCAwIDIuNiAxLjAxNEExMS43NCAxMS43NCAwIDAgMSAxMiAxMS4yNW0tNy4yOCAyLjQxMWw0LjAxLTkuNDc3bTMuMDEgNy4wNjZsMy4wNDItNy4xODhtMi43MjcgOC40ODZsMi40NjEtNS44MTkiIC8+Cgk8L2c+Cjwvc3ZnPg=='),
(14, 'Burger', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `orderitems`
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
-- 转存表中的数据 `orderitems`
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
(60, 59, 12, 1, 4.00, '', '2025-12-15 00:45:00', 'ready'),
(61, 60, 1, 1, 6.00, '', '2025-12-15 01:15:00', 'ready'),
(62, 61, 3, 1, 5.00, '', '2025-12-15 01:15:00', 'ready'),
(63, 62, 3, 1, 5.00, '', '2025-12-15 01:20:00', 'ready'),
(64, 63, 1, 1, 6.00, '', '2025-12-16 14:40:00', 'ready'),
(65, 64, 2, 1, 3.50, '', '2025-12-16 14:40:00', 'ready'),
(66, 65, 16, 1, 8.00, '', '2025-12-16 16:25:00', 'complete'),
(67, 66, 13, 1, 5.00, '', '2025-12-16 16:25:00', 'complete'),
(68, 66, 15, 1, 4.20, '', '2025-12-16 16:25:00', 'complete'),
(69, 67, 25, 1, 6.90, '', '2025-12-16 17:15:00', 'complete'),
(70, 67, 21, 1, 12.50, '', '2025-12-16 17:15:00', 'complete'),
(71, 68, 25, 1, 6.90, '', '2025-12-16 18:00:00', 'ready'),
(72, 69, 1, 1, 6.00, '', '2025-12-16 18:00:00', 'pending'),
(73, 70, 2, 1, 3.50, '', '2025-12-17 09:35:00', 'pending'),
(74, 70, 3, 1, 5.00, '', '2025-12-17 09:35:00', 'pending'),
(75, 71, 24, 1, 15.90, '', '2025-12-17 15:05:00', 'complete'),
(76, 72, 25, 1, 6.90, '', '2025-12-17 15:05:00', 'pending'),
(77, 73, 16, 1, 8.00, '', '2025-12-17 16:30:00', 'pending'),
(78, 74, 12, 1, 4.00, '', '2025-12-17 15:55:00', 'pending'),
(79, 75, 3, 1, 5.00, '', '2025-12-17 16:05:00', 'pending'),
(80, 76, 3, 1, 5.00, '', '2025-12-17 16:05:00', 'pending'),
(81, 77, 2, 1, 3.50, '', '2025-12-17 16:50:00', 'pending'),
(82, 78, 26, 1, 6.90, '', '2025-12-17 17:10:00', 'pending');

-- --------------------------------------------------------

--
-- 表的结构 `orders`
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
-- 转存表中的数据 `orders`
--

INSERT INTO `orders` (`OrderId`, `PaymentId`, `UserId`, `StallId`, `Status`, `Notes`, `CreatedAt`) VALUES
(1, 1, 4, 1, 'pending', 'Chicken Rice x2', '2025-11-17 12:48:15'),
(2, 1, 4, 2, 'ready', 'Waffle x1', '2025-11-17 12:48:15'),
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
(23, 18, 2407502, 2, 'complete', '', '2025-12-05 21:23:24'),
(24, 19, 2407502, 1, 'pending', '', '2025-12-05 21:31:59'),
(25, 20, 2407502, 1, 'pending', '', '2025-12-05 21:37:17'),
(26, 20, 2407502, 2, 'ready', '', '2025-12-05 21:37:17'),
(27, 21, 2407502, 1, 'pending', '', '2025-12-05 21:40:01'),
(28, 22, 2407502, 1, 'pending', '', '2025-12-05 22:11:41'),
(29, 22, 2407502, 2, 'complete', '', '2025-12-05 22:11:41'),
(30, 23, 2407502, 2, 'complete', '', '2025-12-05 22:12:56'),
(31, 24, 2407502, 2, 'complete', '', '2025-12-08 13:55:09'),
(32, 25, 2407123, 1, 'pending', '', '2025-12-08 13:58:28'),
(33, 25, 2407123, 2, 'ready', '', '2025-12-08 13:58:28'),
(34, 26, 2407502, 3, 'pending', '', '2025-12-08 14:21:31'),
(35, 27, 2407502, 2, '', '', '2025-12-08 18:39:02'),
(36, 27, 2407502, 3, 'pending', '', '2025-12-08 18:39:02'),
(37, 28, 2407502, 1, 'pending', '', '2025-12-08 18:45:15'),
(38, 28, 2407502, 1, 'pending', '', '2025-12-08 18:45:15'),
(39, 29, 2407502, 2, 'complete', '', '2025-12-08 18:46:57'),
(40, 30, 2407502, 2, 'complete', '', '2025-12-08 18:51:30'),
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
(59, 47, 90807, 2, 'ready', '', '2025-12-15 00:52:29'),
(60, 48, 90807, 1, 'ready', '', '2025-12-15 00:57:39'),
(61, 49, 90807, 2, 'ready', '', '2025-12-15 00:59:52'),
(62, 50, 6, 2, 'ready', '', '2025-12-15 01:03:33'),
(63, 51, 2410038, 1, 'ready', '', '2025-12-16 14:22:23'),
(64, 52, 2410038, 2, 'ready', '', '2025-12-16 15:31:56'),
(65, 53, 2407502, 3, 'complete', '', '2025-12-16 16:06:19'),
(66, 54, 2407502, 2, 'complete', '', '2025-12-16 16:06:53'),
(67, 55, 2407502, 5, 'complete', '', '2025-12-16 16:55:49'),
(68, 56, 2407123, 5, 'ready', '', '2025-12-16 17:52:19'),
(69, 56, 2407123, 1, 'pending', '', '2025-12-16 17:52:19'),
(70, 57, 2407502, 2, 'pending', '', '2025-12-17 10:06:56'),
(71, 58, 2407502, 5, 'complete', '', '2025-12-17 14:48:23'),
(72, 59, 2407502, 5, 'pending', '', '2025-12-17 14:51:54'),
(73, 60, 2407502, 3, 'pending', '', '2025-12-17 14:55:48'),
(74, 61, 2407502, 2, 'pending', '', '2025-12-17 15:47:21'),
(75, 62, 2407502, 2, 'pending', '', '2025-12-17 15:51:43'),
(76, 63, 2407123, 2, 'pending', '', '2025-12-17 16:30:31'),
(77, 64, 2407123, 2, 'pending', '', '2025-12-17 16:48:56'),
(78, 65, 2407123, 5, 'pending', '', '2025-12-17 16:51:19');

-- --------------------------------------------------------

--
-- 表的结构 `passwordresets`
--

CREATE TABLE `passwordresets` (
  `ResetId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `Token` varchar(64) NOT NULL,
  `ExpiresAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 转存表中的数据 `passwordresets`
--

INSERT INTO `passwordresets` (`ResetId`, `UserId`, `Token`, `ExpiresAt`) VALUES
(18, 2407502, '3bdc421ade795ebb88bb0a1b58fa1854e32d9f0f1775e2a4527503b6c65ab976', '2025-12-16 02:23:11'),
(19, 2410038, '784994093bf5b702440ec475a0b75ebc3c158142e87710709a3d51d3b42c1c37', '2025-12-16 02:24:12');

-- --------------------------------------------------------

--
-- 表的结构 `payments`
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
-- 转存表中的数据 `payments`
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
(47, 90807, 4.00, 'paid', 'cash', '2025-12-15 00:52:29'),
(48, 90807, 6.00, 'paid', 'cash', '2025-12-15 00:57:39'),
(49, 90807, 5.00, 'paid', 'cash', '2025-12-15 00:59:52'),
(50, 6, 5.00, 'paid', 'cash', '2025-12-15 01:03:33'),
(51, 2410038, 6.00, 'paid', 'cash', '2025-12-16 14:22:23'),
(52, 2410038, 3.50, 'paid', 'e-wallet', '2025-12-16 15:31:56'),
(53, 2407502, 8.00, 'paid', 'cash', '2025-12-16 16:06:19'),
(54, 2407502, 9.20, 'paid', 'e-wallet', '2025-12-16 16:06:53'),
(55, 2407502, 19.40, 'paid', 'card', '2025-12-16 16:55:49'),
(56, 2407123, 12.90, 'paid', 'card', '2025-12-16 17:52:19'),
(57, 2407502, 8.50, 'paid', 'card', '2025-12-17 10:06:56'),
(58, 2407502, 15.90, 'paid', 'e-wallet', '2025-12-17 14:48:23'),
(59, 2407502, 6.90, 'pending', 'cash', '2025-12-17 14:51:54'),
(60, 2407502, 8.00, 'paid', 'card', '2025-12-17 14:55:48'),
(61, 2407502, 4.00, 'paid', 'card', '2025-12-17 15:47:21'),
(62, 2407502, 5.00, 'paid', 'card', '2025-12-17 15:51:43'),
(63, 2407123, 5.00, 'paid', 'e-wallet', '2025-12-17 16:30:31'),
(64, 2407123, 3.50, 'pending', 'cash', '2025-12-17 16:48:56'),
(65, 2407123, 6.90, 'pending', 'cash', '2025-12-17 16:51:19');

-- --------------------------------------------------------

--
-- 表的结构 `productimages`
--

CREATE TABLE `productimages` (
  `ImageId` int(11) NOT NULL,
  `ProductId` int(11) NOT NULL,
  `ImageURL` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `productimages`
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
(25, 2, '../assets/images/products/iced_tea_1.jpg'),
(31, 7, '../assets/images/products/prod_1765725566_8953_0.jpg'),
(32, 1, '../assets/images/products/chicken_rice_1.jpg'),
(33, 1, '../assets/images/products/chicken_rice_2.jpg'),
(34, 16, 'https://cf.shopee.com.my/file/33bf83ce5c3d8e0aff26982faa4e1421'),
(35, 19, '../assets/images/products/prod_1765871853_9292_0.webp'),
(36, 20, '../assets/images/products/prod_1765873532_4421_0.png'),
(39, 22, '../assets/images/products/prod_1765873945_1445_0.png'),
(40, 21, '../assets/images/products/prod_1765874005_9800_0.png'),
(41, 23, '../assets/images/products/prod_1765874056_6597_0.png'),
(42, 24, '../assets/images/products/prod_1765874242_4568_0.png'),
(43, 25, '../assets/images/products/prod_1765874453_4032_0.png'),
(44, 26, '../assets/images/products/prod_1765874685_4437_0.png'),
(45, 27, '../assets/images/products/prod_1765874707_6727_0.png'),
(46, 28, '../assets/images/products/prod_1765874751_6013_0.png'),
(48, 30, '../assets/images/products/prod_1765993764_6319_0.png'),
(49, 31, '../assets/images/products/prod_1765993879_5132_0.png'),
(50, 32, '../assets/images/products/prod_1765993953_5499_0.png'),
(51, 33, '../assets/images/products/prod_1765994010_1443_0.png'),
(52, 34, '../assets/images/products/prod_1765994048_3641_0.jpg'),
(53, 35, '../assets/images/products/prod_1765994107_5310_0.png'),
(54, 36, '../assets/images/products/prod_1765994169_7054_0.png'),
(55, 37, '../assets/images/products/prod_1765994218_8350_0.png'),
(56, 38, '../assets/images/products/prod_1765994267_4776_0.jpg'),
(57, 39, '../assets/images/products/prod_1765994302_8742_0.png'),
(58, 40, '../assets/images/products/prod_1765994336_9755_0.png'),
(59, 41, '../assets/images/products/prod_1765994385_3131_0.png'),
(60, 42, '../assets/images/products/prod_1765994512_6056_0.png'),
(61, 43, '../assets/images/products/prod_1765994561_4373_0.png'),
(62, 44, '../assets/images/products/prod_1765994987_4427_0.png'),
(63, 45, '../assets/images/products/prod_1765995053_4735_0.png'),
(64, 46, '../assets/images/products/prod_1765995493_6063_0.jpg'),
(65, 47, '../assets/images/products/prod_1765995540_3894_0.jpg'),
(66, 48, '../assets/images/products/prod_1765995580_5824_0.jpg'),
(67, 49, '../assets/images/products/prod_1765995627_6233_0.jpg'),
(68, 50, '../assets/images/products/prod_1765995680_1116_0.jpg'),
(69, 51, '../assets/images/products/prod_1765995751_6281_0.jpg'),
(70, 52, '../assets/images/products/prod_1765995836_7638_0.jpg'),
(71, 53, '../assets/images/products/prod_1765995901_1710_0.jpg'),
(72, 54, '../assets/images/products/prod_1765995982_9769_0.jpg'),
(73, 55, '../assets/images/products/prod_1765996139_1362_0.png'),
(74, 56, '../assets/images/products/prod_1765996269_8158_0.webp'),
(75, 57, '../assets/images/products/prod_1765996492_9803_0.webp'),
(76, 58, '../assets/images/products/prod_1765997012_2339_0.jpeg'),
(77, 59, '../assets/images/products/prod_1765998185_3855_0.jpg'),
(78, 60, '../assets/images/products/prod_1765998212_5365_0.jpg'),
(79, 61, '../assets/images/products/prod_1765998259_3677_0.jpg'),
(80, 62, '../assets/images/products/prod_1765998313_4713_0.jpg'),
(81, 63, '../assets/images/products/prod_1765998381_5781_0.jpg'),
(82, 64, '../assets/images/products/prod_1765998446_5539_0.jpg'),
(83, 65, '../assets/images/products/prod_1765998531_1693_0.jpg');

-- --------------------------------------------------------

--
-- 表的结构 `products`
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
-- 转存表中的数据 `products`
--

INSERT INTO `products` (`ProductId`, `StallId`, `CategoryId`, `ProductName`, `Description`, `UnitPrice`, `IsAvailable`, `IsUnlimitedStock`, `Stock`) VALUES
(1, 1, 1, 'Chicken Rice', 'Poached chicken with fragrant rice', 6.00, 1, 0, 41),
(2, 2, 2, 'Iced Tea', 'Chilled tea (no sugar by default)', 3.50, 1, 1, 50),
(3, 2, 3, 'Waffle', 'Crispy waffle with maple syrup', 5.00, 1, 0, 0),
(4, 2, 2, 'Lemon Tea', 'Freshly brewed lemon tea', 3.00, 0, 1, 0),
(5, 2, 2, 'Hot Coffee', 'Classic hot coffee', 4.00, 0, 0, 20),
(6, 2, 2, 'Cappuccino', 'Foamy cappuccino', 5.50, 1, 0, 1),
(7, 2, 2, 'Latte', 'Smooth and milky latte', 5.00, 0, 0, 15),
(8, 2, 3, 'Chocolate Waffle', 'Waffle with chocolate syrup', 5.50, 1, 0, 12),
(9, 2, 3, 'Strawberry Waffle', 'Waffle with strawberry toppings', 5.80, 1, 0, 8),
(10, 2, 3, 'Butter Waffle', 'Classic butter waffle', 4.50, 1, 0, 26),
(11, 2, 1, 'Nasi Lemak', 'Traditional Malaysian nasi lemak', 6.50, 1, 0, 25),
(12, 2, 1, 'Chicken Porridge', 'Comforting chicken porridge', 4.00, 1, 0, 16),
(13, 2, 1, 'Fried Rice', 'Homestyle fried rice', 5.00, 1, 0, 20),
(14, 2, 2, 'Iced Milo', 'Popular Malaysian drink', 3.80, 1, 0, 10),
(15, 2, 2, 'Iced Chocolate', 'Sweet iced chocolate', 4.20, 1, 0, 11),
(16, 3, 4, 'Chilli Pan Mee (辣椒板面)', 'delicious pan mee at here', 8.00, 1, 0, 16),
(19, 2, 2, 'Mocha', 'A rich, chocolate-flavoured coffee drink made with espresso, steamed milk, and chocolate, offering a sweet, balanced taste of bitter coffee and decadent cocoa, like a chocolatey latte.', 4.50, 1, 0, 20),
(20, 5, 2, 'Vietnamese Spanish Latté Frappé', 'The Vietnamese Spanish Latté Frappé combines the depth of Vietnamese-style coffee with the sweet, creamy profile of our Spanish Latté, creating a bold, refreshing drink that’s full of character.', 14.50, 1, 0, 20),
(21, 5, 2, 'Matcha Strawberry Latté', 'Imagine a creamy strawberry blend kissed by the finest Niko Neko matcha… That’s our Matcha Strawberry Latté! It’s the perfect pick-me-up that will have you floating on cloud nine, latté-rally!', 12.50, 1, 0, 19),
(22, 5, 2, 'Thunder', 'Thunder was a special concoction when our Head Barista decided that he wanted to create a refreshing coffee for hot days. Being a fan of the Classic American Lemonade, he asked himself, “Why can’t I add Coffee into this?”', 9.90, 1, 0, 20),
(23, 5, 2, 'Pink Black', 'Pink is the new Black! Our ZUS mixologist concoted this Lychee Lemonade with Espresso especially for its attractive contrast in colour and fabulous flavour combination. It’s a must try!', 9.90, 1, 0, 20),
(24, 5, 2, 'Ro-Ro-Rosie Frappé', 'The ultimate sweet escape! This frappé blends milk, cham, and rose into a frosty, indulgent treat topped with a swirl of cream. It’s a playful is ox floral and creamy flavours, giving you a rose-tinted bliss in every sip.', 15.90, 1, 0, 19),
(25, 5, 2, 'Lychee Strawberry Cooler', 'When highlands berries meet lowland tropical fruit, thus the Lychee Strawberry Cooler was born! A refreshing blend of local fruits makes for the perfect combination of sweetness that is sure to refresh your day!', 6.90, 1, 0, 17),
(26, 5, 2, 'Creamy Mango', '', 6.90, 1, 0, 19),
(27, 5, 2, 'Tarik Milk Tea', 'Tarik Milk Tea satu! Blend the old with the new ? rich black tea, creamy cham, and a velvety touch of sweetness.', 6.90, 1, 0, 20),
(28, 5, 2, 'Tarik Cham Latté', 'Creamy milk meets bold black tea and robust coffee. This Malaysian-style drink, finished with smooth Velvet Cremé, gives you a luxurious twist on the classic Teh Tarik. Sekali sip, memang kaw!', 6.90, 1, 0, 20),
(30, 7, 14, 'Big Mac', 'Two all-beef patties with lettuce, onions, pickles, cheese and special sauce in a toasted sesame seed bun.', 13.80, 1, 0, 20),
(31, 7, 10, 'French Fries', 'We only use premium Russet Burbank variety potatoes for that fluffy inside, crispy outside taste of our world-famous fries.', 6.60, 1, 1, 0),
(32, 7, 2, 'Hot Milo', 'Chocolate malt deliciousness.', 5.70, 1, 0, 50),
(33, 7, 3, 'Sundae Cone', 'Sometimes, a McDonald\'s® Sundae Cone is enough to make your day.', 2.60, 1, 0, 100),
(34, 7, 2, 'Ice Blended Mocha', 'Sip on a bittersweet blend of coffee, chocolate, and steamed milk, topped with whipped cream.', 13.20, 1, 0, 20),
(35, 7, 14, 'GCB - Grilled Chicken Burger', 'A delicious grilled chicken thigh, topped with crunchy iceberg lettuce and smoky chargrilled sauce, served in a toasted sesame seed bun', 14.00, 1, 0, 30),
(36, 7, 14, 'Filet-O-Fish', 'A classic favourite of a fish burger served with tartar sauce and cheddar cheese in a steamed bun.', 9.90, 1, 0, 20),
(37, 7, 8, 'Ayam Goreng McD™ (mixed)', 'Juicy and tasty!', 14.20, 1, 0, 30),
(38, 7, 1, 'Nasi Lemak McD', '', 6.90, 1, 0, 20),
(39, 7, 14, 'Spicy Chicken McDeluxe™', 'Specially marinated whole chicken thigh meat with a delightfully crispy coat, layered with fresh lettuce and special sauce in a corn meal bun.', 14.20, 1, 0, 20),
(40, 7, 10, '6pcs Chicken McNuggets®', 'Crispy, boneless chicken breast and thigh meat chunks. Just the right size for a quick bite.', 9.90, 1, 0, 100),
(41, 7, 10, 'Hash Browns', 'Bite into our scrumptious golden Hash Browns made from quality potatoes for a hearty breakfast.', 1.00, 1, 0, 50),
(42, 7, 2, 'Coca-Cola®', 'Adds a zing to any McDonald’s meal.', 5.00, 1, 1, 0),
(43, 7, 2, 'Iced Lemon Tea', 'Citrusy and light flavours in every sip.', 4.30, 1, 1, 0),
(44, 7, 2, 'Iced Milo®', 'Theres nothing like an icy drink full of chocolate and malt flavours.', 5.80, 1, 1, 0),
(45, 7, 3, 'Oreo McFlurry™', 'Indulge in our famous Sundae, whirled with bits of Oreo.', 6.40, 1, 0, 50),
(46, 3, 4, 'Scallion Oil Noodles (葱油拌面)', 'A classic dish from Shanghai, scallion oil noodles are among the simplest dishes to make. Though you’ll be surprised by how delicious they are!', 5.50, 1, 0, 20),
(47, 3, 4, 'Zha Jiang Mian (炸酱面)', 'Chewy noodles served with an irresistible pork sauce and crunchy vegetable toppings, Zha Jiang Mian is a signature Beijing dish that’s perfect for weekday dinners.', 6.50, 1, 0, 30),
(48, 3, 4, 'Lanzhou beef noodle soup (兰州牛肉面)', 'Super fragrant, tangy and comforting, Lanzhou beef noodle soup is a culinary legend from the city which it’s named after. Check out this simplified version!', 13.50, 1, 0, 15),
(49, 3, 4, 'Singapore Mei Fun (星洲米粉)', 'A hearty all-in-one dish, Singapore Mei Fun (Rice Noodles) consists of tasty proteins, crunchy vegetables and flavourful seasonings.', 6.50, 1, 0, 20),
(50, 3, 4, 'Tomato and Egg Noodle Soup (西红柿鸡蛋面)', 'Very easy to make and tastes wonderful, tomato and egg noodle soup requires minimum ingredients and preparation. A dish everyone should learn to cook.', 7.50, 1, 0, 20),
(51, 3, 4, 'Beef Ho Fun (干炒牛河)', 'Succulent beef slices stir fried with soft, springy rice noodles then seasoned with soy sauce, Beef Chow Fun is a classic Cantonese delicacy not to miss.', 6.50, 1, 0, 20),
(52, 3, 4, 'Wonton Noodle Soup (广式云吞面)', 'An authentic tasting Cantonese wonton noodle soup with juicy pork and shrimp wontons, thin chewy Hong Kong noodles, and fragrant homemade chicken stock.', 7.50, 1, 0, 20),
(53, 3, 4, 'Wonton Char Siu Noodle Soup (叉烧云吞面)', 'This easy wonton char siu noodle soup features springy egg noodles and tender leafy greens served in a hearty chicken broth, topped with scrumptious char siu pork and juicy wontons.', 10.50, 1, 0, 20),
(54, 3, 4, 'Hokkien Prawn Mee Noodle Soup (虾面)', 'ender yellow noodles in a rich savory-sweet red-orange broth served with juicy prawns, fish cakes, crunchy bean sprouts, and crispy fried shallots.', 8.50, 1, 0, 20),
(55, 3, 4, 'Cantonese Fried Noodle (广府炸面线)', 'Cantonese Fried Noodle is another classic noodle dish that I grew up eating. It’s saucy, crispy, umami, and of course, easy to make.', 8.00, 1, 0, 20),
(56, 3, 4, 'Malaysian Yin Yong (鸳鸯)', 'Yin Yong is a commonly found Malaysian Cantonese dish made from deep fried rice vermicelli and flat rice noodles, cooked with slices of pork, fish, squid, prawns, cabbage, and choy sum in an egg sauce.', 9.00, 1, 0, 20),
(57, 3, 4, 'Cantonese Yee Mee (广府伊面)', 'This is a Yi Mein (yee mee / 伊面) recipe that is popular among the Chinese in Malaysia. Very flavorful, the deep-fried Yi Mein has a totally different dimension of flavor than other noodles.', 7.00, 1, 0, 20),
(58, 3, 1, 'Cantonese Style Braised Rice (广府焖饭)', 'Not only this is a very simple dish to prepare, it is also a wonderful way of turning the bits and pieces in your fridge into something very delicious.', 6.00, 1, 0, 20),
(59, 8, 1, 'Shrimp Fried Rice', 'Made with juicy shrimp, fluffy eggs, and perfectly seasoned rice, Chinese shrimp fried rice comes together in minutes and is so satisfying to enjoy!', 8.00, 1, 0, 15),
(60, 8, 1, 'Chicken Fried Rice', 'Tender chicken cubes are tossed with springy rice and crunchy vegetables, all flavored with a soy sauce-based seasoning mixture.', 7.00, 1, 0, 20),
(61, 8, 1, 'Yangzhou Fried Rice', 'An easy version of Yangzhou fried rice, a classic stir-fry dish combining a wide range of flavors and textures.', 6.50, 1, 1, 0),
(62, 8, 1, 'Pork Fried Rice', 'Enjoy the mix of fluffy rice, tender pork and crunchy veggies coated with umami-filled seasoning.', 9.00, 1, 0, 15),
(63, 8, 1, 'Soy Sauce Fried Rice', 'Soy Sauce Fried Rice refers to fried rice that’s distinctively flavored with soy sauce.', 5.50, 1, 1, 0),
(64, 8, 1, 'Egg Fried Rice', 'Egg fried rice is an essential dish on typical Chinese restaurant menus outside China.', 5.00, 1, 1, 0),
(65, 8, 1, 'Easy beef & pineapple fried rice', 'A quick one-bowl meal served in a pineapple shell. Try this easy pineapple fried rice with succulent beef cubes! It has a balanced flavour of savoury and sweet.', 7.50, 1, 0, 30);

-- --------------------------------------------------------

--
-- 表的结构 `stalls`
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
-- 转存表中的数据 `stalls`
--

INSERT INTO `stalls` (`StallId`, `StaffId`, `StallName`, `Description`, `IsAvailable`, `CreatedAt`, `LogoUrl`) VALUES
(1, 2, 'Hainan Chicken Rice', 'Signature chicken rice & roasted options', 1, '2025-11-17 12:48:15', 'images/stalls/hainanchickenlogo.png'),
(2, 6, 'Waffle & Coffee', 'Fresh waffles and drinks', 1, '2025-11-17 12:48:15', 'images/stalls/wafflencoffeelogo.png'),
(3, 8, 'Heong Kee Noodles', 'Going to the Asian food store looking for noodles can be overwhelming.', 1, '2025-12-05 21:45:59', 'https://cf.shopee.com.my/file/33bf83ce5c3d8e0aff26982faa4e1421'),
(4, 9, 'Cake Stall', '', 1, '2025-12-05 21:52:32', 'images/stalls/stall_1764942752.jpg'),
(5, 11, 'Zus Coffee', 'A Necessity Not A Luxury', 1, '2025-12-16 16:17:06', 'images/stalls/stall_1765873026.png'),
(6, 12, 'jah skcn', 'zxgbvvbcxv', 1, '2025-12-17 17:31:35', 'images/stalls/stall_1765963895.png'),
(7, 13, 'McDonald', 'A quick bite or a satisfying meal, we’ve got you covered. Click on the product for Nutritional Facts. Discover our food now.', 1, '2025-12-18 01:45:40', 'images/stalls/stall_1765993540.png'),
(8, 3, 'Fried Rice House', '', 1, '2025-12-18 02:48:56', 'images/stalls/stall.png');

-- --------------------------------------------------------

--
-- 表的结构 `users`
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
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`UserId`, `Name`, `Email`, `HashedPassword`, `Role`, `PhoneNumber`, `CreatedAt`) VALUES
(1, 'Admin', 'admin@canteen.test', '$2y$10$PUx346MJyq.ZwUW1vllK..8YB9OmxIXu1VvYwJ7xHIUGZyxH5iyuW', 'admin', '0123456789', '2025-11-17 12:48:15'),
(2, 'Vendor A', 'vendorA@canteen.test', '$2y$10$zCwgIG232WW/PPuECmuVhuy0uLPGzW8WRu3oLgsO4sCn/bWjUVd2a', 'vendor', '0111111111', '2025-11-17 12:48:15'),
(3, 'Vendor B', 'vendorB@canteen.test', '$2y$10$apRVZNZ7gaSV7m8Th5Xqaeo2dIw23iyokUC3a4kfoIH286qqtwP6i', 'vendor', '0111223344555', '2025-12-18 02:48:56'),
(4, 'Alice Student', 'alice@student.test', '$2y$10$PUx346MJyq.ZwUW1vllK..8YB9OmxIXu1VvYwJ7xHIUGZyxH5iyuW', 'customer', '0333333333', '2025-11-17 12:48:15'),
(5, 'chongkimseng', 'chongkimseng@gmail.com', '$2y$10$zCwgIG232WW/PPuECmuVhuy0uLPGzW8WRu3oLgsO4sCn/bWjUVd2a', 'customer', NULL, '2025-11-17 14:49:51'),
(6, 'chongkimseng2', 'chongkimseng2@gmail.com', '$2y$10$zCwgIG232WW/PPuECmuVhuy0uLPGzW8WRu3oLgsO4sCn/bWjUVd2a', 'vendor', NULL, '2025-11-17 14:49:58'),
(7, 'Admin', 'admin@gmail.com', '$2y$10$8QgWumGxt9aUAF524TSRH.r5aiMO3dBIWghqLNhDqr5nxo5CMfoHe', 'admin', '01116478687', '2025-12-05 13:20:45'),
(8, 'Vendor C', 'vendorC@canteen.test', '$2y$10$zCwgIG232WW/PPuECmuVhuy0uLPGzW8WRu3oLgsO4sCn/bWjUVd2a', 'vendor', '0111223344555', '2025-12-05 21:45:59'),
(9, 'Vendor D', 'vendorD@canteen.test', '$2y$10$K9IsHYNj16QY4KcUPHGNaOfep81TJxckLljouv4wE1MB9ijJbbZJm', 'vendor', '0111223344555', '2025-12-05 21:52:32'),
(11, 'Zus Coffee', 'ZusCoffee@canteen.test', '$2y$10$lwbu0Ua0TXK3OEftfRh9Fep5RmDgQZux10GiAJLnH1Wx9JN78trTC', 'vendor', '0111223344555', '2025-12-16 16:17:06'),
(12, 'Vendor E', 'vendorE@canteen.test', '$2y$10$kTYvkAnq6VhK.hx.QKCVhu2gazaH/61s3aSZ7vHZroYcUVWexHtC6', 'vendor', '0111223344555', '2025-12-17 17:31:35'),
(13, 'McDonald', 'mcdonald@gmail.com', '$2y$10$usEi4GeXqap5kWftznWwLuGlW4/W6ci4pyqG/qX3Hwp/Z10SRslmi', 'vendor', '0111223344555', '2025-12-18 01:45:40'),
(90807, 'Sum Ting Wong', 'alexwongfeihong@gmail.com', '$2y$10$x9pwG12w3MM1QGhtput5FumqY0OEZ39pqZADldR3Ij17Gleh3GEyu', 'customer', '', '2025-12-14 16:04:44'),
(2407123, 'LEE ZHEN HONG', 'zhlee-wm24@student.tarc.edu.my', '$2y$10$p8vcPc7j7IYo4IIlwYNcKOUFIXpojZbDD6F.mNfbI9u8cg4cy0aSG', 'customer', '01116478687', '2025-12-08 13:57:32'),
(2407479, 'Damien Goh Kun Xuan', 'damien@gmail.com', '$2y$10$fZbrsBxIUYq/euWmym3E3OnoAjZ6DwPmb2hpQvSd7ry1QeGej3xpe', 'customer', '0123456789', '2025-12-05 22:02:14'),
(2407502, 'LEE ZHEN HONG', 'leezhenhong15@gmail.com', '$2y$10$zCwgIG232WW/PPuECmuVhuy0uLPGzW8WRu3oLgsO4sCn/bWjUVd2a', 'customer', '01116478687', '2025-12-05 13:21:39'),
(2407522, 'Ng Yung Onn', 'ngyungoon@canteen.test', '$2y$10$PUx346MJyq.ZwUW1vllK..8YB9OmxIXu1VvYwJ7xHIUGZyxH5iyuW', 'customer', '0123456789', '2025-12-18 01:39:49'),
(2410038, 'Chan Jian Feng', 'chanjf-wm24@student.tarc.edu.my', '$2y$10$6HZnqse.oYMPmTPYK61alOK3PluFmkZ24asAZlLZUjKxvskUB5HW.', 'customer', '01111280282', '2025-11-22 21:05:00');

--
-- 转储表的索引
--

--
-- 表的索引 `cartitems`
--
ALTER TABLE `cartitems`
  ADD PRIMARY KEY (`CartItemId`),
  ADD KEY `fk_cartitems_carts` (`CartId`),
  ADD KEY `fk_cartitems_products` (`ProductId`),
  ADD KEY `idx_cartitems_cartid` (`CartId`);

--
-- 表的索引 `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`CartId`),
  ADD UNIQUE KEY `UserId` (`UserId`);

--
-- 表的索引 `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`CategoryId`);

--
-- 表的索引 `orderitems`
--
ALTER TABLE `orderitems`
  ADD PRIMARY KEY (`OrderListId`),
  ADD KEY `fk_orderlists_orders` (`OrderId`),
  ADD KEY `fk_orderlists_products` (`ProductId`),
  ADD KEY `idx_orderlists_orderid` (`OrderId`),
  ADD KEY `idx_orderlists_status` (`Status`),
  ADD KEY `idx_orderlists_pickuptime` (`PickupTime`);

--
-- 表的索引 `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`OrderId`),
  ADD KEY `fk_orders_payments` (`PaymentId`),
  ADD KEY `fk_orders_users` (`UserId`),
  ADD KEY `fk_orders_stalls` (`StallId`);

--
-- 表的索引 `passwordresets`
--
ALTER TABLE `passwordresets`
  ADD PRIMARY KEY (`ResetId`),
  ADD KEY `UserId` (`UserId`);

--
-- 表的索引 `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`PaymentId`),
  ADD KEY `fk_payments_users` (`UserId`);

--
-- 表的索引 `productimages`
--
ALTER TABLE `productimages`
  ADD PRIMARY KEY (`ImageId`),
  ADD KEY `fk_productimages_products` (`ProductId`);

--
-- 表的索引 `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`ProductId`),
  ADD KEY `fk_products_stalls` (`StallId`),
  ADD KEY `fk_products_categories` (`CategoryId`);

--
-- 表的索引 `stalls`
--
ALTER TABLE `stalls`
  ADD PRIMARY KEY (`StallId`),
  ADD KEY `fk_stalls_users` (`StaffId`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserId`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `cartitems`
--
ALTER TABLE `cartitems`
  MODIFY `CartItemId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- 使用表AUTO_INCREMENT `carts`
--
ALTER TABLE `carts`
  MODIFY `CartId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 使用表AUTO_INCREMENT `categories`
--
ALTER TABLE `categories`
  MODIFY `CategoryId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- 使用表AUTO_INCREMENT `orderitems`
--
ALTER TABLE `orderitems`
  MODIFY `OrderListId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- 使用表AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `OrderId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- 使用表AUTO_INCREMENT `passwordresets`
--
ALTER TABLE `passwordresets`
  MODIFY `ResetId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- 使用表AUTO_INCREMENT `payments`
--
ALTER TABLE `payments`
  MODIFY `PaymentId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- 使用表AUTO_INCREMENT `productimages`
--
ALTER TABLE `productimages`
  MODIFY `ImageId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- 使用表AUTO_INCREMENT `products`
--
ALTER TABLE `products`
  MODIFY `ProductId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- 使用表AUTO_INCREMENT `stalls`
--
ALTER TABLE `stalls`
  MODIFY `StallId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `UserId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2410039;

--
-- 限制导出的表
--

--
-- 限制表 `cartitems`
--
ALTER TABLE `cartitems`
  ADD CONSTRAINT `fk_cartitems_carts` FOREIGN KEY (`CartId`) REFERENCES `carts` (`CartId`),
  ADD CONSTRAINT `fk_cartitems_products` FOREIGN KEY (`ProductId`) REFERENCES `products` (`ProductId`);

--
-- 限制表 `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_carts_users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`);

--
-- 限制表 `orderitems`
--
ALTER TABLE `orderitems`
  ADD CONSTRAINT `fk_orderlists_orders` FOREIGN KEY (`OrderId`) REFERENCES `orders` (`OrderId`),
  ADD CONSTRAINT `fk_orderlists_products` FOREIGN KEY (`ProductId`) REFERENCES `products` (`ProductId`);

--
-- 限制表 `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_payments` FOREIGN KEY (`PaymentId`) REFERENCES `payments` (`PaymentId`),
  ADD CONSTRAINT `fk_orders_stalls` FOREIGN KEY (`StallId`) REFERENCES `stalls` (`StallId`),
  ADD CONSTRAINT `fk_orders_users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`);

--
-- 限制表 `passwordresets`
--
ALTER TABLE `passwordresets`
  ADD CONSTRAINT `passwordresets_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE;

--
-- 限制表 `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`);

--
-- 限制表 `productimages`
--
ALTER TABLE `productimages`
  ADD CONSTRAINT `fk_productimages_products` FOREIGN KEY (`ProductId`) REFERENCES `products` (`ProductId`);

--
-- 限制表 `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_categories` FOREIGN KEY (`CategoryId`) REFERENCES `categories` (`CategoryId`),
  ADD CONSTRAINT `fk_products_stalls` FOREIGN KEY (`StallId`) REFERENCES `stalls` (`StallId`);

--
-- 限制表 `stalls`
--
ALTER TABLE `stalls`
  ADD CONSTRAINT `fk_stalls_users` FOREIGN KEY (`StaffId`) REFERENCES `users` (`UserId`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
