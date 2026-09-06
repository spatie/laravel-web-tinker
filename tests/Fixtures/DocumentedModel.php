<?php

namespace Spatie\WebTinker\Tests\Fixtures;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Stands in for a real Eloquent model: columns and relations described only by
 * the docblock, and a short `Collection` that means what this file's `use`
 * statements say it means.
 *
 * @property string $email
 * @property Widget[]|Collection $widgets
 * @property-read int $widget_count
 *
 * @method static self findByEmail(string $email)
 */
class DocumentedModel extends Model
{
    /**
     * @return self
     */
    public static function getById(int $id)
    {
        return new self();
    }

    public static function mystery($id)
    {
        return new self();
    }

    /** Declared as a method and documented as a property, the way a relation is. */
    public function widgets()
    {
        return new Collection();
    }

    /**
     * @return Collection<int, Widget>
     */
    public function gadgets()
    {
        return new Collection();
    }
}
