<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome']);
    $email = sanitizeInput($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = "Todos os campos são obrigatórios";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem";
    } else {
        // Verificar se email já existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $erro = "Este email já está cadastrado";
        } else {
            // Inserir novo usuário
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, role) VALUES (?, ?, ?, 'user')");
            
            if ($stmt->execute([$nome, $email, $senha_hash])) {
                header("Location: login.php?registro=sucesso");
                exit();
            } else {
                $erro = "Erro ao cadastrar usuário";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Credits Zaidan</title>
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
                <a href="login.php">Login</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="registro-form">
            <h2>Registro</h2>
            <?php if (isset($erro)): ?>
                <div class="erro"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                
                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha:</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                </div>
                
                <button type="submit" class="btn-primary">Registrar</button>
            </form>
            
            <p>Já tem uma conta? <a href="login.php">Faça login</a></p>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Credits Zaidan - Todos os direitos reservados</p>
    </footer>
</body>
</html> 