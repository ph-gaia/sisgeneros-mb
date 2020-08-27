-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';
SET GLOBAL SQL_MODE=(SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));

-- -----------------------------------------------------
-- Schema sisgeneros_mb
-- -----------------------------------------------------
DROP SCHEMA IF EXISTS `sisgeneros_mb` ;

-- -----------------------------------------------------
-- Schema sisgeneros_mb
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `sisgeneros_mb` DEFAULT CHARACTER SET utf8 ;
USE `sisgeneros_mb` ;

-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`oms`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`oms` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`oms` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `naval_indicative` VARCHAR(6) NOT NULL,
  `uasg` INT(6) NOT NULL,
  `fiscal_agent` VARCHAR(100) NOT NULL COMMENT 'Nome do Agente Fiscal',
  `fiscal_agent_graduation` VARCHAR(50) NOT NULL,
  `munition_manager` VARCHAR(100) NOT NULL COMMENT 'Nome do Gestor de Municiamento',
  `munition_manager_graduation` VARCHAR(50) NOT NULL,
  `munition_fiel` VARCHAR(100) NOT NULL COMMENT 'Nome do Fiel de Municiamento',
  `munition_fiel_graduation` VARCHAR(50) NOT NULL,
  `expense_originator` VARCHAR(100) NULL,
  `expense_originator_graduation` VARCHAR(50) NULL,
  `ug` varchar(30) DEFAULT NULL,
  `ptres` varchar(30) DEFAULT NULL,
  `ai` varchar(30) DEFAULT NULL,
  `do` varchar(30) DEFAULT NULL,
  `bi` varchar(30) DEFAULT NULL,
  `fr` varchar(30) DEFAULT NULL,
  `nd` varchar(30) DEFAULT NULL,
  `cost_center` varchar(30) DEFAULT NULL,
  `classification_items` VARCHAR(30) NULL,
  `limit_request_nl` float(9,2) DEFAULT NULL,
  `created_at` DATE NOT NULL,
  `updated_at` DATE NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC),
  UNIQUE INDEX `naval_indicative_UNIQUE` (`naval_indicative` ASC))
ENGINE = InnoDB
COMMENT = 'Organizações Militares';


-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`users` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oms_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `level` varchar(20) NOT NULL DEFAULT 'NORMAL',
  `username` varchar(20) NOT NULL,
  `password` varchar(60) NOT NULL,
  `change_password` varchar(3) NOT NULL DEFAULT 'yes',
  `nip` varchar(9) NOT NULL DEFAULT '0',
  `active` varchar(3) NOT NULL DEFAULT 'yes',
  `created_at` date NOT NULL,
  `updated_at` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `username_UNIQUE` (`username` ASC),
  INDEX `fk_users_oms_idx` (`oms_id` ASC),
  CONSTRAINT `fk_users_oms`
    FOREIGN KEY (`oms_id`)
    REFERENCES `sisgeneros_mb`.`oms` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'tabela de usuário, contendo os dados do usuário e as credenciais de acesso';


-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`suppliers`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`suppliers` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`suppliers` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `cnpj` VARCHAR(18) NOT NULL,
  `details` VARCHAR(256) NULL DEFAULT 'Dados do fornecedor...',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC),
  UNIQUE INDEX `cnpj_UNIQUE` (`cnpj` ASC))
ENGINE = InnoDB
COMMENT = 'Fornecedores';


-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`biddings`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`biddings` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`biddings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `number` VARCHAR(10) NOT NULL,
  `uasg` INT(6) NOT NULL,
  `uasg_name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(30) NULL,
  `validate` DATE NOT NULL,
  `created_at` DATE NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `number_UNIQUE` (`number` ASC))
ENGINE = InnoDB
COMMENT = 'Licitações do sistema';


-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`ingredients`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`ingredients` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`ingredients` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
COMMENT = 'Igredientes usados na confecção de receitas';


