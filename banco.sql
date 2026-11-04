-- ==========================================================================
-- HP TERRAPLENAGEM - Banco de dados (MySQL / MariaDB - XAMPP)
-- Importe este arquivo no phpMyAdmin (aba "Importar").
-- Ele cria o banco "hp_terraplenagem" do zero, com dados de exemplo.
--
-- Usuários de teste:
--   admin    / admin     (Administrador)
--   operador / operador  (Operador)
-- ==========================================================================

DROP DATABASE IF EXISTS hp_terraplenagem;
CREATE DATABASE hp_terraplenagem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hp_terraplenagem;

-- --------------------------------------------------------------------------
-- Usuários do sistema (senha guardada com password_hash do PHP)
-- --------------------------------------------------------------------------
CREATE TABLE usuarios (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nome      VARCHAR(100) NOT NULL,
    login     VARCHAR(50)  NOT NULL UNIQUE,
    senha     VARCHAR(255) NOT NULL,
    tipo      ENUM('admin', 'operador') NOT NULL DEFAULT 'operador',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------------------------
-- Máquinas e veículos da frota
-- (inclui os dados de manutenção e de documentação de cada um)
-- --------------------------------------------------------------------------
CREATE TABLE maquinas (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nome             VARCHAR(100) NOT NULL UNIQUE,
    tem_horimetro    TINYINT(1) NOT NULL DEFAULT 1,   -- 1 = aparece no apontamento e na manutenção
    horimetro_atual  DECIMAL(10,2) NOT NULL DEFAULT 0,
    ultima_troca     DECIMAL(10,2) NOT NULL DEFAULT 0, -- horímetro da última troca de óleo
    intervalo_troca  INT NOT NULL DEFAULT 250,         -- de quantas em quantas horas troca o óleo
    margem_aviso     INT NOT NULL DEFAULT 50,          -- avisa quando faltar essa quantidade de horas
    placa            VARCHAR(10)  NULL,
    renavam          VARCHAR(20)  NULL,
    crlv_arquivo     VARCHAR(255) NULL                 -- nome do arquivo salvo na pasta uploads/
) ENGINE=InnoDB;

-- --------------------------------------------------------------------------
-- Apontamentos de horas feitos pelos operadores
-- --------------------------------------------------------------------------
CREATE TABLE apontamentos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL,
    maquina_id  INT NOT NULL,
    servico     VARCHAR(200) NOT NULL,
    h_inicial   DECIMAL(10,2) NOT NULL,
    h_final     DECIMAL(10,2) NOT NULL,
    total_horas DECIMAL(10,2) NOT NULL,
    data_hora   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (maquina_id) REFERENCES maquinas(id)
) ENGINE=InnoDB;

-- --------------------------------------------------------------------------
-- Gastos (combustível e manutenção/equipamentos)
-- --------------------------------------------------------------------------
CREATE TABLE gastos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    maquina_id  INT NOT NULL,
    usuario_id  INT NOT NULL,
    tipo        ENUM('combustivel', 'equipamento') NOT NULL,
    valor       DECIMAL(10,2) NOT NULL,
    data        DATE NOT NULL,
    descricao   VARCHAR(255) NULL,
    FOREIGN KEY (maquina_id) REFERENCES maquinas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- --------------------------------------------------------------------------
-- Histórico de trocas de óleo (baixas de manutenção)
-- --------------------------------------------------------------------------
CREATE TABLE manutencoes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    maquina_id  INT NOT NULL,
    usuario_id  INT NOT NULL,
    horimetro   DECIMAL(10,2) NOT NULL,
    observacao  VARCHAR(255) NULL,
    data_hora   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (maquina_id) REFERENCES maquinas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- --------------------------------------------------------------------------
-- Fechamentos mensais
-- --------------------------------------------------------------------------
CREATE TABLE fechamentos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    mes          TINYINT NOT NULL,
    ano          SMALLINT NOT NULL,
    total_horas  DECIMAL(10,2) NOT NULL,
    total_gastos DECIMAL(10,2) NOT NULL,
    usuario_id   INT NOT NULL,
    criado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY periodo (mes, ano),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ==========================================================================
