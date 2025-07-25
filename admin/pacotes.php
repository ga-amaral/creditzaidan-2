<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Ativar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log de erros
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Verificar se é admin
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$mensagem = '';
$erro = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['acao'])) {
            switch ($_POST['acao']) {
                case 'adicionar':
                    $nome = sanitizeInput($_POST['nome']);
                    $quantidade = (int)$_POST['quantidade'];
                    $preco = (float)$_POST['preco'];
                    $descricao = sanitizeInput($_POST['descricao']);
                    $mais_popular = isset($_POST['mais_popular']) ? 1 : 0;
                    
                    error_log("Iniciando adição de pacote: " . json_encode($_POST));
                    
                    $stmt = $conn->prepare("INSERT INTO pacotes_wcoin (nome, quantidade, preco, descricao, mais_popular) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([$nome, $quantidade, $preco, $descricao, $mais_popular])) {
                        $pacote_id = $conn->lastInsertId();
                        error_log("Pacote adicionado com ID: " . $pacote_id);
                        
                        // Processar imagem se foi enviada
                        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                            $imagem = $_FILES['imagem'];
                            error_log("Processando imagem: " . json_encode($imagem));
                            
                            // Validar tipo de arquivo
                            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
                            if (in_array($imagem['type'], $tipos_permitidos)) {
                                // Validar tamanho (máximo 5MB)
                                if ($imagem['size'] <= 5 * 1024 * 1024) {
                                    // Criar diretório se não existir
                                    $diretorio = "../assets/images/produtos";
                                    if (!file_exists($diretorio)) {
                                        if (!mkdir($diretorio, 0777, true)) {
                                            error_log("Erro ao criar diretório: " . $diretorio);
                                            throw new Exception("Erro ao criar diretório para imagens.");
                                        }
                                        chmod($diretorio, 0777);
                                    }
                                    
                                    // Verificar se o diretório é gravável
                                    if (!is_writable($diretorio)) {
                                        error_log("Diretório não é gravável: " . $diretorio);
                                        throw new Exception("O diretório de imagens não tem permissão de escrita.");
                                    }
                                    
                                    // Nome do arquivo
                                    $nome_arquivo = $pacote_id . '.jpg';
                                    $caminho_completo = $diretorio . '/' . $nome_arquivo;
                                    
                                    error_log("Tentando salvar imagem em: " . $caminho_completo);
                                    
                                    // Converter para JPG se necessário
                                    if ($imagem['type'] !== 'image/jpeg') {
                                        $imagem_original = imagecreatefromstring(file_get_contents($imagem['tmp_name']));
                                        if ($imagem_original) {
                                            if (imagejpeg($imagem_original, $caminho_completo, 90)) {
                                                imagedestroy($imagem_original);
                                                $mensagem = "Pacote adicionado com sucesso e imagem convertida!";
                                                error_log("Imagem convertida e salva com sucesso");
                                            } else {
                                                error_log("Erro ao salvar imagem convertida");
                                                throw new Exception("Erro ao salvar a imagem convertida.");
                                            }
                                        } else {
                                            error_log("Erro ao processar a imagem original");
                                            throw new Exception("Erro ao processar a imagem.");
                                        }
                                    } else {
                                        if (move_uploaded_file($imagem['tmp_name'], $caminho_completo)) {
                                            $mensagem = "Pacote adicionado com sucesso e imagem salva!";
                                            error_log("Imagem JPG salva com sucesso");
                                        } else {
                                            $erro_upload = error_get_last();
                                            error_log("Erro ao mover arquivo: " . ($erro_upload ? $erro_upload['message'] : 'Erro desconhecido'));
                                            throw new Exception("Erro ao salvar a imagem. Verifique as permissões do diretório.");
                                        }
                                    }
                                } else {
                                    error_log("Imagem muito grande: " . $imagem['size']);
                                    throw new Exception("A imagem deve ter no máximo 5MB.");
                                }
                            } else {
                                error_log("Tipo de arquivo não permitido: " . $imagem['type']);
                                throw new Exception("Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.");
                            }
                        } else {
                            $mensagem = "Pacote adicionado com sucesso!";
                        }
                    } else {
                        error_log("Erro ao adicionar pacote no banco de dados");
                        throw new Exception("Erro ao adicionar pacote.");
                    }
                    break;
                    
                case 'editar':
                    $id = (int)$_POST['id'];
                    $nome = sanitizeInput($_POST['nome']);
                    $quantidade = (int)$_POST['quantidade'];
                    $preco = (float)$_POST['preco'];
                    $descricao = sanitizeInput($_POST['descricao']);
                    $ativo = isset($_POST['ativo']) ? 1 : 0;
                    $mais_popular = isset($_POST['mais_popular']) ? 1 : 0;
                    
                    try {
                        $stmt = $conn->prepare("UPDATE pacotes_wcoin SET nome = ?, quantidade = ?, preco = ?, descricao = ?, ativo = ?, mais_popular = ? WHERE id = ?");
                        if ($stmt->execute([$nome, $quantidade, $preco, $descricao, $ativo, $mais_popular, $id])) {
                            // Processar imagem se foi enviada
                            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                                $imagem = $_FILES['imagem'];
                                
                                // Log para debug
                                error_log("Tentando atualizar imagem para o pacote ID: " . $id);
                                error_log("Tipo do arquivo: " . $imagem['type']);
                                error_log("Tamanho do arquivo: " . $imagem['size']);
                                
                                // Validar tipo de arquivo
                                $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
                                if (in_array($imagem['type'], $tipos_permitidos)) {
                                    // Validar tamanho (máximo 5MB)
                                    if ($imagem['size'] <= 5 * 1024 * 1024) {
                                        // Criar diretório se não existir
                                        $diretorio = "../assets/images/produtos";
                                        if (!file_exists($diretorio)) {
                                            if (!mkdir($diretorio, 0777, true)) {
                                                error_log("Erro ao criar diretório: " . $diretorio);
                                                $erro = "Erro ao criar diretório para imagens.";
                                                break;
                                            }
                                            chmod($diretorio, 0777);
                                        }
                                        
                                        // Verificar se o diretório é gravável
                                        if (!is_writable($diretorio)) {
                                            error_log("Diretório não é gravável: " . $diretorio);
                                            $erro = "O diretório de imagens não tem permissão de escrita.";
                                            break;
                                        }
                                        
                                        // Nome do arquivo
                                        $nome_arquivo = $id . '.jpg';
                                        $caminho_completo = $diretorio . '/' . $nome_arquivo;
                                        
                                        error_log("Caminho completo do arquivo: " . $caminho_completo);
                                        
                                        // Verificar se o arquivo temporário existe
                                        if (!file_exists($imagem['tmp_name'])) {
                                            error_log("Arquivo temporário não existe: " . $imagem['tmp_name']);
                                            $erro = "Erro ao acessar o arquivo temporário.";
                                            break;
                                        }
                                        
                                        // Verificar se o arquivo temporário é legível
                                        if (!is_readable($imagem['tmp_name'])) {
                                            error_log("Arquivo temporário não é legível: " . $imagem['tmp_name']);
                                            $erro = "Erro ao ler o arquivo temporário.";
                                            break;
                                        }
                                        
                                        // Converter para JPG se necessário
                                        if ($imagem['type'] !== 'image/jpeg') {
                                            $imagem_original = imagecreatefromstring(file_get_contents($imagem['tmp_name']));
                                            if ($imagem_original) {
                                                if (imagejpeg($imagem_original, $caminho_completo, 90)) {
                                                    imagedestroy($imagem_original);
                                                    $mensagem = "Pacote atualizado com sucesso e imagem convertida!";
                                                    error_log("Imagem convertida e salva com sucesso");
                                                } else {
                                                    error_log("Erro ao salvar imagem convertida");
                                                    $erro = "Erro ao salvar a imagem convertida.";
                                                }
                                            } else {
                                                error_log("Erro ao processar a imagem original");
                                                $erro = "Erro ao processar a imagem.";
                                            }
                                        } else {
                                            if (move_uploaded_file($imagem['tmp_name'], $caminho_completo)) {
                                                $mensagem = "Pacote atualizado com sucesso e imagem salva!";
                                                error_log("Imagem JPG salva com sucesso");
                                            } else {
                                                error_log("Erro ao mover arquivo: " . error_get_last()['message']);
                                                $erro = "Erro ao salvar a imagem. Verifique as permissões do diretório.";
                                            }
                                        }
                                    } else {
                                        error_log("Imagem muito grande: " . $imagem['size']);
                                        $erro = "A imagem deve ter no máximo 5MB.";
                                    }
                                } else {
                                    error_log("Tipo de arquivo não permitido: " . $imagem['type']);
                                    $erro = "Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.";
                                }
                            } else {
                                $mensagem = "Pacote atualizado com sucesso!";
                            }
                        } else {
                            $erro = "Erro ao atualizar pacote.";
                        }
                    } catch (PDOException $e) {
                        error_log("Erro no banco de dados: " . $e->getMessage());
                        $erro = "Erro ao atualizar pacote: " . $e->getMessage();
                    }
                    break;
                    
                case 'excluir':
                    $id = (int)$_POST['id'];
                    try {
                        // Primeiro, excluir registros relacionados no carrinho
                        $stmt = $conn->prepare("DELETE FROM carrinho WHERE pacote_id = ?");
                        if ($stmt->execute([$id])) {
                            error_log("Registros do carrinho excluídos para o pacote ID: " . $id);
                            
                            // Agora excluir o pacote
                            $stmt = $conn->prepare("DELETE FROM pacotes_wcoin WHERE id = ?");
                            if ($stmt->execute([$id])) {
                                error_log("Pacote excluído com sucesso. ID: " . $id);
                                
                                // Remover imagem se existir
                                $imagem_path = "../assets/images/produtos/{$id}.jpg";
                                if (file_exists($imagem_path)) {
                                    if (unlink($imagem_path)) {
                                        error_log("Imagem do pacote excluída: " . $imagem_path);
                                    } else {
                                        error_log("Erro ao excluir imagem do pacote: " . $imagem_path);
                                    }
                                }
                                
                                $mensagem = "Pacote excluído com sucesso!";
                            } else {
                                error_log("Erro ao excluir pacote do banco de dados. ID: " . $id);
                                $erro = "Erro ao excluir pacote.";
                            }
                        } else {
                            error_log("Erro ao excluir registros do carrinho. Pacote ID: " . $id);
                            $erro = "Erro ao limpar registros do carrinho.";
                        }
                    } catch (PDOException $e) {
                        error_log("Erro ao excluir pacote: " . $e->getMessage());
                        $erro = "Erro ao excluir pacote: " . $e->getMessage();
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        error_log("Erro no processamento: " . $e->getMessage());
        $erro = $e->getMessage();
    } catch (PDOException $e) {
        error_log("Erro no banco de dados: " . $e->getMessage());
        $erro = "Erro no banco de dados: " . $e->getMessage();
    }
}

