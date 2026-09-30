SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema locadora
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `locadora` ;
USE `locadora` ;

-- -----------------------------------------------------
-- Table `locadora`.`atores`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `locadora`.`atores` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `locadora`.`generos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `locadora`.`generos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `genero` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `locadora`.`clientes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `locadora`.`clientes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(45) NOT NULL,
  `sobrenome` VARCHAR(45) NOT NULL,
  `telefone` VARCHAR(20) NOT NULL,
  `endereco` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `locadora`.`filmes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `locadora`.`filmes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_genero` INT NOT NULL,
  `titulo` VARCHAR(100) NOT NULL,
  `valor` DECIMAL(8,2) NOT NULL,
  `poster_url` VARCHAR(255) DEFAULT NULL,

  PRIMARY KEY (`id`),
  INDEX `fk_filmes_1_idx` (`id_genero` ASC),
  CONSTRAINT `fk_filmes_1`
    FOREIGN KEY (`id_genero`)
    REFERENCES `locadora`.`generos` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `locadora`.`atores_filme`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `locadora`.`atores_filme` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_filme` INT NOT NULL,
  `id_ator` INT NOT NULL,
  `personagem` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_atores_filme_1_idx` (`id_filme` ASC),
  INDEX `fk_atores_filme_2_idx` (`id_ator` ASC),
  CONSTRAINT `fk_atores_filme_1`
    FOREIGN KEY (`id_filme`)
    REFERENCES `locadora`.`filmes` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_atores_filme_2`
    FOREIGN KEY (`id_ator`)
    REFERENCES `locadora`.`atores` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `locadora`.`dvds`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `locadora`.`dvds` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_filme` INT NOT NULL,
  `quantidade` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_dvds_1_idx` (`id_filme` ASC),
  CONSTRAINT `fk_dvds_1`
    FOREIGN KEY (`id_filme`)
    REFERENCES `locadora`.`filmes` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `locadora`.`emprestimos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `locadora`.`emprestimos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `data` DATETIME NOT NULL,
  `data_prevista` DATETIME NULL DEFAULT NULL,
  `id_cliente` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_emprestimos_1_idx` (`id_cliente` ASC),
  CONSTRAINT `fk_emprestimos_1`
    FOREIGN KEY (`id_cliente`)
    REFERENCES `locadora`.`clientes` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `locadora`.`filmes_emprestimo`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `locadora`.`filmes_emprestimo` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_dvd` INT NOT NULL,
  `id_emprestimo` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_filmes_emprestimo_1_idx` (`id_dvd` ASC),
  INDEX `fk_filmes_emprestimo_2_idx` (`id_emprestimo` ASC),
  CONSTRAINT `fk_filmes_emprestimo_1`
    FOREIGN KEY (`id_dvd`)
    REFERENCES `locadora`.`dvds` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_filmes_emprestimo_2`
    FOREIGN KEY (`id_emprestimo`)
    REFERENCES `locadora`.`emprestimos` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `locadora`.`devolucoes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `locadora`.`devolucoes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_emprestimo` INT NOT NULL,
  `data` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_devolucoes_1_idx` (`id_emprestimo` ASC),
  CONSTRAINT `fk_devolucoes_1`
    FOREIGN KEY (`id_emprestimo`)
    REFERENCES `locadora`.`emprestimos` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `locadora`.`filmes_devolucao`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `locadora`.`filmes_devolucao` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_devolucao` INT NOT NULL,
  `id_filme_emprestimo` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_filmes_devolucao_1_idx` (`id_devolucao` ASC),
  INDEX `fk_filmes_devolucao_2_idx` (`id_filme_emprestimo` ASC),
  CONSTRAINT `fk_filmes_devolucao_1`
    FOREIGN KEY (`id_devolucao`)
    REFERENCES `locadora`.`devolucoes` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_filmes_devolucao_2`
    FOREIGN KEY (`id_filme_emprestimo`)
    REFERENCES `locadora`.`filmes_emprestimo` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `locadora`.`usuarios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `locadora`.`usuarios` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `senha` VARCHAR(255) NOT NULL,
  `perfil` ENUM('funcionario', 'administrador', 'cliente') NOT NULL DEFAULT 'funcionario',
  `id_cliente` INT DEFAULT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `email_UNIQUE` (`email` ASC),
  UNIQUE INDEX `usuarios_cliente_UNIQUE` (`id_cliente` ASC),
  CONSTRAINT `fk_usuarios_cliente`
    FOREIGN KEY (`id_cliente`)
    REFERENCES `locadora`.`clientes` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Inserts `locadora`.`usuarios`
