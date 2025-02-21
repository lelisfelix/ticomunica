<?php
session_start();

// Verifica se o usuário está logado e se é o super admin (Rodrigo Alves)
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

// Ações do super admin
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["iniciar"])) {
        $data["votacao"] = [
            "ativa" => true,
            "secreta" => $data["votacao"]["secreta"],
            "votos" => []
        ];
    }

    // Zerando a votação
    if (isset($_POST["zerar"])) {
        $data["votacao"]["votos"] = []; // Limpa todos os votos
        file_put_contents("db.json", json_encode($data, JSON_PRETTY_PRINT));
        header("Refresh:0");
    }

    if (isset($_POST["encerrar"])) {
        $data["votacao"]["ativa"] = false;
    }

    if (isset($_POST["secreta"])) {
        $data["votacao"]["secreta"] = !$data["votacao"]["secreta"];
    }

    // Registrando o voto do super admin
    if (isset($_POST["voto"])) {
        $vereador_id = $_POST["vereador_id"];
        $voto = $_POST["voto"];
        $data["votacao"]["votos"][$vereador_id] = $voto;
    }

    // Marcar como ausente
    if (isset($_POST["ausente"])) {
        $vereador_id = $_POST["ausente"];
        if (in_array($vereador_id, $data["ausentes"])) {
            // Se já está ausente, remove da lista
            $data["ausentes"] = array_diff($data["ausentes"], [$vereador_id]);
        } else {
            // Adiciona como ausente
            $data["ausentes"][] = $vereador_id;
        }
    }

    file_put_contents("db.json", json_encode($data, JSON_PRETTY_PRINT));
    header("Refresh:0");
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
    <title>Painel do Super Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            overflow-x: hidden;
        }
        .container {
            padding-left: 30px;
            padding-right: 30px;
            width: 100%;
            max-width: 100%;
        }
        .btn-custom {
            font-size: 20px;
            padding: 15px 30px;
            width: 100%;
            text-align: center;
            margin-bottom: 15px;
        }
        h2, h3, p, li { font-size: 20px; }
        .voto-msg {
            font-size: 20px;
            font-weight: bold;
        }
        .nome-vereador {
            text-transform: uppercase;
            font-size: 1.5rem;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <h2>Painel do Super Admin</h2>
        <p><strong>Status da Votação:</strong> <?= $data["votacao"]["ativa"] ? "<span class='text-success'>Aberta</span>" : "<span class='text-danger'>Encerrada</span>" ?></p>

        <form method="post">
            <button name="iniciar" class="btn btn-success btn-custom">Iniciar Nova Votação</button>
            <button name="encerrar" class="btn btn-danger btn-custom">Encerrar Votação</button>
            <button name="secreta" class="btn btn-primary btn-custom">
                <?= $data["votacao"]["secreta"] ? "Desativar" : "Ativar" ?> Votação Secreta
            </button>
            <button name="zerar" class="btn btn-warning btn-custom">Zerar Votação</button>
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

        <h3 class="mt-4">Votação dos Vereadores</h3>
        <?php foreach ($vereadores as $id => $nome): ?>
            <div class="form-group">
                <label for="voto_<?= $id ?>" class="nome-vereador"><?= $nome ?></label>

                <?php if (isset($data["votacao"]["votos"][$id])): ?>
                    <p class="voto-msg">Já Votou: <?= $data["votacao"]["votos"][$id] ?></p>
                <?php else: ?>
                    <button class="btn btn-success btn-custom mt-2" onclick="votar(<?= $id ?>, 'Sim')">✅ Sim</button>
                    <button class="btn btn-danger btn-custom mt-2" onclick="votar(<?= $id ?>, 'Não')">❌ Não</button>
                    <button class="btn btn-secondary btn-custom mt-2" onclick="votar(<?= $id ?>, 'Abstenção')">⚪ Abstenção</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <h3 class="mt-4">Marcar Ausentes</h3>
        <form id="ausentes-form">
            <div class="list-group">
                <?php
                foreach ($vereadores as $key => $nome) :
                ?>
                    <label class="list-group-item">
                        <input type="checkbox" value="<?= $key ?>" <?= in_array($key, $data["ausentes"]) ? 'checked' : '' ?> onclick="marcarAusente(<?= $key ?>)">
                        <?= $nome ?> <?= in_array($key, $data["ausentes"]) ? "(Ausente)" : "(Presente)" ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </form>

        <a href="logout.php" class="btn btn-warning btn-custom mt-3">Sair</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function votar(vereador_id, voto) {
            $.ajax({
                url: '',
                method: 'POST',
                data: {
                    voto: voto,
                    vereador_id: vereador_id
                },
                success: function(response) {
                    location.reload();  // Recarrega a página após votar
                }
            });
        }

        function marcarAusente(vereador_id) {
            $.ajax({
                url: '',
                method: 'POST',
                data: {
                    ausente: vereador_id
                },
                success: function(response) {
                    location.reload();  // Recarrega a página após marcar ausente
                }
            });
        }

        // Recarregar os resultados a cada 2 segundos
        setInterval(function() {
            location.reload();
        }, 2000);
    </script>
</body>
</html>
