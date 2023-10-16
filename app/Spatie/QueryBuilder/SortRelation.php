<?php

namespace App\Spatie\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class SortRelation implements Sort
{
    /**
     * Create a new instance.
     *
     * @param string $joinTable
     * @param string $joinColumn
     * @param string $joinSortColumn
     * @param string $relationColumn
     */
    public function __construct(
        public string $joinTable,
        public string $joinColumn,
        public string $joinSortColumn,
        public string $relationColumn
    ) {
    }

    /**
     * Invoke the query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $descending
     * @param string $property
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';

        return $query
            ->join(
                $this->joinTable,
                fn ($join) => $join
                    ->on(
                        ($this->joinTable . '.' . $this->joinColumn),
                        '=',
                        ($query->from . '.' . $this->relationColumn) // @phpstan-ignore-line
                    )
            )
            ->addSelect($this->joinTable . '.' . $this->joinSortColumn . ' as ' . $property)
            ->orderBy($property, $direction);
    }
}
