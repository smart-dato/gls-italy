<?php

return [
    /*
     * Every endpoint can be overridden in the published copy of this file,
     * e.g. to route the label service through an HTTP proxy — the SOAP method
     * is selected by the request body, so a bare URL swap is enough.
     */
    'endpoints' => [
        'label_service' => 'https://labelservice.gls-italy.com/ilswebservice.asmx',
        'legacy' => 'https://www.gls-italy.com/PHPApps',
        'stock_release' => 'https://www.gls-italy.com/PHPApps/redelivery_parcel.php',
        'tracking' => 'https://infoweb.gls-italy.com/XML/get_xml_track.php',
        'check_address' => 'https://checkaddress.gls-italy.com/wscheckaddress.asmx',
    ],

    'http' => [
        'timeout' => 30,
        'verify' => true,
        'label_service_content_type' => 'text/xml',
    ],
];
