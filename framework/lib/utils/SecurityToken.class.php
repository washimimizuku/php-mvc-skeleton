<?php

/**
 * Security Token is used to protect against CSRF attacks.
 *
 * @author yj
 */
class SecurityToken {
     /**
      * Return error type
      * 1 = no token in parameter
      * 2 = received token != generated token
      * 3 = expired token
      * @var int
      */
     static public $error = 0;
     
     /**
      * Generate token and put it in session  default value 30 minutes
      *
      * @param int $ttl lifetime of the token
      */
     static public function genToken($ttl = 30) {
          $salt  = '%#$%"TWRTF$W%)$%()#$"%/Q)W$/N';
          $token = hash('sha1',uniqid(rand(),true).$salt.$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);
          $rand  = rand(1,20);
          $token = substr($token,$rand,20);
          $ttl  *= 60;
          
          $_SESSION['csrf_protect']          = array();
          $_SESSION['csrf_protect']['ttl']   = time()+$ttl;
          $_SESSION['csrf_protect']['token'] = $token;
     }
 
     /**
      * get the token
      *
      * @return string
      */
     static public function getToken() {
          if(isset($_SESSION['csrf_protect']) && !empty($_SESSION['csrf_protect'])){
               return $_SESSION['csrf_protect']['token'];
          } else {
               return false;
          }
     }
 
     /**
      * Get ttl of the token
      *
      * @return int
      */
     static public function getTTL() {
          if(isset($_SESSION['csrf_protect']) && !empty($_SESSION['csrf_protect'])) {
               return $_SESSION['csrf_protect']['ttl'];
          } else {
               return false;
          }
     }
     
     /**
      * Check validity of the token
      *
      * @return boolean
      */
     static public function checkToken($token) {
          if(!isset($_SESSION)) {
               return false;
          }
          if(isset($token) && !empty($token)) {
               if($token == $_SESSION['csrf_protect']['token']) {
                    if($_SESSION['csrf_protect']['ttl']-time()>0){
                        return true;
                    } else {
                        self::$error = 3;
                    }
               } else {
                    self::$error = 2;
               }
          } else {
               self::$error = 1;
          }
          return false;
     }
     
     /**
      * Return error code
      *
      * @return int
      */
     static public function getError(){
          return self::$error;
     }
}

?>