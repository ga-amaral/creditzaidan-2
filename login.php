<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Se já estiver logado, redireciona para a página inicial
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $senha = $_POST['senha'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Login bem-sucedido
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_nome'] = $usuario['nome'];
            $_SESSION['user_role'] = $usuario['role'];
            
            // Redirecionar para a página inicial
            header("Location: index.php");
            exit();
        } else {
            $erro = "E-mail ou senha incorretos.";
        }
    } catch (PDOException $e) {
        error_log("Erro no login: " . $e->getMessage());
        $erro = "Ocorreu um erro ao tentar fazer login. Por favor, tente novamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Credits Zaidan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        }
        
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #4CAF50;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #ccc;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ccc;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }
        
        .form-group input:focus + label {
            color: #4CAF50;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #ccc;
        }
        
        .login-footer a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .login-footer a:hover {
            color: #45a049;
            text-decoration: underline;
        }
        
        .erro {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 40px;
            color: #ccc;
            cursor: pointer;
            user-select: none;
        }
        
        .password-toggle:hover {
            color: #4CAF50;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 28px;
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

    <main class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Bem-vindo de volta!</h1>
                <p>Entre com suas credenciais para acessar sua conta</p>
            </div>
            
            <?php if ($erro): ?>
                <div class="erro"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="seu@email.com"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required 
                           placeholder="Digite sua senha">
                    <span class="password-toggle" onclick="togglePassword()">👁️</span>
                </div>
                
                <button type="submit" class="btn-login">Entrar</button>
            </form>
            
            <div class="login-footer">
                <p>Não tem uma conta? <a href="registro.php">Registre-se</a></p>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Credits Zaidan - Todos os direitos reservados</p>
    </footer>

    <script>
        function togglePassword() {
            const senhaInput = document.getElementById('senha');
            const toggleButton = document.querySelector('.password-toggle');
            
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                toggleButton.textContent = '👁️‍🗨️';
            } else {
                senhaInput.type = 'password';
                toggleButton.textContent = '👁️';
            }
        }
        
        // Adicionar animação de foco nos inputs
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
        });
    </script>
</body>
</html> 