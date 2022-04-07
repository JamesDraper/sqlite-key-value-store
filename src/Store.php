<?php
declare(strict_types=1);

namespace SqliteKeyValueStore;

use PDOException;
use PDOStatement;
use PDO;

use Throwable;

use function array_merge;
use function array_reduce;
use function call_user_func;
use function realpath;
use function basename;
use function dirname;
use function sprintf;
use function fclose;
use function strlen;
use function flock;
use function fopen;
use function mkdir;
use function strtr;
use function touch;
use function copy;
use function trim;

use const LOCK_EX;
use const LOCK_UN;
use const PHP_MAXPATHLEN;

/**
 * Sqlite key-value store
 *
 * A simple key-value storage system powered by sqlite. In-memory sqlite
 * databases are strictly forbidden. If the file does not exist then it is
 * created when the object is constructed.
 */
final class Store
{
    private const SQL_SEARCH = 'SELECT * FROM store WHERE key LIKE :key AND value LIKE :value';

    private const SQL_SEARCH_KEY = 'SELECT * FROM store WHERE key LIKE :key';

    private const SQL_SEARCH_VALUE = 'SELECT * FROM store WHERE value LIKE :value';

    private const SQL_COUNT = 'SELECT COUNT(*) AS count FROM store';

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

    private string $databaseFilePath;

    private string $lockFilePath;

    private PDO $pdo;

    /**
     * @param string $absoluteFilePath the absolute path to the sqlite file.
     *     If it does not exist it will be created.
     *
     * @throws Exception if the sqlite database connection could not be established,
     *     if the sqlite file does not exist and could not be created,
     *     or if the file path is ":memory:".
     */
    public function __construct(string $absoluteFilePath, ?string $lockFilePath = null)
    {
        $this->databaseFilePath = $this->parseAbsoluteFilePath($absoluteFilePath);

        if (null === $lockFilePath) {
            $this->lockFilePath = $this->databaseFilePath . '.lock';
            $this->assertPathLength($this->lockFilePath);
        } else {
            $this->parseAbsoluteFilePath($absoluteFilePath);
        }

        try {
            $this->pdo = new PDO('sqlite:' . $this->databaseFilePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        } catch (PDOException $e) {
            throw new Exception('Sqlite database connection could not be established', $e);
        }
    }

    /**
     * @throws Exception if the backup file path matches the store file path,
     *     if the backup file path was invalid, or if
     *     the backup file path is not empty.
     */
    public function backup(string $absoluteFilePath): self
    {
        $wasCreated = false;
        $filePath = $this->parseAbsoluteFilePath($absoluteFilePath, $wasCreated);

        if ($this->databaseFilePath === $filePath) {
            throw new Exception('Backup file path and store file path cannot match.');
        }

        if (!$wasCreated) {
            throw new Exception('Backup file path must be empty.');
        }

        $this->lock(function () use ($filePath): void {
            if (!@copy($this->databaseFilePath, $filePath)) {
                throw new Exception(sprintf(
                    'Could not back up store: "%s" => "%s".',
                    $this->databaseFilePath,
                    $filePath
                ));
            }
        });

        return $this;
    }

    public function getSize(): int
    {
        try {
            $statement = $this->execute(self::SQL_COUNT);
        } catch (PDOException $e) {
            throw new Exception('Store could not be read from.', $e);
        }

        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $results[0]['count'];
    }

    /**
     * Gets a key from the store, if the key does not exist
     *     then default is returned instead.
     *
     * @param string $key the key to get.
     * @param ?string $default the value to return if the key is not set.
     * @return ?string the value of the default.
     * @throws Exception if there was a problem while getting the key.
     */
    public function get(string $key, ?string $default = null): ?string
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
     * @param string $value the value to set.
     * @return self for method chaining.
     * @throws Exception if there was a problem while setting the key.
     */
    public function set(string $key, string $value): self
    {
        $this->lock(function () use ($key, $value): void {
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
        });

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
        $this->lock(function () use ($key): void {
            try {
                $this->execute(self::SQL_DELETE_KEY, [':key' => $key]);
            } catch (PDOException $e) {
                throw new Exception('Store could not be written to.', $e);
            }
        });

        return $this;
    }

    public function search(string $key, string $value, string $wildcard = '*')
    {
        $key = $this->prepareSearch($key, $wildcard);
        $value = $this->prepareSearch($value, $wildcard);

        try {
            $rows = $this
                ->execute(self::SQL_SEARCH, [':key' => $key, ':value' => $value])
                ->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Store could not be searched.', $e);
        }

        return $this->formatSearch($rows);
    }

    public function searchKey(string $key, string $wildcard = '*'): array
    {
        $key = $this->prepareSearch($key, $wildcard);

        try {
            $rows = $this
                ->execute(self::SQL_SEARCH_KEY, [':key' => $key])
                ->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Store could not be searched.', $e);
        }

        return $this->formatSearch($rows);
    }

    public function searchValue(string $value, string $wildcard = '*'): array
    {
        $value = $this->prepareSearch($value, $wildcard);

        try {
            $rows = $this
                ->execute(self::SQL_SEARCH_VALUE, [':value' => $value])
                ->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Store could not be searched.', $e);
        }

        return $this->formatSearch($rows);
    }

    private function prepareSearch(string $str, $wildcard): string
    {
        if ($wildcard === '%') {
            return $str;
        }

        return strtr($str, [
            $wildcard => '%',
            '%' => '\\%',
        ]);
    }

    private function formatSearch(array $rows): array
    {
        return array_reduce($rows, function (array $rows, array $row): array {
            return array_merge($rows, [$row['key'] => $row['value']]);
        }, []);
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
    private function execute(string $sql, array $params = []): PDOStatement
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

    /**
     * @throws Exception if the file does not exist and could not be created
     *     or if the path is ":memory:".
     */
    private function parseAbsoluteFilePath(string $absoluteFilePath, bool &$wasCreated = false): string
    {
        $absoluteFilePath = trim($absoluteFilePath);

        if (':memory:' === $absoluteFilePath) {
            throw new Exception('Sqlite store cannot be in memory.');
        }

        $formatted = @realpath($absoluteFilePath);

        if (false === $formatted) {
            $wasCreated = true;

            $directoryPath = dirname($absoluteFilePath);
            $fileName = basename($absoluteFilePath);

            @mkdir($directoryPath);
            @touch($directoryPath . '/' . $fileName);

            $formatted = @realpath($absoluteFilePath);

            if (false === $formatted) {
                throw new Exception(sprintf(
                    'Could not create sqlite file: "%s"',
                    $absoluteFilePath
                ));
            }
        }

        $this->assertPathLength($formatted);

        return $formatted;
    }

    private function assertPathLength(string $path): void
    {
        if (strlen($path) > PHP_MAXPATHLEN) {
            throw new Exception('Path exceed maximum path length');
        }
    }

    private function lock(callable $func)
    {
        $fp = @fopen($this->lockFilePath, 'a');

        if (false === $fp) {
            throw new Exception('Could not open obtain lock file.');
        }

        if (false === @flock($fp, LOCK_EX)) {
            throw new Exception('Could not obtain exclusive lock on lock file.');
        }

        try {
            $result = call_user_func($func);
        } catch (Throwable $e) {
            $this->unlock($fp);

            throw $e;
        }

        $this->unlock($fp);

        return $result;
    }

    /**
     * @param resource $fp
     */
    private function unlock($fp): void
    {
        if (false === @flock($fp, LOCK_UN)) {
            throw new Exception('Could not unlock lock file.');
        }

        if (false === @fclose($fp)) {
            throw new Exception('Could not close lock file');
        }
    }
}
