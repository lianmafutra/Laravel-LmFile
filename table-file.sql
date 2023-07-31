CREATE TABLE `file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` varchar(500) DEFAULT NULL,
  `model_id` int(11) NOT NULL,
  `name_origin` text NOT NULL,
  `name_hash` text NOT NULL,
  `path` varchar(100) NOT NULL,
  `mime` varchar(15) NOT NULL,
  `size` float DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
);
