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

$page_title = 'Painel Administrativo';
include '../includes/admin_header.php';
?>

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

<?php include '../includes/admin_footer.php'; ?>
