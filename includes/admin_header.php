<?php if (!isset($page_title)) { $page_title = 'Admin'; } ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Credits Zaidan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="dark-theme admin">
    <header>
        <nav>
            <div class="logo">
                <h1>Credits Zaidan - Admin</h1>
            </div>
            <div class="nav-links">
                <a href="index.php">Dashboard</a>
                <a href="transacoes.php">Transações</a>
                <a href="usuarios.php">Usuários</a>
                <a href="pacotes.php">Pacotes</a>
                <a href="cupons.php">Cupons</a>
                <a href="../index.php">Voltar ao Site</a>
                <a href="../logout.php">Sair</a>
            </div>
        </nav>
    </header>
