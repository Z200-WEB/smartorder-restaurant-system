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
(1,1,1,'とりあえず注文','🍺',1),
(2,1,2,'お寿司','🍣',2),
(3,1,3,'焼き物','🔥',3),
(7,1,0,'揚げ物','🍟',4),
(8,1,0,'ドリンク','🥤',5),
(9,1,0,'デザート','🍮',6);

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
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sItem` VALUES
(2,1,1,'ナンとチキンカレー [期間限定]',1200,'スパイシーなチキンカレーとふわふわナン',NULL,0,1,1,1),
(3,1,1,'インド風おにくとご飯 [おすすめ]',4000,'特製スパイスで煮込んだビーフと白ご飯',NULL,1,0,0,2),
(4,1,1,'お寿司とチキンセット [期間限定]',2000,'新鮮なお寿司とジューシーなチキンのセット',NULL,0,1,0,3),
(5,1,2,'可愛いお巻き',1000,'彩り豊かな細巻きセット',NULL,0,0,0,1),
(6,1,2,'お寿司の森',2000,'季節の旬ネタ盛り合わせ',NULL,1,0,0,2),
(8,1,2,'サーモン巻き',1000,'新鮮サーモンの巻き寿司',NULL,1,0,0,3),
(9,1,2,'季節の巻き寿司',1000,'季節限定の特選巻き',NULL,0,1,0,4),
(10,1,2,'美味しいお寿司セット',3000,'特上ネタ10貫セット',NULL,1,0,0,5),
(13,1,3,'焼き鳥',2000,'備長炭で丁寧に焼き上げた串盛り',NULL,1,0,0,1),
(14,1,3,'お寿司山盛りセット1号',4000,'特上ネタ山盛りセット',NULL,0,0,0,2),
(16,1,3,'炭火焼4種 [おすすめ]',1600,'炭火で香ばしく焼いた4種盛り',NULL,1,0,0,3),
(17,1,7,'旨辛唐揚げ',1500,'ピリ辛特製ダレの唐揚げ',NULL,0,0,1,1),
(20,1,7,'ちょっといいポテト',690,'サクサクのクラフトフライドポテト',NULL,0,0,0,2),
(21,1,7,'普通のポテト',650,'定番フライドポテト',NULL,0,0,0,3),
(23,1,7,'FUJISANポテト [おすすめ]',670,'富士山型の見映えポテト',NULL,1,0,0,4),
(24,1,8,'飲み放題 [おすすめ]',3000,'2時間飲み放題コース',NULL,1,0,0,1),
(25,1,8,'生ビールメガ',500,'キンキンに冷えた生ビール大',NULL,1,0,0,2),
(27,1,8,'レモンサワーメガ',500,'さっぱりレモンサワー大',NULL,0,0,0,3),
(28,1,8,'レモンサワー',300,'定番レモンサワー',NULL,0,0,0,4),
(29,1,8,'ハイボールメガ',500,'濃いめハイボール大',NULL,0,0,0,5),
(30,1,8,'ハイボール',300,'すっきりハイボール',NULL,0,0,0,6),
(32,1,9,'本日のデザート盛り合わせ [おすすめ]',1000,'シェフ特製デザート盛り合わせ',NULL,1,0,0,1),
(33,1,9,'焦った目も美しいデザート',600,'見た目にもこだわったアーティスティックデザート',NULL,0,1,0,2),
(34,1,9,'ワッフル',500,'もちもちワッフル',NULL,0,0,0,3),
(35,1,9,'マカロンとバニラアイスの盛り合わせ [おすすめ]',700,'色とりどりマカロン＋バニラアイス',NULL,1,0,0,4),
(36,1,9,'ブランビーパンケーキ [おすすめ]',600,'ふわふわパンケーキ',NULL,1,0,0,5),
(37,1,3,'炭火焼8種',2000,'炭火で焼いた8種盛り合わせ',NULL,0,0,0,4),
(38,1,1,'枝豆',0,'サービス枝豆',NULL,0,0,0,4);

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
