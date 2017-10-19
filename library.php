<?php
require_once './random_compat/lib/random.php';
$config = [
    'sites_available_path' => '/etc/nginx/subscriptions/sites-available/',
    'sites_enabled_path' =>   '/etc/nginx/subscriptions/sites-enabled/',
    'subscriptions_url' => 'http://formacion.bierzo.online/rest/subscriptions_service.json',
    'subscriptions_users_url' => 'http://formacion.bierzo.online/rest/subscriptions_users_service.json',
    'subscriptions_groups_url' => 'http://formacion.bierzo.online/rest/subscriptions_groups_service.json',
    'subscriptions_environments_url' => 'http://formacion.bierzo.online/rest/subscriptions_environments_service.json',
    'main_subdomain' => 'formacion',
    'subdomain_prefix' => 'subscription'
];

$host_subscriptions = get_host_subscriptions();
$config['data']['host_subscriptions'] = $host_subscriptions;
$subscriptions = get_cms_subscriptions();
$config['data']['subscriptions'] = $subscriptions;
$users = get_cms_subscriptions_users();
$config['data']['users'] = $users;
$groups = get_cms_subscriptions_groups();
$config['data']['groups'] = $groups;
$environments = get_cms_subscriptions_environments();
$config['data']['environments'] = $environments;

/*
$config['data'] = array(
    'host_subscriptions' => $host_subscriptions,
    'subscriptions' => $subscriptions,
    'users' => $users,
    'groups' => $groups,
    'environments' => $environments
);
*/
function get_json_data($url) {
    $content = file_get_contents($url);
    $output = json_decode($content, true);
    return $output;
}

function get_cms_subscriptions($domain = null) {
    global $config;
    if (!is_null($domain)) {
        $output = array();
        foreach ($config['data']['subscriptions'] as $cms_subscription) {
            if ($domain == get_subscription_domain($cms_subscription['id'])) {
                $output[] = array('domain' => $domain);
                return $output;
            }
        }
    } else {
        $subscriptions = get_json_data($config['subscriptions_url']);
        return $subscriptions;
    }
}

function get_subscription_domain($sid) {
    global $config;
    #print print_r($config['data']['subscriptions'],-1).PHP_EOL;
    $subscription = get_item($config['data']['subscriptions'], 'id', $sid);
    $gid = $subscription['gid'];
    $environment = get_group_environment($gid);
    
    if ($environment['subdomain']) {
        $environment_subdomain = '.'.$environment['subdomain'];
    } else {
        $environment_subdomain ='';
    }
    
    $domain = $config['subdomain_prefix'] . $sid . $environment_subdomain . '.' . $environment['domain'];
    return $domain;
}

function get_cms_subscriptions_users() {
    global $config;
    $users = get_json_data($config['subscriptions_users_url']);
    foreach ($users as $key => $user) {
        $subscription = get_item($config['data']['subscriptions'], 'uid', $user['id']);
        $users[$key]['subscriptions'][] = $subscription['id']; 
    }
    
    return $users;
}

function get_cms_subscriptions_groups() {
    global $config;
    return get_json_data($config['subscriptions_groups_url']);
}

function get_cms_subscriptions_environments() {
    global $config;
    return get_json_data($config['subscriptions_environments_url']);
}

function get_item($datasource, $key, $value) {
    
    foreach ($datasource as $data) {
        if ($data[$key] == $value) {
            return $data;
        }
    }

    return False;
}

function get_group_environment($gid) {
    global $config;
    $group = get_item($config['data']['groups'], 'id', $gid);
    $eid = $group['eid'];
    $environment = get_item($config['data']['environments'], 'id', $eid);
    return $environment;
}

