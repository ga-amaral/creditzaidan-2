<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar se o usuário está logado
verificarLogin();

// Buscar itens do carrinho
$stmt = $conn->prepare("
    SELECT c.*, p.nome, p.preco 
    FROM carrinho c 
    JOIN pacotes_wcoin p ON c.pacote_id = p.id 
    WHERE c.usuario_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular total
$total = 0;
foreach ($itens as $item) {
    $total += $item['preco'] * $item['quantidade'];
}

// Processar cupom de desconto
$desconto = 0;
$cupom = null;
$total_com_desconto = $total;

if (isset($_POST['aplicar_cupom'])) {
    verificarTokenCSRF($_POST['csrf_token']);
    
    $codigo_cupom = strtoupper(sanitizeInput($_POST['codigo_cupom']));
    $cupom = verificarCupom($codigo_cupom);
    
    if ($cupom) {
        $desconto = $cupom['desconto'];
        $total_com_desconto = aplicarDesconto($total, $desconto);
        $sucesso = "Cupom aplicado com sucesso! Desconto de {$desconto}%";
    } else {
        $erro = "Cupom inválido ou expirado";
    }
}

// Processar pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_compra'])) {
    verificarTokenCSRF($_POST['csrf_token']);
    
    $metodo_pagamento = sanitizeInput($_POST['metodo_pagamento']);
    
    try {
        $conn->beginTransaction();
        
        // Criar transação
        $stmt = $conn->prepare("
            INSERT INTO transacoes (usuario_id, valor_total, metodo_pagamento) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $total_com_desconto, $metodo_pagamento]);
        $transacao_id = $conn->lastInsertId();
        
        // Registrar itens da transação
        foreach ($itens as $item) {
            $stmt = $conn->prepare("
                INSERT INTO itens_transacao (transacao_id, pacote_id, quantidade, preco_unitario) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$transacao_id, $item['pacote_id'], $item['quantidade'], $item['preco']]);
        }
        
        // Registrar uso do cupom se aplicado
        if ($cupom) {
            registrarUsoCupom($cupom['id']);
        }
        
        // Limpar carrinho
        $stmt = $conn->prepare("DELETE FROM carrinho WHERE usuario_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $conn->commit();
        
        // Redirecionar para página de sucesso
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
    <title>Checkout - Credits Zaidan</title>
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
        <section class="checkout">
            <h2>Finalizar Compra</h2>
            
            <?php if (isset($sucesso)): ?>
                <div class="sucesso"><?php echo $sucesso; ?></div>
            <?php endif; ?>
            
            <?php if (isset($erro)): ?>
                <div class="erro"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <div class="resumo-compra">
                <h3>Resumo da Compra</h3>
                
                <?php if (empty($itens)): ?>
                    <p>Seu carrinho está vazio.</p>
                    <a href="index.php" class="btn">Voltar para a Loja</a>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Pacote</th>
                                <th>Quantidade</th>
                                <th>Preço Unitário</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $item): ?>
                                <tr>
                                    <td><?php echo $item['nome']; ?></td>
                                    <td><?php echo $item['quantidade']; ?></td>
                                    <td><?php echo formatarPreco($item['preco']); ?></td>
                                    <td><?php echo formatarPreco($item['preco'] * $item['quantidade']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">Total:</td>
                                <td><?php echo formatarPreco($total); ?></td>
                            </tr>
                            <?php if ($desconto > 0): ?>
                                <tr>
                                    <td colspan="3">Desconto (<?php echo $desconto; ?>%):</td>
                                    <td>-<?php echo formatarPreco($total - $total_com_desconto); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3">Total com Desconto:</td>
                                    <td><?php echo formatarPreco($total_com_desconto); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tfoot>
                    </table>
                    
                    <div class="cupom-desconto">
                        <h4>Aplicar Cupom de Desconto</h4>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                            <input type="text" name="codigo_cupom" placeholder="Digite o código do cupom" required>
                            <button type="submit" name="aplicar_cupom">Aplicar</button>
                        </form>
                    </div>
                    
                    <div class="metodo-pagamento">
                        <h4>Escolha o Método de Pagamento</h4>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                            
                            <div class="opcoes-pagamento">
                                <label>
                                    <input type="radio" name="metodo_pagamento" value="pix" checked>
                                    <img src="assets/images/pix.png" alt="PIX">
                                    PIX
                                </label>
                                
                                <label>
                                    <input type="radio" name="metodo_pagamento" value="cartao">
                                    <img src="assets/images/cartao.png" alt="Cartão de Crédito">
                                    Cartão de Crédito
                                </label>
                            </div>
                            
                            <button type="submit" name="finalizar_compra" class="btn-pagamento">
                                Finalizar Compra
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Credits Zaidan - Todos os direitos reservados</p>
    </footer>
</body>
</html> 