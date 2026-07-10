<?php

namespace App\Models\CallCenter;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AgentDailyStat extends Model
{
    protected $table = 'agent_daily_stats';

    protected $fillable = [
        'agent_id', 'stat_date',
        'total_calls', 'outgoing_calls', 'incoming_calls',
        'completed_tasks', 'transferred_tasks',
        'avg_call_duration_seconds', 'success_rate',
    ];

    protected $casts = [
        'stat_date'    => 'date',
        'success_rate' => 'decimal:2',
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id')
            ->withDefault(['name' => 'N/A']);
    }

    /**
     * Recalculate and upsert stat for agent on a given date.
     *
     * FIX: Uses `method` field (outgoing/incoming) instead of `type` (task type).
     * FIX: Cleaned up the compact() that produced wrong keys.
     */
    public static function recalculate(int $agentId, ?string $date = null): void
    {
        $date = $date ?? now()->toDateString();

        $logs = \App\Models\PatientCallLog::where('call_by', $agentId)
            ->whereDate('call_date', $date)->get();

        $tasks = \App\Models\CallCenter\Task::where('agent_id', $agentId)
            ->whereDate('created_at', $date)->get();

        $total       = $logs->count();
        $outgoing    = $logs->where('method', 'outgoing')->count();
        $incoming    = $logs->where('method', 'incoming')->count();
        $completed   = $tasks->where('status', 'completed')->count();
        $transferred = $tasks->where('status', 'transferred')->count();
        $avgDur      = $total ? (int) round($logs->avg('duration') ?? 0) : 0;
        $answered    = $logs->where('receive', 1)->count();
        $rate        = $total ? round(($answered / $total) * 100, 2) : 0;

        static::updateOrCreate(
            ['agent_id' => $agentId, 'stat_date' => $date],
            [
                'total_calls'               => $total,
                'outgoing_calls'            => $outgoing,
                'incoming_calls'            => $incoming,
                'completed_tasks'           => $completed,
                'transferred_tasks'         => $transferred,
                'avg_call_duration_seconds' => $avgDur,
                'success_rate'              => $rate,
            ]
        );
    }
}
