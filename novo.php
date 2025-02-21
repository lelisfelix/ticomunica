<?php
session_start();

// Verifica se o usuário está logado e se é o presidente (Rodrigo Alves)
if (!isset($_SESSION["vereador_id"]) || $_SESSION["vereador_id"] !== "1") {
    header("Location: index.php");
    exit();
}

// Carrega os dados do JSON
$data = json_decode(file_get_contents("db.json"), true);

// Inicializa a chave de ausentes se não existir
if (!isset($data["ausentes"])) {
    $data["ausentes"] = [];
}

// Ações do presidente
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["iniciar"])) {
        $data["votacao"] = [
            "ativa" => true,
            "secreta" => $data["votacao"]["secreta"],
            "votos" => []
        ];
    }

    if (isset($_POST["zerar"])) {
        $data["votacao"]["votos"] = [];
        file_put_contents("db.json", json_encode($data, JSON_PRETTY_PRINT));
        exit(); // Evita reload imediato
    }

    if (isset($_POST["encerrar"])) {
        $data["votacao"]["ativa"] = false;
    }

    if (isset($_POST["secreta"])) {
        $data["votacao"]["secreta"] = !$data["votacao"]["secreta"];
    }

    if (isset($_POST["voto"])) {
        if ($data["votacao"]["ativa"] && !isset($data["votacao"]["votos"]["1"])) {
            $data["votacao"]["votos"]["1"] = $_POST["voto"];
        }
    }

    if (isset($_POST["ausente"])) {
        $vereador_id = $_POST["ausente"];
        if (in_array($vereador_id, $data["ausentes"])) {
            $data["ausentes"] = array_diff($data["ausentes"], [$vereador_id]);
        } else {
            $data["ausentes"][] = $vereador_id;
        }
    }

    file_put_contents("db.json", json_encode($data, JSON_PRETTY_PRINT));
    exit();
}

$votos = array_count_values($data["votacao"]["votos"]);

// Mapeamento de vereadores
$vereadores = [
    1 => 'Rodrigo Alves',
    2 => 'Eduardo Almeida',
    3 => 'Paula Lima',
    4 => 'Geraldinho das Almas',
    5 => 'João Pedro Barcelos',
    6 => 'Luis Ricardo Bomba',
    7 => 'Jader "Dois Irmãos"',
    8 => 'Cabo Jota',
    9 => 'João Batista Nenê',
    10 => 'Silvania Lopes',
    11 => 'Julio Moraes'
];
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Presidente</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let lastData = "";

        function checkForUpdates() {
            $.getJSON("db.json", function(newData) {
                let newDataString = JSON.stringify(newData);
                
                if (lastData && lastData !== newDataString) {
                    location.reload();
                }

                lastData = newDataString;
            });
        }

        $(document).ready(function() {
            setInterval(checkForUpdates, 2000); // Verifica a cada 2 segundos
        });
    </script>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <h2>Painel do Presidente</h2>
        <p><strong>Status da Votação:</strong> <?= $data["votacao"]["ativa"] ? "<span class='text-success'>Aberta</span>" : "<span class='text-danger'>Encerrada</span>" ?></p>

        <form method="post">
            <button name="iniciar" class="btn btn-success">Iniciar Nova Votação</button>
            <button name="encerrar" class="btn btn-danger">Encerrar Votação</button>
            <button name="secreta" class="btn btn-primary">
                <?= $data["votacao"]["secreta"] ? "Desativar" : "Ativar" ?> Votação Secreta
            </button>
            <button name="zerar" class="btn btn-warning">Zerar Votação</button>
        </form>

        <h3 class="mt-4">Resultados</h3>
        <p>✅ Sim: <?= $votos["Sim"] ?? 0 ?></p>
        <p>❌ Não: <?= $votos["Não"] ?? 0 ?></p>
        <p>⚪ Abstenção: <?= $votos["Abstenção"] ?? 0 ?></p>

        <?php if (!$data["votacao"]["secreta"]) : ?>
            <h3>Votos Individuais</h3>
            <ul class="list-group">
                <?php foreach ($data["votacao"]["votos"] as $vereador => $voto) : ?>
                    <li class="list-group-item">
                        <?= $vereadores[$vereador] ?>: <?= $voto ?>
                        <?php if (in_array($vereador, $data["ausentes"])) : ?>
                            <span class="text-danger"> (Ausente)</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($data["votacao"]["ativa"] && !isset($data["votacao"]["votos"]["1"])): ?>
            <h2 class="mt-4">VOTE ABAIXO</h2>
            <form method="post">
                <button name="voto" value="Sim" class="btn btn-success">✅ Sim</button>
                <button name="voto" value="Não" class="btn btn-danger">❌ Não</button>
                <button name="voto" value="Abstenção" class="btn btn-secondary">⚪ Abstenção</button>
            </form>
        <?php else: ?>
            <p><strong>Seu voto:</strong> <?= $data["votacao"]["votos"]["1"] ?? "Já votado" ?></p>
        <?php endif; ?>

        <h3 class="mt-4">Marcar Ausentes</h3>
        <form method="post">
            <div class="list-group">
                <?php foreach ($vereadores as $key => $nome) : ?>
                    <label class="list-group-item">
                        <input type="checkbox" value="<?= $key ?>" <?= in_array($key, $data["ausentes"]) ? 'checked' : '' ?> class="ausente-checkbox">
                        <?= $nome ?> <?= in_array($key, $data["ausentes"]) ? "(Ausente)" : "(Presente)" ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </form>

        <a href="logout.php" class="btn btn-warning mt-3">Sair</a>
    </div>

    <script>
        $(document).on("change", ".ausente-checkbox", function() {
            let vereador_id = $(this).val();
            $.post("", { ausente: vereador_id });
        });
    </script>
</body>
</html>
