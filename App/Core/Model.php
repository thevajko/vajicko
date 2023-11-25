<?php

namespace App\Core;

use App\Config\Configuration;
use App\Core\DB\Connection;
use App\Core\DB\ResultSet;
use App\Core\DB\IDbConvention;
use App\Core\Http\Request;
use PDO;
use PDOException;

/**
 * Class Model
 * Abstract class serving as a simple model example, predecessor of all models
 * Allows basic CRUD operations
 * @package App\Core\Storage
 */
abstract class Model implements \JsonSerializable
{
    private static array $dbColumns = [];
    private static IDbConvention $dbConventions;
    private mixed $_dbId = null;
    private ?ResultSet $_resultSet = null;

    /**
     * Returns table name from model class name
     * This method can be overwritten in a descendant of the class Model for custom table name
     * @return string
     */
    protected static function getTableName(): string
    {
        return self::getConventions()->getTableName(get_called_class());
    }

    /**
     * Returns default primary key column name
     * This method can be overwritten in a descendant of the class Model for custom primary key name
     * @return string
     */
    protected static function getPkColumnName(): string
    {
        return self::getConventions()->getPkColumnName(get_called_class());
    }

    /**
     * Returns mapping from model property names to DB column names
     * This method can be overwritten in a descendant of the class Model for custom mapping
     * @return array
     */
    protected static function getColumnsMap(): array
    {
        return [];
    }

