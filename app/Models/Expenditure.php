<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expenditure extends Model
{
    protected $fillable = ['budget_id', 'amount', 'date', 'description', 'paid_to', 'category'];

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }
}
