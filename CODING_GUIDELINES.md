# RBS Change v4.0 Coding Guidelines #

## General developement rules ##

### Rule 1 - Writing Classes

* No class should implement the singleton pattern - use shared instances attached to one of the existing DI containers or create one for that purpose !
* Classes should in general only have protected and public members / function - there can be exceptions to that rule but it's highly unlikely that you hit one of those.
* Static methods should be the exception.
* Don't forget the PHP Docs
* Avoid exposing public shortcut getters methods to `ApplicationServices` / `DocumentServices` /  `...` shared instances. You can however of course provide a getter to the entire Services.

## Frontoffice ##

### Rule 1 - No HTML in locale file ###

HTML belongs to the templates :-)





