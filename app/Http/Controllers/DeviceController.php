<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Devices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DeviceController extends Controller
{
    /**
     * Dipanggil oleh script Python (agent) setiap POLL_INTERVAL detik.
     * POST /api/device/ping
     */
    public function ping(Request $request)
    {
        $data = $request->validate([
            'nama'         => 'required|string',
            'ip_address'   => 'nullable|string',
            'lokasi'       => 'nullable|string',
            'cpu_percent'  => 'nullable|numeric',
            'ram_percent'  => 'nullable|numeric',
            'processes'    => 'nullable|array',
            'windows'      => 'nullable|array',
        ]);

        $flaggedActivity = $this->detectNonWorkActivity(
            $data['processes'] ?? [],
            $data['windows'] ?? []
        );

        $device = Devices::updateOrCreate(
            ['nama' => $data['nama']],
            [
                'ip_address'  => $data['ip_address']  ?? null,
                'lokasi'      => $data['lokasi']      ?? null,
                'cpu_percent' => $data['cpu_percent'] ?? 0,
                'ram_percent' => $data['ram_percent'] ?? 0,
                'processes'   => $data['processes']   ?? [],
                'windows'     => $data['windows']      ?? [],
                'last_seen_at'=> now(),
                'is_flagged'       => $flaggedActivity !== [] ? true : false,
                'flagged_activity' => $flaggedActivity,
            ]
        );

        // Jika dashboard sudah menitipkan command untuk device ini, kirim balik ke agent
        $response = ['status' => 'ok'];

        // Beri tahu agent apakah admin sedang membuka halaman detail (perlu kirim screenshot)
        $response['watch_screen'] = $device->is_watched;

        if ($device->pending_action) {
            $response['action'] = $device->pending_action;

            if ($device->pending_action === 'kill_process') {
                $response['process_name'] = $device->pending_target;
            }

            // Bersihkan pending command supaya tidak dikirim berulang-ulang
            $device->update([
                'pending_action' => null,
                'pending_target' => null,
            ]);
        }

        return response()->json($response);
    }

    /**
     * Menampilkan dashboard monitoring.
     * GET /devices
     */
    public function index()
    {
        $devices = Devices::orderBy('nama')->get();

        return view('devices.index', compact('devices'));
    }

    /**
     * Mengembalikan data devices dalam format JSON (dipakai untuk auto-refresh via fetch/AJAX).
     * GET /devices/data
     */
    public function data()
    {
        $devices = Devices::orderBy('nama')->get()->map(function ($device) {
            return [
                'id'          => $device->id,
                'nama'        => $device->nama,
                'ip_address'  => $device->ip_address,
                'lokasi'      => $device->lokasi,
                'cpu_percent' => $device->cpu_percent,
                'ram_percent' => $device->ram_percent,
                'processes'   => $device->processes,
                'windows'     => $device->windows,
                'status'      => $device->status,
                'is_flagged'      => $device->is_flagged,
                'flagged_activity'=> $device->flagged_activity,
                'last_seen'   => $device->last_seen_at?->diffForHumans(),
            ];
        });

        return response()->json($devices);
    }

    /**
     * Cocokkan nama proses & judul window terhadap daftar kata kunci non-kerja
     * di config/activity_rules.php. Mengembalikan array item yang cocok.
     */
    private function detectNonWorkActivity(array $processes, array $windows): array
    {
        $keywords  = collect(config('activity_rules.non_work_keywords', []))->map(fn ($k) => strtolower($k));
        $whitelist = collect(config('activity_rules.whitelist_keywords', []))->map(fn ($k) => strtolower($k));

        $flagged = [];

        $check = function (string $text, string $source) use ($keywords, $whitelist, &$flagged) {
            $lower = strtolower($text);

            foreach ($whitelist as $safe) {
                if ($safe !== '' && str_contains($lower, $safe)) {
                    return; // dikecualikan lewat whitelist
                }
            }

            foreach ($keywords as $keyword) {
                if ($keyword !== '' && str_contains($lower, $keyword)) {
                    $flagged[] = [
                        'source'          => $source, // 'process' atau 'window'
                        'text'            => $text,
                        'matched_keyword' => $keyword,
                    ];
                    return; // satu kecocokan cukup per item
                }
            }
        };

        foreach ($processes as $p) {
            if (!empty($p['name'])) {
                $check($p['name'], 'process');
            }
        }

        foreach ($windows as $w) {
            if (!empty($w['title'])) {
                $check($w['title'], 'window');
            }
        }

        return $flagged;
    }

    /**
     * Halaman detail satu device: layar monitoring, daftar proses lengkap, daftar window.
     * GET /devices/{device}
     */
    public function show(Devices $device)
    {
        return view('devices.show', compact('device'));
    }

    /**
     * Data JSON untuk satu device (dipakai halaman detail untuk auto-refresh).
     * GET /devices/{device}/data
     */
    public function showData(Devices $device)
    {
        return response()->json([
            'id'             => $device->id,
            'nama'           => $device->nama,
            'ip_address'     => $device->ip_address,
            'lokasi'         => $device->lokasi,
            'cpu_percent'    => $device->cpu_percent,
            'ram_percent'    => $device->ram_percent,
            'processes'      => $device->processes,
            'windows'        => $device->windows,
            'status'         => $device->status,
            'is_flagged'       => $device->is_flagged,
            'flagged_activity' => $device->flagged_activity,
            'last_seen'      => $device->last_seen_at?->diffForHumans(),
            'is_watched'     => $device->is_watched,
            'screenshot_url' => $device->screenshot_url,
        ]);
    }

    /**
     * Dipanggil oleh halaman detail (JS) saat dibuka, dan berulang (heartbeat) selama
     * halaman itu masih terbuka, supaya agent tahu harus mulai/terus kirim screenshot.
     * POST /devices/{device}/watch
     */
    public function watchStart(Devices $device)
    {
        $device->update([
            'screen_watch_until' => now()->addSeconds(20),
        ]);

        return response()->json([
            'status'      => 'ok',
            'watch_until' => $device->screen_watch_until,
        ]);
    }

    /**
     * Endpoint yang dipanggil AGENT PYTHON untuk mengupload screenshot layar.
     * POST /api/device/screenshot
     */
    public function screenshot(Request $request)
    {
        $data = $request->validate([
            'nama'         => 'required|string',
            'image_base64' => 'required|string',
        ]);

        $device = Devices::where('nama', $data['nama'])->first();

        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Device tidak ditemukan'], 404);
        }

        $imageBinary = base64_decode($data['image_base64']);

        Storage::disk('public')->put("screenshots/{$device->nama}.jpg", $imageBinary);

        $device->update(['screenshot_updated_at' => now()]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Menitipkan command (shutdown / restart / kill_process) untuk diambil agent saat ping berikutnya.
     * POST /devices/{device}/command
     */
    public function command(Request $request, Devices $device)
    {
        $validated = $request->validate([
            'action' => 'required|in:shutdown,restart,kill_process',
            'target' => 'nullable|string', // wajib diisi kalau action = kill_process
        ]);

        $device->update([
            'pending_action' => $validated['action'],
            'pending_target' => $validated['target'] ?? null,
        ]);

        return back()->with('success', "Perintah '{$validated['action']}' dikirim ke {$device->nama}, menunggu ping berikutnya...");
    }
}