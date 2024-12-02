# php-winscp-ini-extractor
Simple php Class to extract winscp.ini file


## Usage
```php
require_once('WinSCPParser');

$parser = new WinSCPParser('winscp.ini');
$items = $parser->getItems();
print_r($items);
```

Output is like :
```
Array
(
    [test1] => Array
        (
            [0] => Array
                (
                    [HostName] => 1.2.3.4
                    [PortNumber] => 23
                    [UserName] => 'user'
                    [Password] => 'pass'
                    [Name] => 'user@1.2.3.4'
                    [Protocol] => Protocol.Sftp
                )

        )

    [test2] => Array
        (
            [0] => Array
                (
                    [HostName] => 5.2.2.3
                    [PortNumber] => 6070
                    [UserName] => 'debian'
                    [Password] => 'abcd'
                    [Name] => 'debian@5.2.2.3'
                    [Protocol] => Protocol.Sftp
                )

        )

    [test2/test3] => Array
        (
            [0] => Array
                (
                    [HostName] => 1.2.2.2
                    [PortNumber] => 6565
                    [UserName] => 'ubuntu'
                    [Password] => '123456'
                    [Name] => 'user@1.2.2.2'
                    [Protocol] => Protocol.Sftp
                )

            [1] => Array
                (
                    [HostName] => 1.2.3.5
                    [PortNumber] => 25
                    [UserName] => 'user2'
                    [Password] => 'pass1'
                    [Name] => 'user2@1.2.3.5 xx'
                    [Protocol] => Protocol.Sftp
                )

        )

)
```
