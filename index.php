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

foreach ($tables as $table) {

   $stat = $dbh->query("SHOW TABLE STATUS FROM `laravel` WHERE `name` LIKE '" . $table[0] . "' ")->fetchAll();

   $cols = $dbh->query("SHOW FIELDS FROM " . $table[0] . "")->fetchAll();
   $sql_table = 'CREATE TABLE IF NOT EXISTS `' . $table[0] . '` (' . PHP_EOL;

   foreach ($cols as $key => $col) {
      var_dump($col);
      if ($col['Null'] == 'NO') {
         $null = 'NOT NULL';
      } else {
         $null = 'DEFAULT NULL';
      }

      if ($col['Key'] == 'PRI') {
         $pri = 'AUTO_INCREMENT,';
      } else {
         $pri = ',';
      }
      $sql_table .= ('`' . $col['Field'] . '` ' . $col['Type'] . ' ' . $null . $pri) . PHP_EOL;

   }
   $sql_table .= 'PRIMARY KEY (`id`)
) ENGINE=' . $stat[0]['Engine'] . ' AUTO_INCREMENT=' . $stat[0]['Auto_increment'] . ' DEFAULT CHARSET=utf8mb4;';

   var_dump($sql_table);
}


$filename = date("d_m_y") . '_' . $table[0];
