<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Processar atualização de status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_status'])) {
    verificarTokenCSRF($_POST['csrf_token']);
    
    $transacao_id = (int)$_POST['transacao_id'];
    $novo_status = sanitizeInput($_POST['novo_status']);
    
    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("UPDATE transacoes SET status = ? WHERE id = ?");
        $stmt->execute([$novo_status, $transacao_id]);
        
        // Se o status for alterado para "pago", gerar as keys
        if ($novo_status === 'pago') {
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
        }
        
        $conn->commit();
        $sucesso = "Status da transação atualizado com sucesso!";
    } catch (Exception $e) {
        $conn->rollBack();
        $erro = "Erro ao atualizar status: " . $e->getMessage();
    }
}

// Buscar transações
$stmt = $conn->query("
    SELECT t.*, u.nome as usuario_nome, GROUP_CONCAT(k.codigo) as keys_wcoin
    FROM transacoes t
    JOIN usuarios u ON t.usuario_id = u.id
    LEFT JOIN keys_wcoin k ON t.id = k.transacao_id
    GROUP BY t.id
    ORDER BY t.data_transacao DESC
");
$transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gerenciar Transações';
include '../includes/admin_header.php';
?>

    <main>
        <section class="gerenciar-transacoes">
            <h2>Gerenciar Transações</h2>
            
            <?php if (isset($sucesso)): ?>
                <div class="sucesso"><?php echo $sucesso; ?></div>
            <?php endif; ?>
            
            <?php if (isset($erro)): ?>
                <div class="erro"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <?php if (empty($transacoes)): ?>
                <p>Nenhuma transação realizada.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuário</th>
                            <th>Valor</th>
                            <th>Método</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th>Keys</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transacoes as $transacao): ?>
                            <tr>
                                <td><?php echo $transacao['id']; ?></td>
                                <td><?php echo $transacao['usuario_nome']; ?></td>
                                <td><?php echo formatarPreco($transacao['valor_total']); ?></td>
                                <td><?php echo ucfirst($transacao['metodo_pagamento']); ?></td>
                                <td><?php echo ucfirst($transacao['status']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($transacao['data_transacao'])); ?></td>
                                <td>
                                    <?php if (!empty($transacao['keys_wcoin'])): ?>
                                        <button onclick="mostrarKeys(<?php echo $transacao['id']; ?>)">Ver Keys</button>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="" class="form-status">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                                        <input type="hidden" name="transacao_id" value="<?php echo $transacao['id']; ?>">
                                        
                                        <select name="novo_status" required>
                                            <option value="aguardando" <?php echo $transacao['status'] === 'aguardando' ? 'selected' : ''; ?>>Aguardando</option>
                                            <option value="pago" <?php echo $transacao['status'] === 'pago' ? 'selected' : ''; ?>>Pago</option>
                                            <option value="cancelado" <?php echo $transacao['status'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                            <option value="entregue" <?php echo $transacao['status'] === 'entregue' ? 'selected' : ''; ?>>Entregue</option>
                                        </select>
                                        
                                        <button type="submit" name="atualizar_status">Atualizar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>

<?php include '../includes/admin_footer.php'; ?>

    <script>
    function mostrarKeys(transacaoId) {
        // Aqui você implementaria a lógica para mostrar as keys em um modal
        alert('Funcionalidade de visualização de keys será implementada em breve.');
    }
    </script>
