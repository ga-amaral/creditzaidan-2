<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Processar criação de cupom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_cupom'])) {
    verificarTokenCSRF($_POST['csrf_token']);
    
    $codigo = strtoupper(sanitizeInput($_POST['codigo']));
    $desconto = (float)$_POST['desconto'];
    $validade = sanitizeInput($_POST['validade']);
    $usos_maximos = (int)$_POST['usos_maximos'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    try {
        $stmt = $conn->prepare("INSERT INTO cupons (codigo, desconto, validade, usos_maximos, ativo) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$codigo, $desconto, $validade, $usos_maximos, $ativo]);
        $sucesso = "Cupom criado com sucesso!";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Código de erro para duplicata
            $erro = "Este código de cupom já existe.";
        } else {
            $erro = "Erro ao criar cupom: " . $e->getMessage();
        }
    }
}

// Processar exclusão de cupom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_cupom'])) {
    verificarTokenCSRF($_POST['csrf_token']);
    
    $cupom_id = (int)$_POST['cupom_id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM cupons WHERE id = ?");
        $stmt->execute([$cupom_id]);
        $sucesso = "Cupom excluído com sucesso!";
    } catch (Exception $e) {
        $erro = "Erro ao excluir cupom: " . $e->getMessage();
    }
}

// Processar ativação/desativação de cupom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    verificarTokenCSRF($_POST['csrf_token']);
    
    $cupom_id = (int)$_POST['cupom_id'];
    $novo_status = (int)$_POST['novo_status'];
    
    try {
        $stmt = $conn->prepare("UPDATE cupons SET ativo = ? WHERE id = ?");
        $stmt->execute([$novo_status, $cupom_id]);
        $sucesso = "Status do cupom atualizado com sucesso!";
    } catch (Exception $e) {
        $erro = "Erro ao atualizar status do cupom: " . $e->getMessage();
    }
}

// Buscar cupons com filtro opcional
$search = '';
if (isset($_GET['q'])) {
    $search = sanitizeInput($_GET['q']);
}

if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM cupons WHERE codigo LIKE ? ORDER BY data_criacao DESC");
    $stmt->execute(['%' . $search . '%']);
} else {
    $stmt = $conn->query("SELECT * FROM cupons ORDER BY data_criacao DESC");
}
$cupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gerenciar Cupons';
include '../includes/admin_header.php';
?>
<style>
        .cupom-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }
        .cupons-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .cupons-table th, .cupons-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .cupons-table th {
            background: rgba(255, 255, 255, 0.1);
        }
        .status-ativo {
            color: #4CAF50;
        }
        .status-inativo {
            color: #f44336;
        }
        .btn-group {
            display: flex;
            gap: 8px;
        }
        .btn-toggle {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-toggle.ativar {
            background: #4CAF50;
            color: white;
        }
        .btn-toggle.desativar {
            background: #f44336;
            color: white;
        }

        .cupons-busca {
            text-align: center;
            margin-top: 20px;
        }
        .cupons-busca input[type="text"] {
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        .cupons-busca button {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            background: #2196F3;
            color: #fff;
            cursor: pointer;
        }
        .cupons-busca button:hover {
            opacity: 0.9;
        }

        .uso-progress {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            height: 10px;
            margin-bottom: 4px;
            overflow: hidden;
        }
        .uso-bar {
            background: #4CAF50;
            height: 100%;
        }
    </style>
</head>

    <main>
        <section class="admin-content">
            <h2>Gerenciar Cupons de Desconto</h2>
            
            <?php if (isset($sucesso)): ?>
                <div class="sucesso"><?php echo $sucesso; ?></div>
            <?php endif; ?>
            
            <?php if (isset($erro)): ?>
                <div class="erro"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <div class="cupom-form">
                <h3>Criar Novo Cupom</h3>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                    
                    <div class="form-group">
                        <label for="codigo">Código do Cupom:</label>
                        <input type="text" id="codigo" name="codigo" required 
                               pattern="[A-Za-z0-9]{3,12}" 
                               title="O código deve conter entre 3 e 12 caracteres alfanuméricos"
                               placeholder="Ex: SUMMER2024">
                    </div>
                    
                    <div class="form-group">
                        <label for="desconto">Desconto (%):</label>
                        <input type="number" id="desconto" name="desconto" required 
                               min="1" max="90" step="0.01"
                               placeholder="Ex: 10">
                    </div>
                    
                    <div class="form-group">
                        <label for="validade">Data de Validade:</label>
                        <input type="date" id="validade" name="validade" required 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="usos_maximos">Usos Máximos:</label>
                        <input type="number" id="usos_maximos" name="usos_maximos" required 
                               min="1" value="100">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="ativo" checked>
                            Cupom Ativo
                        </label>
                    </div>
                    
                    <button type="submit" name="criar_cupom" class="btn-primary">Criar Cupom</button>
                </form>
            </div>
            
            <div class="cupons-lista">
                <h3>Cupons Cadastrados</h3>
                <div class="cupons-busca">
                    <form method="GET" action="">
                        <input type="text" name="q" placeholder="Buscar código" value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">Buscar</button>
                    </form>
                </div>
                
                <?php if (empty($cupons)): ?>
                    <p>Nenhum cupom cadastrado.</p>
                <?php else: ?>
                    <table class="cupons-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Desconto</th>
                                <th>Validade</th>
                                <th>Usos</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cupons as $cupom): ?>
                                <tr>
                                    <td><?php echo $cupom['codigo']; ?></td>
                                    <td><?php echo $cupom['desconto']; ?>%</td>
                                    <td><?php echo date('d/m/Y', strtotime($cupom['validade'])); ?></td>
                                    <td>
                                        <?php
                                            $percent = 0;
                                            if ($cupom['usos_maximos'] > 0) {
                                                $percent = ($cupom['usos_atual'] / $cupom['usos_maximos']) * 100;
                                                if ($percent > 100) { $percent = 100; }
                                            }
                                        ?>
                                        <div class="uso-progress">
                                            <div class="uso-bar" style="width: <?php echo $percent; ?>%"></div>
                                        </div>
                                        <?php echo $cupom['usos_atual']; ?>/<?php echo $cupom['usos_maximos']; ?>
                                    </td>
                                    <td>
                                        <span class="<?php echo $cupom['ativo'] ? 'status-ativo' : 'status-inativo'; ?>">
                                            <?php echo $cupom['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($cupom['ativo']): ?>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                                                    <input type="hidden" name="cupom_id" value="<?php echo $cupom['id']; ?>">
                                                    <input type="hidden" name="novo_status" value="0">
                                                    <button type="submit" name="toggle_status" class="btn-toggle desativar">
                                                        Desativar
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                                                    <input type="hidden" name="cupom_id" value="<?php echo $cupom['id']; ?>">
                                                    <input type="hidden" name="novo_status" value="1">
                                                    <button type="submit" name="toggle_status" class="btn-toggle ativar">
                                                        Ativar
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                                                <input type="hidden" name="cupom_id" value="<?php echo $cupom['id']; ?>">
                                                <button type="submit" name="excluir_cupom" class="btn-danger"
                                                        onclick="return confirm('Tem certeza que deseja excluir este cupom?')">
                                                    Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
    </main>

<?php include '../includes/admin_footer.php'; ?>
