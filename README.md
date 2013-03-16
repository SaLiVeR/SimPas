# SimPas Docs

SimPas it's a simple "pastebin" script on GNU v2 licence powered by PHP and additional libraries like **Twig** (templates engine), **GeSHi** (syntax highlighter), **Boostrap from Twitter**.

[Full licence](https://github.com/Macsch15/SimPas/blob/master/LICENCE.txt)

* * *

### Authors

*Maciej* - [@Macsch15](https://twitter.com/Macsch15) / [Macsch15.pl](http://www.macsch15.pl)

* * *

### Demo

[SimPas Demo](http://www.pastebin.macsch15.pl)

* * *


### Configuration files

*   configuration.php
*   db_conf.php

* * *

### Database configuration

File: **db_conf.php**

*   db_port - **Custom MySQL port**
*   db_server - **MySQL Server**
*   db_username - **MySQL Username**
*   db_password - **MySQL Password**
*   db_name - **Database with pastes**

Once you fill in the configuration file with your data, run the page with SimPas.

Application create tables in database.

* * *

### List of settings:

Setting: **true = yes / false = no**

1.  **site_title** - General site title
2.  **site_description** - Site description
3.  **home_url** - Full URL of the site (with a ending forward slash, must be correct)
4.  **favicon_url** - Favicon address (URL) or path
5.  **show\_social\_icons** - Social icons
6.  **social_sites** - If setting "show\_social\_icons" is on a "true" value, then setting allows select a social services, syntax: *key => title*. Full list of the services it's on a page: [ShareThis.com](https://sharethis.com/publishers/services-directory)
7.  **enable\_line\_numbers** - Display line numbers?
8.  **max\_title\_len** - Maximum chars in the field "Title"
9.  **max\_author\_len** - Maximum chars in the field "Author"
10. **default_syntax** - Default syntax highlighter, uppercase or lowercase
11. **show\_additional\_buttons\_in\_error_info** - Display additional buttons in the error ("Home" and "Refresh")
13. **default_lang** - Default language
14. **show_breadcrumb** - Display breadcrumb?
15. **google\_analitycs\_account\_key** - If you've activate "Google Analytics" statistics, put into value you GA ID (f.e. UA-12345678-12) 
15. **google_bots** - Allow bots to index content of the site?
16. **show\_ip\_sender** - Display IP Address paste sender?
17. **show\_ip\_sender\_except\_ip** - If setting "show\_ip\_sender" is on a "true" value, you can put there list of the IP that don't shows on paste page (f.e. IP administrators)
18. **blocked_ip** - IP Addresses with a disallowed access to send new pastes. Wildcard "*" is available
19. **max_len** - Maximum length of the paste (in chars), value "-1" - unlimited
20. **max\_kb\_size** - Maximum size of the paste (in KB), value "-1" - unlimited
21. **my\_global\_message** - Global message. Available colors: "success" = Green; "error" = Red; "info" = Blue
22. **antyflood_status** - Anty-flood
23. **antyflood_time** - If setting "antyflood_status" is on a "true" value, you can put there time limit before next send paste
24. **antyflood\_except\_ip** - If setting "antyflood\_status" "antyflood\_time" is on a "true" value, you can put there list of IP which anty-flood control is inactive (f.e. IP administrators)

#### *Advanced settings. Do not modify if don't you know what are you doing*

1.  **in_dev** - Development mode
2.  **simple_debug** - Debug is active even "in_dev" is off
3.  **extra_debug** - List of database queries 
4.  **show_version** - Show the version of SimPas in footer?
5.  **slow_query** - Mark query as "slow" from X time (only float type) 
6.  **use_furl** - Use FURL?
7.  **errorlog\_prefix\_filename** - First part filename of the error files
8.  **error_reporting** - Error reporting is enabled?
9.  **status** - System is on?
10. **installed** - System is installed? (SimPas oneself changes this value, DO NOT MODIFY)


* * *

## Change language

All strings used in SimPas there are saved in **i18** folder in PHP files.

If you created new language file, you can load translate editing **configuration.php** file:

```
    'default_lang'                          => 'en', 
```

Where  "en" there filename of the translation file **without extension**.
* * *

## Requirements

*   PHP Version: **=< 5.3.x**
*   MySQL (PDO) access

* * *

## Chmod's

*   cache - chmod **777**
*   geshi - chmod **755**
*   i18n - chmod **755**
*   library - chmod **755**
*   static - chmod **755**
*   configuration.php - chmod **666**
*   pdo.php - chmod **644**
*   db_conf.php - chmod **644**
*   index.php - chmod **644**
*   simpas.php - chmod **644**

* * *

### Logic of folders and files:

    -- cache
    -- -- (cache file)
    -- -- (cache file)
    -- -- index.html
    -- -- sqlTable.php
    -- geshi
    -- -- geshi.php
    -- -- geshi
	-- -- -- (list of languages files (syntax highlighter))
    -- i18n
    -- -- pl.php
	-- -- en.php
    -- library
    -- -- Twig
    -- static
    -- -- css
    -- -- -- bootstrap.min.css
    -- -- -- simpas.css
    -- -- img
    -- -- js
    -- -- -- bootstrap.min.js
    -- -- -- jquery-1.9.1.min.js
    -- -- -- simpas.js	
    -- -- template
    -- -- -- globalTemplate.twig.html
    -- .htaccess
    -- configuration.php
    -- pdo.php
    -- db_conf.php
    -- index.php
    -- simpas.php