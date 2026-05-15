-- MySQL dump 10.13  Distrib 8.0.35, for Linux (x86_64)
--
-- Host: localhost    Database: tripplek
-- ------------------------------------------------------
-- Server version	8.0.35

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'Admin','admin@tripplek.co.ke','$2y$12$2qLO9bdow6OkPfd3Xk3/TOzkbgZPM0nzejaUjaCKv12bDSZ5aT4Tm','2026-05-13 23:20:25','2026-05-14 22:58:15');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `booking_ref` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `car_id` int unsigned NOT NULL,
  `customer_id` int unsigned NOT NULL,
  `service_id` tinyint unsigned NOT NULL,
  `pickup_location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dropoff_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_date` date NOT NULL,
  `pickup_time` time NOT NULL DEFAULT '08:00:00',
  `return_date` date DEFAULT NULL,
  `return_time` time DEFAULT NULL,
  `num_days` tinyint unsigned NOT NULL DEFAULT '1',
  `num_passengers` tinyint unsigned NOT NULL DEFAULT '1',
  `flight_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `special_requests` text COLLATE utf8mb4_unicode_ci,
  `base_amount` decimal(10,2) NOT NULL,
  `extras_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','active','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_booking_ref` (`booking_ref`),
  KEY `idx_booking_car` (`car_id`),
  KEY `idx_booking_customer` (`customer_id`),
  KEY `idx_booking_status` (`status`),
  KEY `idx_booking_date` (`pickup_date`),
  KEY `idx_availability` (`car_id`,`pickup_date`,`return_date`,`status`),
  KEY `fk_booking_service` (`service_id`),
  CONSTRAINT `fk_booking_car` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_booking_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_booking_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (1,'TK-2026-00001',4,1,1,'JKIA','KICC','2026-05-15','08:00:00','2026-05-30','08:00:00',16,1,'','None',192000.00,0.00,0.00,192000.00,'confirmed','','2026-05-15 01:01:43','2026-05-15 01:03:02');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `car_categories`
--

DROP TABLE IF EXISTS `car_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `car_categories` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cat_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `car_categories`
--

