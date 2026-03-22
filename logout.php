<?php
require_once 'includes/functions.php';

// Regenerate session ID before destroying to prevent session fixation
session_regenerate_id(true);
session_destroy();
header('Location: index.php');
?>