-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS creditszaidan_db;
USE creditszaidan_db;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de pacotes WCOIN
CREATE TABLE IF NOT EXISTS pacotes_wcoin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    quantidade INT NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN DEFAULT TRUE
);

-- Tabela do carrinho
CREATE TABLE IF NOT EXISTS carrinho (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    pacote_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    data_adicao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (pacote_id) REFERENCES pacotes_wcoin(id),
    UNIQUE KEY unique_user_package (usuario_id, pacote_id)
);

-- Tabela de cupons de desconto
CREATE TABLE IF NOT EXISTS cupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(12) NOT NULL UNIQUE,
    desconto DECIMAL(5,2) NOT NULL,
    validade DATE NOT NULL,
    usos_maximos INT NOT NULL,
    usos_atual INT DEFAULT 0,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de transações
CREATE TABLE IF NOT EXISTS transacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    metodo_pagamento ENUM('pix', 'cartao') NOT NULL,
    status ENUM('aguardando', 'pago', 'cancelado', 'entregue') DEFAULT 'aguardando',
    data_transacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabela de itens da transação
CREATE TABLE IF NOT EXISTS itens_transacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transacao_id INT NOT NULL,
    pacote_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (transacao_id) REFERENCES transacoes(id),
    FOREIGN KEY (pacote_id) REFERENCES pacotes_wcoin(id)
);

-- Tabela de keys WCOIN
CREATE TABLE IF NOT EXISTS keys_wcoin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transacao_id INT NOT NULL,
    codigo VARCHAR(16) NOT NULL UNIQUE,
    usado BOOLEAN DEFAULT FALSE,
    data_uso TIMESTAMP NULL,
    FOREIGN KEY (transacao_id) REFERENCES transacoes(id)
);

-- Inserir usuário administrador padrão
INSERT INTO usuarios (nome, email, senha, role) VALUES 
('Administrador', 'admin@creditszaidan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Inserir alguns pacotes WCOIN de exemplo
INSERT INTO pacotes_wcoin (nome, quantidade, preco, descricao) VALUES 
('Pacote Bronze', 1000, 10.00, '1000 WCOINs'),
('Pacote Prata', 5000, 45.00, '5000 WCOINs'),
('Pacote Ouro', 10000, 80.00, '10000 WCOINs'),
('Pacote Platina', 20000, 150.00, '20000 WCOINs');

-- Inserir alguns cupons de exemplo
INSERT INTO cupons (codigo, desconto, validade, usos_maximos) VALUES 
('WELCOME10', 10.00, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 100),
('SUMMER20', 20.00, DATE_ADD(CURDATE(), INTERVAL 15 DAY), 50); 