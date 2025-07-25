<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar se é admin
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$mensagem = '';
$erro = '';

// Processar upload de imagem
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pacote_id']) && isset($_FILES['imagem'])) {
        $pacote_id = (int)$_POST['pacote_id'];
        $imagem = $_FILES['imagem'];
        
        // Validar tipo de arquivo
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($imagem['type'], $tipos_permitidos)) {
            $erro = "Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.";
        } else {
            // Validar tamanho (máximo 5MB)
            if ($imagem['size'] > 5 * 1024 * 1024) {
                $erro = "A imagem deve ter no máximo 5MB.";
            } else {
                // Criar diretório se não existir
                $diretorio = "../assets/images/produtos";
                if (!file_exists($diretorio)) {
                    mkdir($diretorio, 0777, true);
                }
                
                // Nome do arquivo
                $nome_arquivo = $pacote_id . '.jpg';
                $caminho_completo = $diretorio . '/' . $nome_arquivo;
                
                // Converter para JPG se necessário
                if ($imagem['type'] !== 'image/jpeg') {
                    $imagem_original = imagecreatefromstring(file_get_contents($imagem['tmp_name']));
                    imagejpeg($imagem_original, $caminho_completo, 90);
                    imagedestroy($imagem_original);
                } else {
                    move_uploaded_file($imagem['tmp_name'], $caminho_completo);
                }
                
                $mensagem = "Imagem atualizada com sucesso!";
            }
        }
    }
}

// Buscar pacotes
$stmt = $conn->query("SELECT * FROM pacotes_wcoin ORDER BY nome");
$pacotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Imagens - Credits Zaidan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
        
        .imagens-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .imagem-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        
        .imagem-preview {
            width: 200px;
            height: 200px;
            border-radius: 10px;
            margin: 0 auto 20px;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ccc;
        }
        
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .btn-upload {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-upload:hover {
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
    </style>
</head>
<body class="dark-theme">
    <header>
        <nav>
            <div class="logo">
                <h1>Credits Zaidan</h1>
            </div>
            <div class="nav-links">
                <a href="../index.php">Início</a>
                <a href="index.php">Painel Admin</a>
                <a href="../logout.php">Sair</a>
            </div>
        </nav>
    </header>

    <main class="admin-container">
        <div class="admin-header">
            <h1>Gerenciar Imagens dos Produtos</h1>
            <p>Faça upload das imagens para os pacotes de WCOIN</p>
        </div>
        
        <?php if ($mensagem): ?>
            <div class="mensagem sucesso"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="mensagem erro"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <div class="imagens-grid">
            <?php foreach ($pacotes as $pacote): ?>
                <div class="imagem-card">
                    <?php
                    $imagem_path = "../assets/images/produtos/{$pacote['id']}.jpg";
                    if (file_exists($imagem_path)):
                    ?>
                        <img src="<?php echo $imagem_path; ?>" alt="<?php echo htmlspecialchars($pacote['nome']); ?>" class="imagem-preview">
                    <?php else: ?>
                        <div class="imagem-preview imagem-padrao">
                            WCOIN
                        </div>
                    <?php endif; ?>
                    
                    <h3><?php echo htmlspecialchars($pacote['nome']); ?></h3>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="pacote_id" value="<?php echo $pacote['id']; ?>">
                        
                        <div class="form-group">
                            <label for="imagem_<?php echo $pacote['id']; ?>">Selecione uma imagem</label>
                            <input type="file" id="imagem_<?php echo $pacote['id']; ?>" name="imagem" accept="image/*" required>
                        </div>
                        
                        <button type="submit" class="btn-upload">Atualizar Imagem</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Credits Zaidan - Todos os direitos reservados</p>
    </footer>
</body>
</html> 