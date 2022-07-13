<?php
try {
   $dbh = new PDO('mysql:host=localhost; dbname=laravel; charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
   $data = $dbh->query("SELECT * FROM tables")->fetchAll();
} catch (Exception $e) {
   echo 'Выброшено исключение: ', $e->getMessage(), "\n";
}

$tables = $dbh->query('SHOW TABLES')->fetchAll();

$sql_arr = [];

//var_dump($dbh->query("SHOW FIELDS FROM tables")->fetchAll());

foreach ($tables as $key => $table) {

   $stat = $dbh->query("SHOW TABLE STATUS FROM `laravel` WHERE `name` LIKE '" . $table[0] . "' ")->fetchAll();
//   var_dump($stat);
   $sql_arr[$table[0]]['status']['Engine'] = $stat[0]['Engine'];
   $sql_arr[$table[0]]['status']['Auto_increment'] = $stat[0]['Auto_increment'];
   $sql_arr[$table[0]]['status']['Collation'] = $stat[0]['Collation'];

   $cols = $dbh->query("SHOW FIELDS FROM " . $table[0] . "")->fetchAll();

   foreach ($cols as $key => $col) {
      var_dump($col);
      $sql_arr[$table[0]]['cols'][$key]['Field'] = $col['Field'];
      $sql_arr[$table[0]]['cols'][$key]['Type'] = $col['Type'];
      if ($col['Null'] == 'NO') {
         $sql_arr[$table[0]]['cols'][$key]['Null'] = 'NOT NULL';
      } else {
         $sql_arr[$table[0]]['cols'][$key]['Null'] = 'DEFAULT NULL';
      }

      if (strlen($col['Key']) > 0) {
         $sql_arr[$table[0]]['PK'] = "PRIMARY KEY (`" . $col['Field'] . "`)";
      }

      if (strlen($col['Extra']) > 0) {
         $sql_arr[$table[0]]['cols'][$key]['Extra'] = "AUTO_INCREMENT";
      }

   }

//
//   $sql_arr[$table[0]]['cols']['Type'] = $cols[$key]['Type'];

//   var_dump($dbh->query('SELECT * FROM ' . $table[0])->fetchAll());
}

//var_dump($sql_arr);
var_dump($sql_arr['daily_reports']);