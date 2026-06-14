-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Hôte : sql305.infinityfree.com
-- Généré le :  sam. 18 avr. 2026 à 08:56
-- Version du serveur :  11.4.10-MariaDB
-- Version de PHP :  7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `if0_40944037_ecommerce`
--

-- --------------------------------------------------------

--
-- Structure de la table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$VY9f4dyjvBq11yOh58ZTb.TeEwSwHFicm7c0gEwS5owQi6Rkawuxy', '2026-01-17 09:42:11');

-- --------------------------------------------------------

--
-- Structure de la table `carts`
--

CREATE TABLE `carts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','abandoned','ordered') DEFAULT 'active'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cart_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `payment_method` enum('cod','paygate','other') DEFAULT 'cod',
  `reference` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Processed','Shipped','Delivered') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_address` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `payment_method`, `reference`, `status`, `created_at`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`) VALUES
(28, 4, '10000.00', 'cod', NULL, 'Pending', '2026-01-15 23:59:21', 'Amenson', 'amentoulassi@gmail.com', '93814645', 'Léo2000'),
(29, 4, '25000.00', 'cod', NULL, 'Shipped', '2026-01-16 00:00:25', 'Amenson', 'amentoulassi@gmail.com', '93814645', 'Léo2000'),
(30, 5, '15000.00', 'cod', NULL, '', '2026-01-16 00:10:29', 'AMENS', 'amensontoulass@gmil.com', '99757811', 'Cacaveli'),
(31, 4, '1400.00', 'cod', NULL, 'Pending', '2026-01-16 19:35:30', 'Amenson', 'kossi@gmail.com', '93814645', 'Agoe Zongo'),
(32, 4, '5000.00', 'cod', NULL, 'Pending', '2026-01-16 19:40:56', 'Amenson', 'kossi@gmail.com', '93814645', 'cacaveli'),
(33, 4, '2000.00', 'cod', NULL, 'Shipped', '2026-01-17 11:45:12', 'Amenson', 'admin@gmail.com', '93814645', '1Cacaveli'),
(34, 4, '2000.00', 'cod', NULL, 'Shipped', '2026-01-19 19:52:29', 'Amenson', 'kossi@gmail.com', '93814645', 'SSS'),
(35, 4, '5000.00', 'cod', NULL, 'Shipped', '2026-01-20 12:14:40', 'Amenson', 'etudiant@ujm.fr', '93814645', 'cacaveli'),
(36, 4, '12000.00', 'cod', NULL, 'Pending', '2026-01-21 21:08:48', 'Amenson', 'kossi@gmail.com', '93814645', 'DDDD'),
(37, 4, '2000.00', 'cod', NULL, 'Shipped', '2026-01-22 07:57:17', 'Amenson', 'kossi@gmail.com', '93814645', '1234'),
(38, NULL, '2000.00', 'cod', NULL, 'Delivered', '2026-01-22 22:17:46', 'FORMATEC', 'etudiant@gmail.com', '93814645', '1234'),
(39, 4, '10000.00', 'paygate', NULL, 'Pending', '2026-01-22 23:50:14', 'Amenson', 'amentoulassui@gmail.com', '93814645', '1234'),
(40, 4, '10000.00', 'cod', NULL, 'Pending', '2026-01-22 23:56:14', 'Amenson', 'etudiant@ujm.fr', '93814645', 'ZZE'),
(41, 4, '10000.00', 'paygate', NULL, 'Pending', '2026-01-23 00:21:27', 'Amenson', 'admin@gmail.com', '93814645', '1'),
(42, 4, '120000.00', 'paygate', NULL, 'Pending', '2026-01-24 09:16:59', 'Amenson', 'amensontoulass@gmail.com', '99757811', 'leo-2mile(deux lions)'),
(43, 4, '120000.00', 'cod', NULL, 'Pending', '2026-01-24 09:17:10', 'Amenson', 'amentoulassi@gmail.com', '99757811', 'leo-2mile(deux lions)'),
(44, 4, '120000.00', 'paygate', NULL, 'Pending', '2026-01-24 09:17:29', 'Amenson', 'kossi@gmail.com', '99757811', 'leo-2mile(deux lions)'),
(45, 4, '15000.00', 'cod', NULL, 'Pending', '2026-01-25 20:40:23', 'Amenson', 'amentoulassi@gmail.com', '99757811', 'leo-2mile(deux lions)');

-- --------------------------------------------------------

--
-- Structure de la table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(41, 38, 34, 1, '2000.00'),
(40, 37, 21, 1, '2000.00'),
(39, 36, 34, 1, '2000.00'),
(38, 36, 35, 2, '5000.00'),
(37, 35, 35, 1, '5000.00'),
(36, 34, 21, 1, '2000.00'),
(35, 33, 25, 1, '2000.00'),
(34, 32, 33, 1, '5000.00'),
(33, 31, 24, 1, '1400.00'),
(32, 30, 30, 1, '15000.00'),
(31, 29, 31, 1, '25000.00'),
(30, 28, 32, 1, '10000.00'),
(42, 45, 30, 1, '15000.00');

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 100,
  `category` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `rating_total` int(11) DEFAULT 0,
  `rating_count` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `stock`, `category`, `created_at`, `rating_total`, `rating_count`) VALUES
(24, 'Parfums', 'Bien parfumé pour être alaise', '15000.00', 'uploads/products/prod_6967af1a714bb6.35933111.jpg', 5, 'mode', '2026-01-14 14:58:34', 0, 0),
(8, 'chaussures', '👟chaussure pour enfant', '1500.00', 'uploads/products/prod_6966d55a9a34b9.18413719.jpg', 5, 'mode', '2026-01-13 23:29:30', 0, 0),
(9, 'pull-over', 'Pull-over pour homme et femme à tous les couleurs disponibles', '15000.00', 'uploads/products/prod_6966d5fca59ac5.13164646.jpg', 10, 'mode', '2026-01-13 23:32:12', 0, 0),
(10, 'pull-over', 'pull-over pour 🚺femmes disponibles', '12000.00', 'uploads/products/prod_6966d63b57f4b1.07083939.jpg', 10, 'mode', '2026-01-13 23:33:15', 0, 0),
(11, '🤠chapeaux', 'A la mode style', '7000.00', 'uploads/products/prod_6966d68acb0117.18980853.jpg', 11, 'mode', '2026-01-13 23:34:34', 0, 0),
(12, '🥋ceinture', 'belt pour bien sapé', '1000.00', 'uploads/products/prod_6966d6f20dee53.15041127.jpg', 17, 'mode', '2026-01-13 23:36:18', 0, 0),
(13, 'jacket', 'jacket très clean à la mode', '13000.00', 'uploads/products/prod_6966d74490bbb7.19354802.jpg', 7, 'mode', '2026-01-13 23:37:40', 0, 0),
(14, 'shoes', 'pero-pero la sapologie', '12000.00', 'uploads/products/prod_6966d78775afe4.89781240.jpg', 10, 'mode', '2026-01-13 23:38:47', 0, 0),
(15, 'shoes', 'shoes sport', '13000.00', 'uploads/products/prod_6966d7b3379ae4.32537168.jpg', 10, 'mode', '2026-01-13 23:39:31', 0, 0),
(16, 'watch', 'montre de qualité', '12500.00', 'uploads/products/prod_6966d81547d886.09391069.jpg', 97, 'mode', '2026-01-13 23:41:09', 0, 0),
(17, 'laptop', 'pc portable de qualité', '100000.00', 'uploads/products/prod_6966d8606de991.00862685.jpg', 5, 'electronique', '2026-01-13 23:42:24', 0, 0),
(18, 'écouteurs', '🎧écouteur 🔚', '12000.00', 'uploads/products/prod_6966d8b5c08c69.29810296.jpg', 6, 'electronique', '2026-01-13 23:43:49', 0, 0),
(19, 'iPod', 'iPod de qualité resté clean', '9000.00', 'uploads/products/prod_6966d8fde79ef1.17310613.jpg', 3, 'electronique', '2026-01-13 23:45:01', 0, 0),
(31, 'BOATS', 'Codasse lamine yamal disponibles 👌', '25000.00', 'uploads/products/prod_69682b16528cc0.92093195.jpg', 4, 'loisirs', '2026-01-14 23:47:34', 0, 0),
(21, 'Alu outils', 'alutracko outils', '200000.00', 'uploads/products/prod_6966da887bb892.69391355.jpg', 3, 'electronique', '2026-01-13 23:51:36', 0, 0),
(22, 'Aspirateur', 'STANLEY Aspirateur Solides et Liquides SXVC25PTDE avec Prise Outils Électroportatifs (1200 W, 25 l)', '120000.00', 'uploads/products/prod_6966dc243122f6.36577753.jpg', 11, 'maison', '2026-01-13 23:58:28', 0, 0),
(23, 'Clés d\'Activité', 'Clés d\'activité Fieldmann pour les projets de bricolage et de jardinage à domicile', '120000.00', 'uploads/products/prod_6966dc74c117f7.99997252.jpg', 5, 'maison', '2026-01-13 23:59:48', 0, 0),
(25, 'Shoes 👟', '👟chaussure sport', '20000.00', 'uploads/products/prod_6967afb4931cb9.65463245.jpg', 3, 'mode', '2026-01-14 15:01:08', 0, 0),
(26, 'Shorts', 'porté propre le short très simples', '15000.00', 'uploads/products/prod_6967b01e081a26.04951847.jpg', 8, 'mode', '2026-01-14 15:02:54', 0, 0),
(30, 'AIR JORDAN 11 RETRO', 'Jordan chics a la mode pour toute catégorie', '15000.00', 'uploads/products/prod_69682ac21fa305.53371446.jpg', 8, 'mode', '2026-01-14 23:46:10', 14, 4),
(32, 'maillots Maroc', 'Maillot maroc disponible pour la CAN 2026', '10000.00', 'uploads/products/prod_69682b54eff134.72910873.jpg', 8, 'loisirs', '2026-01-14 23:48:36', 0, 0),
(33, 'Barcelona 25-26 Nike maillots', 'Dernière version de Maillots Barça', '5000.00', 'uploads/products/prod_69682be55dbe99.69376238.jpg', 9, 'loisirs', '2026-01-14 23:51:01', 0, 0),
(34, 'Maillots', 'Maillot dispo pour ce qui non pas de clubs fixe🤣', '2000.00', 'uploads/products/prod_69682caa6cee04.68858899.jpg', 0, 'loisirs', '2026-01-14 23:54:18', 3, 1),
(35, 'Maillot du bayern a domicile', 'Domicile maillots 25-26', '5000.00', 'uploads/products/prod_69682d14644c30.40696483.jpg', 6, 'loisirs', '2026-01-14 23:56:04', 0, 0);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_blocked` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `address`, `password`, `is_blocked`, `created_at`, `updated_at`) VALUES
(4, 'Amenson', 'amentoulassi@gmail.com', '93814645', 'AGOE1', '$2y$10$diG6DWA6exVUEsfGlxjrKOCdSMz/2da/DYDwkWxK4ssR0KCmR/Sgu', 0, '2026-01-15 20:39:39', '2026-01-15 21:31:29'),
(5, 'AMENS', 'amensontoulass@gmil.com', NULL, NULL, '$2y$10$IsYoFIVAY8YR6/r303qqUebv6mCxFiyHsS5QLcnU.XXjdgk0Oez9u', 0, '2026-01-15 20:44:02', '2026-01-15 20:44:02');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Index pour la table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`(250)),
  ADD KEY `idx_updated` (`updated_at`);

--
-- Index pour la table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Index pour la table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Index pour la table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT pour la table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT pour la table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
