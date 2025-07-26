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
        try {
            // Verificar se email já existe
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $erro = "Este email já está cadastrado";
            } else {
                // Inserir novo usuário
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, role) VALUES (?, ?, ?, 'user')");
                $stmt->execute([$nome, $email, $senha_hash]);

                header("Location: login.php?registro=sucesso");
                exit();
            }
        } catch (PDOException $e) {
            error_log('Erro no registro: ' . $e->getMessage());
            $erro = 'Ocorreu um erro ao tentar registrar. Por favor, tente novamente.';
        }
    }
}
?>
