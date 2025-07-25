<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarTokenCSRF($_POST['csrf_token']);
    
    if (isset($_POST['acao'])) {
        $usuario_id = (int)$_POST['usuario_id'];
        
        switch ($_POST['acao']) {
            case 'alterar_status':
                $novo_status = (int)$_POST['status'];
                try {
                    $stmt = $conn->prepare("UPDATE usuarios SET ativo = ? WHERE id = ?");
                    $stmt->execute([$novo_status, $usuario_id]);
                    $sucesso = "Status do usuário atualizado com sucesso!";
                } catch (Exception $e) {
                    $erro = "Erro ao atualizar status do usuário: " . $e->getMessage();
                }
                break;
                
            case 'alterar_role':
                $novo_role = sanitizeInput($_POST['role']);
                try {
                    // Verificar se é o último admin
                    if ($novo_role === 'user') {
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE role = 'admin' AND id != ?");
                        $stmt->execute([$usuario_id]);
                        $total_admins = $stmt->fetch()['total'];
                        
                        if ($total_admins == 0) {
                            throw new Exception("Não é possível remover o último administrador do sistema.");
                        }
                    }
                    
                    $stmt = $conn->prepare("UPDATE usuarios SET role = ? WHERE id = ?");
                    $stmt->execute([$novo_role, $usuario_id]);
                    $sucesso = "Função do usuário atualizada com sucesso!";
                } catch (Exception $e) {
                    $erro = "Erro ao atualizar função do usuário: " . $e->getMessage();
                }
                break;
                
            case 'banir':
                try {
                    $stmt = $conn->prepare("UPDATE usuarios SET banido = 1 WHERE id = ?");
                    $stmt->execute([$usuario_id]);
                    $sucesso = "Usuário banido com sucesso!";
                } catch (Exception $e) {
                    $erro = "Erro ao banir usuário: " . $e->getMessage();
                }
                break;
                
            case 'desbanir':
                try {
                    $stmt = $conn->prepare("UPDATE usuarios SET banido = 0 WHERE id = ?");
                    $stmt->execute([$usuario_id]);
                    $sucesso = "Usuário desbanido com sucesso!";
                } catch (Exception $e) {
                    $erro = "Erro ao desbanir usuário: " . $e->getMessage();
                }
                break;
                
            case 'excluir':
                try {
                    // Verificar se o usuário tem transações
                    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM transacoes WHERE usuario_id = ?");
                    $stmt->execute([$usuario_id]);
                    $tem_transacoes = $stmt->fetch()['total'] > 0;
                    
                    if ($tem_transacoes) {
                        $erro = "Não é possível excluir um usuário que possui transações.";
                    } else {
                        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                        $stmt->execute([$usuario_id]);
                        $sucesso = "Usuário excluído com sucesso!";
                    }
                } catch (Exception $e) {
                    $erro = "Erro ao excluir usuário: " . $e->getMessage();
                }
                break;
        }
    }
}

// Buscar usuários
$stmt = $conn->query("
    SELECT u.*, 
           COUNT(DISTINCT t.id) as total_transacoes,
           COALESCE(SUM(CASE WHEN t.status = 'pago' THEN t.valor_total ELSE 0 END), 0) as total_gasto
    FROM usuarios u
    LEFT JOIN transacoes t ON u.id = t.usuario_id
    GROUP BY u.id
    ORDER BY u.nome
");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Credits Zaidan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .usuarios-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .usuarios-table th,
        .usuarios-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .usuarios-table th {
            background: rgba(0, 0, 0, 0.2);
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
        }
        .status-ativo { background: #4CAF50; }
        .status-inativo { background: #f44336; }
        .role-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
        }
        .role-admin { background: #9C27B0; }
        .role-user { background: #2196F3; }
        .ban-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            background: #FF5722;
        }
        .btn-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-group button {
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            white-space: nowrap;
        }
        .btn-status { background: #2196F3; }
        .btn-role { background: #9C27B0; }
        .btn-ban { background: #FF5722; }
        .btn-excluir { background: #f44336; }
        .select-role {
            padding: 6px;
            border-radius: 4px;
            background: #333;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="dark-theme">
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

    <main>
        <section class="gerenciar-usuarios">
            <h2>Gerenciar Usuários</h2>
            
            <?php if (isset($sucesso)): ?>
                <div class="sucesso"><?php echo $sucesso; ?></div>
            <?php endif; ?>
            
            <?php if (isset($erro)): ?>
                <div class="erro"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <?php if (empty($usuarios)): ?>
                <p>Nenhum usuário cadastrado.</p>
            <?php else: ?>
                <table class="usuarios-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Função</th>
                            <th>Banido</th>
                            <th>Total Transações</th>
                            <th>Total Gasto</th>
                            <th>Data Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo $usuario['id']; ?></td>
                                <td><?php echo $usuario['nome']; ?></td>
                                <td><?php echo $usuario['email']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $usuario['ativo'] ? 'ativo' : 'inativo'; ?>">
                                        <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="role-badge role-<?php echo $usuario['role']; ?>">
                                        <?php echo ucfirst($usuario['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($usuario['banido']): ?>
                                        <span class="ban-badge">Banido</span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $usuario['total_transacoes']; ?></td>
                                <td><?php echo formatarPreco($usuario['total_gasto']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                                            <input type="hidden" name="acao" value="alterar_status">
                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $usuario['ativo'] ? 0 : 1; ?>">
                                            <button type="submit" class="btn-status">
                                                <?php echo $usuario['ativo'] ? 'Desativar' : 'Ativar'; ?>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                                            <input type="hidden" name="acao" value="alterar_role">
                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                            <select name="role" class="select-role" onchange="this.form.submit()">
                                                <option value="user" <?php echo $usuario['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                <option value="admin" <?php echo $usuario['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                        </form>
                                        
                                        <?php if (!$usuario['banido']): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                                                <input type="hidden" name="acao" value="banir">
                                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                <button type="submit" class="btn-ban" onclick="return confirm('Tem certeza que deseja banir este usuário?')">
                                                    Banir
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                                                <input type="hidden" name="acao" value="desbanir">
                                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                <button type="submit" class="btn-ban">
                                                    Desbanir
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($usuario['total_transacoes'] == 0): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                                                <input type="hidden" name="acao" value="excluir">
                                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                <button type="submit" class="btn-excluir" onclick="return confirm('Tem certeza que deseja excluir este usuário?')">
                                                    Excluir
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Credits Zaidan - Todos os direitos reservados</p>
    </footer>
</body>
</html> 