<?php

return [
    'remoteFileUri' => [
        'https' => 'https://curl.haxx.se/ca/cacert.pem',
        'http' => 'http://curl.haxx.se/ca/cacert.pem',
    ],
    'remoteProtocol' => '*',
    'path' => '<APPLICATION>/files/cacert.pem',
    'maxAge' => 1296000, // 15 days
];
