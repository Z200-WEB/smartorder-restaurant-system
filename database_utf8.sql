SET NAMES utf8mb4;

-- ============================================================
-- SmartOrder v2.0 - Enhanced Database Schema
-- ============================================================

DROP TABLE IF EXISTS `sCategory`;
CREATE TABLE `sCategory` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `state` int(11) DEFAULT 1,
    `categoryNo` int(11) NOT NULL,
    `categoryName` varchar(100) NOT NULL,
    `icon` varchar(50) DEFAULT '🍽️',
    `sort_order` int(11) DEFAULT 0,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sCategory` VALUES
(1,1,1,'ခေါက်ဆွဲ / ထမင်း','🍜',1),
(2,1,2,'အသုပ်','🥗',2),
(3,1,3,'ဟင်း (ထမင်းပါ)','🍛',3),
(4,1,4,'အကြော်','🍤',4),
(5,1,5,'သောက်စရာ','🥤',5),
(6,1,6,'အချိုပွဲ','🍮',6);

DROP TABLE IF EXISTS `sItem`;
CREATE TABLE `sItem` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `state` int(11) DEFAULT 1,
    `category` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `price` int(11) NOT NULL,
    `description` varchar(500) DEFAULT NULL,
    `image_url` varchar(500) DEFAULT NULL,
    `is_popular` int(11) DEFAULT 0,
    `is_new` int(11) DEFAULT 0,
    `is_spicy` int(11) DEFAULT 0,
    `sort_order` int(11) DEFAULT 0,
    `allergens` varchar(255) DEFAULT NULL COMMENT 'Allergens (English, comma separated). none = no major allergens',
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sItem` VALUES
(1,1,1,'မုန့်ဟင်းခါး [おすすめ]',3000,'Mohinga: thin rice noodles in a fish and lemongrass broth with banana stem, topped with a boiled egg, fried split-pea fritters, coriander and lime. The national breakfast of Myanmar.',NULL,1,0,0,1,'fish, egg'),
(2,1,1,'ရှမ်းခေါက်ဆွဲ',3500,'Shan noodles: rice noodles with a mild chicken and tomato sauce, topped with crushed peanuts and pickled mustard greens.',NULL,1,0,0,2,'peanut, soy'),
(3,1,1,'အုန်းနို့ခေါက်ဆွဲ [期間限定]',4000,'Ohn no khao swe: wheat egg noodles in a creamy coconut-milk chicken soup, with boiled egg, crispy noodles, shallots and lime.',NULL,0,1,0,3,'egg, wheat, coconut'),
(4,1,1,'ဒံပေါက်',6000,'Danbauk (Myanmar biryani): fragrant butter rice cooked with spiced chicken, cashew nuts and raisins. Served with a small salad.',NULL,0,0,0,4,'milk, cashew (tree nut)'),
(5,1,2,'လက်ဖက်သုပ် [おすすめ]',3000,'Lahpet thoke (tea leaf salad): fermented green tea leaves mixed with fried beans, peanuts, sesame, dried shrimp, tomato, cabbage, garlic and chili. Contains caffeine.',NULL,1,0,1,1,'peanut, sesame, shrimp'),
(6,1,2,'ခရမ်းချဉ်သီးသုပ်',2500,'Tomato salad: fresh tomatoes and onion dressed with roasted chickpea powder, crushed peanuts, fish sauce and lime.',NULL,0,0,0,2,'peanut, fish'),
(7,1,2,'ဂျင်းသုပ်',2500,'Gyin thoke (ginger salad): shredded pickled ginger with fried beans, peanuts, sesame, dried shrimp and fried garlic.',NULL,0,0,0,3,'peanut, sesame, shrimp'),
(8,1,3,'ကြက်သားဟင်း',5000,'Chicken curry: chicken slow-cooked with onion, garlic, ginger, turmeric and chili in oil (Myanmar style). Served with steamed rice.',NULL,1,0,1,1,'none'),
(9,1,3,'ဝက်သားဆီပြန်',5500,'Pork curry: tender pork belly cooked with garlic, ginger, soy sauce and spices. Served with steamed rice.',NULL,0,0,0,2,'soy'),
(10,1,3,'ပုစွန်ဆီပြန် [期間限定]',8000,'Prawn curry: large prawns cooked in a tomato, onion and chili sauce. Served with steamed rice.',NULL,0,1,1,3,'shrimp'),
(11,1,3,'ပဲဟင်း',2000,'Dal (lentil soup): yellow lentils cooked with vegetables and mild spices. Vegan. Served with steamed rice.',NULL,0,0,0,4,'none'),
(12,1,4,'ဘူးသီးကြော်',1500,'Bottle gourd fritters: strips of gourd fried in a rice and wheat flour batter. Vegan.',NULL,0,0,0,1,'wheat'),
(13,1,4,'ပဲကြော်',1000,'Split-pea fritters: crispy fritters made only from yellow split peas, onion and spices. Vegan and gluten-free.',NULL,1,0,0,2,'none'),
(14,1,4,'ပုစွန်ကြော်',2500,'Shrimp fritters: small shrimp fried in a wheat flour and egg batter.',NULL,0,0,0,3,'shrimp, wheat, egg'),
(15,1,5,'ရေနွေးကြမ်း',0,'Myanmar green tea: plain hot green tea, free refills. Contains caffeine.',NULL,0,0,0,1,'none'),
(16,1,5,'လက်ဖက်ရည်',1500,'Myanmar milk tea: strong black tea with condensed and evaporated milk. Sweet. Contains caffeine.',NULL,1,0,0,2,'milk'),
(17,1,5,'ကော်ဖီ',1500,'Myanmar coffee: sweet coffee with condensed milk. Contains caffeine.',NULL,0,0,0,3,'milk'),
(18,1,5,'ကြံရည်',1500,'Sugarcane juice: freshly pressed sugarcane with a squeeze of lime. Non-alcoholic.',NULL,0,0,0,4,'none'),
(19,1,5,'မြန်မာ ဘီယာ',3000,'Myanmar Beer: local lager beer (alcoholic, 20+ only).',NULL,0,0,0,5,'wheat (barley)'),
(20,1,6,'ဖါလူဒါ [おすすめ]',3500,'Falooda: rose syrup and milk with vanilla ice cream, jelly, sago and basil seeds.',NULL,1,0,0,1,'milk, egg'),
(21,1,6,'ရွှေကြည်',2000,'Shwe gyi mont: baked semolina cake made with coconut milk, eggs and butter, topped with poppy seeds.',NULL,0,0,0,2,'egg, milk, wheat, coconut'),
(22,1,6,'မုန့်လုံးရေပေါ်',1500,'Mont lone yay paw: glutinous rice balls filled with palm sugar, topped with grated coconut. No egg, no milk.',NULL,0,0,0,3,'coconut'),
(23,1,6,'ကျောက်ကျော',1500,'Kyauk kyaw: two-layer agar jelly made with coconut milk and sugar. No egg, no milk.',NULL,0,0,0,4,'coconut');

