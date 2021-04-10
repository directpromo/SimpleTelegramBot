<?php
//2021.04.10.00

function AccentInsensitive(string $Text):string{
  return strtr(
    $Text,
    ['ã' => 'a']
  );
}

function Equals(string $Text1, string $Text2):bool{
  $Text1 = AccentInsensitive($Text1);
  $Text2 = AccentInsensitive($Text2);
  if(strcasecmp($Text1, $Text2) === 0):
    return true;
  else:
    return false;
  endif;
}