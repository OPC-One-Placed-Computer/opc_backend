<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderResourceCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {

        $queryParams = $request->query();

        unset($queryParams['page']);

        $queryString = http_build_query($queryParams);

        $self = url()->current() . "?" . http_build_query(array_merge(['page' => $this->currentPage()], $queryParams));
        $first = url()->current() . "?" . http_build_query(array_merge(['page' => 1], $queryParams));
        $last = url()->current() . "?" . http_build_query(array_merge(['page' => $this->lastPage()], $queryParams));

        return [
            'data' => $this->collection->map(function ($item) {
                return new OrderResource($item);
            }),

            'links' => [
                'self' => $self,
                'first' => $first,
                'last' => $last,
                'prev' => $this->previousPageUrl() ? $this->previousPageUrl() . ($queryString ? '&' . $queryString : '') : null,
                'next' => $this->nextPageUrl() ? $this->nextPageUrl() . ($queryString ? '&' . $queryString : '') : null,
            ],

            'meta' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'path' => url()->current(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
            ],
        ];
    }
}
