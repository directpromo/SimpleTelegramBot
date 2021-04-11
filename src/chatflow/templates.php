<?php
//2021.04.11.03

function TmpBtnYesNo():array{
  return [
    'one_time_keyboard' => true,
    'resize_keyboard' => true,
    'keyboard'=>[
      [Lang_No, Lang_Yes]
    ]
  ];
}

function TmpBtnRemove():array{
  return ['remove_keyboard' => true];
}

function TmpUsersInWaitList(int $User):void{
  $count = WaitListCount();
  if($count === 0):
    $kb = TmpBtnRemove();
  else:
    $kb = [
      'one_time_keyboard' => true,
      'resize_keyboard' => true,
      'keyboard'=>[
        [Lang_ChatFlow_Cmd_Next]
      ]
    ];
  endif;
  Send($User, sprintf(Lang_ChatFlow_UsersInWaitList, $count, Lang_ChatFlow_Cmd_Next), $kb);
}