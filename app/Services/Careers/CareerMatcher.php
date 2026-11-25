<?php

namespace App\Services\Careers;

use App\Models\Career;
use Illuminate\Support\Collection;

/** Mencocokkan sekumpulan skill dengan syarat tiap karier. */
class CareerMatcher
{
    /**
     * @param  array<int,int>  $skillIds
     * @return Collection<int, array{career:Career, score:int, matched:array<int,string>, missing:array<int,string>}>
     */
    public function rank(array $skillIds): Collection
    {
        return Career::listed()->with('skills')->get()->map(function (Career $c) use ($skillIds) {
            [$have, $lack] = $c->skills->partition(fn ($s) => in_array($s->id, $skillIds, true));
            $total = max(1, $c->skills->count());

            return [
                'career' => $c,
                'score' => (int) round($have->count() / $total * 100),
                'matched' => $have->pluck('name')->values()->all(),
                'missing' => $lack->pluck('name')->values()->all(),
            ];
        })->sortByDesc('score')->values();
    }
}
