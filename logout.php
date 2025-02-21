<?php
session_start();

// Remover todas as variáveis da sessão
$_SESSION = [];

// Destruir a sessão
session_destroy();

// Remover o cookie de "manter conectado"
setcookie("remember_me", "", time() - 3600, "/");

// Redirecionar para a página de login
header("Location: index.php");
exit();
?>
