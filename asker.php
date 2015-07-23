<?php
// Asker by Mark Verboom
//
define("CONFIGDIR", "configs");
define("NAME", "Asker");
define("VERSION", "0.72");
define("OVERRULE_SSL", true);
define("OVERRULE_AUTH", false);

// Do not cache output
header("Expires: Mon, 26 Jul 1990 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

function substitute($text, $array) {
   foreach ($array as $var => $val) {
      if ((substr($var, -1) == "%") && (substr($var,0,1) == "%"))
         $text = str_replace($var, urldecode($val), $text);
   }
   return $text;
}

function phtml($text) {
   if (func_num_args() > 1 && func_get_arg(1) != "")
      printf("<div id=%s>\n", func_get_arg(1));
   printf("%s\n", $text);
   if (func_num_args() > 1 && func_get_arg(1) != "")
      printf("</div>\n");
}

function clearvars($text) {
   return preg_replace("/%.*%/", "", $text);  
}

function logopen($config) {
   if (isset($config['start']['log'])) {
      $logconfig = $config['start']['log'];
      $logtype = shift($logconfig, ",");
      switch($logtype) {
         case "file":
            $GLOBALS['logdata'] = fopen($logconfig, "a");
            if ($GLOBALS['logdata'] == FALSE)
               showerror("Unable to open logfile: " . $logconfig);
            $GLOBALS['log'] = $logtype;
         break;
         case "syslog":
            $GLOBALS['logdata'] = $logconfig;
            $GLOBALS['log'] = $logtype;
        break;
      }
   }
   else
      $GLOBALS['log'] = FALSE;
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
   $title = substitute($title, $_REQUEST);

   phtml("<html><head><title>" . NAME . ": " . $name . " - " . $title . "</title>");
   if ($css != "")
      phtml("<link rel=stylesheet type=text/css href=" . $css . ">");

   phtml("</head>");
   phtml("<div id=heading>" . $GLOBALS['user'] . "@" . NAME . "(" . $action . "): " . $name . " - " . $title . "</div>");
   phtml("<h1>" . $name . "</h1>");
   phtml("<h2>" . $title . "</h2>");
   phtml("<form accept-charset=UTF-8 name=asker enctype=multipart/form-data method=post>");
   phtml("<input type=hidden name=action value=" . $action . ">");
}

function showend() {
   phtml("</form>");
   phtml("</html>");
   if ($GLOBALS['log'] != FALSE) {
      switch($GLOBALS['log']) {
         case "file":
            fclose($GLOBALS['logdata']);
         break;
      }
   }
   exit;
}

function showtext($data, $id) {

   $output = substitute($data, $_REQUEST);
   debug("type","text","id",$id,"text",$data);
   phtml(clearvars($output) . "<br>", $id);
}

function inputtext($variable, $question, $size, $required, $id) {
   if ($required == "true")
      $req="required";
   else
      $req="";
   debug("type", "input", "id", $id, "var", $variable, "req", $required, "size", $size, "text", $question);
   phtml($question . " <input type=text name=" . $variable . " size=" . $size ." maxlength=" . $size . " " . $req . "><br>", $id);
}

function inputpassword($variable, $question, $size, $required, $id) {
   if ($required == "true")
      $req="required";
   else
      $req="";
   debug("type","password","id", $id, "var", $variable, "req", $required, "size", $size, "text", $question);
   phtml($question . " <input type=password name=" . $variable . " size=" . $size ." maxlength=" . $size . " " . $req . "><br>", $id);
}

function inputnumber($variable, $question, $required, $min, $max, $id) {
   debug("type","number","id", $id, "var", $variable, "req", $required, "min", $min, "max", $max, "text", $question);
   if ($required == "true")
      $req="required";
   else
      $req="";
   if ($min != "")
      $min="min=".$min;
   if ($max != "")
      $max="max=".$max;
   phtml($question . " <input type=number name=" . $variable . " " . $req . " " . $min . " " . $max . "><br>", $id);
}

function inputedit($variable, $text, $required, $width, $height, $id) {
   if ($required == "true")
      $req="required";
   else
      $req="";
   debug("type","edit","id", $id, "var", $variable, "req", $required, "width", $width, "height", $height, "text", $text);
   phtml("<textarea name=" . $variable . " " . $req . " cols=" . $width . " rows=" . $height . ">" . $text . "</textarea>", $id);
}

function inputcheckbox($variable, $value, $question, $id) {
   debug("type","checkbox","id", $id, "var", $variable, "text", $question);
   phtml("<input type=checkbox name=" . $variable . " value=" . $value . ">" . $question ."</input><br>", $id);
}

function select($required, $size, $variable, $list, $question,$id) {
   if ($id != "")
      phtml("<div id=" . $id . ">");
   if ($required == "true")
      $req="required";
   else
      $req="";
   debug("type","select","id", $id, "var", $variable, "req", $required, "size", $size, "list", $list, "text", $question);
   phtml($question . " <select name=" . $variable . " size=" . $size . " " . $req . ">");
   foreach (explode("\n", urldecode($_REQUEST[$list])) as $item) {
      if ($item != "") {
         if (strpos($item,'	') !== false) {
            $split = explode("	", $item);
            phtml("<option value=\"" . $split[0] . "\">" . $split[1]);
         } else
            phtml("<option value=\"" . $item . "\">" . $item . "</option>");
      }
   }
   phtml("</select><br>");
   if ($id != "")
      phtml("</div>");
}

function keep($variable) {
   $output="<input type=hidden name=" . $variable . " value=";
   
   $value = substitute($variable, $_REQUEST);
   debug("type","keep", "var", $variable, "value", $value);
   phtml($output . urlencode($value) . ">");
}

function autosubmit($screen) {
   phtml("<input type=hidden name=state value=" . $screen . ">");
   phtml("<script>window.onload = function(){document.forms[\"asker\"].submit();}</script>");
}

function button($screen, $label, $id) {
   debug("type","button", "id", $id, "screen", $screen, "text", $label);
   phtml("<button type=submit name=state value=" . $screen . ">" . $label . "</button>", $id);
}

function uploadfile($dir, $name, $text, $id, $required) {
   if ($required == "true")
      $req="required";
   else
      $req="";
   debug("type","upload", "id", $id, "req", $required, "dir", $dir, "name", $name, "text", $text);
   phtml($text . "<input type=file name=" . urlencode($name . " " . $dir) . " " . $req . ">", $id);
}

function iftest($var, $op, $then, $else, $val) {
   $actionset=0;
   $val1 = $_REQUEST[$var];
   $val2 = substitute($val, $_REQUEST);
   phtml("if " . $val1 . " " . $op . " " . $val2 . " then " . $then . " else " . $else . "<br>");
   switch ($op) {
      case "eq":
         if ($val1 == $val2) {
            autosubmit($then);
            $actionset=1;
         } else {
            if ($else != "") {
               autosubmit($else);
               $actionset=1;
            }
         }
      break;
      case "ne":
         if ($val1 != $val2) {
            autosubmit($then);
            $actionset=1;
         } else {
            if ($else != "") {
               autosubmit($else);
               $actionset=1;
            }
         }
      break;
      case "gt":
         if ($val1 > $val2) {
            autosubmit($then);
            $actionset=1;
         } else {
            if ($else != "") {
               autosubmit($else);
               $actionset=1;
            }
         }
      break;
      case "lt":
         if ($val1 < $val2) {
            autosubmit($then);
            $actionset=1;
         } else {
            if ($else != "") {
               autosubmit($else);
               $actionset=1;
            }
         }
      break;
      default: 
         showerror("iftest unknown operand " . $op);
   }
   return $actionset;
}

function startrun($type, $var, $id, $ignoreerr, $text, $action) {
   $cmd=clearvars(substitute($action, $_REQUEST));
   logline("Running action: " . $cmd);
   exec("(" . $cmd . " ;echo -n ASKER$?ASKER ) > /tmp/asker.start 2>&1 & echo $!", $output, $retval);
   $pid = (int)$output[0];
   rename("/tmp/asker.start", "/tmp/asker." . $pid . ".out");
   $f = fopen("/tmp/asker." . $pid . ".var", 'w');
   fwrite($f, serialize($_REQUEST));
   fclose($f);
   phtml("<script>
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
               document.getElementById('time').innerHTML = '" . $text . "<br>' + (\"0\" + Math.floor(ts / 60)).slice(-2) + \":\" + (\"0\" + Math.floor(ts % 60)).slice(-2);
               if (type == \"follow\") {
                  id = document.getElementById('" . $id . "');
                  id.innerHTML = id.innerHTML + data;
                  id.scrollTop = id.scrollHeight;
               }
               if (val == \"running\")
                  setTimeout(checkpid,timeout);
               else
                  document.forms[\"asker\"].submit();
            }
         }
         url=\"pidcheck=" . $pid . "\";
         if (type == \"follow\")
            url=url + \"&offset=\" + follow;
         ajax.open(\"POST\",\"asker.php\",true);
         ajax.setRequestHeader(\"Content-type\", \"application/x-www-form-urlencoded\");
         ajax.send(url);
      }
      checkpid();
      </script>
      <div id=progress><div id=time></div></div>
      <form accept-charset=UTF-8 name=asker enctype=multipart/form-data method=post><input type=hidden name=resumerun value=" . $pid . "><input type=hidden name=var value=". $var ."></form>");
   switch ($type) {
      case "follow":
         phtml("<div id=" . $id . "></div>");
      break;
   }
   showend();
}

function resumerun($pid, &$retcode) {
   $file = "/tmp/asker." . $pid . ".var";
   if (!file_exists($file))
      showerror("Can not find command running state.");
   $rest = file_get_contents($file);
   $_REQUEST = unserialize($rest);
   unlink($file);
   $file = "/tmp/asker." . $pid . ".out";
   if (!file_exists($file))
      showerror("Can not find command output.");
   $output = file_get_contents($file);
   unlink($file);
   $retcode=preg_replace('/.*ASKER(\d+)ASKER$/s', '$1', $output);
   return(preg_replace('/(.*)ASKER\d+ASKER$/s', '$1', $output));
}

function showerror($error) {
   logline("Error: " . $error);
   phtml("<h1>Error</h1>");
   phtml($error);
   showend();
}

function shift(&$string, $seperator) {

   $pos = strpos($string, $seperator);

   $val = substr($string,0,$pos);
   $string = substr($string,$pos + 1);
   return($val);
}

function sanitychecks() {
   if ( !OVERRULE_SSL && !(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') and
      !(!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') 
      )
      showerror("Communication does not seem to be encrypted.");

   if ( !OVERRULE_AUTH && !isset($_SERVER['PHP_AUTH_USER']))
      showerror("There seems to be no authentication on the communcation.");

   if (isset($_SERVER['PHP_AUTH_USER']))
      $GLOBALS['user'] = $_SERVER['PHP_AUTH_USER'];
   else
      $GLOBALS['user'] = "anonymous";
}

function readconfig($action) {
      if (is_writable(CONFIGDIR))
         showerror("Configuration directory " . CONFIGDIR . " is writable by the web user. This is really insecure.");
      $configfile = CONFIGDIR . "/" . $action. ".ini";
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
   return $config;
}

function parseoptions($cfgline, &$text) {
   $runline = substr($cfgline,strpos($cfgline,'{') + 1);
   $optionsraw = shift($runline, "}");
   if (strlen($runline) > 0 && substr($runline,0,1) != ",")
      showerror("No , after options defined");
   $text = substr($runline,1);
   $cfg = "";
   if ($optionsraw != "")
      foreach (explode(",", $optionsraw) as $item) {
         if (strpos($item, ":") == FALSE)
            showerror("Error parsing options: " . $item);
         $s = explode(":", $item);
         $s[0] = strtolower($s[0]);
         if ($s[0] != "var" && $s[0] != "list")
            $s[1] = substitute($s[1], $_REQUEST);
         $cfg[$s[0]] = $s[1];
      }
   return($cfg);
}

function debug($text) {
   if ($GLOBALS['debug'] > 0) {
      $output = "<div id=debug><div id=debuglabel>D</div><div id=debugtext>";
      for($i = 0 ; $i < func_num_args(); $i+=2) {
         $output = $output . func_get_arg($i) .": ";
         $val=func_get_arg($i+1);
         if ($val != "")
            $output = $output . $val;
         else
            $output = $output . "(none)";
         $output = $output . "<br>";
      }
      $output = $output . "</div></div>";
      phtml($output);
   }
}

function processaction($action, $resumerun, $runcode) {
   $showerror = 0;
   $config = readconfig($action);
   logopen($config);
   logline("Starting config: " . $action);

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

   if (isset($config[$state]['run']) & $resumerun == 1 & $runcode != 0) {
      $cfg = parseoptions($config[$state]['run'], $command);
      if (isset($cfg['err']))
         $state = $cfg['err'];
      else
         if (isset($cfg['ignoreerr']))
            $_REQUEST[$cfg['ignoreerr']] = $runcode;
         else
            $showerror = 1;
      unset($cfg);
   }

   showstart($config['start']['name'], $config[$state]['title'], $action, $css);

   if ($showerror == 1)
      showerror("Errorcode " . $runcode . " when running command.");

   if (isset($config[$state]['run']) & $resumerun == 0) {
      $cfg = parseoptions($config[$state]['run'], $command);
      startrun(isset($cfg['type'])?$cfg['type']:"normal",$cfg['var'], isset($cfg['id'])?$cfg['id']:"follow",isset($cfg['ignoreerr'])?$cfg['ignoreerr']:"",isset($cfg['text'])?$cfg['text']:"Running",$command);
   }

   if (isset($config[$state]['item'])) {
      foreach ($config[$state]['item'] as $id => $name) {
         $cmd = substr($name, 0, strpos($name, "{"));
         unset($cfg);
         $cfg = parseoptions($name, $text);
         $text = clearvars(substitute($text, $_REQUEST));

         switch ($cmd) {
            case "text":
               showtext($text,isset($cfg['id'])?$cfg['id']:"");
            break;
            case "input":
               if (!isset($cfg['var']))
                  showerror("Input requires option var to be defined.");
               inputtext($cfg['var'], $text,isset($cfg['size'])?$cfg['size']:30,isset($cfg['req'])?$cfg['req']:"",isset($cfg['id'])?$cfg['id']:"");
            break;
            case "password":
               if (!isset($cfg['var']))
                  showerror("Password requires option var to be defined.");
               inputpassword($cfg['var'], $text,isset($cfg['size'])?$cfg['size']:10,isset($cfg['req'])?$cfg['req']:"",isset($cfg['id'])?$cfg['id']:"");
            break;
            case "number":
               if (!isset($cfg['var']))
                  showerror("Number requires option var to be defined.");
               inputnumber($cfg['var'], $text,isset($cfg['req'])?$cfg['req']:"",isset($cfg['min'])?$cfg['min']:"",isset($cfg['max'])?$cfg['max']:"",isset($cfg['id'])?$cfg['id']:"");
            break;
            case "edit":
               if (!isset($cfg['var']))
                  showerror("Edit requires option var to be defined.");
               inputedit($cfg['var'], $text,isset($cfg['req'])?$cfg['req']:"",isset($cfg['width'])?$cfg['width']:40,isset($cfg['height'])?$cfg['height']:20,isset($cfg['id'])?$cfg['id']:"");
            break;

            case "select":
               if (!isset($cfg['var']))
                  showerror("Select requires option var to be defined.");
               if (!isset($cfg['list']))
                  showerror("Select requires option list to be defined.");
               select(isset($cfg['req'])?$cfg['req']:"", isset($cfg['size'])?$cfg['size']:1, $cfg['var'], $cfg['list'], $text,isset($cfg['id'])?$cfg['id']:"");
            break;
            case "button":
               if (!isset($cfg['scr']))
                  showerror("Button requires option scr to be defined.");
               if (!isset($config[$cfg['scr']]))
                  showerror("Button options scr references non existing screen ". $cfg['scr']);
               button($cfg['scr'], $text,isset($cfg['id'])?$cfg['id']:"");
            break;
            case "checkbox":
               if (!isset($cfg['var']))
                  showerror("Checkbox requires optoin var to be defined.");
               inputcheckbox($cfg['var'], $cfg['val'], $text,isset($cfg['id'])?$cfg['id']:"");
            break;
            case "keep":
               if (!isset($cfg['var']))
                  showerror("Keep requires option var to be defined.");
               keep($cfg['var']);
            break;
            case "autosubmit":
               if (!isset($cfg['scr']))
                  showerror("Autosubmit requires option scr to be defined.");
               if (!isset($config[$cfg['scr']]))
                  showerror("Screen " . $cfg['scr'] . " does not exist for autosubmit.");
               autosubmit($cfg['scr']);
            break;
            case "upload":
               if (!isset($cfg['dir']))
                  showerror("Upload requires option dir to be defined.");
               if (!isset($cfg['name']))
                  showerror("Upload requires option name to be defined.");
               uploadfile($cfg['dir'], $cfg['name'], $text,isset($cfg['id'])?$cfg['id']:"",isset($cfg['req'])?$cfg['req']:"");
            break;
            case "setvar":
               if (!isset($cfg['var']))
                  showerror("Setvar requires option var to be defined.");
               $trans = array('\n' => "\n", '\t' => "\t");
               $_REQUEST[$cfg['var']] = strtr($text, $trans);
            break;
            case "if":
               if (!isset($cfg['var']))
                  showerror("If requires option var to be defined.");
               if (!isset($cfg['op']))
                  showerror("If requires option op to be defined.");
               if (!isset($cfg['then']))
                  showerror("If requires option then to be defined.");
               if (!isset($config[$cfg['then']]))
                  showerror("If option then references non existing screen.");
               if (!isset($text))
                  showerror("If requires option op to be defined.");
               if (isset($cfg['else']) && !isset($config[$cfg['else']]))
                  showerror("If option else references non existing screen.");
               iftest($cfg['var'], $cfg['op'], $cfg['then'], isset($cfg['else'])?$cfg['else']:"",$text);
            break;
         }
      }
   }
   logline("Finished.");
   showend();
}

function processuploadedfiles() {
   foreach ($_FILES as $id => $file) {
      if ($file['error'] != UPLOAD_ERR_NO_FILE) {
         if ($file['error'] != UPLOAD_ERR_OK)
            showerror("Uploading file " . $file['name'] . " failed.");
        $parts=explode(" ", urldecode($id));
        $targetfile=$parts[1] . "/" . $file['name'];
        if (file_exists($targetfile))
           showerror("File " . $targetfile . " already exists.");
        move_uploaded_file($file["tmp_name"], $targetfile);
        $_REQUEST[$parts[0]] = $targetfile;
      }
   }
}

function main() {
   global $log;
   global $logdata;
   global $user;
   global $debug;
   $debug=0;
   $runcode=0;

   sanitychecks();

   if (isset($_REQUEST['debug']))
      $debug = $_REQUEST['debug'];
   
   if (isset($_FILES))
      processuploadedfiles();

   $resumerun = 0;
   if (isset($_REQUEST['resumerun'])) {
      $pid = $_REQUEST['resumerun'];
      $var = $_REQUEST['var'];
      $_REQUEST["$var"] = resumerun($pid, $runcode);
      $resumerun = 1;
   }

   if (isset($_REQUEST['action'])) {
      processaction($_REQUEST['action'], $resumerun, $runcode);
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
      phtml("Choose action for asker to do.");
}

main();
?>
