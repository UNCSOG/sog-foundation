<?php

namespace Beehive;

// Don't redefine the functions if included multiple times.
if (!\function_exists('Beehive\\GuzzleHttp\\uri_template')) {
    require __DIR__ . '/functions.php';
}
