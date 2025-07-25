<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Buscar pacotes ativos
$pacotes = getPacotesWcoin();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credits Zaidan - Pacotes de WCOIN</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section {
            text-align: center;
            padding: 60px 20px;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            margin-bottom: 40px;
        }
        
        .hero-section h1 {
            color: #4CAF50;
            font-size: 48px;
            margin-bottom: 20px;
            animation: fadeInDown 0.8s ease-out;
        }
        
        .hero-section p {
            color: #ccc;
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto;
            animation: fadeInUp 0.8s ease-out;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .pacotes-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .pacotes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .pacote-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .pacote-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .pacote-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(76, 175, 80, 0.1), transparent);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .pacote-card:hover::before {
            opacity: 1;
        }
        
        .pacote-imagem {
            width: 200px;
            height: 200px;
            border-radius: 15px;
            margin-bottom: 20px;
            object-fit: cover;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .pacote-card:hover .pacote-imagem {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
        
        .pacote-conteudo {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        
        .pacote-nome {
            color: #4CAF50;
            font-size: 24px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        
        .pacote-quantidade {
            font-size: 36px;
            color: #FFC107;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .pacote-descricao {
            color: #ccc;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .pacote-preco {
            font-size: 32px;
            color: #4CAF50;
            margin-bottom: 25px;
            font-weight: bold;
        }
        
        .form-comprar {
            width: 100%;
            margin-top: auto;
        }
        
        .btn-comprar {
            display: inline-block;
            padding: 12px 30px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: center;
            margin-top: auto;
            position: relative;
            z-index: 1;
        }
        
        .btn-comprar:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
        }
        
        .destaque {
            position: absolute;
            top: 20px;
            right: -35px;
            background: #FFC107;
            color: #000;
            padding: 5px 40px;
            transform: rotate(45deg);
            font-weight: bold;
            font-size: 14px;
        }
        
        .filtros {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .filtro-btn {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 8px;
            color: #ccc;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filtro-btn:hover, .filtro-btn.ativo {
            background: #4CAF50;
            color: white;
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
        
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 36px;
            }
            
            .pacotes-grid {
                grid-template-columns: 1fr;
            }
            
            .filtros {
                flex-wrap: wrap;
            }
        }
        
        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .popup-content {
            background: #1a1a1a;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: popupFadeIn 0.3s ease-out;
        }
        
        @keyframes popupFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .popup-content h3 {
            color: #4CAF50;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .popup-content p {
            color: #ccc;
            margin-bottom: 25px;
            font-size: 16px;
        }
        
        .popup-botoes {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn-popup {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
        }
        
        .btn-carrinho {
            background: #4CAF50;
            color: white;
        }
        
        .btn-carrinho:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        
        .btn-continuar {
            background: #2196F3;
            color: white;
        }
        
        .btn-continuar:hover {
            background: #1976D2;
            transform: translateY(-2px);
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
                <a href="index.php">Início</a>
                <a href="carrinho.php">Carrinho</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="perfil.php">Meu Perfil</a>
                    <?php if (isAdmin()): ?>
                        <a href="admin/index.php">Painel Admin</a>
                    <?php endif; ?>
                    <a href="logout.php">Sair</a>
                <?php else: ?>
                    <a href="login.php">Entrar</a>
                    <a href="registro.php">Registrar</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <section class="hero-section">
        <h1>Pacotes de WCOIN</h1>
        <p>Escolha o pacote ideal para suas necessidades e comece a usar os WCOINs agora mesmo!</p>
    </section>

    <main class="pacotes-container">
        <div class="filtros">
            <button class="filtro-btn ativo" data-filtro="todos">Todos</button>
            <button class="filtro-btn" data-filtro="menor">Menor Preço</button>
            <button class="filtro-btn" data-filtro="maior">Maior Preço</button>
            <button class="filtro-btn" data-filtro="popular">Mais Popular</button>
        </div>

        <div class="pacotes-grid">
            <?php foreach ($pacotes as $pacote): ?>
                <div class="pacote-card" data-preco="<?php echo $pacote['preco']; ?>">
                    <?php if ($pacote['mais_popular']): ?>
                        <div class="destaque">Mais Popular</div>
                    <?php endif; ?>
                    
                    <?php
                    $imagem_path = "assets/images/produtos/{$pacote['id']}.jpg";
                    if (file_exists($imagem_path)):
                    ?>
                        <img src="<?php echo $imagem_path; ?>" alt="<?php echo htmlspecialchars($pacote['nome']); ?>" class="pacote-imagem">
                    <?php else: ?>
                        <div class="pacote-imagem imagem-padrao">
                            WCOIN
                        </div>
                    <?php endif; ?>
                    
                    <div class="pacote-conteudo">
                        <h2 class="pacote-nome"><?php echo htmlspecialchars($pacote['nome']); ?></h2>
                        <div class="pacote-quantidade"><?php echo number_format($pacote['quantidade']); ?> WCOIN</div>
                        <p class="pacote-descricao"><?php echo htmlspecialchars($pacote['descricao']); ?></p>
                        <div class="pacote-preco"><?php echo formatarPreco($pacote['preco']); ?></div>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form method="POST" action="carrinho.php" class="form-comprar">
                                <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
                                <input type="hidden" name="acao" value="adicionar">
                                <input type="hidden" name="pacote_id" value="<?php echo $pacote['id']; ?>">
                                <input type="hidden" name="quantidade" value="1">
                                <button type="submit" class="btn-comprar">Adicionar ao Carrinho</button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="btn-comprar">Fazer Login para Comprar</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Credits Zaidan - Todos os direitos reservados</p>
    </footer>

    <!-- Popup de Confirmação -->
    <div id="popup-confirmacao" class="popup">
        <div class="popup-content">
            <h3>Produto Adicionado!</h3>
            <p>O que você deseja fazer agora?</p>
            <div class="popup-botoes">
                <button id="btn-ir-carrinho" class="btn-popup btn-carrinho">Ir para o Carrinho</button>
                <button id="btn-continuar" class="btn-popup btn-continuar">Continuar Comprando</button>
            </div>
        </div>
    </div>

    <script>
        // Filtros de pacotes
        document.querySelectorAll('.filtro-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove classe ativo de todos os botões
                document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('ativo'));
                // Adiciona classe ativo ao botão clicado
                this.classList.add('ativo');
                
                const filtro = this.dataset.filtro;
                const cards = document.querySelectorAll('.pacote-card');
                const cardsArray = Array.from(cards);
                
                switch(filtro) {
                    case 'menor':
                        cardsArray.sort((a, b) => parseFloat(a.dataset.preco) - parseFloat(b.dataset.preco));
                        break;
                    case 'maior':
                        cardsArray.sort((a, b) => parseFloat(b.dataset.preco) - parseFloat(a.dataset.preco));
                        break;
                    case 'popular':
                        cardsArray.sort((a, b) => {
                            const aPopular = a.querySelector('.destaque') ? 1 : 0;
                            const bPopular = b.querySelector('.destaque') ? 1 : 0;
                            return bPopular - aPopular;
                        });
                        break;
                    default:
                        // Ordem original
                        cardsArray.sort((a, b) => {
                            const aIndex = Array.from(cards).indexOf(a);
                            const bIndex = Array.from(cards).indexOf(b);
                            return aIndex - bIndex;
                        });
                }
                
                // Reorganiza os cards
                const container = document.querySelector('.pacotes-grid');
                cardsArray.forEach(card => container.appendChild(card));
            });
        });

        // Adicionar ao carrinho com popup personalizado
        document.querySelectorAll('form[action="carrinho.php"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('acao', 'adicionar');
                
                // Adicionar ao carrinho
                fetch('carrinho.php', {
                    method: 'POST',
                    body: formData
                }).then(response => {
                    if (response.ok) {
                        // Mostrar popup
                        const popup = document.getElementById('popup-confirmacao');
                        popup.style.display = 'flex';
                        
                        // Configurar botões
                        document.getElementById('btn-ir-carrinho').onclick = function() {
                            window.location.href = 'carrinho.php';
                        };
                        
                        document.getElementById('btn-continuar').onclick = function() {
                            popup.style.display = 'none';
                        };
                    }
                });
            });
        });
        
        // Fechar popup ao clicar fora
        document.getElementById('popup-confirmacao').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    </script>
</body>
</html> 