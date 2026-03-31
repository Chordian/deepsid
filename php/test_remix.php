<?php

$partnerId = 'deepsid';
$apiPassword = 'FeekTER1mNgoxusi3VejlOLDVdJIPgS0';
$sharedSecret = 'oDqHpvKZp2fM05JydWY2ylR8bCE8Y2PN'; // Not used for this endpoint

$endpointPath = 'remix/get_remixes_by_hvsc_path';
$params = [
    'hvsc_path' => 'MUSICIANS/H/Hubbard_Rob/ACE_II.sid'
];

$url = 'https://remix64.com/services/api/gb/2/' .
       $endpointPath . '/?' . http_build_query($params);

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => true,
    CURLOPT_HTTPGET        => true,
    CURLOPT_USERPWD        => $partnerId . ':' . $apiPassword,
    CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
    CURLOPT_USERAGENT      => 'Mozilla/5.0',

    // Local testing only
    //CURLOPT_SSL_VERIFYPEER => false,
    //CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$info = curl_getinfo($ch);
$error = curl_error($ch);
$errno = curl_errno($ch);

curl_close($ch);

echo "<pre>";

if ($response === false) {
    echo "cURL failed\n";
    echo "Error number: $errno\n";
    echo "Error: $error\n";
    echo "</pre>";
    exit;
}

$headerSize = $info['header_size'];
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "CURL Info:\n";
print_r($info);

echo "\nResponse headers:\n$headers";
echo "\nRaw body:\n$body";

$data = json_decode($body, true);

echo "\n\nDecoded JSON:\n";
if ($data === null) {
    echo "Failed to decode JSON: " . json_last_error_msg() . "\n";
} else {
    print_r($data);
}

echo "</pre>";

?>