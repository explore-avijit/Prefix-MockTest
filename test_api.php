<?php
// Simulate the GET request
$_GET['lang'] = 'English';
$_GET['role'] = 'aspirant';
$_GET['target'] = 'TET (SSC)';
$_GET['subject'] = 'beng';
$_GET['limit'] = 10;

// Include the API file (it will use these $_GET values)
include 'api/fetch_questions.php';
?>
