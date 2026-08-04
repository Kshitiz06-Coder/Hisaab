-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 27, 2026 at 05:41 AM
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
-- Database: `hisaab_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `currency` varchar(10) DEFAULT 'Rs',
  `avatar_color` varchar(10) DEFAULT '#16A34A',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `currency`, `avatar_color`, `created_at`) VALUES
(3, 'Kshitiz Khatiwada', 'kshitizkhatiwada787@gmail.com', '$2y$10$4P8CXfCOowWwJcCtEsy6mu2MSDkHYVtItGlDrm4zosE0iOHdbBuga', 'Rs', '#16A34A', '2026-07-25 17:50:15'),
(4, 'Sujal Sapkota', 'DevSujal@gmail.com', '$2y$10$lknpbVld6V.n3/KDnkpyWuytZAGHUqFJB0nKkedtoWE8pKuog67YW', 'Rs', '#16A34A', '2026-07-26 16:47:13'),
(5, 'Sarthak', 'sarthak@gmail.com', '$2y$10$NU2jZKNNuQ3HtCKTyJj/JeejfNxl0wlhDycj5tqsDbop49HybfEOu', 'Rs', '#16A34A', '2026-07-26 16:50:17');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
