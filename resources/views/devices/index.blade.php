<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lab Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
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

    <header class="border-b border-line px-6 py-4 flex items-center justify-between sticky top-0 bg-ink/95 backdrop-blur z-10">
        <div>
            <h1 class="text-lg tracking-wide text-neutral-50">LAB MONITORING</h1>
            <p class="text-xs text-neutral-500" id="lastUpdate">memuat data...</p>
        </div>
        <div class="text-xs text-neutral-500">
            <span id="onlineCount">0</span> online &middot;
            <span id="totalCount">0</span> total
        </div>
    </header>

    @if(session('success'))
        <div class="mx-6 mt-4 border border-mint/40 bg-mint/10 text-mint text-sm px-4 py-2 rounded">
            {{ session('success') }}
        </div>
    @endif

    <main class="p-6">
        <div id="deviceGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"></div>
        <p id="emptyState" class="text-neutral-600 text-sm hidden">Belum ada device yang mengirim data.</p>
    </main>

    <!-- Template satu card device -->
    <template id="deviceCardTemplate">
        <div class="device-card border border-line bg-panel rounded-lg p-4 flex flex-col gap-3">
            <div class="flex items-start justify-between">
                <div>
                    <p class="dev-nama text-neutral-50 text-sm tracking-wide"></p>
                    <p class="dev-lokasi text-xs text-neutral-500"></p>
                </div>
                <span class="dev-status text-[10px] px-2 py-0.5 rounded-full border tracking-widest uppercase"></span>
            </div>

            <div class="dev-flag-banner hidden text-[11px] border border-rose/40 bg-rose/10 text-rose rounded px-2 py-1.5"></div>

            <div class="text-xs text-neutral-500 dev-ip"></div>

            <div class="space-y-2">
                <div>
                    <div class="flex justify-between text-[11px] text-neutral-500 mb-1">
                        <span>CPU</span><span class="dev-cpu-label"></span>
                    </div>
                    <div class="h-1.5 bg-line rounded-full overflow-hidden">
                        <div class="dev-cpu-bar h-full bg-mint rounded-full transition-all"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-[11px] text-neutral-500 mb-1">
                        <span>RAM</span><span class="dev-ram-label"></span>
                    </div>
                    <div class="h-1.5 bg-line rounded-full overflow-hidden">
                        <div class="dev-ram-bar h-full bg-amber rounded-full transition-all"></div>
                    </div>
                </div>
            </div>

            <details class="text-xs">
                <summary class="cursor-pointer text-neutral-500 hover:text-neutral-300">Proses aktif</summary>
                <ul class="dev-process-list mt-2 space-y-1 text-neutral-400"></ul>
            </details>

            <details class="text-xs">
                <summary class="cursor-pointer text-neutral-500 hover:text-neutral-300">Window terbuka</summary>
                <ul class="dev-window-list mt-2 space-y-1 text-neutral-400"></ul>
            </details>

            <div class="flex flex-wrap gap-2 pt-2 border-t border-line">
                <a class="dev-detail-link px-2 py-1 text-[11px] border border-line rounded hover:border-neutral-400 hover:text-neutral-100 transition">Detail →</a>
                <form class="cmd-form" data-action="restart">
                    @csrf
                    <button class="px-2 py-1 text-[11px] border border-line rounded hover:border-amber hover:text-amber transition">Restart</button>
                </form>
                <form class="cmd-form" data-action="shutdown">
                    @csrf
                    <button class="px-2 py-1 text-[11px] border border-line rounded hover:border-rose hover:text-rose transition">Shutdown</button>
                </form>
                <form class="cmd-form flex items-center gap-1" data-action="kill_process">
                    @csrf
                    <input type="text" name="target" placeholder="nama proses" class="w-24 bg-ink border border-line rounded px-1.5 py-1 text-[11px] focus:outline-none focus:border-mint">
                    <button class="px-2 py-1 text-[11px] border border-line rounded hover:border-mint hover:text-mint transition">Kill</button>
                </form>
            </div>
        </div>
    </template>

    <script>
        const grid = document.getElementById('deviceGrid');
        const template = document.getElementById('deviceCardTemplate');
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content
            ?? document.querySelector('input[name="_token"]')?.value;

        async function fetchDevices() {
            try {
                const res = await fetch('{{ route('devices.data') }}');
                const devices = await res.json();
                renderDevices(devices);
                document.getElementById('lastUpdate').textContent = 'terakhir diperbarui: ' + new Date().toLocaleTimeString('id-ID');
                document.getElementById('totalCount').textContent = devices.length;
                document.getElementById('onlineCount').textContent = devices.filter(d => d.status === 'online').length;
                document.getElementById('emptyState').classList.toggle('hidden', devices.length > 0);
            } catch (e) {
                console.error('Gagal mengambil data device', e);
            }
        }

        function renderDevices(devices) {
            grid.innerHTML = '';
            devices.forEach(device => {
                const node = template.content.cloneNode(true);
                const card = node.querySelector('.device-card');

                card.querySelector('.dev-detail-link').setAttribute('href', `/devices/${device.id}`);
                card.querySelector('.dev-nama').textContent = device.nama;
                card.querySelector('.dev-lokasi').textContent = device.lokasi ?? '-';
                card.querySelector('.dev-ip').textContent = device.ip_address ?? '-';

                const statusEl = card.querySelector('.dev-status');
                statusEl.textContent = device.status;
                if (device.status === 'online') {
                    statusEl.classList.add('border-mint/40', 'text-mint');
                } else {
                    statusEl.classList.add('border-rose/40', 'text-rose');
                }

                card.querySelector('.dev-cpu-label').textContent = device.cpu_percent + '%';
                card.querySelector('.dev-cpu-bar').style.width = Math.min(device.cpu_percent, 100) + '%';
                card.querySelector('.dev-ram-label').textContent = device.ram_percent + '%';
                card.querySelector('.dev-ram-bar').style.width = Math.min(device.ram_percent, 100) + '%';

                const flagBanner = card.querySelector('.dev-flag-banner');
                if (device.is_flagged && device.flagged_activity?.length) {
                    const items = device.flagged_activity.map(f => f.text).slice(0, 2).join(', ');
                    flagBanner.textContent = `⚠ Aktivitas non-kerja: ${items}`;
                    flagBanner.classList.remove('hidden');
                }

                const procList = card.querySelector('.dev-process-list');
                (device.processes ?? []).forEach(p => {
                    const li = document.createElement('li');
                    li.textContent = `${p.name} — CPU ${p.cpu}% / RAM ${p.memory}%`;
                    procList.appendChild(li);
                });
                if (!device.processes?.length) {
                    procList.innerHTML = '<li class="text-neutral-600">tidak ada data</li>';
                }

                const winList = card.querySelector('.dev-window-list');
                (device.windows ?? []).forEach(w => {
                    const li = document.createElement('li');
                    li.textContent = `[${w.process_name}] ${w.title}`;
                    winList.appendChild(li);
                });
                if (!device.windows?.length) {
                    winList.innerHTML = '<li class="text-neutral-600">tidak ada data</li>';
                }

                card.querySelectorAll('.cmd-form').forEach(form => {
                    form.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        const action = form.dataset.action;
                        const target = form.querySelector('input[name="target"]')?.value ?? null;

                        if (action === 'kill_process' && !target) {
                            alert('Isi nama proses yang ingin dihentikan.');
                            return;
                        }
                        if (!confirm(`Kirim perintah '${action}' ke ${device.nama}?`)) return;

                        await fetch(`/devices/${device.id}/command`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF_TOKEN,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ action, target }),
                        });

                        alert(`Perintah '${action}' terkirim, menunggu ping berikutnya dari agent.`);
                    });
                });

                grid.appendChild(node);
            });
        }

        fetchDevices();
        setInterval(fetchDevices, 5000); // sesuaikan dengan POLL_INTERVAL di agent Python
    </script>
</body>
</html>