<?php
session_start();

// Redireciona imediatamente para o painel se já estiver logado
if (isset($_SESSION['vereador_id'])) {
    header("Location: https://patosnoticias.com.br/camara/painel.php");
    exit();
}

$data = json_decode(file_get_contents("db.json"), true);

// Verificar se o cookie "remember_me" está definido e se a sessão ainda não foi iniciada
if (isset($_COOKIE['remember_me']) && !isset($_SESSION['vereador_id'])) {
    $id = $_COOKIE['remember_me'];
    if (isset($data["vereadores"][$id])) {
        $_SESSION["vereador_id"] = $id;
        $_SESSION["vereador_nome"] = $data["vereadores"][$id]["nome"];
        header("Location: https://patosnoticias.com.br/camara/painel.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST["id"];
    $senha = $_POST["senha"];
    $manter_conectado = isset($_POST["manter_conectado"]);

    if (isset($data["vereadores"][$id]) && $data["vereadores"][$id]["senha"] === $senha) {
        $_SESSION["vereador_id"] = $id;
        $_SESSION["vereador_nome"] = $data["vereadores"][$id]["nome"];

        // Se a opção "manter conectado" foi marcada, cria o cookie com expiração de 1 ano
        if ($manter_conectado) {
            // Ajuste no setcookie: Caminho "/" para ser acessível em todo o site
            setcookie("remember_me", $id, time() + (86400 * 365), "/"); // 1 ano
        }

        // Redireciona após salvar o cookie
        header("Location: https://patosnoticias.com.br/camara/painel.php");
        exit();
    } else {
        $erro = "Credenciais inválidas!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Votação</title>
    <link rel="icon" href="https://cdn-icons-png.flaticon.com/128/5161/5161776.png" type="image/x-icon">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#007bff">
    <script>
        if ("serviceWorker" in navigator) {
            navigator.serviceWorker.register("sw.js")
                .then(() => console.log("Service Worker registrado!"))
                .catch((err) => console.log("Erro ao registrar Service Worker:", err));
        }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card mx-auto" style="max-width: 400px;">
            <div class="card-body">
                <h2 class="text-center">Login</h2>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">ID do Vereador</label>
                        <input type="text" name="id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input type="password" name="senha" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <input type="checkbox" name="manter_conectado" id="manter_conectado">
                        <label for="manter_conectado" class="form-label">Manter conectado</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                </form>
                <?php if (isset($erro)) echo "<p class='text-danger text-center mt-2'>$erro</p>"; ?>
            </div>
        </div>
    </div>
</body>
</html>
