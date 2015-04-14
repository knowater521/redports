<?php

class Session
{
   function __construct()
   {
      self::initialize();

      if(isset($_COOKIE['SESSIONID']))
      {
         session_start();

         if($_SESSION['loginip'] != $_SERVER['REMOTE_ADDR'])
            $this->logout();
      }
   }

   static function initialize()
   {
      // Set Redis as session handler
      ini_set('session.save_handler', 'redis');
      ini_set('session.save_path', "unix:///var/run/redis/redis.sock?persistent=1");

      // Specify hash function used for session ids
      ini_set('session.hash_function', 'sha256');
      ini_set('session.hash_bits_per_character', 5);
      ini_set('session.entropy_length', 512);

      // Set session lifetime in redis (8h)
      ini_set('session.gc_maxlifetime', 28800);

      // Set cookie lifetime on client
      ini_set('session.cookie_lifetime', 0);

      // do not expose Cookie value to JavaScript (enforced by browser)
      ini_set('session.cookie_httponly', 1);

      if(Config::get('https_only') === true)
      {
         // only send cookie over https
         ini_set('session.cookie_secure', 1);
      }

      // prevent caching by sending no-cache header
      session_cache_limiter('nocache');

      // rename session
      session_name('SESSIONID');
   }

   static function getSessionId()
   {
      return session_id();
   }

   static function login($machineid, $secret)
   {
      $machines = new Machines();
      if(($machine = $machines->getMachine($machineid)) === false)
         return false;

      if($machine->getToken() !== $secret)
         return false;

      if(session_id() === '')
         session_start();

      /* login successfull */
      $_SESSION['authenticated'] = true;
      $_SESSION['machineid'] = $machine->getName();
      $_SESSION['loginip'] = $_SERVER['REMOTE_ADDR'];
      $_SESSION['useragent'] = $_SERVER['HTTP_USER_AGENT'];

      return true;
   }

   static function getMachineId()
   {
      if(isset($_SESSION['machineid']))
         return $_SESSION['machineid'];
      return false;
   }

   static function isAuthenticated()
   {
      return isset($_SESSION['authenticated']);
   }

   static function logout()
   {
      $_SESSION = array();

      /* also destroy session cookie on client */
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000,
         $params["path"], $params["domain"],
         $params["secure"], $params["httponly"]
      );

      session_destroy();
      return true;
   }

   static function generateRandomToken()
   {
      $cstrong = true;
      $bytes = '';

      for($i = 0; $i <= 32; $i++)
         $bytes .= bin2hex(openssl_random_pseudo_bytes(8, $cstrong));

      return hash('sha256', $bytes);
   }
}

