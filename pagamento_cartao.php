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

$stmt = $conn->prepare("SELECT * FROM transacoes WHERE id = ? AND usuario_id = ?");
$stmt->execute([$transacao_id, $_SESSION['user_id']]);
$transacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transacao) {
    header("Location: index.php");
    exit();
}

// Processar pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarTokenCSRF($_POST['csrf_token']);
    
    // Aqui você implementaria a integração com uma API de pagamento
    // como PagSeguro, Mercado Pago, etc.
    
    // Exemplo de processamento (substitua pela integração real)
    $numero_cartao = str_replace(' ', '', $_POST['numero_cartao']);
    $nome_titular = sanitizeInput($_POST['nome_titular']);
    $validade = sanitizeInput($_POST['validade']);
    $cvv = sanitizeInput($_POST['cvv']);
    
    // Simulação de processamento bem-sucedido
    try {
        $conn->beginTransaction();
        
        // Atualizar status da transação
        $stmt = $conn->prepare("UPDATE transacoes SET status = 'pago' WHERE id = ?");
        $stmt->execute([$transacao_id]);
        
        // Gerar e salvar keys de WCOINs
        $stmt = $conn->prepare("SELECT pacote_id, quantidade FROM itens_transacao WHERE transacao_id = ?");
        $stmt->execute([$transacao_id]);
        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($itens as $item) {
            $stmt = $conn->prepare("SELECT quantidade FROM pacotes_wcoin WHERE id = ?");
            $stmt->execute([$item['pacote_id']]);
            $pacote = $stmt->fetch(PDO::FETCH_ASSOC);
            
            for ($i = 0; $i < $item['quantidade']; $i++) {
                $codigo = strtoupper(substr(md5(uniqid(rand(), true)), 0, 16));
                $stmt = $conn->prepare("INSERT INTO keys_wcoin (transacao_id, codigo) VALUES (?, ?)");
                $stmt->execute([$transacao_id, $codigo]);
            }
        }
        
        $conn->commit();
        header("Location: sucesso.php?transacao_id=" . $transacao_id);
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $erro = "Erro ao processar pagamento: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Cartão - Credits Zaidan</title>
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
                <a href="perfil.php">Perfil</a>
                <a href="logout.php">Sair</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="pagamento-cartao">
            <h2>Pagamento com Cartão de Crédito</h2>
            
            <?php if (isset($erro)): ?>
                <div class="erro"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <div class="info-pagamento">
                <p>Valor: <?php echo formatarPreco($transacao['valor_total']); ?></p>
                <p>ID da Transação: <?php echo $transacao_id; ?></p>
            </div>
            
            <form method="POST" action="" class="form-cartao">
                <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                
                <div class="form-group">
                    <label for="numero_cartao">Número do Cartão:</label>
                    <input type="text" id="numero_cartao" name="numero_cartao" required 
                           pattern="[0-9\s]{13,19}" maxlength="19"
                           placeholder="0000 0000 0000 0000">
                </div>
                
                <div class="form-group">
                    <label for="nome_titular">Nome do Titular:</label>
                    <input type="text" id="nome_titular" name="nome_titular" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="validade">Validade:</label>
                        <input type="text" id="validade" name="validade" required 
                               pattern="(0[1-9]|1[0-2])\/[0-9]{2}" maxlength="5"
                               placeholder="MM/AA">
                    </div>
                    
                    <div class="form-group">
                        <label for="cvv">CVV:</label>
                        <input type="text" id="cvv" name="cvv" required 
                               pattern="[0-9]{3,4}" maxlength="4"
                               placeholder="000">
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">Finalizar Pagamento</button>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Credits Zaidan - Todos os direitos reservados</p>
    </footer>

    <script>
    // Formatar número do cartão
    document.getElementById('numero_cartao').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.replace(/(\d{4})/g, '$1 ').trim();
        e.target.value = value;
    });
    
    // Formatar validade
    document.getElementById('validade').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.slice(0,2) + '/' + value.slice(2);
        }
        e.target.value = value;
    });
    </script>
</body>
</html> 