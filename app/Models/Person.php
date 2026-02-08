<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (Person $person) {
            if ($person->isForceDeleting()) {
                $person->facilitator()->withTrashed()->forceDelete();

                return;
            }

            $person->facilitator()->delete();
        });

        static::restoring(function (Person $person) {
            $person->facilitator()->withTrashed()->restore();
        });
    }

    protected $fillable = [
        'name',
        'last_name',
        'id_number',
        'id_type_id',
        'phone',
        'sex',
        'avatar_path',
    ];
    protected $dates = ['deleted_at',];

    public function id_format(){ //returns id_number as 10.000.000
        return number_format($this->id_number, 0, '', '.');
    }

    public function user(){
        return $this->hasOne(User::class);
    }

    public function facilitator(){
        return $this->hasOne(Facilitator::class);
    }

     public function participant(){
        return $this->hasMany(Participant::class);
    }

    public function scheduled(){
        return $this->belongsToMany(Scheduled::class, 'participants');
    }

     /* public function scheduled(){
        return $this->hasManyThrough(Scheduled::class,Participant::class);
    }  */

    public function idType(){
        return $this->belongsTo(IdType::class);
    }
}   
