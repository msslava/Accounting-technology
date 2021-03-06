<?php
include_once("config.php");
define("DIR_ROOT", __DIR__);
define("DS", DIRECTORY_SEPARATOR);
include_once('sys/Parsedown.php');
include_once('sys/class.phpmailer.php');
require 'library/HTMLPurifier.auto.php';
$dbConnection = new PDO(
    'mysql:host='.$CONF_DB['host'].';dbname='.$CONF_DB['db_name'],
    $CONF_DB['username'],
    $CONF_DB['password'],
    array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
);
$dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$CONF = array (
'title_header'	=> get_conf_param('title_header'),
'hostname'	=> get_conf_param('hostname'),
'mail'	=> get_conf_param('mail'),
'name_of_firm'	=> get_conf_param('name_of_firm'),
'fix_subj'	=> get_conf_param('fix_subj'),
'first_login'	=> get_conf_param('first_login'),
'file_types' => get_conf_param('file_types'),
'file_types_img' => get_conf_param('file_types'),
'file_size' => get_conf_param('file_size'),
'permit_users_knt' => get_conf_param('permit_users_knt'),
'permit_users_req' => get_conf_param('permit_users_req'),
'permit_users_cont' => get_conf_param('permit_users_cont'),
'permit_users_documents' => get_conf_param('permit_users_documents'),
'permit_users_news' => get_conf_param('permit_users_news'),
'permit_users_license' => get_conf_param('permit_users_license'),
'default_org' => get_conf_param('default_org'),
'what_cartridge' => get_conf_param('what_cartridge'),
'what_print_test' => get_conf_param('what_print_test'),
'what_license' => get_conf_param('what_license'),
'home_text' => get_conf_param('home_text'),
'time_zone' => get_conf_param('time_zone')
);

$CONF_MAIL = array (
'active'	=> get_conf_param('mail_active'),
'host'	=> get_conf_param('mail_host'),
'port'	=> get_conf_param('mail_port'),
'auth'	=> get_conf_param('mail_auth'),
'auth_type' => get_conf_param('mail_auth_type'),
'username'	=> get_conf_param('mail_username'),
'password'	=> get_conf_param('mail_password'),
'from'	=> get_conf_param('mail_from'),
'debug' => 'false'
);

if ($CONF_AT['debug_mode'] == true) {
error_reporting(E_ALL ^ E_NOTICE);
error_reporting(0);
}
date_default_timezone_set(get_conf_param('time_zone'));

include_once('inc/mail.php');

function get_version(){
  $v = '1.09';
  return $v;
}

function get_user_lang(){
    global $dbConnection;


    $mid=$_SESSION['dilema_user_id'];
    $stmt = $dbConnection->prepare('SELECT lang from users where id=:mid');
    $stmt->execute(array(':mid' => $mid));
    $max = $stmt->fetch(PDO::FETCH_NUM);

    $max_id=$max[0];
    $length = strlen(utf8_decode($max_id));
    if (($length < 1) || $max_id == "0") {$ress='ru';} else {$ress=$max_id;}
    return $ress;
}

function get_lang($in){

  $lang2 = get_user_lang();
  switch ($lang2) {
      case 'ru':
          $lang_file2 = (DIR_ROOT . DS . "lang" . DS ."lang-ru.json");
          break;

      case 'en':
          $lang_file2 = (DIR_ROOT . DS . "lang" . DS ."lang-en.json");
          break;

      default:
          $lang_file2 = (DIR_ROOT . DS . "lang" . DS ."lang-ru.json");

  }
  $file = file_get_contents($lang_file2);
  $json = json_decode($file);
  if (isset($json->$in)){
  return $json->$in;
  }else {
  return 'undefined';
}
}
function make_html($in, $type) {



 $Parsedown = new Parsedown();
 $text=$Parsedown->text($in);

$text=str_replace("\n", "<br />", $text);
$config = HTMLPurifier_Config::createDefault();



$config->set('Core.Encoding', 'UTF-8');
$config->set('HTML.Doctype', 'XHTML 1.0 Transitional');
$config->set('Cache.DefinitionImpl', null);
$config->set('AutoFormat.RemoveEmpty',false);
$config->set('AutoFormat.AutoParagraph',true);
//$config->set('URI.DisableExternal', true);
if ($type == "no") {
$config->set('HTML.ForbiddenElements', array( 'p' ) );
}

$purifier = new HTMLPurifier($config);
$def = $config->getHTMLDefinition(true);
$def->addElement('ul', 'List', 'Optional: List | li', 'Common', array());
$def->addElement('ol', 'List', 'Optional: List | li', 'Common', array());
// here, the javascript command is stripped off
$content = $purifier->purify($text);

return $content;

}
function GetRandomId($in) // результат - случайная строка из цифр длинной n
{
  $id="";
  for ($i = 1; $i <= $in; $i++)
  {
    $id=$id.chr(rand(48,56));
  }
    return $id;
}
function UpdateLastdt($in){ // обновляем данные о последнем посещении
		global $dbConnection;
		$lastdt=date( 'Y-m-d H:i:s');
  		$stmt = $dbConnection->prepare ("UPDATE users SET lastdt=:lastdt WHERE id=:in");
      $stmt->execute(array(':in' => $in, ':lastdt' => $lastdt));
    }
