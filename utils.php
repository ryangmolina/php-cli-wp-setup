<?php

function recursive_rmdir($dir) {
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) { 
        (is_dir("$dir/$file")) ? recursive_rmdir("$dir/$file") : unlink("$dir/$file"); 
    }
    return rmdir($dir); 
}
