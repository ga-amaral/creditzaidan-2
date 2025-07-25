<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar se o usuário está logado
verificarLogin();

// Processar cupom de desconto
$desconto = 0;
$cupom = null;
$total_com_desconto = 0;

if (isset($_POST['aplicar_cupom'])) {
    verificarTokenCSRF($_POST['csrf_token']);
    
    $codigo_cupom = strtoupper(sanitizeInput($_POST['codigo_cupom']));
    $cupom = verificarCupom($codigo_cupom);
    
    if ($cupom) {
        $desconto = $cupom['desconto'];
        $_SESSION['cupom'] = $cupom;
    } else {
        $erro = "Cupom inválido ou expirado";
        unset($_SESSION['cupom']);
    }
}

// Processar ações do carrinho
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarTokenCSRF($_POST['csrf_token']);
    
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'adicionar':
                if (isset($_POST['pacote_id']) && isset($_POST['quantidade'])) {
                    $pacote_id = (int)$_POST['pacote_id'];
                    $quantidade = (int)$_POST['quantidade'];
                    
                    if (!isset($_SESSION['carrinho'])) {
                        $_SESSION['carrinho'] = [];
                    }
                    
                    if (isset($_SESSION['carrinho'][$pacote_id])) {
                        $_SESSION['carrinho'][$pacote_id] += $quantidade;
                    } else {
                        $_SESSION['carrinho'][$pacote_id] = $quantidade;
                    }
                    
                    // Se for uma requisição AJAX, retorna sucesso
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                        exit;
                    }
                    
                    $mensagem = "Pacote adicionado ao carrinho com sucesso!";
                }
                break;
                
            case 'atualizar':
                if (isset($_POST['quantidade']) && isset($_POST['pacote_id'])) {
                    $pacote_id = (int)$_POST['pacote_id'];
                    $quantidade = (int)$_POST['quantidade'];
                    
                    if ($quantidade > 0) {
                        $_SESSION['carrinho'][$pacote_id] = $quantidade;
                    } else {
                        unset($_SESSION['carrinho'][$pacote_id]);
                    }
                }
                break;
                
            case 'remover':
                if (isset($_POST['pacote_id'])) {
                    $pacote_id = (int)$_POST['pacote_id'];
                    unset($_SESSION['carrinho'][$pacote_id]);
                }
                break;
                
            case 'limpar':
                $_SESSION['carrinho'] = [];
                unset($_SESSION['cupom']);
                header("Location: carrinho.php");
                exit();
                break;
        }
    }
}

