<?php
session_start();

// Verifica se o usuário está logado via sessão ou cookie
if (!isset($_SESSION["vereador_id"]) && isset($_COOKIE['remember_me'])) {
    $id = $_COOKIE['remember_me'];
    $data = json_decode(file_get_contents("db.json"), true);
    if (isset($data["vereadores"][$id])) {
        $_SESSION["vereador_id"] = $id;
        $_SESSION["vereador_nome"] = $data["vereadores"][$id]["nome"];
    }
}

// Verifica se o usuário está logado
if (!isset($_SESSION["vereador_id"]) || !isset($_SESSION["vereador_nome"])) {
    header("Location: index.php");
    exit();
}

// Redireciona vereador id 1 para o painel administrativo
if ($_SESSION["vereador_id"] == 1) {
    header("Location: https://patosnoticias.com.br/camara/admin.php");
    exit();
}

$vereador_id = $_SESSION["vereador_id"];
$vereador_nome = $_SESSION["vereador_nome"];

$data = json_decode(file_get_contents("db.json"), true);

// Verifica se a votação está ativa e se o vereador já votou
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["voto"]) && $data["votacao"]["ativa"]) {
    if (!isset($data["votacao"]["votos"][$vereador_id])) {
        $data["votacao"]["votos"][$vereador_id] = $_POST["voto"];
        file_put_contents("db.json", json_encode($data, JSON_PRETTY_PRINT));
        header("Refresh:0");
    }
}

$votos = array_count_values($data["votacao"]["votos"]);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel de Votação</title>

    <!-- Adiciona o favicon -->
    <link rel="icon" href="https://cdn-icons-png.flaticon.com/128/5161/5161776.png" type="image/x-icon">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <!-- Configuração do PWA -->
    <link rel="manifest" href="manifest2.json">
    <meta name="theme-color" content="#007bff">
    <script>
        if ("serviceWorker" in navigator) {
            navigator.serviceWorker.register("sw.js")
                .then(() => console.log("Service Worker registrado!"))
                .catch((err) => console.log("Erro ao registrar Service Worker:", err));
        }
    </script>

    <script>
        setInterval(() => { location.reload(); }, 5000); // Atualiza a cada 5 segundos
    </script>

    <!-- Adiciona o script para modal de confirmação -->
    <script>
        function confirmarSaida() {
            var senha = prompt("Digite a senha de administrador para confirmar a saída:");
            if (senha === "5252") {
                window.location.href = "logout.php"; // Redireciona para logout
            } else {
                alert("Senha incorreta! Não foi possível realizar a saída.");
            }
        }
    </script>
</head>
<body class="bg-light">
    <div class="container mt-4 text-start">
        <h2 class="fw-bold fs-2">Painel de Votação</h2>
        <p class="fs-4"><strong>Vereador:</strong> <?= htmlspecialchars($vereador_nome) ?></p>
        <p class="fs-4"><strong>Status da Votação:</strong> 
            <?= $data["votacao"]["ativa"] ? "<span class='text-success fw-bold'>Aberta</span>" : "<span class='text-danger fw-bold'>Encerrada</span>" ?>
        </p>

        <?php if ($data["votacao"]["ativa"] && !isset($data["votacao"]["votos"][$vereador_id])): ?>
            <h3 class="mt-4 fw-bold fs-2">Seu Voto</h3>
            <form method="post" class="mt-3">
                <button name="voto" value="Sim" class="btn btn-success btn-lg w-100 text-start fs-3 mb-3">✅ SIM</button>
                <button name="voto" value="Não" class="btn btn-danger btn-lg w-100 text-start fs-3 mb-3">❌ NÃO</button>
                <button name="voto" value="Abstenção" class="btn btn-dark btn-lg w-100 text-start fs-3 mb-3">⚫ ABSTENÇÃO</button>
            </form>
        <?php else: ?>
            <p class="mt-3 fs-4"><strong>Seu voto:</strong> <?= $data["votacao"]["votos"][$vereador_id] ?? "Já votado" ?></p>
        <?php endif; ?>

        <h3 class="mt-4 fw-bold fs-2">Resultados</h3>
        <p class="fs-4">✅ Sim: <strong><?= $votos["Sim"] ?? 0 ?></strong></p>
        <p class="fs-4">❌ Não: <strong><?= $votos["Não"] ?? 0 ?></strong></p>
        <p class="fs-4">⚫ Abstenção: <strong><?= $votos["Abstenção"] ?? 0 ?></strong></p>

        <?php if (!$data["votacao"]["secreta"]) : ?>
            <h3 class="mt-4 fw-bold fs-2">Votos Individuais</h3>
            <ul class="list-group text-start fs-4">
                <?php foreach ($data["votacao"]["votos"] as $vereador => $voto) : ?>
                    <li class="list-group-item"><?= $data["vereadores"][$vereador]["nome"] ?>: <strong><?= $voto ?></strong></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- Botão de Sair com confirmação -->
        <button class="btn btn-warning btn-lg w-100 text-start fs-3 mt-4" onclick="confirmarSaida()">Sair</button>
    </div>
</body>
</html>