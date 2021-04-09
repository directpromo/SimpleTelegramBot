<?php
//2021.04.09.02
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/TelegramBot


// ----------------------- System ---------------------------------
set_error_handler('ErrorSet');
require_once(__DIR__ . '/config.php');
const Url = 'https://api.telegram.org/bot' . Token;
const FilesUrl = 'https://api.telegram.org/file/bot' . Token;

$temp = file_get_contents(__DIR__ . '/commands/admins.json');
$temp = json_decode($temp, true);
$temp = array_merge($temp, [DebugId]);
define('Admins', $temp);

require(__DIR__ . '/language/' . DefaultLanguage . '.php');

$temp = file_get_contents(Url . '/getMe');
$temp = json_decode($temp, true);
define('Bot', $temp['result']);

$temp = scandir(__DIR__ . '/modules');
foreach($temp as $file):
  if($file !== '.' and $file !== '..' and is_dir(__DIR__ . '/modules/' . $file) === false):
    include(__DIR__ . '/modules/' . $file);
  endif;
endforeach;

// ------------------- System functions ---------------------------

function ErrorSet(int $errno, string $errstr, ?string $errfile = null, ?int $errline = null, ?array $errcontext = null):void{
  global $Server;
  Send(DebugId, "Error $errno: $errstr in file $errfile line $errline\n" . json_encode($Server, JSON_PRETTY_PRINT));
  die();
}

function IsAdmin(int $Id):bool{
  if(array_search($Id, Admins) === false):
    return false;
  else:
    return true;
  endif;
}

function LogEvent(string $Event):void{
  global $Server;
  file_put_contents(__DIR__ . '/usage.log', date('Y-m-d H:i:s') . "\t" . $Server['message']['from']['id'] . ' ' . $Server['message']['from']['first_name'] . "\t" . $Event . "\n", FILE_APPEND);
}

function Send(int $UserId, string $Msg, ?array $Markup = null):array{
  $temp = Url . '/sendMessage?chat_id=' . $UserId . '&text=' . urlencode($Msg) . '&parse_mode=HTML';
  if($Markup !== null):
    $temp .= '&reply_markup=' . urlencode(json_encode($Markup));
  endif;
  $temp = file_get_contents($temp);
  return json_decode($temp, true);
}

function SendPhoto(int $UserId, string $File):void{
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:multipart/form-data']);
  curl_setopt($curl, CURLOPT_URL, Url . '/sendPhoto');
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, [
    'chat_id' => $UserId,
    'photo' => new CurlFile($File)
  ]);
  curl_setopt($curl, CURLOPT_INFILESIZE, filesize($File));
  curl_exec($curl);
}

function Unknow():void{
  global $Server;
  Send($Server['message']['chat']['id'], file_get_contents(__DIR__ . '/commands/unknow.txt'));
  Send(DebugId, sprintf(LangUnknowSent, json_encode($Server, JSON_PRETTY_PRINT)));
}

function DownloadFile(string $Folder = __DIR__ . '/commands'):string{
  global $Server;
  if(isset($Server['message']['document'])):
    $file = file_get_contents(Url . '/getFile?file_id=' . $Server['message']['document']['file_id']);
    $file = json_decode($file, true);
    $content = file_get_contents(FilesUrl . '/' . $file['result']['file_path']);
    file_put_contents($Folder . '/' . $Server['message']['document']['file_name'], $content);
    $file = $Server['message']['document']['file_name'];
  elseif(isset($Server['message']['photo'])):
    $file = file_get_contents(Url . '/getFile?file_id=' . $Server['message']['photo'][1]['file_id']);
    $file = json_decode($file, true);
    $content = file_get_contents(FilesUrl . '/' . $file['result']['file_path']);
    $file = date('s') . '.png';
    file_put_contents($Folder . '/' . $file, $content);
  endif;
  return $file;
}

// ----------------------- Commands -------------------------------

function Command_list():void{
  global $Server;
  if(IsAdmin($Server['message']['from']['id'])):
    $msg = '';
    $i = 0;
    foreach(scandir(__DIR__ . '/commands') as $file):
      if($file !== '.' and $file !== '..' and substr($file, -3) !== 'php' and $file !== 'error_log'):
        $msg .= $file . "\n";
        $i++;
      endif;
    endforeach;
    Send($Server['message']['from']['id'], "$i files:\n" . $msg);
  endif;
}

