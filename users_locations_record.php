<?php
class CookieInterface {
  public function setCookie ($cookieName, $cookieValue) {
    $_SESSION[$cookieName] = $cookieValue;
  }
  public function getCookie ($cookieName) {
    return $_SESSION[$cookieName];
  }
  public function destroy () {
    if (ini_get('session.use_cookies')) {
	  $params = session_get_cookie_params();
	  setcookie(session_name(), '', time() - 42000,
	    $params['path'], $params['domain'],
	    $params['secure'], $params['httponly']
        );
    }
    session_destroy();
  }
  public function __construct () {
    session_start([
      'cookie_lifetime' => 86400,
    ]);
  }
}

class MysqlInterface {
  private $query_char = '?';
  public $link;
  public $result;
	
  private function conect ($sname, $uname, $pass, $dbname) {
    $this->link = new mysqli(
      $sname, $uname, $pass, $dbname
      );
    if ($this->link->connect_error) {
      throw new Exception('Session error');
    }
  }
	
  private function sanitize ($par) {
    $par = $this->link->real_escape_string($par);
    return $par;
  }
	
  private function parseMetaSql ($meta_sql, $sql_par) {
    $n = count($sql_par);
    $sql = '';
    for ($i = 0; $i < strlen($meta_sql); $i++) {
		
      if ($meta_sql[$i] == $this->query_char && $n > 0) {
        $current_par = $sql_par[count($sql_par)-$n];
        $sql = $sql . $current_par;
        $n--;
      } else {
        $sql = $sql . $meta_sql[$i];
      }
    }
    return $sql;
  }
	
  public function __construct ($sname, $uname, $pass, $dbname) {
    $this->conect(
      $sname, $uname, $pass, $dbname
      );
  }
	
  public function query ($meta_sql) {
    $sql_par = array();
    for ($i = 0; $i < func_num_args()-1; $i++) {
      $par = func_get_arg($i+1);
      $par = $this->sanitize($par);
      $par = '"' . $par . '"';
      array_push($sql_par, $par);
    }
    $sql = $this->parseMetaSql($meta_sql, $sql_par);
    $this->result = $this->link->query($sql);
  }
	
  public function close () {
    $this->link->close();
  }	
}

//remplaza por tus datos
$mi0 = new MysqlInterface(
  'localhost',
  'usuario',
  'contraseña',
  'base de datos'
  );

$ci0 = new CookieInterface();
$cookieName = 'user_life';
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$current_ip = $_SERVER['REMOTE_ADDR'];

if ($ci0->getCookie($cookieName) === NULL) {
	$ci0->setCookie($cookieName, session_id());
}

$mi0->query('INSERT INTO UsersLocations (user_id, user_location, user_ip)
	VALUES (?, ?, ?)',
	$ci0->getCookie($cookieName),
	$current_url,
	$current_ip
	);
if ($mi0->result !== TRUE) {
	//si hubo algun error
} else {
	//si todo salio bien
}
