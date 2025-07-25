<?php
session_start();

function verificarLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function gerarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verificarTokenCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('Erro de segurança: Token CSRF inválido');
    }
}

function formatarPreco($preco) {
    return 'R$ ' . number_format($preco, 2, ',', '.');
}

function getPacotesWcoin() {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM pacotes_wcoin ORDER BY preco");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function adicionarAoCarrinho($pacote_id, $quantidade) {
    if (!isset($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = array();
    }
    
    if (isset($_SESSION['carrinho'][$pacote_id])) {
        $_SESSION['carrinho'][$pacote_id] += $quantidade;
    } else {
        $_SESSION['carrinho'][$pacote_id] = $quantidade;
    }
}

function calcularTotalCarrinho() {
    global $conn;
    $total = 0;
    
    if (isset($_SESSION['carrinho']) && !empty($_SESSION['carrinho'])) {
        foreach ($_SESSION['carrinho'] as $pacote_id => $quantidade) {
            $stmt = $conn->prepare("SELECT preco FROM pacotes_wcoin WHERE id = ?");
            $stmt->execute([$pacote_id]);
            $pacote = $stmt->fetch(PDO::FETCH_ASSOC);
            $total += $pacote['preco'] * $quantidade;
        }
    }
    
    return $total;
}

function verificarCupom($codigo) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT * FROM cupons 
        WHERE codigo = ? 
        AND ativo = 1 
        AND validade >= CURDATE() 
        AND usos_atual < usos_maximos
    ");
    $stmt->execute([$codigo]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function aplicarDesconto($valor_total, $desconto) {
    return $valor_total * (1 - ($desconto / 100));
}

function registrarUsoCupom($cupom_id) {
    global $conn;
    
    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("UPDATE cupons SET usos_atual = usos_atual + 1 WHERE id = ?");
        $stmt->execute([$cupom_id]);
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        return false;
    }
}

function gerarCodigoKey() {
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, 16));
}

function verificarKeyWcoin($codigo) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM keys_wcoin WHERE codigo = ? AND usado = 0");
    $stmt->execute([$codigo]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function marcarKeyComoUsada($key_id) {
    global $conn;
    
    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("UPDATE keys_wcoin SET usado = 1, data_uso = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$key_id]);
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        return false;
    }
}
?> 