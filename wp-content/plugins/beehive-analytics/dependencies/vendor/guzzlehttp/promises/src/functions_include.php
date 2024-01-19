<?php

namespace Beehive;

// Don't redefine the functions if included multiple times.
if (!\function_exists('Beehive\\GuzzleHttp\\Promise\\promise_for')) {
    require __DIR__ . '/functions.php';
}
