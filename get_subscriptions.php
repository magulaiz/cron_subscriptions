#!/usr/bin/php
<?php
/*
 * [created] => 2017-09-24T11:15:35+02:00
 * [expiration] => 2017-10-24T11:15:35+02:00
 * [sid] => 1
 * [gid] => 3
 * [uid] => 3
 * [eid] => 9
 * [core] => corev99
 * [www_root] => /path/to/root/
 * [www_path] => subpath/
 * [www_domain] => domain.tld
 * [www_subdomain] => subdomain.
 * [correo] => mail@example.com
 * [www_config] => longtext

 * const FLAG_USER_EXISTS = 1;
 * const FLAG_DB_EXISTS = 2;
 * const FLAG_DRUPAL_CONFIG = 4;
 * const FLAG_WWW_AVAILABLE = 8;
 * const FLAG_WWW_ENABLED = 16;
 * const FLAG_WWW_ONLINE = 32;
 * 
 * $sflags->userSetExists(TRUE|FALSE); 
 * $sflags->dbSetExists(TRUE|FALSE);
 * $sflags->wwwSetAvailable(TRUE|FALSE);
 * $sflags->wwwSetEnabled(TRUE|FALSE);
 * $sflags->wwwSetOnline(TRUE|FALSE);
 *   
 * userExists()
 * dbExists()
 * wwwIsAvailable()
 * wwwIsEnabled()
 * wwwIsOnline()
 * 
 */

require('subscriptionFlags.php');

require_once './random_compat/lib/random.php';

$config = [
    'sites_available_path' => '/etc/nginx/subscriptions/sites-available/',
    'sites_enabled_path' => '/etc/nginx/subscriptions/sites-enabled/',
    'subscriptions_url' => 'http://formacion.bierzo.online/rest/subscriptions.json',
    'subscriptions_users_url' => 'http://formacion.bierzo.online/rest/subscriptions_users_service.json',
    'subscriptions_groups_url' => 'http://formacion.bierzo.online/rest/subscriptions_groups_service.json',
    'subscriptions_environments_url' => 'http://formacion.bierzo.online/rest/subscriptions_environments_service.json',
    'main_subdomain' => 'formacion',
    'subdomain_prefix' => 'subscription'
];

$SFlags = new subscriptionFlags();

$subscriptions = get_json_data($config['subscriptions_url']);

$host_subscriptions = get_host_subscriptions();

$host_subscriptors = get_host_subscriptors('subscriptors');

$drupal_subscriptors = get_drupal_subscriptors($subscriptions);

foreach ($subscriptions as $key => $data) {
    $subscription_data[$data['sid']] = $data;
}

foreach ($host_subscriptions as $key => $data) {
    $subscription_data[$data['sid']]['active'] = FALSE;
    $subscription_data[$data['sid']] += $data;
}

foreach ($subscriptions as $key => $data) {
    unset($subscription_data[$data['sid']]);
    $subscription_data[$data['sid']]['active'] = TRUE;
    $subscription_data[$data['sid']] += $data;
}
ksort($subscription_data);

foreach ($subscription_data as $sid => $subscription) {

        ${'SFlags' . $sid} = new subscriptionFlags();
        $sflags = ${'SFlags' . $sid};
        set_status($sid, $sflags);
        $username = $config['subdomain_prefix'] . $sid;
        $full_domain = $config['subdomain_prefix'] . $sid;
        if (!empty($subscription['www_subdomain'])) {
            $full_domain .= '.' . $subscription['www_subdomain'];
        }
        $full_domain .= '.' . $subscription['www_domain'];
        $www_root = $subscription['www_root'];
        $www_path = $subscription['www_path'];
        $home_dir = $subscription['www_root'] . $subscription['www_path'] . $full_domain;
        $www_subdomain = $subscription['www_subdomain'];
        $pass = random_str();
        print PHP_EOL;

        if ($subscription['active'] == 1) {

            $core = $subscription['core'];

            if (!$sflags->userExists()) {
                print 'No existe usuario' . PHP_EOL;
                shell_exec('./server_user_add.sh ' . ' ' . $username . ' ' . $home_dir . ' ' . $pass);
                shell_exec('php smtpmail.phps ' . 'info@bierzo.online' . ' ' . $username . ' ' . $pass . ' ' . $full_domain);
            }
            if (!$sflags->dbExists()) {
                print 'No existe DB' . PHP_EOL;
                shell_exec('./db_user_add.sh ' . ' ' . $username . ' ' . $pass);
            }
            if (!$sflags->drupalIsConfig()) {
                print 'Drupal Está configurado' . PHP_EOL;
                drupal_site_add($core, $www_root, $www_path, $full_domain, $username);
            }
            if (!$sflags->wwwIsAvailable()) {
                print 'No esta disponible' . PHP_EOL;
                $tpl = $subscription['www_config'];
                www_set_config($tpl, $full_domain, $www_root);
            }
            if (!$sflags->wwwIsEnabled()) {
                print 'No esta activo' . PHP_EOL;
                www_active_config($full_domain);
            }
            if (!$sflags->wwwIsOnline()) {
                print 'No está online' . PHP_EOL;
                www_set_dns($sid, $www_subdomain);
            }
        } else {
            print 'Suscripción inactiva' . PHP_EOL;
            shell_exec('userdel -r ' . $username . ' >/dev/null 2>&1');
            shell_exec('./db_user_del.sh ' . ' ' . $username);
            www_unset_config($full_domain);
            www_deactivate_config($full_domain);
            www_unset_dns($sid, $www_subdomain);
        }
        set_status($sid, $sflags);
        $subscription_data[$sid]['status'] = strval($sflags);

}

