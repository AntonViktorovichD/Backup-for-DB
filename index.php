<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

try {
   $dbh = new PDO('mysql:host=localhost; dbname=laravel; charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
   echo 'Выброшено исключение: ', $e->getMessage(), "\n";
   $err = 'Ошибка соединения с БД';
}

date_default_timezone_set('Europe/Moscow');

$tables = $dbh->query('SHOW TABLES')->fetchAll();

$sql_table = '';

$indx_key = '';

$prim_key = '';

$col_name = '';

$table_val = '';

$vals_arr = [];

try {

   foreach ($tables as $k => $table) {

      if ($table[0] == 'model_has_permissions' || $table[0] == 'model_has_roles' || $table[0] == 'personal_access_tokens' || $table[0] == 'role_has_permissions') {
         $stat = $dbh->query("SHOW TABLE STATUS FROM `laravel` WHERE `name` LIKE '" . $table[0] . "' ")->fetchAll(PDO::FETCH_ASSOC)[0];
         $cols = $dbh->query("SHOW FIELDS FROM " . $table[0] . "")->fetchAll(PDO::FETCH_ASSOC);
         $values = $dbh->query("SELECT * FROM " . $table[0] . "")->fetchAll(PDO::FETCH_NUM);
         $indxs = $dbh->query("SHOW INDEXES FROM " . $table[0] . "")->fetchAll(PDO::FETCH_ASSOC);
         $sql_table = 'CREATE TABLE IF NOT EXISTS `' . $table[0] . '` (' . PHP_EOL;

         for ($i = 0; $i < count($indxs); $i++) {
            if ($indxs[$i]['Key_name'] == 'PRIMARY') {
               $prim_key .= '`' . $indxs[$i]['Column_name'] . '`, ';
            } else {
               $prim_key .= '';
            }

            if ($indxs[$i]['Key_name'] != 'PRIMARY' && strlen($indxs[$i]['Key_name']) > 0) {
               if ($indxs[$i - 1]['Key_name'] != $indxs[$i]['Key_name']) {
                  $indx_key .= '`' . $indxs[$i]['Key_name'] . '` (`' . $indxs[$i]['Column_name'] . '`, ';
//               var_dump($indxs[$i]['Key_name']);
               } else {
                  $indx_key .= '`' . $indxs[$i]['Column_name'] . '`, ';
               }
            } else {
               $indx_key .= '';
            }
         }

         foreach ($cols as $key => $col) {

            if ($col['Null'] == 'NO') {
               $null = 'NOT NULL ';
            } else {
               $null = 'DEFAULT NULL';
            }

            if ($key < count($cols) - 1) {
               $col_name .= '`' . $col['Field'] . '`, ';
            } else {
               $col_name .= '`' . $col['Field'] . '`';
            }

            $sql_table .= ('`' . $col['Field'] . '` ' . $col['Type'] . ' ' . $null . ',') . PHP_EOL;
         }

         if (strripos($sql_table, 'COLLATE') > 0) {
            $collate = 'COLLATE utf8mb4_unicode_ci';
         } else {
            $collate = '';
         }

         if (strlen($prim_key) > 0) {
            $sql_table .= 'PRIMARY KEY (' . $prim_key . '), ';
            $sql_table = str_replace(", )", ")", $sql_table);
         }

         if (strlen($indx_key) > 0) {
            $sql_table .= 'KEY ' . $indx_key . ')) ENGINE=' . $stat['Engine'] . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;' . PHP_EOL;
            $sql_table = str_replace(", )", ")", $sql_table);
         }

         $prim_key = '';

         $indx_key = '';

         foreach ($values as $value) {
            if (array_search(null, $value)) {
               $value[array_search(null, $value)] = NULL;
            }
            foreach ($value as $key => $str) {
               $value[$key] = addslashes($str);
            }

            $val = str_replace("''", "NULL", implode("', '", $value));
            $sql_table .= "INSERT INTO `" . $table[0] . "` (" . $col_name . ") VALUES ('" . $val . "'); " . PHP_EOL;

         }
         $sql_table = str_replace("NULL');", "NULL);", $sql_table);
         $sql_table = str_replace("'');", "NULL);", $sql_table);

         $col_name = '';

         $table_val = '';
         unset($tables[$k]);

//      echo '<pre>';
//      print_r($sql_table);
//      echo '</pre>';

         $filename = date("d_m_y") . '_' . $table[0];
         $fd = fopen($filename . ".sql", 'w') or die("не удалось создать файл");
         fwrite($fd, $sql_table);
         fclose($fd);
      }
   }

   foreach ($tables as $k => $table) {

      $cnt = $dbh->query("SELECT COUNT(*) FROM " . $table[0] . "")->fetchAll(PDO::FETCH_NUM)[0][0];

      if ($cnt > 1000) {

         $indx_keys = ceil($cnt / 1000);
         for ($i = 0; $i < $indx_keys; $i++) {
            $values = $dbh->query("SELECT * FROM " . $table[0] . " LIMIT " . $i . "000, 1000")->fetchAll(PDO::FETCH_NUM);
            limiter($dbh, $table, $values, $indx_key, $col_name, $i);
         }
      } elseif ($cnt < 1000) {
         $values = $dbh->query("SELECT * FROM " . $table[0] . "")->fetchAll(PDO::FETCH_NUM);
         limiter($dbh, $table, $values, $indx_key, $col_name, 0);
      }
   }

} catch (Exception $e) {
   echo 'Выброшено исключение: ', $e->getMessage(), "\n";
   $err = 'Ошибка парсинга БД';
   send_mail($e, $err);
}

function limiter($dbh, $table, $values, $indx_key, $col_name, $i) {

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

   foreach ($values as $value) {
      if (array_search(null, $value)) {
         $value[array_search(null, $value)] = NULL;
      }
      foreach ($value as $key => $str) {
         $value[$key] = addslashes($str);
      }

      $val = str_replace("''", "NULL", implode("', '", $value));
      $sql_table .= "INSERT INTO `" . $table[0] . "` (" . $col_name . ") VALUES ('" . $val . "'); " . PHP_EOL;

   }
   $sql_table = str_replace("NULL');", "NULL);", $sql_table);
   $sql_table = str_replace("'');", "NULL);", $sql_table);

   $col_name = '';

   $table_val = '';

   $filename = date("d_m_y") . '_' . $table[0] . '_part' . $i;
   $fd = fopen($filename . ".sql", 'w') or die("не удалось создать файл");
   fwrite($fd, $sql_table);
   fclose($fd);
   clearstatcache();
}


try {
   $files = scandir($_SERVER['DOCUMENT_ROOT']);
   $zip = new ZipArchive();

   $zip_name = date("d_m_y") . '_backup.zip';

   if (!file_exists($zip_name)) {
      $zip->open($zip_name, ZipArchive::CREATE);
      foreach ($files as $file) {
         if (stristr($file, date("d_m_y"))) {
            $zip->addFile($file);
         }
      }
      $zip->close();
   }
} catch (Exception $e) {
   echo 'Выброшено исключение: ', $e->getMessage(), "\n";
   $err = 'Ошибка парсинга создания архива';
   send_mail($e, $err);
}

$zips = [];

foreach ($files as $file) {
   if (stristr($file, date("d_m_y")) && stristr($file, "sql")) {
      unlink($file);
   }
   if (stristr($file, "zip") && count($files) > 10) {
      $zips[] = filectime($file);
   }
}

sort($zips, SORT_NUMERIC);
if (stristr($file, "zip") && count($files) > 10) {
   for ($i = 9; $i < count($zips); $i++) {
      foreach ($files as $file) {
         if (filectime($file) == $zips[$i]) {
            unlink($file);
         }
      }
   }
}

function send_mail($e, $err) {

   require_once "vendor/autoload.php";

   $phpmailer = new PHPMailer();
   $phpmailer->CharSet = 'utf-8';
   $phpmailer->isSMTP();
   $phpmailer->Host = 'smtp.mailtrap.io';
   $phpmailer->SMTPAuth = true;
   $phpmailer->Port = 2525;
   $phpmailer->Username = 'e1fc95cd066969';
   $phpmailer->Password = '3632b106d20e2e';
   $phpmailer->From = "from@yourdomain.com";
   $phpmailer->FromName = "Backup error";
   $phpmailer->addAddress('@mail');
   $phpmailer->addReplyTo("monitoring@minsocium.ru", 'Информационно-аналитический сервис "Автоматизированный сбор показателей работы социальных учреждений Нижегородской области"');
   $phpmailer->isHTML(true);
   $phpmailer->Subject = "Backup error:" . $err;
   $phpmailer->Body = '<html><body>Выброшено исключение: ' . $e->getMessage() . "\n" . '</body></html>';
   try {
      $phpmailer->send();
      echo "Message has been sent successfully";
   } catch (Exception $e) {
      echo "Mailer Error: " . $phpmailer->ErrorInfo;
      print_r(error_get_last());
   }

   //   $to = $email;
//   $subject = "Backup error:" . $err;
//   $mail_message = '<html><body>Выброшено исключение: ' . $e->getMessage() . "\n" . '</body></html>';
//   $headers = 'MIME-Version: 1.0' . "\r\n";
//   $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
//   $sender = "Backup error";
//   $headers .= 'From: ' . $sender . ' ' . "\r\n";
//   mail($to, $subject, $mail_message, $headers);
}