function get_host_subscriptions($domain = Null) {
    global $config;
    $sites_available = array_slice(scandir($config['sites_available_path']), 2);
    $sites_enabled = array_slice(scandir($config['sites_enabled_path']), 2);
    $host_subscriptions['sites_available'] = $sites_available;
    $host_subscriptions['sites_enabled'] = $sites_enabled;
    $tmp = array();
    foreach ($host_subscriptions['sites_available'] as $key => $data) {
        $owner = posix_getpwuid(fileowner($config['sites_available_path'].$data));
        $owner_group = posix_getgrgid($owner['gid']);
        $tmp[] = ['id' => get_subscription_id($data),
            'domain' => $data,
            'group' => $owner_group['name'],
            'owner' =>  $owner['name']
            ];
    }
    $host_subscriptions = $tmp;
    $output = $tmp;
    if (!is_null($domain)) {
        $output = 0;
        foreach ($host_subscriptions as $key => $file) {
            if ($file['domain'] == $domain) {
                $file_data = stat($config['sites_available_path'].$domain);
                $output = array();
                $output[] = array(
                    'domain' => $domain,
                    'user' => $file_data['uid']
                        );
                return $output;
            }
        }
    }
    return $output;
}

function get_subscription_id($domain) {
    global $config;
    $zones = explode('.', $domain);
    return substr($zones[0], strlen($config['subdomain_prefix']));
}

function get_reset_data(){
    global $config;
        #$reset['config'] = $config;
        foreach ($config['data']['subscriptions'] as $key => $subscription) {

        $environment = get_group_environment($subscription['gid']);
        $subdomain = $config['subdomain_prefix'] . $subscription['id'];
        #$domain = $subdomain . '.' . $environment['domain'];
        if ($environment['subdomain'] !== '') {
            $environment_subdomain = '.'.$environment['subdomain'];
        }
        $domain = $subdomain . $environment_subdomain . '.' . $environment['domain'];
        $item = [
            'id' => $subscription['id'],
            'core' => $environment['core'],
            'main_subdomain' => $environment['subdomain'],
            'subdomain' => $subdomain,
            'domain' => $domain,
            'root' => $environment['root'],
            'path' => $environment['path'],
            'mail_from' => $environment['mail'],
            'virtualhostconf' => $environment['virtualhostconf']
            ];    
        $group = get_item($config['data']['groups'], 'id', $subscription['gid']);
        $item['group'] = $group['name'];
        $user = get_item($config['data']['users'], 'id', $subscription['uid']);
        $item['user'] = $user['user'];
        $item['mail_to'] = $user['mail'];

        $host_subscription = get_host_subscriptions($domain);
        if (is_array($host_subscription)) {
            $reset['up'][] = $item;
        } else {
            $reset['add'][] = $item;
        }
    }

    foreach ($config['data']['host_subscriptions'] as $key => $data) {
        $cms_domain = get_cms_subscriptions($data['domain']);

        if (sizeof($cms_domain) == 0) {

            $reset['del'][] = $data;
        }
    }
    if (sizeof($reset > 0)) {
        return $reset;
    }
    return False;
}

