<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


class BiseRecord extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'bise_records';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'name' => 'string',
    ];

    // Relationships
    public function sscRecords(): HasMany
    {
        return $this->hasMany(SscRecord::class, 'bise_id', 'id');
    }

    // Scopes
    public function scopeSearchByName(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "%{$search}%");
    }

    // Custom methods
    public function getFormattedNameAttribute(): string
    {
        return ucwords(strtolower($this->name));
    }

    public function getShortNameAttribute(): string
    {
        return substr($this->name, 0, 20) . (strlen($this->name) > 20 ? '...' : '');
    }
}
