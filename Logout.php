<?php
session_start();
session_destroy();
header("Location: Form3.php");
exit();
?>