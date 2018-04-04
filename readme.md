## MultiDb module for Codeception

This module based on original Db module

#### Installation
```
composer require iamdevice/codeception-multidb
```

Config example
```
modules:
   enabled:
     - MultiDb
   config:
     MultiDb:
       connections:
         masterDb:
           dsn: 'mysql:host=localhost;port=3306;dbname=database'
           user: 'username'
           password: 'password'
           primary: true
           dump: ''
           populate: true
           cleanup: false
           reconnect: true
         slaveDb:
           dsn: 'mysql:host=localhost;port=3307;dbname=database'
           user: 'username'
           password: 'password'
           dump: ''
           populate: true
           cleanup: false
           reconnect: true
 ```
 
 Before actions with base you need to select Db like
 
 ```
 $I->amConnectedToDb('primary')
 ```
 
 or you must define one of the connections as primary with
 
 ```
 primary: true
 ```
 
 Aleksandr Kozhevnikov &copy; 2017