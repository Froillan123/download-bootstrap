-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 13, 2025 at 05:45 AM
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
-- Database: `election`
--

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `candidate_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `position_id` int(11) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`candidate_id`, `full_name`, `position_id`, `photo`, `created_at`) VALUES
(2, 'Kenji Bushido', 6, NULL, '2025-06-12 13:57:54'),
(3, 'kimperor123', 3, NULL, '2025-06-12 14:16:40'),
(4, 'Maverick Villarta', 2, NULL, '2025-06-12 14:23:58'),
(5, 'Digong', 1, NULL, '2025-06-12 14:27:13'),
(6, 'Daisy', 1, NULL, '2025-06-12 14:27:25'),
(7, 'Jane Lopez', 1, NULL, '2025-06-12 14:36:17'),
(8, 'Ram Railey Alin', 1, NULL, '2025-06-12 14:50:58'),
(9, 'Bato Dela Rosa', 3, NULL, '2025-06-12 15:09:27'),
(10, 'Migz Zubiri', 3, NULL, '2025-06-12 15:09:34'),
(11, 'Mark Binay', 3, NULL, '2025-06-12 15:09:45'),
(12, 'Emee Marcos', 3, NULL, '2025-06-12 15:09:53'),
(13, 'Gwen Gatchalian', 3, NULL, '2025-06-12 15:10:02'),
(14, 'Dennis Durano', 3, NULL, '2025-06-12 15:10:15'),
(15, 'Erap Estrada', 3, NULL, '2025-06-12 15:10:27'),
(16, 'Mike Rama', 6, NULL, '2025-06-12 15:10:56'),
(17, 'Cinthia Villar', 3, NULL, '2025-06-12 15:11:19'),
(18, 'Kiko Pangilinan', 3, NULL, '2025-06-12 15:11:34'),
(19, 'Hacuman Carla', 2, NULL, '2025-06-12 15:12:39'),
(20, 'Bajenting John', 3, NULL, '2025-06-12 15:12:54'),
(21, 'Tidert', 3, NULL, '2025-06-12 15:23:12'),
(22, 'GG', 3, NULL, '2025-06-12 15:23:18'),
(101, 'Juan Dela Cruz', 1, NULL, '2025-06-13 03:07:35'),
(102, 'Leni Robredo', 1, NULL, '2025-06-13 03:07:35'),
(103, 'Carlos Ramos', 2, NULL, '2025-06-13 03:07:35'),
(104, 'Martha Santiago', 2, NULL, '2025-06-13 03:07:35'),
(105, 'Ariel Villanueva', 3, NULL, '2025-06-13 03:07:35'),
(106, 'Janice Cruz', 3, NULL, '2025-06-13 03:07:35'),
(107, 'Richard Moreno', 3, NULL, '2025-06-13 03:07:35'),
(108, 'Sandra Agustin', 3, NULL, '2025-06-13 03:07:35'),
(109, 'Emmanuel Cordero', 3, NULL, '2025-06-13 03:07:35'),
(110, 'Venus Herrera', 3, NULL, '2025-06-13 03:07:35'),
(111, 'Paul Mercado', 3, NULL, '2025-06-13 03:07:35'),
(112, 'Bea Flores', 3, NULL, '2025-06-13 03:07:35'),
(113, 'George Basilio', 3, NULL, '2025-06-13 03:07:35'),
(114, 'Tina Perez', 3, NULL, '2025-06-13 03:07:35'),
(115, 'Ronald Lim', 3, NULL, '2025-06-13 03:07:35'),
(116, 'Maynard Uy', 3, NULL, '2025-06-13 03:07:35'),
(117, 'Jose Ramirez', 6, NULL, '2025-06-13 03:07:35'),
(118, 'Anna Cruz', 6, NULL, '2025-06-13 03:07:35'),
(119, 'Maria Santos', 7, NULL, '2025-06-13 03:07:35'),
(120, 'Rafael Dizon', 7, NULL, '2025-06-13 03:07:35'),
(121, 'Pedro Pascual', 8, NULL, '2025-06-13 03:07:35'),
(122, 'Andrea Villanueva', 8, NULL, '2025-06-13 03:07:35'),
(123, 'Rico Navarro', 9, NULL, '2025-06-13 03:07:35'),
(124, 'Elaine Martinez', 9, NULL, '2025-06-13 03:07:35'),
(125, 'Miguel Garcia', 10, NULL, '2025-06-13 03:07:35'),
(126, 'Kristine Lopez', 10, NULL, '2025-06-13 03:07:35'),
(127, 'Arvin Reyes', 10, NULL, '2025-06-13 03:07:35'),
(128, 'Shiela Mendoza', 10, NULL, '2025-06-13 03:07:35'),
(129, 'Leo Salazar', 10, NULL, '2025-06-13 03:07:35'),
(130, 'Grace Tan', 10, NULL, '2025-06-13 03:07:35'),
(131, 'Romeo Dizon', 10, NULL, '2025-06-13 03:07:35'),
(132, 'Catherine Lim', 10, NULL, '2025-06-13 03:07:35'),
(133, 'Mark David', 10, NULL, '2025-06-13 03:07:35'),
(134, 'Jessa Manalo', 10, NULL, '2025-06-13 03:07:35'),
(135, 'Nathaniel Ong', 10, NULL, '2025-06-13 03:07:35'),
(136, 'Fatima Aquino', 10, NULL, '2025-06-13 03:07:35');

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `position_id` int(11) NOT NULL,
  `position_name` varchar(100) NOT NULL,
  `max_winners` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`position_id`, `position_name`, `max_winners`, `created_at`) VALUES
(1, 'President', 1, '2025-06-12 13:38:07'),
(2, 'Vice President', 1, '2025-06-12 13:38:16'),
(3, 'Senate', 12, '2025-06-12 13:38:24'),
(6, 'Mayor', 1, '2025-06-12 13:57:30'),
(7, 'Governor', 1, '2025-06-13 01:30:16'),
(8, 'Vice Governor', 1, '2025-06-13 01:30:25'),
(9, 'Vice Mayor', 1, '2025-06-13 01:30:40'),
(10, 'Municipal Councilors', 8, '2025-06-13 01:30:53');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('voting_end', '2025-06-13 11:31:00'),
('voting_start', '2025-06-13 11:10:00'),
('voting_status', 'inactive');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','voter') DEFAULT 'voter',
  `has_voted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `role`, `has_voted`, `created_at`) VALUES