-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`biddings_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`biddings_items` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`biddings_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `biddings_id` INT NOT NULL,
  `suppliers_id` INT NOT NULL,
  `ingredients_id` INT NOT NULL,
  `number` INT(5) NOT NULL,
  `name` VARCHAR(256) NOT NULL,
  `uf` VARCHAR(4) NOT NULL,
  `quantity` float(9,3) NOT NULL,
  `quantity_compromised` float(9,3) DEFAULT NULL,
  `quantity_committed` float(9,3) DEFAULT NULL,
  `quantity_available` float(9,3) DEFAULT NULL,
  `value` FLOAT(9,2) NOT NULL,
  `active` VARCHAR(3) NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`id`),
  INDEX `fk_biddings_items_biddings1_idx` (`biddings_id` ASC),
  INDEX `fk_biddings_items_suppliers1_idx` (`suppliers_id` ASC),
  INDEX `fk_biddings_items_ingredients1_idx` (`ingredients_id` ASC),
  CONSTRAINT `fk_biddings_items_biddings1`
    FOREIGN KEY (`biddings_id`)
    REFERENCES `sisgeneros_mb`.`biddings` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_biddings_items_suppliers1`
    FOREIGN KEY (`suppliers_id`)
    REFERENCES `sisgeneros_mb`.`suppliers` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_biddings_items_ingredients1`
    FOREIGN KEY (`ingredients_id`)
    REFERENCES `sisgeneros_mb`.`ingredients` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Itens das Licitações Registradas no Sistema';

--
-- Table structure for table `biddings_oms_lists`
--

DROP TABLE IF EXISTS `sisgeneros_mb`.`biddings_oms_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`biddings_oms_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `biddings_id` int(11) NOT NULL,
  `oms_id` int(11) NOT NULL,
  PRIMARY KEY (`id`,`biddings_id`,`oms_id`),
    KEY `fk_biddings_has_oms_oms1_idx` (`oms_id`),
    KEY `fk_biddings_has_oms_biddings1_idx` (`biddings_id`),
  CONSTRAINT `fk_biddings_has_oms_biddings1`
    FOREIGN KEY (`biddings_id`)
    REFERENCES `biddings` (`id`)
    ON DELETE NO ACTION 
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_biddings_has_oms_oms1` FOREIGN KEY (`oms_id`) REFERENCES `oms` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;



-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`billboards`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`billboards` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`billboards` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(100) NOT NULL,
  `content` VARCHAR(256) NOT NULL,
  `beginning_date` DATE NOT NULL,
  `ending_date` DATE NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
COMMENT = 'Quadro de avisos';


-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`billboards_oms_lists`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`billboards_oms_lists` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`billboards_oms_lists` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `billboards_id` INT NOT NULL,
  `oms_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_billboards_oms_lists_billboards1_idx` (`billboards_id` ASC),
  INDEX `fk_billboards_oms_lists_oms1_idx` (`oms_id` ASC),
  CONSTRAINT `fk_billboards_oms_lists_billboards1`
    FOREIGN KEY (`billboards_id`)
    REFERENCES `sisgeneros_mb`.`billboards` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_billboards_oms_lists_oms1`
    FOREIGN KEY (`oms_id`)
    REFERENCES `sisgeneros_mb`.`oms` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Organizações Militares permitidas';

--
-- Table structure for table `historic_action_requests`
--

DROP TABLE IF EXISTS `sisgeneros_mb`.`historic_action_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`historic_action_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requests_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `action` varchar(15) NOT NULL,
  `nip` varchar(8) NOT NULL,
  `user_name` varchar(50) NOT NULL,
  `user_profile` varchar(20) NOT NULL,
  `date_action` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_historic_action_requests_1_idx` (`requests_id`),
  KEY `fk_historic_action_requests_2_idx` (`users_id`),
  CONSTRAINT `fk_historic_action_requests_1`
    FOREIGN KEY (`requests_id`)
    REFERENCES `requests` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_historic_action_requests_2`
    FOREIGN KEY (`users_id`)
    REFERENCES `users` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `historic_provisioned_credits`
--

DROP TABLE IF EXISTS `sisgeneros_mb`.`historic_provisioned_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`historic_provisioned_credits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operation_type` varchar(7) NOT NULL DEFAULT 'CREDITO',
  `value` float(9,2) NOT NULL,
  `observation` varchar(100) DEFAULT NULL,
  `provisioned_credits_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_historic_provisioned_credits_1_idx` (`provisioned_credits_id`),
  CONSTRAINT `fk_historic_provisioned_credits_1`
    FOREIGN KEY (`provisioned_credits_id`)
    REFERENCES `provisioned_credits` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `sisgeneros_mb`.`invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oms_id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ABERTO',
  `complement` varchar(250) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_invoice_oms_id` (`oms_id`),
  CONSTRAINT `fk_invoice_oms`
    FOREIGN KEY (`oms_id`)
    REFERENCES `oms` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoices_items`
--