function get_conf_param($in) {
 global $dbConnection;
 $stmt = $dbConnection->prepare('SELECT value FROM perf where param=:in');
 $stmt->execute(array(':in' => $in));
 $row = $stmt->fetch(PDO::FETCH_ASSOC);

return $row['value'];

}
function cutstr_news2_ret($input) {

    $result = implode(array_slice(explode('<br>',wordwrap($input,50,'<br>',false)),0,1));
    $r=$result;
    if($result!=$input)$r.='...';
    return $r;
}
function get_user_status($in) {
	    global $dbConnection;

    $stmt = $dbConnection->prepare('select lastdt from users where id=:in and us_kill=1');
    $stmt->execute(array(':in' => $in));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
	$lt=$row['lastdt'];
        $d = time()-strtotime($lt);
	if ($d > 20) {
	$lt_tooltip="";
	if ($lt != '0000-00-00 00:00:00') {$lt_tooltip=get_lang('stats_last_time')."<br>".MySQLDateTimeToDateTime($lt);}
  else{$lt_tooltip=get_lang('stats_last_time')."<br>".get_lang('login_never');}
  $res="<span data-toggle=\"tooltip\" data-placement=\"bottom\" class=\"label label-default margin\" data-original-title=\"".$lt_tooltip."\" data-html=\"true\"><i class=\"fa fa-thumbs-down\" aria-hidden=\"true\"></i> offline</span>";}
	else {$res="<span class=\"label label-success margin\"><i class=\"fa fa-thumbs-up\" aria-hidden=\"true\"></i> online</span>";}

	return $res;
}

function update_val_by_key($key,$val) {
 global $dbConnection;
$stmt = $dbConnection->prepare('update perf set value=:value where param=:param');
$stmt->execute(array(':value' => $val,':param' => $key));
return true;

}
function validate_alphanumeric_underscore($str)
{
    return preg_match('/^[a-zA-Z0-9_\.-]+$/',$str);
}
function validate_email($str)
{
    return preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/',$str);
}
function validate_exist_mail($str) {
    global $dbConnection;
    $uid=$_SESSION['dilema_user_id'];
    $email_all = "no-email@holding.lan.zt";

    $stmt = $dbConnection->prepare('SELECT count(email) as n from users where email=:str and id != :uid and email != :email_all');
    $stmt->execute(array(':str' => $str,':uid' => $uid, ':email_all' => $email_all));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row['n'] > 0) {$r=false;}
    else if ($row['n'] == 0) {$r=true;}

    return $r;
}
function nameshort($name) {
    $nameshort = preg_replace('/(\w+) (\w)\w+ (\w)\w+/iu', '$1 $2. $3.', $name);
    return $nameshort;
}
function cutstr_news_ret($input) {

    $result = implode(array_slice(explode('<br>',wordwrap($input,500,'<br>',false)),0,1));
    $r=$result;
    if($result!=$input)$r.='...';
    return $r;
}
function cutstr_news_home_ret($input) {

    $result = implode(array_slice(explode('<br>',wordwrap($input,300,'<br>',false)),0,1));
    $r=$result;
    if($result!=$input)$r.='...';
    return $r;
}

function randomPassword() {
    $alphabet = "abcdefghijklmnopqrstuwxyz0123456789";
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < 5; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass);
}

