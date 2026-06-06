<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resource Planner AI</title>
    <style>
        :root {
            --bg: #f3efe6;
            --ink: #1f2933;
            --muted: #52606d;
            --card: #fffdf8;
            --line: #d9d4c8;
            --brand: #0f766e;
            --brand-2: #115e59;
            --accent: #b45309;
            --ok: #166534;
            --bad: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--ink);
            font-family: "IBM Plex Sans", "Segoe UI", Tahoma, sans-serif;
            background:
                radial-gradient(1200px 600px at 90% -20%, #fde68a 0%, transparent 60%),
                radial-gradient(800px 500px at -10% 120%, #99f6e4 0%, transparent 60%),
                var(--bg);
        }
        .wrap { max-width: 1140px; margin: 0 auto; padding: 24px; }
        .hero {
            background: linear-gradient(130deg, #0f766e, #0f766e 45%, #0b4d49);
            color: #f7fafc;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 16px 40px rgba(15, 118, 110, 0.25);
        }
        .hero h1 { margin: 0; font-size: clamp(28px, 4vw, 42px); letter-spacing: -0.02em; }
        .hero p { margin: 10px 0 0; color: #d8f8f4; }
        .grid {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 14px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 8px 20px rgba(31, 41, 51, 0.08);
        }
        .span-4 { grid-column: span 4; }
        .span-8 { grid-column: span 8; }
        .span-12 { grid-column: span 12; }
        h2 { margin: 0 0 12px; font-size: 20px; }
        .sub { margin: 0 0 14px; color: var(--muted); font-size: 14px; }
        .row { display: flex; gap: 10px; flex-wrap: wrap; }
        input, button, select {
            border-radius: 10px;
            border: 1px solid var(--line);
            font: inherit;
        }
        input, select { padding: 10px 12px; background: #ffffff; color: var(--ink); }
        .grow { flex: 1 1 210px; }
        button {
            padding: 10px 14px;
            cursor: pointer;
            border: 0;
            color: #fff;
            background: var(--brand);
            transition: transform .16s ease, opacity .16s ease;
        }
        button.secondary { background: var(--accent); }
        button:disabled { opacity: .55; cursor: default; }
        button:hover:not(:disabled) { transform: translateY(-1px); }
        .pill {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #ecfdf5;
            border: 1px solid #86efac;
            color: var(--ok);
            font-size: 12px;
            font-weight: 600;
        }
        .pill.bad {
            background: #fef2f2;
            border-color: #fca5a5;
            color: var(--bad);
        }
        pre {
            margin: 0;
            overflow: auto;
            max-height: 420px;
            background: #1f2933;
            color: #e6edf3;
            border-radius: 12px;
            padding: 12px;
            font-family: "IBM Plex Mono", Consolas, monospace;
            font-size: 12px;
            line-height: 1.45;
        }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; border-bottom: 1px solid var(--line); padding: 8px 6px; }
        th { color: var(--muted); font-weight: 600; }
        .kpi { font-size: 26px; font-weight: 700; letter-spacing: -0.01em; }
        .fade {
            animation: fadeUp .35s ease;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 900px) {
            .span-4, .span-8 { grid-column: span 12; }
            .wrap { padding: 14px; }
            .hero { padding: 16px; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <section class="hero fade">
        <h1>Resource Planner AI</h1>
        <p>Operational dashboard for login, project visibility, staff pipeline, and forecast checks.</p>
    </section>

    <section class="grid fade" style="animation-delay:.06s;">
        <article class="card span-4">
            <h2>Session</h2>
            <p class="sub">Authenticate with your API and keep token in browser memory.</p>
            <div class="row">
                <input id="email" class="grow" value="admin@resourceplanner.local" placeholder="Email" />
                <input id="password" class="grow" type="password" value="password" placeholder="Password" />
            </div>
            <div class="row" style="margin-top:10px;">
                <button id="loginBtn">Login</button>
                <button id="logoutBtn" class="secondary">Logout</button>
                <span id="sessionState" class="pill bad">Not authenticated</span>
            </div>
        </article>

        <article class="card span-8">
            <h2>Health & Quick Calls</h2>
            <p class="sub">Run API checks from the product UI.</p>
            <div class="row">
                <button data-call="/api/health">Health</button>
                <button data-call="/api/projects">Projects</button>
                <button data-call="/api/staff">Staff</button>
                <button data-call="/api/scenarios">Scenarios</button>
                <button data-call="/api/dashboards/role-gap?month=2027-03">Role Gap</button>
            </div>
            <div style="margin-top:10px;" id="quickMeta" class="sub">No call made yet.</div>
            <pre id="quickOutput">{}</pre>
        </article>

        <article class="card span-4">
            <h2>Portfolio KPI</h2>
            <p class="sub">Simple count from current project list response.</p>
            <div id="projectCount" class="kpi">0</div>
            <div class="sub">projects loaded</div>
        </article>

        <article class="card span-8">
            <h2>Projects Snapshot</h2>
            <p class="sub">Top records from latest projects call.</p>
            <div style="overflow:auto;">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Project</th>
                        <th>Region</th>
                        <th>Stage</th>
                    </tr>
                    </thead>
                    <tbody id="projectsBody">
                    <tr><td colspan="4" class="sub">No data yet.</td></tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</div>

<script>
(() => {
    let token = null;

    const el = {
        email: document.getElementById('email'),
        password: document.getElementById('password'),
        loginBtn: document.getElementById('loginBtn'),
        logoutBtn: document.getElementById('logoutBtn'),
        sessionState: document.getElementById('sessionState'),
        quickOutput: document.getElementById('quickOutput'),
        quickMeta: document.getElementById('quickMeta'),
        projectsBody: document.getElementById('projectsBody'),
        projectCount: document.getElementById('projectCount'),
    };

    function setSessionState(on, label) {
        el.sessionState.textContent = label;
        el.sessionState.className = on ? 'pill' : 'pill bad';
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
        el.quickMeta.textContent = method + ' ' + path + ' - ' + res.status + ' in ' + ms + 'ms';
        el.quickOutput.textContent = typeof json === 'string' ? json : JSON.stringify(json, null, 2);

        return { ok: res.ok, status: res.status, data: json };
    }

    async function login() {
        el.loginBtn.disabled = true;
        try {
            const out = await callApi('/api/auth/login', 'POST', {
                email: el.email.value.trim(),
                password: el.password.value,
            });

            if (!out.ok || !out.data || !out.data.data || !out.data.data.token) {
                throw new Error((out.data && out.data.error) || 'Login failed.');
            }

            token = out.data.data.token;
            setSessionState(true, 'Authenticated');
            await loadProjects();
        } catch (err) {
            setSessionState(false, err.message || 'Auth failed');
        } finally {
            el.loginBtn.disabled = false;
        }
    }

    async function logout() {
        try {
            if (token) await callApi('/api/auth/logout', 'POST');
        } finally {
            token = null;
            setSessionState(false, 'Not authenticated');
            el.projectsBody.innerHTML = '<tr><td colspan="4" class="sub">No data yet.</td></tr>';
            el.projectCount.textContent = '0';
        }
    }

    async function loadProjects() {
        const out = await callApi('/api/projects');
        const rows = parseRows(out.data).slice(0, 8);
        el.projectCount.textContent = String(rows.length);

        if (!rows.length) {
            el.projectsBody.innerHTML = '<tr><td colspan="4" class="sub">No rows returned.</td></tr>';
            return;
        }

        el.projectsBody.innerHTML = rows.map((r) => {
            const id = r.id ?? r.project_id ?? '-';
            const name = r.project_name ?? r.name ?? '-';
            const region = r.region ?? '-';
            const stage = r.project_stage ?? r.stage ?? '-';
            return `<tr><td>${id}</td><td>${name}</td><td>${region}</td><td>${stage}</td></tr>`;
        }).join('');
    }

    document.getElementById('loginBtn').addEventListener('click', login);
    document.getElementById('logoutBtn').addEventListener('click', logout);

    document.querySelectorAll('[data-call]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const path = btn.getAttribute('data-call');
            await callApi(path);
            if (path === '/api/projects') {
                await loadProjects();
            }
        });
    });

    setSessionState(false, 'Not authenticated');
})();
</script>
</body>
</html>
