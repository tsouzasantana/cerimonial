<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ChecklistTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'days_offset',
    ];

    protected function casts(): array
    {
        return [
            'days_offset' => 'integer',
        ];
    }

    public function dueDateFor(\DateTimeInterface $eventDate): Carbon
    {
        return Carbon::parse($eventDate)->subDays($this->days_offset);
    }
}
