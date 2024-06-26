<?php

namespace Database\MySQL;

use PDO;
use PDOException;
use Core\MClass\Root;
use Core\MClass\Mob;

class MySQL extends Root
{

    // Propriedades para armazenar detalhes da conexão e instância do PDO.
    private $host;
    private $username;
    private $password;
    private $database;
    private $port;
    private $driver;
    private $pdo;
    private $mob;

    // Construtor da classe, que configura e inicia a conexão automaticamente.
    function __construct()
    {
        $this->configureConnection(CONN); // Supõe-se que a constante CONN seja definida em algum lugar.
        $this->connect();
        $this->mob = new Mob;
    }

    // Método privado para configurar detalhes da conexão com o banco de dados.
    private function configureConnection(array $connectionDetails)
    {
        $this->driver = $connectionDetails['driver'];
        $this->host = $connectionDetails['host'];
        $this->username = $connectionDetails['username'];
        $this->password = $connectionDetails['password'];
        $this->database = $connectionDetails['database'];
        $this->port = $connectionDetails['port'];
    }

    // Método privado para estabelecer a conexão com o banco de dados usando PDO.
    private function connect()
    {
        try {
            $this->pdo = new PDO(
                $this->driver . ':host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->database,
                $this->username,
                $this->password
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->handleConnectionError($e);
        }
    }

    // Método privado para lidar com erros de conexão.
    private function handleConnectionError(PDOException $e)
    {
        //$this->mob->ErrorMini();
        $error = "Não foi possível conectar ao banco de dados <strong>{$this->driver}</strong>.<br><strong>Erro: </strong><span>{$e->getMessage()}</span>";
        require_once ("./core/Php/Error.php"); // Supõe-se que o arquivo error.php esteja no caminho especificado.
        exit();
    }

    /**
     * Executa uma consulta SQL e retorna os resultados em JSON.
     *
     * @param string $sql A consulta SQL a ser executada.
     * @param array $params Parâmetros para a consulta (opcional).
     * @return string|false Os resultados da consulta em JSON ou false em caso de erro.
     */
    public function query($sql, $params = [])
    {
        try {
            // Verifica se a conexão PDO está estabelecida
            if ($this->pdo instanceof PDO) {
                // Prepara a consulta SQL e a executa com os parâmetros fornecidos.
                $statement = $this->pdo->prepare($sql);
                $statement->execute($params);
                // Retorna os resultados da consulta como um array associativo convertido para JSON.
                return json_encode($statement->fetchAll(PDO::FETCH_ASSOC));
            } else {
                // Se a conexão PDO não estiver estabelecida, retorna uma mensagem de erro
                return json_encode(['error' => 'PDO connection error.'], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            // Se ocorrer um erro durante a consulta, pode-se lidar com ele aqui.
            // Exemplo: logar o erro, lançar exceção personalizada, etc.
            return false;
        }
    }


    // Método para inserir dados no banco de dados.
    /**
     * Insere dados em uma tabela e retorna um JSON indicando sucesso ou falha.
     *
     * @param string $table O nome da tabela.
     * @param array $data Os dados a serem inseridos na tabela.
     * @return string JSON indicando sucesso ou falha na inserção.
     */
    public function insert($table, $data)
    {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO $table ($columns) VALUES ($values)";

        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute(array_values($data));

            // Obter o ID da última inserção
            $lastInsertId = $this->pdo->lastInsertId();

            return json_encode(['success' => true, 'id' => $lastInsertId]);
        } catch (PDOException $e) {
            // Lidar com erros de inserção
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }


    // Método para obter todos os registros de uma tabela.
    /**
     * Obtém todos os registros de uma tabela e retorna um JSON.
     *
     * @param string $table O nome da tabela.
     * @return string JSON contendo todos os registros da tabela ou false em caso de erro.
     */
    public function getAll($table)
    {
        $sql = "SELECT * FROM $table";

        try {
            $statement = $this->pdo->query($sql);
            return json_encode($statement->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            // Lidar com erros de consulta
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    // Método para obter um registro por ID de uma tabela.
    /**
     * Obtém um registro por ID de uma tabela e retorna um JSON.
     *
     * @param string $table O nome da tabela.
     * @param int $id O ID do registro a ser obtido.
     * @return string JSON contendo o registro ou false em caso de erro.
     */
    public function getById($table, $tupla, $id)
    {
        $sql = "SELECT * FROM $table WHERE $tupla = ?";

        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$id]);
            return json_encode($statement->fetch(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            // Lidar com erros de consulta
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Atualiza registros em uma tabela com condições dinâmicas e retorna um JSON indicando sucesso ou falha.
     *
     * @param string $table O nome da tabela.
     * @param array $data Os novos dados a serem atualizados na tabela.
     * @param array $conditions Condições para a cláusula WHERE em formato de array associativo.
     * @return string JSON indicando sucesso ou falha na atualização.
     */
    public function update($table, $data, $conditions)
    {
        $setClause = implode('=?, ', array_keys($data)) . '=?';
        $conditionClauses = [];
        $conditionValues = [];

        foreach ($conditions as $key => $value) {
            $conditionClauses[] = "$key = ?";
            $conditionValues[] = $value;
        }

        $conditionClause = implode(' AND ', $conditionClauses);
        $sql = "UPDATE $table SET $setClause WHERE $conditionClause";

        try {
            $values = array_merge(array_values($data), $conditionValues);
            $statement = $this->pdo->prepare($sql);
            $statement->execute($values);
            return json_encode(['success' => true]);
        } catch (PDOException $e) {
            // Lidar com erros de atualização
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }


    // Método para excluir um registro de uma tabela.
    /**
     * Exclui um registro de uma tabela e retorna um JSON indicando sucesso ou falha.
     *
     * @param string $table O nome da tabela.
     * @param int $id O ID do registro a ser excluído.
     * @return string JSON indicando sucesso ou falha na exclusão.
     */
    public function delete($table, $condition = null, $params = [])
    {
        $sql = "DELETE FROM $table";

        if ($condition) {
            $sql .= " WHERE $condition";
        }

        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($params);
            return json_encode(['success' => true]);
        } catch (PDOException $e) {
            // Lidar com erros de exclusão
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

     /**
     * Executa múltiplos comandos SQL de uma string.
     *
     * @param string $sql A string contendo múltiplos comandos SQL separados por ponto e vírgula.
     * @return array Retorna um array contendo o sucesso da operação e mensagens de erro, se houver.
     */
    public function execMulti($sql)
    {
        $queries = explode(';', $sql);
        $errors = [];
        $this->pdo->beginTransaction();

        try {
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $this->pdo->exec($query);
                }
            }
            $this->pdo->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $errors[] = $e->getMessage();
            return ['success' => false, 'errors' => $errors];
        }
    }

}