// Adicionar item ao carrinho via GET
if (isset($_GET['adicionar'])) {
    $pacote_id = (int)$_GET['adicionar'];
    $quantidade = isset($_GET['quantidade']) ? (int)$_GET['quantidade'] : 1;
    
    if (!isset($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }
    
    $_SESSION['carrinho'][$pacote_id] = isset($_SESSION['carrinho'][$pacote_id]) 
        ? $_SESSION['carrinho'][$pacote_id] + $quantidade 
        : $quantidade;
    
    header("Location: carrinho.php");
    exit();
}

// Buscar itens do carrinho
$itens_carrinho = [];
$total = 0;

if (!empty($_SESSION['carrinho'])) {
    $ids = array_keys($_SESSION['carrinho']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    $stmt = $conn->prepare("SELECT * FROM pacotes_wcoin WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $pacotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($pacotes as $pacote) {
        $quantidade = $_SESSION['carrinho'][$pacote['id']];
        $subtotal = $pacote['preco'] * $quantidade;
        $total += $subtotal;
        
        $itens_carrinho[] = [
            'id' => $pacote['id'],
            'nome' => $pacote['nome'],
            'quantidade' => $quantidade,
            'preco' => $pacote['preco'],
            'subtotal' => $subtotal,
            'descricao' => $pacote['descricao']
        ];
    }
}

// Aplicar desconto do cupom se existir
if (isset($_SESSION['cupom'])) {
    $cupom = $_SESSION['cupom'];
    $desconto = $cupom['desconto'];
    $total_com_desconto = $total - ($total * ($desconto / 100));
} else {
    $total_com_desconto = $total;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho - Credits Zaidan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .carrinho-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .carrinho-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
        }
        
        .carrinho-header h1 {
            color: #4CAF50;
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .carrinho-vazio {
            text-align: center;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            margin-bottom: 20px;
        }
        
        .carrinho-vazio p {
            font-size: 18px;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .cupom-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .cupom-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .cupom-form input {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
            color: white;
            font-size: 16px;
        }
        
        .cupom-form button {
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .cupom-form button:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        
        .cupom-ativo {
            color: #4CAF50;
            margin-top: 15px;
            font-weight: bold;
            font-size: 16px;
            padding: 10px;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 8px;
            display: inline-block;
        }
        
        .carrinho-itens {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .carrinho-item {
            display: grid;
            grid-template-columns: 100px 1fr auto auto auto;
            gap: 20px;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .carrinho-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .item-imagem {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .item-imagem img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-info h3 {
            color: #4CAF50;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .item-info p {
            color: #ccc;
            margin-bottom: 5px;
        }
        
        .item-quantidade {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .item-quantidade input {
            width: 60px;
            padding: 8px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
            color: white;
            text-align: center;
            font-size: 16px;
        }
        
        .item-preco {
            font-size: 20px;
            color: #FFC107;
            font-weight: bold;
        }
        
        .item-remover button {
            background: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .item-remover button:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }
        
        .carrinho-total {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .carrinho-total h2 {
            color: #4CAF50;
            margin-bottom: 15px;
            font-size: 24px;
        }
        
        .carrinho-total p {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .valor-desconto {
            color: #f44336;
            font-weight: bold;
        }
        
        .total-final {
            font-size: 24px;
            color: #FFC107;
            font-weight: bold;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .carrinho-acoes {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 30px;
            margin-bottom: 50px;
        }
        
        .btn {
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 16px;
        }
        
        .btn-continuar {
            background: #2196F3;
            color: white;
        }
        
        .btn-continuar:hover {
            background: #1976D2;
            transform: translateY(-2px);
        }
        
        .btn-limpar {
            background: #f44336;
            color: white;
        }
        
        .btn-limpar:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }
        
        .btn-finalizar {
            background: #4CAF50;
            color: white;
            flex: 1;
        }
        
        .btn-finalizar:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        
        .erro {
            color: #f44336;
            background: rgba(244, 67, 54, 0.1);
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
        }
    </style>
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
        <div class="carrinho-container">
            <div class="carrinho-header">
                <h1>Seu Carrinho</h1>
                <p>Revise seus itens antes de finalizar a compra</p>
            </div>
            
            <?php if (empty($itens_carrinho)): ?>
                <div class="carrinho-vazio">
                    <p>Seu carrinho está vazio</p>
                    <a href="index.php" class="btn btn-continuar">Continuar Comprando</a>
                </div>
            <?php else: ?>
                <div class="cupom-container">
                    <form method="POST" action="" class="cupom-form" id="cupomForm">
                        <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                        <input type="text" name="codigo_cupom" placeholder="Digite o código do cupom" required>
                        <button type="submit" name="aplicar_cupom">Aplicar Cupom</button>
                    </form>
                    
                    <?php if (isset($erro)): ?>
                        <div class="erro"><?php echo $erro; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($cupom)): ?>
                        <div class="cupom-ativo">
                            Cupom ativo: <?php echo $cupom['codigo']; ?> (<?php echo $desconto; ?>% de desconto)
                        </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST" action="" id="carrinhoForm">
                    <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                    <input type="hidden" name="acao" value="atualizar">
                    
                    <div class="carrinho-itens">
                        <?php foreach ($itens_carrinho as $item): ?>
                            <div class="carrinho-item" data-id="<?php echo $item['id']; ?>">
                                <div class="item-imagem">
                                    <img src="assets/images/wcoinimg.jpg" alt="WCOIN">
                                </div>
                                <div class="item-info">
                                    <h3><?php echo $item['nome']; ?></h3>
                                    <p><?php echo $item['descricao']; ?></p>
                                </div>
                                <div class="item-quantidade">
                                    <input type="number" 
                                           name="quantidade[<?php echo $item['id']; ?>]" 
                                           value="<?php echo $item['quantidade']; ?>" 
                                           min="1" 
                                           class="quantidade-input"
                                           data-preco="<?php echo $item['preco']; ?>"
                                           data-id="<?php echo $item['id']; ?>">
                                </div>
                                <div class="item-preco" id="preco-<?php echo $item['id']; ?>">
                                    <?php echo formatarPreco($item['subtotal']); ?>
                                </div>
                                <div class="item-remover">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                                        <input type="hidden" name="acao" value="remover">
                                        <input type="hidden" name="pacote_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit">Remover</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="carrinho-total">
                        <h2>Total do Carrinho</h2>
                        <p>Subtotal: <span id="subtotal"><?php echo formatarPreco($total); ?></span></p>
                        <?php if ($desconto > 0): ?>
                            <p class="valor-desconto">Desconto (<?php echo $desconto; ?>%): -<span id="valor-desconto"><?php echo formatarPreco($total - $total_com_desconto); ?></span></p>
                        <?php endif; ?>
                        <p class="total-final">Total com Desconto: <span id="total-final"><?php echo formatarPreco($total_com_desconto); ?></span></p>
                    </div>
                    
                    <div class="carrinho-acoes">
                        <a href="index.php" class="btn btn-continuar">Continuar Comprando</a>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                            <input type="hidden" name="acao" value="limpar">
                            <button type="submit" class="btn btn-limpar">Limpar Carrinho</button>
                        </form>
                        <a href="checkout.php" class="btn btn-finalizar">Finalizar Compra</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Credits Zaidan - Todos os direitos reservados</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quantidadeInputs = document.querySelectorAll('.quantidade-input');
            
            // Prevenir envio do formulário principal ao pressionar Enter
            document.getElementById('carrinhoForm').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    return false;
                }
            });
            
            function atualizarValores() {
                let subtotal = 0;
                
                quantidadeInputs.forEach(input => {
                    const quantidade = parseInt(input.value);
                    const preco = parseFloat(input.dataset.preco);
                    const itemId = input.dataset.id;
                    const subtotalItem = quantidade * preco;
                    
                    document.getElementById(`preco-${itemId}`).textContent = formatarPreco(subtotalItem);
                    subtotal += subtotalItem;
                });
                
                const desconto = <?php echo $desconto; ?>;
                const valorDesconto = subtotal * (desconto / 100);
                const totalFinal = subtotal - valorDesconto;
                
                document.getElementById('subtotal').textContent = formatarPreco(subtotal);
                if (desconto > 0) {
                    document.getElementById('valor-desconto').textContent = formatarPreco(valorDesconto);
                }
                document.getElementById('total-final').textContent = formatarPreco(totalFinal);
            }
            
            function formatarPreco(valor) {
                return 'R$ ' + valor.toFixed(2).replace('.', ',');
            }
            
            quantidadeInputs.forEach(input => {
                // Prevenir envio do formulário ao pressionar Enter
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.blur(); // Remove o foco do input
                        return false;
                    }
                });
                
                input.addEventListener('change', function() {
                    const quantidade = parseInt(this.value);
                    if (quantidade < 1) {
                        this.value = 1;
                        return;
                    }
                    
                    // Criar formulário para atualizar quantidade
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';
                    
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = 'csrf_token';
                    csrfToken.value = document.querySelector('input[name="csrf_token"]').value;
                    
                    const acao = document.createElement('input');
                    acao.type = 'hidden';
                    acao.name = 'acao';
                    acao.value = 'atualizar';
                    
                    const pacoteId = document.createElement('input');
                    pacoteId.type = 'hidden';
                    pacoteId.name = 'pacote_id';
                    pacoteId.value = this.dataset.id;
                    
                    const quantidadeInput = document.createElement('input');
                    quantidadeInput.type = 'hidden';
                    quantidadeInput.name = 'quantidade';
                    quantidadeInput.value = quantidade;
                    
                    form.appendChild(csrfToken);
                    form.appendChild(acao);
                    form.appendChild(pacoteId);
                    form.appendChild(quantidadeInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                });
            });
            
            // Atualizar valores iniciais
            atualizarValores();
        });
    </script>
</body>
</html> 