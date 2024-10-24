-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2024 at 02:58 PM
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
-- Database: `journal`
--

-- --------------------------------------------------------

--
-- Table structure for table `daily_goals`
--

CREATE TABLE `daily_goals` (
  `id` int(11) NOT NULL,
  `goal` varchar(255) NOT NULL,
  `category` enum('work','personal','health','learning') NOT NULL,
  `deadline` date DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_goals`
--

INSERT INTO `daily_goals` (`id`, `goal`, `category`, `deadline`, `progress`, `created_at`) VALUES
(3, 'buy a new car', 'personal', '2024-10-14', 31, '2024-10-14 10:02:57'),
(4, 'Write a few tweets', 'personal', '2024-10-23', 100, '2024-10-23 10:26:34');

-- --------------------------------------------------------

--
-- Table structure for table `diary_entries`
--

CREATE TABLE `diary_entries` (
  `id` int(11) NOT NULL,
  `entry_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `mood_emoji` varchar(10) DEFAULT NULL,
  `text_entry` varchar(200) DEFAULT NULL,
  `sentiment_class` varchar(10) NOT NULL DEFAULT 'neutral',
  `sentiment_positive` float NOT NULL DEFAULT 0,
  `sentiment_neutral` float NOT NULL DEFAULT 0,
  `sentiment_negative` float NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `diary_entries`
--

INSERT INTO `diary_entries` (`id`, `entry_date`, `mood_emoji`, `text_entry`, `sentiment_class`, `sentiment_positive`, `sentiment_neutral`, `sentiment_negative`) VALUES
(19, '2023-12-29 09:11:29', '🥳', 'Social media is now a gateway for customer service.', 'neutral', 0, 0, 0),
(20, '2023-12-29 09:12:00', '❤', 'Genuine passion magnetizes customers, team members, and investors.', 'neutral', 0, 0, 0),
(21, '2023-12-29 09:14:58', '🙄', 'Authentic leadership involves adapting learned behaviors to fit your style.', 'neutral', 0, 0, 0),
(22, '2023-12-29 09:15:24', '😎', 'Commit fully to tasks; consistent results get noticed.', 'neutral', 0, 0, 0),
(23, '2023-12-29 10:03:30', '😊', 'Physical activity boosts both brain function and mood.', 'neutral', 0, 0, 0),
(24, '2023-12-29 14:40:57', '❤', 'Be honest in conversations rather than making excuses.', 'neutral', 0, 0, 0),
(25, '2023-12-29 14:41:28', '❤', 'Avoid overcomplicating language; simplicity is key.', 'neutral', 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `life_events`
--

CREATE TABLE `life_events` (
  `id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `event_date` date NOT NULL DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `life_events`
--

INSERT INTO `life_events` (`id`, `category`, `description`, `event_date`, `created_at`) VALUES
(1, 'Milestone', 'Bought a new car - Toyota CHR 2024', '2024-10-12', '2024-10-12 14:50:37'),
(5, 'Achievement', 'I started learning Portuguese with the help of ChatGPT 4o. ', '2024-10-12', '2024-10-12 18:33:22'),
(6, 'Celebration', 'Sold my Jeep Renegade today!', '2024-10-13', '2024-10-13 09:47:57');

-- --------------------------------------------------------

--
-- Table structure for table `media_entries`
--

CREATE TABLE `media_entries` (
  `id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `place` varchar(100) DEFAULT NULL,
  `place_icon` varchar(50) DEFAULT NULL,
  `upload_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `media_entries`
--

INSERT INTO `media_entries` (`id`, `image_path`, `description`, `place`, `place_icon`, `upload_date`) VALUES
(2, 'uploads/media/671113fec5702.jpg', 'An image Bing dedicated to me', 'Tbilisi, Georgia', 'fas fa-map-marker-alt', '2024-10-17 17:41:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT 'uploads/profile/default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `profile_picture`) VALUES
(2, 'Nat', '$2y$10$FA0qQ4oV2j363CWaOXL9TO1rH7mRlKTi2xmK5DuiydcrnLkRGBbYG', 'uploads/profile/default.png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `daily_goals`
--
ALTER TABLE `daily_goals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `diary_entries`
--
ALTER TABLE `diary_entries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `life_events`
--
ALTER TABLE `life_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `media_entries`
--
ALTER TABLE `media_entries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `daily_goals`
--
ALTER TABLE `daily_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `diary_entries`
--
ALTER TABLE `diary_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `life_events`
--
ALTER TABLE `life_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `media_entries`
--
ALTER TABLE `media_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
