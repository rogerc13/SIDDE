<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Floor extends Model
{
    use HasFactory;
    use SoftDeletes;

    const MAX_LENGTH_NAME = 100;

    protected $table = 'floors';
    protected $fillable = [
        'building_id',
        'name',
    ];
    protected $dates = ['deleted_at'];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }
}
