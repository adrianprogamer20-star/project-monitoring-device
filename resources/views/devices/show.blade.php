<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Detail — {{ $device->nama }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace']
                    },
                    colors: {
                        ink: '#0B0F0E',
                        panel: '#121816',
                        line: '#233029',
                        mint: '#3ECF8E',
                        amber: '#E3A008',
                        rose: '#F04438',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-ink text-neutral-200 font-mono min-h-screen">

    <header
        class="border-b border-line px-6 py-4 flex items-center justify-between sticky top-0 bg-ink/95 backdrop-blur z-10">
        <div class="flex items-center gap-3">
            <a href="{{ route('devices.index') }}" class="text-neutral-500 hover:text-neutral-200 text-sm">&larr;
                Kembali</a>
            <div>
                <h1 class="text-lg tracking-wide text-neutral-50">{{ $device->nama }}</h1>
                <p class="text-xs text-neutral-500" id="lastUpdate">memuat data...</p>
            </div>
        </div>
        <span id="statusBadge" class="text-[10px] px-2 py-0.5 rounded-full border tracking-widest uppercase"></span>
    </header>

    <main class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-4">

        <div id="flagBanner"
            class="hidden lg:col-span-3 border border-rose/40 bg-rose/10 text-rose text-xs rounded-lg px-4 py-3">
            <p class="font-semibold mb-1">⚠ Terdeteksi aktivitas di luar kerja</p>
            <ul id="flagList" class="space-y-0.5 text-rose/90"></ul>
        </div>

        <!-- Panel layar monitoring -->
        <section class="lg:col-span-2 border border-line bg-panel rounded-lg p-4 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm text-neutral-300 tracking-wide">LAYAR MONITORING</h2>
                <span id="liveIndicator"
                    class="text-[10px] px-2 py-0.5 rounded-full border border-line text-neutral-500 tracking-widest">MENGHUBUNGKAN...</span>
            </div>

            <div
                class="aspect-video bg-black/40 border border-line rounded overflow-hidden flex items-center justify-center">
                <img id="screenImg" class="hidden w-full h-full object-contain"
                    alt="Screenshot layar {{ $device->nama }}">
                <p id="screenPlaceholder" class="text-neutral-600 text-xs px-4 text-center">
                    Menunggu screenshot pertama dari agent...<br>
                    <span class="text-neutral-700">Muncul setelah agent melakukan ping berikutnya (sesuai POLL_INTERVAL
                        di agent, default 5 detik).</span>
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-2">
                <div>
                    <div class="flex justify-between text-[11px] text-neutral-500 mb-1">
                        <span>CPU</span><span id="cpuLabel">0%</span>
                    </div>
                    <div class="h-1.5 bg-line rounded-full overflow-hidden">
                        <div id="cpuBar" class="h-full bg-mint rounded-full transition-all" style="width:0%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-[11px] text-neutral-500 mb-1">
                        <span>RAM</span><span id="ramLabel">0%</span>
                    </div>
                    <div class="h-1.5 bg-line rounded-full overflow-hidden">
                        <div id="ramBar" class="h-full bg-amber rounded-full transition-all" style="width:0%"></div>
                    </div>
                </div>
            </div>

            <p class="text-[11px] text-neutral-600" id="deviceMeta"></p>
        </section>

        <!-- Panel kontrol -->
        <section class="border border-line bg-panel rounded-lg p-4 flex flex-col gap-3 h-fit">
            <h2 class="text-sm text-neutral-300 tracking-wide">KONTROL</h2>

            @if (session('success'))
                <div class="border border-mint/40 bg-mint/10 text-mint text-xs px-3 py-2 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <form class="cmd-form" data-action="restart">
                @csrf
                <button
                    class="w-full px-3 py-2 text-xs border border-line rounded hover:border-amber hover:text-amber transition">Restart
                    PC</button>
            </form>
            <form class="cmd-form" data-action="shutdown">
                @csrf
                <button
                    class="w-full px-3 py-2 text-xs border border-line rounded hover:border-rose hover:text-rose transition">Shutdown
                    PC</button>
            </form>
            <form class="cmd-form flex gap-2" data-action="kill_process">
                @csrf
                <input type="text" name="target" placeholder="nama proses (misal chrome.exe)"
                    class="flex-1 bg-ink border border-line rounded px-2 py-2 text-xs focus:outline-none focus:border-mint">
                <button
                    class="px-3 py-2 text-xs border border-line rounded hover:border-mint hover:text-mint transition whitespace-nowrap">Kill</button>
            </form>
        </section>

        <!-- Detail aplikasi / proses -->
        <section class="lg:col-span-2 border border-line bg-panel rounded-lg p-4">
            <h2 class="text-sm text-neutral-300 tracking-wide mb-3">APLIKASI & PROSES AKTIF</h2>
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-neutral-500 border-b border-line">
                        <th class="text-left py-1.5 font-normal">Nama</th>
                        <th class="text-left py-1.5 font-normal">PID</th>
                        <th class="text-right py-1.5 font-normal">CPU</th>
                        <th class="text-right py-1.5 font-normal">RAM</th>
                    </tr>
                </thead>
                <tbody id="processTableBody" class="divide-y divide-line/60"></tbody>
            </table>
            <p id="processEmpty" class="text-neutral-600 text-xs py-3 hidden">Tidak ada data proses.</p>
        </section>

        <!-- Detail window terbuka -->
        <section class="border border-line bg-panel rounded-lg p-4">
            <h2 class="text-sm text-neutral-300 tracking-wide mb-3">WINDOW TERBUKA</h2>
            <ul id="windowList" class="space-y-2 text-xs text-neutral-400"></ul>
            <p id="windowEmpty" class="text-neutral-600 text-xs py-3 hidden">Tidak ada window terbuka.</p>
        </section>

    </main>

    <script>
        const DEVICE_ID = {{ $device->id }};
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

        function startWatching() {
            fetch(`/devices/${DEVICE_ID}/watch`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
            }).catch(() => {});
        }

        async function refreshData() {
            try {
                const res = await fetch(`/devices/${DEVICE_ID}/data`);
                const device = await res.json();
                render(device);
                document.getElementById('lastUpdate').textContent = 'terakhir diperbarui: ' + new Date()
                    .toLocaleTimeString('id-ID');
            } catch (e) {
                console.error('Gagal mengambil data device', e);
            }
        }

        function render(device) {
            const statusBadge = document.getElementById('statusBadge');
            statusBadge.textContent = device.status;
            statusBadge.className = 'text-[10px] px-2 py-0.5 rounded-full border tracking-widest uppercase ' +
                (device.status === 'online' ? 'border-mint/40 text-mint' : 'border-rose/40 text-rose');

            document.getElementById('deviceMeta').textContent =
                `${device.ip_address ?? '-'} · ${device.lokasi ?? '-'} · terakhir ping ${device.last_seen ?? '-'}`;

            const flagBanner = document.getElementById('flagBanner');
            const flagList = document.getElementById('flagList');
            if (device.is_flagged && device.flagged_activity?.length) {
                flagList.innerHTML = '';
                device.flagged_activity.forEach(f => {
                    const li = document.createElement('li');
                    li.textContent =
                        `[${f.source === 'process' ? 'Aplikasi' : 'Window'}] ${f.text}`;
                    flagList.appendChild(li);
                });
                flagBanner.classList.remove('hidden');
            } else {
                flagBanner.classList.add('hidden');
            }

            document.getElementById('cpuLabel').textContent = device.cpu_percent + '%';
            document.getElementById('cpuBar').style.width = Math.min(device.cpu_percent, 100) + '%';
            document.getElementById('ramLabel').textContent = device.ram_percent + '%';
            document.getElementById('ramBar').style.width = Math.min(device.ram_percent, 100) + '%';

            const liveIndicator = document.getElementById('liveIndicator');
            const img = document.getElementById('screenImg');
            const placeholder = document.getElementById('screenPlaceholder');

            if (device.is_watched) {
                liveIndicator.textContent = '● LIVE';
                liveIndicator.className =
                    'text-[10px] px-2 py-0.5 rounded-full border border-rose/40 text-rose tracking-widest animate-pulse';
            } else {
                liveIndicator.textContent = 'MENGHUBUNGKAN...';
                liveIndicator.className =
                    'text-[10px] px-2 py-0.5 rounded-full border border-line text-neutral-500 tracking-widest';
            }

            if (device.screenshot_url) {
                img.src = device.screenshot_url; // sudah ada query ?t= dari server untuk cache-busting
                img.classList.remove('hidden');
                placeholder.classList.add('hidden');
            }

            // Tabel proses
            const tbody = document.getElementById('processTableBody');
            tbody.innerHTML = '';
            const processes = device.processes ?? [];
            document.getElementById('processEmpty').classList.toggle('hidden', processes.length > 0);
            processes.forEach(p => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="py-1.5 text-neutral-300">${p.name}</td>
                    <td class="py-1.5 text-neutral-600">${p.pid}</td>
                    <td class="py-1.5 text-right text-neutral-400">${p.cpu}%</td>
                    <td class="py-1.5 text-right text-neutral-400">${p.memory}%</td>
                `;
                tbody.appendChild(tr);
            });

            // List window
            const windowList = document.getElementById('windowList');
            windowList.innerHTML = '';
            const windows = device.windows ?? [];
            document.getElementById('windowEmpty').classList.toggle('hidden', windows.length > 0);
            windows.forEach(w => {
                const li = document.createElement('li');
                li.className = 'border-b border-line/60 pb-2';
                li.innerHTML = `<span class="text-neutral-500">[${w.process_name}]</span> ${w.title}`;
                windowList.appendChild(li);
            });
        }

        document.querySelectorAll('.cmd-form').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const action = form.dataset.action;
                const target = form.querySelector('input[name="target"]')?.value ?? null;

                if (action === 'kill_process' && !target) {
                    alert('Isi nama proses yang ingin dihentikan.');
                    return;
                }
                if (!confirm(`Kirim perintah '${action}' ke device ini?`)) return;

                await fetch(`/devices/${DEVICE_ID}/command`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        action,
                        target
                    }),
                });

                alert(`Perintah '${action}' terkirim, menunggu ping berikutnya dari agent.`);
            });
        });

        // Mulai "watch" begitu halaman dibuka, lalu heartbeat tiap 8 detik supaya
        // agent terus tahu harus mengirim screenshot (watch di server berlaku 20 detik).
        startWatching();
        setInterval(startWatching, 8000);

        // Refresh data (termasuk screenshot terbaru) tiap 3 detik.
        refreshData();
        setInterval(refreshData, 3000);
    </script>
</body>

</html>
