<?php
//2021.04.11.00

const ChatFlowStatus_WaitingResponse = '0';
const ChatFlowStatus_Chatting = '1';

function ChatFlowLoad():void{
  global $ChatFlow;
  $ChatFlow = file_get_contents(__DIR__ . '/chatflow.json');
  $ChatFlow = json_decode($ChatFlow, true);
}

function ChatFlowSave():void{
  global $ChatFlow;
  file_put_contents(__DIR__ . '/chatflow.json', json_encode($ChatFlow));
}

function ChatFlowSet(int $UserId, string $Field, string $Value):void{
  global $ChatFlow;
  $ChatFlow[$UserId][$Field] = $Value;
  $ChatFlow[$UserId]['time'] = time();
}

/**
 * @return string|false
 */
function ChatFlowGet(int $UserId, string $Field){
  global $ChatFlow;
  return $ChatFlow[$UserId][$Field] ?? false;
}

function ChatFlowDel(int $UserId):void{
  global $ChatFlow;
  unset($ChatFlow[$UserId]);
}

function GetAnAttendant():int{
  $Attendants = file_get_contents(__DIR__ . '/commands/chatflow.json');
  $Attendants = json_decode($Attendants, true);
  shuffle($Attendants);
  foreach($Attendants as $Attendant):
    if(ChatFlowGet($Attendant, 'status') !== ChatFlowStatus_Chatting):
      return $Attendant;
    endif;
  endforeach;
}

function CheckTimes():void{
  global $ChatFlow;
  foreach($ChatFlow as $User => $data):
    if($data['time'] < strtotime('-10 minutes')):
      Send($User, Lang_ChatEnded);
      Send($data['with'], Lang_ChatEnded);
      ChatFlowDel($User);
      ChatFlowDel($data['with']);
    endif;
  endforeach;
}

ChatFlowLoad();
CheckTimes(); //Check the times in each message, case cron are not set
if(isset($Server['message'])):
  if(ChatFlowGet($Server['message']['from']['id'], 'status') === false):
    Send($Server['message']['chat']['id'], Lang_WantAttendant, [
      'one_time_keyboard' => true,
      'resize_keyboard' => true,
      'keyboard'=>[
          [Lang_No, Lang_Yes]
        ]
      ]
    );
    ChatFlowSet($Server['message']['from']['id'], 'status', ChatFlowStatus_WaitingResponse);
  elseif(ChatFlowGet($Server['message']['from']['id'], 'status') == ChatFlowStatus_WaitingResponse):
    if(Equals($Text, Lang_Yes)):
      $Attendant = GetAnAttendant();

      Send($Server['message']['from']['id'], Lang_WaitForAttender, ['remove_keyboard' => true]);
      ChatFlowSet($Server['message']['from']['id'], 'status', ChatFlowStatus_Chatting);
      ChatFlowSet($Server['message']['from']['id'], 'with', $Attendant);

      Send($Attendant, sprintf(Lang_WantToChat, $Server['message']['from']['first_name'], Lang_EndChat));
      ChatFlowSet($Attendant, 'status', ChatFlowStatus_Chatting);
      ChatFlowSet($Attendant, 'with', $Server['message']['from']['id']);
    elseif(Equals($Text, Lang_No)):
      Send($Server['message']['from']['id'], Lang_DontWaitForAttender, ['remove_keyboard' => true]);
      ChatFlowDel($Server['message']['from']['id']);
    else:
      Send($Server['message']['chat']['id'], Lang_WantAttendant, [
        'one_time_keyboard' => true,
        'resize_keyboard' => true,
        'keyboard'=>[
            [Lang_No, Lang_Yes]
          ]
        ]
      );
    endif;
  elseif(ChatFlowGet($Server['message']['from']['id'], 'status') == ChatFlowStatus_Chatting):
    if(Equals($Text, Lang_EndChat)):
      $with = ChatFlowGet($Server['message']['from']['id'], 'with');
      Send($Server['message']['from']['id'], Lang_ChatEnded);
      Send($with, Lang_ChatEnded);
      ChatFlowDel($Server['message']['from']['id']);
      ChatFlowDel($with);
    else:
      Send(ChatFlowGet($Server['message']['from']['id'], 'with'), $Text);
      ChatFlowSet($Server['message']['from']['id'], 'time', '');
    endif;
  endif;
endif;
ChatFlowSave();