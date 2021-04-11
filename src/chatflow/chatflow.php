<?php
//2021.04.11.02

require(dirname(__DIR__, 1) . '/language/' . DefaultLanguage . '_chatflow.php');
require(__DIR__ . '/templates.php');

const CfStatus_WaitingReply_WaitList = 0;
const CfStatus_WaitList = 1;
const CfStatus_Chatting = 2;

function ChatFlowLoad():void{
  global $ChatFlow;
  $ChatFlow = file_get_contents(__DIR__ . '/chatflow.json');
  $ChatFlow = json_decode($ChatFlow, true);
}

function ChatFlowSave():void{
  global $ChatFlow;
  file_put_contents(__DIR__ . '/chatflow.json', json_encode($ChatFlow));
}

function ChatFlowSet(int $User, string $Field, int $Value):void{
  global $ChatFlow;
  $index = array_search($User, array_column($ChatFlow, 'user'));
  if($index === false):
    $ChatFlow[] = [
      'user' => $User,
      $Field => $Value,
      'time' => time()
    ];
  else:
    $ChatFlow[$index]['user'] = $User;
    $ChatFlow[$index][$Field] = $Value;
    $ChatFlow[$index]['time'] = time();
  endif;
}

/**
 * @return string|false
 */
function ChatFlowGet(int $User, string $Field){
  global $ChatFlow;
  $index = array_search($User, array_column($ChatFlow, 'user'));
  if($index === false):
    return false;
  else:
    return $ChatFlow[$index][$Field];
  endif;
}

function ChatFlowDel(int $User):void{
  global $ChatFlow;
  $index = array_search($User, array_column($ChatFlow, 'user'));
  unset($ChatFlow[$index]);
}

//--------------------------------------------------------------------------------

function IsAttendant(int $User):bool{
  $Attendants = file_get_contents(dirname(__DIR__, 1) . '/commands/chatflow.json');
  $Attendants = json_decode($Attendants, true);
  if(array_search($User, $Attendants) === false):
    return false;
  else:
    return true;
  endif;
}

/**
 * @return int|false
 */
function GetAnAttendant(){
  $Attendants = file_get_contents(dirname(__DIR__, 1) . '/commands/chatflow.json');
  $Attendants = json_decode($Attendants, true);
  shuffle($Attendants);
  foreach($Attendants as $Attendant):
    if(ChatFlowGet($Attendant, 'status') !== CfStatus_Chatting):
      return $Attendant;
    endif;
  endforeach;
  return false;
}

/**
 * @return int|false
 */
function WaitListNext(){
  global $ChatFlow;
  $index = array_search(CfStatus_WaitList, array_column($ChatFlow, 'status'));
  if($index === false):
    return false;
  else:
    return $ChatFlow[$index]['user'];
  endif;
}

function WaitListCount():int{
  global $ChatFlow;
  $count = array_count_values(array_column($ChatFlow, 'status'));
  return $count[CfStatus_WaitList] ?? 0;
}

function WaitListBroadcast():void{
  $Attendants = file_get_contents(dirname(__DIR__, 1) . '/commands/chatflow.json');
  $Attendants = json_decode($Attendants, true);
  foreach($Attendants as $Attendant):
    if(ChatFlowGet($Attendant, 'status') !== CfStatus_Chatting):
      TmpUsersInWaitList($Attendant);
    endif;
  endforeach;
}

function ChatEnd(int $User):void{
  $User2 = ChatFlowGet($User, 'with');
  ChatFlowDel($User);
  ChatFlowDel($User2);
  Send($User, Lang_ChatFlow_ChatEnded);
  Send($User2, Lang_ChatFlow_ChatEnded);
  if(IsAttendant(($User))):
    TmpUsersInWaitList($User);
  else:
    TmpUsersInWaitList($User2);
  endif;
}

function CheckTimes():void{
  global $ChatFlow;
  foreach($ChatFlow as $User => $data):
    if($data['time'] < strtotime('-10 minutes')):
      if($data['status'] === CfStatus_Chatting):
        ChatEnd($User);
      elseif($data['status'] === CfStatus_WaitList):
        Send($User, Lang_ChatFlow_NoAttenders, TmpBtnYesNo());
        ChatFlowSet($User, 'status', CfStatus_WaitingReply_WaitList);
      endif;
    endif;
  endforeach;
}

ob_start();
ChatFlowLoad();
CheckTimes(); //Check the times in each message, case cron are not set
if(isset($Server['message'])):
  $User = $Server['message']['from']['id'];
  $Msg = $Server['message']['text'];
  if(IsAttendant($User)):
    $Attendant = $User;
    if(Equals($Msg, Lang_ChatFlow_Cmd_Next) and ChatFlowGet($Attendant, 'status') !== CfStatus_Chatting):
      $User = WaitListNext();
      if($User === false):
        TmpUsersInWaitList($Attendant);
      else:
        
        ChatFlowSet($User, 'status', CfStatus_Chatting);
        ChatFlowSet($User, 'with', $Attendant);
        ChatFlowSet($Attendant, 'status', CfStatus_Chatting, []);
        ChatFlowSet($Attendant, 'with', $User);
        Send($User, Lang_ChatFlow_ChattingWithAttendant);
        Send($Attendant, sprintf(Lang_ChatFlow_ChattingWithClient, $Server['message']['from']['first_name']), TmpBtnRemove());
      endif;
    elseif(Equals($Msg, Lang_ChatFlow_Cmd_EndChat) and ChatFlowGet($Attendant, 'status') === CfStatus_Chatting):
      ChatEnd($Attendant);
    elseif(ChatFlowGet($Attendant, 'status') === CfStatus_Chatting):
      $User = ChatFlowGet($Attendant, 'with');
      Send($User, $Msg);
      ChatFlowSet($Attendant, 'status', CfStatus_Chatting);
    else:
      TmpUsersInWaitList($Attendant);
    endif;

  elseif(ChatFlowGet($User, 'status') === false):
    Send($User, Lang_ChatFlow_DontKnow . Lang_ChatFlow_WantAttendant, TmpBtnYesNo());
    ChatFlowSet($User, 'status', CfStatus_WaitingReply_WaitList);

  elseif(ChatFlowGet($User, 'status') == CfStatus_WaitingReply_WaitList):
    if(Equals($Text, Lang_Yes)):
      Send($User, Lang_ChatFlow_InWaitList, TmpBtnRemove());
      ChatFlowSet($User, 'status', CfStatus_WaitList);
      WaitListBroadcast();
    elseif(Equals($Text, Lang_No)):
      Send($User, Lang_ChatFlow_DontWait, TmpBtnRemove());
      ChatFlowDel($User);
    else:
      Send($User, Lang_ChatFlow_WantAttendant, TmpBtnYesNo());
      ChatFlowSet($User, 'status', CfStatus_WaitingReply_WaitList);
    endif;

  elseif(ChatFlowGet($User, 'status') == CfStatus_Chatting):
    $Attendant = ChatFlowGet($User, 'with');
    ChatFlowSet($User, 'status', CfStatus_Chatting);
    Send($Attendant, $Msg);
  endif;
endif;
ChatFlowSave();

//Debug
$data = ob_get_contents();
if($data !== ''):
  Send(DebugId, $data);
endif;