shell_exec('service nginx reload');
print_r($subscription_data);
print_r($host_subscriptions);

function get_json_data($url) {
    $content = file_get_contents($url);
    $output = json_decode($content, true);
    return $output;
}

function get_item($datasource, $key, $value) {
    foreach ($datasource as $data) {
        if ($data[$key] == $value) {
            return $data;
        }
    }
    return False;
}

function get_data_sid($sid) {
    global $subscription_data;
    $subscription_item = get_item($subscription_data, 'sid', $sid);
    if (is_array($subscription_item)) {
        return $subscription_item;
    } else {
        FALSE;
    }
}

function get_full_domain($sid) {
    global $config;
    global $subscription_data;
    $subscription_item = $subscription_data[$sid];
    if (!empty($subscription_item['www_subdomain'])) {
        $www_subdomain = '.' . $subscription_item['www_subdomain'];
    }
    $full_domain = $config['subdomain_prefix'] . $sid . $www_subdomain . '.' . $subscription_item['www_domain'];
    return $full_domain;
}

function set_user_exists($sid, $sflags) {
    global $config;
    $user = $config['subdomain_prefix'] . $sid;
    posix_getpwnam($user);
    if (!posix_getpwnam($user)) {
        $sflags->userSetExists(FALSE);
    } else {
        $sflags->userSetExists(TRUE);
    }
}

function server_user_add($username, $dir, $pass) {
    shell_exec('./server_user_add.sh ' . ' ' . $username . ' ' . $dir . ' ' . $pass);
}

function set_status($sid, $sflags) {
    global $config;
    global $subscription_data;

    $full_domain = get_full_domain($sid);

    #set_user_exists($sid, $sflags);
    $user = $config['subdomain_prefix'] . $sid;
    posix_getpwnam($user);
    if (!posix_getpwnam($user)) {
        $sflags->userSetExists(FALSE);
    } else {
        $sflags->userSetExists(TRUE);
    }

    #set_db_exists($sid, $sflags);
    $db = $config['subdomain_prefix'] . $sid;
    $conn = @mysqli_connect('localhost', 'root', '1do9re6na5', $db);
    if (!$conn) {
        $sflags->dbSetExists(FALSE);
    } else {
        $sflags->dbSetExists(TRUE);
    }
    @mysqli_close($conn);

    #set_drupal_config($sid, $sflags);
    $data = @get_item($subscription_data, 'sid', $sid);
    $drupal_config_file = $data['www_root'] . $data['www_path'] . $full_domain . '/' . 'settings.php';
    if (file_exists($drupal_config_file)) {
        $sflags->drupalSetConfig(TRUE);
    } else {
        $sflags->drupalSetConfig(FALSE);
    }

    #set_www_status($sid, $sflags);
    if ($socket = fsockopen($full_domain, 80, $errno, $errstr, 30)) {
        $sflags->wwwSetOnline(TRUE);
        fclose($socket);
    } else {
        $sflags->wwwSetOnline(FALSE);
    }

    #set_www_config_status($sid, $sflags);
    $www_config_file = $config['sites_available_path'] . $full_domain;
    if (file_exists($www_config_file)) {
        $sflags->wwwSetAvailable(TRUE);
    } else {
        $sflags->wwwSetAvailable(FALSE);
    }
    $www_config_file = $config['sites_enabled_path'] . $full_domain;
    if (file_exists($www_config_file)) {
        $sflags->wwwSetEnabled(TRUE);
    } else {
        $sflags->wwwSetEnabled(FALSE);
    }
}

function get_subscription_id($domain) {
    global $config;
    $zones = explode('.', $domain);
    return substr($zones[0], strlen($config['subdomain_prefix']));
}