-- DADOS DE EXEMPLO
-- ==========================================================================

INSERT INTO usuarios (id, nome, login, senha, tipo) VALUES
(1, 'Administrador',     'admin',    '$2y$10$5BVkFzj9w5rioq0HRB2YwuE6kW6R97SON/8fmuY3XkvmnIUntElr2', 'admin'),
(2, 'Henrique Operador', 'operador', '$2y$10$EPbYnh5nC.xKwfKx/6eJYuVsGdBom1zsu34.AM6zStpbKXBIE50nq', 'operador');

INSERT INTO maquinas (id, nome, tem_horimetro, horimetro_atual, ultima_troca, intervalo_troca, margem_aviso, placa, renavam) VALUES
(1,  'Retroescavadeira John Deere', 1,  1007.00,   800.00, 250,  50, NULL, NULL),
(2,  'Retroescavadeira JCB',        1,  1255.50,  1000.00, 250,  50, NULL, NULL),
(3,  'Escavadeira',                 1,  5120.00,  5000.00, 250,  50, NULL, NULL),
(4,  'Pá Carregadeira Liugong',     1,  3485.00,  3250.00, 250,  50, NULL, NULL),
(5,  'Caminhão Caçamba VW',         1, 10100.00, 10000.00, 500, 100, 'RTY9988', '12345678901'),
(6,  'Caminhão Caçamba Ford',       1,  7800.00,  7500.00, 500, 100, NULL, NULL),
(7,  'Caminhão Caçamba M.Benz',     1,  5018.50,  4600.00, 500, 100, NULL, NULL),
(8,  'Caminhão Prancha',            1,   345.50,   200.00, 500, 100, NULL, NULL),
(9,  'Carro L200',                  0,     0.00,     0.00, 250,  50, 'ABC1234', '98765432109'),
(10, 'Carro Strada',                0,     0.00,     0.00, 250,  50, NULL, NULL);

INSERT INTO apontamentos (usuario_id, maquina_id, servico, h_inicial, h_final, total_horas, data_hora) VALUES
(2, 3, 'Abertura de vala',               5110.00,  5120.00, 10.00, '2026-09-25 09:00:00'),
(2, 8, 'Transporte de maquinário',        340.00,   345.50,  5.50, '2026-09-28 10:00:00'),
(2, 1, 'Limpeza de terreno SAAE',        1000.50,  1007.00,  6.50, '2026-10-15 08:30:00'),
(2, 4, 'Carregamento de brita',          3481.00,  3485.00,  4.00, '2026-10-18 13:00:00'),
(2, 7, 'Frete de areia Centro',          5010.00,  5018.50,  8.50, '2026-10-20 07:00:00'),
(2, 2, 'Terraplenagem Loteamento Norte', 1248.00,  1255.50,  7.50, '2026-11-03 07:30:00'),
(2, 5, 'Retirada de entulho',           10094.00, 10100.00,  6.00, '2026-11-03 14:00:00');

INSERT INTO gastos (maquina_id, usuario_id, tipo, valor, data, descricao) VALUES
(3, 1, 'combustivel',  480.00, '2026-09-26', 'Posto Ipiranga'),
(2, 1, 'combustivel',  350.00, '2026-10-12', 'Posto Ipiranga'),
(4, 1, 'equipamento', 1200.00, '2026-10-15', 'Troca de pneus dianteiros'),
(9, 1, 'combustivel',  250.00, '2026-10-18', 'Posto Shell'),
(5, 1, 'combustivel',  620.00, '2026-11-02', 'Diesel S10 - Posto Shell'),
(2, 1, 'equipamento',  380.00, '2026-11-03', 'Filtro de ar e mangueira hidráulica');

INSERT INTO manutencoes (maquina_id, usuario_id, horimetro, observacao, data_hora) VALUES
(2, 1, 1000.00, 'Troca de óleo e filtro', '2026-08-10 10:00:00'),
(5, 1, 10000.00, 'Troca de óleo do motor', '2026-08-20 09:00:00');

INSERT INTO fechamentos (mes, ano, total_horas, total_gastos, usuario_id, criado_em) VALUES
(9, 2026, 15.50, 480.00, 1, '2026-10-01 08:00:00');
