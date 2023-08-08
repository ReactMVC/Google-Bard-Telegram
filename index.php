<?php
/*
*** Google Bard ***
Telegram: @h3dev
Email: h3dev.pira@gmail.com
Github: https://github.com/ReactMVC
*/

// Set the Telegram bot token and name and channel
$token = 'token';
$botname = 'Google Bard';
$channel_username = '@StealthySolutions';

// Set the API URL for Telegram
$api_url = 'https://api.telegram.org/bot' . $token . '/';

// Function to send a message to a chat ID
function sendMessage($chat_id, $text, $reply_to_message_id = null)
{
    global $api_url;
    $url = $api_url . 'sendMessage';
    $post_fields = array(
        'chat_id' => $chat_id,
        'text' => $text,
        'reply_to_message_id' => $reply_to_message_id
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
    global $botname, $token, $channel_username;

    // Check if the message contains text
    if (isset($message['text'])) {
        $message_text = $message['text'];
        if ($message['chat']['type'] == 'private') {
            $chat_id = $message['chat']['id'];

            // Check if the user is a member of the channel
            $user_id = $message['from']['id'];
            $response = json_decode(file_get_contents("https://api.telegram.org/bot$token/getChatMember?chat_id=$channel_username&user_id=$user_id"), true);
            if ($response['ok'] != true || !in_array($response['result']['status'], ['member', 'administrator', 'creator'])) {
                // If the user is not a member, admin or creator, send a message asking them to join the channel
                $join_message = "برای استفاده از ربات، لطفا به کانال $channel_username بپیوندید.\n\nدر گروه خود نیاز به عضویت در کانال ما نیست. اما اگر می خواهید در ربات چت کنید نیاز است عضو کانال ما شوید.";
                sendMessage($chat_id, $join_message, $message['message_id']);
                return;
            }

            // If the message is the "/start" command, greet the user and provide instructions
            if ($message_text == '/start') {
                $welcome_message = "به ربات $botname خوش آمدید!\n\nسوال خود را از هوش مصنوعی قدرتمند بپرسید.\nاگر دوست داری منو ببری گروهت به شکل زیر از من در گروه استفاده کن:\nbard: YOUR_TEXT";

                sendMessage($chat_id, $welcome_message, $message['message_id']);
            } else {
                // If the message is not the "/start" command, send it to the BardAI
                $api_url = 'https://api.safone.me/bard?message=' . urlencode($message_text);

                // Initialize a cURL session
                $ch = curl_init();

                // Set the cURL options
                // Set the URL to send the GET request to
                curl_setopt($ch, CURLOPT_URL, $api_url);
                // Return the response instead of printing it
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // Send a message to the user to indicate that their message is being processed
                sendMessage($chat_id, 'کمی صبر کنید...', $message['message_id']);

                // Send the GET request with cURL and get the response
                $result = curl_exec($ch);

                // Check if there was an error with the cURL request
                if (!$result) {
                    sendMessage($chat_id, 'خطا در برقراری ارتباط.', $message['message_id']);
                    return;
                }

                // Decode the JSON response into an associative array
                $response = json_decode($result, true);

                // Check if the response contains an answer field
                if (!isset($response['message'])) {
                    sendMessage($chat_id, 'خطا در دریافت پاسخ.', $message['message_id']);
                    return;
                }

                // If the answer is longer than 4096 characters, split it into multiple messages
                $message_length = strlen($response['message']);
                if ($message_length > 4096) {
                    $split_message = str_split($response['message'], 4096);
                    foreach ($split_message as $msg) {
                        sendMessage($chat_id, $msg, $message['message_id']);
                    }
                } else {
                    // If the message is shorter than 4096 characters, send it in a single message
                    sendMessage($chat_id, $response['message'], $message['message_id']);
                }
            }
        } else {
            // Check if the message starts with "bard:"
            if (!preg_match('/^bard:/i', $message_text)) {
                return;
            }
            // Remove "bard:" from the message text
            $message_text = preg_replace('/^bard:\s*/i', '', $message_text);

            $chat_id = $message['chat']['id'];

            // send it to the BardAI
            $api_url = 'https://api.safone.me/bard?message=' . urlencode($message_text);

            // Initialize a cURL session
            $ch = curl_init();

            // Set the cURL options
            // Set the URL to send the GET request to
            curl_setopt($ch, CURLOPT_URL, $api_url);
            // Return the response instead of printing it
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Send a message to the user to indicate that their message is being processed
            sendMessage($chat_id, 'کمی صبر کنید...', $message['message_id']);

            // Send the GET request with cURL and get the response
            $result = curl_exec($ch);

            // Check if there was an error with the cURL request
            if (!$result) {
                sendMessage($chat_id, 'خطا در برقراری ارتباط.', $message['message_id']);
                return;
            }

            // Decode the JSON response into an associative array
            $response = json_decode($result, true);

            // Check if the response contains an answer field
            if (!isset($response['message'])) {
                sendMessage($chat_id, 'خطا در دریافت پاسخ.', $message['message_id']);
                return;
            }

            // If the answer is longer than 4096 characters, split it into multiple messages
            $message_length = strlen($response['message']);
            if ($message_length > 4096) {
                $split_message = str_split($response['message'], 4096);
                foreach ($split_message as $msg) {
                    sendMessage($chat_id, $msg, $message['message_id']);
                }
            } else {
                // If the message is shorter than 4096 characters, send it in a single message
                sendMessage($chat_id, $response['message'], $message['message_id']);
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