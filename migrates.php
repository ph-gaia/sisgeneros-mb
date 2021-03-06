<?php

/**
 * This file migrates the data from Sqlite to MySQL database
 */
require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Configurations as cfg;
use App\Config\DatabaseConfig;

ini_set('max_execution_time', 300);

try {

    new class ($argv)
    {

        /**
         * The command to able the migration
         */
        const RUN_COMMAND = '--executar';

        /**
         * @var \PDO The PDO instance
         */
        private $connectionMySQL;

        /**
         * @var \PDO The PDO instance
         */
        private $connectionMySQLOld;

        /**
         * @var App\Config\DatabaseConfig
         */
        private $databaseConfig;

        /**
         * @var array The temporary cache SQL queries
         */
        private $queryCache = [];

        /**
         * @param array $args The values of CLI $argv
         */
        public function __construct(array $args)
        {
            $this->databaseConfig = new DatabaseConfig();
            $this->setUp($args);
            $this->migrateTableOms();
            $this->migrateTableUsers();
            $this->migrateTableSuppliers();
            $this->migrateTableBiddings();
            $this->migrateTableBiddingsItems();
            $this->migrateTableBiddingsOmsLists();
            // $this->migrateTableRequests();
            // $this->migrateTableRequestsItems();
            // $this->migrateTableSuppliersEvaluantions();
            $this->migrateTableBillboards();
            $this->migrateTableBillboardsOmsLists();
            $this->migrateTableIngredients();
            $this->migrateTableRecipePatterns();
            $this->migrateTableRecipePatternsItems();
            $this->migrateTableMenu();
            $this->migrateTableRecipes();
            $this->migrateTableRecipesItems();
            // $this->balanceCompromisedBiddings();
            // $this->balanceCommittedBiddings();
            // $this->updateAvailableBiddings();
        }

        /**
         * Check the system and configurates the things
         * @param array $args The value of $argv - CLI
         * @throws \Exception
         */
        public function setUp(array $args)
        {
            if (!isset($args[1]) || (isset($args[1]) && $args[1] !== self::RUN_COMMAND)) {
                throw new \Exception(""
                    . "Para executar a migração é necessário passar como parâmtero: '--executar'"
                    . PHP_EOL
                    . "> php migrates.php --executar"
                    . PHP_EOL . "");
            }

            $this->message(""
                . "Esta operação pode levar alguns minutos..."
                . PHP_EOL
                . "O melhor a fazer é tomar um café e aguardar."
                . PHP_EOL
                . "");
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
         * Abstracr the configuration and format the return of date function
         * @param string $format The output format
         * @return string
         */
        private function getTime(string $format = 'd-m-Y H:i:s'): string
        {
            return date($format);
        }

        /**
         * Try connect with database and returns the connection reference
         * @return \PDO
         * @throws \Exception
         */
        private function connectMySQL(): \PDO
        {
            try {
                if (!$this->connectionMySQL) {
                    $config = $this->databaseConfig->db;
                    $dns = 'mysql:host=' . $config['servidor'] . ';dbname=' . $config['banco'];
                    $this->connectionMySQL = new \PDO(
                        $dns,
                        $config['usuario'],
                        $config['senha'],
                        $config['opcoes']
                    );
                    $this->connectionMySQL->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                }
                return $this->connectionMySQL;
            } catch (\PDOException $ex) {
                throw new \Exception(""
                    . "ERRO!"
                    . PHP_EOL
                    . "Não foi possível connectar ao banco de dados MySQL"
                    . PHP_EOL
                    . $ex->getMessage()
                    . "" . PHP_EOL);
            }
        }

        /**
         * Try connect with database and returns the connection reference
         * @return \PDO
         * @throws \Exception
         */
        private function connectMySQLOldDatabase(): \PDO
        {
            try {
                if (!$this->connectionMySQLOld) {
                    $config = $this->databaseConfig->db;
                    $dns = 'mysql:host=' . $config['servidor'] . ';dbname=sisgeneros';
                    $this->connectionMySQLOld = new \PDO(
                        $dns,
                        $config['usuario'],
                        $config['senha'],
                        $config['opcoes']
                    );
                    $this->connectionMySQLOld->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                }
                return $this->connectionMySQLOld;
            } catch (\PDOException $ex) {
                throw new \Exception(""
                    . "ERRO!"
                    . PHP_EOL
                    . "Não foi possível connectar ao banco de dados MySQL"
                    . PHP_EOL
                    . $ex->getMessage()
                    . "" . PHP_EOL);
            }
        }

        /**
         * Generic method used to insert a new register into database
         * @param array $data The data to be inserted
         * @param string $entity The entity from database (MySQL)
         * @return bool
         * @throws \Exception
         */
        private function create(array $data, string $entity): bool
        {
            try {
                $fields = implode(',', array_keys($data));
                $values = implode(',', array_fill(0, count($data), '?'));
                $dataBind = array_values($data);
                $sql = "INSERT INTO `{$entity}` ({$fields}) VALUES($values);";
                return $this->connectMySQL()->prepare($sql)->execute($dataBind);
            } catch (\Exception $ex) {
                throw new \Exception(""
                    . "ERRO!"
                    . PHP_EOL
                    . "Não foi possível inserir o registro no banco de dados MySQL"
                    . PHP_EOL
                    . $ex->getMessage()
                    . "" . PHP_EOL);
            }
        }

        /**
         * Update a register on databse
         * @param array $data The datas to be modified
         * @param mixed $id The id of register
         * @param string $entity The entity from database (MySQL)
         * @return bool
         * @throws \Exception
         */
        private function update(array $data, $id, string $entity): bool
        {
            try {
                $fields = array_reduce($data, function ($acc, $item) use ($data) {
                    $field = array_search($item, $data);
                    $acc[] = "{$field}=`{$item}`";
                    return $acc;
                }, []);
                $fields = implode(',', $fields);
                $sql = "UPDATE `{$entity}` SET {$fields} WHERE `{$entity}`.`id` = {$id};";
                return !$this->connectMySQL()->exec($sql) === false;
            } catch (\PDOException $ex) {
                throw new \Exception(""
                    . "ERRO!"
                    . PHP_EOL
                    . "Não foi possível atualizar o registro no banco de dados MySQL"
                    . PHP_EOL
                    . $ex->getMessage()
                    . "" . PHP_EOL);
            }
        }

        /**
         * Remove all datas from table
         * @param string $entity The entity to be used
         * @return bool
         * @throws \Exception
         */
        private function clearTable(string $entity): bool
        {
            try {
                $sql = "DELETE FROM `{$entity}` WHERE `{$entity}`.`id` != '0';";
                $sql .= "ALTER TABLE `{$entity}` AUTO_INCREMENT = 1;";
                return !$this->connectMySQL()->exec($sql) === false;
            } catch (\PDOException $ex) {
                throw new \Exception(""
                    . "ERRO!"
                    . PHP_EOL
                    . "Não foi possível limpar a tabela {$entity}"
                    . PHP_EOL
                    . $ex->getMessage()
                    . "" . PHP_EOL);
            }
        }

        /**
         * Simple cache systems to avoid repeated SQL queries
         * @param string $entity The entity of data
         * @param mixed $identifier The key of data
         * @return array|bool
         */
        private function getFromCache(string $entity, $identifier)
        {
            return $this->queryCache[$entity][$identifier] ?? false;
        }

        /**
         * Save the result queries to avoid repeat SQL queries
         * @param string $entity The entity of data
         * @param type $identifier The key of data
         * @param array $data The data values to be saved
         */
        private function saveIntoCache(string $entity, $identifier, array $data)
        {
            $this->queryCache[$entity][$identifier] = $data;
        }

        /**
         * Returns the new register for OM based in old Id
         * @param int $oldId The old id
         * @return array The result
         */
        private function fetchNewOmsData($oldId): array
        {
            $entityMySQL = 'oms';
            $cache = $this->getFromCache($entityMySQL, $oldId);

            if ($cache) {
                return $cache;
            } else {
                $valueMysql = $this->fetchData($entityMySQL, "id = {$oldId}", 'mysql');
                $result = $valueMysql[0] ?? false;

                if ($result) {
                    $this->saveIntoCache($entityMySQL, $oldId, $result);
                    return $result;
                }

                return [];
            }
        }

        /**
         * Returns the new register for Biddings based in id_lista
         * @param int $idLista The value of id_lista
         * @return array The result
         */
        private function fetchNewBiddingsData($idLista): array
        {
            $entityMySQL = 'biddings';
            $cache = $this->getFromCache($entityMySQL, $idLista);

            if ($cache) {
                return $cache;
            } else {
                $valueOld = $this->fetchData($entityMySQL, "id = {$idLista}");

                if (isset($valueOld[0])) {
                    $where = "uasg = '{$valueOld[0]['uasg']}' AND number = '{$valueOld[0]['number']}'";
                    $valueMysql = $this->fetchData($entityMySQL, $where ?? '', 'mysql');
                    $result = $valueMysql[0] ?? false;

                    if ($result) {
                        $this->saveIntoCache($entityMySQL, $idLista, $result);
                        return $result;
                    }
                }

                return [];
            }
        }

        /**
         * Returns the new register for Suppliers based in old Id
         * @param int $oldId The old id
         * @return array The result
         */
        private function fetchNewSuppliersData($oldId): array
        {
            $entityMySQL = 'suppliers';
            $cache = $this->getFromCache($entityMySQL, $oldId);

            if ($cache) {
                return $cache;
            } else {
                $valueOld = $this->fetchData($entityMySQL, "id = {$oldId}");

                if (isset($valueOld[0])) {
                    $where = "cnpj = '{$valueOld[0]['cnpj']}'";
                    $valueMysql = $this->fetchData($entityMySQL, $where ?? '', 'mysql');
                    $result = $valueMysql[0] ?? false;

                    if ($result) {
                        $this->saveIntoCache($entityMySQL, $oldId, $result);
                        return $result;
                    }
                }

                return [];
            }
        }

        /**
         * Returns the new register for Requests based in id_lista
         * @param int $idLista The value of id_lista
         * @return array The result
         */
        private function fetchNewRequestsData($idLista): array
        {
            $entityMySQL = 'requests';
            $cache = $this->getFromCache($entityMySQL, $idLista);

            if ($cache) {
                return $cache;
            } else {
                $valueOld = $this->fetchData($entityMySQL, "id = '{$idLista}'");

                if (isset($valueOld[0])) {
                    $where = "number = '{$valueOld[0]['number']}'";
                    $valueMysql = $this->fetchData($entityMySQL, $where ?? '', 'mysql');
                    $result = $valueMysql[0] ?? false;

                    if ($result) {
                        $this->saveIntoCache($entityMySQL, $idLista, $result);
                        return $result;
                    }
                }

                return [];
            }
        }

        /**
         * Returns the new register for Requests based in old Id
         * @param int $oldId The old id
         * @return array The result
         */
        private function fetchNewRequestsByIdData($oldId): array
        {
            $entitySqlite = 'solicitacao';
            $entityMySQL = 'requests';
            $cache = $this->getFromCache($entityMySQL . '_id', $oldId);

            if ($cache) {
                return $cache;
            } else {
                $valueSqlite = $this->fetchData($entitySqlite, "id = '{$oldId}'");
                if (isset($valueSqlite[0])) {
                    $where = "number = '{$valueSqlite[0]['numero']}'";
                    $valueMysql = $this->fetchData($entityMySQL, $where ?? '', 'mysql');
                    $result = $valueMysql[0] ?? false;

                    if ($result) {
                        $this->saveIntoCache($entityMySQL . '_id', $oldId, $result);
                        return $result;
                    }
                }

                return [];
            }
        }

        /**
         * Returns the new register for Billboards based in old Id
         * @param int $oldId The old id
         * @return array The result
         */
        private function fetchNewBillboardsData($oldId): array
        {
            $entityMySQL = 'billboards';
            $cache = $this->getFromCache($entityMySQL, $oldId);

            if ($cache) {
                return $cache;
            } else {
                $valueOld = $this->fetchData($entityMySQL, "id = '{$oldId}'");

                if (isset($valueOld[0])) {
                    $where = "title = '{$valueOld[0]['title']}' AND content = '{$valueOld[0]['content']}'";
                    $valueMysql = $this->fetchData($entityMySQL, $where ?? '', 'mysql');
                    $result = $valueMysql[0] ?? false;

                    if ($result) {
                        $this->saveIntoCache($entityMySQL, $oldId, $result);
                        return $result;
                    }
                }

                return [];
            }
        }

        /**
         * Convert 1 to 'yes' and 0 to 'no'
         * @param string $value
         * @return string
         */
        private function translateYesNo(string $value): string
        {
            return $value == '1' ? 'yes' : 'no';
        }

        /**
         * Just abstract the show of dialog messages
         * @param string $table The entity to be processed
         * @param bool $isEnding Indicate if it is the last message
         */
        private function showMessageBeginningAndEndExecution(string $table, bool $isEnding = false)
        {
            if ($isEnding) {
                $this->message("> {$this->getTime()} - Finalizou a migração da tabela '{$table}'");
            } else {
                $this->message("> {$this->getTime()} - Iniciou a migração da tabela '{$table}'");
            }
        }

        /**
         * Fetch the datas from database according the entity, where sentense and the database used
         * @param string $entity The entity used in query
         * @param string $where The where sentense
         * @param string $use Indicate how the database to be used. The accepted values is 'sqlite' and 'mysql'
         * @return array
         * @throws \Exception
         */
        private function fetchData(string $entity, string $where = '', string $use = 'old'): array
        {
            try {
                /**
                 * @var \PDO
                 */
                $connection = $use === 'old' ? $this->connectMySQLOldDatabase() : $this->connectMySQL();
                $where = $where !== '' ? " WHERE {$where} " : '';
                $sql = "SELECT * FROM {$entity} {$where};";
                return $connection->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\PDOException $ex) {
                throw new \Exception(""
                    . "ERRO!"
                    . PHP_EOL
                    . "Não foi possível limpar a tabela {$entity}"
                    . PHP_EOL
                    . $ex->getMessage()
                    . "" . PHP_EOL);
            }
        }

        /**
         * The method returns the new status based on the old status
         * @return string
         */
        private function requestStatusFromTo($statusOld)
        {
            $status = [
                "ABERTO" => "ELABORADO",
                "APROVADO" => "ENCAMINHADO",
                "PROCESSADO" => "CONFERIDO",
                "EMPENHADO" => "CONFERIDO",
                "SOLICITADO" => "SOLICITADO",
                "RECEBIDO" => "RECEBIDO",
                'NF-ENTREGUE' => 'NF-FINANCAS',
                'NF-FINANCAS' => 'NF-FINANCAS',
                'NF-PAGA' => 'NF-PAGA'
            ];

            return $status[$statusOld];
        }

        /**
         * Migrate the data from table 'om'
         * @throws \Exception
         */
        private function migrateTableOms()
        {
            $table = 'oms';
            $this->showMessageBeginningAndEndExecution($table);
            $this->clearTable($table);
            $oldData = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            foreach ($oldData as $value) {
                $data = [
                    'id' => null,
                    'name' => $value['name'],
                    'naval_indicative' => $value['naval_indicative'],
                    'uasg' => $value['uasg'],
                    'fiscal_agent' => $value['fiscal_agent'] ?? 'FISCAL',
                    'fiscal_agent_graduation' => $value['fiscal_agent_graduation'] ?? 'FISCAL',
                    'munition_manager' => $value['munition_manager'] ?? 'GERENTE',
                    'munition_manager_graduation' => $value['munition_manager_graduation'] ?? 'GERENTE',
                    'munition_fiel' => $value['munition_fiel'] ?? 'FIEL',
                    'munition_fiel_graduation' => $value['munition_fiel_graduation'] ?? 'FIEL',
                    'expense_originator' => 'ORDENADOR',
                    'expense_originator_graduation' => 'ORDENADOR',
                    'ug' => 'UG',
                    'ptres' => 'PTRES',
                    'ai' => 'AI',
                    'do' => 'DO',
                    'bi' => 'BI',
                    'fr' => 'FR',
                    'nd' => 'ND',
                    'cost_center' => 'COST_CENTER',
                    'created_at' => date('Y-m-d', strtotime($value['created_at'])),
                    'updated_at' => date('Y-m-d', strtotime($value['updated_at']))
                ];

                if (!$this->create($data, $table)) {
                    $this->connectMySQL()->rollBack();
                    throw new \Exception("Erro ao inserir " . $value['nome']);
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'users'
         * @throws \Exception
         */
        private function migrateTableUsers()
        {
            $table = 'users';
            $this->showMessageBeginningAndEndExecution($table);
            $this->clearTable($table);
            $oldDate = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            $password = (new \HTR\Helpers\Criptografia\Criptografia())->passHash('administrador' . cfg::STR_SALT);

            foreach ($oldDate as $value) {
                $omData = $this->fetchNewOmsData($value['oms_id']);
                $level = ($value['level'] == 'CONTROLADOR') ? 'CONTROLADOR_OBTENCAO' : $value['level'];
                $data = [
                    'id' => null,
                    'oms_id' => $omData['id'],
                    'name' => $value['name'],
                    'email' => $value['email'],
                    'level' => $level,
                    'username' => $value['username'],
                    'password' => $password,
                    'change_password' => 'yes',
                    'active' => $value['active'],
                    'created_at' => date('Y-m-d', strtotime($value['created_at'])),
                    'updated_at' => date('Y-m-d', strtotime($value['updated_at']))
                ];

                if (!$this->create($data, $table)) {
                    $this->connectMySQL()->rollBack();
                    throw new \Exception("Erro ao inserir " . $value['name']);
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'fornecedor'
         * @throws \Exception
         */
        private function migrateTableSuppliers()
        {
            $table = 'suppliers';
            $this->showMessageBeginningAndEndExecution($table);
            $oldDate = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            foreach ($oldDate as $value) {
                $data = [
                    'id' => null,
                    'name' => $value['name'],
                    'cnpj' => $value['cnpj'],
                    'details' => $value['details'],
                ];

                if (!$this->create($data, $table)) {
                    $this->connectMySQL()->rollBack();
                    throw new \Exception("Erro ao inserir " . $value['nome']);
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'licitacao'
         * @throws \Exception
         */
        private function migrateTableBiddings()
        {
            $table = 'biddings';
            $this->showMessageBeginningAndEndExecution($table);
            $oldDate = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            foreach ($oldDate as $value) {
                $data = [
                    'id' => null,
                    'number' => $value['number'],
                    'uasg' => $value['uasg'],
                    'uasg_name' => $value['uasg_name'],
                    'description' => $value['description'],
                    'validate' => date('Y-m-d', strtotime($value['validate'])),
                    'created_at' => date('Y-m-d', strtotime($value['created_at']))
                ];

                if (!$this->create($data, $table)) {
                    $this->connectMySQL()->rollBack();
                    throw new \Exception("Erro ao inserir " . $value['numero']);
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'licitacao_item'
         * @throws \Exception
         */
        private function migrateTableBiddingsItems()
        {
            $table = 'biddings_items';
            $this->showMessageBeginningAndEndExecution($table);
            $oldDate = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            foreach ($oldDate as $value) {
                $biddingsData = $this->fetchNewBiddingsData($value['biddings_id']);
                $suppliersData = $this->fetchNewSuppliersData($value['suppliers_id']);

                $data = [
                    'id' => null,
                    'biddings_id' => $biddingsData['id'],
                    'suppliers_id' => $suppliersData['id'],
                    'ingredients_id' => 1,
                    'number' => $value['number'],
                    'name' => $value['name'],
                    'uf' => $value['uf'],
                    'quantity' => $value['quantity'],
                    'quantity_compromised' => 0,
                    'quantity_committed' => 0,
                    'quantity_available' => $value['quantity'],
                    'value' => $value['value'],
                    'active' => $value['active']
                ];

                if (!$this->create($data, $table)) {
                    $this->connectMySQL()->rollBack();
                    throw new \Exception("Erro ao inserir " . $value['number']);
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'licitacao'
         * @throws \Exception
         */
        private function migrateTableBiddingsOmsLists()
        {
            $table = 'biddings_oms_lists';
            $this->showMessageBeginningAndEndExecution($table);
            $oldDate = $this->fetchData('biddings', '', 'mysql');
            $this->connectMySQL()->beginTransaction();

            foreach ($oldDate as $value) {
                $omsData = $this->fetchData('oms', '', 'mysql');

                foreach ($omsData as $oms) {
                    $data = [
                        'id' => null,
                        'biddings_id' => $value['id'],
                        'oms_id' => $oms['id'],
                    ];

                    if (!$this->create($data, $table)) {
                        $this->connectMySQL()->rollBack();
                        throw new \Exception("Erro ao inserir {$value['id']}");
                    }
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'solicitacao'
         * @throws \Exception
         */
        private function migrateTableRequests()
        {
            $table = 'requests';
            $this->showMessageBeginningAndEndExecution($table);
            $oldDate = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            foreach ($oldDate as $value) {

                $omsData = $this->fetchNewOmsData($value['oms_id']);
                $suppliersData = $this->fetchNewSuppliersData($value['suppliers_id']);
                $biddingsData = $this->fetchNewBiddingsData($value['biddings_id']);

                if (isset($omsData['id'], $suppliersData['id'])) {
                    $data = [
                        'id' => null,
                        'oms_id' => $omsData['id'],
                        'suppliers_id' => $suppliersData['id'],
                        'biddings_id' => $biddingsData['id'],
                        'number' => $value['number'],
                        'status' => $this->requestStatusFromTo($value['status']),
                        'invoice' => $value['invoice'] ?? 'S/N',
                        'observation' => $value['observation'],
                        'modality' => $value['number'] ? 'Pregão Eletrônico' : 'Dispensa de Licitação Valor',
                        'types_invoices' => 'ESTIMATIVO',
                        'account_plan' => '339030.07',
                        'purposes' => 'Aquisição de gêneros alimentícios para confecção do rancho do ' . $omsData['naval_indicative'],
                        'created_at' => date('Y-m-d', strtotime($value['created_at'])),
                        'updated_at' => date('Y-m-d H:i:s', strtotime($value['updated_at']))
                    ];

                    if (!$this->create($data, $table)) {
                        $this->connectMySQL()->rollBack();
                        throw new \Exception("Erro ao inserir {$value['id']}");
                    }
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'solicitacao_item'
         * @throws \Exception
         */
        private function migrateTableRequestsItems()
        {
            $table = 'requests_items';
            $this->showMessageBeginningAndEndExecution($table);
            $oldDate = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            foreach ($oldDate as $value) {

                $requestsData = $this->fetchNewRequestsData($value['requests_id']);
                if (isset($requestsData['id'])) {

                    $data = [
                        'id' => null,
                        'requests_id' => $requestsData['id'],
                        'number' => $value['number'],
                        'name' => $value['name'],
                        'uf' => $value['uf'],
                        'quantity' => $value['quantity'],
                        'delivered' => $value['delivered'] ?? '0',
                        'value' => $value['value']
                    ];

                    if (!$this->create($data, $table)) {
                        $this->connectMySQL()->rollBack();
                        throw new \Exception("Erro ao inserir {$value['id']}");
                    }
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'avaliacao_fornecedor'
         * @throws \Exception
         */
        private function migrateTableSuppliersEvaluantions()
        {
            $table = 'suppliers_evaluations';
            $this->showMessageBeginningAndEndExecution($table);
            $oldDate = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            foreach ($oldDate as $value) {

                $requestsData = $this->fetchNewRequestsByIdData($value['solicitacao_id']);
                if (isset($requestsData['id'])) {
                    $data = [
                        'id' => null,
                        'requests_id' => $requestsData['id'],
                        'evaluation' => $value['nota'],
                    ];

                    if (!$this->create($data, $table)) {
                        $this->connectMySQL()->rollBack();
                        throw new \Exception("Erro ao inserir {$value['id']}");
                    }
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'quadro_avisos'
         * @throws \Exception
         */
        private function migrateTableBillboards()
        {
            $table = 'billboards';
            $this->showMessageBeginningAndEndExecution($table);
            $oldDate = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            foreach ($oldDate as $value) {

                $data = [
                    'id' => null,
                    'title' => $value['title'],
                    'content' => $value['content'],
                    'beginning_date' => $value['beginning_date'],
                    'ending_date' => $value['ending_date'],
                ];

                if (!$this->create($data, $table)) {
                    $this->connectMySQL()->rollBack();
                    throw new \Exception("Erro ao inserir {$value['id']}");
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'quadro_avisos'
         * @throws \Exception
         */
        private function migrateTableBillboardsOmsLists()
        {
            $table = 'billboards_oms_lists';
            $this->showMessageBeginningAndEndExecution($table);
            $oldDate = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            foreach ($oldDate as $value) {

                $omsData = $this->fetchNewOmsData($value['oms_id']);
                $billboardsData = $this->fetchNewBillboardsData($value['id']);

                if (isset($billboardsData['id'], $omsData['id'])) {
                    $data = [
                        'id' => null,
                        'billboards_id' => $billboardsData['id'],
                        'oms_id' => $omsData['id'],
                    ];

                    if (!$this->create($data, $table)) {
                        $this->connectMySQL()->rollBack();
                        throw new \Exception("Erro ao inserir {$value['id']}");
                    }
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'ingredients'
         * @throws \Exception
         */
        private function migrateTableIngredients()
        {
            $table = 'ingredients';
            $this->showMessageBeginningAndEndExecution($table);
            $oldDate = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            foreach ($oldDate as $value) {

                $data = [
                    'id' => null,
                    'name' => $value['name']
                ];

                if (!$this->create($data, $table)) {
                    $this->connectMySQL()->rollBack();
                    throw new \Exception("Erro ao inserir {$value['id']}");
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'recipes_patterns'
         * @throws \Exception
         */
        private function migrateTableRecipePatterns()
        {
            $table = 'recipes_patterns';
            $this->showMessageBeginningAndEndExecution($table);
            $oldDate = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            foreach ($oldDate as $value) {

                $data = [
                    'id' => null,
                    'name' => $value['name']
                ];

                if (!$this->create($data, $table)) {
                    $this->connectMySQL()->rollBack();
                    throw new \Exception("Erro ao inserir {$value['id']}");
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'recipes_patterns_items'
         * @throws \Exception
         */
        private function migrateTableRecipePatternsItems()
        {
            $table = 'recipes_patterns_items';
            $this->showMessageBeginningAndEndExecution($table);
            $oldDate = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            foreach ($oldDate as $value) {

                $data = [
                    'id' => null,
                    'ingredients_id' => $value['ingredients_id'],
                    'recipes_patterns_id' => $value['recipes_patterns_id'],
                    'quantity' => $value['quantity'],
                ];

                if (!$this->create($data, $table)) {
                    $this->connectMySQL()->rollBack();
                    throw new \Exception("Erro ao inserir {$value['id']}");
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'menus'
         * @throws \Exception
         */
        private function migrateTableMenu()
        {
            $table = 'menus';
            $this->showMessageBeginningAndEndExecution($table);
            $oldDate = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            foreach ($oldDate as $value) {
                $omsData = $this->fetchNewOmsData($value['oms_id']);

                $data = [
                    'id' => null,
                    'oms_id' => $omsData['id'],
                    'users_id_requesters' => $value['users_id_requesters'],
                    'users_id_authorizers' => $value['users_id_authorizers'],
                    'beginning_date' => $value['beginning_date'],
                    'ending_date' => $value['ending_date'],
                    'status' => $value['status'],
                    'raw_menus_object' => $value['raw_menus_object'],
                ];

                if (!$this->create($data, $table)) {
                    $this->connectMySQL()->rollBack();
                    throw new \Exception("Erro ao inserir {$value['id']}");
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'recipes'
         * @throws \Exception
         */
        private function migrateTableRecipes()
        {
            $table = 'recipes';
            $this->showMessageBeginningAndEndExecution($table);
            $oldDate = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            foreach ($oldDate as $value) {

                $data = [
                    'id' => null,
                    'meals_id' => $value['meals_id'],
                    'menus_id' => $value['menus_id'],
                    'recipes_patterns_id' => $value['recipes_patterns_id'],
                    'name' => $value['name'],
                    'quantity_people' => $value['quantity_people'],
                    'date' => $value['date'],
                    'sort' => $value['sort'],
                ];

                if (!$this->create($data, $table)) {
                    $this->connectMySQL()->rollBack();
                    throw new \Exception("Erro ao inserir {$value['id']}");
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * Migrate the data from table 'recipes_items'
         * @throws \Exception
         */
        private function migrateTableRecipesItems()
        {
            $table = 'recipes_items';
            $this->showMessageBeginningAndEndExecution($table);
            $oldDate = $this->fetchData($table);
            $this->connectMySQL()->beginTransaction();

            foreach ($oldDate as $value) {

                $data = [
                    'id' => null,
                    'recipes_id' => $value['recipes_id'],
                    'biddings_items_id' => $value['biddings_items_id'],
                    'name' => $value['name'],
                    'suggested_quantity' => $value['suggested_quantity'],
                    'quantity' => $value['quantity']
                ];

                if (!$this->create($data, $table)) {
                    $this->connectMySQL()->rollBack();
                    throw new \Exception("Erro ao inserir {$value['id']}");
                }
            }

            $this->connectMySQL()->commit();
            $this->showMessageBeginningAndEndExecution($table, true);
        }

        /**
         * @throws \Exception
         */
        private function balanceCompromisedBiddings()
        {
            try {
                $connection = $this->connectMySQL();
                $sql = " SELECT A.number, B.biddings_id, A.name, A.quantity FROM requests_items as A "
                    . " INNER JOIN requests as B ON B.id = A.requests_id "
                    . " WHERE B.status = 'CONFERIDO';";
                $data = $connection->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($data as $value) {
                    $sqlUpdate = " "
                        . " UPDATE biddings_items "
                        . " SET quantity_compromised = {$value['quantity']} "
                        . " WHERE biddings_id = {$value['biddings_id']} "
                        . " and number = {$value['number']};";
                    $connection->exec($sqlUpdate);
                }
            } catch (\PDOException $ex) {
                throw new \Exception(""
                    . "ERRO!"
                    . $sqlUpdate
                    . PHP_EOL
                    . $ex->getMessage()
                    . "" . PHP_EOL);
            }
        }

        /**
         * @throws \Exception
         */
        private function balanceCommittedBiddings()
        {
            try {
                $connection = $this->connectMySQL();
                $sql = " SELECT A.number, B.biddings_id, A.name, A.quantity FROM requests_items as A "
                    . " INNER JOIN requests as B ON B.id = A.requests_id "
                    . " WHERE B.status IN ('EMPENHADO', 'SOLICITADO', 'RECEBIDO', 'NF-ENTREGUE', 'NF-FINANCAS', 'NF-PAGA');";
                $data = $connection->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($data as $value) {
                    $sqlUpdate = " "
                        . " UPDATE biddings_items "
                        . " SET quantity_committed = {$value['quantity']} "
                        . " WHERE biddings_id = {$value['biddings_id']} "
                        . " and number = {$value['number']};";
                    $connection->exec($sqlUpdate);
                }
            } catch (\PDOException $ex) {
                throw new \Exception(""
                    . "ERRO!"
                    . $sqlUpdate
                    . PHP_EOL
                    . $ex->getMessage()
                    . "" . PHP_EOL);
            }
        }

        private function updateAvailableBiddings()
        {
            try {
                $connection = $this->connectMySQL();
                $sql = "UPDATE sisgeneros_mb.biddings_items SET quantity_available = quantity - (quantity_compromised + quantity_committed);";
                $connection->exec($sql);
            } catch (\PDOException $ex) {
                throw new \Exception(""
                    . "ERRO!"
                    . PHP_EOL
                    . $ex->getMessage()
                    . "" . PHP_EOL);
            }
        }
    };
} catch (\Exception $ex) {
    echo $ex->getMessage();
}
