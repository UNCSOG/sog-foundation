<?php

// Jumpstart new Views
require_once WPV_PATH. '/application/controllers/main.php';
$wpv_main = new WPV_Main();
$wpv_main->initialize();

// Load routes.
require_once __DIR__ . '/routes.php';
