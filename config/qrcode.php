<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | QR Code Defaults
    |--------------------------------------------------------------------------
    |
    | Default options used when generating QR codes. Every value can be
    | overridden fluently at runtime, e.g.:
    |
    |   QrCode::make('...')->size(512)->format('png')->generate();
    |
    */

    // Size of the rendered image in pixels.
    'size' => 300,

    // Quiet-zone margin around the code, expressed in modules.
    'margin' => 4,

    // Output format: "svg" or "png".
    'format' => 'svg',

    // Error correction level: L (7%), M (15%), Q (25%), H (30%).
    'error_correction' => 'M',

];
