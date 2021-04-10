<?php
//2021.04.10.01

const FlowStatusWaitingResponse = '0';
const FlowStatusChatting = '1';

function FlowLoad():void{
  global $Flow;
  $Flow = file_get_contents(__DIR__ . '/chatflow.json');
  $Flow = json_decode($Flow, true);
}

function FlowSave():void{
  global $Flow;
  file_put_contents(__DIR__ . '/chatflow.json', json_encode($Flow));
}

function FlowSet(int $UserId, string $Field, string $Value):void{
  global $Flow;
  $Flow[$UserId][$Field] = $Value;
  $Flow[$UserId]['time'] = time();
}

/**
 * @return string|false
 */
function FlowGet(int $UserId, string $Field){
  global $Flow;
  return $Flow[$UserId][$Field] ?? false;
}

function FlowDel(int $UserId):void{
  global $Flow;
  unset($Flow[$UserId]);
}

function InAttendiment(int $Attender):bool{
  global $Flow;
  foreach($Flow as $User):
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

FlowLoad();
if(FlowGet($Server['message']['from']['id'], 'status') === false):
  Send($Server['message']['chat']['id'], LangWantAttendant, [
    'one_time_keyboard' => true,
    'resize_keyboard' => true,
    'keyboard'=>[
        [LangNo, LangYes]
      ]
    ]
  );
  FlowSet($Server['message']['from']['id'], 'status', FlowStatusWaitingResponse);
elseif(FlowGet($Server['message']['from']['id'], 'status') == FlowStatusWaitingResponse):
  if(strcasecmp($Text, LangYes) === 0):
    $Attendant = GetAnAttendant();

    Send($Server['message']['from']['id'], LangWaitForAttender);
    FlowSet($Server['message']['from']['id'], 'status', FlowStatusChatting);
    FlowSet($Server['message']['from']['id'], 'with', $Attendant);

    Send($Attendant, sprintf(LangWantToChat, $Server['message']['from']['first_name'], LangEndChat));
    FlowSet($Attendant, 'status', FlowStatusChatting);
    FlowSet($Attendant, 'with', $Server['message']['from']['id']);
  elseif(Equals($Text, LangNo)):
    Send($Server['message']['from']['id'], LangDontWaitForAttender);
    FlowDel($Server['message']['from']['id']);
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
elseif(FlowGet($Server['message']['from']['id'], 'status') == FlowStatusChatting):
  if(Equals($Text, LangEndChat)):
    $with = FlowGet($Server['message']['from']['id'], 'with');
    Send($Server['message']['from']['id'], LangChatEnded);
    Send($with, LangChatEnded);
    FlowDel($Server['message']['from']['id']);
    FlowDel($with);
  else:
    Send(FlowGet($Server['message']['from']['id'], 'with'), $Text);
    FlowSet($Server['message']['from']['id'], 'time', '');
  endif;
endif;
FlowSave();