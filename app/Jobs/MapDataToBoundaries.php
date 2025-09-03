<?php

namespace App\Jobs;

use App\Models\Boundary;
use App\Models\MapData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class MapDataToBoundaries implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $columns, public string $tableName)
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
      $points = DB::table($this->tableName)->whereNotNull('location')->get();

      foreach ($points as $point) {
        $boundary = Boundary::where('geometry', 'contains', $point->location)->first();
        $point->boundary_id = $boundary->id;
        $point->save();
      }
    }
}