function get_host_subscriptors($group_name) {
    $members = shell_exec('members ' . $group_name . '  2>&1');
    $array = explode(' ', $members);
    if ($array[0] <> 'members:') {
        return $array;
    }
    return FALSE;
}

function get_drupal_subscriptors($subscriptions) {
    global $config;
    foreach ($subscriptions as $key => $subscription) {
        $output[] = $config['subdomain_prefix'] . $subscription['sid'];
    }
    return $output;
}

function get_host_subscriptions($domain = Null) {
    global $config;
    $sites_available = array_slice(scandir($config['sites_available_path']), 2);
    $sites_enabled = array_slice(scandir($config['sites_enabled_path']), 2);
    $host_subscriptions['sites_available'] = $sites_available;
    $host_subscriptions['sites_enabled'] = $sites_enabled;

    foreach ($host_subscriptions['sites_available'] as $key => $data) {
        $sid = get_subscription_id($data);
        $owner = posix_getpwuid(fileowner($config['sites_available_path'] . $data));
        $owner_group = posix_getgrgid($owner['gid']);
        $www_config_file = $config['sites_available_path'] . $data;
        $content = file_get_contents($www_config_file);
        preg_match('/root (.*?);/', $content, $match);
        $www_root = $match[1];

        $zones = array_reverse(explode('.', $data));
        $www_domain = $zones[1] . '.' . $zones[0];
        array_shift($zones);
        array_shift($zones);
        $prefix = array_pop($zones);
        $www_subdomain = implode('.', array_reverse($zones));
        $tmp[] = ['sid' => $sid,
            'www_root' => $www_root,
            'www_path' => 'sites/',
            'www_domain' => $www_domain,
            'www_subdomain' => $www_subdomain,
            'full_domain' => $data,
            'group' => $owner_group['name'],
            'owner' => $owner['name']
        ];
    }
    $host_subscriptions = $tmp;
    $output = $tmp;
    if (!is_null($domain)) {
        $output = 0;
        foreach ($host_subscriptions as $key => $file) {
            if ($file['full_domain'] == $domain) {
                $file_data = stat($config['sites_available_path'] . $domain);
                $output = array();
                $output[] = array(
                    'ful_domain' => $domain,
                    'user' => $file_data['uid']
                );
                return $output;
            }
        }
    }
    return $output;
}

function drupal_site_add($core, $www_root, $www_path, $full_domain, $username) {
    $default_dir = $www_root . $www_path . 'default';
    $site_dir = $www_root . $www_path . $full_domain;
    mkdir($site_dir);
    switch ($core) {
        case 'd7x':
            $source = $default_dir . '/default.settings.php';
            $target = $site_dir . '/settings.php';
            copy($source, $target);
            chown($target, $username);
            chgrp($target, 'subscriptors');
            break;
        case 'd8x':
            $source = $default_dir . '/default.services.yml';
            $target = $site_dir . '/services.yml';
            copy($source, $target);
            chown($target, $username);
            chgrp($target, 'subscriptors');
            $source = $default_dir . '/default.settings.php';
            $target = $site_dir . '/settings.php';
            copy($source, $target);
            chown($target, $username);
            chgrp($target, 'subscriptors');
            break;
    }
}

function www_set_config($tpl, $full_domain, $www_root) {
    global $config;
    $vhostconf = str_replace('__SERVER_NAME__', $full_domain, $tpl);
    $vhostconf = str_replace('__ROOT__', $www_root, $vhostconf);
    $vhostconf = html_entity_decode($vhostconf);
    $vhostconf = str_replace("&#039;", "'", $vhostconf);

    file_put_contents($config['sites_available_path'] . $full_domain, $vhostconf);
}

function www_unset_config($full_domain) {
    global $config;
    unlink($config['sites_available_path'].$full_domain);
}

function www_deactivate_config($full_domain) {
    global $config;
    unlink($config['sites_enabled_path'].$full_domain);
}

function www_active_config($full_domain) {
    global $config;
    $subscription_slink = symlink($config['sites_available_path'] . $full_domain, $config['sites_enabled_path'] . $full_domain);
}

function www_set_dns($sid, $www_subdomain) {
    global $config;
    if ($www_subdomain) {
        $www_subdomain = '.' . $www_subdomain;
    }
    shell_exec('php ovh_create_dns_record/ovh_create_dns_record ' . $config['subdomain_prefix'] . $sid . $www_subdomain);
}

function www_unset_dns($sid, $www_subdomain) {
    global $config;
    if ($www_subdomain) {
        $www_subdomain = '.' . $www_subdomain;
    }
    shell_exec('php ovh_create_dns_record/ovh_delete_dns_record ' . $config['subdomain_prefix'] . $sid . $www_subdomain);
}

function random_str($length = 8, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}
