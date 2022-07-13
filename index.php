<?php
try {
   $dbh = new PDO('mysql:host=localhost; dbname=laravel; charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
   $data = $dbh->query("SELECT * FROM tables")->fetchAll();
} catch (Exception $e) {
   echo 'Выброшено исключение: ', $e->getMessage(), "\n";
}

date_default_timezone_set('Europe/Moscow');

$tables = $dbh->query('SHOW TABLES')->fetchAll();

$sql_table = '';

//var_dump($dbh->query("SHOW FIELDS FROM tables")->fetchAll());

foreach ($tables as $key => $table) {

   $stat = $dbh->query("SHOW TABLE STATUS FROM `laravel` WHERE `name` LIKE '" . $table[0] . "' ")->fetchAll();

   $sql_table = 'CREATE TABLE IF NOT EXISTS `' . $table[0] . '` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(256) NOT NULL,
  `table_uuid` varchar(128) NOT NULL,
  `row_uuid` varchar(128) NOT NULL,
  `user_id` smallint(6) NOT NULL,
  `user_dep` smallint(6) NOT NULL,
  `json_val` json DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=' . $stat[0]['Engine'] . ' AUTO_INCREMENT=' . $stat[0]['Auto_increment'] . ' DEFAULT CHARSET=utf8mb4;';

   var_dump($sql_table);
}


$filename = date("d_m_y") . '_' . $table[0];
