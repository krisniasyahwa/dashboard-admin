<?php
namespace App\Traits;
use Carbon\Carbon;

trait FilterByDate{
    public function scopeToday($query, $column = 'created_at'){
        return $query->whereDate($column, Carbon::today());
    }
    public function yesterday($query, $column = 'created_at'){
        return $query->whereDate($column, Carbon::yesterday());
    }

    public function monthToDate($query,  $column = 'created_at'){
        return $query->whereBetween($column, [Carbon::now()->startOfMonth(), Carbon::now()]);
    }

    public function quarterToDate($query, $column = 'created_at'){
        $now = Carbon::now();
        return $query->whereBetween($column, [$now->startOfQuarter(), $now]);
    }

    public function yearToDate($query, $column = 'created_at'){
        $now = Carbon::now();
        return $query->whereBetween($column, $now->startOfYear, $now);
    }

    public function last7Days($query, $column = 'created_at'){
        return $query->whereBetween($column, [Carbon::today()->subDays(7), Carbon::now()]);
    }

    public function last3Days($query, $column = 'created_at'){
        return $query->whereBetween($column, [Carbon::today()->subDays(3), Carbon::now()]);
    }

    public function last30Days($query, $column='created_at'){
        return $query->whereBetween($column, [Carbon::today()->subDays(30), Carbon::now()]);
    }

    public function lastQuarter($query, $column = 'created_at'){
        return $query->whereBetween($column, [Carbon::now()->startOfQuarter()->subMonth(3), Carbon::now()->startOfQuarter()]);
    }

    public function lastYear($query, $column = 'created_at'){
        return $query->whereBetween($column, [Carbon::now()->subYear(), Carbon::now()->subYear()]);
    }

}

?>