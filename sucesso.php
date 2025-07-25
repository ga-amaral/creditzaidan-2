<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar se o usuário está logado
verificarLogin();

// Verificar se a transação existe e pertence ao usuário
if (!isset($_GET['transacao_id'])) {
    header("Location: index.php");
    exit();
}

$transacao_id = (int)$_GET['transacao_id'];

$stmt = $conn->prepare("
    SELECT t.*, GROUP_CONCAT(k.codigo) as keys_wcoin
    FROM transacoes t
    LEFT JOIN keys_wcoin k ON t.id = k.transacao_id
    WHERE t.id = ? AND t.usuario_id = ?
    GROUP BY t.id
");
$stmt->execute([$transacao_id, $_SESSION['user_id']]);
$transacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transacao) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Realizado - Credits Zaidan</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dark-theme">
    <header>
        <nav>
            <div class="logo">
                <h1>Credits Zaidan</h1>
            </div>
            <div class="nav-links">
                <a href="index.php">Início</a>
                <a href="carrinho.php">Carrinho</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="perfil.php">Meu Perfil</a>
                    <?php if (isAdmin()): ?>
                        <a href="admin/index.php">Painel Admin</a>
                    <?php endif; ?>
                    <a href="logout.php">Sair</a>
                <?php else: ?>
                    <a href="login.php">Entrar</a>
                    <a href="registro.php">Registrar</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <section class="sucesso">
            <h2>Pagamento Realizado com Sucesso!</h2>
            
            <div class="info-transacao">
                <p>ID da Transação: <?php echo $transacao_id; ?></p>
                <p>Valor: <?php echo formatarPreco($transacao['valor_total']); ?></p>
                <p>Data: <?php echo date('d/m/Y H:i', strtotime($transacao['data_transacao'])); ?></p>
            </div>
            
            <?php if (!empty($transacao['keys_wcoin'])): ?>
                <div class="keys-wcoin">
                    <h3>Suas Keys de WCOINs:</h3>
                    <ul>
                        <?php foreach (explode(',', $transacao['keys_wcoin']) as $key): ?>
                            <li><?php echo $key; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="instrucoes">
                    <h3>Instruções:</h3>
                    <ol>
                        <li>Copie as keys acima</li>
                        <li>Acesse o jogo Mu Online</li>
                        <li>Use as keys para resgatar seus WCOINs</li>
                    </ol>
                </div>
            <?php endif; ?>
            
            <div class="acoes">
                <a href="perfil.php" class="btn-primary">Ver Histórico de Compras</a>
                <a href="index.php" class="btn-secondary">Voltar para a Loja</a>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Credits Zaidan - Todos os direitos reservados</p>
    </footer>
</body>
</html> 