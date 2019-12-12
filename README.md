# README

I made this bot reusing the code for @comunepulito from @piersoft in order to have a custom application
to report points in Bruxelles streets that are perceived as dangerous.
This project is related to the Filter Cafe Filtre of Bruxelles for health and well living of people in the city

## how to configure the system

In localhost is possible to launch
php start.php 'sethook' to set start.php as webhook
php start.php 'removehook' to remove start.php as webhook
php start.php 'getupdates' to run getupdates.php

After setup webhook is possible to use telegram managed by webhost

## how to use the system

- Make a Telegram Bot
- Send Location to it
- Reply to bot with a text description
- All data are sent in the database and than convert in CSV format
- Data can mapped now

To use the application use "start.php getupdates" for manual execution. "start.php sethook" for Telegram webhook execution.

thanks to my friend Matteo Tempestini.

Good Luck!
