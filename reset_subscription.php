<?php

chdir (dirname(__FILE__));

include 'library.php';

$reset = get_reset_data();
$reset_output = $reset;
reset_data($reset);
shell_exec('service php5-fpm restart');
shell_exec('service nginx restart');

foreach ( $reset['up'] as $key => $value ) {
    $reset_output['up'][$key]['virtualhostconf'] = '';
}

foreach ( $reset['add'] as $key => $value ) {
    $reset_output['add'][$key]['virtualhostconf'] = '';
}

print '<!--'.PHP_EOL;
print print_r($reset_output, -1) . PHP_EOL;
print '-->'.PHP_EOL;

