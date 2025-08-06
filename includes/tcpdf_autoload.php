<?php
// This is a stub file to help with manual TCPDF installation
// The actual file should be in the TCPDF installation directory

// Define the TCPDF installation path
define('K_TCPDF_EXTERNAL_CONFIG', true);

// Path to the main TCPDF library
define('K_PATH_MAIN', __DIR__ . '/tcpdf/');

// Path to the TCPDF fonts
if (!defined('K_PATH_FONTS')) {
    define('K_PATH_FONTS', K_PATH_MAIN . 'fonts/');
}

// Path to the TCPDF cache
if (!defined('K_PATH_CACHE')) {
    define('K_PATH_CACHE', K_PATH_MAIN . 'cache/');
}

// Path to the TCPDF images directory
if (!defined('K_PATH_IMAGES')) {
    define('K_PATH_IMAGES', '');
}

// Default image directory used in the examples
if (!defined('PDF_HEADER_LOGO')) {
    define('PDF_HEADER_LOGO', '');
}

// Include the main TCPDF library
require_once(K_PATH_MAIN . 'tcpdf.php');