    /**
     * Set model property values to values from request, only corresponding names are matched
     * @param Request $request Request
     * @return void
     */
    public function setFromRequest(Request $request): void
    {
        $data = $request->isPost() ? $request->post() : $request->get();
        foreach ($data as $key => $value) {
            if (property_exists(get_class($this), $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Returns an array of models from DB
     * @param string|null $whereClause WHERE clause content
     * @param array $whereParams WHERE parameters
     * @param string|null $orderBy ORDER BY clause content, including ASC or DESC
     * @param int|null $limit LIMIT clause content
     * @param int|null $offset OFFSET clause content
     * @return static[]
     * @throws \Exception Returns exception, if there is a problem with SQL query
     */
    public static function getAll(
        ?string $whereClause = null,
        array $whereParams = [],
        ?string $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        try {
            $sql = "SELECT " . static::getDBColumnNamesList() . " FROM `" . static::getTableName() . "`";
            if ($whereClause != null) {
                $sql .= " WHERE $whereClause";
            }
            if ($orderBy != null) {
                $sql .= " ORDER BY $orderBy";
            }
            if ($limit != null) {
                $sql .= " LIMIT $limit";
            }
            if ($offset != null) {
                $sql .= " OFFSET $offset";
            }

            $stmt = Connection::getInstance()->prepare($sql);
            $stmt->execute($whereParams);
            $models = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, static::class);
            $dataSet = new ResultSet($models);
            /** @var static $model */
            foreach ($models as $model) {
                $model->_dbId = $model->getIdValue();
                $model->_resultSet = $dataSet;
            }
            return $models;
        } catch (PDOException $exception) {
            throw new \Exception('Query failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Returns one model from DB by a primary key
     * @param mixed $id Primary key value
     * @return static|null
     * @throws \Exception
     */
    public static function getOne(mixed $id): ?static
    {
        if ($id == null) {
            return null;
        }

        try {
            $sql = "SELECT " . static::getDBColumnNamesList() . " FROM `" . static::getTableName() . "` WHERE `" .
                static::getPkColumnName() . "`=?";
            $stmt = Connection::getInstance()->prepare($sql);
            $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, static::class);
            $stmt->execute([$id]);
            /** @var static $model * */
            $model = $stmt->fetch() ?: null;
            if ($model != null) {
                $model->_dbId = $model->getIdValue();
                $model->_resultSet = new ResultSet([$model]);
            }
            return $model;
        } catch (PDOException $exception) {
            throw new \Exception('Query failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Returns count of models in DB
     * @param string|null $whereClause WHERE clause content
     * @param array $whereParams WHERE parameters
     * @return int
     * @throws \Exception Returns exception, if there is a problem with SQL query
     */
    public static function getCount(?string $whereClause = null, array $whereParams = []): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM `" . static::getTableName() . "`";
            if ($whereClause != null) {
                $sql .= " WHERE $whereClause";
            }

            $stmt = Connection::getInstance()->prepare($sql);
            $stmt->execute($whereParams);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $exception) {
            throw new \Exception('Query failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Saves the current model to DB. If model property $_dbId is set, update the record, else insert a new record
     * @return void
     * @throws \Exception
     */
    public function save(): void
    {
        try {
            $data = array_fill_keys(static::getDbColumns(), null);
            foreach ($data as $key => &$item) {
                $item = $this->{static::toPropertyName($key)};
            }
            // insert
            if ($this->_dbId == null) {
                $arrColumns = array_map(fn($item) => (':' . $item), array_keys($data));
                $columns = '`' . implode('`,`', array_keys($data)) . "`";
                $params = implode(',', $arrColumns);
                $sql = "INSERT INTO `" . static::getTableName() . "` ($columns) VALUES ($params)";
                $stmt = Connection::getInstance()->prepare($sql);
                $stmt->execute($data);

                $pkPropertyName = static::toPropertyName(static::getPkColumnName());
                if (!isset($this->{$pkPropertyName})) {
                    $this->{$pkPropertyName} = Connection::getInstance()->lastInsertId();
                    $this->_dbId = $this->{$pkPropertyName};
                }
                // update
            } else {
                $arrColumns = array_map(fn($item) => ("`" . $item . '`=:' . $item), array_keys($data));
                $columns = implode(',', $arrColumns);
                $sql = "UPDATE `" . static::getTableName() . "` SET $columns WHERE `" . static::getPkColumnName() .
                    "`=:__pk";
                $stmt = Connection::getInstance()->prepare($sql);
                $data["__pk"] = $this->_dbId;
                $stmt->execute($data);
            }
        } catch (PDOException $exception) {
            throw new \Exception('Query failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Deletes current model from DB
     * @throws \Exception If model not exists, throw an exception
     */
    public function delete(): void
    {
        if ($this->getIdValue() == null) {
            return;
        }
        try {
            $sql = "DELETE FROM `" . static::getTableName() . "` WHERE `" . static::getPkColumnName() . "`=?";
            $stmt = Connection::getInstance()->prepare($sql);
            $stmt->execute([$this->getIdValue()]);
            if ($stmt->rowCount() == 0) {
                throw new \Exception('Model not found!');
            }
        } catch (PDOException $exception) {
            throw new \Exception('Query failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Loads referenced entity of type $modelClass using default property.
     * @param string $modelClass Model to load
     * @param string|null $refColumn Change DB column name used to load referenced property
     * @return mixed
     */
    public function getOneRelated(string $modelClass, ?string $refColumn = null)
    {
        $refColumn ??= static::getConventions()->getFkColumn($modelClass);
        return $this->_resultSet->getOneRelated(
            $modelClass,
            self::toPropertyName($refColumn),
            fn($e) => $e->{self::toPropertyName($refColumn)},
            fn($e) => $e->getIdValue(),
            $modelClass::getPkColumnName(),
            $this->{self::toPropertyName($refColumn)},
        );
    }

    /**
     * Loads entity of type $modelClass witch reference this entity.
     * @param string $modelClass Model to load
     * @param string|null $refColumn Db column name used to reference this entity
     * @param string|null $where Additional conditions to restrict loaded references
     * @param array $whereParams
     * @return mixed
     */
    public function getAllRelated(
        string $modelClass,
        ?string $refColumn = null,
        ?string $where = null,
        array $whereParams = []
    ) {
        $refColumn ??= self::getConventions()->getFkColumn(static::class);
        return $this->_resultSet->getAllRelated(
            $modelClass,
            $refColumn,
            $where,
            $whereParams,
            fn($e) => $e->getIdValue(),
            fn($e) => $e->{self::toPropertyName($refColumn)},
            $this->getIdValue()
        );
    }

    /**
     * Default implementation of JSON serialize method. Returns all properties from model
     * @return array
     */
    public function jsonSerialize(): array
    {
        $properties = get_object_vars($this);
        unset($properties["_dbId"]); //Remove internal object ID
        unset($properties["_resultSet"]); //Remove resultset
        return $properties;
    }

    /**
     * Returns an array of column names from the associated model table
     * @return array
     * @throws \Exception
     */
    private static function getDbColumns(): array
    {
        if (isset(self::$dbColumns[static::class])) {
            return self::$dbColumns[static::class];
        }
        try {
            $sql = "DESCRIBE " . static::getTableName();
            $stmt = Connection::getInstance()->prepare($sql);
            $stmt->execute([]);
            self::$dbColumns[static::class] = array_column($stmt->fetchAll(), 'Field');
            return self::$dbColumns[static::class];
        } catch (PDOException $exception) {
            throw new \Exception('Query failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Returns the value of private key
     * @return mixed
     */
    private function getIdValue(): mixed
    {
        $pk = static::getPkColumnName();
        return $this->{static::toPropertyName($pk)};
    }

    /**
     * Returns the list of DB column names for SELECT clause
     * @return string
     * @throws \Exception
     */
    private static function getDBColumnNamesList(): string
    {
        $dbColumns = [];
        foreach (static::getDbColumns() as $columnName) {
            $name = static::toPropertyName($columnName);
            if ($name != $columnName) {
                $dbColumns[] = "`$columnName` AS {$name}";
            } else {
                $dbColumns[] = $columnName;
            }
        }
        return implode(', ', $dbColumns);
    }

    /**
     * Returns the property name for DB column name considering the user mapping (if applied)
     * @param string $columnName Name of the DB column
     * @return string
     */
    private static function toPropertyName(string $columnName): string
    {
        $customMapping = static::getColumnsMap();
        if (isset($customMapping[$columnName])) {
            return $customMapping[$columnName];
        } else {
            return static::getConventions()->toPropertyName($columnName);
        }
    }

    /**
     * Returns instance of name conventions used in DB
     * @return IDbConvention
     */
    private static function getConventions(): IDbConvention
    {
        if (!isset(static::$dbConventions)) {
            static::$dbConventions = new (Configuration::DB_CONVENTIONS_CLASS)();
        }
        return static::$dbConventions;
    }
}
