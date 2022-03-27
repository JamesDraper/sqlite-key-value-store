<?php
declare(strict_types=1);

namespace SqliteKeyValueStore;

use PDOException;
use PDOStatement;
use PDO;

use LogicException;

use function file_exists;
use function touch;

/**
 * Sqlite key-value store
 *
 * A simple key-value storage system powered by sqlite. In-memory sqlite
 * databases are strictly forbidden. If the file does not exist then it is
 * created when the object is constructed.
 */
final class Store
{
    private const SQL_GET_KEY = 'SELECT value FROM store WHERE key=:key';

    private const SQL_SET_KEY = 'INSERT INTO '
        . 'store (key, value) VALUES(:key, :value) '
        . 'ON CONFLICT(key) DO UPDATE SET value=:value where key=:key';

    private const SQL_DELETE_KEY = 'DELETE FROM store WHERE key=:key';

    private const SQL_TABLE_EXISTS = 'SELECT name FROM sqlite_master '
        . 'WHERE type="table" AND name="store"';

    private const SQL_MAKE_TABLE = 'CREATE TABLE store '
        . '(key TEXT PRIMARY KEY, value TEXT) '
        . 'WITHOUT ROWID';

    private PDO $pdo;

    /**
     * @param string $filePath the absolute path to the sqlite file.
     *     If it does not exist it will be created.
     *
     * @throws LogicException if $filePath is ":memory:" as this indicates
     *     an in-memory sqlite database, which would not persist between requests.
     * @throws Exception if the sqlite database connection could not be established.
     */
    public function __construct(string $filePath)
    {
        $filePath = trim($filePath);

        if (':memory:' === $filePath) {
            throw new LogicException('Sqlite store cannot be in memory.');
        }

        if (!file_exists($filePath)) {
            touch($filePath);
        }

        try {
            $this->pdo = new PDO('sqlite:' . $filePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        } catch (PDOException $e) {
            throw new Exception('Sqlite database connection could not be established', $e);
        }
    }

    /**
     * Gets a key from the store, if the key does not exist
     *     then default is returned instead.
     *
     * @param string $key the key to get.
     * @param mixed $default the value to return if the key is not set.
     * @return mixed the value of the default.
     * @throws Exception if there was a problem while getting the key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $statement = $this->execute(self::SQL_GET_KEY, [':key' => $key]);
        } catch (PDOException $e) {
            throw new Exception('Store could not be read from.', $e);
        }

        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $results ? $results[0]['value'] : $default;
    }

    /**
     * Writes a key to the store. If the key does not exist then it is created.
     *
     * @param string $key the key to set.
     * @param mixed $value the value to set.
     * @return self for method chaining.
     * @throws Exception if there was a problem while setting the key.
     */
    public function set(string $key, mixed $value): self
    {
        try {
            $this->execute(
                self::SQL_SET_KEY,
                [
                    ':value' => $value,
                    ':key' => $key,
                ]
            );
        } catch (PDOException $e) {
            throw new Exception('Store could not be written to.', $e);
        }

        return $this;
    }

    /**
     * Removes a key from the store. If the key does not exist then nothing happens.
     *
     * @param string $key the key to delete.
     * @return self for method chaining.
     * @throws Exception if there was a problem while deleting the key.
     */
    public function remove(string $key): self
    {
        try {
            $this->execute(self::SQL_DELETE_KEY, [':key' => $key]);
        } catch (PDOException $e) {
            throw new Exception('Store could not be written to.', $e);
        }

        return $this;
    }

    /**
     * Executes an SQL statement. If the database is not initialized
     *     it will try to create it and re-run the statement.
     *
     * @param string $sql The SQL statement including any parameter placeholders.
     * @param array $params A key-value array mapping parameter placeholders
     *     in the SQL statement to values.
     * @return PDOStatement the bound and executed statement.
     * @throws PDOException If the statement could not be executed
     *     or the database could not be initialized.
     */
    private function execute(string $sql, array $params): PDOStatement
    {
        try {
            return $this->bindAndExecuteStatement($sql, $params);
        } catch (PDOException $e) {
            if (!$this->tableExists()) {
                $this->makeTable();
            } else {
                throw $e;
            }
        }

        return $this->bindAndExecuteStatement($sql, $params);
    }

    /**
     * Determines if the store table exists in the sqlite file or not.
     *
     * @return bool true if the store table exists, otherwise false.
     * @throws PDOException if the SQL to read the database could not be run.
     */
    private function tableExists(): bool
    {
        return [] !== $this
                ->bindAndExecuteStatement(self::SQL_TABLE_EXISTS)
                ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Creates the key-value store table in the sqlite file.
     *
     * @throws PDOException if the store table could not be created.
     */
    private function makeTable(): void
    {
        $this->bindAndExecuteStatement(self::SQL_MAKE_TABLE);
    }

    /**
     * Prepares an SQL statement, binds the parameters to it,
     *     executes it, and returns the PDOStatement object.
     *
     * @param string $sql The SQL statement including any parameter placeholders.
     * @param array $params A key-value array mapping parameter placeholders
     *     in the SQL statement to values.
     * @return PDOStatement the bound and executed statement.
     * @throws PDOException If the statement could not be prepared, executed,
     *     or if a parameter could not be bound to it.
     */
    private function bindAndExecuteStatement(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            if (false === $statement->bindValue($key, $value)) {
                throw new PDOException(
                    sprintf('Could not bind value to SQL: %s => %s', $key, $sql)
                );
            }
        }

        $statement->execute();

        return $statement;
    }
}
