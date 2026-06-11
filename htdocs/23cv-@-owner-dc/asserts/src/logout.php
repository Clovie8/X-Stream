<?php 
session_start();
if (session_destroy()) {
    header('location:../../way-to-go');
    exit();
}
?>