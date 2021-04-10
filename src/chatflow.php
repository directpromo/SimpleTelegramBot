<?php
//2021.04.10.02

const FlowStatusWaitingResponse = '0';
const FlowStatusChatting = '1';

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

function InAttendiment(int $Attender):bool{
  global $ChatFlow;
  foreach($ChatFlow as $User):
    if($User['status'] === FlowStatusChatting and ($User['Attender'] ?? null) === $Attender):
      return true;
    endif;
  endforeach;
  return false;
}

function GetAnAttendant():int{
  $Attendants = file_get_contents(__DIR__ . '/commands/chatflow.json');
  $Attendants = json_decode($Attendants, true);
  shuffle($Attendants);
  foreach($Attendants as $Attendant):
    if(InAttendiment($Attendant) === false):
      return $Attendant;
    endif;
  endforeach;
}

ChatFlowLoad();
if(ChatFlowGet($Server['message']['from']['id'], 'status') === false):
  Send($Server['message']['chat']['id'], LangWantAttendant, [
    'one_time_keyboard' => true,
    'resize_keyboard' => true,
    'keyboard'=>[
        [LangNo, LangYes]
      ]
    ]
  );
  ChatFlowSet($Server['message']['from']['id'], 'status', FlowStatusWaitingResponse);
elseif(ChatFlowGet($Server['message']['from']['id'], 'status') == FlowStatusWaitingResponse):
  if(strcasecmp($Text, LangYes) === 0):
    $Attendant = GetAnAttendant();

    Send($Server['message']['from']['id'], LangWaitForAttender);
    ChatFlowSet($Server['message']['from']['id'], 'status', FlowStatusChatting);
    ChatFlowSet($Server['message']['from']['id'], 'with', $Attendant);

    Send($Attendant, sprintf(LangWantToChat, $Server['message']['from']['first_name'], LangEndChat));
    ChatFlowSet($Attendant, 'status', FlowStatusChatting);
    ChatFlowSet($Attendant, 'with', $Server['message']['from']['id']);
  elseif(Equals($Text, LangNo)):
    Send($Server['message']['from']['id'], LangDontWaitForAttender);
    ChatFlowDel($Server['message']['from']['id']);
  else:
    Send($Server['message']['chat']['id'], LangWantAttendant, [
      'one_time_keyboard' => true,
      'resize_keyboard' => true,
      'keyboard'=>[
          [LangNo, LangYes]
        ]
      ]
    );
  endif;
elseif(ChatFlowGet($Server['message']['from']['id'], 'status') == FlowStatusChatting):
  if(Equals($Text, LangEndChat)):
    $with = ChatFlowGet($Server['message']['from']['id'], 'with');
    Send($Server['message']['from']['id'], LangChatEnded);
    Send($with, LangChatEnded);
    ChatFlowDel($Server['message']['from']['id']);
    ChatFlowDel($with);
  else:
    Send(ChatFlowGet($Server['message']['from']['id'], 'with'), $Text);
    ChatFlowSet($Server['message']['from']['id'], 'time', '');
  endif;
endif;
ChatFlowSave();