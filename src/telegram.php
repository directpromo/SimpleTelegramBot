<?php
//2021.03.29.03
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/TelegramBot


// ----------------------- System ---------------------------------
set_error_handler('ErrorSet');
require_once(__DIR__ . '/config.php');
const Url = 'https://api.telegram.org/bot' . Token;
const FilesUrl = 'https://api.telegram.org/file/bot' . Token;

$temp = file_get_contents(__DIR__ . '/admins.json');
$temp = json_decode($temp, true);
$temp = array_merge($temp, [DebugId]);
define('Admins', $temp);

$files = scandir(__DIR__ . '/modules');
foreach($files as $file):
  if($file !== '.' and $file !== '..'):
    include(__DIR__ . '/modules/' . $file);
  endif;
endforeach;

// ------------------- System functions ---------------------------

function Send(int $UserId, string $Msg):void{
  file_get_contents(Url . '/sendMessage?chat_id=' . $UserId . '&text=' . urlencode($Msg) . '&parse_mode=HTML');
}

function ErrorSet(int $errno, string $errstr, ?string $errfile = null, ?int $errline = null, ?array $errcontext = null):void{
  global $Server;
  Send(DebugId, "Error $errno in file $errfile line $errline\n$errstr\n" . json_encode($Server, JSON_PRETTY_PRINT));
  die();
}

function Unknow():void{
  global $Server;
  Send($Server['message']['from']['id'], file_get_contents(__DIR__ . '/commands/unknow.txt'));
  Send(DebugId, json_encode($Server, JSON_PRETTY_PRINT));
}

function DownloadFile():void{
  global $Server;
  $file = file_get_contents(Url . '/getFile?file_id=' . $Server['message']['document']['file_id']);
  $file = json_decode($file, true);
  $content = file_get_contents(FilesUrl . '/' . $file['result']['file_path']);
  file_put_contents(__DIR__ . '/' . $Server['message']['document']['file_name'], $content);
  Send($Server['message']['from']['id'], 'File saved.');
}

function IsAdmin(int $Id):bool{
  if(array_search($Id, Admins) === false):
    return false;
  else:
    return true;
  endif;
}

// ----------------------- Commands -------------------------------

function Command_list():void{
  global $Server;
  $msg = '';
  $i = 0;
  foreach(scandir(__DIR__) as $file):
    if($file !== '.' and $file !== '..' and substr($file, -3) !== 'php' and $file !== 'error_log'):
      $msg .= $file . "\n";
      $i++;
    endif;
  endforeach;
  Send($Server['message']['from']['id'], "$i files:\n" . $msg);
}

function Command_get():void{
  global $Server;
  $file = substr($Server['message']['text'], 5);
  if(file_exists(__DIR__ . '/commands/' . $file)):
    $file = file_get_contents(__DIR__ . '/commands/' . $file);
    Send($Server['message']['from']['id'], $file);
  else:
    Send($Server['message']['from']['id'], 'File not found.');
  endif;
}

function Command_set():void{
  global $Server;
  $text = substr($Server['message']['text'], 5);
  $pos = strpos($text, ' ');
  $file = substr($text, 0, $pos);
  if(substr($file, -3) !== 'php'):
    $content = substr($text, $pos + 1);
    file_put_contents(__DIR__ . '/commands/' . $file, $content);
    Send($Server['message']['from']['id'], $file . ' saved.');
  endif;
}

function Command_del():void{
  global $Server;
  $file = substr($Server['message']['text'], 5);
  unlink(__DIR__ . '/commands/' . $file);
  Send($Server['message']['from']['id'], $file . ' deleted.');
}

function Command_cmdget():void{
  global $Server;
  $commands = file_get_contents(Url . '/getMyCommands');
  $commands = json_decode($commands, true);
  $msg = '';
  foreach($commands['result'] as $command):
    $msg .= $command['command'] . ' ' . $command['description'] . "\n";
  endforeach;
  Send($Server['message']['from']['id'], $msg);
}

function Command_cmdset():void{
  global $Server;
  $commands = [];
  $pos = strpos($Server['message']['text'], ' ');
  $temp = substr($Server['message']['text'], $pos + 1);
  $temp = explode("\n", $temp);
  foreach($temp as $command):
    $pos = strpos($command, ' ');
    $commands[] = [
      'command' => substr($command, 0, $pos),
      'description' => substr($command, $pos + 1)
    ];
  endforeach;
  $temp = file_get_contents(Url . '/setMyCommands?commands=' . json_encode($commands));
  //Send(DebugId, $temp);
  Send($Server['message']['from']['id'], 'Commands updated.');
}

// ------------------------ Actions -------------------------------

function Action_HookSet():void{
  file_get_contents(Url . '/setWebhook?url=' . urlencode($_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME']));
  var_dump(file_get_contents(Url . '/getWebhookInfo'));
}

function Action_():void{
  global $Server;
  $Server = file_get_contents('php://input');
  $Server = json_decode($Server, true);
  if(isset($Server['message']['document']) and array_search($Server['message']['from']['id'], Admins) !== false):
    DownloadFile();
  else:
    $Texto = strtolower($Server['message']['text']);
    if(substr($Texto, 0, 1) === '/'):
      $command = substr($Texto, 1);
      if(file_exists(__DIR__ . '/commands/' . $command . '.txt')):
        $temp = file_get_contents(__DIR__ . '/commands/' . $command . '.txt');
        $temp = str_replace('##NOME##', $Server['message']['from']['first_name'], $temp);
        Send($Server['message']['from']['id'], $temp);
      elseif(IsAdmin($Server['message']['from']['id'])):
        $pos = strpos($command, ' ');
        if($pos > 0):
          $command = substr($command, 0, $pos);
        endif;
        if(function_exists('Command_' . $command)):
          call_user_func('Command_' . $command);
        else:
          Send($Server['message']['from']['id'], 'Command not found.');
        endif;
      else:
        Unknow();
      endif;
    elseif($Texto === 'my id'):
      Send($Server['message']['from']['id'], 'Your ID is ' . $Server['message']['from']['id']);
    else:
      Unknow();
    endif;
  endif;
}

// ---------------------- Entry point -----------------------------

$_GET['a'] ??= '';
if(function_exists('Action_' . $_GET['a'])):
  call_user_func('Action_' . $_GET['a']);
endif;