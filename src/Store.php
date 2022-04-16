<?php
declare(strict_types=1);

namespace SqliteKeyValueStore;

use PDOException;
use PDOStatement;
use PDO;

use Throwable;

use function call_user_func;
use function array_reduce;
use function array_merge;
use function file_exists;
use function preg_split;
use function array_pop;
use function realpath;
use function dirname;
use function implode;
use function is_file;
use function sprintf;
use function fclose;
use function strlen;
use function touch;
use function chmod;
use function flock;
use function fopen;
use function mkdir;
use function copy;
use function trim;

use const PHP_MAXPATHLEN;
use const LOCK_EX;
use const LOCK_UN;

/**
 * Sqlite key-value store
 *
 * A simple key-value storage system powered by sqlite. In-memory sqlite
 * databases are strictly forbidden. If the file does not exist then it is
 * created when the object is constructed.
 */
final class Store
{
    private const SQL_SEARCH = ''
        . 'SELECT * '
        . 'FROM store '
        . 'WHERE key LIKE :key ESCAPE "^"'
        . '    AND value LIKE :value ESCAPE "^"';

    private const SQL_SEARCH_KEY = ''
        . 'SELECT * '
        . 'FROM store '
        . 'WHERE key LIKE :key ESCAPE "^"';

    private const SQL_SEARCH_VALUE = ''
        . 'SELECT * '
        . 'FROM store '
        . 'WHERE value LIKE :value ESCAPE "^"';

    private const SQL_COUNT = ''
        . 'SELECT COUNT(*) AS count '
        . 'FROM store';

    private const SQL_GET_KEY = ''
        . 'SELECT value '
        . 'FROM store '
        . 'WHERE key=:key';

    private const SQL_SET_KEY = ''
        . 'INSERT INTO store (key, value) '
        . '    VALUES(:key, :value) '
        . 'ON CONFLICT(key) '
        . '    DO UPDATE '
        . '        SET value=:value '
        . '            WHERE key=:key';

    private const SQL_DELETE_KEY = ''
        . 'DELETE FROM store '
        . 'WHERE key=:key';

    private const SQL_TABLE_EXISTS = ''
        . 'SELECT name '
        . 'FROM sqlite_master '
        . 'WHERE type="table" '
        . '    AND name="store"';

    private const SQL_TABLE_MAKE = ''
        . 'CREATE TABLE store ( '
        . '    key TEXT PRIMARY KEY, '
        . '   value TEXT '
        . ') WITHOUT ROWID';

    private string $sqlitePath;

    private string $mutexPath;

    private PDO $pdo;

    /**
     * @throws Exception if the sqlite database connection could not be established,
     *     if the sqlite file does not exist and could not be created,
     *     or if the file path is ":memory:".
     */
    public static function make(string $absoluteFilePath, ?string $lockFilePath = null): self
    {
        return new self($absoluteFilePath, $lockFilePath);
    }

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
        $this->sqlitePath = $this->parseAbsolutePath($absoluteFilePath);

        if (!is_file($this->sqlitePath)) {
            if (file_exists($absoluteFilePath)) {
                throw new Exception(sprintf(
                    'Path is not a file: %s',
                    $absoluteFilePath
                ));
            }

            $this->makeEmptyFile($absoluteFilePath);
        }

        if (!file_exists($this->sqlitePath)) {
            $this->makeEmptyFile($absoluteFilePath);
        }

        if (null === $lockFilePath) {
            $this->mutexPath = $this->sqlitePath . '.mutex';
            $this->assertPathLength($this->mutexPath);
        } else {
            $this->mutexPath = $this->parseAbsolutePath($lockFilePath);
        }

