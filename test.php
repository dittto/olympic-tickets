<?php

// gets the url of the page
$url = isset($_GET['url']) ? $_GET['url'] : '';
#$url = 'http://www.tickets.london2012.com/browse?form=search&tab=oly&sport=&venue=loc_1&fromDate=2012-08-08&toDate=2012-08-10&evening=1&show_available_events=1';
#$url = 'http://www.tickets.london2012.com/eventdetails?id=0000455AC9A00958';
#$url = 'http://www.tickets.london2012.com/eventdetails?id=0000455AC9A3095E';
#$url = 'http://www.tickets.london2012.com/eventdetails?id=0000455AC9A40960';

$main = isset($_GET['main']) ? true : false;
$id = isset($_GET['id']) ? $_GET['id'] : '';

// add a cache breaker to the url
if (strstr($url, '?') !== false) {
    $url .= '&break='.time();
}

// create a stream
$opts = array('http' => array('method' => 'GET', 'header'=>"Accept-language: en\r\n"));
$context = stream_context_create($opts);

// open the file using the HTTP headers set above
$file = file_get_contents($url, false, $context);

// handle the main page
if ($main) {
    // get the number of sessions available
    preg_match('/Showing(.+)of(.*)(\d+)(.*)sessions/Usi', $file, $match);
    $num = isset($match[3]) ? $match[3] : 0;
    
    
    
    // get the matches found
    $options = array();
    preg_match_all('/\<td\sheaders\="date_time".*\>(.*)\<\/td\>.*<td\sheaders\="sports".*\>(.*)\<\/td\>.*<td\sheaders\="select".*\>(.*)\<\/td\>/Usi', $file, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $match[1] = str_replace('<br />', " - ", $match[1]);
        $match[1] = str_replace('  ', "", $match[1]);
        $match[2] = str_replace('<br />', "\n", $match[2]);
        $match[2] = str_replace('  ', "", $match[2]);
        
        $path = '';
        preg_match('/value\="(.*)"/Usi', $match[3], $value);
        if (isset($value[1])) {
            $path = 'eventdetails?id='.$value[1];
        }
        $options[] = array('date' => trim(strip_tags($match[1])), 'session' => trim(strip_tags($match[2])), 'open' => !strstr($match[3], 'Currently'), 'path' => $path);
    }
    
    // output the number of sessions found
    echo json_encode(array('sessions' => $num, 'options' => $options, 'id' => $id));
    
    die();
}

// handle the direct pages
else {
    // get the number of tickets
    preg_match('/\<div class\="tix_limit_num"\>(.*)\<\/div\>/Usi', $file, $match);
    $ticket_count = $match[1];

    // get the available options
    $options = array();
    preg_match('/\<select.*price_category.*\>(.*)\<\/select\>/Usi', $file, $match);
    if ($match) {
        preg_match_all('/\<option.*\>£(.*)\s\-\s(.*)\<\/option\>/Usi', $match[1], $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $options[] = array('price' => $match[1], 'type' => $match[2]);
        }
    }

    // output the number of sessions found
    echo json_encode(array('count' => $ticket_count, 'options' => $options, 'id' => $id));
    
    die();
}
