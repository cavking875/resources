<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resource Planner AI</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&family=IBM+Plex+Mono:wght@400;500&display=swap');

        :root {
            --bg: #f4efe4;
            --card: #fffdf8;
            --line: #ddd4c3;
            --ink: #16212b;
            --muted: #5e6975;
            --brand: #0f766e;
            --brand-dark: #0b5b55;
            --accent: #9a3412;
            --ok: #166534;
            --bad: #b91c1c;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: var(--ink);
            font-family: "Plus Jakarta Sans", "Segoe UI", sans-serif;
            min-height: 100vh;
            background:
                radial-gradient(850px 520px at 90% -25%, #fde68a 0%, transparent 55%),
                radial-gradient(900px 550px at -15% 120%, #99f6e4 0%, transparent 57%),
                var(--bg);
        }

        .container {
            width: min(1400px, calc(100vw - 24px));
            margin: 12px auto;
            border: 1px solid var(--line);
            border-radius: 20px;
            overflow: hidden;
            background: var(--card);
            box-shadow: 0 26px 60px rgba(22, 33, 43, 0.12);
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: calc(100vh - 24px);
        }

        .sidebar {
            background: linear-gradient(160deg, var(--brand), var(--brand-dark));
            color: #e8fffc;
            padding: 20px;
            display: grid;
            gap: 14px;
            align-content: start;
        }

        .title {
            margin: 0;
            font-weight: 800;
            font-size: 26px;
            line-height: 1;
            letter-spacing: -0.02em;
        }

        .subtitle {
            margin: 6px 0 0;
            font-size: 13px;
            color: #bff3ed;
        }

        .box {
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.08);
            padding: 12px;
        }

        .box h3 {
            margin: 0 0 9px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #d5fffa;
        }

        .box input,
        .box select,
        .btn,
        .main input,
        .main select { font: inherit; border-radius: 9px; }

        .box input {
            width: 100%;
            margin-bottom: 8px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: rgba(0, 30, 28, 0.45);
            color: #e8fffc;
            padding: 9px 10px;
        }

        .row { display: flex; gap: 8px; flex-wrap: wrap; }

        .btn {
            border: 0;
            padding: 9px 12px;
            font-weight: 700;
            letter-spacing: 0.01em;
            cursor: pointer;
            transition: transform .14s ease, opacity .14s ease;
        }

        .btn:hover { transform: translateY(-1px); }
        .btn:disabled { opacity: .6; cursor: default; }

        .btn-primary { background: #f97316; color: white; }
        .btn-ghost { background: rgba(255,255,255,.1); color: #defdfa; border: 1px solid rgba(255,255,255,.28); }
        .btn-neutral { background: #e8ecf1; color: #1f2937; }

        .badge {
            display: inline-block;
            margin-top: 8px;
            padding: 5px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.26);
            font-size: 12px;
            color: #d9fffb;
            background: rgba(255,255,255,.08);
        }

        .badge.offline { color: #ffe2e2; border-color: rgba(248,113,113,.55); background: rgba(127,29,29,.35); }

        .nav { display: grid; gap: 7px; }

        .nav button {
            text-align: left;
            width: 100%;
            border: 1px solid rgba(255,255,255,.25);
            background: rgba(255,255,255,.08);
            color: #eafffd;
            border-radius: 10px;
            padding: 9px 10px;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .nav button.active { background: #f97316; border-color: #fb923c; color: #fff; }

        .helper {
            font-size: 12px;
            color: #c8f7f2;
            line-height: 1.45;
        }

        .main { padding: 18px; display: grid; gap: 12px; align-content: start; }

        .head { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; }

        .head h2 {
            margin: 0;
            font-size: clamp(24px, 2.4vw, 34px);
            letter-spacing: -0.02em;
        }

        .head p { margin: 4px 0 0; color: var(--muted); font-size: 14px; }

        .grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 12px;
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 13px;
            background: white;
            padding: 14px;
            box-shadow: 0 7px 16px rgba(22, 33, 43, 0.06);
        }

        .span-3 { grid-column: span 3; }
        .span-4 { grid-column: span 4; }
        .span-5 { grid-column: span 5; }
        .span-6 { grid-column: span 6; }
        .span-7 { grid-column: span 7; }
        .span-8 { grid-column: span 8; }
        .span-12 { grid-column: span 12; }

        .metric-title { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; }
        .metric-value { font-size: 33px; font-weight: 800; letter-spacing: -0.02em; margin-top: 6px; }
        .metric-foot { color: var(--muted); font-size: 12px; }

        h3 { margin: 0; font-size: 16px; }
        .sub { margin: 5px 0 11px; font-size: 13px; color: var(--muted); }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { text-align: left; border-bottom: 1px solid #ece5d7; padding: 8px 6px; vertical-align: top; }
        th { color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; }

        .filters {
            display: grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap: 8px;
            margin-bottom: 10px;
        }

        .main input,
        .main select {
            border: 1px solid var(--line);
            padding: 8px 10px;
            background: #fff;
            color: var(--ink);
            width: 100%;
        }

        .mono {
            margin: 0;
            max-height: 320px;
            overflow: auto;
            background: #111827;
            color: #e5e7eb;
            border-radius: 10px;
            padding: 11px;
            font-family: "IBM Plex Mono", Consolas, monospace;
            font-size: 12px;
            line-height: 1.45;
        }

        .view { display: none; }
        .view.active { display: block; }

        .toast {
            display: none;
            border: 1px solid transparent;
            border-radius: 9px;
            padding: 8px 10px;
            font-size: 13px;
            margin-top: 8px;
        }

        .toast.ok { display: block; color: var(--ok); background: #ecfdf5; border-color: #86efac; }
        .toast.bad { display: block; color: var(--bad); background: #fef2f2; border-color: #fca5a5; }

        @media (max-width: 1120px) {
            .container { grid-template-columns: 1fr; }
            .span-3, .span-4, .span-5, .span-6, .span-7, .span-8 { grid-column: span 12; }
            .filters { grid-template-columns: repeat(2, minmax(0,1fr)); }
        }
    </style>
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <section>
            <h1 class="title">Resource Planner AI</h1>
            <p class="subtitle">Workforce forecasting and allocation platform.</p>
        </section>

        <section class="box">
            <h3>Session</h3>
            <input id="email" value="admin@resourceplanner.local" placeholder="Email">
            <input id="password" type="password" value="password" placeholder="Password">
            <div class="row">
                <button class="btn btn-primary" id="loginBtn">Login</button>
                <button class="btn btn-ghost" id="logoutBtn">Logout</button>
            </div>
            <div id="sessionState" class="badge offline">Not authenticated</div>
            <div id="sessionToast" class="toast"></div>
        </section>

        <section class="box">
            <h3>Modules</h3>
            <div class="nav">
                <button data-view="dashboard" class="active">Dashboard</button>
                <button data-view="projects">Projects</button>
                <button data-view="staff">Staff</button>
                <button data-view="forecast">Forecast</button>
                <button data-view="scenarios">Scenarios</button>
                <button data-view="diagnostics">Diagnostics</button>
            </div>
        </section>

        <section class="helper">
            Root API: <strong>/api</strong><br>
            Health: <strong>/api/health</strong><br>
            Use admin seed credentials to load protected modules.
        </section>
    </aside>

    <main class="main">
        <header class="head">
            <div>
                <h2>Operations Workspace</h2>
                <p id="meta">Ready. No API call made yet.</p>
            </div>
            <div class="row">
                <button class="btn btn-neutral" id="refreshDashboardBtn">Refresh Dashboard</button>
            </div>
        </header>

        <section id="view-dashboard" class="view active">
            <div class="grid">
                <article class="panel span-3">
                    <div class="metric-title">Projects</div>
                    <div id="kpiProjects" class="metric-value">0</div>
                    <div class="metric-foot">portfolio rows</div>
                </article>
                <article class="panel span-3">
                    <div class="metric-title">Staff</div>
                    <div id="kpiStaff" class="metric-value">0</div>
                    <div class="metric-foot">people records</div>
                </article>
                <article class="panel span-3">
                    <div class="metric-title">Scenarios</div>
                    <div id="kpiScenarios" class="metric-value">0</div>
                    <div class="metric-foot">active planning sets</div>
                </article>
                <article class="panel span-3">
                    <div class="metric-title">Role Gap Rows</div>
                    <div id="kpiRoleGap" class="metric-value">0</div>
                    <div class="metric-foot">for selected month</div>
                </article>

                <article class="panel span-8">
                    <h3>Portfolio Snapshot</h3>
                    <p class="sub">Latest project records from /api/projects.</p>
                    <table>
                        <thead>
                        <tr><th>ID</th><th>Project</th><th>Region</th><th>Stage</th><th>Value</th></tr>
                        </thead>
                        <tbody id="dashboardProjectsBody"><tr><td colspan="5">No data.</td></tr></tbody>
                    </table>
                </article>

                <article class="panel span-4">
                    <h3>Role Gap Query</h3>
                    <p class="sub">Inspect resource gaps for a target month.</p>
                    <div class="row">
                        <input id="roleGapMonth" type="month" value="2027-03">
                        <button class="btn btn-primary" id="loadRoleGapBtn">Load</button>
                    </div>
                    <pre id="roleGapOutput" class="mono" style="margin-top:10px;">[]</pre>
                </article>
            </div>
        </section>

        <section id="view-projects" class="view">
            <div class="panel">
                <h3>Projects</h3>
                <p class="sub">Filter by region, stage, and search term based on original API contract.</p>
                <div class="filters">
                    <input id="projectRegion" placeholder="Region (e.g. South East)">
                    <input id="projectStage" placeholder="Project Stage (e.g. Construction)">
                    <input id="projectSearch" placeholder="Search term">
                    <button class="btn btn-primary" id="loadProjectsBtn">Load Projects</button>
                </div>
                <table>
                    <thead>
                    <tr><th>ID</th><th>Name</th><th>Region</th><th>Stage</th><th>Client</th><th>Value</th></tr>
                    </thead>
                    <tbody id="projectsBody"><tr><td colspan="6">No projects loaded.</td></tr></tbody>
                </table>
            </div>
        </section>

        <section id="view-staff" class="view">
            <div class="panel">
                <h3>Staff</h3>
                <p class="sub">Filter staff by region, availability status, and role id.</p>
                <div class="filters">
                    <input id="staffRegion" placeholder="Region">
                    <select id="staffAvailability">
                        <option value="">Availability Status</option>
                        <option value="available">available</option>
                        <option value="allocated">allocated</option>
                    </select>
                    <input id="staffRoleId" type="number" placeholder="Role ID">
                    <button class="btn btn-primary" id="loadStaffBtn">Load Staff</button>
                </div>
                <table>
                    <thead>
                    <tr><th>ID</th><th>Name</th><th>Role</th><th>Region</th><th>Status</th><th>Max FTE</th></tr>
                    </thead>
                    <tbody id="staffBody"><tr><td colspan="6">No staff loaded.</td></tr></tbody>
                </table>
            </div>
        </section>

        <section id="view-forecast" class="view">
            <div class="grid">
                <article class="panel span-6">
                    <h3>Demand Forecast</h3>
                    <p class="sub">Run POST /api/forecast with project parameters.</p>
                    <div class="filters">
                        <input id="fValue" type="number" value="6000000" placeholder="Project Value">
                        <input id="fStart" type="date" value="2026-09-01">
                        <input id="fEnd" type="date" value="2028-03-31">
                        <select id="fComplexity">
                            <option value="high" selected>high</option>
                            <option value="medium">medium</option>
                            <option value="low">low</option>
                        </select>
                    </div>
                    <div class="row">
                        <select id="fPlanning">
                            <option value="high">planning high</option>
                            <option value="medium" selected>planning medium</option>
                            <option value="low">planning low</option>
                        </select>
                        <select id="fCommercial">
                            <option value="high" selected>commercial high</option>
                            <option value="medium">commercial medium</option>
                            <option value="low">commercial low</option>
                        </select>
                        <button class="btn btn-primary" id="runForecastBtn">Run Forecast</button>
                    </div>
                </article>

                <article class="panel span-6">
                    <h3>Forecast Response</h3>
                    <p class="sub">Raw response payload.</p>
                    <pre id="forecastOutput" class="mono">{}</pre>
                </article>
            </div>
        </section>

        <section id="view-scenarios" class="view">
            <div class="grid">
                <article class="panel span-5">
                    <h3>Create Scenario</h3>
                    <p class="sub">Create via POST /api/scenarios.</p>
                    <div class="row">
                        <input id="scenarioName" placeholder="Scenario name">
                        <select id="scenarioBase">
                            <option value="false" selected>base_case: false</option>
                            <option value="true">base_case: true</option>
                        </select>
                        <button class="btn btn-primary" id="createScenarioBtn">Create</button>
                    </div>
                </article>

                <article class="panel span-7">
                    <h3>Scenario List</h3>
                    <p class="sub">List from GET /api/scenarios.</p>
                    <table>
                        <thead>
                        <tr><th>ID</th><th>Name</th><th>Base Case</th><th>Created</th></tr>
                        </thead>
                        <tbody id="scenariosBody"><tr><td colspan="4">No scenarios loaded.</td></tr></tbody>
                    </table>
                </article>
            </div>
        </section>

        <section id="view-diagnostics" class="view">
            <div class="panel">
                <h3>Diagnostics Stream</h3>
                <p class="sub">Live request output and API diagnostics.</p>
                <pre id="streamOutput" class="mono">{}</pre>
            </div>
        </section>
    </main>
</div>

<script>
(() => {
    let token = null;
    const $ = (id) => document.getElementById(id);

    const ui = {
        meta: $('meta'),
        streamOutput: $('streamOutput'),
        sessionState: $('sessionState'),
        sessionToast: $('sessionToast'),
        kpiProjects: $('kpiProjects'),
        kpiStaff: $('kpiStaff'),
        kpiScenarios: $('kpiScenarios'),
        kpiRoleGap: $('kpiRoleGap'),
    };

    function setSession(ok, message) {
        ui.sessionState.textContent = message;
        ui.sessionState.className = ok ? 'badge' : 'badge offline';
    }

    function toast(message, ok) {
        ui.sessionToast.textContent = message;
        ui.sessionToast.className = 'toast ' + (ok ? 'ok' : 'bad');
    }

    function authHeaders() {
        return token ? { Authorization: 'Bearer ' + token } : {};
    }

    function parseRows(payload) {
        if (Array.isArray(payload)) return payload;
        if (payload && Array.isArray(payload.data)) return payload.data;
        if (payload && payload.data && Array.isArray(payload.data.items)) return payload.data.items;
        if (payload && Array.isArray(payload.items)) return payload.items;
        return [];
    }

    function safe(value) {
        if (value === null || value === undefined || value === '') return '-';
        return String(value);
    }

    async function api(path, method = 'GET', body = null) {
        const started = performance.now();
        const opts = {
            method,
            headers: { 'Content-Type': 'application/json', ...authHeaders() },
        };
        if (body) opts.body = JSON.stringify(body);

        const res = await fetch(path, opts);
        const raw = await res.text();
        let data = raw;
        try { data = JSON.parse(raw); } catch (_) {}

        const ms = Math.round(performance.now() - started);
        ui.meta.textContent = method + ' ' + path + ' • ' + res.status + ' • ' + ms + 'ms';
        ui.streamOutput.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);

        return { ok: res.ok, status: res.status, data };
    }

    async function login() {
        const out = await api('/api/auth/login', 'POST', {
            email: $('email').value.trim(),
            password: $('password').value,
        });

        if (!out.ok || !out.data?.data?.token) {
            setSession(false, 'Auth failed');
            toast(out.data?.error || 'Login failed', false);
            return;
        }

        token = out.data.data.token;
        setSession(true, 'Authenticated');
        toast('Signed in as ' + safe(out.data.data.user?.email), true);
        await refreshDashboard();
    }

    async function logout() {
        if (token) await api('/api/auth/logout', 'POST');
        token = null;
        setSession(false, 'Not authenticated');
        toast('Session revoked', true);
    }

    async function refreshDashboard() {
        await Promise.allSettled([loadProjectsDashboard(), loadStaffDashboard(), loadScenariosDashboard(), loadRoleGap()]);
    }

    async function loadProjectsDashboard() {
        const out = await api('/api/projects');
        const rows = parseRows(out.data).slice(0, 8);
        ui.kpiProjects.textContent = String(rows.length);
        $('dashboardProjectsBody').innerHTML = rows.length ? rows.map((r) => `
            <tr>
                <td>${safe(r.id ?? r.project_id)}</td>
                <td>${safe(r.project_name ?? r.name)}</td>
                <td>${safe(r.region)}</td>
                <td>${safe(r.project_stage ?? r.stage)}</td>
                <td>${safe(r.project_value ?? r.value)}</td>
            </tr>
        `).join('') : '<tr><td colspan="5">No project rows.</td></tr>';
    }

    async function loadStaffDashboard() {
        const out = await api('/api/staff');
        const rows = parseRows(out.data);
        ui.kpiStaff.textContent = String(rows.length);
    }

    async function loadScenariosDashboard() {
        const out = await api('/api/scenarios');
        const rows = parseRows(out.data);
        ui.kpiScenarios.textContent = String(rows.length);
    }

    async function loadRoleGap() {
        const month = $('roleGapMonth').value || '2027-03';
        const out = await api('/api/dashboards/role-gap?month=' + encodeURIComponent(month));
        const rows = parseRows(out.data);
        ui.kpiRoleGap.textContent = String(rows.length);
        $('roleGapOutput').textContent = JSON.stringify(rows, null, 2);
    }

    async function loadProjectsModule() {
        const query = new URLSearchParams();
        if ($('projectRegion').value.trim()) query.set('region', $('projectRegion').value.trim());
        if ($('projectStage').value.trim()) query.set('project_stage', $('projectStage').value.trim());
        if ($('projectSearch').value.trim()) query.set('search', $('projectSearch').value.trim());
        query.set('page', '1');
        query.set('per_page', '25');
        const out = await api('/api/projects?' + query.toString());
        const rows = parseRows(out.data);
        $('projectsBody').innerHTML = rows.length ? rows.map((r) => `
            <tr>
                <td>${safe(r.id ?? r.project_id)}</td>
                <td>${safe(r.project_name ?? r.name)}</td>
                <td>${safe(r.region)}</td>
                <td>${safe(r.project_stage ?? r.stage)}</td>
                <td>${safe(r.client_name ?? r.client)}</td>
                <td>${safe(r.project_value ?? r.value)}</td>
            </tr>
        `).join('') : '<tr><td colspan="6">No projects returned.</td></tr>';
    }

    async function loadStaffModule() {
        const query = new URLSearchParams();
        if ($('staffRegion').value.trim()) query.set('region', $('staffRegion').value.trim());
        if ($('staffAvailability').value) query.set('availability_status', $('staffAvailability').value);
        if ($('staffRoleId').value) query.set('role_id', $('staffRoleId').value);
        const out = await api('/api/staff?' + query.toString());
        const rows = parseRows(out.data);
        $('staffBody').innerHTML = rows.length ? rows.map((r) => `
            <tr>
                <td>${safe(r.id)}</td>
                <td>${safe((r.first_name || '') + ' ' + (r.last_name || '')).trim()}</td>
                <td>${safe(r.role_name ?? r.role)}</td>
                <td>${safe(r.region)}</td>
                <td>${safe(r.availability_status ?? r.status)}</td>
                <td>${safe(r.max_fte)}</td>
            </tr>
        `).join('') : '<tr><td colspan="6">No staff returned.</td></tr>';
    }

    async function runForecast() {
        const payload = {
            project: {
                project_value: Number($('fValue').value || 0),
                start_date: $('fStart').value,
                end_date: $('fEnd').value,
                complexity_level: $('fComplexity').value,
                planning_intensity: $('fPlanning').value,
                commercial_intensity: $('fCommercial').value,
                site_presence_required: 'full-time',
            }
        };
        const out = await api('/api/forecast', 'POST', payload);
        $('forecastOutput').textContent = JSON.stringify(out.data, null, 2);
    }

    async function loadScenariosModule() {
        const out = await api('/api/scenarios');
        const rows = parseRows(out.data);
        $('scenariosBody').innerHTML = rows.length ? rows.map((r) => `
            <tr>
                <td>${safe(r.id)}</td>
                <td>${safe(r.scenario_name ?? r.name)}</td>
                <td>${safe(r.base_case)}</td>
                <td>${safe(r.created_at)}</td>
            </tr>
        `).join('') : '<tr><td colspan="4">No scenarios returned.</td></tr>';
    }

    async function createScenario() {
        const name = $('scenarioName').value.trim();
        if (!name) {
            toast('Scenario name is required', false);
            return;
        }
        const out = await api('/api/scenarios', 'POST', {
            scenario_name: name,
            base_case: $('scenarioBase').value === 'true',
        });
        if (out.ok) {
            toast('Scenario created', true);
            $('scenarioName').value = '';
            await loadScenariosModule();
            await loadScenariosDashboard();
        } else {
            toast(out.data?.error || 'Failed to create scenario', false);
        }
    }

    function switchView(next) {
        document.querySelectorAll('.view').forEach((v) => v.classList.remove('active'));
        $('view-' + next).classList.add('active');
        document.querySelectorAll('.nav button').forEach((b) => {
            b.classList.toggle('active', b.getAttribute('data-view') === next);
        });
    }

    $('loginBtn').addEventListener('click', login);
    $('logoutBtn').addEventListener('click', logout);
    $('refreshDashboardBtn').addEventListener('click', refreshDashboard);
    $('loadRoleGapBtn').addEventListener('click', loadRoleGap);
    $('loadProjectsBtn').addEventListener('click', loadProjectsModule);
    $('loadStaffBtn').addEventListener('click', loadStaffModule);
    $('runForecastBtn').addEventListener('click', runForecast);
    $('createScenarioBtn').addEventListener('click', createScenario);

    document.querySelectorAll('.nav button').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const view = btn.getAttribute('data-view');
            switchView(view);
            if (view === 'projects') await loadProjectsModule();
            if (view === 'staff') await loadStaffModule();
            if (view === 'scenarios') await loadScenariosModule();
        });
    });

    setSession(false, 'Not authenticated');
})();
</script>
</body>
</html>
