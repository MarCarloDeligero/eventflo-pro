-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 07, 2025 at 12:18 PM
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
-- Database: `eventflow_pro`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#007bff',
  `icon` varchar(50) DEFAULT 'fa-calendar',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `color`, `icon`, `is_active`, `created_at`) VALUES
(1, 'Conference', NULL, '#007bff', 'fa-users', 1, '2025-11-03 07:08:59'),
(2, 'Workshop', NULL, '#28a745', 'fa-tools', 1, '2025-11-03 07:08:59'),
(3, 'Networking', NULL, '#6f42c1', 'fa-handshake', 1, '2025-11-03 07:08:59'),
(4, 'Seminar', NULL, '#fd7e14', 'fa-chalkboard-teacher', 1, '2025-11-03 07:08:59'),
(5, 'Social', NULL, '#e83e8c', 'fa-glass-cheers', 1, '2025-11-03 07:08:59'),
(6, 'Training', NULL, '#20c997', 'fa-graduation-cap', 1, '2025-11-03 07:08:59'),
(7, 'Meetup', NULL, '#17a2b8', 'fa-meetup', 1, '2025-11-03 07:08:59');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location` varchar(255) NOT NULL,
  `venue_name` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `capacity` int(11) DEFAULT 0,
  `price` decimal(10,2) DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `status` enum('draft','published','cancelled','completed') DEFAULT 'published',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_recurring` tinyint(1) DEFAULT 0,
  `recurrence_pattern` enum('none','daily','weekly','monthly') DEFAULT 'none',
  `max_registrations` int(11) DEFAULT NULL,
  `registration_deadline` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `short_description`, `date`, `start_time`, `end_time`, `location`, `venue_name`, `latitude`, `longitude`, `capacity`, `price`, `image`, `user_id`, `category_id`, `status`, `is_featured`, `is_recurring`, `recurrence_pattern`, `max_registrations`, `registration_deadline`, `created_at`, `updated_at`) VALUES
(6, 'dasfdsaf', 'afda', 'adsfasdf', '2025-11-06', '09:00:00', '17:00:00', 'sasa', 's', NULL, NULL, 502, 1.00, '', 7, 7, 'published', 0, 0, 'none', NULL, NULL, '2025-11-05 14:38:25', '2025-11-05 14:40:51'),
(7, 'asfassss', 'afdsf', 'afasdf', '2025-11-14', '09:00:00', '17:00:00', 'dsad', 'dad', NULL, NULL, 50, 1.00, NULL, 11, 4, 'published', 0, 0, 'none', NULL, '2025-11-14 23:42:00', '2025-11-05 15:46:29', '2025-11-05 15:52:51'),
(8, 'sasa', 'sasa', 'sasa', '2025-11-27', '09:00:00', '17:00:00', 'sasa', 'sas', NULL, NULL, 50, 2.00, '690b74c81c880.jfif', 8, 5, 'published', 1, 0, 'none', NULL, NULL, '2025-11-05 16:00:45', '2025-11-05 16:01:12');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error','system') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `action_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_method` enum('stripe','paypal','bank_transfer','cash') DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `payer_email` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `status` enum('registered','attended','cancelled','no_show') DEFAULT 'registered',
  `ticket_number` varchar(20) NOT NULL,
  `guests_count` int(11) DEFAULT 0,
  `special_requirements` text DEFAULT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `check_in_time` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key_name` varchar(100) NOT NULL,
  `value` text NOT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key_name`, `value`, `description`, `updated_at`) VALUES
(1, 'site_name', 'EventFlow Pro', 'Website name', '2025-11-03 07:08:59'),
(2, 'site_email', 'noreply@eventflow.com', 'System email address', '2025-11-03 07:08:59'),
(3, 'registration_open', '1', 'Whether event registration is open globally', '2025-11-03 07:08:59'),
(4, 'max_events_per_user', '10', 'Maximum events a user can create', '2025-11-03 07:08:59'),
(5, 'auto_approve_events', '0', 'Whether events are auto-approved', '2025-11-03 07:08:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','organizer','attendee') DEFAULT 'attendee',
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `avatar`, `bio`, `phone`, `email_verified`, `verification_token`, `is_active`, `created_at`, `updated_at`) VALUES
(7, 'System Administrator', 'admin@eventflow.com', '$2y$10$U6VKSSb55yXTsROnoXCPKeKYj.LmkB4eJ9lMoekerbdNdYiIwIBvi', 'super_admin', NULL, '', '', 1, NULL, 1, '2025-11-03 08:49:55', '2025-11-03 09:59:37'),
(8, 'carlo baong deligero', 'carlo@gmail.com', '$2y$10$ENT2658p3/vuBK2A0ApBr.fpK5bu0s38GKsfocffNTsQwNmaGwkMy', 'organizer', 'avatar_8_69087ecb7dd54.jpg', 'biot', '', 1, NULL, 1, '2025-11-03 09:13:32', '2025-11-03 10:07:07'),
(9, 'Elmark Omasdang', 'elmark@gmail.com', '$2y$10$TvITvk4qyC12.vDVYn8HGObzJUTsNZWnVeWOkqlaJmLCmrsx8iTt2', 'attendee', NULL, '', '', 1, NULL, 1, '2025-11-03 09:28:40', '2025-11-03 09:59:37'),
(10, 'Mar Carlo B. Deligero', 'carlo1@gmail.com', '$2y$10$PZTbsfD7dPP6fHOFAKu9cuvTcJp4g22ZamI35P0iIKd29k1EvxLWK', 'attendee', NULL, 'nothing', '987 778723', 1, NULL, 1, '2025-11-03 12:35:15', '2025-11-03 12:42:03'),
(11, 'Mar Carlo B. Deligero', 'carlo11@gmail.com', '$2y$10$oL/ihYi7fa.65ottKRz90OyW54On8KqTedXN91xkeN1poyBYL9hqq', 'organizer', 'avatar_11_690b4757f282c.jpg', 'hehe', '', 1, NULL, 1, '2025-11-05 12:46:39', '2025-11-05 15:52:25'),
(12, 'Mar Carlo B. Deligero', 'carlo1q1@gmail.com', '$2y$10$Hk2/KPx/D6EonpaUP41Ex.GNB7lsA7Om0XAoDol8N3TYxGHJHp2Ly', 'attendee', NULL, NULL, NULL, 1, NULL, 1, '2025-11-07 06:03:35', '2025-11-07 06:03:35');

-- --------------------------------------------------------

--
-- Table structure for table `waitlist`
--

CREATE TABLE `waitlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `status` enum('waiting','promoted','cancelled') DEFAULT 'waiting',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `promoted_at` timestamp NULL DEFAULT NULL,
  `notified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_events_user_id` (`user_id`),
  ADD KEY `idx_events_category_id` (`category_id`),
  ADD KEY `idx_events_status` (`status`),
  ADD KEY `idx_events_date` (`date`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD UNIQUE KEY `unique_registration` (`user_id`,`event_id`),
  ADD KEY `fk_registrations_event` (`event_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `waitlist`
--
ALTER TABLE `waitlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `waitlist`
--
ALTER TABLE `waitlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `fk_registrations_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `waitlist`
--
ALTER TABLE `waitlist`
  ADD CONSTRAINT `waitlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `waitlist_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
