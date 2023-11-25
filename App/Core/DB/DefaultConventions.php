<?php

namespace App\Core\Db;

use App\Helpers\Inflect;

/**
 * Implementation of default conventions:
 * - column names stay unchanged
 * - table names are pluralized from model names
 * - primary key column names are id
 */
class DefaultConventions implements IDbConvention
{
    /**
     * Returns pluralized name of the DB table
     * This method can be overwritten in a descendant of the class Model
     * @param string $className Class name of a model
     * @return string
     */
    public function getTableName(string $className): string
    {
        $arr = explode("\\", $className);
        return Inflect::pluralize(strtolower(end($arr)));
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
     * Return foreign key name as (classname)Id
     * @param string $className Class name of a model
     * @return string
     */
    public function getFkColumn(string $className)
    {
        $arr = explode("\\", $className);
        return lcfirst(end($arr)) . "Id";
    }

    /**
     * Returns the DB column name, the name is the same as the property name
     * @param string $propertyName Name of a model property
     * @return string
     */
    public function toDbColumnName(string $propertyName): string
    {
        return $propertyName;
    }

    /**
     * Returns the model property name, the name is the same as the DB column name
     * @param string $columnName Name of a DB column name
     * @return string
     */
    public function toPropertyName(string $columnName): string
    {
        return $columnName;
    }
}
