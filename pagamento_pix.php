<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar se o usuário está logado
verificarLogin();

// Verificar se a transação existe e pertence ao usuário
if (!isset($_GET['transacao_id'])) {
    header("Location: index.php");
    exit();
}

$transacao_id = (int)$_GET['transacao_id'];

$stmt = $conn->prepare("SELECT * FROM transacoes WHERE id = ? AND usuario_id = ?");
$stmt->execute([$transacao_id, $_SESSION['user_id']]);
$transacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transacao) {
    header("Location: index.php");
    exit();
}

// Gerar QR Code do PIX (exemplo)
$qr_code = "00020126580014BR.GOV.BCB.PIX0136123e4567-e89b-12d3-a456-4266141740005204000053039865405" . number_format($transacao['valor_total'], 2, '.', '') . "5802BR5913CreditsZaidan6008SAO PAULO62070503***6304" . substr(md5($transacao_id), 0, 4);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento PIX - Credits Zaidan</title>
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
                <a href="perfil.php">Perfil</a>
                <a href="logout.php">Sair</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="pagamento-pix">
            <h2>Pagamento via PIX</h2>
            
            <div class="info-pagamento">
                <p>Valor: <?php echo formatarPreco($transacao['valor_total']); ?></p>
                <p>ID da Transação: <?php echo $transacao_id; ?></p>
            </div>
            
            <div class="qr-code">
                <h3>Escaneie o QR Code</h3>
                <!-- Aqui você pode usar uma biblioteca para gerar o QR Code -->
                <div class="qr-code-image">
                    <!-- QR Code será gerado aqui -->
                </div>
            </div>
            
            <div class="codigo-pix">
                <h3>Ou copie o código PIX</h3>
                <div class="codigo-copia-cola">
                    <input type="text" value="<?php echo $qr_code; ?>" readonly>
                    <button onclick="copiarCodigo()">Copiar</button>
                </div>
            </div>
            
            <div class="instrucoes">
                <h3>Instruções</h3>
                <ol>
                    <li>Abra o aplicativo do seu banco</li>
                    <li>Escaneie o QR Code ou cole o código PIX</li>
                    <li>Confirme os dados e finalize o pagamento</li>
                    <li>Após o pagamento, aguarde a confirmação automática</li>
                </ol>
            </div>
            
            <div class="status-pagamento">
                <p>Status: Aguardando Pagamento</p>
                <button onclick="verificarPagamento()">Verificar Pagamento</button>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Credits Zaidan - Todos os direitos reservados</p>
    </footer>

    <script>
    function copiarCodigo() {
        const codigo = document.querySelector('.codigo-copia-cola input');
        codigo.select();
        document.execCommand('copy');
        alert('Código PIX copiado!');
    }
    
    function verificarPagamento() {
        // Aqui você implementaria a verificação do status do pagamento
        // usando uma API de pagamento ou consulta ao banco de dados
        alert('Verificando status do pagamento...');
    }
    </script>
</body>
</html> 