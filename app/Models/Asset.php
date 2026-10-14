<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = ['category_id', 'name', 'serial_number', 'purchase_date', 'purchase_cost', 'condition', 'location'];

    public function category()
    {
        return $this->belongsTo(AssetCategory::class);
    }
}
