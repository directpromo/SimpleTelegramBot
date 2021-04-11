<?php
//2021.04.11.00

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
  Send($User, sprintf(Lang_ChatFlow_UsersInWaitList, WaitListCount()), [
    'one_time_keyboard' => true,
    'resize_keyboard' => true,
    'keyboard'=>[
      [Lang_ChatFlow_Cmd_Next]
    ]
  ]);
}