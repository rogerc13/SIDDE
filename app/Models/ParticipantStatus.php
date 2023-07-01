<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipantStatus extends Model
{
    use HasFactory;

    protected $fillabled = ['name'];

    protected $table = 'participant_status';

    public function participant(){
        return $this->hasMany(Participant::class);
    }
}