DROP TABLE IF EXISTS `sisgeneros_mb`.`invoices_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`invoices_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requests_id` int(11) NOT NULL,
  `invoices_id` int(11) NOT NULL,
  `suppliers_id` int(11) NOT NULL,
  `number` int(8) DEFAULT NULL,
  `name` varchar(256) NOT NULL,
  `uf` varchar(4) NOT NULL,
  `quantity` float(9,3) NOT NULL,
  `delivered` float(9,3) NOT NULL,
  `value` float(9,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_invoices_items_invoices_1_idx` (`invoices_id`),
  KEY `fk_invoices_items_invoices_2_idx` (`requests_id`),
  KEY `fk_invoices_items_invoices_3_idx` (`suppliers_id`),
  CONSTRAINT `fk_invoices_items_invoices_1`
    FOREIGN KEY (`invoices_id`)
    REFERENCES `invoices` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_invoices_items_invoices_2`
    FOREIGN KEY (`requests_id`)
    REFERENCES `requests` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `provisioned_credits`
--

DROP TABLE IF EXISTS `sisgeneros_mb`.`provisioned_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`provisioned_credits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oms_id` int(11) NOT NULL,
  `credit_note` varchar(30) NOT NULL,
  `value` float(9,2) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `active` varchar(3) DEFAULT 'yes',
  PRIMARY KEY (`id`),
  KEY `fk_provisioned_credits_oms_id` (`oms_id`),
  CONSTRAINT `fk_provisioned_credits_oms` FOREIGN KEY (`oms_id`) REFERENCES `oms` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`requests`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`requests` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`requests` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `oms_id` INT NOT NULL,
  `suppliers_id` INT NOT NULL,
  `biddings_id` INT NULL,
  `number` INT(8) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'ABERTO',
  `invoice` VARCHAR(20) NOT NULL DEFAULT 'S/N',
  `observation` varchar(512) DEFAULT NULL,
  `complement` varchar(250) DEFAULT NULL,
  `modality` varchar(30) DEFAULT NULL,
  `types_invoices` varchar(10) DEFAULT NULL,
  `account_plan` varchar(10) DEFAULT NULL,
  `purposes` varchar(200) DEFAULT NULL,
  `reason_action` varchar(250) DEFAULT NULL,
  `created_at` DATE NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `number_UNIQUE` (`number` ASC),
  INDEX `fk_requests_oms1_idx` (`oms_id` ASC),
  INDEX `fk_requests_suppliers1_idx` (`suppliers_id` ASC),
  CONSTRAINT `fk_requests_oms1`
    FOREIGN KEY (`oms_id`)
    REFERENCES `sisgeneros_mb`.`oms` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_requests_suppliers1`
    FOREIGN KEY (`suppliers_id`)
    REFERENCES `sisgeneros_mb`.`suppliers` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Solicitações de itens Licitados e Não Licitados';


-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`requests_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`requests_items` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`requests_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `requests_id` INT NOT NULL,
  `number` INT(8) NULL,
  `name` VARCHAR(256) NOT NULL,
  `uf` VARCHAR(4) NOT NULL,
  `quantity` FLOAT(9,3) NOT NULL COMMENT 'Quantidade solicitada',
  `delivered` FLOAT(9,3) NOT NULL COMMENT 'Quantidade entregue',
  `value` FLOAT(9,2) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_requests_items_requests1_idx` (`requests_id` ASC),
  CONSTRAINT `fk_requests_items_requests1`
    FOREIGN KEY (`requests_id`)
    REFERENCES `sisgeneros_mb`.`requests` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Items das solicitações';

--
-- Table structure for table `requests_invoices`
--

