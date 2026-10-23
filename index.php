<?php
// Inicia a sessão se precisar criar login no futuro
session_start();

// O index principal apenas carrega a parte visual do site (as views)
require_once 'views/header.php';
require_once 'views/home.php';
require_once 'views/footer.php';
?>