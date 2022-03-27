# Sqlite key value store

A package for creating self-contained sqlite key-value stores held in a file.

## Usage

A key-value file is managed via the `\SqliteKeyValueStore\Store` class. To
create an instance, pass the absolute path to the key-value file into the
constructor. If the file does not exist yet, the constructor will create it.

    $store = \SqliteKeyValueStore\Store('path/to/file');

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
