<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class Devices extends Model
{
    protected $fillable = [
        'nama',
        'ip_address',
        'lokasi',
        'cpu_percent',
        'ram_percent',
        'processes',
        'windows',
        'is_flagged',
        'flagged_activity',
        'pending_action',
        'pending_target',
        'last_seen_at',
        'screen_watch_until',
        'screenshot_updated_at',
    ];

    protected $casts = [
        'processes'              => 'array',
        'windows'                => 'array',
        'is_flagged'             => 'boolean',
        'flagged_activity'       => 'array',
        'last_seen_at'            => 'datetime',
        'screen_watch_until'      => 'datetime',
        'screenshot_updated_at'   => 'datetime',
    ];

    /**
     * Dianggap online jika ping terakhir kurang dari 15 detik yang lalu.
     * Sesuaikan angka ini dengan POLL_INTERVAL di script Python (default 5 detik).
     */
    public function getStatusAttribute(): string
    {
        if (!$this->last_seen_at) {
            return 'offline';
        }

        return $this->last_seen_at->greaterThan(Carbon::now()->subSeconds(15))
            ? 'online'
            : 'offline';
    }

    /**
     * True selama dashboard sedang membuka halaman detail device ini
     * (agent akan mengirim screenshot ketika ini true).
     */
    public function getIsWatchedAttribute(): bool
    {
        return $this->screen_watch_until && $this->screen_watch_until->isFuture();
    }

    /**
     * URL screenshot terakhir yang diupload agent, null jika belum pernah ada.
     */
    public function getScreenshotUrlAttribute(): ?string
    {
        $path = "screenshots/{$this->nama}.jpg";

        if (!Storage::disk('public')->exists($path)) {
            return null;
        }

        $timestamp = $this->screenshot_updated_at?->timestamp ?? time();

        return Storage::url($path) . '?t=' . $timestamp;
    }
}
