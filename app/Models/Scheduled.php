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
   * Merge sessions that share weekday and exact start/end time into a single
   * weekly block, collecting every aula and concrete date it takes place on.
   *
   * @return array<int, array{weekday: int, start_time: string, end_time: string, location_name: string, location_names: array<int, string>, dates: array<int, string>}>
   */
  public function weeklySessionGroups(): array
  {
      $this->loadMissing(['sessions.location' => function ($query) {
          $query->withTrashed();
      }]);

      $groups = [];

      foreach ($this->sessions as $session) {
          $weekday = Carbon::parse($session->session_date)->dayOfWeekIso;
          $key = $weekday . '|' . $session->start_time . '|' . $session->end_time;

          if (!isset($groups[$key])) {
              $groups[$key] = [
                  'weekday' => $weekday,
                  'start_time' => $session->start_time,
                  'end_time' => $session->end_time,
                  'location_names' => [],
                  'location_name' => '',
                  'dates' => [],
              ];
          }

          $locationName = $session->location ? $session->location->name : '';
          if ($locationName !== '' && !in_array($locationName, $groups[$key]['location_names'], true)) {
              $groups[$key]['location_names'][] = $locationName;
          }

          $dateLabel = Carbon::parse($session->session_date)->format('d-m-Y');
          if (!in_array($dateLabel, $groups[$key]['dates'], true)) {
              $groups[$key]['dates'][] = $dateLabel;
          }
      }

      foreach ($groups as &$group) {
          $group['location_name'] = implode(', ', $group['location_names']);
      }
      unset($group);

      usort($groups, function ($a, $b) {
          return [$a['weekday'], $a['start_time']] <=> [$b['weekday'], $b['start_time']];
      });

      return array_values($groups);
  }

  /**
   * Arrange the weekly session groups in non-overlapping columns per weekday.
   *
   * @return array<int, array{weekday: int, start_time: string, end_time: string, location_name: string, location_names: array<int, string>, dates: array<int, string>, column: int, columns: int}>
   */
  public function weeklySessionLayout(): array
  {
      $groups = $this->weeklySessionGroups();
      $byDay = [];

      foreach ($groups as $index => $group) {
          $byDay[(int) $group['weekday']][] = $index;
      }

      foreach ($byDay as $indices) {
          usort($indices, function (int $a, int $b) use ($groups): int {
              $byStart = $this->timeToMinutes($groups[$a]['start_time']) <=> $this->timeToMinutes($groups[$b]['start_time']);

              if ($byStart !== 0) {
                  return $byStart;
              }

              return $this->timeToMinutes($groups[$b]['end_time']) <=> $this->timeToMinutes($groups[$a]['end_time']);
          });

          $cluster = [];
          $clusterEnd = -1;

          foreach ($indices as $index) {
              $start = $this->timeToMinutes($groups[$index]['start_time']);

              if ($cluster !== [] && $start >= $clusterEnd) {
                  $this->assignSessionColumns($groups, $cluster);
                  $cluster = [];
                  $clusterEnd = -1;
              }

              $cluster[] = $index;
              $clusterEnd = max($clusterEnd, $this->timeToMinutes($groups[$index]['end_time']));
          }

          if ($cluster !== []) {
              $this->assignSessionColumns($groups, $cluster);
          }
      }

      return $groups;
  }

  /**
   * @param array<int, array<string, mixed>> $groups
   * @param array<int, int> $indices
   */
  private function assignSessionColumns(array &$groups, array $indices): void
  {
      $columnEnds = [];
      $assigned = [];

      foreach ($indices as $index) {
          $start = $this->timeToMinutes($groups[$index]['start_time']);
          $end = $this->timeToMinutes($groups[$index]['end_time']);
          $column = null;

          foreach ($columnEnds as $candidate => $columnEnd) {
              if ($start >= $columnEnd) {
                  $column = $candidate;
                  $columnEnds[$candidate] = $end;
                  break;
              }
          }

          if ($column === null) {
              $column = count($columnEnds);
              $columnEnds[] = $end;
          }

          $assigned[$index] = $column;
      }

      $columns = count($columnEnds);

      foreach ($assigned as $index => $column) {
          $groups[$index]['column'] = $column;
          $groups[$index]['columns'] = $columns;
      }
  }

  private function timeToMinutes(string $time): int
  {
      $parts = explode(':', $time);

      return ((int) $parts[0]) * 60 + (int) ($parts[1] ?? 0);
  }

  /**
   * Lay the weekly session groups on a slot grid split into per-day lanes so
   * overlapping blocks share their horizontal space instead of stacking.
   *
   * @return array{columns: array<int, int>, rows: array<int, array{hour: ?string, cells: array<int, array<int, array{colspan: int, rowspan: int, block: ?array{time: string, location_name: string, dates: array<int, string>}}>>}>}
   */
  public function weeklyScheduleGrid(int $startHour = 6, int $endHour = 22, int $stepMinutes = 30): array
  {
      $dayStart = $startHour * 60;
      $slotCount = (int) ((($endHour - $startHour) * 60) / $stepMinutes);

      $groups = $this->weeklySessionLayout();
      $columns = array_fill(1, 7, 1);
      $byDay = array_fill(1, 7, []);

      foreach ($groups as $group) {
          $day = (int) $group['weekday'];

          if ($day < 1 || $day > 7) {
              continue;
          }

          $byDay[$day][] = $group;
          $columns[$day] = max($columns[$day], (int) $group['columns']);
      }

      $starts = [];
      $covered = [];

      foreach ($byDay as $day => $dayGroups) {
          $lanes = $columns[$day];
          $starts[$day] = [];
          $covered[$day] = array_fill(0, $slotCount, array_fill(0, $lanes, false));

          foreach ($dayGroups as $group) {
              $startMin = max($this->timeToMinutes($group['start_time']), $dayStart);
              $endMin = min($this->timeToMinutes($group['end_time']), $endHour * 60);

              if ($endMin <= $startMin) {
                  continue;
              }

              $row = intdiv($startMin - $dayStart, $stepMinutes);
              $span = min(
                  max(1, (int) ceil(($endMin - $startMin) / $stepMinutes)),
                  $slotCount - $row
              );

              if ($span < 1) {
                  continue;
              }

              $groupColumns = max(1, (int) $group['columns']);
              $colStart = intdiv((int) $group['column'] * $lanes, $groupColumns);
              $colEnd = intdiv(((int) $group['column'] + 1) * $lanes, $groupColumns);
              $colspan = max(1, $colEnd - $colStart);

              if (! $this->isGridAreaFree($covered[$day], $row, $span, $colStart, $colspan)) {
                  continue;
              }

              for ($r = $row; $r < $row + $span; $r++) {
                  for ($c = $colStart; $c < $colStart + $colspan; $c++) {
                      $covered[$day][$r][$c] = true;
                  }
              }

              $starts[$day][$row][$colStart] = [
                  'colspan' => $colspan,
                  'rowspan' => $span,
                  'block' => [
                      'time' => Carbon::parse($group['start_time'])->format('g:i A') . ' – ' . Carbon::parse($group['end_time'])->format('g:i A'),
                      'location_name' => $group['location_name'],
                      'dates' => $group['dates'],
                  ],
              ];
          }
      }

      $rows = [];

      for ($row = 0; $row < $slotCount; $row++) {
          $totalMinutes = $dayStart + $row * $stepMinutes;
          $gridRow = [
              'hour' => $totalMinutes % 60 === 0 ? sprintf('%02d:00', intdiv($totalMinutes, 60)) : null,
              'cells' => [],
          ];

          for ($day = 1; $day <= 7; $day++) {
              $cells = [];
              $cursor = 0;
              $lanes = $columns[$day];

              while ($cursor < $lanes) {
                  if (isset($starts[$day][$row][$cursor])) {
                      $cell = $starts[$day][$row][$cursor];
                      $cells[] = $cell;
                      $cursor += $cell['colspan'];
                      continue;
                  }

                  if ($covered[$day][$row][$cursor]) {
                      $cursor++;
                      continue;
                  }

                  $cells[] = ['colspan' => 1, 'rowspan' => 1, 'block' => null];
                  $cursor++;
              }

              $gridRow['cells'][$day] = $cells;
          }

          $rows[] = $gridRow;
      }

      return ['columns' => $columns, 'rows' => $rows];
  }

  /**
   * @param array<int, array<int, bool>> $covered
   */
  private function isGridAreaFree(array $covered, int $row, int $span, int $colStart, int $colspan): bool
  {
      for ($r = $row; $r < $row + $span; $r++) {
          for ($c = $colStart; $c < $colStart + $colspan; $c++) {
              if ($covered[$r][$c] ?? true) {
                  return false;
              }
          }
      }

      return true;
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