LOCK TABLES `car_categories` WRITE;
/*!40000 ALTER TABLE `car_categories` DISABLE KEYS */;
INSERT INTO `car_categories` VALUES (1,'saloon','Saloon / Sedan','Toyota Axio, Fielder, Corolla — city & everyday use',1),(2,'suv','SUV / 4x4','Toyota Prado, Land Cruiser — safaris & rough terrain',2),(3,'executive','Executive','Mercedes S-Class, BMW 7 Series — VIP & weddings',3),(4,'van','Van / Minibus','Toyota Hiace — group travel & airport transfers',4);
/*!40000 ALTER TABLE `car_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `car_features`
--

DROP TABLE IF EXISTS `car_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `car_features` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `car_id` int unsigned NOT NULL,
  `feature` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_car_features` (`car_id`),
  CONSTRAINT `fk_feature_car` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `car_features`
--

LOCK TABLES `car_features` WRITE;
/*!40000 ALTER TABLE `car_features` DISABLE KEYS */;
/*!40000 ALTER TABLE `car_features` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `car_images`
--

DROP TABLE IF EXISTS `car_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `car_images` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `car_id` int unsigned NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` tinyint unsigned NOT NULL DEFAULT '0',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_car_images_car` (`car_id`),
  CONSTRAINT `fk_image_car` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `car_images`
--

LOCK TABLES `car_images` WRITE;
/*!40000 ALTER TABLE `car_images` DISABLE KEYS */;
INSERT INTO `car_images` VALUES (2,10,'cars/10/img_6a06652180a020.55143583.jpg',NULL,1,0,'2026-05-15 00:13:21'),(3,9,'cars/9/img_6a06653d26d0e9.84659961.jpg',NULL,0,0,'2026-05-15 00:13:49'),(4,8,'cars/8/img_6a0665659e1ec1.53151226.jpg',NULL,0,0,'2026-05-15 00:14:29'),(5,7,'cars/7/img_6a0665943aa187.52778736.jpg',NULL,0,0,'2026-05-15 00:15:16'),(6,7,'cars/7/img_6a066594475b26.26814102.jpg',NULL,1,0,'2026-05-15 00:15:16'),(7,7,'cars/7/img_6a06659453e983.40277982.jpg',NULL,2,0,'2026-05-15 00:15:16'),(8,6,'cars/6/img_6a0667c3ba9aa3.87688447.jpg',NULL,0,0,'2026-05-15 00:24:35'),(9,5,'cars/5/img_6a066a4f0af043.27038551.jpg',NULL,0,0,'2026-05-15 00:35:27'),(10,4,'cars/4/img_6a066a9245cbb6.56323755.jpg',NULL,0,0,'2026-05-15 00:36:34'),(11,3,'cars/3/img_6a066aae26aae1.03619059.jpg',NULL,0,0,'2026-05-15 00:37:02'),(12,2,'cars/2/img_6a066ae53e4c66.73017219.jpg',NULL,0,0,'2026-05-15 00:37:57'),(13,1,'cars/1/img_6a066af2cf5952.16009862.webp',NULL,0,0,'2026-05-15 00:38:11');
/*!40000 ALTER TABLE `car_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cars`
--

DROP TABLE IF EXISTS `cars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cars` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` tinyint unsigned NOT NULL,
  `make` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` smallint unsigned NOT NULL,
  `registration_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'White',
  `transmission` enum('automatic','manual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'automatic',
  `fuel_type` enum('petrol','diesel','hybrid','electric') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'petrol',
  `passenger_capacity` tinyint unsigned NOT NULL DEFAULT '5',
  `luggage_capacity` tinyint unsigned NOT NULL DEFAULT '2',
  `engine_cc` smallint unsigned DEFAULT NULL,
  `has_ac` tinyint(1) NOT NULL DEFAULT '1',
  `has_wifi` tinyint(1) NOT NULL DEFAULT '0',
  `has_gps` tinyint(1) NOT NULL DEFAULT '0',
  `has_child_seat` tinyint(1) NOT NULL DEFAULT '0',
  `price_per_day` decimal(10,2) NOT NULL,
  `price_airport` decimal(10,2) DEFAULT NULL,
  `price_wedding` decimal(10,2) DEFAULT NULL,
  `status` enum('available','hired','maintenance','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `thumbnail_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_car_reg` (`registration_number`),
  KEY `idx_category` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_price` (`price_per_day`),
  KEY `idx_passengers` (`passenger_capacity`),
  KEY `idx_transmission` (`transmission`),
  CONSTRAINT `fk_car_category` FOREIGN KEY (`category_id`) REFERENCES `car_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cars`
--

LOCK TABLES `cars` WRITE;
/*!40000 ALTER TABLE `cars` DISABLE KEYS */;
INSERT INTO `cars` VALUES (1,1,'Toyota','Axio',2021,'KDA 123A','Silver','automatic','petrol',4,2,NULL,1,0,0,0,3500.00,NULL,NULL,'available','cars/1/img_6a066af2cf5952.16009862.webp','Well-maintained Toyota Axio, ideal for business and city travel. Fuel-efficient and comfortable.',0,1,'2026-05-14 22:58:15','2026-05-15 00:38:11'),(2,1,'Toyota','Premio',2020,'KDB 456B','White','automatic','petrol',4,2,NULL,1,0,0,0,4000.00,NULL,NULL,'available','cars/2/img_6a066ae53e4c66.73017219.jpg','Spacious and reliable Toyota Premio. Perfect for long-distance drives and corporate use.',0,1,'2026-05-14 22:58:15','2026-05-15 00:37:57'),(3,1,'Nissan','Tiida',2019,'KDC 789C','Blue','manual','petrol',4,2,NULL,1,0,0,0,3000.00,NULL,NULL,'available','cars/3/img_6a066aae26aae1.03619059.jpg','Affordable Nissan Tiida — great fuel economy, easy to drive in city traffic.',0,0,'2026-05-14 22:58:15','2026-05-15 00:37:02'),(4,2,'Toyota','Land Cruiser Prado',2022,'KDD 321D','Black','automatic','diesel',7,4,NULL,1,0,0,0,12000.00,NULL,NULL,'available','cars/4/img_6a066a9245cbb6.56323755.jpg','Premium Land Cruiser Prado — the ultimate safari and off-road vehicle. 4WD, leather seats, sun roof.',0,1,'2026-05-14 22:58:15','2026-05-15 00:36:34'),(5,2,'Toyota','RAV4',2021,'KDE 654E','White','automatic','petrol',5,3,NULL,1,0,0,0,7500.00,NULL,NULL,'available','cars/5/img_6a066a4f0af043.27038551.jpg','Versatile Toyota RAV4 — perfect for both city drives and weekend getaways.',0,1,'2026-05-14 22:58:15','2026-05-15 00:35:27'),(6,2,'Mitsubishi','Pajero',2020,'KDF 987F','Grey','automatic','diesel',7,4,NULL,1,0,0,0,9000.00,NULL,NULL,'available','cars/6/img_6a0667c3ba9aa3.87688447.jpg','Powerful Mitsubishi Pajero with full 4WD. Handles Kenya\'s toughest roads with ease.',0,0,'2026-05-14 22:58:15','2026-05-15 00:24:35'),(7,3,'Mercedes-Benz','E-Class',2022,'KDG 111G','Black','automatic','petrol',4,2,NULL,1,0,0,0,18000.00,NULL,NULL,'available','cars/7/img_6a0665943aa187.52778736.jpg','Luxurious Mercedes-Benz E-Class — the perfect vehicle for corporate executives and VIP guests.',0,1,'2026-05-14 22:58:15','2026-05-15 00:15:16'),(8,3,'BMW','5 Series',2021,'KDH 222H','White','automatic','petrol',4,2,NULL,1,0,0,0,16000.00,NULL,NULL,'available','cars/8/img_6a0665659e1ec1.53151226.jpg','Elegant BMW 5 Series with premium interior. Ideal for business meetings and special occasions.',0,0,'2026-05-14 22:58:15','2026-05-15 00:14:29'),(9,4,'Toyota','Hiace',2021,'KDI 333I','White','manual','diesel',14,6,NULL,1,0,0,0,8000.00,NULL,NULL,'available','cars/9/img_6a06653d26d0e9.84659961.jpg','Spacious Toyota Hiace — perfect for group airport transfers, tours, and corporate shuttles.',0,1,'2026-05-14 22:58:15','2026-05-15 00:13:49'),(10,4,'Toyota','Noah',2020,'KDJ 444J','Silver','automatic','petrol',8,3,NULL,1,0,0,0,6000.00,NULL,NULL,'available','cars/10/img_6a06652180a020.55143583.jpg','Comfortable Toyota Noah minivan — ideal for family trips and small group transfers.',0,1,'2026-05-14 22:58:15','2026-05-15 00:38:28');
/*!40000 ALTER TABLE `cars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cust_email` (`email`),
  KEY `idx_cust_phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'kelino chebon','kelvinchebon90@gmail.com','+254715925156','36893359',NULL,'2026-05-15 01:01:43');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int unsigned NOT NULL,
  `payment_method` enum('mpesa','airtel_money','bank_transfer','card','cash') COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_ref` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_ref` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KES',
  `status` enum('pending','processing','completed','failed','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `gateway_response` json DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pay_booking` (`booking_id`),
  KEY `idx_pay_method` (`payment_method`),
  KEY `idx_pay_status` (`status`),
  KEY `idx_pay_tx_ref` (`transaction_ref`),
  CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` tinyint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_service_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,'car-hire','Car Hire','Self-drive car rental by the day',NULL,1,1),(2,'airport-transfer','Airport Transfer','Point-to-point airport pickup and drop-off',NULL,2,1),(3,'chauffeur','Chauffeur Service','Professional driver for half-day or full-day',NULL,3,1),(4,'wedding','Wedding Packages','Elegant vehicles for your special day',NULL,4,1);
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_val` text COLLATE utf8mb4_unicode_ci,
  `label` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `sort_order` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`),
  KEY `idx_setting_group` (`group_name`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'company_name','Tripple K Car Hire & Transfers','Company Name','general',1),(2,'company_tagline','Your road, our wheels','Tagline','general',2),(3,'company_phone','+254 700 000 000','Primary Phone','general',3),(4,'company_whatsapp','+254700000000','WhatsApp Number','general',4),(5,'company_email','info@tripplek.co.ke','Email Address','general',5),(6,'company_address','Nairobi, Kenya','Physical Address','general',6),(7,'company_hours','Mon–Sat 7am–8pm, Sun 8am–6pm','Business Hours','general',7),(8,'company_website','','Website URL','general',8),(9,'maps_embed_url','','Google Maps Embed URL','general',9),(10,'kra_pin','','KRA PIN','legal',1),(11,'tra_license','','TRA License Number','legal',2),(12,'security_deposit_min','15000','Min Security Deposit (KES)','legal',3),(13,'security_deposit_max','50000','Max Security Deposit (KES)','legal',4),(14,'mpesa_env','sandbox','M-Pesa Environment','payments',1),(15,'mpesa_shortcode','','M-Pesa Paybill/Till No','payments',2),(16,'mpesa_consumer_key','','Daraja Consumer Key','payments',3),(17,'mpesa_consumer_secret','','Daraja Consumer Secret','payments',4),(18,'mpesa_passkey','','Daraja Passkey','payments',5),(19,'flutterwave_env','test','Flutterwave Environment','payments',6),(20,'flutterwave_public_key','','Flutterwave Public Key','payments',7),(21,'flutterwave_secret_key','','Flutterwave Secret Key','payments',8),(22,'flutterwave_hash','','Flutterwave Webhook Hash','payments',9),(23,'invoice_prefix','TK','Invoice Prefix','invoices',1),(24,'invoice_footer','Thank you for choosing Tripple K!','Invoice Footer Text','invoices',2),(25,'invoice_bank_name','','Bank Name','invoices',3),(26,'invoice_bank_account','','Bank Account Number','invoices',4),(27,'invoice_bank_branch','','Bank Branch','invoices',5);
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'tripplek'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-15 15:28:19
