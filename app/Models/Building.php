<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use HasFactory;
    use SoftDeletes;

    const MAX_LENGTH_NAME = 100;

    protected $table = 'buildings';
    protected $fillable = [
        'name',
    ];
    protected $dates = ['deleted_at'];

    public function floors()
    {
        return $this->hasMany(Floor::class);
    }
}
