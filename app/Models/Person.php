<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
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

    public function id_format(): string
    {
        return number_format((float) $this->id_number, 0, '', '.');
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

    public function scopeWithoutSessionConflict(Builder $query, Scheduled $scheduledCourse): Builder
    {
        return $query->whereNotExists(function ($query) use ($scheduledCourse) {
            $query->selectRaw('1')
                ->from('participants as existing_participants')
                ->join('scheduled_course as existing_scheduled', 'existing_scheduled.id', '=', 'existing_participants.scheduled_id')
                ->join('course_sessions as existing_sessions', 'existing_sessions.scheduled_course_id', '=', 'existing_scheduled.id')
                ->whereColumn('existing_participants.person_id', 'people.id')
                ->where('existing_scheduled.id', '!=', $scheduledCourse->id)
                ->where('existing_participants.participant_status_id', '!=', Participant::CANCELADO)
                ->whereNull('existing_participants.deleted_at')
                ->where('existing_scheduled.course_status_id', '!=', CourseStatus::CANCELADO)
                ->whereNull('existing_scheduled.deleted_at')
                ->where('existing_sessions.status', '!=', 'cancelled')
                ->whereNull('existing_sessions.deleted_at')
                ->whereExists(function ($targetSessions) use ($scheduledCourse) {
                    $targetSessions->selectRaw('1')
                        ->from('course_sessions as target_sessions')
                        ->where('target_sessions.scheduled_course_id', $scheduledCourse->id)
                        ->where('target_sessions.status', '!=', 'cancelled')
                        ->whereNull('target_sessions.deleted_at')
                        ->whereColumn('existing_sessions.session_date', 'target_sessions.session_date')
                        ->whereColumn('existing_sessions.start_time', '<', 'target_sessions.end_time')
                        ->whereColumn('existing_sessions.end_time', '>', 'target_sessions.start_time');
                });
        });
    }
}   
