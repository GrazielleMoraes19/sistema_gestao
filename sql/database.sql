-- Banco de Dados do Sistema de RH
CREATE DATABASE IF NOT EXISTS rh_system DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rh_system;

-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obfuscated_id VARCHAR(50) UNIQUE NOT NULL,
    nome_completo VARCHAR(200) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    data_nascimento DATE NOT NULL,
    cargo VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    nivel_acesso ENUM('admin','rh','coordenacao','funcionario') DEFAULT 'funcionario',
    status ENUM('ativo','inativo','ferias','licenca') DEFAULT 'ativo',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_obfuscated_id (obfuscated_id),
    INDEX idx_email (email),
    INDEX idx_cargo (cargo)
) ENGINE=InnoDB;

-- Tabela de Registro de Ponto
CREATE TABLE IF NOT EXISTS ponto_registro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    data_registro DATE NOT NULL,
    entrada_manha TIME,
    saida_manha TIME,
    entrada_tarde TIME,
    saida_tarde TIME,
    horas_extras DECIMAL(5,2) DEFAULT 0.00,
    justificativa_extras TEXT,
    status ENUM('trabalhado','nao_trabalhado','sem_registro','falta_justificada') DEFAULT 'sem_registro',
    observacoes TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_data (user_id, data_registro)
) ENGINE=InnoDB;

-- Tabela de Atestados/Justificativas de Falta
CREATE TABLE IF NOT EXISTS atestados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    horario_entrada TIME,
    horario_saida TIME,
    motivo TEXT NOT NULL,
    comprovante_path VARCHAR(255),
    status ENUM('pendente','aprovado','rejeitado') DEFAULT 'pendente',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela de Folha de Pagamento
CREATE TABLE IF NOT EXISTS folha_pagamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mes_referencia INT NOT NULL,
    ano_referencia INT NOT NULL,
    valor_bruto DECIMAL(10,2) NOT NULL,
    valor_liquido DECIMAL(10,2) NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_periodo (user_id, mes_referencia, ano_referencia)
) ENGINE=InnoDB;

-- Tabela de Descontos
CREATE TABLE IF NOT EXISTS descontos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folha_id INT NOT NULL,
    descricao VARCHAR(200) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    justificativa TEXT,
    FOREIGN KEY (folha_id) REFERENCES folha_pagamento(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela de Benefícios
CREATE TABLE IF NOT EXISTS beneficios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folha_id INT NOT NULL,
    descricao VARCHAR(200) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (folha_id) REFERENCES folha_pagamento(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela de Serviços/Tarefas
CREATE TABLE IF NOT EXISTS servicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    prioridade ENUM('baixa','media','alta','urgente') DEFAULT 'media',
    status ENUM('pendente','em_andamento','concluido','cancelado') DEFAULT 'pendente',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_limite DATE,
    criado_por INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela de Chamados/Erros Reportados
CREATE TABLE IF NOT EXISTS chamados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tipo ENUM('erro_ponto','erro_folha','outro') NOT NULL,
    assunto VARCHAR(200) NOT NULL,
    descricao TEXT NOT NULL,
    status ENUM('aberto','em_analise','resolvido','fechado') DEFAULT 'aberto',
    resposta TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Inserir admin padrão (senha: Admin@2026)
INSERT INTO users (obfuscated_id, nome_completo, cpf, data_nascimento, cargo, email, senha_hash, nivel_acesso) VALUES
('ADM-X7K9P2', 'Administrador Sistema', '000.000.000-00', '1990-01-01', 'Administrador', 'admin@rh.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Inserir alguns funcionários de exemplo
INSERT INTO users (obfuscated_id, nome_completo, cpf, data_nascimento, cargo, email, senha_hash, nivel_acesso) VALUES
('EMP-A3B7M1', 'João Silva Santos', '123.456.789-00', '1985-03-15', 'Analista de TI', 'joao.silva@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'funcionario'),
('EMP-C5D9N3', 'Maria Oliveira Lima', '987.654.321-00', '1992-07-22', 'Designer UX', 'maria.oliveira@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'funcionario');

-- Inserir registros de ponto exemplo
INSERT INTO ponto_registro (user_id, data_registro, entrada_manha, saida_manha, entrada_tarde, saida_tarde, horas_extras, status) VALUES
(2, '2026-05-01', '08:00:00', '12:00:00', '13:00:00', '17:00:00', 1.50, 'trabalhado'),
(2, '2026-05-02', '08:05:00', '12:00:00', '13:00:00', '17:10:00', 0.75, 'trabalhado'),
(3, '2026-05-01', '08:00:00', '12:00:00', '13:00:00', '18:00:00', 2.00, 'trabalhado');

-- Inserir folha de pagamento exemplo
INSERT INTO folha_pagamento (user_id, mes_referencia, ano_referencia, valor_bruto, valor_liquido) VALUES
(2, 5, 2026, 5500.00, 4235.50),
(3, 5, 2026, 4800.00, 3696.00);

INSERT INTO descontos (folha_id, descricao, valor, justificativa) VALUES
(1, 'INSS', 605.00, 'Contribuição previdenciária obrigatória'),
(1, 'IRRF', 420.50, 'Imposto de renda retido na fonte'),
(1, 'Vale Transporte', 239.00, 'Desconto de 6% sobre salário bruto'),
(2, 'INSS', 528.00, 'Contribuição previdenciária obrigatória'),
(2, 'IRRF', 336.00, 'Imposto de renda retido na fonte'),
(2, 'Vale Transporte', 240.00, 'Desconto de 6% sobre salário bruto');

INSERT INTO beneficios (folha_id, descricao, valor) VALUES
(1, 'Vale Alimentação', 450.00),
(1, 'Plano de Saúde', 350.00),
(2, 'Vale Alimentação', 450.00),
(2, 'Plano de Saúde', 350.00);

-- Inserir serviços exemplo
INSERT INTO servicos (user_id, titulo, descricao, prioridade, status, data_limite, criado_por) VALUES
(2, 'Atualizar documentação do sistema', 'Revisar e atualizar toda documentação técnica', 'media', 'em_andamento', '2026-06-15', 1),
(3, 'Criar protótipo nova landing page', 'Desenvolver wireframes e protótipo interativo', 'alta', 'pendente', '2026-06-10', 1);