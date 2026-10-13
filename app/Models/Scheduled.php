<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;


class Scheduled extends Model
{
  use SoftDeletes, HasFactory;

  protected $table = 'scheduled_course';
  protected $fillable = [
    'course_id', 'course_status_id' , 'facilitator_id', 'start_date', 'end_date',
  ];

  public function course()
  {
    return $this->belongsTo(Course::class);
  }

  public function facilitator()
  {
    return $this->belongsTo(Facilitator::class);
  }

  public function courseStatus()
  {
    return $this->belongsTo(CourseStatus::class);
  }

  public function participants() //directo a la tabla pivote
  {
    return $this->hasMany(Participant::class);
  }

  public function sessions()
  {
    return $this->hasMany(CourseSession::class, 'scheduled_course_id');
  }

  public function persons(){
    return $this->belongsToMany(Person::class,'participants');
  }
    //

 /*  public function participantes(){
    return $this->belongsToMany(User::class,'participante_curso','curso_programado_id','user_id')->withPivot('id');
  } */

  public function atMaxCapacity(): bool
  {
      $participantCount = $this->participants()->count();
      $maxCapacity = $this->course->capacity->first()?->max ?? 0;

      return $participantCount >= $maxCapacity;
  }

  public function totalSessionHours(): float
  {
      return (float) $this->sessions->sum(fn ($s) => $s->durationHours());
  }

  public function remainingHours(): float
  {
      return (float) $this->course->duration - $this->totalSessionHours();
  }

  /**
   * Merge sessions that share weekday, time and aula into a single weekly
   * block, collecting the concrete dates it takes place on.
   *
   * @return array<int, array{weekday: int, start_time: string, end_time: string, location_name: string, dates: array<int, string>}>
   */
  public function weeklySessionGroups(): array
  {
      $this->loadMissing(['sessions.location' => function ($query) {
          $query->withTrashed();
      }]);

      $groups = [];

      foreach ($this->sessions as $session) {
          $weekday = Carbon::parse($session->session_date)->dayOfWeekIso;
          $key = $weekday . '|' . $session->start_time . '|' . $session->end_time . '|' . $session->location_id;

          if (!isset($groups[$key])) {
              $groups[$key] = [
                  'weekday' => $weekday,
                  'start_time' => $session->start_time,
                  'end_time' => $session->end_time,
                  'location_name' => $session->location ? $session->location->name : '',
                  'dates' => [],
              ];
          }

          $dateLabel = Carbon::parse($session->session_date)->format('d-m-Y');
          if (!in_array($dateLabel, $groups[$key]['dates'], true)) {
              $groups[$key]['dates'][] = $dateLabel;
          }
      }

      usort($groups, function ($a, $b) {
          return [$a['weekday'], $a['start_time']] <=> [$b['weekday'], $b['start_time']];
      });

      return array_values($groups);
  }

  /**
   * Lay the weekly session groups out on a fixed time-slot grid (one row per
   * slot, one column per weekday) so it can be rendered as an HTML table.
   *
   * @return array<int, array{hour: ?string, cells: array<int, ?array{rowspan: int, time: string, location_name: string, dates: array<int, string>}>}>
   */
  public function weeklyScheduleGrid(int $startHour = 6, int $endHour = 22, int $stepMinutes = 30): array
  {
      $dayStart = $startHour * 60;
      $slotCount = (int) ((($endHour - $startHour) * 60) / $stepMinutes);

      $cells = array_fill(1, 7, []);
      $covered = array_fill(1, 7, array_fill(0, $slotCount, false));

      foreach ($this->weeklySessionGroups() as $group) {
          $day = (int) $group['weekday'];
          if ($day < 1 || $day > 7) {
              continue;
          }

          $start = Carbon::parse($group['start_time']);
          $end = Carbon::parse($group['end_time']);
          $startMin = max($start->hour * 60 + $start->minute, $dayStart);
          $endMin = min($end->hour * 60 + $end->minute, $endHour * 60);

          if ($endMin <= $startMin) {
              continue;
          }

          $span = max(1, (int) ceil(($endMin - $startMin) / $stepMinutes));
          $row = intdiv($startMin - $dayStart, $stepMinutes);
          $placed = false;

          for (; $row <= $slotCount - $span; $row++) {
              $free = true;
              for ($offset = 0; $offset < $span; $offset++) {
                  if ($covered[$day][$row + $offset]) {
                      $free = false;
                      break;
                  }
              }

              if ($free) {
                  $placed = true;
                  break;
              }
          }

          if (!$placed) {
              continue;
          }

          for ($offset = 0; $offset < $span; $offset++) {
              $covered[$day][$row + $offset] = true;
          }

          $cells[$day][$row] = [
              'rowspan' => $span,
              'time' => $start->format('g:i A') . ' – ' . $end->format('g:i A'),
              'location_name' => $group['location_name'],
              'dates' => $group['dates'],
          ];
      }

      $rows = [];
      $occupiedUntil = array_fill(1, 7, 0);

      for ($row = 0; $row < $slotCount; $row++) {
          $totalMinutes = $dayStart + $row * $stepMinutes;
          $gridRow = [
              'hour' => $totalMinutes % 60 === 0 ? sprintf('%02d:00', intdiv($totalMinutes, 60)) : null,
              'cells' => [],
          ];

          for ($day = 1; $day <= 7; $day++) {
              if ($occupiedUntil[$day] > $row) {
                  continue;
              }

              if (isset($cells[$day][$row])) {
                  $gridRow['cells'][$day] = $cells[$day][$row];
                  $occupiedUntil[$day] = $row + $cells[$day][$row]['rowspan'];
              } else {
                  $gridRow['cells'][$day] = null;
              }
          }

          $rows[] = $gridRow;
      }

      return $rows;
  }
  
  public function isPorDictar(){
        return $this->course_status_id==CourseStatus::POR_DICTAR;
  }
  public function isEnCurso(){
        return $this->course_status_id==CourseStatus::EN_CURSO;
  }
  public function isCulminado(){
    return $this->course_status_id==CourseStatus::CULMINADO;
  }
  public function isCancelado(){
      return $this->course_status_id==CourseStatus::CANCELADO;
  }

  public function hasStarted(): bool
  {
      if ($this->isEnCurso() || $this->isCulminado()) {
          return true;
      }

      return $this->start_date
          && Carbon::parse($this->start_date)->startOfDay()->lessThanOrEqualTo(Carbon::today());
  }

  public function canBeDeleted(): bool
  {
      return ! $this->hasStarted();
  }

  public function badgeStatus(): string
  {
      if ($this->isPorDictar())
          return 'warning';
      elseif ($this->isEnCurso())
          return 'info';
      elseif ($this->isCulminado())
          return 'success';
      elseif ($this->isCancelado())
          return 'danger';

      return 'secondary';
  }


}