function Command_get():void{
  global $Server;
  if(IsAdmin($Server['message']['from']['id'])):
    $file = substr($Server['message']['text'], 5);
    if(file_exists(__DIR__ . '/commands/' . $file)):
      $file = file_get_contents(__DIR__ . '/commands/' . $file);
      Send($Server['message']['from']['id'], $file);
    else:
      Send($Server['message']['from']['id'], sprintf(LangFileNotFound, $file));
    endif;
  endif;
}

function Command_set():void{
  global $Server;
  if(IsAdmin($Server['message']['from']['id'])):
    $text = substr($Server['message']['text'], 5);
    $pos = strpos($text, ' ');
    $file = substr($text, 0, $pos);
    if(substr($file, -3) !== 'php'):
      $content = substr($text, $pos + 1);
      file_put_contents(__DIR__ . '/commands/' . $file, $content);
      Send($Server['message']['from']['id'], sprintf(LangSaved, $file));
    endif;
  endif;
}

function Command_del():void{
  global $Server;
  if(IsAdmin($Server['message']['from']['id'])):
    $file = substr($Server['message']['text'], 5);
    unlink(__DIR__ . '/commands/' . $file);
    Send($Server['message']['from']['id'], sprintf(LangDeleted, $file));
  endif;
}

function Command_ren():void{
  global $Server;
  if(IsAdmin($Server['message']['from']['id'])):
    $file = substr($Server['message']['text'], 5);
    $file = explode(' ', $file);
    rename(__DIR__ . '/commands/' . $file[0], __DIR__ . '/commands/' . $file[1]);
    Send($Server['message']['from']['id'], sprintf(LangRenamed, $file[0]));
  endif;
}

function Command_cmdget():void{
  global $Server;
  if(IsAdmin($Server['message']['from']['id'])):
    $commands = file_get_contents(Url . '/getMyCommands');
    $commands = json_decode($commands, true);
    $msg = '';
    foreach($commands['result'] as $command):
      $msg .= $command['command'] . ' ' . $command['description'] . "\n";
    endforeach;
    Send($Server['message']['from']['id'], $msg);
  endif;
}

function Command_cmdset():void{
  global $Server;
  if(IsAdmin($Server['message']['from']['id'])):
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
    Send($Server['message']['from']['id'], LangCommUpdate);
  endif;
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
  if(IsAdmin($Server['message']['from']['id']) and (isset($Server['message']['document']) or isset($Server['message']['photo']))):
    $file = DownloadFile();
    Send($Server['message']['from']['id'], sprintf(LangFileSaved, $file));
  elseif(isset($Server['message']['text'])):
    $count = strlen(Bot['username']) + 1;
    $Text = $Server['message']['text'];
    if($Server['message']['chat']['type'] === 'group' and substr($Server['message']['text'], -$count) === ('@' . Bot['username'])):
      $Text = substr($Text, 0, -$count);
    endif;
    $Text = strtolower($Text);
    if(substr($Text, 0, 1) === '/'):
      $command = substr($Text, 1);
      $pos = strpos($command, ' ');
      if($pos !== false):
        $command = substr($command, 0, $pos);
      endif;
      if(file_exists(__DIR__ . '/commands/' . $command . '.txt')):
        $temp = file_get_contents(__DIR__ . '/commands/' . $command . '.txt');
        $temp = str_replace('##NAME##', $Server['message']['from']['first_name'], $temp);
        LogEvent($command);
        Send($Server['message']['chat']['id'], $temp);
        if(file_exists(__DIR__ . '/commands/' . $command . '.png')):
          SendPhoto($Server['message']['chat']['id'], __DIR__ . '/commands/' . $command . '.png');
        endif;
      elseif(function_exists('Command_' . $command)):
        LogEvent($command);
        call_user_func('Command_' . $command);
      else:
        LogEvent('unknow');
        Unknow();
      endif;
    elseif($Text === 'my id'):
      LogEvent('MyId');
      Send($Server['message']['from']['id'], LangYourId . $Server['message']['from']['id']);
    else:
      LogEvent('Unknow');
      Unknow();
    endif;
  endif;
}

// ---------------------- Entry point -----------------------------

$_GET['a'] ??= '';
if(function_exists('Action_' . $_GET['a'])):
  call_user_func('Action_' . $_GET['a']);
endif;