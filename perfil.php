<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Gerar novo token CSRF se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Função para formatar número de telefone
function formatarNumeroTelefone($numero) {
    if (empty($numero)) return '';
    
    // Remove caracteres não numéricos
    $numero = preg_replace('/[^0-9]/', '', $numero);
    
    // Formata o número
    if (strlen($numero) === 11) {
        return '(' . substr($numero, 0, 2) . ') ' . substr($numero, 2, 5) . '-' . substr($numero, 7);
    } elseif (strlen($numero) === 10) {
        return '(' . substr($numero, 0, 2) . ') ' . substr($numero, 2, 4) . '-' . substr($numero, 6);
    }
    
    return $numero;
}

// Buscar dados do usuário
$user_id = $_SESSION['user_id'];
try {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        // Se o usuário não for encontrado, redireciona para login
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
    // Formatar o número de telefone para exibição
    $usuario['telefone_celular'] = formatarNumeroTelefone($usuario['telefone_celular']);
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do usuário: " . $e->getMessage());
    die("Desculpe, ocorreu um erro ao carregar seus dados. Por favor, tente novamente mais tarde.");
}

// Processar atualização de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $erro = "Erro de validação do formulário. Por favor, tente novamente.";
    } else {
        if (isset($_POST['acao'])) {
            switch ($_POST['acao']) {
                case 'atualizar_perfil':
                    $nome = sanitizeInput($_POST['nome']);
                    $email = sanitizeInput($_POST['email']);
                    $telefone = sanitizeInput($_POST['telefone']);
                    
                    // Remover caracteres não numéricos do telefone
                    $telefone = preg_replace('/[^0-9]/', '', $telefone);
                    
                    // Validar telefone (deve ter entre 10 e 11 dígitos)
                    if (!empty($telefone) && (strlen($telefone) < 10 || strlen($telefone) > 11)) {
                        $erro = "Número de telefone inválido. Deve conter 10 ou 11 dígitos.";
                        break;
                    }
                    
                    try {
                        // Verificar se o email já existe
                        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
                        $stmt->execute([$email, $user_id]);
                        if ($stmt->rowCount() > 0) {
                            $erro = "Este e-mail já está em uso por outro usuário.";
                            break;
                        }
                        
                        // Atualizar dados do usuário
                        $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone_celular = ? WHERE id = ?");
                        if ($stmt->execute([$nome, $email, $telefone, $user_id])) {
                            $mensagem = "Perfil atualizado com sucesso!";
                            $_SESSION['user_nome'] = $nome;
                            
                            // Recarregar dados do usuário
                            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
                            $stmt->execute([$user_id]);
                            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Gerar novo token CSRF após sucesso
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        } else {
                            $erro = "Erro ao atualizar perfil. Tente novamente.";
                        }
                    } catch (PDOException $e) {
                        error_log("Erro ao atualizar perfil: " . $e->getMessage());
                        $erro = "Erro ao atualizar perfil. Por favor, tente novamente.";
                    }
                    break;
                    
                case 'alterar_senha':
                    $senha_atual = $_POST['senha_atual'];
                    $nova_senha = $_POST['nova_senha'];
                    $confirmar_senha = $_POST['confirmar_senha'];
                    
                    // Verificar senha atual
                    if (password_verify($senha_atual, $usuario['senha'])) {
                        if ($nova_senha === $confirmar_senha) {
                            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                            $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                            if ($stmt->execute([$senha_hash, $user_id])) {
                                $mensagem = "Senha alterada com sucesso!";
                            } else {
                                $erro = "Erro ao alterar senha. Tente novamente.";
                            }
                        } else {
                            $erro = "As senhas não coincidem.";
                        }
                    } else {
                        $erro = "Senha atual incorreta.";
                    }
                    break;
            }
        }
    }
}

