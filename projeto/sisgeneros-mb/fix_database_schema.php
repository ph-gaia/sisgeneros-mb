#!/usr/bin/php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Configurations as cfg;

ini_set('max_execution_time', 300);

new class ($argv)
{

    /**
     * @var \PDO The PDO instance
     */
    private $connection;

    public function __construct()
    {
        try {
            $this->runFix();
            $this->message('>> Configurações finalizadas');
        } catch (\Exception $ex) {
            print $ex->getMessage();
        }
    }

    /**
     * Try connect with database and returns the connection reference
     * @return \PDO
     * @throws \Exception
     */
    private function connectDatabase(): \PDO
    {
        try {
            if (!$this->connection) {
                $this->connection = (new HTR\System\ModelCRUD())->pdo;
            }
            return $this->connection;
        } catch (\Exception $ex) {
            throw new \Exception(""
                . "ERRO!"
                . PHP_EOL
                . "Não foi possível connectar ao banco de dados"
                . PHP_EOL
                . $ex->getMessage()
                . PHP_EOL
                . "" . PHP_EOL);
        }
    }

    /**
     * Print a message on screen. Use just one line.
     * @param string $message
     */
    private function message(string $message)
    {
        echo $message . PHP_EOL;
    }

    /**
     * Try creating the database schemas according the dump.sql file
     * @throws \Exception
     */
    private function runFix()
    {
        try {
            // connect into database and execute the SQL queries
            $this->connectDatabase()->exec(
                ""
                    . " ALTER TABLE `sisgeneros`.`biddings_items` "
                    . "     ADD COLUMN `quantity_compromised` FLOAT(9,3) NULL AFTER `quantity`, "
                    . "     ADD COLUMN `quantity_committed` FLOAT(9,3) NULL AFTER `quantity_compromised`, "
                    . "     ADD COLUMN `quantity_available` FLOAT(9,3) NULL AFTER `quantity_committed`; "

                    . " ALTER TABLE `sisgeneros`.`users` "
                    . " CHANGE COLUMN `level` `level` VARCHAR(20) NOT NULL DEFAULT 'NORMAL' ;"

                    . " ALTER TABLE `sisgeneros`.`recipes` "
                    . " CHANGE COLUMN `sort` `sort` VARCHAR(15) NOT NULL; "

                    . " ALTER TABLE `sisgeneros`.`users` "
                    . " ADD COLUMN `nip` VARCHAR(9) NOT NULL; "

                    . " ALTER TABLE `sisgeneros`.`oms` "
                    . "     ADD COLUMN `expense_originator` VARCHAR(100) NULL AFTER `munition_fiel_graduation`, "
                    . "     ADD COLUMN `expense_originator_graduation` VARCHAR(50) NULL AFTER `expense_originator`, "
                    . "     ADD COLUMN `ug` VARCHAR(30) NULL AFTER `expense_originator_graduation`, "
                    . "     ADD COLUMN `ptres` VARCHAR(30) NULL AFTER `ug`, "
                    . "     ADD COLUMN `ai` VARCHAR(30) NULL AFTER `ptres`, "
                    . "     ADD COLUMN `do` VARCHAR(30) NULL AFTER `ai`, "
                    . "     ADD COLUMN `bi` VARCHAR(30) NULL AFTER `do`, "
                    . "     ADD COLUMN `fr` VARCHAR(30) NULL AFTER `bi`, "
                    . "     ADD COLUMN `nd` VARCHAR(30) NULL AFTER `fr`, "
                    . "     ADD COLUMN `cost_center` VARCHAR(30) NULL AFTER `nd`, "
                    . "     ADD COLUMN `classification_items` VARCHAR(30) NULL AFTER `cost_center`, "
                    . " CHANGE COLUMN `updated_at` `updated_at` DATE NOT NULL AFTER `classification_items`; "

                    . " CREATE TABLE IF NOT EXISTS `sisgeneros`.`biddings_oms_lists` ( "
                    . "     `id` INT(11) NOT NULL AUTO_INCREMENT, "
                    . "     `biddings_id` INT(11) NOT NULL, "
                    . "     `oms_id` INT(11) NOT NULL, "
                    . "  PRIMARY KEY (`id`, `biddings_id`, `oms_id`), "
                    . "  INDEX `fk_biddings_has_oms_oms1_idx` (`oms_id` ASC), "
                    . "  INDEX `fk_biddings_has_oms_biddings1_idx` (`biddings_id` ASC), "
                    . "  CONSTRAINT `fk_biddings_has_oms_biddings1` "
                    . "     FOREIGN KEY (`biddings_id`) "
                    . "     REFERENCES `sisgeneros`.`biddings` (`id`) "
                    . "     ON DELETE NO ACTION "
                    . "     ON UPDATE NO ACTION, "
                    . "  CONSTRAINT `fk_biddings_has_oms_oms1` "
                    . "     FOREIGN KEY (`oms_id`) "
                    . "     REFERENCES `sisgeneros`.`oms` (`id`) "
                    . "     ON DELETE NO ACTION "
                    . "     ON UPDATE NO ACTION) "
                    . " ENGINE = InnoDB "
                    . " DEFAULT CHARACTER SET = utf8; "

                    . " ALTER TABLE `sisgeneros`.`requests` "
                    . "     DROP COLUMN `delivery_date`, "
                    . "     ADD COLUMN `complement` VARCHAR(250) NULL AFTER `observation`"
                    . "     ADD COLUMN `modality` VARCHAR(30) NULL AFTER `complement`, "
                    . "     ADD COLUMN `types_invoices` VARCHAR(10) NULL AFTER `modality`, "
                    . "     ADD COLUMN `account_plan` VARCHAR(10) NULL AFTER `types_invoices`, "
                    . "     ADD COLUMN `purposes` VARCHAR(200) NULL AFTER `account_plan`, "
                    . "     ADD COLUMN `reason_rejected` VARCHAR(250) NULL AFTER `purposes`, "
                    . "     ADD COLUMN `reason_canceled` VARCHAR(250) NULL AFTER `reason_rejected`; "

                    . " CREATE TABLE IF NOT EXISTS `sisgeneros`.`provisioned_credits` ( "
                    . "      `id` INT(11) NOT NULL AUTO_INCREMENT, "
                    . "      `oms_id` INT(11) NOT NULL, "
                    . "      `credit_note` VARCHAR(30) NOT NULL, "
                    . "      `value` FLOAT(9,2) NOT NULL, "
                    . "      `created_at` DATETIME NOT NULL, "
                    . "      `updated_at` DATETIME NOT NULL, "
                    . "      `active` VARCHAR(3) NULL DEFAULT 'yes', "
                    . "  PRIMARY KEY (`id`), "
                    . "  INDEX `fk_provisioned_credits_oms_id` (`oms_id` ASC), "
                    . "  CONSTRAINT `fk_provisioned_credits_oms` "
                    . "      FOREIGN KEY (`oms_id`) "
                    . "      REFERENCES `sisgeneros`.`oms` (`id`) "
                    . "      ON DELETE NO ACTION "
                    . "      ON UPDATE NO ACTION) "
                    . "  ENGINE = InnoDB "
                    . "  DEFAULT CHARACTER SET = utf8; "

                    . " CREATE TABLE IF NOT EXISTS `sisgeneros`.`historic_provisioned_credits` ( "
                    . "    `id` INT NOT NULL AUTO_INCREMENT, "
                    . "    `operation_type` VARCHAR(7) NOT NULL DEFAULT 'CREDITO', "
                    . "    `value` FLOAT(9,2) NOT NULL, "
                    . "    `observation` VARCHAR(100) NULL, "
                    . "    `user_id` INT NOT NULL, "
                    . "    `provisioned_credits_id` INT NOT NULL, "
                    . "    `created_at` DATETIME NOT NULL, "
                    . "    PRIMARY KEY (`id`), "
                    . "    INDEX `fk_historic_provisioned_credits_1_idx` (`provisioned_credits_id` ASC), "
                    . "    INDEX `fk_historic_provisioned_credits_2_idx` (`user_id` ASC), "
                    . "    CONSTRAINT `fk_historic_provisioned_credits_1` "
                    . "      FOREIGN KEY (`provisioned_credits_id`) "
                    . "      REFERENCES `sisgeneros`.`provisioned_credits` (`id`) "
                    . "      ON DELETE NO ACTION "
                    . "      ON UPDATE NO ACTION, "
                    . "    CONSTRAINT `fk_historic_provisioned_credits_2` "
                    . "      FOREIGN KEY (`user_id`) "
                    . "      REFERENCES `sisgeneros`.`users` (`id`) "
                    . "      ON DELETE NO ACTION "
                    . "      ON UPDATE NO ACTION); "
                    . "   ENGINE = InnoDB "
                    . "   DEFAULT CHARACTER SET = utf8; "

                    . " CREATE TABLE IF NOT EXISTS `sisgeneros`.`invoices` ( "
                    . "     `id` INT(11) NOT NULL AUTO_INCREMENT, "
                    . "     `oms_id` INT(11) NOT NULL, "
                    . "     `code` VARCHAR(50) NULL DEFAULT NULL, "
                    . "     `total` FLOAT(9,2) NOT NULL, "
                    . "     `status` VARCHAR(20) NOT NULL DEFAULT 'ABERTO', "
                    . "     `created_at` DATETIME NOT NULL, "
                    . "     `updated_at` DATETIME NOT NULL, "
                    . "     PRIMARY KEY (`id`), "
                    . "     INDEX `fk_invoice_oms_id` (`oms_id` ASC), "
                    . "   CONSTRAINT `fk_invoice_oms` "
                    . "       FOREIGN KEY (`oms_id`) "
                    . "       REFERENCES `sisgeneros`.`oms` (`id`) "
                    . "       ON DELETE NO ACTION "
                    . "       ON UPDATE NO ACTION) "
                    . "   ENGINE = InnoDB "
                    . "   DEFAULT CHARACTER SET = utf8; "

                    . " CREATE TABLE IF NOT EXISTS `sisgeneros`.`invoices_items` ( "
                    . "    `id` INT NOT NULL AUTO_INCREMENT, "
                    . "    `requests_id` INT(11) NOT NULL, "
                    . "    `invoices_id` INT(11) NOT NULL, "
                    . "    `suppliers_id` INT(11) NOT NULL, "
                    . "    `number` INT(8) NULL, "
                    . "    `name` VARCHAR(256) NOT NULL, "
                    . "    `uf` VARCHAR(4) NOT NULL, "
                    . "    `quantity` FLOAT(9,3) NOT NULL, "
                    . "    `value` FLOAT(9,2) NOT NULL, "
                    . "    PRIMARY KEY (`id`), "
                    . "    INDEX `fk_invoices_items_invoices_1_idx` (`invoices_id` ASC), "
                    . "    INDEX `fk_invoices_items_invoices_2_idx` (`requests_id` ASC), "
                    . "    INDEX `fk_invoices_items_invoices_3_idx` (`suppliers_id` ASC), "
                    . "    CONSTRAINT `fk_invoices_items_invoices_1` "
                    . "      FOREIGN KEY (`invoices_id`) "
                    . "      REFERENCES `sisgeneros`.`invoices` (`id`) "
                    . "      ON DELETE NO ACTION "
                    . "      ON UPDATE NO ACTION, "
                    . "    CONSTRAINT `fk_invoices_items_invoices_3` "
                    . "      FOREIGN KEY (`suppliers_id`) "
                    . "      REFERENCES `sisgeneros`.`suppliers` (`id`) "
                    . "      ON DELETE NO ACTION "
                    . "      ON UPDATE NO ACTION, "
                    . " CONSTRAINT `fk_invoices_items_invoices_2` "
                    . "     FOREIGN KEY (`requests_id`) "
                    . "     REFERENCES `sisgeneros`.`requests` (`id`) "
                    . "     ON DELETE NO ACTION "
                    . "     ON UPDATE NO ACTION); "

                    . " CREATE TABLE IF NOT EXISTS `sisgeneros`.`requests_invoices` ( "
                    . "    `id` INT(11) NOT NULL AUTO_INCREMENT, "
                    . "    `invoices_id` INT(11) NOT NULL, "
                    . "    `suppliers_id` INT(11) NOT NULL, "
                    . "    `code` INT NOT NULL, "
                    . "    `number` INT(8) NULL DEFAULT NULL, "
                    . "    `invoice` VARCHAR(20) NULL, "
                    . "    `name` VARCHAR(256) NOT NULL, "
                    . "    `uf` VARCHAR(4) NOT NULL, "
                    . "    `quantity` FLOAT(9,3) NOT NULL, "
                    . "    `delivered` FLOAT(9,3) NOT NULL, "
                    . "    `value` FLOAT(9,2) NOT NULL, "
                    . "    `status` VARCHAR(20) NOT NULL, "
                    . "    `observation` VARCHAR(100) NULL, "
                    . "     `created_at` DATETIME NOT NULL, "
                    . "     `updated_at` DATETIME NOT NULL, "
                    . "    PRIMARY KEY (`id`), "
                    . "    INDEX `fk_invoices_items_invoices_1_idx` (`invoices_id` ASC), "
                    . "    INDEX `fk_invoices_items_invoices_2_idx` (`suppliers_id` ASC), "
                    . "    CONSTRAINT `fk_invoices_items_invoices_2` "
                    . "      FOREIGN KEY (`suppliers_id`) "
                    . "      REFERENCES `sisgeneros`.`suppliers` (`id`) "
                    . "      ON DELETE NO ACTION "
                    . "      ON UPDATE NO ACTION, "
                    . "    CONSTRAINT `fk_invoices_items_invoices_10` "
                    . "      FOREIGN KEY (`invoices_id`) "
                    . "      REFERENCES `sisgeneros`.`invoices` (`id`) "
                    . "      ON DELETE NO ACTION "
                    . "      ON UPDATE NO ACTION) "
                    . "  ENGINE = InnoDB "
                    . "  DEFAULT CHARACTER SET = utf8;"

                    . " CREATE TABLE IF NOT EXISTS `sisgeneros`.`historic_action_requests` ( "
                    . "     `id` INT NOT NULL AUTO_INCREMENT, "
                    . "     `requests_id` INT NOT NULL, "
                    . "     `users_id` INT NOT NULL, "
                    . "     `action` VARCHAR(15) NOT NULL, "
                    . "     `nip` VARCHAR(8) NOT NULL, "
                    . "     `user_name` VARCHAR(50) NOT NULL, "
                    . "     `user_profile` VARCHAR(20) NOT NULL, "
                    . "     `date_action` DATETIME NOT NULL, "
                    . " PRIMARY KEY (`id`), "
                    . "     INDEX `fk_historic_action_requests_1_idx` (`requests_id` ASC), "
                    . "     INDEX `fk_historic_action_requests_2_idx` (`users_id` ASC), "
                    . " CONSTRAINT `fk_historic_action_requests_1` "
                    . "     FOREIGN KEY (`requests_id`) "
                    . "     REFERENCES `sisgeneros`.`requests` (`id`) "
                    . "     ON DELETE NO ACTION "
                    . "     ON UPDATE NO ACTION, "
                    . " CONSTRAINT `fk_historic_action_requests_2` "
                    . "     FOREIGN KEY (`users_id`) "
                    . "     REFERENCES `sisgeneros`.`users` (`id`) "
                    . "     ON DELETE NO ACTION "
                    . "     ON UPDATE NO ACTION); "
            );
            $this->message('> Banco de Dados alterado com sucesso');
        } catch (\PDOException $ex) {
            throw new \Exception(""
                . "Não foi possível executar operação" . PHP_EOL
                . "Log:" . $ex->getMessage()
                . "" . PHP_EOL);
        }
    }
};
