<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Buscar estatísticas
$total_usuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE role = 'user'")->fetch()['total'];
$total_transacoes = $conn->query("SELECT COUNT(*) as total FROM transacoes")->fetch()['total'];
$total_vendas = $conn->query("SELECT COALESCE(SUM(valor_total), 0) as total FROM transacoes WHERE status = 'pago'")->fetch()['total'];

// Buscar últimas transações
$stmt = $conn->query("
    SELECT t.*, u.nome as usuario_nome 
    FROM transacoes t 
    JOIN usuarios u ON t.usuario_id = u.id 
    ORDER BY t.data_transacao DESC 
    LIMIT 5
");
$ultimas_transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Credits Zaidan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0;
            color: #4CAF50;
        }
        .stat-card p {
            margin: 10px 0 0;
            font-size: 24px;
            font-weight: bold;
        }
        .ultimas-transacoes {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 8px;
        }
        .transacoes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .transacoes-table th,
        .transacoes-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .transacoes-table th {
            background: rgba(0, 0, 0, 0.2);
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
        }
        .status-aguardando { background: #FFA000; }
        .status-pago { background: #4CAF50; }
        .status-cancelado { background: #f44336; }
        .status-entregue { background: #2196F3; }
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
        <section class="admin-content">
            <h2>Dashboard</h2>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Total de Usuários</h3>
                    <p><?php echo $total_usuarios; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Total de Transações</h3>
                    <p><?php echo $total_transacoes; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Total de Vendas</h3>
                    <p><?php echo formatarPreco($total_vendas); ?></p>
                </div>
            </div>
            
            <div class="ultimas-transacoes">
                <h3>Últimas Transações</h3>
                
                <?php if (empty($ultimas_transacoes)): ?>
                    <p>Nenhuma transação realizada.</p>
                <?php else: ?>
                    <table class="transacoes-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>Valor</th>
                                <th>Método</th>
                                <th>Status</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimas_transacoes as $transacao): ?>
                                <tr>
                                    <td>#<?php echo $transacao['id']; ?></td>
                                    <td><?php echo $transacao['usuario_nome']; ?></td>
                                    <td><?php echo formatarPreco($transacao['valor_total']); ?></td>
                                    <td><?php echo ucfirst($transacao['metodo_pagamento']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $transacao['status']; ?>">
                                            <?php echo ucfirst($transacao['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($transacao['data_transacao'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Credits Zaidan - Todos os direitos reservados</p>
    </footer>
</body>
</html> 