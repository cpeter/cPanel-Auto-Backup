<?php

$login  = 'cpanel_user';
$pass   = exec("id -nu | md5sum | awk -F- '{ print \$1 }'");
$auth   = base64_encode("$login:$pass");
$domain = "https://example.com:2083";
$theme  = "paper_lantern";

// Do not change below 
$url     = $domain . "/frontend/" . $theme . "/backup/dofullbackup.html";
$data    = array();
$options = array(
    'http' => array(
        'header' => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Basic $auth\r\n",
        'method' => 'POST',
        'content' => http_build_query($data)
    ),
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    )
);

# execute backup generation
$context = stream_context_create($options);
$result  = file_get_contents($url, false, $context);

$url     = $domain . "/frontend/" . $theme . "/backup/fullbackup.html";
$options = array(
    'http' => array(
        'header' => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Basic $auth\r\n",
        'method' => 'GET'
    ),
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    )
);

$context = stream_context_create($options);
$result  = file_get_contents($url, false, $context);

# get downloadable files
preg_match_all("|/download\?file=(.*?)\"|", $result, $matches);
foreach ($matches[1] as $match) {
    $auth = $login . ':' . $pass;
    
    // due to memory contrains we will do the download with curl
    $url = $domain . "/download?file=" . $match;
    exec("curl -s -u " . escapeshellarg($auth) . ' ' . escapeshellarg($url) . " -o " . escapeshellarg("/home/backup/hosting_backup/" . $match));
    
    // now delete them
    $url = $domain . "/json-api/cpanel";
    exec("curl -s -u " . escapeshellarg($auth) . ' ' . escapeshellarg($url) . " --data " . escapeshellarg("cpanel_jsonapi_module=Fileman&amp;cpanel_jsonapi_func=fileop&amp;cpanel_jsonapi_apiversion=2&amp;filelist=1&amp;multiform=1&amp;doubledecode=0&amp;op=unlink&amp;metadata=[object Object]&amp;sourcefiles=%2fhome%2fpmcnet%2f" . $match));
}

if ($result === FALSE) {
    exit("Error backing up server.");
}