        try {
            $this->pdo = new PDO('sqlite:' . $this->sqlitePath, null, null, [
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
        $filePath = $this->parseAbsolutePath($absoluteFilePath);

        if ($this->sqlitePath === $filePath) {
            throw new Exception('Backup file path and store file path cannot match.');
        }

        if (file_exists($filePath)) {
            throw new Exception('Backup file path must be empty.');
        }

        $this->sync(function () use ($filePath): void {
            if (!@copy($this->sqlitePath, $filePath)) {
                throw new Exception(sprintf(
                    'Could not back up store: "%s" => "%s".',
                    $this->sqlitePath,
                    $filePath
                ));
            }
        });

        return $this;
    }

    /**
     * Return the store size.
     *
     * @return int the number of key-value pairs in the collection.
     * @throws Exception if there was a problem interacting with the sqlite database.
     */
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
        $this->sync(function () use ($key, $value): void {
            try {
                $this->execute(self::SQL_SET_KEY, [
                    ':value' => $value,
                    ':key' => $key,
                ]);
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
        $this->sync(function () use ($key): void {
            try {
                $this->execute(self::SQL_DELETE_KEY, [':key' => $key]);
            } catch (PDOException $e) {
                throw new Exception('Store could not be written to.', $e);
            }
        });

        return $this;
    }

    /**
     * Search the keys and values for a specified term.
     *
     * todo: wildcard character validation including restricting it to 1 character.
     *
     * @param string $key the key to search for.
     * @param string $value the value to look for.
     * @param string $wildcard the wildcard character to use in the key and
     *     value. This can appear before the string, after the string, or
     *     anywhere in the middle of the string. It defaults to *.
     * @return array the key-value pairs that matched the search criteria.
     * @throws Exception if there was a problem searching the store.
     */
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

    /**
     * Search the keys for a specified term.
     *
     * todo: wildcard character validation including restricting it to 1 character.
     *
     * @param string $key the key to search for.
     * @param string $wildcard the wildcard character to use in the key.
     *     This can appear before the string, after the string, or
     *     anywhere in the middle of the string. It defaults to *.
     * @return array the key-value pairs that matched the search criteria.
     * @throws Exception if there was a problem searching the store.
     */
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

    /**
     * Search the values for a specified term.
     *
     * todo: wildcard character validation including restricting it to 1 character.
     *
     * @param string $value the value to search for.
     * @param string $wildcard the wildcard character to use in the value.
     *     This can appear before the string, after the string, or
     *     anywhere in the middle of the string. It defaults to *.
     * @return array the key-value pairs that matched the search criteria.
     * @throws Exception if there was a problem searching the store.
     */
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

    /**
     * Returns the path to the mutex file used when writing to the store.
     *
     * @return string
     */
    public function getMutexFilePath(): string
    {
        return $this->mutexPath;
    }

    /**
     * Returns the file path to the sqlite store file.
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->sqlitePath;
    }

    /**
     * Takes a string, and a wildcard character.
     *     Returns a string to be utilised in an SQLite LIKE clause.
     *
     * @param string $str the string to be searched for.
     * @param string $wildcard the wildcard characters.
     * @return string the formatted SQLite search term.
     */
    private function prepareSearch(string $str, string $wildcard): string
    {
        if ($wildcard === '%') {
            return $str;
        }

        return strtr($str, [
            $wildcard => '%',
            '%' => '^%',
        ]);
    }

    /**
     * Formats rows returned by a search query as a key-value array.
     *
     * @param array $rows array of associative arrays indicating query results.
     * @return array the query results as a key-value array.
     */
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
        $this->bindAndExecuteStatement(self::SQL_TABLE_MAKE);
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
                throw new PDOException(sprintf(
                    'Could not bind value to SQL: %s => %s',
                    $key,
                    $sql
                ));
            }
        }

        $statement->execute();

        return $statement;
    }

    /**
     * Runs a callable as a synchronous operation across all processes/threads.
     *
     * @param callable $func the callable to execute.
     * @return mixed the result of the callable.
     * @throws Exception if the mutex could not be obtained/released properly.
     */
    private function sync(callable $func): mixed
    {
        $fp = $this->lock();

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
     * Opens and obtains an exclusive lock on the mutex file.
     *     If the lock file does not yet exist then it will be created.
     *
     * @return resource the mutex file pointer.
     * @throws Exception if the lock file could not be created, opened,
     *     or if an exclusive lock could not be obtained.
     */
    private function lock()
    {
        if (!file_exists($this->mutexPath)) {
            $this->makeEmptyFile($this->mutexPath);
        }

        $fp = @fopen($this->mutexPath, 'r');

        if (false === $fp) {
            throw new Exception('Could not open lock file.');
        }

        if (false === @flock($fp, LOCK_EX)) {
            throw new Exception('Could not obtain exclusive lock on lock file.');
        }

        return $fp;
    }

    /**
     * Unlocks and closes the mutex file.
     *
     * @param resource $fp the file pointer.
     * @throws Exception if the file could not be unlocked or properly closed.
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

    /**
     * Creates an empty file at the specified path.
     *
     * @param string $absoluteFilePath the absolute path to the file to be created.
     * @throws Exception if the file could not be properly created.
     */
    private function makeEmptyFile(string $absoluteFilePath): void
    {
        $directoryPath = dirname($absoluteFilePath);
        @mkdir($directoryPath, 0755, true);

        @touch($absoluteFilePath);

        if (!file_exists($absoluteFilePath)) {
            throw new Exception(sprintf(
                'File could not be created: %s.',
                $absoluteFilePath
            ));
        }

        if (!chmod($absoluteFilePath, 0644)) {
            throw new Exception(sprintf(
                'Could not set file permissions: %s.',
                $absoluteFilePath
            ));
        }
    }

    /**
     * Cleans up a path, standardizing directory separators, removing ".." etc.
     *
     * @param string $path the path to format.
     * @return string the formatted path.
     * @throws Exception if the provided path is invalid or cannot be formatted.
     */
    private function parseAbsolutePath(string $absoluteFilePath): string
    {
        if (':memory:' === trim($absoluteFilePath)) {
            throw new Exception('Sqlite store cannot be in memory.');
        }

        $formatted = @realpath($absoluteFilePath);

        if (false === $formatted) {
            $formatted = $this->formatPath($absoluteFilePath);
        }

        $this->assertPathLength($formatted);

        return $formatted;
    }

    /**
     * Cleans up a path, standardizing directory separators, removing ".." etc.
     *
     * This function should behave as much like the native PHP function realpath()
     * as possible but without checking that the file actually exists. It has been
     * placed into it's own file so that it can be independently tested.
     *
     * @param string $path the path to format.
     * @return string the formatted path.
     * @throws Exception If the provided path is invalid and cannot be formatted.
     */
    private function formatPath(string $path): string
    {
        $segments = preg_split('~[/\\\\]~', $path);

        $segments = array_reduce($segments, function (array $segments, string $segment) use ($path): array {
            switch ($segment) {
                case '.':
                case '':
                    break;

                case '..':
                    if (empty($segments)) {
                        throw new Exception(sprintf('Invalid file path: %s.', $path));
                    }

                    array_pop($segments);

                    break;

                default:
                    $segments[] = $segment;
            }

            return $segments;
        }, []);

        return implode('/', $segments);
    }

    /**
     * Asserts that a file/directory path does not exceed PHP_MAXPATHLEN.
     *
     * @param string $path the file/directory path to check.
     * @throws Exception if the file/directory path exceeds PHP_MAXPATHLEN.
     */
    private function assertPathLength(string $path): void
    {
        if (strlen($path) > PHP_MAXPATHLEN) {
            throw new Exception(sprintf(
                'Path exceeds maximum path length: "%s".',
                $path
            ));
        }
    }
}
