# TelegramBot

This bot replys the commands with a TXT file content. To create a new reply/command, just create the TXT file. The name is the command and the content is the reply. You can put a picture with the text, just put a PNG image with the command name.

The Telegram command list can be set with the command /cmdset.

It also has a chat flow system, to users chat with attendants.

## Chat flow

To manage attendants list, edit the file commands/chatflow.json (JSON format).

Eg: {"roger":123456789,"sidney":741852963}

## Dependencies

This bot use the Webhook tecnology. A web server with HTTPS URL are required.
- PHP >= 7.4;
- OpenSSL extension;
- Curl extension;

## Simple guide to install this bot:

1) Get a bot API (if you dont have yet) with @botfather in Telegram;
2) Put this token in config file;
3) Rename the telegram.php with a random name, to avoid attacks. The Telegram API manual sugest to use the token.
4) Register the WebHook acessing you file in the web with parameter "?a=HookSet" (https://yoursite.com/telegram_fcgb8fg8aba8g.php?a=HookSet)
5) Done.

## Commands

This commands are use in the Telegram chat with your bot.
To use them, put your ID in the "admins.json" file (JSON format).
To get your ID, send a message to bot saying "my id".

### /list
List files in the directory

### /get
Get the file content (/get start.txt)

### /set
Create or edit a file (/set start.txt Hi everybody!)

### /del
Delete a file (/del start.txt)

### /ren
Rename a file (/ren 10.png start.png)

### /cmdget
Get the commands list

### /cmdset
Set the new commands (/cmdset cmd1 Do the thing 1)
