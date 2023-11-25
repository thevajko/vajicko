<?php

namespace App\Core\DB;

use App\Helpers\Inflect;

/**
 * Implementation of these conventions:
 * - model property names are camel cased and DB column names are converted to snake case
 * - DB column names are snake cased and model property names are converted to camel case
 * - table name are pluralized from model names and converted to snake case
 * - primary key column names are id
 */
class SnakeConventions implements IDbConvention
{
    /**
     * Returns snake case pluralized name of the DB table
     * This method can be overwritten in a descendant of the class Model
     * @param string $className Class name of a model
     * @return string
     */
    public function getTableName(string $className): string
    {
        $arr = explode("\\", $className);
        $tableName = Inflect::pluralize(strtolower(end($arr)));
        return $this->toSnakeCase($tableName);
    }

    /**
     * Returns the name of primary key, returns 'id'
     * This method can be overwritten in a descendant of the class Model
     * @param string $className Class name of a model
     * @return string
     */
    public function getPkColumnName(string $className): string
    {
        return 'id';
    }

    /**
     * Return foreign key name as (classname in snake case)_id
     * @param string $className Class name of a model
     * @return string
     */
    public function getFkColumn(string $className)
    {
        $arr = explode("\\", $className);
        return strtolower($this->toSnakeCase(end($arr))) . "_id";
    }

    /**
     * Returns the DB column name, the name is converted to snake case
     * @param string $propertyName Name of a model property
     * @return string
     */
    public function toDbColumnName(string $propertyName): string
    {
        return $this->toSnakeCase($propertyName);
    }

    /**
     * Returns the model property name, the name is converted to camel case
     * @param string $columnName Name of a DB column name
     * @return string
     */
    public function toPropertyName(string $columnName): string
    {
        return $this->toCamelCase($columnName);
    }

    /**
     * Converts string from snake case to camel case
     * @param string $input Input string to convert
     * @param string $separator Separator (snake character), usually _
     * @return string
     */
    private function toCamelCase(string $input, string $separator = '_'): string
    {
        return lcfirst(str_replace($separator, '', ucwords($input, $separator)));
    }

    /**
     * Converts string from camel case to snake case
     * @param string $input Input string to convert
     * @return string
     */
    private function toSnakeCase(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
