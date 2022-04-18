# Sqlite key value store

A package for creating self-contained sqlite key-value stores held in a file.

## Usage

A key-value file is managed via the `\SqliteKeyValueStore\Store` class. To
create an instance, pass the absolute path to the key-value file into the
constructor. If the file does not exist yet, the constructor will create it.

    $store = \SqliteKeyValueStore\Store('/path/to/file');

There are 2 exceptions that can be thrown by the constructor:
- `\LogicException` if the path is `:memory:`. In memory databases are strictly forbidden.
- `\SqliteKeyValueStore\Exception` if the database connection could not be established.

### Getting a value from the store

Values can be retrieved from the store via the `get` method:

    $value = $store->get('key', 'default value');

If the key exists then it is returned. If it does not then the default value
(second parameter) is returned instead. The default value is `null` if not
specified. `\SqliteKeyValueStore\Exception` if there was an issue
retrieving the key.

### Setting a value to the store

Values can be added/updated to the store via the `set` method:

    $value = $store->set('key', 'value');

If the key exists then it is updated. If it does not then it is created.
`\SqliteKeyValueStore\Exception` if there was an issue setting the key. This
method returns the instance for method chaining.

### Removing a value from the store

Values can be removed from the store via the `remove` method:

    $value = $store->remove('key');

If the key exists, then it is removed. If it does not exist then nothing happens.
`\SqliteKeyValueStore\Exception` if there was an issue deleting the key. This
method returns the instance for method chaining.

## Searching the store

There are 3 ways to search the store:

    // search by keys containing the substring "some_key" where "?" is a wildcard.
    $matches = $store->searchKeys('?some_key?', '?');

    // search by values containing the substring "some_value" where "?" is a wildcard.
    $matches = $store->searchValues('?some_value?', '?');

    // search by keys containing the substring "some_key"
    // AND by values containing the substring "some_value"
    // where "?" is a wildcard.
    $matches = $store->search('?some_key?', '?some_value?', '?');

The wildcard is an optional argument that defaults to `*`. If it is not exactly
1 character then `\SqliteKeyValueStore\Exception` is thrown. Each method returns
a key-value array of pairs that were matched in the store.

## Backing up the store

The store can be backed up by calling `backup` and specifying a path to backup
the database to. The path must be empty or `\SqliteKeyValueStore\Exception` is
thrown. While backing up the database is locked, and all write operations will
block until the backup is complete.

    $store->backup('backup/path');

## Getting the store size

The size of the store can be determined by calling `getSize` which returns the
number of key-value pairs in the store as an integer.

    $store->getSize();