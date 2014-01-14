Password Manager
-------------------------

If u won't to lose your passwords or any secret data, that App is for you.
You should use it only for your needs.
Example - https://pwd.assorium.ru/

Installation
-------------------------
You need to install PhalconPHP extension http://phalconphp.com/en/, PHP APC extension.

Copy this git project. Insert database structure from db folder. Edit **config_default.ini** file and rename it to **config.ini**

**You should check all the fields in config file.**

DB. All the Database settings
- host: host path
- dbname: database name
- user: user name
- password: password
- charset
- persistent

Captcha parameters. Get your keys on http://www.google.com/recaptcha
- pub: public key
- priv: private key

Application settings
- base_uri: Your app uri
- static_salt: string to be static salt
- suffix: suffix for cache
- hash_rounds: times to hash each secret. **Change only one time!**
- session_lifetime:: 0 for short session or >0 for static lifetime
- debug: show Exceptions by 1
- cache_apc: 1 - cache to APC (needed extension), else FILES, give permissions to write for /tmp/cache/ directory
