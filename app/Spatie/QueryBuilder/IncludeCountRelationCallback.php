<?php

namespace App\Spatie\QueryBuilder;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Includes\IncludeInterface;

class IncludeCountRelationCallback implements IncludeInterface
{
    public Closure $query;

    /**
     * Build constructor.
     *
     * @param \Closure $query
     */
    public function __construct(Closure $query)
    {
        $this->query = $query;
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
        return $query->withCount([
            $include => $this->query
        ]);
    }
}