-- -----------------------------------------------------
INSERT INTO `locadora`.`usuarios` (`nome`, `email`, `senha`, `perfil`) VALUES
('Admin Locadora', 'admin@locadora.com', '$2b$12$M0AT5foh8ayi6dRY9wl1R.Uz60fC8a0VeAgfA7E1bSYzRMcNfARY6', 'administrador'),
('Funcionario Teste', 'funcionario@locadora.com', '$2b$12$.StFPfly7WzQdhbv5S5r3.VPl4sP4IXbj/4YvxtLAO7L4nzYHfdgW', 'funcionario');

-- -----------------------------------------------------
-- Clientes de exemplo (100 registros)
-- -----------------------------------------------------
SET NAMES utf8mb4;

INSERT INTO `locadora`.`clientes` (`nome`, `sobrenome`, `telefone`, `endereco`) VALUES
('Ana', 'Souza', '(51) 99101-1001', 'Rua das Flores, 101'),
('Bruno', 'Lima', '(51) 99102-1002', 'Av. Brasil, 202'),
('Carla', 'Pereira', '(51) 99103-1003', 'Rua São José, 303'),
('Diego', 'Almeida', '(51) 99104-1004', 'Rua das Acácias, 404'),
('Elaine', 'Martins', '(51) 99105-1005', 'Av. Rio Branco, 505'),
('Fábio', 'Rodrigues', '(51) 99106-1006', 'Rua do Comércio, 606'),
('Gabriela', 'Costa', '(51) 99107-1007', 'Rua das Palmeiras, 707'),
('Henrique', 'Nunes', '(51) 99108-1008', 'Av. Independência, 808'),
('Isabela', 'Rocha', '(51) 99109-1009', 'Rua Carlos Gomes, 909'),
('João', 'Farias', '(51) 99110-1010', 'Rua Bento Gonçalves, 1110'),
('Karina', 'Mendes', '(51) 99111-1011', 'Av. Farrapos, 1211'),
('Leandro', 'Barbosa', '(51) 99112-1012', 'Rua dos Andradas, 1312'),
('Mariana', 'Teixeira', '(51) 99113-1013', 'Rua General Câmara, 1413'),
('Nathan', 'Carvalho', '(51) 99114-1014', 'Av. Assis Brasil, 1514'),
('Otávio', 'Ribeiro', '(51) 99115-1015', 'Rua Voluntários da Pátria, 1615'),
('Patrícia', 'Moreira', '(51) 99116-1016', 'Rua Dona Laura, 1716'),
('Rafael', 'Cardoso', '(51) 99117-1017', 'Av. Cristóvão Colombo, 1817'),
('Sabrina', 'Correia', '(51) 99118-1018', 'Rua Padre Chagas, 1918'),
('Thiago', 'Pinto', '(51) 99119-1019', 'Rua 24 de Outubro, 2020'),
('Vanessa', 'Azevedo', '(51) 99120-1020', 'Av. Carlos Gomes, 2120'),
('Alexandre', 'Freitas', '(51) 99121-1021', 'Rua Eudoro Berlink, 2221'),
('Beatriz', 'Campos', '(51) 99122-1022', 'Rua Gonçalves Dias, 2322'),
('Caio', 'Duarte', '(51) 99123-1023', 'Av. Ipiranga, 2423'),
('Daniela', 'Vieira', '(51) 99124-1024', 'Rua São Manoel, 2524'),
('Eduardo', 'Barros', '(51) 99125-1025', 'Rua dos Bragas, 2625'),
('Fernanda', 'Sales', '(51) 99126-1026', 'Av. Princesa Isabel, 2726'),
('Gustavo', 'Machado', '(51) 99127-1027', 'Rua Dr. Flores, 2827'),
('Helena', 'Dias', '(51) 99128-1028', 'Rua Vasco da Gama, 2928'),
('Igor', 'Monteiro', '(51) 99129-1029', 'Av. Protásio Alves, 3030'),
('Jéssica', 'Antunes', '(51) 99130-1030', 'Rua Anita Garibaldi, 3130'),
('Kleber', 'Fonseca', '(51) 99131-1031', 'Rua Marquês do Herval, 3231'),
('Larissa', 'Peixoto', '(51) 99132-1032', 'Av. Wenceslau Escobar, 3332'),
('Marcelo', 'Vargas', '(51) 99133-1033', 'Rua Dr. Timóteo, 3433'),
('Natália', 'Serrano', '(51) 99134-1034', 'Rua dos Oitis, 3534'),
('Paulo', 'Siqueira', '(51) 99135-1035', 'Av. Nilo Peçanha, 3635'),
('Renata', 'Cavalcanti', '(51) 99136-1036', 'Rua Dom Pedro II, 3736'),
('Sérgio', 'Batista', '(51) 99137-1037', 'Rua Marechal Floriano, 3837'),
('Tatiane', 'Luz', '(51) 99138-1038', 'Av. Baltazar de Oliveira, 3938'),
('Vinícius', 'Anastácio', '(51) 99139-1039', 'Rua Félix da Cunha, 4040'),
('Wellington', 'Guedes', '(51) 99140-1040', 'Rua Rivadávia Corrêa, 4140'),
('Yasmin', 'Tavares', '(51) 99141-1041', 'Av. Teresópolis, 4241'),
('André', 'Muniz', '(51) 99142-1042', 'Rua Siqueira Campos, 4342'),
('Bianca', 'Nogueira', '(51) 99143-1043', 'Rua Arthur Rocha, 4443'),
('Cristiano', 'Borges', '(51) 99144-1044', 'Av. Pinheiro Borda, 4544'),
('Débora', 'Melo', '(51) 99145-1045', 'Rua Baltazar de Bern, 4645'),
('Emerson', 'Klein', '(51) 99146-1046', 'Rua Pará, 4746'),
('Franciele', 'Aguiar', '(51) 99147-1047', 'Av. Sertório, 4847'),
('Gerson', 'Boff', '(51) 99148-1048', 'Rua Casemiro de Abreu, 4948'),
('Heloísa', 'Ramos', '(51) 99149-1049', 'Rua Érico Veríssimo, 5050'),
('Ítalo', 'Moreno', '(51) 99150-1050', 'Av. Cavalhada, 5150'),
('Jaqueline', 'Ferraz', '(51) 99151-1051', 'Rua Arlindo Pasqualini, 5251'),
('Lucas', 'Quirino', '(51) 99152-1052', 'Rua Duque de Caxias, 5352'),
('Milena', 'Bastos', '(51) 99153-1053', 'Av. Getúlio Vargas, 5453'),
('Nicolas', 'Fontes', '(51) 99154-1054', 'Rua Morro do Saboia, 5554'),
('Osmar', 'Teles', '(51) 99155-1055', 'Rua Dutra, 5655'),
('Priscila', 'Whitaker', '(51) 99156-1056', 'Av. Bento Gonçalves, 5756'),
('Rogério', 'Sales', '(51) 99157-1057', 'Rua Jardim Botânico, 5857'),
('Simone', 'Graça', '(51) 99158-1058', 'Rua Luciana de Abreu, 5958'),
('Tadeu', 'Souto', '(51) 99159-1059', 'Av. Silva Só, 6060'),
('Ulisses', 'Prates', '(51) 99160-1060', 'Rua dos Farrapos, 6160'),
('Vitória', 'Braga', '(51) 99161-1061', 'Rua Coronel Bordini, 6261'),
('Wagner', 'Cunha', '(51) 99162-1062', 'Av. José de Alencar, 6362'),
('Adriana', 'Loreto', '(51) 99163-1063', 'Rua Barão do Triunfo, 6463'),
('Bernardo', 'Goulart', '(51) 99164-1064', 'Rua Senhor dos Passos, 6564'),
('Cíntia', 'Beltrão', '(51) 99165-1065', 'Av. Júlio de Castilhos, 6665'),
('Davi', 'Sampaio', '(51) 99166-1066', 'Rua Demétrio Ribeiro, 6766'),
('Elisa', 'Coutinho', '(51) 99167-1067', 'Rua Visconde do Herval, 6867'),
('Felipe', 'Macedo', '(51) 99168-1068', 'Av. Alberto Bins, 6968'),
('Giovana', 'Neves', '(51) 99169-1069', 'Rua Hilário Ribeiro, 7070'),
('Heitor', 'Paz', '(51) 99170-1070', 'Rua Ramiro Barcelos, 7170'),
('Isadora', 'Toledo', '(51) 99171-1071', 'Av. Plínio Brasil Milano, 7271'),
('Joaquim', 'Aragão', '(51) 99172-1072', 'Rua Comendador Crespo, 7372'),
('Kelly', 'Bernardes', '(51) 99173-1073', 'Rua Faria Santos, 7473'),
('Lorena', 'Calixto', '(51) 99174-1074', 'Av. Icarai, 7574'),
('Murilo', 'Esteves', '(51) 99175-1075', 'Rua Uruguai, 7675'),
('Nívea', 'Gonçalves', '(51) 99176-1076', 'Rua Lopo Gonçalves, 7776'),
('Otília', 'Franz', '(51) 99177-1077', 'Av. Oscar Pereira, 7877'),
('Pedro', 'Vasques', '(51) 99178-1078', 'Rua São Luís, 7978'),
('Quezia', 'Dornelles', '(51) 99179-1079', 'Rua Almirante Barroso, 8080'),
('Rodrigo', 'Fraga', '(51) 99180-1080', 'Av. Saturnino de Brito, 8180'),
('Sandra', 'Palma', '(51) 99181-1081', 'Rua Cristóvão Pereira, 8281'),
('Toninho', 'Miranda', '(51) 99182-1082', 'Rua Dr. Pessoa, 8382'),
('Úrsula', 'Delgado', '(51) 99183-1083', 'Av. João Pessoa, 8483'),
('Valter', 'Seixas', '(51) 99184-1084', 'Rua Padre Landell, 8584'),
('William', 'Torres', '(51) 99185-1085', 'Rua Riachuelo, 8685'),
('Ximena', 'Castilho', '(51) 99186-1086', 'Av. Ceará, 8786'),
('Yuri', 'Pinzon', '(51) 99187-1087', 'Rua Piauí, 8887'),
('Zuleica', 'Amorim', '(51) 99188-1088', 'Rua Fernão Dias, 8988'),
('Alice', 'Sobrinho', '(51) 99189-1089', 'Av. Bahia, 9090'),
('Breno', 'Ataíde', '(51) 99190-1090', 'Rua Sergipe, 9190'),
('Clara', 'Felix', '(51) 99191-1091', 'Rua Maranhão, 9291'),
('Douglas', 'Toscano', '(51) 99192-1092', 'Av. Amazonas, 9392'),
('Ester', 'Lamarr', '(51) 99193-1093', 'Rua Tocantins, 9493'),
('Flávio', 'Guimarães', '(51) 99194-1094', 'Rua Goiás, 9594'),
('Geovana', 'Rosa', '(51) 99195-1095', 'Av. Paraná, 9695'),
('Hugo', 'Nascimento', '(51) 99196-1096', 'Rua Santa Catarina, 9796'),
('Ingrid', 'Zanella', '(51) 99197-1097', 'Rua Espírito Santo, 9897'),
('Joana', 'Belo', '(51) 99198-1098', 'Av. Mato Grosso, 9998'),
('Levi', 'Couto', '(51) 99199-1099', 'Rua Alagoas, 10099'),
('Mônica', 'Iasbeck', '(51) 99200-1100', 'Rua Sérgio de Andrade, 101100');


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;