function get_news(){
  global $dbConnection;

  $stmt = $dbConnection->prepare('SELECT
        id, user_init_id, title, dt, message, hashname
        from news
        order by dt desc
        limit 1');
  $stmt->execute();
  $result = $stmt->fetchAll();
  ?>
  <table class="table" style="margin-bottom: 0px;" id="">
      <?php

      if (empty($result)) {
          ?>
          <div id="" class="well well-large well-transparent lead">
              <center>
                  <?= get_lang('News_no_records'); ?>
              </center>
          </div>
      <?php
      } else if (!empty($result)) {
          foreach ($result as $row) {
            $dt = $row['dt'];
            $stmt = $dbConnection->prepare('SELECT
                  count(*) as count, dt
                  from news
                  where dt < :dt_r');
            $stmt->execute(array(':dt_r' => $row['dt']));
            $row2 = $stmt->fetch(PDO::FETCH_ASSOC);
            $dt = $row['dt'];
            $count = $row2['count'];
            if ($count == 0){
            ?>
            <input type="hidden" id="previous_d" value="disabled">
            <?php
          }
                  ?>
                  <tr><td><small><i class="fa fa-file-text-o" aria-hidden="true"></i> </small><a href="news?h=<?= $row['hashname']; ?>"><small><?= cutstr_news2_ret($row['title']); ?></small></a></td><td><small style="float:right;" class="text-muted">(<?= get_lang('News_author'); ?>: <?= nameshort(name_of_user_ret($row['user_init_id'])); ?>)<br>(<?= get_lang('News_date'); ?>: <?= ($row['dt']); ?>)</small></td></tr>
                  <tr>
                  <td colspan="2"><small><i class="fa fa-file-text-o" aria-hidden="true"></i> </small><small><?= cutstr_news_home_ret(strip_tags($row['message'])); ?></small></td>
                  </tr>
                  <input type="hidden" id="news_dt" value="<?php echo $dt; ?>">
              <?php
              }
      }
      ?>
  </table>
  <?php
}
// на выходе - массив из папок в укзанной папке
function GetArrayFilesInDir($dir)
{
	$includes_dir = opendir("$dir");
	$files = array();
	while (($inc_file = readdir($includes_dir)) != false) {
        if (($inc_file!='.') and ($inc_file!='..')) {
            $files[] = $inc_file;
        }
    }
    closedir($includes_dir);
    sort($files);
    return $files;
}

// Преобразует дату типа dd.mm.2012 в формат MySQL 2012-01-01 00:00:00
function DateToMySQLDateTime2($dt)
{
   $str_exp = explode(".", $dt);
   $str_exp2 = explode(" ", $str_exp[2]);
   $dtt=$str_exp2[0]."-".$str_exp[1]."-".$str_exp[0]." ".$str_exp2[1].":00";
   return $dtt;
};
// Преобразует дату типа dd.mm.2012 в формат MySQL 2012-01-01 00:00:00
function DateToMySQLDateTimeCalStart($dt)
{
   $str_exp = explode(".", $dt);
   $str_exp2 = explode(" ", $str_exp[2]);
   $dtt=$str_exp2[0]."-".$str_exp[1]."-".$str_exp[0]." ".$str_exp2[1]."00:00:00";
   return $dtt;
};
// Преобразует дату типа dd.mm.2012 в формат MySQL 2012-01-01 23:59:00
function DateToMySQLDateTimeCalEnd($dt)
{
   $str_exp = explode(".", $dt);
   $str_exp2 = explode(" ", $str_exp[2]);
   $dtt=$str_exp2[0]."-".$str_exp[1]."-".$str_exp[0]." ".$str_exp2[1]."23:59:00";
   return $dtt;
};
//День рождение
function DateToMySQLDateBirthday($dt)
{
   $str_exp = explode(".", $dt);
   $dtt=$str_exp[1]."-".$str_exp[0];

   return $dtt;
};

// Преобразует дату MySQL 2012-01-01 00:00:00 в dd.mm.2012 00:00
function MySQLDateTimeToDateTime($dt)
{

   $str1 = explode("-", $dt);
   $str2 = explode(" ", $str1[2]);
   $str3 = explode(":", $str2[1]);
   $dtt=$str2[0].".".$str1[1].".".$str1[0]." ".$str3[0].":".$str3[1];
   return $dtt;
};
// Преобразует дату MySQL 2012-01-01 00:00:00 в mm-dd 00:00:00
function MySQLYearDateTimeToDateTime($dt)
{

   $str1 = explode("-", $dt);
   $dtt=$str1[1]."-".$str1[2];
   return $dtt;
};

// Преобразует дату MySQL 2012-01-01 в dd.mm.YYYY
function MySQLDateToDate($dt)
{

   $str1 = explode("-", $dt);
   $dtt=$str1[2].".".$str1[1].".".$str1[0];
   return $dtt;
};
// Преобразует дату MySQL 2012-01-01 00:00:00 в dd.mm.YYYY
function MySQLDateTimeToDateCal($dt)
{
   $str = explode(" ",$dt);
   $str1 = explode("-", $str[0]);
   $dtt=$str1[2].".".$str1[1].".".$str1[0];
   return $dtt;
};
// Преобразует дату MySQL 2012-01-01 00:00:00 в mm.YYYY
function MySQLDateTimeToDateRemindMonth($dt)
{
   $str = explode(" ",$dt);
   $str1 = explode("-", $str[0]);
   $dtt=$str1[1].".".$str1[0];
   return $dtt;
};
// Преобразует дату MySQL 2012-01-01 00:00:00 в mm
function MySQLDateTimeToDateMonth($dt)
{
   $str = explode(" ",$dt);
   $str1 = explode("-", $str[0]);
   $dtt=$str1[1];
   return $dtt;
};
// Преобразует дату MySQL 2012-01-01 00:00:00 в dd
function MySQLDateTimeToDateDay($dt)
{
   $str = explode(" ",$dt);
   $str1 = explode("-", $str[0]);
   $dtt=$str1[2];
   return $dtt;
};
// Преобразует дату MySQL 2012-01-01 00:00:00 в YYYY
function MySQLDateTimeToDateYear($dt)
{
   $str = explode(" ",$dt);
   $str1 = explode("-", $str[0]);
   $dtt=$str1[0];
   return $dtt;
};
// Преобразует дату MySQL 2012-01-01 00:00:00 в 2012-01-01
function MySQLDateTimeToDateNoTime($dt)
{
   $str = explode(" ",$dt);
   $str1 = explode("-", $str[0]);
   $dtt=$str1[0]."-".$str1[1]."-".$str1[2];
   return $dtt;
};
// Преобразует дату MySQL 2012-01-01 в mm.YYYY
function MySQLDateToMonth($dt)
{
   $str1 = explode("-", $dt);
   $dtt=$str1[1].".".$str1[0];
   return $dtt;
};
// Преобразует дату MySQL 2012-01-01 00:00:00 в dd.mm.YYYY
function MySQLDateTimeToDateTimeNoTime($dt)
{

   $str1 = explode("-", $dt);
   $str2 = explode(" ", $str1[2]);
   $dtt=$str2[0].".".$str1[1].".".$str1[0];
  //         echo "!$dtt!";
   return $dtt;
};
//Возврат кол-ва дней
function count_week_days($__date_from, $__date_to) {
   $total_days_count = $__date_to > $__date_from ? round(($__date_to - $__date_from)/(24*3600)) : 0;
   return $total_days_count;
}

function name_of_user_ret($input) {
    global $dbConnection;


    $stmt = $dbConnection->prepare('SELECT fio FROM users where id=:input');
    $stmt->execute(array(':input' => $input));
    $fio = $stmt->fetch(PDO::FETCH_ASSOC);


    return($fio['fio']);
}
function randomhash() {
    $alphabet = "abcdefghijklmnopqrstuwxyz0123456789";
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < 24; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass);
}
function validate_priv($user_id) {
    global $dbConnection;

    $stmt = $dbConnection->prepare('SELECT priv from users where id=:user_id LIMIT 1');
    $stmt->execute(array(':user_id' => $user_id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $priv=$row['priv'];

    // if ($admin == "1") {return true;}
    // else {return false;}
    return $priv;

}
function validate_menu($user_id) {
    global $dbConnection;

    $stmt = $dbConnection->prepare('SELECT permit_menu from users where id=:user_id LIMIT 1');
    $stmt->execute(array(':user_id' => $user_id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $permit=$row['permit_menu'];

    return $permit;

}

function validate_menu_lang($user_id) {
    global $dbConnection;
    $menu_lang = array();
    $stmt = $dbConnection->prepare('SELECT permit_menu from users where id=:user_id LIMIT 1');
    $stmt->execute(array(':user_id' => $user_id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $menu=$row['permit_menu'];
    $menu = explode(",", $menu);
    foreach ($menu as $key)  {
      switch ($key) {
        case '1-1':
        $menu_lang[] = get_lang('Menu_reports');
        break;
        case '1-2':
        $menu_lang[] = get_lang('Menu_invoice');
        break;
        case '1-3':
        $menu_lang[] = get_lang('Menu_history_moving');
        break;
        case '1-4':
        $menu_lang[] = get_lang('Menu_cartridge');
        break;
        case '1-5':
        $menu_lang[] = get_lang('Menu_license');
        break;
        case '1-6':
        $menu_lang[] = get_lang('Menu_equipment');
        break;
        case '2-1':
        $menu_lang[] = get_lang('Menu_ping');
        break;
        case '2-2':
        $menu_lang[] = get_lang('Menu_printer');
        break;
        case '3-1':
        $menu_lang[] = get_lang('Menu_news');
        break;
        case '3-2':
        $menu_lang[] = get_lang('Menu_eqlist');
        break;
        case '3-3':
        $menu_lang[] = get_lang('Menu_requisites');
        break;
        case '3-4':
        $menu_lang[] = get_lang('Menu_knt');
        break;
        case '3-5':
        $menu_lang[] = get_lang('Menu_documents');
        break;
        case '3-6':
        $menu_lang[] = get_lang('Menu_contact');
        break;
        case '3-7':
        $menu_lang[] = get_lang('Menu_calendar');
        break;
      }
    }

    return implode("<br>",$menu_lang);

}
function ProgrammingNameReturn($id) {
    global $dbConnection;
    $name_po = array();
    $id = explode(",", $id);
    foreach ($id as $key) {

    $stmt = $dbConnection->prepare('SELECT name from programming where id=:id LIMIT 1');
    $stmt->execute(array(':id' => $key));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $name_po[]=$row['name'];
  }
    return implode(", ",$name_po);

}
function validate_user($user_id, $input) {

    global $dbConnection;

    if (!isset($_SESSION['us_code'])) {

        if (isset($_COOKIE['authhash_uscode'])) {

            $user_id=$_COOKIE['authhash_usid'];
            $input=$_COOKIE['authhash_uscode'];
            $_SESSION['us_code']=$input;
            $_SESSION['dilema_user_id']=$user_id;
            $_SESSION['dilema_org'] = get_conf_param('default_org');
            $_SESSION['dilema_date'] = date('Y-m-d');

        }


    }


    $stmt = $dbConnection->prepare('SELECT pass,login,fio from users where id=:user_id LIMIT 1');
    $stmt->execute(array(':user_id' => $user_id));


    if ($stmt -> rowCount() == 1) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $dbpass=$row['pass'];
        $_SESSION['dilema_user_login'] = $row['login'];
        $_SESSION['dilema_user_fio'] = $row['fio'];

        if ($dbpass == $input) {return true;}
        else { return false;}
    }
}
function get_myname(){
    $uid=$_SESSION['dilema_user_id'];
    $nu=name_of_user_ret($uid);
    $length = strlen(utf8_decode($nu));

    if ($length > 2) {$n=explode(" ", name_of_user_ret($uid)); $t=$n[1]." ".$n[2];}
    else if ($length <= 2) {$t="";}
    //$n=explode(" ", name_of_user_ret($uid));
    return $t;
}
function get_avatar(){
  global $dbConnection;
  $uid=$_SESSION['dilema_user_id'];
  $stmt = $dbConnection->prepare('SELECT jpegphoto from users_profile where usersid=:user_id');
  $stmt->execute(array(':user_id' => $uid));
  $ava = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($ava['jpegphoto'] != ''){return ($ava['jpegphoto']."?".time());}
  else {return ('noavatar.png?'.time());}
}
function validate_exist_login($str) {
global $dbConnection;
$uid=$_SESSION['dilema_user_id'];

$stmt = $dbConnection->prepare('SELECT count(login) as n from users where login=:str');
$stmt->execute(array(':str' => $str));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row['n'] > 0) {$r=false;}
else if ($row['n'] == 0) {$r=true;}

return $r;
}
function validate_exist_programming($str) {
global $dbConnection;

$stmt = $dbConnection->prepare('SELECT count(name) as n from programming where name=:str');
$stmt->execute(array(':str' => $str));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row['n'] > 0) {$r=false;}
else if ($row['n'] == 0) {$r=true;}

return $r;
}
function validate_exist_user_name($str) {
global $dbConnection;
$uid=$_SESSION['dilema_user_id'];

$stmt = $dbConnection->prepare('SELECT count(user_name) as un from users where user_name=:str');
$stmt->execute(array(':str' => $str));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row['un'] > 0) {$r=false;}
else if ($row['un'] == 0) {$r=true;}

return $r;
}
function validate_login_equipment($str) {
global $dbConnection;

$stmt = $dbConnection->prepare('SELECT count(usersid) as u from equipment where usersid=:str and util=0 and sale=0');
$stmt->execute(array(':str' => $str));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row['u'] > 0) {$u=false;}
else if ($row['u'] == 0) {$u=true;}

return $u;
}
function GetArrayKnt(){ // Возврат - массив активных контрагентов
		global $dbConnection;
		$cnt=0;
		$mOrgs = array();
  		$stmt = $dbConnection->prepare('SELECT * FROM knt WHERE active=1 order by name');
      $stmt->execute();
      $res1 = $stmt->fetchAll();
  		if ($res1!='') {
        foreach($res1 as $myrow) {
				   $mOrgs[$cnt]["id"]=$myrow["id"];
				   $mOrgs[$cnt]["name"]=$myrow["name"];
                                   $mOrgs[$cnt]["active"]=$myrow["active"];
				   $cnt++;
				  };
				return $mOrgs;
                    }
};

function GetArrayOrg(){ // Возврат - массив активных организаций
		global $dbConnection;
		$cnt=0;
		$mOrgs = array();
  		$stmt = $dbConnection->prepare('SELECT * FROM org WHERE active=1 order by name');
      $stmt->execute();
      $res1 = $stmt->fetchAll();
  		if ($res1!='') {
        foreach($res1 as $myrow) {
				   $mOrgs[$cnt]["id"]=$myrow["id"];
				   $mOrgs[$cnt]["name"]=$myrow["name"];
                                   $mOrgs[$cnt]["active"]=$myrow["active"];
				   $cnt++;
				  };
				return $mOrgs;
                    }
};

function GetArrayPrint(){ // Возврат - массив принтеров
		global $dbConnection;
		$cnt=0;
    $cartridge = get_conf_param('what_cartridge');
		$mOrgs = array();
    $stmt= $dbConnection->prepare("SELECT nome.name as name, nome.id as id FROM nome LEFT JOIN equipment ON equipment.nomeid = nome.id INNER JOIN print ON print.nomeid=nome.id WHERE nome.active=1 and print.active=1 and nome.groupid IN (".$cartridge.") and equipment.util=0 and equipment.sale=0 group by nome.name order by nome.name;");
      $stmt->execute();
      $res1 = $stmt->fetchAll();
  		if ($res1!='') {
        foreach($res1 as $myrow) {
				   $mOrgs[$cnt]["id"]=$myrow["id"];
				   $mOrgs[$cnt]["name"]=$myrow["name"];
                                   $mOrgs[$cnt]["active"]=$myrow["active"];
				   $cnt++;
				  };
				return $mOrgs;
                    }
};

function GetArrayPlaces(){ // Возврат - массив активных помещений
		global $dbConnection;
		$cnt=0;
		$mOrgs = array();
  		$stmt = $dbConnection->prepare('SELECT * FROM places WHERE active=1 order by name');
      $stmt->execute();
      $res1 = $stmt->fetchAll();
  		if ($res1!='') {
        foreach($res1 as $myrow) {
				   $mOrgs[$cnt]["id"]=$myrow["id"];
				   $mOrgs[$cnt]["name"]=$myrow["name"];
                                   $mOrgs[$cnt]["active"]=$myrow["active"];
				   $cnt++;
				  };
				return $mOrgs;
                    }
};

function GetArrayUsers(){ // Возврат - массив активных пользователей
		global $dbConnection;
		$cnt=0;
		$mOrgs = array();
  		$stmt = $dbConnection->prepare('SELECT * FROM users WHERE active=1 and on_off=1 order by fio');
      $stmt->execute();
      $res1 = $stmt->fetchAll();
  		if ($res1!='') {
        foreach($res1 as $myrow) {
				   $mOrgs[$cnt]["id"]=$myrow["id"];
				   $mOrgs[$cnt]["fio"]= nameshort($myrow["fio"]);
                                   $mOrgs[$cnt]["active"]=$myrow["active"];
				   $cnt++;
				  };
				return $mOrgs;
                    }
};

function GetArrayUsersOnline(){ // Возврат - массив пользователей online
		global $dbConnection;
    $id_user = $_SESSION['dilema_user_id'];
		$mOrgs = array();
  		$stmt = $dbConnection->prepare('SELECT * FROM users WHERE active=1 and on_off=1 and us_kill=1');
      $stmt->execute();
      $res1 = $stmt->fetchAll();
  		if ($res1!='') {
        foreach($res1 as $myrow) {
          $lt=$myrow['lastdt'];
                $d = time()-strtotime($lt);
          if ($d < 20) {
				   $mOrgs[]=$myrow["id"];
          }
				  };
          $us_me = array_search($id_user,$mOrgs);
          if ($us_me !== FALSE){
            unset($mOrgs[$us_me]);
            $us_dd = $mOrgs;
          }
          else{
            $us_dd = $mOrgs;
          }

				return $us_dd;
                    }
};

function GetArrayUsers_Ping_Test(){ // Возврат - массив активных пользователей
		global $dbConnection;
		$cnt=0;
    $what_print_test = get_conf_param('what_print_test');
		$mOrgs = array();
  		$stmt = $dbConnection->prepare("SELECT users.id as id, users.fio as fio, users.active as active FROM users INNER JOIN equipment ON equipment.usersid = users.id WHERE users.active = 1 and users.on_off = 1 and equipment.active=1 and equipment.util=0 and equipment.sale=0 and equipment.ip<>'' group by users.fio order by users.fio");
      $stmt->execute();
      $res1 = $stmt->fetchAll();
  		if ($res1!='') {
        foreach($res1 as $myrow) {
				   $mOrgs[$cnt]["id"]=$myrow["id"];
				   $mOrgs[$cnt]["fio"]= nameshort($myrow["fio"]);
                                   $mOrgs[$cnt]["active"]=$myrow["active"];
				   $cnt++;
				  };
				return $mOrgs;
                    }
};

function GetArrayUsers_Print_Test(){ // Возврат - массив активных пользователей
		global $dbConnection;
		$cnt=0;
    $what_print_test = get_conf_param('what_print_test');
		$mOrgs = array();
  		$stmt = $dbConnection->prepare("SELECT users.id as id, users.fio as fio, users.active as active FROM users INNER JOIN equipment ON equipment.usersid = users.id INNER JOIN nome ON nome.id = equipment.nomeid WHERE users.active = 1 and users.on_off = 1 and nome.groupid IN (".$what_print_test.") and equipment.active=1 and equipment.util=0 and equipment.sale=0 and equipment.ip<>'' group by users.fio order by users.fio");
      $stmt->execute();
      $res1 = $stmt->fetchAll();
  		if ($res1!='') {
        foreach($res1 as $myrow) {
				   $mOrgs[$cnt]["id"]=$myrow["id"];
				   $mOrgs[$cnt]["fio"]= nameshort($myrow["fio"]);
                                   $mOrgs[$cnt]["active"]=$myrow["active"];
				   $cnt++;
				  };
				return $mOrgs;
                    }
};

function GetArrayPlaces_Ping_Test(){ // Возврат - массив активных помещений
		global $dbConnection;
		$cnt=0;
    $what_print_test = get_conf_param('what_print_test');
		$mOrgs = array();
  		$stmt = $dbConnection->prepare("SELECT places.id as id, places.name as name, places.active as active FROM places INNER JOIN equipment ON equipment.placesid = places.id WHERE places.active=1 and equipment.active=1 and equipment.util=0 and equipment.sale=0 and equipment.ip<>'' group by places.name order by places.name");
      $stmt->execute();
      $res1 = $stmt->fetchAll();
  		if ($res1!='') {
        foreach($res1 as $myrow) {
				   $mOrgs[$cnt]["id"]=$myrow["id"];
				   $mOrgs[$cnt]["name"]=$myrow["name"];
                                   $mOrgs[$cnt]["active"]=$myrow["active"];
				   $cnt++;
				  };
				return $mOrgs;
                    }
};

function GetArrayPlaces_Print_Test(){ // Возврат - массив активных помещений
		global $dbConnection;
		$cnt=0;
    $what_print_test = get_conf_param('what_print_test');
		$mOrgs = array();
  		$stmt = $dbConnection->prepare("SELECT places.id as id, places.name as name, places.active as active FROM places INNER JOIN equipment ON equipment.placesid = places.id INNER JOIN nome ON nome.id = equipment.nomeid  WHERE places.active=1 and nome.groupid IN (".$what_print_test.") and equipment.active=1 and equipment.util=0 and equipment.sale=0 and equipment.ip<>'' group by places.name order by places.name");
      $stmt->execute();
      $res1 = $stmt->fetchAll();
  		if ($res1!='') {
        foreach($res1 as $myrow) {
				   $mOrgs[$cnt]["id"]=$myrow["id"];
				   $mOrgs[$cnt]["name"]=$myrow["name"];
                                   $mOrgs[$cnt]["active"]=$myrow["active"];
				   $cnt++;
				  };
				return $mOrgs;
                    }
};
function GetArrayProgramming($gr){ // Возврат - массив активного программного обеспечения
  global $dbConnection;
  $cnt=0;
  $mOrgs = array();
    $stmt = $dbConnection->prepare('SELECT * FROM programming WHERE active=1 and groupid=:gr order by name');
    $stmt->execute(array(':gr' => $gr));
    $res1 = $stmt->fetchAll();
    if ($res1!='') {
      foreach($res1 as $myrow) {
         $mOrgs[$cnt]["id"]=$myrow["id"];
         $mOrgs[$cnt]["name"]=$myrow["name"];
                      $mOrgs[$cnt]["active"]=$myrow["active"];
         $cnt++;
        };
      return $mOrgs;
                  }
};
function GetArrayGroup(){ // Возврат - массив групп номенклатуры..
global $dbConnection;
$cnt=0;
$mOrgs = array();
 $stmt = $dbConnection->prepare('SELECT * FROM group_nome WHERE active=1 ORDER BY name');
 $stmt->execute();
 $res1 = $stmt->fetchAll();
 foreach($res1 as $myrow) {
  $mOrgs[$cnt]["id"]=$myrow["id"];
   $mOrgs[$cnt]["name"]=$myrow["name"];
                                   $mOrgs[$cnt]["active"]=$myrow["active"];
  $cnt++;
 };
return $mOrgs;

};

function GetArrayVendor(){ // Возврат - массив производителей..
global $dbConnection;
$cnt=0;
$mOrgs = array();
 $stmt = $dbConnection->prepare('SELECT * FROM vendor WHERE active=1 ORDER BY name');
 $stmt->execute();
 $res1 = $stmt->fetchAll();
 foreach($res1 as $myrow) {
  $mOrgs[$cnt]["id"]=$myrow["id"];
   $mOrgs[$cnt]["name"]=$myrow["name"];
                                   $mOrgs[$cnt]["active"]=$myrow["active"];
  $cnt++;
 };
return $mOrgs;

};

function GetArrayGroup_vendor($in){ // Возврат - массив производителей по группе..
echo "Vendor\n";
echo "ID=".var_dump($in)."\n";
global $dbConnection;
$cnt=0;
$mOrgs = array();
$stmt = $dbConnection->prepare('SELECT gr.*, ven.* FROM group_nome as gr INNER JOIN nome as nom ON gr.id=nom.groupid INNER JOIN vendor as ven ON nom.vendorid=ven.id WHERE gr.id=:in and ven.active=1 group by ven.name order by ven.name');
$stmt->execute(array(':in' => $in));
$res1 = $stmt->fetchAll();
foreach($res1 as $myrow) {

   $mOrgs[$cnt]["id"]=$myrow["id"];
   $mOrgs[$cnt]["name"]=$myrow["name"];
                                   $mOrgs[$cnt]["active"]=$myrow["active"];
   $cnt++;
  };
return $mOrgs;
};

function GetArrayVendor_nome($in,$in2){ // Возврат - массив вендер и группы..
echo "Nome\n";
echo "ID=".var_dump($in,$in2)."\n";
global $dbConnection;
$cnt=0;
$mOrgs = array();
$stmt = $dbConnection->prepare('SELECT gr.*, nom.* FROM nome as nom INNER JOIN group_nome as gr ON gr.id=nom.groupid INNER JOIN vendor as ven ON nom.vendorid =ven.id WHERE gr.id = :in and ven.id = :in2  and nom.active=1 order by nom.name');
$stmt->execute(array(':in' => $in, ':in2' => $in2));
$res1 = $stmt->fetchAll();
foreach($res1 as $myrow) {
   $mOrgs[$cnt]["id"]=$myrow["id"];
   $mOrgs[$cnt]["name"]=$myrow["name"];
                                   $mOrgs[$cnt]["active"]=$myrow["active"];
   $cnt++;
  };
return $mOrgs;
};
function GetArrayNome_users($in){ // Возврат - массив тмц лицензирования
		echo "INVOICE\n";
		echo "ID=".var_dump($in)."\n";
    global $dbConnection;
		$cnt=0;
		$mOrgs = array();
    $wlicense = get_conf_param('what_license');
		$stmt = $dbConnection->prepare("SELECT nome.name as name, nome.id as nomeid, equipment.id as id FROM equipment INNER JOIN nome ON equipment.nomeid = nome.id WHERE equipment.usersid = :in and nome.groupid IN (".$wlicense.") and equipment.util=0 and equipment.sale=0 order by nome.name");
    $stmt->execute(array(':in' => $in));
    $res1 = $stmt->fetchAll();
    foreach($res1 as $myrow) {
				   $mOrgs[$cnt]["id"]=$myrow["id"];
				   $mOrgs[$cnt]["name"]=$myrow["name"];
				   $cnt++;
				  };
				return $mOrgs;

};
function lang_delete($in){
  switch ($in) {
    case 'org':
      $name = get_lang('Menu_org');
      break;
      case 'equipment':
        $name = get_lang('Menu_equipment');
        break;
        case 'print':
          $name = get_lang('Menu_cartridge');
          break;
          case 'print_param':
            $name = get_lang('Print_param');
            break;
            case 'eq_param':
              $name = get_lang('Equipment_param');
              break;
              case 'move':
                $name = get_lang('Equipment_move');
                break;
                case 'repair':
                  $name = get_lang('Equipment_repair_title');
                  break;
                  case 'shtr':
                    $name = get_lang('Shtr');
                    break;
                    case 'places':
                      $name = get_lang('Menu_places');
                      break;
                      case 'places_users':
                        $name = get_lang('Places_users');
                        break;
                        case 'users':
                          $name = get_lang('Menu_users');
                          break;
                          case 'users_profile':
                            $name = get_lang('Users_profile');
                            break;
                            case 'nome':
                              $name = get_lang('Menu_nome');
                              break;
                              case 'group_nome':
                                $name = get_lang('Menu_group_nome');
                                break;
                                case 'vendor':
                                  $name = get_lang('Menu_vendor');
                                  break;
                                  case 'group_param':
                                    $name = get_lang('Group_param');
                                    break;
                                    case 'requisites':
                                      $name = get_lang('Requisites');
                                      break;
                                      case 'files_requisites':
                                        $name = get_lang('Requisites_files');
                                        break;
                                        case 'knt':
                                          $name = get_lang('Knt');
                                          break;
                                          case 'files_contractor':
                                            $name = get_lang('Knt_files');
                                            break;
  }
  return $name;
}
function lang_is_delete($in){
  switch ($in) {
    case 'no':
      $name = get_lang('No');
      break;
      case 'yes':
        $name = get_lang('Yes');
        break;
  }
  return $name;
}
function get_count_delete() {
global $dbConnection;

            $stmt = $dbConnection->prepare('select count(id) as t1 from approve ');
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $row['t1'];
            if ($count != '0'){
              return $count;
            }
}
class Tequipment
{
    var $id;            // уникальный идентификатор
    var $orgid;         // какой организации принадлежит
    var $placesid;      // в каком помещении
    var $usersid;       // какому пользователю принадлежит
    var $nomeid;        // связь со справочником номенклатуры
    var $tmcname;       // наименование ТМЦ из справочника номенклатуры
    var $buhname;       // имя по "бухгалтерии"
    var $datepost;      // дата прихода
    var $dtendgar;      // дата гарантии
    var $cost;          // стоимость прихода
    var $currentcost;   // текущая стоимость
    var $sernum;        // серийный номер
    var $invnum;        // инвентарный номер
    var $invoice;       // номер накладной
    var $bum;           // на бумаге
    var $os;            // основные средства? 1 - да, 0 - нет
    var $mode;          // списано?  1 - да, 0 - нет
    var $comment;       // комментарий к ТМЦ
    var $photo;         // файл с фото
    var $repair;        // в ремонте?   1 - да, 0 - нет
    var $active;        // помечено на удаление?  1 - да, 0 - нет
    var $ip;            // Ip адрес
    var $kntid;        // поставщик
    var $util;        // утилизировано? 1 - да, 0 - нет
    var $sale;        // продано? 1 - да, 0 - нет

function GetById($in){ // обновляем профиль работника с текущими данными (все что заполнено)
	global $dbConnection;
	$stmt = $dbConnection->prepare ('SELECT equipment.comment,equipment.ip,equipment.photo,equipment.nomeid,getvendorandgroup.grnomeid,equipment.id AS eqid,equipment.orgid AS eqorgid, org.name AS orgname, getvendorandgroup.vendorname AS vname,
            getvendorandgroup.groupname AS grnome,places.id as placesid,knt.id as kntid, places.name AS placesname, users.login AS userslogin, users.id AS usersid,
            getvendorandgroup.nomename AS nomename, buhname, sernum, invnum, invoice, datepost,dtendgar, cost, currentcost, os, equipment.mode AS eqmode,bum,equipment.comment AS eqcomment, equipment.active AS eqactive,equipment.repair AS eqrepair,equipment.util AS equtil,equipment.sale AS eqsale
	FROM equipment
	INNER JOIN (
	SELECT nome.groupid AS grnomeid,nome.id AS nomeid, vendor.name AS vendorname, group_nome.name AS groupname, nome.name AS nomename
	FROM nome
	INNER JOIN group_nome ON nome.groupid = group_nome.id
	INNER JOIN vendor ON nome.vendorid = vendor.id
	) AS getvendorandgroup ON getvendorandgroup.nomeid = equipment.nomeid
	INNER JOIN org ON org.id = equipment.orgid
	INNER JOIN places ON places.id = equipment.placesid
	INNER JOIN users ON users.id = equipment.usersid
        LEFT JOIN knt ON knt.id = equipment.kntid WHERE equipment.id IN (:in)');
        $stmt->execute(array(':in' => $in));
        $res1 = $stmt->fetchAll();
          		if ($res1!=''){
        foreach($res1 as $myrow) {
                        $this->id=$myrow["eqid"];
                        $this->orgid=$myrow["eqorgid"];
                        $this->placesid=$myrow["placesid"];
                        $this->usersid=$myrow["usersid"];
                        $this->nomeid=$myrow["nomeid"];
                        $this->buhname=$myrow["buhname"];
                        $this->datepost=$myrow["datepost"];
                        $this->dtendgar=$myrow["dtendgar"];
                        $this->cost=$myrow["cost"];
                        $this->currentcost=$myrow["currentcost"];
                        $this->sernum=$myrow["sernum"];
                        $this->invnum=$myrow["invnum"];
                        $this->invoice=$myrow["invoice"];
                        $this->os=$myrow["os"];
                        $this->mode=$myrow["eqmode"];
                        $this->bum=$myrow["bum"];
                        $this->comment=$myrow["comment"];
                        $this->photo=$myrow["photo"];
                        $this->repair=$myrow["eqrepair"];
                        $this->active=$myrow["eqactive"];
                        $this->ip=$myrow["ip"];
                        $this->tmcname=$myrow["nomename"];
                        $this->kntid=$myrow["kntid"];
                        $this->util=$myrow["equtil"];
                        $this->sale=$myrow["eqsale"];
                };};
              }
            };
            class Helper_TimeZone
{
public static function getTimeZoneSelect($selectedZone = NULL)
{
$regions = array(
'Africa' => DateTimeZone::AFRICA,
'America' => DateTimeZone::AMERICA,
'Antarctica' => DateTimeZone::ANTARCTICA,
'Aisa' => DateTimeZone::ASIA,
'Atlantic' => DateTimeZone::ATLANTIC,
'Europe' => DateTimeZone::EUROPE,
'Indian' => DateTimeZone::INDIAN,
'Pacific' => DateTimeZone::PACIFIC
);

$structure = '<select data-placeholder="'.get_lang('Select_time_zone').'" class="my_select select"  name="time_zone" id="time_zone">';
$structure .= '<option value=""></option>';

foreach ($regions as $mask) {
$zones = DateTimeZone::listIdentifiers($mask);
$zones = self::prepareZones($zones);

foreach ($zones as $zone) {
    $continent = $zone['continent'];
    $city = $zone['city'];
    $subcity = $zone['subcity'];
    $p = $zone['p'];
    $timeZone = $zone['time_zone'];

    if (!isset($selectContinent)) {
        $structure .= '<optgroup label="'.$continent.'">';
    }
    elseif ($selectContinent != $continent) {
        $structure .= '</optgroup><optgroup label="'.$continent.'">';
    }

    if ($city) {
        if ($subcity) {
            $city = $city . '/'. $subcity;
        }

        $structure .= "<option ".(($timeZone == $selectedZone) ? 'selected="selected "':'') . " value=\"".($timeZone)."\">(UTC ".$p.") " .str_replace('_',' ',$city)."</option>";
    }

    $selectContinent = $continent;
}
}

$structure .= '</optgroup></select>';

return $structure;
}

private static function prepareZones(array $timeZones)
{
$list = array();
foreach ($timeZones as $zone) {
$time = new DateTime(NULL, new DateTimeZone($zone));
$p = $time->format('P');
if ($p > 13) {
    continue;
}
$parts = explode('/', $zone);

$list[$time->format('P')][] = array(
    'time_zone' => $zone,
    'continent' => isset($parts[0]) ? $parts[0] : '',
    'city' => isset($parts[1]) ? $parts[1] : '',
    'subcity' => isset($parts[2]) ? $parts[2] : '',
    'p' => $p,
);
}

ksort($list, SORT_NUMERIC);

$zones = array();
foreach ($list as $grouped) {
$zones = array_merge($zones, $grouped);
}

return $zones;
}
}
 ?>
