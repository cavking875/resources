<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resource Planner AI</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=IBM+Plex+Mono:wght@400;500&display=swap');

        :root {
            --bg: #f5f1e8;
            --panel: #fffdf7;
            --ink: #172230;
            --muted: #5a6571;
            --line: #d8d0bf;
            --teal: #0f766e;
            --teal-dark: #115e59;
            --rust: #b45309;
            --sky: #0369a1;
            --ok: #166534;
            --bad: #b91c1c;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Space Grotesk", "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1000px 620px at 95% -20%, #fcd34d 0%, transparent 60%),
                radial-gradient(760px 520px at -15% 110%, #99f6e4 0%, transparent 58%),
                var(--bg);
        }

        .shell {
            width: min(1380px, calc(100vw - 26px));
            margin: 16px auto;
            border: 1px solid var(--line);
            border-radius: 22px;
            overflow: hidden;
            background: var(--panel);
            box-shadow: 0 28px 60px rgba(23, 34, 48, 0.12);
            display: grid;
            grid-template-columns: 300px 1fr;
            min-height: calc(100vh - 32px);
        }

        .side {
            background: linear-gradient(165deg, #0f766e 0%, #0b4d49 62%, #083a37 100%);
            color: #ecfeff;
            padding: 22px;
            display: grid;
            grid-template-rows: auto auto 1fr auto;
            gap: 16px;
        }

        .brand h1 {
            margin: 0;
            font-size: 26px;
            line-height: 1.05;
            letter-spacing: -0.02em;
        }

        .brand p {
            margin: 6px 0 0;
            font-size: 13px;
            color: #c5f7f3;
        }

        .auth {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 14px;
            padding: 12px;
        }

        .auth h2 {
            margin: 0 0 10px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.01em;
            color: #e6fffb;
            text-transform: uppercase;
        }

        .auth input {
            width: 100%;
            margin-bottom: 8px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            background: rgba(1, 34, 33, 0.55);
            color: #f0fdfa;
            border-radius: 9px;
            padding: 10px;
            font: inherit;
        }

        .auth input::placeholder { color: #b7dbd7; }

        .row { display: flex; gap: 8px; flex-wrap: wrap; }

        .btn {
            border: 0;
            border-radius: 9px;
            font: inherit;
            font-weight: 600;
            letter-spacing: 0.01em;
            padding: 10px 12px;
            cursor: pointer;
            transition: transform .14s ease, opacity .14s ease;
        }

        .btn:disabled { opacity: .65; cursor: default; }
        .btn:hover:not(:disabled) { transform: translateY(-1px); }

        .btn.primary { color: white; background: var(--rust); }
        .btn.ghost { color: #d9fffb; background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.22); }
        .btn.api { color: #f8fafc; background: var(--sky); }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            background: rgba(255, 255, 255, 0.08);
            color: #dbfffb;
            padding: 5px 10px;
            font-size: 12px;
            margin-top: 6px;
        }

        .pill.bad { color: #ffe2e2; border-color: rgba(248, 113, 113, 0.55); background: rgba(127, 29, 29, 0.33); }

        .quick {
            display: grid;
            gap: 8px;
            align-content: start;
        }

        .quick .btn { width: 100%; text-align: left; }

        .hint {
            font-size: 12px;
            color: #b7dbd7;
            line-height: 1.45;
        }

        .main {
            padding: 20px;
            display: grid;
            gap: 14px;
            align-content: start;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .top h2 {
            margin: 0;
            font-size: clamp(24px, 2.6vw, 34px);
            letter-spacing: -0.02em;
        }

        .top p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 12px;
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 14px;
            background: white;
            padding: 14px;
            box-shadow: 0 8px 18px rgba(23, 34, 48, 0.06);
        }

        .span-3 { grid-column: span 3; }
        .span-4 { grid-column: span 4; }
        .span-5 { grid-column: span 5; }
        .span-6 { grid-column: span 6; }
        .span-7 { grid-column: span 7; }
        .span-8 { grid-column: span 8; }
        .span-12 { grid-column: span 12; }

        .metric .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--muted);
        }

        .metric .value {
            margin-top: 8px;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .metric .foot {
            margin-top: 6px;
            font-size: 13px;
            color: var(--muted);
        }

        h3 {
            margin: 0;
            font-size: 16px;
            letter-spacing: -0.01em;
        }

        .sub {
            margin: 6px 0 12px;
            color: var(--muted);
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th, td {
            text-align: left;
            border-bottom: 1px solid #ebe5d6;
            padding: 8px 6px;
            vertical-align: top;
        }

        th {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 11px;
            font-weight: 700;
        }

        .mono {
            margin: 0;
            max-height: 300px;
            overflow: auto;
            padding: 10px;
            border-radius: 10px;
            background: #111827;
            color: #e5e7eb;
            font-family: "IBM Plex Mono", Consolas, monospace;
            font-size: 12px;
            line-height: 1.45;
        }

        .forecast-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .forecast-grid input,
        .forecast-grid select {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 9px 10px;
            font: inherit;
        }

        .toast {
            display: none;
            margin-top: 10px;
            border-radius: 10px;
            padding: 9px 11px;
            font-size: 13px;
            border: 1px solid transparent;
        }

        .toast.ok { display: block; color: var(--ok); background: #ecfdf5; border-color: #86efac; }
        .toast.bad { display: block; color: var(--bad); background: #fef2f2; border-color: #fca5a5; }

        @keyframes reveal {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card, .top, .brand, .auth, .quick { animation: reveal .35s ease both; }

        @media (max-width: 1080px) {
            .shell { grid-template-columns: 1fr; }
            .side { grid-template-rows: auto auto auto auto; }
            .span-3, .span-4, .span-5, .span-6, .span-7, .span-8 { grid-column: span 12; }
        }
    </style>
</head>
<body>
<div class="shell">
    <aside class="side">
        <section class="brand">
            <h1>Resource Planner AI</h1>
            <p>Planning command center for workforce demand, staffing pressure, and scenario trade-offs.</p>
        </section>

        <section class="auth">
            <h2>Session</h2>
            <input id="email" value="admin@resourceplanner.local" placeholder="Email">
            <input id="password" type="password" value="password" placeholder="Password">
            <div class="row">
                <button class="btn primary" id="loginBtn">Login</button>
                <button class="btn ghost" id="logoutBtn">Logout</button>
            </div>
            <div id="sessionPill" class="pill bad">Not authenticated</div>
            <div id="sessionToast" class="toast"></div>
        </section>

        <section class="quick">
            <button class="btn api" data-call="/api/health">Check API Health</button>
            <button class="btn api" data-call="/api/projects">Refresh Projects</button>
            <button class="btn api" data-call="/api/staff">Refresh Staff</button>
            <button class="btn api" data-call="/api/scenarios">Refresh Scenarios</button>
            <button class="btn api" data-call="/api/dashboards/role-gap?month=2027-03">Role Gap (Mar 2027)</button>
            <p class="hint">This app is live-wired to your backend. Use the quick actions to inspect real payloads and operational state.</p>
        </section>

        <section class="hint">
            Endpoint root:
            <strong>/api</strong>
            <br>
            Working health probe:
            <strong>/api/health</strong>
        </section>
    </aside>

    <main class="main">
        <header class="top">
            <div>
                <h2>Operations Dashboard</h2>
                <p id="meta">No request executed yet.</p>
            </div>
            <button class="btn primary" id="refreshAllBtn">Refresh All</button>
        </header>

        <section class="grid">
            <article class="card metric span-3">
                <div class="label">Projects</div>
                <div id="kpiProjects" class="value">0</div>
                <div class="foot">active portfolio rows</div>
            </article>

            <article class="card metric span-3">
                <div class="label">Staff</div>
                <div id="kpiStaff" class="value">0</div>
                <div class="foot">available records</div>
            </article>

            <article class="card metric span-3">
                <div class="label">Scenarios</div>
                <div id="kpiScenarios" class="value">0</div>
                <div class="foot">planning scenarios</div>
            </article>

            <article class="card metric span-3">
                <div class="label">Role Gap Warnings</div>
                <div id="kpiWarnings" class="value">0</div>
                <div class="foot">detected in selected month</div>
            </article>

            <article class="card span-7">
                <h3>Projects Pipeline</h3>
                <p class="sub">Top portfolio records from the API.</p>
                <div style="overflow:auto;">
                    <table>
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Project</th>
                            <th>Region</th>
                            <th>Stage</th>
                            <th>Value</th>
                        </tr>
                        </thead>
                        <tbody id="projectsBody">
                        <tr><td colspan="5">No data loaded.</td></tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="card span-5">
                <h3>Staff Availability</h3>
                <p class="sub">Current staff rows and role coverage.</p>
                <div style="overflow:auto; max-height: 280px;">
                    <table>
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Region</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody id="staffBody">
                        <tr><td colspan="4">No data loaded.</td></tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="card span-6">
                <h3>Forecast Playground</h3>
                <p class="sub">Run a demand forecast from UI inputs.</p>
                <div class="forecast-grid">
                    <input id="projectValue" type="number" value="6000000" placeholder="Project Value">
                    <input id="startDate" type="date" value="2026-09-01">
                    <input id="endDate" type="date" value="2028-03-31">
                    <select id="complexity">
                        <option value="high" selected>high</option>
                        <option value="medium">medium</option>
                        <option value="low">low</option>
                    </select>
                    <select id="planningIntensity">
                        <option value="high">high</option>
                        <option value="medium" selected>medium</option>
                        <option value="low">low</option>
                    </select>
                    <select id="commercialIntensity">
                        <option value="high" selected>high</option>
                        <option value="medium">medium</option>
                        <option value="low">low</option>
                    </select>
                </div>
                <div class="row" style="margin-top: 10px;">
                    <button class="btn primary" id="runForecastBtn">Run Forecast</button>
                </div>
                <pre id="forecastOutput" class="mono" style="margin-top: 10px;">{}</pre>
            </article>

            <article class="card span-6">
                <h3>API Event Stream</h3>
                <p class="sub">Raw payloads for observability and troubleshooting.</p>
                <pre id="streamOutput" class="mono">{}</pre>
            </article>

            <article class="card span-12">
                <h3>Scenarios</h3>
                <p class="sub">Scenario planning snapshots.</p>
                <div style="overflow:auto;">
                    <table>
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Base Case</th>
                            <th>Created At</th>
                        </tr>
                        </thead>
                        <tbody id="scenariosBody">
                        <tr><td colspan="4">No data loaded.</td></tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </main>
</div>

<script>
(() => {
    let token = null;

    const $ = (id) => document.getElementById(id);
    const ui = {
        email: $('email'),
        password: $('password'),
        sessionPill: $('sessionPill'),
        sessionToast: $('sessionToast'),
        meta: $('meta'),
        streamOutput: $('streamOutput'),
        forecastOutput: $('forecastOutput'),
        projectsBody: $('projectsBody'),
        staffBody: $('staffBody'),
        scenariosBody: $('scenariosBody'),
        kpiProjects: $('kpiProjects'),
        kpiStaff: $('kpiStaff'),
        kpiScenarios: $('kpiScenarios'),
        kpiWarnings: $('kpiWarnings'),
    };

    const setToast = (msg, ok) => {
        ui.sessionToast.textContent = msg;
        ui.sessionToast.className = 'toast ' + (ok ? 'ok' : 'bad');
    };

    const setSession = (ok, label) => {
        ui.sessionPill.textContent = label;
        ui.sessionPill.className = 'pill' + (ok ? '' : ' bad');
    };

    const authHeaders = () => token ? { Authorization: 'Bearer ' + token } : {};

    const parseRows = (payload) => {
        if (Array.isArray(payload)) return payload;
        if (payload && Array.isArray(payload.data)) return payload.data;
        if (payload && payload.data && Array.isArray(payload.data.items)) return payload.data.items;
        if (payload && Array.isArray(payload.items)) return payload.items;
        return [];
    };

    const safe = (v) => (v === null || v === undefined || v === '') ? '-' : String(v);

    async function callApi(path, method = 'GET', body = null) {
        const started = performance.now();
        const opts = {
            method,
            headers: {
                'Content-Type': 'application/json',
                ...authHeaders(),
            },
        };
        if (body) opts.body = JSON.stringify(body);

        const res = await fetch(path, opts);
        const text = await res.text();
        let json;
        try { json = JSON.parse(text); } catch (_) { json = text; }

        const ms = Math.round(performance.now() - started);
        ui.meta.textContent = method + ' ' + path + ' • ' + res.status + ' • ' + ms + 'ms';
        ui.streamOutput.textContent = typeof json === 'string' ? json : JSON.stringify(json, null, 2);

        return { ok: res.ok, status: res.status, data: json };
    }

    async function login() {
        try {
            const out = await callApi('/api/auth/login', 'POST', {
                email: ui.email.value.trim(),
                password: ui.password.value,
            });

            if (!out.ok || !out.data?.data?.token) {
                throw new Error(out.data?.error || 'Login failed.');
            }

            token = out.data.data.token;
            setSession(true, 'Authenticated');
            setToast('Connected as ' + safe(out.data.data.user?.email), true);
            await refreshAll();
        } catch (err) {
            setSession(false, 'Auth failed');
            setToast(err.message || 'Authentication failed', false);
        }
    }

    async function logout() {
        if (token) {
            await callApi('/api/auth/logout', 'POST');
        }
        token = null;
        setSession(false, 'Not authenticated');
        setToast('Session revoked.', true);
    }

    async function loadProjects() {
        const out = await callApi('/api/projects');
        const rows = parseRows(out.data).slice(0, 12);
        ui.kpiProjects.textContent = String(rows.length);
        if (!rows.length) {
            ui.projectsBody.innerHTML = '<tr><td colspan="5">No projects returned.</td></tr>';
            return;
        }

        ui.projectsBody.innerHTML = rows.map((r) => `
            <tr>
                <td>${safe(r.id ?? r.project_id)}</td>
                <td>${safe(r.project_name ?? r.name)}</td>
                <td>${safe(r.region)}</td>
                <td>${safe(r.project_stage ?? r.stage)}</td>
                <td>${safe(r.project_value ?? r.value)}</td>
            </tr>
        `).join('');
    }

    async function loadStaff() {
        const out = await callApi('/api/staff');
        const rows = parseRows(out.data).slice(0, 12);
        ui.kpiStaff.textContent = String(rows.length);
        if (!rows.length) {
            ui.staffBody.innerHTML = '<tr><td colspan="4">No staff returned.</td></tr>';
            return;
        }

        ui.staffBody.innerHTML = rows.map((r) => `
            <tr>
                <td>${safe((r.first_name || '') + ' ' + (r.last_name || '')).trim()}</td>
                <td>${safe(r.role_name ?? r.role)}</td>
                <td>${safe(r.region)}</td>
                <td>${safe(r.availability_status ?? r.status)}</td>
            </tr>
        `).join('');
    }

    async function loadScenarios() {
        const out = await callApi('/api/scenarios');
        const rows = parseRows(out.data).slice(0, 10);
        ui.kpiScenarios.textContent = String(rows.length);
        if (!rows.length) {
            ui.scenariosBody.innerHTML = '<tr><td colspan="4">No scenarios returned.</td></tr>';
            return;
        }

        ui.scenariosBody.innerHTML = rows.map((r) => `
            <tr>
                <td>${safe(r.id)}</td>
                <td>${safe(r.scenario_name ?? r.name)}</td>
                <td>${safe(r.base_case)}</td>
                <td>${safe(r.created_at)}</td>
            </tr>
        `).join('');
    }

    async function loadRoleGap() {
        const out = await callApi('/api/dashboards/role-gap?month=2027-03');
        const rows = parseRows(out.data);
        ui.kpiWarnings.textContent = String(rows.length);
    }

    async function runForecast() {
        const payload = {
            project: {
                project_value: Number($('projectValue').value || 0),
                start_date: $('startDate').value,
                end_date: $('endDate').value,
                complexity_level: $('complexity').value,
                planning_intensity: $('planningIntensity').value,
                commercial_intensity: $('commercialIntensity').value,
                site_presence_required: 'full-time',
            }
        };

        const out = await callApi('/api/forecast', 'POST', payload);
        ui.forecastOutput.textContent = JSON.stringify(out.data, null, 2);
    }

    async function refreshAll() {
        await Promise.allSettled([
            loadProjects(),
            loadStaff(),
            loadScenarios(),
            loadRoleGap(),
        ]);
    }

    $('loginBtn').addEventListener('click', login);
    $('logoutBtn').addEventListener('click', logout);
    $('refreshAllBtn').addEventListener('click', refreshAll);
    $('runForecastBtn').addEventListener('click', runForecast);

    document.querySelectorAll('[data-call]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const path = btn.getAttribute('data-call');
            await callApi(path);
            if (path === '/api/projects') await loadProjects();
            if (path === '/api/staff') await loadStaff();
            if (path === '/api/scenarios') await loadScenarios();
        });
    });

    setSession(false, 'Not authenticated');
})();
</script>
</body>
</html>
