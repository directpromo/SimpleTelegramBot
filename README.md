# TelegramBot

This bot just anwser  the commands with the txt file contents.

## Dependencies

This bot use the Webhook tecnology. A web server with HTTPS URL are required.

## Commands

This commands are use in the Telegram chat with your bot.
To use them, put your ID in the "DebugId" constant, or in "admins.json" file (JSON format).
To get your ID, send a message to bot saying "my id".

### /list
List files in the directory

### /get
Get the file content (/get start.txt)

### /set
Create or edit a file (/set start.txt Hi everybody!)

### /del
Delete a file (/del start.txt)

### /cmdget
Get the commands list

### /cmdset
Set the new commands (/cmdset cmd1 Do the thing 1)


## Simple guide to install this bot:

1) Get a bot API (if you dont have yet) with @botfather in Telegram;
2) Put this token in config file;
3) Rename the telegram.php with a random name, to avoid attacks. The Telegram API manual sugest to use the token.
4) Register the WebHook acessing you file in the web with parameter "?a=HookSet" (https://yoursite.com/telegram_fcgb8fg8aba8g.php?a=HookSet)
5) Done.