DROP TABLE IF EXISTS `sisgeneros_mb`.`requests_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`requests_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoices_id` int(11) NOT NULL,
  `suppliers_id` int(11) DEFAULT NULL,
  `code` int(11) NOT NULL,
  `number` int(8) DEFAULT NULL,
  `invoice` varchar(20) DEFAULT NULL,
  `name` varchar(256) NOT NULL,
  `uf` varchar(4) NOT NULL,
  `quantity` float(9,3) NOT NULL,
  `delivered` float(9,3) NOT NULL,
  `value` float(9,2) NOT NULL,
  `status` varchar(20) NOT NULL,
  `observation` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_invoices_items_invoices_1_idx` (`invoices_id`),
  KEY `fk_requests_invoices_code` (`code`),
  CONSTRAINT `fk_invoices_items_invoices_10`
    FOREIGN KEY (`invoices_id`)
    REFERENCES `invoices` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`suppliers_evaluations`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`suppliers_evaluations` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`suppliers_evaluations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `requests_id` INT NOT NULL,
  `evaluation` INT(1) NOT NULL DEFAULT 3,
  PRIMARY KEY (`id`),
  INDEX `fk_suppliers_evaluations_requests1_idx` (`requests_id` ASC),
  CONSTRAINT `fk_suppliers_evaluations_requests1`
    FOREIGN KEY (`requests_id`)
    REFERENCES `requests_invoices` (`code`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
ENGINE = InnoDB
COMMENT = 'Avaliação de entrega dos fornecedores';


-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`recipes_patterns`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`recipes_patterns` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`recipes_patterns` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
COMMENT = 'Receitas padrões registradas no sistema';


-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`recipes_patterns_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`recipes_patterns_items` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`recipes_patterns_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ingredients_id` INT NOT NULL,
  `recipes_patterns_id` INT NOT NULL,
  `quantity` FLOAT(9,3) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_recipes_patterns_items_recipes_patterns1_idx` (`recipes_patterns_id` ASC),
  INDEX `fk_recipes_patterns_items_ingredients1_idx` (`ingredients_id` ASC),
  CONSTRAINT `fk_recipes_patterns_items_recipes_patterns1`
    FOREIGN KEY (`recipes_patterns_id`)
    REFERENCES `sisgeneros_mb`.`recipes_patterns` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_recipes_patterns_items_ingredients1`
    FOREIGN KEY (`ingredients_id`)
    REFERENCES `sisgeneros_mb`.`ingredients` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Itens das receitas';


-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`menus`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`menus` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`menus` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `oms_id` INT NOT NULL,
  `users_id_requesters` INT NOT NULL,
  `users_id_authorizers` INT NOT NULL,
  `beginning_date` DATE NOT NULL,
  `ending_date` DATE NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'ABERTO',
  `raw_menus_object` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_menus_oms1_idx` (`oms_id` ASC),
  INDEX `fk_menus_users1_idx` (`users_id_requesters` ASC),
  INDEX `fk_menus_users2_idx` (`users_id_authorizers` ASC),
  CONSTRAINT `fk_menus_oms1`
    FOREIGN KEY (`oms_id`)
    REFERENCES `sisgeneros_mb`.`oms` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_menus_users1`
    FOREIGN KEY (`users_id_requesters`)
    REFERENCES `sisgeneros_mb`.`users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_menus_users2`
    FOREIGN KEY (`users_id_authorizers`)
    REFERENCES `sisgeneros_mb`.`users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Cardápios registrados pela Organizações Militares';


-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`meals`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`meals` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`meals` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sort` VARCHAR(15) NOT NULL DEFAULT 1,
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
COMMENT = 'Refeições diárias';


-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`recipes`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`recipes` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`recipes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `meals_id` INT NOT NULL,
  `menus_id` INT NOT NULL,
  `recipes_patterns_id` INT NOT NULL COMMENT 'Receita padrão usada como base',
  `name` VARCHAR(50) NOT NULL,
  `quantity_people` INT(5) NOT NULL COMMENT 'Quantidade de pessoas a serem atendidas',
  `date` DATE NOT NULL,
  `sort` VARCHAR(15) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_recipes_recipes_patterns1_idx` (`recipes_patterns_id` ASC),
  INDEX `fk_recipes_meals1_idx` (`meals_id` ASC),
  INDEX `fk_recipes_menus1_idx` (`menus_id` ASC),
  CONSTRAINT `fk_recipes_recipes_patterns1`
    FOREIGN KEY (`recipes_patterns_id`)
    REFERENCES `sisgeneros_mb`.`recipes_patterns` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_recipes_meals1`
    FOREIGN KEY (`meals_id`)
    REFERENCES `sisgeneros_mb`.`meals` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_recipes_menus1`
    FOREIGN KEY (`menus_id`)
    REFERENCES `sisgeneros_mb`.`menus` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sisgeneros_mb`.`recipes_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros_mb`.`recipes_items` ;

CREATE TABLE IF NOT EXISTS `sisgeneros_mb`.`recipes_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `recipes_id` INT NOT NULL,
  `biddings_items_id` INT NULL COMMENT 'Item da licitação quando houver',
  `name` VARCHAR(50) NOT NULL,
  `suggested_quantity` FLOAT(9,3) NOT NULL,
  `quantity` FLOAT(9,3) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_recispes_items_recipes1_idx` (`recipes_id` ASC),
  CONSTRAINT `fk_recispes_items_recipes1`
    FOREIGN KEY (`recipes_id`)
    REFERENCES `sisgeneros_mb`.`recipes` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
