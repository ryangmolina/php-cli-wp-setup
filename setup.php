<?php
/**
 * php setup.php --path C:/xampp/htdocs/testwp --theme_mods=bloggers-lite.json --post=post.json --config=config.json
 */
require_once 'utils.php';


/**
 * GET SCRIPT ARGUMENTS
 */
$longsopts = [
    "path:",
	"config::",
    "post::",
    "user::",
	"theme_mods::"
];
$options = getopt('', $longsopts);


/**
 * CONFIG
 */
if (isset($options['config'])) {
    $setup_file = file_get_contents($options['config']);
    $setup_data = json_decode($setup_file, true);

    $config = [
		'dbname' => $setup_data['db']['name'],
		'dbuser' => $setup_data['db']['user'],
		'dbpass' => $setup_data['db']['pass'],
		'url' => $setup_data['site']['url'],
		'title' => $setup_data['site']['title'],
		'description' => $setup_data['site']['description'],
		'admin_user' => $setup_data['admin']['user'],
		'admin_pass' => $setup_data['admin']['pass'],
		"admin_email" => $setup_data['admin']['email'],
		'home_url' => $setup_data['option']['home_url'],
		'site_url' => $setup_data['option']['site_url'],
		'themes' => $setup_data['themes'],
		'plugins' => $setup_data['plugins'],
	];


    /**
     * SETUP THE DB FOR WORDPRESS
     */
    $mysql_pdo = new PDO('mysql:host=localhost', $config['dbuser'], $config['dbpass']);
    $result = $mysql_pdo->exec("DROP DATABASE IF EXISTS `{$config['dbname']}`;");
    $result = $mysql_pdo->exec("CREATE DATABASE `{$config['dbname']}`;");

    if ($result) {
        echo "[+] DATABASE `{$config['dbname']}` WAS SUCCESSFULLY CREATED.".PHP_EOL;
    } else {
        exit("[-] FAILED TO CREATE DATABASE, GOOD BYE!");
    }


    if (file_exists($options['path'])) {
        recursive_rmdir($options['path']);
    }


    /**
     * DOWNLOAD WORDPRESS CORE
     */
    echo '[+] DOWNLOADING WORDPRESS CORE...'.PHP_EOL;
    exec("wp core download --path={$options['path']}");


    /**
     * CREATE WP CONFIG FILE
     */
    echo '[+] CREATING WP-CONFIG...'.PHP_EOL;
    $cmd = exec("wp config create --path={$options['path']}"
                            ." --dbname={$config['dbname']}"
                            ." --dbuser={$config['dbuser']}");

    /**
     * INSTALL WORDPRESS CORE
     */
    echo '[+] INSTALLING WORDPRESS CORE...'.PHP_EOL;
    exec("wp core install --path={$options['path']}"
                           ." --url={$config['url']}"
                           ." --title=\"{$config['title']}\""
                           ." --admin_user={$config['admin_user']}"
                           ." --admin_password={$config['admin_pass']}"
                           ." --admin_email={$config['admin_email']}");

    /**
     * UPDATE SITE TITLE AND DESCRIPTION
     */
    exec("wp option update blogname --path={$options['path']} \"{$config['title']}\"");
    exec("wp option update blogdescription --path={$options['path']} \"{$config['description']}\"");

    /**
     * UPDATE HOME and SITE URL
     */
    exec("wp option update home --path={$options['path']} {$config['home_url']}");
    exec("wp option update siteurl --path={$options['path']} {$config['site_url']}");


    /**
     * INSTALL THEMES
     */
    echo "[+] DOWNLOADING THEMES...".PHP_EOL;
    foreach ($config['themes'] as $theme) {
        exec("wp theme install --path={$options['path']} {$theme}");
    }

    /**
     * INSTALL PLUGINS
     */
    echo "[+] DOWNLOADING PLUGINS...".PHP_EOL;
    foreach ($config['plugins'] as $plugin) {
        exec("wp plugin install --path={$options['path']} {$plugin}");
    }
}

/**
 * ADD ALL POST
 */
if(isset($options['post'])) {
	$content_file = file_get_contents($options['post']);
	$content_data = json_decode($content_file, true);

	foreach($content_data as $post) {
	    $wp_post_create = "wp post create";
		foreach($post as $key => $value) {
			$optional_args = '';

			if(empty($value)) {
				continue;
			}

            if(is_array($value)) {	
                $arr_string = implode(',', $value);
                foreach($value as $category) {
				    exec("wp term create category {$category} --path={$options['path']}");
                }
				$optional_args = " --{$key}=[{$arr_string}]";
			} else {
				$optional_args = " --{$key}=\"{$value}\"";
			}

			$wp_post_create .= $optional_args;
        }
        $wp_post_create .= " --path={$options['path']}";
	    exec($wp_post_create);
	}
} 

/**
 * CUSTOMIZE THEME MODS
 */
if(isset($options['theme_mods'])) {
    $theme_mods_file = file_get_contents($options['theme_mods']);
    $theme_mods_data = json_decode($theme_mods_file, true);
    
    $theme_name = basename($options['theme_mods'], '.json');

    exec("wp theme activate ace-blog --path={$options['path']}");

    foreach($theme_mods_data as $theme_mod => $value) {
        if(!empty($value)) {
            exec("wp theme mod set {$theme_mod} \"{$value}\" --path={$options['path']}");
        }
    }
}
