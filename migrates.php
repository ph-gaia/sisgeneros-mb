<?php
/**
 * This file migrates the data from Sqlite to MySQL database
 */
require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Configurations as cfg;
use App\Config\DatabaseConfig;

ini_set('max_execution_time', 300);

try {

    new class($argv) {

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
                $this->migrateTableRequests();
                $this->migrateTableRequestsItems();
                //$this->migrateTableSuppliersEvaluantions();
                $this->migrateTableBillboards();
                $this->migrateTableBillboardsOmsLists();
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
                            $dns, $config['usuario'], $config['senha'], $config['opcoes']
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
                            $dns, $config['usuario'], $config['senha'], $config['opcoes']
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
                $entitySqlite = 'om';
                $entityMySQL = 'oms';
                $cache = $this->getFromCache($entityMySQL, $oldId);

                if ($cache) {
                    return $cache;
                } else {
                    $valueSqlite = $this->fetchData($entitySqlite, "id = {$oldId}");
                    $where = $valueSqlite[0]['indicativo_naval'] ?? time();
                    $valueMysql = $this->fetchData($entityMySQL, "naval_indicative = '{$where}'", 'mysql');
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
                $entitySqlite = 'licitacao';
                $entityMySQL = 'biddings';
                $cache = $this->getFromCache($entityMySQL, $idLista);

                if ($cache) {
                    return $cache;
                } else {
                    $valueSqlite = $this->fetchData($entitySqlite, "id_lista = {$idLista}");

                    if (isset($valueSqlite[0])) {
                        $where = "uasg = '{$valueSqlite[0]['uasg']}' AND number = '{$valueSqlite[0]['numero']}'";
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
                $entitySqlite = 'fornecedor';
                $entityMySQL = 'suppliers';
                $cache = $this->getFromCache($entityMySQL, $oldId);

                if ($cache) {
                    return $cache;
                } else {
                    $valueSqlite = $this->fetchData($entitySqlite, "id = {$oldId}");

                    if (isset($valueSqlite[0])) {
                        $where = "cnpj = '{$valueSqlite[0]['cnpj']}'";
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
                $entitySqlite = 'solicitacao';
                $entityMySQL = 'requests';
                $cache = $this->getFromCache($entityMySQL, $idLista);

                if ($cache) {
                    return $cache;
                } else {
                    $valueSqlite = $this->fetchData($entitySqlite, "id_lista = '{$idLista}'");

                    if (isset($valueSqlite[0])) {
                        $where = "number = '{$valueSqlite[0]['numero']}'";
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
                $entitySqlite = 'quadro_avisos';
                $entityMySQL = 'billboards';
                $cache = $this->getFromCache($entityMySQL, $oldId);

                if ($cache) {
                    return $cache;
                } else {
                    $valueSqlite = $this->fetchData($entitySqlite, "id = '{$oldId}'");

                    if (isset($valueSqlite[0])) {
                        $where = "title = '{$valueSqlite[0]['titulo']}' AND content = '{$valueSqlite[0]['corpo']}'";
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
                return $value === '1' ? 'yes' : 'no';
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
                        'created_at' => date('Y-m-d', $value['created_at']),
                        'updated_at' => date('Y-m-d', $value['updated_at'])
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

                foreach ($oldDate as $value) {
                    $omData = $this->fetchNewOmsData($value['om_id']);
                    $data = [
                        'id' => null,
                        'oms_id' => $omData['id'],
                        'name' => $value['name'],
                        'email' => $value['email'],
                        'level' => $value['level'],
                        'username' => $value['username'],
                        'password' => $value['password'],
                        'change_password' => $this->translateYesNo($value['change_password']),
                        'active' => $this->translateYesNo($value['active']),
                        'created_at' => date('Y-m-d', $value['created_at']),
                        'updated_at' => date('Y-m-d', $value['updated_at'])
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
                        'validate' => date('Y-m-d', $value['validate']),
                        'created_at' => date('Y-m-d', $value['created_at'])
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
                        'active' => $this->translateYesNo($value['active'])
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

                    if (isset($omsData['id'], $suppliersData['id'])) {
                        $data = [
                            'id' => null,
                            'oms_id' => $omsData['id'],
                            'suppliers_id' => $suppliersData['id'],
                            'biddings_id' => $value['biddings_id'],
                            'number' => $value['number'],
                            'status' => $value['status'],
                            'invoice' => $value['invoice'] ?? 'S/N',
                            'observation' => $value['observation'],
                            'modality' => 'Pregão Eletrônico',
                            'types_invoices' => 'ESTIMATIVO',
                            'account_plan' => '339030.07',
                            'purposes' => 'Aquisição de gêneros alimentícios para confecção do rancho do ' . $omsData['naval_indicative'],
                            'created_at' => date('Y-m-d', $value['created_at']),
                            'updated_at' => date('Y-m-d H:i:s', $value['updated_at'])
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
        };
} catch (\Exception $ex) {
    echo $ex->getMessage();
}
