<?php
try {
   $dbh = new PDO('mysql:host=localhost; dbname=laravel; charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
   $data = $dbh->query("SELECT * FROM tables")->fetchAll();
} catch (Exception $e) {
   echo 'Выброшено исключение: ', $e->getMessage(), "\n";
}

date_default_timezone_set('Europe/Moscow');

$tables = $dbh->query('SHOW TABLES')->fetchAll();

$sql_cols_arr = [];

//var_dump($dbh->query("SHOW FIELDS FROM tables")->fetchAll());

foreach ($tables as $key => $table) {

   $stat = $dbh->query("SHOW TABLE STATUS FROM `laravel` WHERE `name` LIKE '" . $table[0] . "' ")->fetchAll();
   $sql_cols_arr[$table[0]]['status']['Engine'] = $stat[0]['Engine'];
   $sql_cols_arr[$table[0]]['status']['Auto_increment'] = $stat[0]['Auto_increment'];
   $sql_cols_arr[$table[0]]['status']['Collation'] = $stat[0]['Collation'];

   $cols = $dbh->query("SHOW FIELDS FROM " . $table[0] . "")->fetchAll();

   foreach ($cols as $k => $col) {
      $sql_cols_arr[$table[0]]['cols'][$key]['Field'] = $col['Field'];
      $sql_cols_arr[$table[0]]['cols'][$key]['Type'] = $col['Type'];
      if ($col['Null'] == 'NO') {
         $sql_cols_arr[$table[0]]['cols'][$key]['Null'] = 'NOT NULL';
      } else {
         $sql_cols_arr[$table[0]]['cols'][$key]['Null'] = 'DEFAULT NULL';
      }

      if (strlen($col['Key']) > 0) {
         $sql_cols_arr[$table[0]]['PK'] = "PRIMARY KEY (`" . $col['Field'] . "`)";
      }

      if (strlen($col['Extra']) > 0) {
         $sql_cols_arr[$table[0]]['cols'][$key]['Extra'] = "AUTO_INCREMENT";
      }
   }
//   var_dump($sql_table);

//   $fp = fopen($filename . ".sql", "w");
//   fwrite($fp, $sql_table);
//   fclose($fp);
}

$filename = date("d_m_y") . '_' . $table[0];

foreach ($sql_cols_arr as $key => $val) {

}
$sql_table = 'CREATE TABLE IF NOT EXISTS `' . $table[0] . '` (
  `' . $col['Field'] . '` smallint(6) NOT NULL AUTO_INCREMENT,
  `' . $col['Field'] . '` varchar(256) NOT NULL,
  `' . $col['Field'] . '` varchar(128) NOT NULL,
  `' . $col['Field'] . '` varchar(128) NOT NULL,
  `' . $col['Field'] . '` smallint(6) NOT NULL,
  `' . $col['Field'] . '` smallint(6) NOT NULL,
  `' . $col['Field'] . '` json DEFAULT NULL,
  `' . $col['Field'] . '` timestamp NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=196 DEFAULT CHARSET=utf8mb4;';
var_dump($sql_cols_arr);
//var_dump($sql_cols_arr['daily_reports']);

//
//   $sql_cols_arr[$table[0]]['cols']['Type'] = $cols[$key]['Type'];

//   var_dump($dbh->query('SELECT * FROM ' . $table[0])->fetchAll());