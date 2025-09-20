#!/usr/bin/php
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set("max_execution_time",-1);
require "includes/phpagi.php";
$agi = new AGI();
$agi->verbose("In agi script.");
$filenane = rand(1,500000) . ".wav";
// Set the recording parameters
$recordingFile = '/tmp/' . $filenane;


$maxRecordingTime = 15; // Maximum recording time in second
// Record the audio
$agi->exec('Record', "$recordingFile,2,$maxRecordingTime");
if(file_exists($recordingFile)){
    $agi->verbose("file exists");
} else {
    while(!file_exists($recordingFile)){
        $agi->verbose("There is not a file: " . $recordingFile);
        usleep(250);
    }
}

// Post the recording to the specified URL
$apiUrl = 'http://127.0.0.1:9000/asr?encode=true&task=transcribe&word_timestamps=false&output=txt';
$postData = array(
    'audio_file' => curl_file_create($recordingFile, 'audio/wav', 'recording'),
);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch,CURLOPT_CONNECTTIMEOUT_MS,0);
curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,0);
$response = curl_exec($ch);
$response = preg_replace('/[^a-zA-Z0-9 ]/','', $response);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode == 200) {
    $agi->verbose("Recording posted successfully.");
} else {
    $agi->verbose("Error posting recording. HTTP Code: $httpCode");
}
curl_close($ch);
$response = trim($response,'"\n');
$agi->verbose("RESPONSE:");
$agi->verbose(print_r($response, true));

// Check the HTTP response code
$agi->verbose("In agi transcribe script.");

$filenameNoExtension = rand(1,500000);
$filenane = $filenameNoExtension . ".wav";
// Set the recording parameters
$agi->set_variable("wavefile",$filenane);

$recordingFile = '/var/lib/asterisk/sounds/en/' . $filenane;
$agi->verbose("Before Command set");
$command = "echo \"$response\" | /home/pnovack/code/piper_amd64/piper/piper "
    ."--model /home/pnovack/code/piper_amd64/piper/espeak-ng-data/voices/en/amy/medium/en_US-amy-medium.onnx "
    . "--output_file /var/lib/asterisk/sounds/en/$filenane > /dev/null 2>&1";
$agi->verbose("After Command Set");
$agi->verbose($command);
$agi->verbose("3");
$result = null;
$var = system($command, $result);
unlink("/var/lib/asterisk/sounds/en/myfile.wav");

$ffmpegcomman = "ffmpeg -i /var/lib/asterisk/sounds/en/$filenane -ar 8000 /var/lib/asterisk/sounds/en/myfile.wav -y > /dev/null 2>&1";
$agi->verbose($ffmpegcomman);
$var = system($ffmpegcomman);

usleep(250);
while(!file_exists("/var/lib/asterisk/sounds/en/myfile.wav")){
    //$agi->verbose("no file");
    usleep(250);
}
$agi->stream_file($filenameNoExtension);
// Clean up: delete the recorded file
//unlink("/var/lib/asterisk/sounds/en/$filenane");
//unlink($recordingFile);

// Exit the AGI script

// Set your OpenAI API key here
$api_key = '<keyHere>';

$ch = curl_init();

$url = 'https://api.openai.com/v1/chat/completions';



//$query = 'What is the capital city of England?';

$post_fields = array(
    "model" => "gpt-3.5-turbo",
    "messages" => array(
        array(
            "role" => "user",
            "content" => $response
        )
    ),
    "max_tokens" => 250,
    "temperature" => 0
);

$header  = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
];

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    //echo 'Error: ' . curl_error($ch);
}
curl_close($ch);

$response = json_decode($result);
var_dump($response->choices[0]->message->content);

$response = preg_replace('/[^a-zA-Z0-9 ]/','',$response->choices[0]->message->content);

// Output the generated response
$chatGPTResponse = 'ChatGPT Response: ' . $response;

//$agi->verbose($chatGPTResponse);

$recordingFile = '/var/lib/asterisk/sounds/en/' . $filenane;
$agi->verbose("Before Command set");
$command = "echo \"$chatGPTResponse\" | /home/pnovack/code/piper_amd64/piper/piper "
    ."--model /home/pnovack/code/piper_amd64/piper/espeak-ng-data/voices/en/amy/medium/en_US-amy-medium.onnx "
    . "--output_file /var/lib/asterisk/sounds/en/$filenane > /dev/null 2>&1";
$agi->verbose("After Command Set");
$agi->verbose($command);
$agi->verbose("3");
$result = null;
$var = system($command, $result);

$ffmpegcomman = "ffmpeg -i /var/lib/asterisk/sounds/en/$filenane -ar 8000 /var/lib/asterisk/sounds/en/chatgpt-response.wav -y > /dev/null 2>&1";
$agi->verbose($ffmpegcomman);
$var = system($ffmpegcomman);




exit(0);