function reset_data($data) {

    global $config;
    
    if (isset($data['up'])) {
        foreach($data['up'] as $key => $value) {
            if (!is_link($config['sites_enabled_path'] . $value['domain']) || readlink($config['sites_enabled_path'] . $value['domain']) !== $config['sites_available_path'] . $value['domain']) {
                $subscription_slink = symlink($config['sites_available_path'] .$value['domain'], $config['sites_enabled_path'] . $value['domain']);
            }
            /*
            server_user_del($value['subdomain']);
            
            $pass = random_str();
            $home_dir = $value['root'].$value['path'].$value['domain'];
            server_user_add($value['subdomain'], $home_dir, $pass );
            
            db_user_add($value['subdomain'], $pass);
            
            $virtualhostconf = html_entity_decode($virtualhostconf);            
            $virtualhostconf = str_replace('${domain}', $value['domain'], $value['virtualhostconf']);
            $virtualhostconf = str_replace('${www_root}', $value['root'], $virtualhostconf);
            $virtualhostconf = str_replace("&#039;", "'", $virtualhostconf);

            file_put_contents($config['sites_available_path'].$value['domain'], $virtualhostconf);
            drupal_site_add($value['core'], $value['root'], $value['path'], $value['domain']);
            if($value['main_subdomain']) {
                $main_subdomain = '.' . $value['main_subdomain'];
            }
            shell_exec('php ovh_create_dns_record/ovh_create_dns_record '.$config['subdomain_prefix'].$value['id'].$main_subdomain);
            shell_exec('php smtpmail.phps '.$value['mail_from'].' '.$value['subdomain'].' '.$pass.' '.$value['domain']);
             */
        }
    }
    $sites_available = array_slice(scandir($config['sites_available_path']), 2);
    $sites_enabled = array_slice(scandir($config['sites_enabled_path']), 2);
    $sites_enabled = array_diff($sites_enabled, $sites_available);
    foreach ($sites_enabled as $domain) {
        unlink($config['sites_enabled_path'].$domain);
    }
    
    if (isset($data['del'])) {
        foreach ($data['del'] as $key => $value) {
            unlink($config['sites_available_path'].$value['domain']);
            unlink($config['sites_enabled_path'].$value['domain']);
            server_user_del($config['subdomain_prefix'].$value['id']);
        }
    }
    
    if (isset($data['add'])) {
        foreach ($data['add'] as $key => $value) {
            $subscription_file = fopen($config['sites_available_path'] . $value['domain'], 'w');
            $subscription_slink = symlink($config['sites_available_path'] . $value['domain'], $config['sites_enabled_path'] . $value['domain']);
            
            $pass = random_str();
            $home_dir = $value['root'].$value['path'].$value['domain'];
            server_user_add($value['subdomain'], $home_dir, $pass );
            
            db_user_add($value['subdomain'], $pass);
            
            $virtualhostconf = html_entity_decode($virtualhostconf);            
            $virtualhostconf = str_replace('${domain}', $value['domain'], $value['virtualhostconf']);
            $virtualhostconf = str_replace('${www_root}', $value['root'], $virtualhostconf);
            $virtualhostconf = str_replace("&#039;", "'", $virtualhostconf);

            file_put_contents($config['sites_available_path'].$value['domain'], $virtualhostconf);
            drupal_site_add($value['core'], $value['root'], $value['path'], $value['domain'], $config['subdomain_prefix'].$value['id']);
            if($value['main_subdomain']) {
                $main_subdomain = '.' . $value['main_subdomain'];
            }
            shell_exec('php ovh_create_dns_record/ovh_create_dns_record '.$config['subdomain_prefix'].$value['id'].$main_subdomain);
            shell_exec('php smtpmail.phps '.$value['mail_from'].' '.$value['subdomain'].' '.$pass.' '.$value['domain']);
        }        
    }
    if (isset($data['add']) || isset($data['del']) || isset($data['up'])) {
        return True;
    }
    return False;
}

function random_str($length = 8, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}
function server_user_add($username, $dir, $pass) {
    shell_exec('./server_user_add.sh '.' '.$username.' '.$dir.' '.$pass);
}

function server_user_del($username) {
    shell_exec('./server_user_del.sh'.' '.$username);
}

function db_user_add($dbuser,$pass) {
    shell_exec('./db_user_add.sh '.' '.$dbuser.' '.$pass);
}

function drupal_site_add($core, $root, $path, $domain, $owner) {
    $default_dir = $root.$path.'default';
    $site_dir = $root.$path.$domain;
    mkdir($site_dir);
    switch ($core) {
        case 'd7x':
            $source = $default_dir.'/default.settings.php';
            $target = $site_dir.'/settings.php';
            copy($source, $target);
            chown($target, $owner);
            chgrp($target, 'subscriptors');
            break;
        case 'd8x':
            $source = $default_dir.'/default.services.yml';
            $target = $site_dir.'/services.yml';
            copy($source, $target);
            chown($target, $owner);
            chgrp($target, 'subscriptors');
            $source = $default_dir.'/default.settings.php';
            $target = $site_dir.'/settings.php';
            copy($source, $target);
            chown($target, $owner);
            chgrp($target, 'subscriptors');
            break;
    }
    
}