DROP TABLE IF EXISTS `sManagement`;
CREATE TABLE `sManagement` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `state` int(11) DEFAULT 1,
    `orderNo` varchar(50) NOT NULL,
    `tableNo` int(11) NOT NULL,
    `dateA` datetime DEFAULT current_timestamp(),
    `dateB` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `kitchen_state` int(11) DEFAULT 0 COMMENT '0=new,1=cooking,2=ready,3=served',
    `estimated_minutes` int(11) DEFAULT 15,
    `notes` varchar(500) DEFAULT NULL,
    `ordered_at` datetime DEFAULT NULL COMMENT 'When the customer confirmed the order',
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sOrder`;
CREATE TABLE `sOrder` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `state` int(11) DEFAULT 1,
    `orderNo` varchar(50) NOT NULL,
    `itemNo` int(11) NOT NULL,
    `amount` int(11) DEFAULT 1,
    `item_notes` varchar(255) DEFAULT NULL COMMENT 'Customer note for this item',
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sTables`;
CREATE TABLE `sTables` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tableNo` int(11) NOT NULL,
    `tableName` varchar(50) DEFAULT NULL,
    `capacity` int(11) DEFAULT 4,
    `state` int(11) DEFAULT 1 COMMENT '1=active',
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sTables` VALUES
(1,1,'テーブル 1',4,1),
(2,2,'テーブル 2',4,1),
(3,3,'テーブル 3',4,1),
(4,4,'テーブル 4',4,1),
(5,5,'テーブル 5',6,1),
(6,6,'テーブル 6',6,1),
(7,7,'カウンター 1',2,1),
(8,8,'カウンター 2',2,1),
(9,9,'個室 A',8,1),
(10,10,'個室 B',8,1);
