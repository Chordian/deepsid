<?php
$url = 'https://remix64.com/services/api/de/deepsid/?task=get_remixes&api_user=deepsid&hash=f81f410a049f09d6a2b19cbeccc87c66&data={%22hvsc_path%22:%22MUSICIANS\/H\/Hubbard_Rob\/ACE_II.sid%22}';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$response = curl_exec($ch);

$info = curl_getinfo($ch);
$error = curl_error($ch);
curl_close($ch);

// Strip headers
$headerSize = $info['header_size'];
$body = substr($response, $headerSize);

// Decode JSON
$data = json_decode($body, true);

echo "<pre>";
echo "CURL Info:\n";
print_r($info);

echo "\nDecoded JSON:\n";
if ($data === null) {
    echo "Failed to decode JSON: " . json_last_error_msg();
} else {
    print_r($data);
}
echo "</pre>";
?>