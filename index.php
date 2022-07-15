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

$indx_key = '';

$col_name = '';

$table_val = '';

$vals_arr = [];

foreach ($tables as $table) {

   $stat = $dbh->query("SHOW TABLE STATUS FROM `laravel` WHERE `name` LIKE '" . $table[0] . "' ")->fetchAll();
   $cols = $dbh->query("SHOW FIELDS FROM " . $table[0] . "")->fetchAll();
   $sql_table = 'CREATE TABLE IF NOT EXISTS `' . $table[0] . '` (' . PHP_EOL;

   foreach ($cols as $key => $col) {

      if ($col['Null'] == 'NO') {
         $null = 'NOT NULL ';
      } else {
         $null = 'DEFAULT NULL';
      }

      if ($col['Key'] == 'PRI') {
         $pri = 'AUTO_INCREMENT,';
      } else {
         $pri = ',';
      }

      if ($col['Key'] == 'MUL') {
         $mul = 'COLLATE utf8mb4_unicode_ci';
         $indx_key .= 'KEY `' . $col['Field'] . '` (`' . $col['Field'] . '`), ';
      } else {
         $mul = '';
         $indx_key .= '';
      }

      if ($key < count($cols) - 1) {
         $col_name .= '`' . $col['Field'] . '`, ';
      } else {
         $col_name .= '`' . $col['Field'] . '`';
      }

      $sql_table .= ('`' . $col['Field'] . '` ' . $col['Type'] . ' ' . $mul . ' ' . $null . $pri) . PHP_EOL;
   }

   if (strripos($sql_table, 'COLLATE') > 0) {
      $collate = 'COLLATE utf8mb4_unicode_ci';
   } else {
      $collate = '';
   }


   if (strlen($indx_key) > 0) {
      $sql_table .= 'PRIMARY KEY (`id`), ' . $indx_key . ') ENGINE=' . $stat[0]['Engine'] . ' AUTO_INCREMENT=' . $stat[0]['Auto_increment'] . ' DEFAULT CHARSET=utf8mb4 ' . $collate . ';' . PHP_EOL;
      $sql_table = str_replace(", ) ENGINE", " ) ENGINE", $sql_table);
   } else {
      $sql_table .= 'PRIMARY KEY (`id`) ) ENGINE=' . $stat[0]['Engine'] . ' AUTO_INCREMENT=' . $stat[0]['Auto_increment'] . ' DEFAULT CHARSET=utf8mb4 ' . $collate . ';' . PHP_EOL;
   }

   $indx_key = '';
   $value = '';

   $values = $dbh->query("SELECT * FROM " . $table[0] . "")->fetchAll(PDO::FETCH_NUM);
   foreach ($values as $value) {
      $val = implode("', '", $value);
      $sql_table .= "INSERT INTO `" . $table[0] . "` (" . $col_name . ") VALUES (" . $val . "); " . PHP_EOL;
   }

   $sql_table = str_replace("'NULL'", "NULL", $sql_table);
   echo '<pre>';
   print_r($sql_table);
   echo '</pre>';

   $col_name = '';

   $table_val = '';

   $filename = date("d_m_y") . '_' . $table[0];

   $fd = fopen($filename . ".sql", 'w') or die("не удалось создать файл");
   fwrite($fd, $sql_table);
   fclose($fd);
}