// Buscar histórico de compras
try {
    $stmt = $conn->prepare("
        SELECT t.*, p.nome as pacote_nome 
        FROM transacoes t 
        JOIN pacotes_wcoin p ON t.pacote_id = p.id 
        WHERE t.usuario_id = ? 
        ORDER BY t.data_transacao DESC
    ");
    $stmt->execute([$user_id]);
    $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar histórico de compras: " . $e->getMessage());
    $compras = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Credits Zaidan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .perfil-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .perfil-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
        }
        
        .perfil-header h1 {
            color: #4CAF50;
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .perfil-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .perfil-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .perfil-card h2 {
            color: #4CAF50;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ccc;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
            color: white;
            font-size: 16px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 16px;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        
        .historico-compras {
            margin-top: 30px;
        }
        
        .compra-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            align-items: center;
        }
        
        .compra-info h3 {
            color: #4CAF50;
            margin-bottom: 5px;
        }
        
        .compra-info p {
            color: #ccc;
            margin-bottom: 5px;
        }
        
        .compra-valor {
            font-size: 20px;
            color: #FFC107;
            font-weight: bold;
        }
        
        .mensagem {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .mensagem.sucesso {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }
        
        .mensagem.erro {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
        }
        
        @media (max-width: 768px) {
            .perfil-grid {
                grid-template-columns: 1fr;
            }
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
        <div class="perfil-container">
            <div class="perfil-header">
                <h1>Meu Perfil</h1>
                <p>Gerencie suas informações e visualize seu histórico de compras</p>
            </div>
            
            <?php if (isset($mensagem)): ?>
                <div class="mensagem sucesso"><?php echo $mensagem; ?></div>
            <?php endif; ?>
            
            <?php if (isset($erro)): ?>
                <div class="mensagem erro"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <div class="perfil-grid">
                <div class="perfil-card">
                    <h2>Informações Pessoais</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="acao" value="atualizar_perfil">
                        
                        <div class="form-group">
                            <label for="nome">Nome Completo</label>
                            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="telefone">Telefone Celular</label>
                            <input type="tel" id="telefone" name="telefone" 
                                   value="<?php echo htmlspecialchars($usuario['telefone_celular']); ?>"
                                   placeholder="(00) 00000-0000"
                                   oninput="formatarTelefone(this)"
                                   maxlength="15">
                            <small class="form-text">Formato: (00) 00000-0000</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </form>
                </div>
                
                <div class="perfil-card">
                    <h2>Alterar Senha</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="acao" value="alterar_senha">
                        
                        <div class="form-group">
                            <label for="senha_atual">Senha Atual</label>
                            <input type="password" id="senha_atual" name="senha_atual" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="nova_senha">Nova Senha</label>
                            <input type="password" id="nova_senha" name="nova_senha" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmar_senha">Confirmar Nova Senha</label>
                            <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Alterar Senha</button>
                    </form>
                </div>
            </div>
            
            <div class="historico-compras">
                <h2>Histórico de Compras</h2>
                <?php if (empty($compras)): ?>
                    <p>Nenhuma compra realizada ainda.</p>
                <?php else: ?>
                    <?php foreach ($compras as $compra): ?>
                        <div class="compra-item">
                            <div class="compra-info">
                                <h3><?php echo htmlspecialchars($compra['pacote_nome']); ?></h3>
                                <p>Data: <?php echo date('d/m/Y H:i', strtotime($compra['data_transacao'])); ?></p>
                                <p>Status: <?php echo ucfirst($compra['status']); ?></p>
                            </div>
                            <div class="compra-valor">
                                <?php echo formatarPreco($compra['valor_total']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Credits Zaidan - Todos os direitos reservados</p>
    </footer>

    <script>
        function formatarTelefone(input) {
            // Remove tudo que não é número
            let numero = input.value.replace(/\D/g, '');
            
            // Limita a 11 dígitos
            numero = numero.substring(0, 11);
            
            // Formata o número
            if (numero.length > 0) {
                if (numero.length <= 2) {
                    numero = '(' + numero;
                } else if (numero.length <= 6) {
                    numero = '(' + numero.substring(0, 2) + ') ' + numero.substring(2);
                } else if (numero.length <= 10) {
                    numero = '(' + numero.substring(0, 2) + ') ' + numero.substring(2, 6) + '-' + numero.substring(6);
                } else {
                    numero = '(' + numero.substring(0, 2) + ') ' + numero.substring(2, 7) + '-' + numero.substring(7);
                }
            }
            
            // Atualiza o valor do input
            input.value = numero;
        }
        
        // Formatar telefone ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            const telefoneInput = document.getElementById('telefone');
            if (telefoneInput.value) {
                formatarTelefone(telefoneInput);
            }
        });
    </script>
</body>
</html> 