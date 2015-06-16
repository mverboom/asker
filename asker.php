<?
// Asker by Mark Verboom
//
$CONFIGDIR="configs";
$NAME="Asker";
$VERSION="0.6";
$OVERRULE_SSL=false;
$OVERRULE_AUTH=false;

// Do not cache output
header("Expires: Mon, 26 Jul 1990 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

function substitute($text, $array) {
   foreach ($array as $var => $val) {
      if ((substr($var, -1) == "%") && (substr($var,0,1) == "%"))
         $text = str_replace($var, $val, $text);
   }
   return $text;
}

function clearvars($text) {
   return preg_replace("/%.*%/", "", $text);  
}

function showtext($data) {
   global $action;
   $output = substitute($data, $_REQUEST);
   echo clearvars($output) . "<br>";
}

function logline($loginfo) {
   if ($GLOBALS['log'] != FALSE) {
      $msg = sprintf("%s %s@%s: %s\n", date("Y/m/d H:i:s"), $GLOBALS['user'], $_SERVER['SERVER_NAME'], $loginfo);
      switch($GLOBALS['log']) {
         case "file":
            fprintf($GLOBALS['logdata'], $msg);
         break;
         case "syslog":
            syslog(constant($GLOBALS['logdata']), $msg);
         break;
      }
   }
}

function showstart($name, $title, $action, $css) {
   echo "<html><head><title>" . $GLOBALS["NAME"] . ": " . $name . " - " . $title . "</title>";
   if ($css != "")
      echo "<link rel=stylesheet type=text/css href=" . $css . ">";

   echo "</head>";
   echo "<div id=heading>" . $GLOBALS['user'] . "@" . $GLOBALS["NAME"] . "(" . $_REQUEST["action"] . "): " . $name . " - " . $title . "</div>";
   echo "<h1>" . $name . "</h1>";
   echo "<h2>" . $title . "</h2>";
   echo "<form accept-charset=UTF-8>";
   echo "<input type=hidden name=action value=" . $action . ">";
}

function showend() {
   echo "</form>";
   echo "</html>";
   if ($GLOBALS['log'] != FALSE) {
      switch($GLOBALS['log']) {
         case "file":
            fclose($GLOBALS['logdata']);
         break;
      }
   }
   exit;
}

function inputtext($variable, $question) {
   echo $question . " <input type=text name=" . $variable . "><br>";
}

function inputcheckbox($variable, $value, $question) {
   echo "<input type=checkbox name=" . $variable . " value=" . $value . ">" . $question ."</input><br>";
}


function inputselect($size, $variable, $list, $question) {
   echo $question . " <select name=" . $variable . " size=" . $size . ">";
   foreach (explode("\n", $_REQUEST[$list]) as $item) {
      if ($item != "") {
         if (strpos($item,'	') !== false) {
            $split = explode("	", $item);
            echo "<option value=\"" . $split[0] . "\">" . $split[1];
         } else
            echo "<option value=\"" . $item . "\">" . $item . "</option>";
      }
   }
   echo "</select><br>";
}

function keep($variable) {
   global $action;

   $output="<input type=hidden name=" . $variable . " value=";
   
   $value = substitute($variable, $_REQUEST);
   echo $output . $value . ">";
}

function button($screen, $label) {
   echo "<button type=submit name=state value=" . $screen . ">" . $label . "</button>";
}

function startaction($type, $var, $action) {
   $cmd=clearvars(substitute($action, $_REQUEST));
   logline("Running action: " . $cmd);
   exec("(" . $cmd . ") > /tmp/asker.start 2>&1 & echo $!", $output, $retval);
   $pid = (int)$output[0];
   rename("/tmp/asker.start", "/tmp/asker." . $pid . ".out");
   $f = fopen("/tmp/asker." . $pid . ".var", 'w');
   fwrite($f, serialize($_REQUEST));
   fclose($f);
   echo "<script>
      var type=\"" . $type . "\";
      var time=0;
      var follow=0;
      var timeout=500;
      function checkpid(pid) {
         ajax=new XMLHttpRequest();
         ajax.onreadystatechange=function() {
            if (ajax.readyState==4) {
               time+=timeout;
               ret = ajax.responseText;
               lines = ret.split('\\n');
               val = lines[0];
               follow = lines[1];
               lines.splice(0,2);
               data=lines.join('\\n');
               ts=Math.floor(time / 1000);
               document.getElementById('time').innerHTML = (\"0\" + Math.floor(ts / 60)).slice(-2) + \":\" + (\"0\" + Math.floor(ts % 60)).slice(-2);
               if (type == \"follow\") {
                  id = document.getElementById('follow');
                  id.innerHTML = id.innerHTML + data;
                  id.scrollTop = id.scrollHeight;
               }
               if (val == \"running\")
                  setTimeout(checkpid,timeout);
               else
                  window.location.href=\"asker.php?resumeaction=" . $pid . "&var=" . $var . "\";
            }
         }
         url=\"asker.php?pidcheck=" . $pid . "\";
         if (type == \"follow\")
            url=url + \"&offset=\" + follow;
         ajax.open(\"GET\",url,true);
         ajax.send(null);
      }
      checkpid();
      </script>
      <div id=progress><div id=time>00:01</div></div>";
   switch ($type) {
      case "follow":
         echo "<div id=follow></div>";
      break;
   }
   showend();
}

function resumeaction($pid) {
   $file = "/tmp/asker." . $pid . ".var";
   $rest = file_get_contents($file);
   $_REQUEST = unserialize($rest);
   unlink($file);
   $file = "/tmp/asker." . $pid . ".out";
   $output = file_get_contents($file);
   unlink($file);
   return($output);
}

function showerror($error) {
   logline("Error: " . $error);
   echo "<h1>Error</h1>";
   echo $error;
   showend();
}

function shift(&$string, $seperator) {

   $pos = strpos($string, $seperator);

   $val = substr($string,0,$pos);
   $string = substr($string,$pos + 1);
   return($val);
}

function main() {

   global $action;
   global $log;
   global $logdata;
   global $user;

   if ( !$GLOBALS['OVERRULE_SSL'] && !(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') and
      !(!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') 
      )
      showerror("Communication does not seem to be encrypted.");

   if ( !$GLOBALS['OVERRULE_AUTH'] && !isset($_SERVER['PHP_AUTH_USER']))
      showerror("There seems to be no authentication on the communcation.");

   if (isset($_SERVER['PHP_AUTH_USER']))
      $user = $_SERVER['PHP_AUTH_USER'];
   else
      $user = "anonymous";

   if (isset($_REQUEST['resumeaction'])) {
      $pid = $_REQUEST['resumeaction'];
      $var = $_REQUEST['var'];
      $_REQUEST["$var"] = resumeaction($pid);
      $resumeaction = 1;
   }

   if (isset($_REQUEST['action'])) {
      if (is_writable($GLOBALS["CONFIGDIR"]))
         showerror("Configuration directory " . $GLOBALS["CONFIGDIR"] . " is writable by the web user. This is really insecure.");
      $configfile = $GLOBALS["CONFIGDIR"] . "/" . $_REQUEST['action']. ".ini";
      if (preg_match('/^[a-z0-9-\/]+\.ini$/', $configfile)) {
         if (file_exists($configfile)) {
            if (is_writable($configfile))
               showerror("Configuration file " . $configfile . " is writable by the web user. This is really insecure.");
            $config = parse_ini_file($configfile, true);
            if ($config == FALSE) {
               $err = error_get_last();
               showerror("Configuration file " . $configfile . " contains an error: " . $err['message']);
            }
         }
         else
            showerror("Configuration file " . $configfile . " does not exist.");
      } else
         showerror("Configuration file " . $configfile . " contains invalid characters.");

      if (isset($config['start']['log'])) {
         $logconfig = $config['start']['log'];
         $logtype = shift($logconfig, ",");
         switch($logtype) {
            case "file":
               $logdata = fopen($logconfig, "a");
               if ($logdata == FALSE)
                  showerror("Unable to open logfile: " . $logconfig);
               $log = $logtype;
            break;
            case "syslog":
               $logdata = $logconfig;
               $log = $logtype;
           break;
         }
      }
      else
         $log = FALSE;
      logline("Starting config: " . $configfile);

      if (!isset($_REQUEST['state']))
         $state = $config['start']['begin'];
      else
         $state = $_REQUEST['state'];

      logline("Requesting state: " . $state);

      if (!isset($config[$state]))
         showerror("Screen " . $state . " does not exist.");

      if (!isset($config[$state]['title']))
         showerror("Screen " . $state . " does not have a title.");

      if (isset($config['start']['css']))
         $css=$config['start']['css'];
      else
         $css="";

      showstart($config['start']['name'], $config[$state]['title'], $_REQUEST['action'], $css);

      if (isset($config[$state]['action']) & ! isset($resumeaction)) {
         $action = $config[$state]['action'];
         $type = shift($action, ",");
         $var = shift($action, ",");
         startaction($type, $var, $action);
      }

      if (isset($config[$state]['item'])) {
         foreach ($config[$state]['item'] as $id => $name) {
            $cmd = shift($name, ",");
            switch ($cmd) {
               case "text":
                  showtext($name);
               break;
               case "input":
                  $variable = shift($name, ",");
                  inputtext($variable, $name);
               break;
               case "select":
                  $size = shift($name, ",");
                  $variable = shift($name, ",");
                  $list = shift($name, ",");
                  inputselect($size, $variable, $list, $name);
               break;
               case "button":
                  $screen = shift($name, ",");
                  button($screen, $name);
               break;
               case "checkbox":
                  $variable = shift($name, ",");
                  $value = shift($name, ",");
                  inputcheckbox($variable, $value, $name);
               break;

               case "keep":
                  keep($name);
               break;
               case "showvar":
                  $format = shift($name, ",");
                  showvar($format, $name);
               break;
            }
         }
      }
      logline("Finished.");
      showend();
   }
   elseif (isset($_REQUEST['pidcheck'])) {
      $pid = $_REQUEST['pidcheck'];
      if (file_exists("/tmp/asker." . $pid . ".var")) {
         if (file_exists("/proc/" . $pid ))
            echo "running\n";
         else
            echo "done\n";
         if (isset($_REQUEST['offset'])) {
            $f= fopen("/tmp/asker." . $pid . ".out", 'r');
            $data = stream_get_contents($f, -1, $_REQUEST['offset']);
            echo ftell($f) . "\n";
            echo $data;
            fclose($f);
         }
      } else
         echo "not running";
   }
   else
      echo "Nothing to do";
}

main();
?>