(1, 'Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0, '2025-06-12 13:20:44'),
(6, 'Joseph Chavez', 'vmontes@hotmail.com', '$2y$10$ZaKSsEeHJV6BILzlGDIukOq2udRbEGmvqFflVW59IWzWI1L/9Wvca', 'voter', 1, '2025-06-13 02:52:29'),
(7, 'Froillan Kim B. Edem', 'froillan.edem@gmail.com', '$2y$10$ijEm.3fi82gi/ItWMSxrA.VIHBoASvqmlTD85KY62UpQacvQtePnu', 'voter', 1, '2025-06-13 02:52:54'),
(8, 'Elizabeth Richards', 'ampbelljohnathan@neal.com', '$2y$10$FXeah//d7J5SQXVVL47SfeMrtcTI9hBY0LttIVxewhaHKLmhoTWlS', 'voter', 1, '2025-06-13 02:53:28'),
(9, 'Albert Sanchez', 'vboyer@hotmail.com', '$2y$10$44vNtvp8Wmd4o8o7cpiVk.ISeZUjNvB58aNMfX8jpw1IJRbivw8ai', 'voter', 1, '2025-06-13 02:53:42'),
(10, 'Louis Martin', 'cjohnston@gmail.com', '$2y$10$RIoBg/5h1hxlsvhVXlT5Gu5Wa0TFTfI5sxJ6CTR6WpvPOfYheqbV2', 'voter', 1, '2025-06-13 02:53:58'),
(11, 'Taylor Beltran', 'ehale@cole.net', '$2y$10$5izIq8idNCF4GeRRDQeuAOcLodmHxde52URpfnTEjKYsShvHr0IqO', 'voter', 1, '2025-06-13 02:54:14'),
(12, 'Patrick Holmes', 'davissusan@hotmail.com', '$2y$10$ftxhBw9SfxSSdc/8I4dOG.qHePDd4YZO6W/2ONx8lKN19U5SQ8o2q', 'voter', 1, '2025-06-13 02:55:11'),
(13, 'Kimberly Paul', 'fgilbert@gmail.com', '$2y$10$XCfbznXIZEvvaokq6Y.U/eHXTjhYkktTUV/0Hh7BhHWtQb8b8gcI.', 'voter', 1, '2025-06-13 02:55:59'),
(14, 'Charles Moore', 'frank81@martin.com', '$2y$10$dELJqnfVBhPr0xBmh2Li.O/Ynw4EcAhuqjIUnH4lUUKMLYa8CIM8O', 'voter', 1, '2025-06-13 02:56:20'),
(15, 'Michael Sutton', 'gomezbrandi@hotmail.com', '$2y$10$WrZXIZMx1z5zl0ufhijekOmv5qx/TUhwZRmjqZ0HfoPziF87CxX6K', 'voter', 1, '2025-06-13 02:56:35'),
(16, 'David Stevens', 'christopherhuber@medina.com', '$2y$10$n9oYX9lUNH7ZmWJdR17iP..bD971aWV9.547GYJByU/cFlbKYd6aS', 'voter', 1, '2025-06-13 02:57:06'),
(17, 'Andrew Pittman', 'qramsey@miller.net', '$2y$10$FL1ZnEPQ6I8H65RH06Gx/.tchEjWiywD5q7YfebmyAG68d5Qj3fSa', 'voter', 1, '2025-06-13 02:57:25'),
(18, 'Zachary Ruiz', 'lovemark@smith.com', '$2y$10$4Yg4rL4HVaGv4FrhIhvPW.cuGZu0a/wB3I2pi5j16BBFZeA5xremG', 'voter', 1, '2025-06-13 02:57:48'),
(19, 'Sheri Mejia', 'zjimenez@yahoo.com', '$2y$10$wA1booPXLzQYvNcOF8ceauRu8m.3J2LtVLSt2n0pp5Zm37I4fxLpy', 'voter', 1, '2025-06-13 02:58:02'),
(20, 'David Davis', 'jkemp@white.com', '$2y$10$RotlFrYrBQ5u4ZKtPSQQ2eQF4VezPi2koDUtdOWNPyg33njf6wCgK', 'voter', 1, '2025-06-13 02:58:18'),
(21, 'Samuel Sanchez', 'wpeters@yahoo.com', '$2y$10$nq9NmIGds0wQ/D8K1zc/w.eO1ZRTzKdkBIplndY5prYtjHZf1A5cO', 'voter', 1, '2025-06-13 02:58:30'),
(22, 'Glenda Hall', 'penny61@yahoo.com', '$2y$10$8WtxMbeiTcOhcdtlTMQ18ODyH84zqwc1U6V2GgAAM8.UMREIptuGW', 'voter', 1, '2025-06-13 02:58:54'),
(23, 'Jonathan Dunlap', 'james13@yahoo.com', '$2y$10$0w.sRgDlMqvY.yyHpo6R2uCiKPcY8peENoOHNExQ97AFVBOGJDWuK', 'voter', 1, '2025-06-13 02:59:08'),
(24, 'Zachary Harrison', 'rushglenn@garcia.info', '$2y$10$s4c4dWY6ZuNa8WiWjendKOIGMWZYsAtI1SFr9WAuWDc47owG4Atmu', 'voter', 1, '2025-06-13 02:59:21'),
(25, 'Ashley Johnson', 'sprice@hotmail.com', '$2y$10$hFkRi4gNbAT8ftlW.AZrvOR3Z3I4roYcLmN713L44O0fI.GlXWg6W', 'voter', 1, '2025-06-13 02:59:38');

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `vote_id` int(11) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `voted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `winners`
--

CREATE TABLE `winners` (
  `winner_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `position_name` varchar(255) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `candidate_name` varchar(255) NOT NULL,
  `vote_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `winners`
--

INSERT INTO `winners` (`winner_id`, `position_id`, `position_name`, `candidate_id`, `candidate_name`, `vote_count`, `created_at`) VALUES
(27, 1, 'President', 7, 'Jane Lopez', 15, '2025-06-13 03:37:18'),
(28, 2, 'Vice President', 19, 'Hacuman Carla', 17, '2025-06-13 03:37:18'),
(29, 3, 'Senate', 3, 'kimperor123', 20, '2025-06-13 03:37:18'),
(30, 3, 'Senate', 9, 'Bato Dela Rosa', 20, '2025-06-13 03:37:18'),
(31, 3, 'Senate', 13, 'Gwen Gatchalian', 18, '2025-06-13 03:37:18'),
(32, 3, 'Senate', 11, 'Mark Binay', 17, '2025-06-13 03:37:18'),
(33, 3, 'Senate', 20, 'Bajenting John', 17, '2025-06-13 03:37:18'),
(34, 3, 'Senate', 12, 'Emee Marcos', 17, '2025-06-13 03:37:18'),
(35, 3, 'Senate', 14, 'Dennis Durano', 16, '2025-06-13 03:37:18'),
(36, 3, 'Senate', 18, 'Kiko Pangilinan', 16, '2025-06-13 03:37:18'),
(37, 3, 'Senate', 10, 'Migz Zubiri', 15, '2025-06-13 03:37:18'),
(38, 3, 'Senate', 105, 'Ariel Villanueva', 13, '2025-06-13 03:37:18'),
(39, 3, 'Senate', 21, 'Tidert', 12, '2025-06-13 03:37:18'),
(40, 3, 'Senate', 15, 'Erap Estrada', 11, '2025-06-13 03:37:18'),
(41, 6, 'Mayor', 16, 'Mike Rama', 17, '2025-06-13 03:37:18'),
(42, 7, 'Governor', 119, 'Maria Santos', 11, '2025-06-13 03:37:18'),
(43, 8, 'Vice Governor', 121, 'Pedro Pascual', 16, '2025-06-13 03:37:18'),
(44, 9, 'Vice Mayor', 124, 'Elaine Martinez', 13, '2025-06-13 03:37:18'),
(45, 10, 'Municipal Councilors', 125, 'Miguel Garcia', 20, '2025-06-13 03:37:18'),
(46, 10, 'Municipal Councilors', 132, 'Catherine Lim', 18, '2025-06-13 03:37:18'),
(47, 10, 'Municipal Councilors', 130, 'Grace Tan', 16, '2025-06-13 03:37:18'),
(48, 10, 'Municipal Councilors', 129, 'Leo Salazar', 16, '2025-06-13 03:37:18'),
(49, 10, 'Municipal Councilors', 126, 'Kristine Lopez', 16, '2025-06-13 03:37:18'),
(50, 10, 'Municipal Councilors', 131, 'Romeo Dizon', 15, '2025-06-13 03:37:18'),
(51, 10, 'Municipal Councilors', 128, 'Shiela Mendoza', 15, '2025-06-13 03:37:18'),
(52, 10, 'Municipal Councilors', 127, 'Arvin Reyes', 13, '2025-06-13 03:37:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`candidate_id`),
  ADD KEY `position_id` (`position_id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`position_id`),
  ADD UNIQUE KEY `position_name` (`position_name`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`vote_id`),
  ADD KEY `voter_id` (`voter_id`),
  ADD KEY `candidate_id` (`candidate_id`),
  ADD KEY `position_id` (`position_id`);

--
-- Indexes for table `winners`
--
ALTER TABLE `winners`
  ADD PRIMARY KEY (`winner_id`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `candidate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `vote_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `winners`
--
ALTER TABLE `winners`
  MODIFY `winner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`) ON DELETE CASCADE;

--
-- Constraints for table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`voter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`candidate_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `votes_ibfk_3` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`) ON DELETE CASCADE;

--
-- Constraints for table `winners`
--
ALTER TABLE `winners`
  ADD CONSTRAINT `winners_ibfk_1` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`),
  ADD CONSTRAINT `winners_ibfk_2` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`candidate_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