// Buscar pacotes
$stmt = $conn->query("SELECT * FROM pacotes_wcoin ORDER BY preco");
$pacotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Atualizar todos os pacotes para não serem mais populares por padrão
$stmt = $conn->prepare("UPDATE pacotes_wcoin SET mais_popular = 0 WHERE mais_popular IS NULL");
$stmt->execute();

$page_title = 'Gerenciar Pacotes';
include '../includes/admin_header.php';
?>
<style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
        }
        
        .admin-header h1 {
            color: #4CAF50;
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .pacotes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .pacote-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            position: relative;
        }
        
        .pacote-imagem {
            width: 100%;
            height: 200px;
            border-radius: 10px;
            margin-bottom: 20px;
            object-fit: cover;
            background: #2d2d2d;
        }
        
        .imagem-padrao {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: bold;
        }
        
        .pacote-info {
            margin-bottom: 20px;
        }
        
        .pacote-nome {
            color: #4CAF50;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .pacote-detalhes {
            color: #ccc;
            margin-bottom: 5px;
        }
        
        .pacote-preco {
            color: #FFC107;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .pacote-acoes {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
        }
        
        .btn-editar {
            background: #2196F3;
            color: white;
        }
        
        .btn-excluir {
            background: #f44336;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
        }
        
        .modal-content {
            position: relative;
            background: #1a1a1a;
            margin: 50px auto;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            color: #ccc;
            cursor: pointer;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ccc;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .form-group input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .btn-submit {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-weight: bold;
        }
        
        .btn-submit:hover {
            background: #45a049;
            transform: translateY(-2px);
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
        
        .btn-adicionar {
            display: inline-block;
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .btn-adicionar:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        
        .nav-links a.ativo {
            background: #4CAF50;
            color: white;
            border-radius: 8px;
            padding: 8px 16px;
        }
        
        .nav-links a:hover {
            background: rgba(76, 175, 80, 0.1);
            border-radius: 8px;
            padding: 8px 16px;
        }
    </style>

    <main class="admin-container">
        <div class="admin-header">
            <h1>Gerenciar Pacotes de WCOIN</h1>
            <p>Adicione, edite ou remova pacotes de WCOIN</p>
        </div>
        
        <?php if ($mensagem): ?>
            <div class="mensagem sucesso"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="mensagem erro"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <a href="#" class="btn-adicionar" onclick="abrirModal('adicionar')">Adicionar Novo Pacote</a>
        
        <div class="pacotes-grid">
            <?php foreach ($pacotes as $pacote): ?>
                <div class="pacote-card">
                    <?php if ($pacote['mais_popular']): ?>
                        <div class="destaque">Mais Popular</div>
                    <?php endif; ?>
                    
                    <?php
                    $imagem_path = "../assets/images/produtos/{$pacote['id']}.jpg";
                    $pacote_data = $pacote;
                    if (file_exists($imagem_path)) {
                        $pacote_data['imagem_url'] = $imagem_path;
                        echo '<img src="' . $imagem_path . '" alt="' . htmlspecialchars($pacote['nome']) . '" class="pacote-imagem">';
                    } else {
                        $pacote_data['imagem_url'] = '';
                        echo '<div class="pacote-imagem imagem-padrao">WCOIN</div>';
                    }
                    ?>
                    
                    <div class="pacote-info">
                        <h3 class="pacote-nome"><?php echo htmlspecialchars($pacote['nome']); ?></h3>
                        <p class="pacote-detalhes">Quantidade: <?php echo number_format($pacote['quantidade']); ?> WCOIN</p>
                        <p class="pacote-detalhes">Descrição: <?php echo htmlspecialchars($pacote['descricao']); ?></p>
                        <p class="pacote-preco"><?php echo formatarPreco($pacote['preco']); ?></p>
                        <p class="pacote-detalhes">Status: <?php echo $pacote['ativo'] ? 'Ativo' : 'Inativo'; ?></p>
                    </div>
                    
                    <div class="pacote-acoes">
                        <button class="btn btn-editar" onclick='abrirModal("editar", <?php echo htmlspecialchars(json_encode($pacote_data)); ?>)'>Editar</button>
                        <button class="btn btn-excluir" onclick="confirmarExclusao(<?php echo $pacote['id']; ?>)">Excluir</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Modal Adicionar/Editar -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModal()">&times;</span>
            <h2 id="modal-titulo">Adicionar Pacote</h2>

            <form method="POST" enctype="multipart/form-data" id="form-pacote" class="admin-form">
                <input type="hidden" name="acao" id="acao" value="adicionar">
                <input type="hidden" name="id" id="pacote-id">
                
                <div class="form-group">
                    <label for="nome">Nome do Pacote</label>
                    <input type="text" id="nome" name="nome" placeholder="Pacote" required>
                </div>
                
                <div class="form-group">
                    <label for="quantidade">Quantidade de WCOIN</label>
                    <input type="number" id="quantidade" name="quantidade" placeholder="Quantidade" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="preco">Preço (R$)</label>
                    <input type="number" id="preco" name="preco" placeholder="0.00" min="0" step="0.01" required>
                </div>
                
                <div class="form-group" style="grid-column: span 2;">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" placeholder="Descrição" required></textarea>
                </div>
                
                <div class="form-group" style="grid-column: span 2;">
                    <label for="imagem">Imagem do Pacote</label>
                    <img id="preview-imagem" class="imagem-preview" alt="Prévia da imagem" />
                    <input type="file" id="imagem" name="imagem" accept="image/*">
                </div>
                
                <div class="form-group" id="grupo-ativo" style="display: none;">
                    <label>
                        <input type="checkbox" id="ativo" name="ativo" value="1">
                        Pacote Ativo
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="mais_popular" name="mais_popular" value="1">
                        Marcar como Mais Popular
                    </label>
                </div>
                
                <button type="submit" class="btn-submit">Salvar</button>
            </form>
        </div>
    </div>

    <!-- Modal de Confirmação -->
    <div id="modal-confirmacao" class="modal">
        <div class="modal-content">
            <h2>Confirmar Exclusão</h2>
            <p>Tem certeza que deseja excluir este pacote?</p>
            
            <form method="POST">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="id" id="id-excluir">
                
                <div class="pacote-acoes">
                    <button type="submit" class="btn btn-excluir">Confirmar Exclusão</button>
                    <button type="button" class="btn btn-editar" onclick="fecharModalConfirmacao()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

<?php include '../includes/admin_footer.php'; ?>

    <script>
        const previewImagem = document.getElementById('preview-imagem');
        const inputImagem = document.getElementById('imagem');

        inputImagem.addEventListener('change', function(e) {
            const [file] = e.target.files;
            if (file) {
                previewImagem.src = URL.createObjectURL(file);
                previewImagem.style.display = 'block';
            } else {
                previewImagem.style.display = 'none';
                previewImagem.src = '';
            }
        });

        function abrirModal(tipo, dados = null) {
            const modal = document.getElementById('modal');
            const titulo = document.getElementById('modal-titulo');
            const form = document.getElementById('form-pacote');
            const grupoAtivo = document.getElementById('grupo-ativo');
            
            if (tipo === 'adicionar') {
                titulo.textContent = 'Adicionar Pacote';
                form.reset();
                previewImagem.style.display = 'none';
                previewImagem.src = '';
                document.getElementById('acao').value = 'adicionar';
                grupoAtivo.style.display = 'none';
            } else {
                titulo.textContent = 'Editar Pacote';
                document.getElementById('acao').value = 'editar';
                document.getElementById('pacote-id').value = dados.id;
                document.getElementById('nome').value = dados.nome;
                document.getElementById('quantidade').value = dados.quantidade;
                document.getElementById('preco').value = dados.preco;
                document.getElementById('descricao').value = dados.descricao;
                document.getElementById('ativo').checked = dados.ativo == 1;
                document.getElementById('mais_popular').checked = dados.mais_popular == 1;
                grupoAtivo.style.display = 'block';
                if (dados.imagem_url) {
                    previewImagem.src = dados.imagem_url + '?' + Date.now();
                    previewImagem.style.display = 'block';
                } else {
                    previewImagem.style.display = 'none';
                    previewImagem.src = '';
                }
            }
            
            modal.style.display = 'block';
        }
        
        function fecharModal() {
            document.getElementById('modal').style.display = 'none';
        }
        
        function confirmarExclusao(id) {
            document.getElementById('modal-confirmacao').style.display = 'block';
            document.getElementById('id-excluir').value = id;
        }
        
        function fecharModalConfirmacao() {
            document.getElementById('modal-confirmacao').style.display = 'none';
        }
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('modal');
            const modalConfirmacao = document.getElementById('modal-confirmacao');
            if (event.target === modal) {
                fecharModal();
            }
            if (event.target === modalConfirmacao) {
                fecharModalConfirmacao();
            }
        }
    </script>
