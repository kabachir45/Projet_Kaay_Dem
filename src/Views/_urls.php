<?php
$_scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host     = $_SERVER['HTTP_HOST'];
$_docRoot  = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$_projPath = str_replace('\\', '/', dirname(dirname(__DIR__))); 
$baseUrl   = $_scheme . '://' . $_host . str_replace($_docRoot, '', $_projPath) . '/';
$viewsUrl  = $baseUrl . 'src/Views/';
