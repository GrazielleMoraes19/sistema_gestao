-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome_completo VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    cargo VARCHAR(100),
    nivel_acesso ENUM('admin', 'rh', 'coordenacao', 'funcionario') DEFAULT 'funcionario',
    status ENUM('ativo', 'inativo', 'ferias', 'licenca') DEFAULT 'ativo',
    data_nascimento DATE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Registro de Ponto
CREATE TABLE IF NOT EXISTS ponto_registro (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    data_registro DATE NOT NULL,
    entrada_manha TIME,
    saida_manha TIME,
    entrada_tarde TIME,
    saida_tarde TIME,
    horas_extras DECIMAL(5, 2) DEFAULT 0,
    status ENUM('trabalhado', 'falta', 'atraso', 'saida_antecipada') DEFAULT 'trabalhado',
    justificativa TEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_ponto (usuario_id, data_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Folha de Pagamento
CREATE TABLE IF NOT EXISTS folha_pagamento (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    mes_referencia INT NOT NULL,
    ano_referencia INT NOT NULL,
    valor_bruto DECIMAL(10, 2) NOT NULL,
    valor_liquido DECIMAL(10, 2) NOT NULL,
    data_processamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('rascunho', 'processada', 'paga') DEFAULT 'rascunho',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_folha (usuario_id, mes_referencia, ano_referencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Descontos
CREATE TABLE IF NOT EXISTS descontos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    folha_id INT NOT NULL,
    descricao VARCHAR(100) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    tipo ENUM('inss', 'irrf', 'vt', 'outro') DEFAULT 'outro',
    FOREIGN KEY (folha_id) REFERENCES folha_pagamento(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Benefícios
CREATE TABLE IF NOT EXISTS beneficios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    folha_id INT NOT NULL,
    descricao VARCHAR(100) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    tipo ENUM('va', 'vr', 'plano_saude', 'outro') DEFAULT 'outro',
    FOREIGN KEY (folha_id) REFERENCES folha_pagamento(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Serviços/Tarefas
CREATE TABLE IF NOT EXISTS servicos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    prioridade ENUM('baixa', 'media', 'alta', 'urgente') DEFAULT 'media',
    status ENUM('pendente', 'em_andamento', 'concluido', 'cancelado') DEFAULT 'pendente',
    data_limite DATE,
    criado_por INT NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Chamados
CREATE TABLE IF NOT EXISTS chamados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    tipo ENUM('erro_ponto', 'erro_folha', 'outro') DEFAULT 'outro',
    assunto VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    status ENUM('aberto', 'em_analise', 'resolvido', 'fechado') DEFAULT 'aberto',
    resposta TEXT,
    respondido_por INT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (respondido_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Atestados
CREATE TABLE IF NOT EXISTS atestados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    motivo VARCHAR(255),
    arquivo_url VARCHAR(255),
    status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar índices para melhor performance
CREATE INDEX idx_usuario_ponto ON ponto_registro(usuario_id);
CREATE INDEX idx_usuario_folha ON folha_pagamento(usuario_id);
CREATE INDEX idx_usuario_servicos ON servicos(usuario_id);
CREATE INDEX idx_usuario_chamados ON chamados(usuario_id);
CREATE INDEX idx_status_chamados ON chamados(status);
CREATE INDEX idx_status_servicos ON servicos(status);
