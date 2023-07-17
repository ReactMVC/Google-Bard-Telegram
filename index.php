<?php
/*
*** Google Bard ***
Telegram: @h3dev
Email: h3dev.pira@gmail.com
Github: https://github.com/ReactMVC
*/

// Set the Telegram bot token and name
$token = 'YOUR_BOT_TOKEN';
$botname = 'Google Bard';

// Set the API URL for Telegram
$api_url = 'https://api.telegram.org/bot' . $token . '/';

// Function to send a message to a chat ID
function sendMessage($chat_id, $text)
{
    global $api_url;
    $url = $api_url . 'sendMessage';
    $post_fields = array(
        'chat_id' => $chat_id,
        'text' => $text
    );

    // Initialize a cURL session
    $ch = curl_init();

    // Set the cURL options
    // Set the URL to send the POST request to
    curl_setopt($ch, CURLOPT_URL, $url);
    // Set the POST fields
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    // Return the response instead of printing it
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Send the POST request with cURL and get the response
    $result = curl_exec($ch);

    // Check if there was an error with the cURL request
    if (!$result) {
        return false;
    }

    // Decode the JSON response into an associative array
    $response = json_decode($result, true);

    // Check if the response is valid and contains an OK status
    if (!$response || $response['ok'] != true) {
        return false;
    }

    // If there were no errors, return true
    return true;
}

// Function to process an incoming message
function processMessage($message)
{
    global $botname;

    // Check if the message contains text
    if (isset($message['text'])) {
        $chat_id = $message['chat']['id'];
        $message_text = $message['text'];

        // If the message is the "/start" command, greet the user and provide instructions
        if ($message_text == '/start') {
            $welcome_message = "به ربات $botname خوش آمدید!\n\nسوال خود را از هوش مصنوعی بارد بپرسید.";

            sendMessage($chat_id, $welcome_message);
        } else {
            // If the message is not the "/start" command, send it to the Google Bard AI
            $api_url = 'https://api.safone.me/bard?message=' . urlencode($message_text);

            // Initialize a cURL session
            $ch = curl_init();

            // Set the cURL options
            // Set the URL to send the GET request to
            curl_setopt($ch, CURLOPT_URL, $api_url);
            // Return the response instead of printing it
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Send a message to the user to indicate that their message is being processed
            sendMessage($chat_id, 'کمی صبر کنید...');

            // Send the GET request with cURL and get the response
            $result = curl_exec($ch);

            // Check if there was an error with the cURL request
            if (!$result) {
                sendMessage($chat_id, 'خطا در برقراری ارتباط.');
                return;
            }

            // Decode the JSON response into an associative array
            $response = json_decode($result, true);

            // Check if the response contains a message field
            if (!isset($response['message'])) {
                sendMessage($chat_id, 'خطا در دریافت پاسخ.');
                return;
            }

            // If the message is longer than 4096 characters, split it into multiple messages
            $message_length = strlen($response['message']);
            if ($message_length > 4096) {
                $split_message = str_split($response['message'], 4096);
                foreach ($split_message as $msg) {
                    sendMessage($chat_id, $msg);
                }
            } else {
                // If the message is shorter than 4096 characters, send it in a single message
                sendMessage($chat_id, $response['message']);
            }
        }
    }
}

// Get the incoming update from the Telegram API
$update = json_decode(file_get_contents('php://input'), true);

// Exit the script if there was an error getting the update
if (!$update) {
    exit('خطا در دریافت پیام');
}

// Process the incoming message if it is a message (not a callback query or inline query)
if (isset($update['message'])) {
    processMessage($update['message']);
}