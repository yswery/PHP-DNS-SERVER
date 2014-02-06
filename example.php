<? 

require "dns_server.class.php"; 


// dns records
$ds_records = array(
    'test.com' => array(
        'A' => '111.111.111.111',
        'MX' => '112.112.112.112',
        'NS' => 'ns1.test.com',
        'TXT' => 'Some text.'
    ),
    'test2.com' => array(
        // allow multiple records of same type
        'A' => array(
            '111.111.111.111',
            '112.112.112.112'
        )
    )
);

// Creating a new instance of our class
$dns = new PHP_DNS_SERVER($ds_records);

// Starting our DNS server
$dns->start();


?>
