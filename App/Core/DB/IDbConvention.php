<?php

namespace App\Core\DB;

/**
 * Interface for DB name conventions implementation
 */
interface IDbConvention
{
    /**
     * Returns name of the DB table
     * This method can be overwritten in a descendant of the class Model
     * @param string $className Class name of the model
     * @return string
     */
    public function getTableName(string $className): string;

    /**
     * Returns the name of the primary key
     * This method can be overwritten in a descendant of the class Model
     * @param string $className Class name of the model
     * @return string
     */
    public function getPkColumnName(string $className): string;

    /**
     * Returns the name of the foreign key for model class
     * @param string $className Class name of the model
     * @return string
     */
    public function getFkColumn(string $className);

    /**
     * Converts model property name to DB column name
     * @param string $propertyName Name of a model property
     * @return string
     */
    public function toDbColumnName(string $propertyName): string;

    /**
     * Converts DB column name to model property name
     * @param string $columnName Name of a DB column name
     * @return string
     */
    public function toPropertyName(string $columnName): string;
}
