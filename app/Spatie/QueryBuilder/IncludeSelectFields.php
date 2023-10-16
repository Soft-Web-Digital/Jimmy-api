<?php

namespace App\Spatie\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Includes\IncludeInterface;

class IncludeSelectFields implements IncludeInterface
{
    /**
     * Build constructor.
     *
     * @param array<int, string>|null $values
     */
    public function __construct(public ?array $values = null)
    {
        //
    }

    /**
     * Invoke the query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $include
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, string $include)
    {
        $values = $this->values ?? [
            'id',
        ];

        return $query->with([
            $include => fn ($query) => $query->select($values),
        ]);
    }
}
