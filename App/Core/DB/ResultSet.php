<?php

namespace App\Core\DB;

class ResultSet
{
    private array $entities;
    private array $relatedEntities = [];

    /**
     * @param array $entities
     */
    public function __construct(array $entities)
    {
        $this->entities = $entities;
    }

    /**
     * @param string $modelClass Model class to load
     * @param string $fk DB column name in entity used to load referenced model
     * @param callable $fkPropertyAccessor Callback to access model property used to load referenced entity
     * @param callable $idPropertyAccessor Callback to get id of loaded entity
     * @param string $pk Primary key of loaded entity
     * @param mixed $id Id of entity to load
     * @return mixed Loaded entity of $modelClass type
     */
    public function getOneRelated(
        string $modelClass,
        string $fk,
        callable $fkPropertyAccessor,
        callable $idPropertyAccessor,
        string $pk,
        mixed $id
    ): mixed {
        $relationKey = $modelClass . '<|' . $fk;
        if (!isset($this->relatedEntities[$relationKey])) {
            $this->relatedEntities[$relationKey] = [];
            $ids = array_values(array_unique(array_map($fkPropertyAccessor, $this->entities)));
            if (count($ids) > 0) {
                $data = $modelClass::getAll("`$pk` IN ({$this->generatePlaceholders(count($ids))})", $ids);
                foreach ($data as $row) {
                    $this->relatedEntities[$relationKey][$idPropertyAccessor($row)] = $row;
                }
            }
        }

        return $this->relatedEntities[$relationKey][$id] ?? null;
    }

    /**
     * @param string $modelClass Model class to load
     * @param string $fk DB column name referenced entity used to load referencies
     * @param string|null $where Additional parameters for filter loaded entities
     * @param array $whereParams Parameter values
     * @param callable $idPropertyAccessor Callback to load models primary key value
     * @param callable $referencedPropertyAccessor Callback to load referenced property value
     * @param mixed $id Id of entities to load
     * @return array of $modelClass
     */
    public function getAllRelated(
        string $modelClass,
        string $fk,
        ?string $where,
        array $whereParams,
        callable $idPropertyAccessor,
        callable $referencedPropertyAccessor,
        mixed $id
    ): array {
        $relationKey = $modelClass . '|>' . $fk;
        if (!isset($this->relatedEntities[$relationKey])) {
            $this->relatedEntities[$relationKey] = [];
            $ids = array_values(array_unique(array_map($idPropertyAccessor, $this->entities)));
            if (count($ids) > 0) {
                $data = $modelClass::getAll(
                    "`$fk` IN ({$this->generatePlaceholders(count($ids))}) " . ($where != null ? " AND ($where)" : ""),
                    array_merge($ids, $whereParams)
                );
                foreach ($data as $row) {
                    if (!isset($this->relatedEntities[$relationKey][$referencedPropertyAccessor($row)])) {
                        $this->relatedEntities[$relationKey][$referencedPropertyAccessor($row)] = [];
                    }
                    $this->relatedEntities[$relationKey][$referencedPropertyAccessor($row)][] = $row;
                }
            }
        }

        return $this->relatedEntities[$relationKey][$id] ?? [];
    }

    private function generatePlaceholders($count): string
    {
        return implode(", ", array_fill(0, $count, "?"));
    }
}
