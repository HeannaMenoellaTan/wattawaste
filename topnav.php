<?php
require_once 'firebase_config.php';

function getSensorStatus($database, $sensorType) {
    try {
        $latestRef = $database->getReference("sensors/$sensorType/latest");
        $snapshot = $latestRef->getSnapshot();
        if (!$snapshot->exists()) return ['status' => 'faulty', 'time' => 'No data'];
        $data = $snapshot->getValue();
        $val = $data['value'] ?? null;
        $timestamp = $data['timestamp'] ?? null;
        if ($val === null || $val < 0) return ['status' => 'faulty', 'time' => 'Invalid'];
        if ($timestamp === null) return ['status' => 'faulty', 'time' => 'No timestamp'];
        $diff = time() - $timestamp;
        if ($diff <= 10) return ['status' => 'online', 'time' => $diff];
        elseif ($diff <= 30) return ['status' => 'delayed', 'time' => $diff];
        else return ['status' => 'offline', 'time' => $diff];
    } catch (Exception $e) {
        return ['status' => 'faulty', 'time' => 'Error'];
    }
}

$database = getDatabase();
$tempStatus = getSensorStatus($database, 'temperature');
$humStatus  = getSensorStatus($database, 'humidity');
$gasStatus  = getSensorStatus($database, 'gas');
$phStatus   = getSensorStatus($database, 'ph');

$sensors = [
    'Temperature' => $tempStatus,
    'Humidity'    => $humStatus,
    'Gas'         => $gasStatus,
    'pH'          => $phStatus
];

$faultyCount = 0;
foreach($sensors as $sensor) {
    if($sensor['status'] === 'faulty') $faultyCount++;
}
?>

<!-- ===== TOP NAV ===== -->
<div class="top-nav d-flex justify-content-between align-items-center p-2 px-3 shadow-sm bg-white rounded-3" id="topNav">
    <h1 class="page-title m-0">Leafcycle</h1>

    <div class="top-right d-flex align-items-center gap-2 gap-md-3">

        <!-- DATE & TIME (hidden on small mobile) -->
        <div id="dateTime" class="datetime fw-semibold d-none d-md-block"></div>

        <!-- SENSOR STATUS DROPDOWN -->
        <div class="dropdown">
            <button class="btn btn-light btn-sm dropdown-toggle px-2 px-md-3" type="button" id="sensorDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="d-none d-sm-inline">⚡ Sensor Status</span>
                <span class="d-inline d-sm-none">⚡</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sensorDropdown">
                <?php foreach($sensors as $name => $sensor):
                    $statusClass = $sensor['status'];
                    $timeAgo = is_numeric($sensor['time']) ? $sensor['time'].'s ago' : $sensor['time'];
                    $icon = match($name){
                        'Temperature'=>'🌡️', 'Humidity'=>'💧', 'Gas'=>'💨', 'pH'=>'⚗️', default=>'📡'
                    };
                ?>
                <li>
                    <div class="dropdown-item d-flex justify-content-between align-items-center gap-2">
                        <span><?php echo "$icon $name"; ?></span>
                        <span class="dot <?php echo $statusClass; ?>"></span>
                        <small class="text-muted ms-auto"><?php echo $timeAgo; ?></small>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- FAULTY COUNT -->
        <div class="faulty" id="faultyText">
            <span class="d-none d-sm-inline">⚠️ <?php echo $faultyCount; ?> Faulty Sensor<?php echo $faultyCount !== 1 ? 's' : ''; ?></span>
            <span class="d-inline d-sm-none" title="Faulty sensors">⚠️ <?php echo $faultyCount; ?></span>
        </div>

        <!-- PROFILE DROPDOWN -->
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle p-0 border-0" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div id="topNavAvatar" class="topnav-avatar rounded-circle">
                    <span id="topNavInitial">U</span>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                <li><a class="dropdown-item" href="profile.php">
                    <i class="fas fa-user me-2"></i>Profile
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="openSettingsModal(); return false;">
                    <i class="fas fa-cog me-2"></i>Settings
                </a></li>
                <!-- Date/time shown in dropdown on mobile -->
                <li class="d-md-none">
                    <div class="dropdown-item text-muted small" id="dateTimeMobile"></div>
                </li>
            </ul>
        </div>

    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     SETTINGS MODAL
═══════════════════════════════════════════════════════ -->
<div id="settingsModal" style="
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.6); backdrop-filter:blur(6px);
    z-index:99999; align-items:center; justify-content:center; padding:20px;">

    <div id="settingsPanel" style="
        background:#fff; border-radius:24px;
        width:100%; max-width:560px; max-height:90vh;
        overflow-y:auto; box-shadow:0 24px 64px rgba(0,0,0,0.25);
        animation: settingsSlideIn .3s ease;">

        <!-- Header -->
        <div style="
            display:flex; align-items:center; justify-content:space-between;
            padding:28px 32px 20px; border-bottom:1px solid rgba(0,0,0,0.07);">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="
                    width:40px; height:40px; border-radius:12px;
                    background:linear-gradient(135deg,#23ed99,#0f8156);
                    display:flex; align-items:center; justify-content:center;
                    font-size:20px;">⚙️</div>
                <div>
                    <h2 style="margin:0; font-size:20px; font-weight:800; color:#1a1a1a;">Settings</h2>
                    <p style="margin:0; font-size:12px; color:#888;">Customize your experience</p>
                </div>
            </div>
            <button onclick="closeSettingsModal()" style="
                background:rgba(0,0,0,0.06); border:none; border-radius:10px;
                width:36px; height:36px; font-size:18px; cursor:pointer;
                display:flex; align-items:center; justify-content:center;
                transition:background .2s;">✕</button>
        </div>

        <!-- Tab bar -->
        <div style="display:flex; gap:4px; padding:16px 32px 0; border-bottom:1px solid rgba(0,0,0,0.07);">
            <button class="settings-tab active" onclick="switchSettingsTab('display', this)">
                <i class="fas fa-paint-brush"></i> Display
            </button>
            <button class="settings-tab" onclick="switchSettingsTab('accessibility', this)">
                <i class="fas fa-universal-access"></i> Accessibility
            </button>
        </div>

        <!-- ── DISPLAY TAB ── -->
        <div id="settingsTab-display" class="settings-tab-content" style="padding:28px 32px;">

            <!-- Appearance -->
            <div class="settings-section">
                <div class="settings-section-header">
                    <span class="settings-section-icon">🎨</span>
                    <div>
                        <div class="settings-section-title">Appearance</div>
                        <div class="settings-section-sub">Choose your preferred theme</div>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-top:14px;">
                    <div class="theme-card" id="theme-light" onclick="setTheme('light')">
                        <div class="theme-preview" style="background:#f9fafb; border:1px solid #e5e7eb;">
                            <div style="height:8px; background:#fff; border-radius:4px; margin-bottom:4px;"></div>
                            <div style="height:5px; background:#e5e7eb; border-radius:3px; width:70%;"></div>
                            <div style="height:5px; background:#e5e7eb; border-radius:3px; width:50%; margin-top:3px;"></div>
                        </div>
                        <div class="theme-label">☀️ Light</div>
                    </div>
                    <div class="theme-card" id="theme-dark" onclick="setTheme('dark')">
                        <div class="theme-preview" style="background:#1e1e2e; border:1px solid #333;">
                            <div style="height:8px; background:#2d2d3f; border-radius:4px; margin-bottom:4px;"></div>
                            <div style="height:5px; background:#444; border-radius:3px; width:70%;"></div>
                            <div style="height:5px; background:#444; border-radius:3px; width:50%; margin-top:3px;"></div>
                        </div>
                        <div class="theme-label">🌙 Dark</div>
                    </div>
                    <div class="theme-card" id="theme-system" onclick="setTheme('system')">
                        <div class="theme-preview" style="background:linear-gradient(135deg,#f9fafb 50%,#1e1e2e 50%); border:1px solid #ccc;">
                            <div style="height:8px; background:linear-gradient(135deg,#fff 50%,#2d2d3f 50%); border-radius:4px; margin-bottom:4px;"></div>
                            <div style="height:5px; background:linear-gradient(135deg,#e5e7eb 50%,#444 50%); border-radius:3px; width:70%;"></div>
                        </div>
                        <div class="theme-label">💻 System</div>
                    </div>
                </div>
            </div>

            <div class="settings-divider"></div>

            <!-- Language -->
            <div class="settings-section">
                <div class="settings-section-header">
                    <span class="settings-section-icon">🌐</span>
                    <div>
                        <div class="settings-section-title">Language</div>
                        <div class="settings-section-sub">Select your preferred interface language</div>
                    </div>
                </div>
                <div style="margin-top:14px;">
                    <select id="languageSelect" onchange="setLanguage(this.value); setTimeout(()=>applyLanguage(this.value),100);" style="
                        width:100%; padding:11px 14px;
                        background:rgba(0,0,0,0.03); border:2px solid rgba(0,0,0,0.1);
                        border-radius:10px; font-size:14px; font-family:inherit;
                        color:#333; appearance:none; cursor:pointer;
                        transition:border-color .3s;">
                        <option value="en">🇺🇸 English</option>
                        <option value="fil">🇵🇭 Filipino (Tagalog)</option>
                        <option value="ceb">🇵🇭 Cebuano</option>
                        <option value="es">🇪🇸 Español</option>
                        <option value="zh">🇨🇳 中文 (Chinese)</option>
                        <option value="ja">🇯🇵 日本語 (Japanese)</option>
                        <option value="ko">🇰🇷 한국어 (Korean)</option>
                        <option value="fr">🇫🇷 Français</option>
                    </select>
                    <p style="font-size:12px; color:#aaa; margin-top:6px; margin-bottom:0;">
                        ⚠️ Some languages may require a page reload to fully apply.
                    </p>
                </div>
            </div>

        </div>

        <!-- ── ACCESSIBILITY TAB ── -->
        <div id="settingsTab-accessibility" class="settings-tab-content" style="padding:28px 32px; display:none;">

            <!-- Font Size -->
            <div class="settings-section">
                <div class="settings-section-header">
                    <span class="settings-section-icon">🔤</span>
                    <div>
                        <div class="settings-section-title">Font Size</div>
                        <div class="settings-section-sub">Adjust text size across the interface</div>
                    </div>
                </div>
                <div style="margin-top:16px;">
                    <div style="display:flex; align-items:center; gap:14px;">
                        <span style="font-size:12px; color:#888; min-width:20px;">A</span>
                        <input type="range" id="fontSizeSlider" min="80" max="130" value="100" step="5"
                            oninput="applyFontSize(this.value)"
                            style="flex:1; accent-color:#0f8156;">
                        <span style="font-size:20px; color:#888; min-width:24px;">A</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:11px; color:#bbb; margin-top:4px; padding:0 34px;">
                        <span>80%</span>
                        <span id="fontSizeLabel" style="color:#0f8156; font-weight:700;">100%</span>
                        <span>130%</span>
                    </div>
                    <div style="
                        margin-top:14px; padding:12px 16px;
                        background:rgba(15,129,86,0.06); border-radius:10px;
                        border:1px solid rgba(15,129,86,0.15); font-size:13px; color:#333;">
                        Preview: <span id="fontPreviewText" style="font-weight:600;">The quick brown fox jumps over the lazy dog.</span>
                    </div>
                </div>
            </div>

            <div class="settings-divider"></div>

            <!-- High Contrast -->
            <div class="settings-section">
                <div class="settings-section-header" style="justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span class="settings-section-icon">🔆</span>
                        <div>
                            <div class="settings-section-title">High Contrast</div>
                            <div class="settings-section-sub">Sharper borders and stronger color differentiation</div>
                        </div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="highContrastToggle" onchange="applyHighContrast(this.checked)">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="settings-divider"></div>

            <!-- Reduce Motion -->
            <div class="settings-section">
                <div class="settings-section-header" style="justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span class="settings-section-icon">🎞️</span>
                        <div>
                            <div class="settings-section-title">Reduce Motion</div>
                            <div class="settings-section-sub">Minimize animations and transitions</div>
                        </div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="reduceMotionToggle" onchange="applyReduceMotion(this.checked)">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="settings-divider"></div>

            <!-- Screen Reader hint -->
            <div class="settings-section">
                <div class="settings-section-header">
                    <span class="settings-section-icon">🔊</span>
                    <div>
                        <div class="settings-section-title">Screen Reader</div>
                        <div class="settings-section-sub">Optimize layout for assistive technologies</div>
                    </div>
                </div>
                <div style="margin-top:14px; display:flex; flex-direction:column; gap:8px;">
                    <label class="sr-option" id="srOpt-none">
                        <input type="radio" name="srMode" value="none" onchange="applyScreenReader('none')" checked>
                        <span>None</span>
                    </label>
                    <label class="sr-option" id="srOpt-aria">
                        <input type="radio" name="srMode" value="aria" onchange="applyScreenReader('aria')">
                        <span>Enhanced ARIA labels</span>
                    </label>
                    <label class="sr-option" id="srOpt-simplified">
                        <input type="radio" name="srMode" value="simplified" onchange="applyScreenReader('simplified')">
                        <span>Simplified layout</span>
                    </label>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div style="
            padding:16px 32px 24px;
            border-top:1px solid rgba(0,0,0,0.07);
            display:flex; justify-content:space-between; align-items:center; gap:12px;">
            <button onclick="resetAllSettings()" style="
                padding:10px 20px; background:transparent;
                border:2px solid rgba(0,0,0,0.12); border-radius:10px;
                font-size:14px; font-weight:600; color:#666;
                cursor:pointer; font-family:inherit; transition:all .2s;">
                ↺ Reset to Default
            </button>
            <button onclick="closeSettingsModal()" style="
                padding:10px 28px;
                background:linear-gradient(135deg,#23ed99,#0f8156);
                border:none; border-radius:10px; color:#fff;
                font-size:14px; font-weight:700; cursor:pointer;
                font-family:inherit; box-shadow:0 4px 12px rgba(35,237,153,.35);
                transition:all .2s;">
                Done
            </button>
        </div>

    </div>
</div>

<!-- ===== STYLES ===== -->
<style>
/* ── Top Nav ── */
.top-nav {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: rgba(255,255,255,0.97);
    backdrop-filter: blur(12px);
    border-radius: 12px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}
.top-nav.hidden { transform: translateY(-120%); }

.page-title { color:#2E7D32; font-weight:700; font-size:clamp(1rem, 3vw, 1.25rem); }
.top-right .datetime { color:#2E7D32; opacity:.85; font-weight:600; font-size: clamp(.75rem, 2vw, .95rem); }

.dropdown-menu { min-width: 230px; z-index: 4000; }
.dropdown-item { display:flex; justify-content:space-between; align-items:center; }

.dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.dot.online  { background:#2ecc71; }
.dot.delayed { background:#f1c40f; }
.dot.offline { background:#e74c3c; }
.dot.faulty  { background:#7f8c8d; }

.faulty { font-size: clamp(12px, 2.5vw, 14px); font-weight:600; color:#E65100; white-space:nowrap; }

.topnav-avatar {
    width: 36px; height: 36px; object-fit: cover;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
    background: linear-gradient(135deg, #23ed99, #0f8156);
    color: #fff; font-weight: 700; font-size: 15px; flex-shrink: 0;
}
.topnav-avatar img {
    width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block;
}

#profileDropdown + .dropdown-menu .dropdown-item { padding: 10px 16px; transition: all 0.2s ease; }
#profileDropdown + .dropdown-menu .dropdown-item:hover { background: rgba(76,175,80,0.1); color: #2E7D32; }
#profileDropdown + .dropdown-menu .dropdown-item i { color: #4CAF50; }

@media (max-width: 768px) {
    .top-nav { border-radius: 10px; padding-left: 60px !important; }
}

/* ── Settings Modal ── */
@keyframes settingsSlideIn {
    from { opacity:0; transform:translateY(-30px) scale(.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}

.settings-tab {
    padding: 9px 16px;
    border: none; background: transparent;
    border-radius: 10px 10px 0 0;
    font-size: 13px; font-weight: 600;
    color: #888; cursor: pointer;
    display: flex; align-items: center; gap: 6px;
    transition: all .2s;
    border-bottom: 3px solid transparent;
    font-family: inherit;
}
.settings-tab:hover { color: #0f8156; background: rgba(15,129,86,.05); }
.settings-tab.active { color: #0f8156; border-bottom-color: #0f8156; background: rgba(15,129,86,.07); }

.settings-section { margin-bottom: 4px; }
.settings-section-header { display:flex; align-items:flex-start; gap:12px; }
.settings-section-icon { font-size:22px; flex-shrink:0; margin-top:1px; }
.settings-section-title { font-size:15px; font-weight:700; color:#1a1a1a; }
.settings-section-sub { font-size:12px; color:#999; margin-top:1px; }
.settings-divider { height:1px; background:rgba(0,0,0,0.06); margin:20px 0; }

/* Theme cards */
.theme-card {
    border: 2px solid rgba(0,0,0,0.1);
    border-radius: 12px; padding: 10px 8px;
    cursor: pointer; text-align: center;
    transition: all .2s;
}
.theme-card:hover { border-color: #0f8156; transform:translateY(-2px); }
.theme-card.active { border-color: #0f8156; box-shadow: 0 0 0 3px rgba(15,129,86,.15); }
.theme-preview {
    height: 52px; border-radius: 8px; padding: 8px;
    margin-bottom: 8px;
}
.theme-label { font-size: 12px; font-weight: 600; color: #555; }

/* Toggle switch */
.toggle-switch { position:relative; display:inline-block; width:46px; height:26px; flex-shrink:0; }
.toggle-switch input { opacity:0; width:0; height:0; }
.toggle-slider {
    position:absolute; cursor:pointer; inset:0;
    background:#ddd; border-radius:26px;
    transition:.3s;
}
.toggle-slider::before {
    content:''; position:absolute;
    width:20px; height:20px; border-radius:50%;
    background:#fff; left:3px; bottom:3px;
    transition:.3s; box-shadow:0 1px 4px rgba(0,0,0,.2);
}
.toggle-switch input:checked + .toggle-slider { background:linear-gradient(135deg,#23ed99,#0f8156); }
.toggle-switch input:checked + .toggle-slider::before { transform:translateX(20px); }

/* Screen reader radio options */
.sr-option {
    display:flex; align-items:center; gap:10px;
    padding:10px 14px; border-radius:10px;
    border:2px solid rgba(0,0,0,0.08);
    cursor:pointer; font-size:14px; font-weight:500; color:#444;
    transition:all .2s;
}
.sr-option:hover { border-color:#0f8156; background:rgba(15,129,86,.04); }
.sr-option input[type=radio] { accent-color:#0f8156; width:16px; height:16px; }
.sr-option input[type=radio]:checked ~ span { color:#0f8156; font-weight:700; }

/* ══════════════════════════════════════════════════════════════
   DARK MODE — comprehensive rules targeting all 8 pages
   Strategy: override CSS custom properties at :root level so
   every var(--bg), var(--panel), var(--ink), var(--muted) etc.
   automatically flips. Then patch hard-coded colours individually.
══════════════════════════════════════════════════════════════ */
body.dark-mode {
    /* ── CSS variable overrides (covers all pages that use :root vars) ── */
    --bg:        #0d0d14 !important;
    --panel:     #181824 !important;
    --ink:       #dde1ec !important;
    --muted:     #9094a6 !important;
    --brand:     #23ed99 !important;
    --brand-dark:#23ed99 !important;
    --ok:        #23ed99 !important;
    --warn:      #fbbf24 !important;
    --crit:      #f87171 !important;
    background:  #0d0d14 !important;
    color:       #dde1ec !important;
}

/* ── Top Nav ── */
body.dark-mode .top-nav {
    background: rgba(24,24,36,0.98) !important;
    box-shadow: 0 2px 12px rgba(0,0,0,.5) !important;
}
body.dark-mode .page-title { color: #23ed99 !important; }
body.dark-mode .datetime    { color: #23ed99 !important; }
body.dark-mode .btn-light   { background: #22223a !important; color: #dde1ec !important; border-color: #333 !important; }
body.dark-mode .dropdown-menu  { background: #1e1e30 !important; border-color: #333 !important; }
body.dark-mode .dropdown-item  { color: #c0c0d8 !important; }
body.dark-mode .dropdown-item:hover { background: rgba(35,237,153,.12) !important; color: #23ed99 !important; }
body.dark-mode .dropdown-item i { color: #23ed99 !important; }
body.dark-mode .text-muted  { color: #7a7e94 !important; }

/* ── Cards & panels (all pages) ── */
body.dark-mode .card,
body.dark-mode .chart-card,
body.dark-mode .weight-card,
body.dark-mode .plant-section,
body.dark-mode .fertilizer-calc-section,
body.dark-mode .tips-section,
body.dark-mode .tip-card,
body.dark-mode .calc-card,
body.dark-mode .user-plant-card,
body.dark-mode .stage-card-inner,
body.dark-mode .sensor-card,
body.dark-mode .sensor-pill,
body.dark-mode .param-chip,
body.dark-mode .rec-item,
body.dark-mode .benchmark-box,
body.dark-mode .weight-explainer,
body.dark-mode .history-item,
body.dark-mode .reset-modal,
body.dark-mode .success-modal,
body.dark-mode .modal-content,
body.dark-mode #settingsPanel  {
    background: #181824 !important;
    color:      #dde1ec !important;
    border-color: rgba(255,255,255,0.07) !important;
}
body.dark-mode .card { box-shadow: 0 6px 20px rgba(0,0,0,.4) !important; }

/* ── Text elements ── */
body.dark-mode h1, body.dark-mode h2, body.dark-mode h3,
body.dark-mode h4, body.dark-mode h5, body.dark-mode h6,
body.dark-mode .page-title,
body.dark-mode .section-title,
body.dark-mode .settings-section-title,
body.dark-mode .stat-value,
body.dark-mode .weight-value,
body.dark-mode .history-value,
body.dark-mode .kpi-value,
body.dark-mode .ring-pct,
body.dark-mode .stage-name  { color: #dde1ec !important; }

body.dark-mode .small-muted,
body.dark-mode .kpi-avg,
body.dark-mode .ring-sub,
body.dark-mode .stat-label,
body.dark-mode .weight-capacity,
body.dark-mode .history-time,
body.dark-mode .p-label,
body.dark-mode .settings-section-sub,
body.dark-mode .plant-scientific { color: #9094a6 !important; }

/* ── index.php – dashboard specific ── */
body.dark-mode .sensor-card   { background: #181824 !important; color: #dde1ec !important; }
body.dark-mode .sensor-card h3 { color: #9094a6 !important; }
body.dark-mode .sensor-card .value { color: #dde1ec !important; }
body.dark-mode .bar-container,
body.dark-mode .thermo-meter,
body.dark-mode .droplet-meter,
body.dark-mode .gas-meter,
body.dark-mode .ph-meter,
body.dark-mode .progress-bar-container,
body.dark-mode .wl-bar-wrap { background: #2a2a3e !important; }
body.dark-mode .unauthorized-message { background: #2a2a1a !important; border-color: #fbbf24 !important; color: #fbbf24 !important; }
body.dark-mode #sensorLockBadge { background: #2a1e0e !important; border-color: #fb923c !important; color: #fb923c !important; }

/* ── temperature.php ── */
body.dark-mode .temp-hero     { /* keep gradient */ box-shadow: 0 8px 32px rgba(0,0,0,.4) !important; }
body.dark-mode .alert-custom.warning  { background: #1e1800 !important; color: #fbbf24 !important; border-color: #fbbf24 !important; }
body.dark-mode .alert-custom.critical { background: #1e0808 !important; color: #f87171 !important; border-color: #f87171 !important; }
body.dark-mode .range-btn     { background: #22223a !important; border-color: #333 !important; color: #dde1ec !important; }
body.dark-mode .range-btn.active { background: #0f8156 !important; border-color: #0f8156 !important; color: #fff !important; }
body.dark-mode .thermo-visual { box-shadow: inset 0 0 20px rgba(0,0,0,.4) !important; }
body.dark-mode .thermo-indicator,
body.dark-mode .droplet-indicator { background: rgba(24,24,36,0.9) !important; }

/* ── humidity.php ── */
body.dark-mode .humidity-hero { box-shadow: 0 8px 32px rgba(0,0,0,.4) !important; }
body.dark-mode .droplet-visual { background: linear-gradient(to bottom,#0060bb,#0088cc,#00aadd) !important; }
body.dark-mode .alert-custom.critical { background: #1e0808 !important; }

/* ── gas.php ── */
body.dark-mode .gas-hero { box-shadow: 0 8px 32px rgba(0,0,0,.4) !important; }

/* ── ph.php ── */
body.dark-mode .ph-hero.ok       { /* keep green gradient */ }
body.dark-mode .ph-hero.acidic   { /* keep red gradient */ }
body.dark-mode .ph-hero.alkaline { /* keep blue gradient */ }
body.dark-mode .recommendation-popup { background: #181824 !important; color: #dde1ec !important; }
body.dark-mode .alert-custom.acidic   { background: #1e0808 !important; color: #f87171 !important; border-color: #f87171 !important; }
body.dark-mode .alert-custom.alkaline { background: #08081e !important; color: #93c5fd !important; border-color: #60a5fa !important; }

/* ── weight.php ── */
body.dark-mode .warning-popup { background: #181824 !important; color: #dde1ec !important; border-color: #f87171 !important; }
body.dark-mode .alert-custom.info { background: #081018 !important; color: #93c5fd !important; border-color: #60a5fa !important; }
body.dark-mode .weight-change-badge.decrease { background: #0a2218 !important; color: #23ed99 !important; }
body.dark-mode .weight-change-badge.increase { background: #1e0808 !important; color: #f87171 !important; }
body.dark-mode .weight-change-badge.stable   { background: #1e1e30 !important; color: #9094a6 !important; }
body.dark-mode .fertilizer-status.no-weight  { background: #1e1800 !important; color: #fbbf24 !important; }
body.dark-mode .fertilizer-status.composting { background: #0a2218 !important; color: #23ed99 !important; }
body.dark-mode .fertilizer-status.ready      { background: #0a2218 !important; color: #23ed99 !important; }
body.dark-mode .plot-size-badge { background: #0a2218 !important; color: #23ed99 !important; }

/* ── data.php (analytics) ── */
body.dark-mode .season-btn   { background: #1e1e30 !important; border-color: rgba(255,255,255,.1) !important; color: #dde1ec !important; }
body.dark-mode .season-btn.active { background: linear-gradient(135deg,#0f8156,#23ed99) !important; color: #fff !important; }
body.dark-mode .plant-card   { /* keep gradient */ }
body.dark-mode .modal-overlay { background: rgba(0,0,0,.85) !important; }
body.dark-mode .form-input,
body.dark-mode .form-select,
body.dark-mode .form-select-custom,
body.dark-mode select,
body.dark-mode textarea,
body.dark-mode input[type=number],
body.dark-mode input[type=text] { background: #1e1e30 !important; border-color: rgba(255,255,255,.12) !important; color: #dde1ec !important; }
body.dark-mode .info-item       { background: rgba(255,255,255,.04) !important; border-color: rgba(255,255,255,.08) !important; }
body.dark-mode .info-value      { color: #dde1ec !important; }
body.dark-mode .no-data         { color: #9094a6 !important; }

/* ── ai_advisor.php ── */
body.dark-mode .sensor-pill   { background: #181824 !important; border-top-color: #23ed99 !important; }
body.dark-mode .s-value       { color: #dde1ec !important; }
body.dark-mode .s-label       { color: #9094a6 !important; }
body.dark-mode .auto-card.ok  { background: #0a2218 !important; }
body.dark-mode .auto-card.warn{ background: #1e1600 !important; }
body.dark-mode .auto-card.crit{ background: #1e0808 !important; }
body.dark-mode .phase-aerobic  { background: #0a2218 !important; color: #23ed99 !important; border-color: #23ed99 !important; }
body.dark-mode .phase-anaerobic{ background: #1e1600 !important; color: #fbbf24 !important; border-color: #fbbf24 !important; }
body.dark-mode .outcome-pill.success { background: #0a2218 !important; color: #23ed99 !important; }
body.dark-mode .outcome-pill.partial { background: #1e1600 !important; color: #fbbf24 !important; }
body.dark-mode .outcome-pill.failed  { background: #1e0808 !important; color: #f87171 !important; }
body.dark-mode .weight-explainer { background: #11111e !important; border-color: rgba(255,255,255,.07) !important; }
body.dark-mode .weight-row     { color: #dde1ec !important; border-bottom-color: rgba(255,255,255,.06) !important; }

/* ── Settings modal in dark mode ── */
body.dark-mode #settingsPanel  { border: 1px solid rgba(255,255,255,0.08) !important; }
body.dark-mode .settings-divider { background: rgba(255,255,255,0.08) !important; }
body.dark-mode .theme-card     { border-color: rgba(255,255,255,0.12) !important; background: #11111e !important; }
body.dark-mode .theme-label    { color: #aaa !important; }
body.dark-mode .sr-option      { border-color: rgba(255,255,255,0.1) !important; color: #ccc !important; background: #11111e !important; }
body.dark-mode .settings-tab   { color: #888 !important; }
body.dark-mode .settings-tab.active { color: #23ed99 !important; border-bottom-color: #23ed99 !important; background: rgba(35,237,153,.07) !important; }

/* ── Bootstrap overrides ── */
body.dark-mode .form-control,
body.dark-mode .form-select { background: #1e1e30 !important; border-color: #333 !important; color: #dde1ec !important; }
body.dark-mode .form-control:focus,
body.dark-mode .form-select:focus { background: #22223a !important; border-color: #23ed99 !important; }
body.dark-mode .badge.bg-secondary { background: #2a2a3e !important; }
body.dark-mode .alert-success  { background: #0a2218 !important; color: #23ed99 !important; border-color: #23ed99 !important; }
body.dark-mode .alert-danger   { background: #1e0808 !important; color: #f87171 !important; border-color: #f87171 !important; }
body.dark-mode hr              { border-color: rgba(255,255,255,.1) !important; }
body.dark-mode .border         { border-color: rgba(255,255,255,.1) !important; }
/* Prevent Bootstrap's bg-white from overriding */
body.dark-mode .bg-white       { background: #181824 !important; }

/* ── High Contrast ── */
body.high-contrast { filter: contrast(1.4); }
body.high-contrast .dot.online  { background:#00ff44; outline:2px solid #fff; }
body.high-contrast .dot.delayed { background:#ffdd00; outline:2px solid #fff; }
body.high-contrast .dot.offline { background:#ff2200; outline:2px solid #fff; }

/* ── Reduce Motion ── */
body.reduce-motion *, body.reduce-motion *::before, body.reduce-motion *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
}
</style>

<!-- ===== SCRIPTS ===== -->
<script>
/* ── DateTime ── */
function updateDateTime(){
    const now = new Date();
    const options = { month:'short', day:'numeric', year:'numeric', hour:'2-digit', minute:'2-digit' };
    const str = now.toLocaleString('en-US', options);
    const el = document.getElementById('dateTime');
    const elM = document.getElementById('dateTimeMobile');
    if (el) el.textContent = str;
    if (elM) elM.textContent = str;
}
setInterval(updateDateTime, 1000);
updateDateTime();

/* ── Sensor refresh ── */
function updateFaultyCount() {
    const faultyDots = document.querySelectorAll('#sensorDropdown + .dropdown-menu .dot.faulty');
    const count = faultyDots.length;
    const el = document.getElementById('faultyText');
    if (!el) return;
    el.innerHTML = `
        <span class="d-none d-sm-inline">⚠️ ${count} Faulty Sensor${count!==1?'s':''}</span>
        <span class="d-inline d-sm-none" title="Faulty sensors">⚠️ ${count}</span>
    `;
}

async function refreshSensors(){
    try{
        const res = await fetch('sensor_status.php');
        const data = await res.json();
        const dropdown = document.querySelector('#sensorDropdown + .dropdown-menu');
        if (!dropdown) return;
        dropdown.innerHTML = '';
        data.forEach(sensor => {
            const iconMap = {'Temperature':'🌡️','Humidity':'💧','Gas':'💨','pH':'⚗️'};
            const icon = iconMap[sensor.name]||'📡';
            const timeAgo = isNaN(sensor.lastUpdate)?sensor.lastUpdate:sensor.lastUpdate+'s ago';
            dropdown.innerHTML += `
                <li>
                    <div class="dropdown-item d-flex justify-content-between align-items-center gap-2">
                        <span>${icon} ${sensor.name}</span>
                        <span class="dot ${sensor.class}"></span>
                        <small class="text-muted ms-auto">${timeAgo}</small>
                    </div>
                </li>
            `;
        });
        updateFaultyCount();
    } catch(e){ console.error('Sensor fetch error', e); }
}
setInterval(refreshSensors, 3000);

/* ── Hide topnav on scroll down ── */
let lastScroll = 0;
const topNav = document.getElementById('topNav');
window.addEventListener('scroll', () => {
    const cur = window.pageYOffset || document.documentElement.scrollTop;
    if (cur > lastScroll && cur > 60) topNav.classList.add('hidden');
    else topNav.classList.remove('hidden');
    lastScroll = cur <= 0 ? 0 : cur;
});

/* ── Profile picture sync ── */
function setTopNavAvatar(profilePicture, displayName) {
    const avatarEl = document.getElementById('topNavAvatar');
    if (!avatarEl) return;
    if (profilePicture) {
        avatarEl.innerHTML = `<img src="${profilePicture}" alt="Profile">`;
    } else {
        const initial = (displayName || 'U').charAt(0).toUpperCase();
        avatarEl.innerHTML = `<span id="topNavInitial">${initial}</span>`;
    }
}

(function injectTopNavFirebaseListener() {
    let attempts = 0;
    function tryUseExistingInstance() {
        attempts++;
        if (window.firebaseAuth && window.firebaseDatabase && window.firebaseRef && window.firebaseOnValue) {
            attachTopNavListener(window.firebaseAuth, window.firebaseDatabase, window.firebaseRef, window.firebaseOnValue);
        } else if (attempts < 20) {
            setTimeout(tryUseExistingInstance, 100);
        } else {
            bootstrapOwnFirebaseInstance();
        }
    }
    tryUseExistingInstance();
})();

function attachTopNavListener(auth, database, ref, onValue) {
    const tryAttach = () => {
        const userId = sessionStorage.getItem('userId');
        if (!userId) {
            setTimeout(() => {
                const uid = sessionStorage.getItem('userId');
                if (uid) loadTopNavProfile(database, ref, onValue, uid);
            }, 800);
            return;
        }
        loadTopNavProfile(database, ref, onValue, userId);
    };
    if (typeof auth.onAuthStateChanged === 'function') {
        auth.onAuthStateChanged((user) => {
            if (user) loadTopNavProfile(database, ref, onValue, user.uid);
        });
    } else {
        tryAttach();
    }
}

function loadTopNavProfile(database, ref, onValue, userId) {
    const userRef = ref(database, `users/${userId}`);
    onValue(userRef, (snapshot) => {
        const data = snapshot.val();
        if (data) setTopNavAvatar(data.profilePicture || null, data.name || data.google || '');
    }, { onlyOnce: true });
}

function bootstrapOwnFirebaseInstance() {
    const script = document.createElement('script');
    script.type = 'module';
    script.textContent = `
        import { initializeApp, getApps } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
        import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
        import { getDatabase, ref, onValue } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';
        const firebaseConfig = {
            apiKey: "AIzaSyAu9hOwjiuAl9PCh50HefMGZU9XDosu68I",
            authDomain: "wattawaste-d3503.firebaseapp.com",
            databaseURL: "https://wattawaste-d3503-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "wattawaste-d3503",
            storageBucket: "wattawaste-d3503.firebasestorage.app",
            messagingSenderId: "842761118644",
            appId: "1:842761118644:web:ddef65fd892486f67f88e1"
        };
        const app = getApps().length ? getApps()[0] : initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const db   = getDatabase(app);
        onAuthStateChanged(auth, (user) => {
            if (!user) return;
            const userRef = ref(db, 'users/' + user.uid);
            onValue(userRef, (snapshot) => {
                const data = snapshot.val();
                if (data) window.setTopNavAvatar(data.profilePicture || null, data.name || data.google || '');
            }, { onlyOnce: true });
        });
    `;
    document.head.appendChild(script);
}

/* ═══════════════════════════════════════
   SETTINGS MODAL
═══════════════════════════════════════ */
const SETTINGS_KEY = 'leafcycle_settings';

function getSettings() {
    try { return JSON.parse(localStorage.getItem(SETTINGS_KEY)) || {}; } catch(e) { return {}; }
}
function saveSettings(patch) {
    const current = getSettings();
    localStorage.setItem(SETTINGS_KEY, JSON.stringify({ ...current, ...patch }));
}

/* Open / Close */
function openSettingsModal() {
    loadSettingsIntoUI();
    const modal = document.getElementById('settingsModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeSettingsModal() {
    document.getElementById('settingsModal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('settingsModal').addEventListener('click', function(e){
    if (e.target === this) closeSettingsModal();
});

/* Tab switching */
function switchSettingsTab(tab, btn) {
    document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.settings-tab-content').forEach(c => c.style.display = 'none');
    btn.classList.add('active');
    document.getElementById('settingsTab-' + tab).style.display = 'block';
}

/* Load saved settings into UI controls */
function loadSettingsIntoUI() {
    const s = getSettings();

    // Theme
    const theme = s.theme || 'system';
    document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('active'));
    const tc = document.getElementById('theme-' + theme);
    if (tc) tc.classList.add('active');

    // Language
    const langEl = document.getElementById('languageSelect');
    if (langEl) langEl.value = s.language || 'en';

    // Font size
    const fs = s.fontSize || 100;
    const slider = document.getElementById('fontSizeSlider');
    if (slider) { slider.value = fs; updateFontSizeLabel(fs); }

    // High contrast
    const hc = document.getElementById('highContrastToggle');
    if (hc) hc.checked = !!s.highContrast;

    // Reduce motion
    const rm = document.getElementById('reduceMotionToggle');
    if (rm) rm.checked = !!s.reduceMotion;

    // Screen reader
    const srVal = s.screenReader || 'none';
    const srRadio = document.querySelector(`input[name="srMode"][value="${srVal}"]`);
    if (srRadio) srRadio.checked = true;
}

/* ── Theme ── */
function setTheme(theme) {
    document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('active'));
    const card = document.getElementById('theme-' + theme);
    if (card) card.classList.add('active');
    saveSettings({ theme });
    applyTheme(theme);
}

function applyTheme(theme) {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = theme === 'dark' || (theme === 'system' && prefersDark);
    document.body.classList.toggle('dark-mode', isDark);
}

/* ══════════════════════════════════════════════════════════════
   LANGUAGE — Runtime i18n engine
   Walks every text node in the document and replaces known
   strings. Covers topnav + all 8 pages.
   Call applyLanguage(lang) at any time to re-translate.
══════════════════════════════════════════════════════════════ */
const I18N = {
    en: {}, // English is the source — no mapping needed

    fil: {
        // Nav / settings
        'Profile': 'Profile',
        'Settings': 'Mga Setting',
        'Sensor Status': 'Status ng Sensor',
        'Faulty Sensor': 'Sirang Sensor',
        'Faulty Sensors': 'Mga Sirang Sensor',
        // Dashboard
        'Dashboard': 'Dashboard',
        'Temperature': 'Temperatura',
        'Humidity': 'Halumigmig',
        'Gas Level': 'Antas ng Gas',
        'pH Level': 'Antas ng pH',
        'Weight': 'Timbang',
        'Data Analytics': 'Pagsusuri ng Data',
        'AI Advisor': 'AI Tagapayo',
        'Compost Stage': 'Yugto ng Compost',
        'No Compost': 'Walang Compost',
        'Awaiting compost': 'Naghihintay ng compost',
        'Current Waste Weight': 'Kasalukuyang Bigat ng Basura',
        'Fertilizer Output': 'Output ng Pataba',
        'Predicted': 'Hinuhulaan',
        'Actual': 'Aktwal',
        'Waiting for compost': 'Naghihintay ng compost',
        'Not ready yet': 'Hindi pa handa',
        'Sensor History': 'Kasaysayan ng Sensor',
        'Mixer is ON': 'Naka-ON ang Mixer',
        'Mixer is OFF': 'Naka-OFF ang Mixer',
        'Logout': 'Mag-logout',
        // Temperature page
        'Temperature Monitoring': 'Pagsubaybay ng Temperatura',
        'Visual Indicator': 'Visual na Tagapagpahiwatig',
        'Maximum': 'Pinakamataas',
        'Average': 'Karaniwan',
        'Minimum': 'Pinakamababa',
        'Temperature Trend': 'Trend ng Temperatura',
        'Recent Live Readings': 'Kamakailang Mga Pagbabasa',
        // Humidity page
        'Humidity Monitoring': 'Pagsubaybay ng Halumigmig',
        'Moisture Level': 'Antas ng Kahalumigmigan',
        'Humidity Trend': 'Trend ng Halumigmig',
        // Gas page
        'Gas Level Monitoring': 'Pagsubaybay ng Antas ng Gas',
        'Gas Emission': 'Paglabas ng Gas',
        'Gas Level Trend': 'Trend ng Antas ng Gas',
        // pH page
        'pH Level Monitoring': 'Pagsubaybay ng Antas ng pH',
        // Weight page
        'Weight Monitoring': 'Pagsubaybay ng Timbang',
        'Current Weight': 'Kasalukuyang Timbang',
        'Total Compost Fertilizer': 'Kabuuang Pataba ng Compost',
        'Weight Trend': 'Trend ng Timbang',
        // Data Analytics
        'Data Analytics Overview': 'Pangkalahatang Pagsusuri ng Data',
        'Philippine Seasonal Plants': 'Mga Pananim sa Pilipinas',
        'My Garden': 'Aking Hardin',
        'Fertilizer Calculator': 'Kalkulator ng Pataba',
        'Available Fertilizer': 'Naaangkop na Pataba',
        'Total Required': 'Kabuuang Kinakailangan',
        'Balance': 'Balanse',
        'How to Prevent Fertilizer Loss': 'Paano Maiwasan ang Pagkawala ng Pataba',
        // AI Advisor
        'AI Compost Advisor': 'AI Tagapayo ng Compost',
        'Compost Stage': 'Yugto ng Compost',
        'Automated Control Decisions': 'Mga Awtomatikong Desisyon sa Kontrol',
        'AI Recommendations': 'Mga Rekomendasyon ng AI',
        'Adaptive Parameters': 'Mga Adaptive na Parameter',
        // Common
        'Loading...': 'Naglo-load...',
        'No data available': 'Walang available na data',
        'Refresh': 'I-refresh',
        'Save': 'I-save',
        'Cancel': 'Kanselahin',
        'Last updated': 'Huling na-update',
        'readings': 'mga pagbabasa',
    },

    ceb: {
        'Profile': 'Profile',
        'Settings': 'Mga Setting',
        'Sensor Status': 'Status sa Sensor',
        'Temperature': 'Temperatura',
        'Humidity': 'Humedad',
        'Gas Level': 'Lebel sa Gas',
        'pH Level': 'Lebel sa pH',
        'Weight': 'Timbang',
        'Data Analytics': 'Pagtuki sa Data',
        'AI Advisor': 'AI Magtatambag',
        'Dashboard': 'Dashboard',
        'Logout': 'Logout',
        'Loading...': 'Nagkarga...',
    },

    es: {
        'Profile': 'Perfil',
        'Settings': 'Configuración',
        'Sensor Status': 'Estado del Sensor',
        'Faulty Sensor': 'Sensor Defectuoso',
        'Faulty Sensors': 'Sensores Defectuosos',
        'Dashboard': 'Panel',
        'Temperature': 'Temperatura',
        'Humidity': 'Humedad',
        'Gas Level': 'Nivel de Gas',
        'pH Level': 'Nivel de pH',
        'Weight': 'Peso',
        'Data Analytics': 'Análisis de Datos',
        'AI Advisor': 'Asesor de IA',
        'Compost Stage': 'Etapa del Compost',
        'No Compost': 'Sin Compost',
        'Awaiting compost': 'Esperando compost',
        'Current Waste Weight': 'Peso Actual de Residuos',
        'Fertilizer Output': 'Producción de Fertilizante',
        'Predicted': 'Predicho',
        'Actual': 'Actual',
        'Not ready yet': 'Aún no está listo',
        'Waiting for compost': 'Esperando compost',
        'Temperature Monitoring': 'Monitoreo de Temperatura',
        'Humidity Monitoring': 'Monitoreo de Humedad',
        'Gas Level Monitoring': 'Monitoreo de Nivel de Gas',
        'pH Level Monitoring': 'Monitoreo del Nivel de pH',
        'Weight Monitoring': 'Monitoreo de Peso',
        'Data Analytics Overview': 'Resumen de Análisis de Datos',
        'Loading...': 'Cargando...',
        'Logout': 'Cerrar sesión',
        'Maximum': 'Máximo',
        'Average': 'Promedio',
        'Minimum': 'Mínimo',
        'Recent Live Readings': 'Lecturas Recientes en Vivo',
        'Available Fertilizer': 'Fertilizante Disponible',
        'Total Required': 'Total Requerido',
        'Balance': 'Balance',
        'My Garden': 'Mi Jardín',
        'Refresh': 'Actualizar',
        'Last updated': 'Última actualización',
    },

    zh: {
        'Profile': '个人资料',
        'Settings': '设置',
        'Sensor Status': '传感器状态',
        'Faulty Sensor': '故障传感器',
        'Faulty Sensors': '故障传感器',
        'Dashboard': '仪表盘',
        'Temperature': '温度',
        'Humidity': '湿度',
        'Gas Level': '气体水平',
        'pH Level': 'pH值',
        'Weight': '重量',
        'Data Analytics': '数据分析',
        'AI Advisor': 'AI顾问',
        'Compost Stage': '堆肥阶段',
        'No Compost': '无堆肥',
        'Loading...': '加载中...',
        'Logout': '退出',
        'Maximum': '最大值',
        'Average': '平均值',
        'Minimum': '最小值',
        'Available Fertilizer': '可用肥料',
        'Total Required': '所需总量',
        'Balance': '余额',
        'My Garden': '我的花园',
    },

    ja: {
        'Profile': 'プロフィール',
        'Settings': '設定',
        'Sensor Status': 'センサー状態',
        'Faulty Sensor': '故障センサー',
        'Faulty Sensors': '故障センサー',
        'Dashboard': 'ダッシュボード',
        'Temperature': '温度',
        'Humidity': '湿度',
        'Gas Level': 'ガスレベル',
        'pH Level': 'pHレベル',
        'Weight': '重量',
        'Data Analytics': 'データ分析',
        'AI Advisor': 'AIアドバイザー',
        'Loading...': '読み込み中...',
        'Logout': 'ログアウト',
        'Maximum': '最大値',
        'Average': '平均',
        'Minimum': '最小値',
        'Available Fertilizer': '利用可能な肥料',
        'Total Required': '必要合計',
        'Balance': '残高',
        'My Garden': '私の庭',
    },

    ko: {
        'Profile': '프로필',
        'Settings': '설정',
        'Sensor Status': '센서 상태',
        'Faulty Sensor': '결함 센서',
        'Faulty Sensors': '결함 센서',
        'Dashboard': '대시보드',
        'Temperature': '온도',
        'Humidity': '습도',
        'Gas Level': '가스 수준',
        'pH Level': 'pH 수준',
        'Weight': '무게',
        'Data Analytics': '데이터 분석',
        'AI Advisor': 'AI 어드바이저',
        'Loading...': '로딩 중...',
        'Logout': '로그아웃',
        'Maximum': '최대',
        'Average': '평균',
        'Minimum': '최소',
        'Available Fertilizer': '사용 가능한 비료',
        'Total Required': '총 필요량',
        'Balance': '균형',
        'My Garden': '내 정원',
    },

    fr: {
        'Profile': 'Profil',
        'Settings': 'Paramètres',
        'Sensor Status': 'État des Capteurs',
        'Faulty Sensor': 'Capteur Défectueux',
        'Faulty Sensors': 'Capteurs Défectueux',
        'Dashboard': 'Tableau de Bord',
        'Temperature': 'Température',
        'Humidity': 'Humidité',
        'Gas Level': 'Niveau de Gaz',
        'pH Level': 'Niveau de pH',
        'Weight': 'Poids',
        'Data Analytics': 'Analyse des Données',
        'AI Advisor': 'Conseiller IA',
        'Compost Stage': 'Étape du Compost',
        'No Compost': 'Aucun Compost',
        'Loading...': 'Chargement...',
        'Logout': 'Déconnexion',
        'Maximum': 'Maximum',
        'Average': 'Moyenne',
        'Minimum': 'Minimum',
        'Recent Live Readings': 'Lectures Récentes en Direct',
        'Available Fertilizer': 'Engrais Disponible',
        'Total Required': 'Total Requis',
        'Balance': 'Bilan',
        'My Garden': 'Mon Jardin',
        'Refresh': 'Actualiser',
        'Last updated': 'Dernière mise à jour',
    },
};

/**
 * Walk all text nodes under `root` (default: document.body)
 * and swap any matching source text for the target language translation.
 * We operate on text nodes so we never mangle HTML attributes or tags.
 */
function applyLanguage(lang) {
    saveSettings({ language: lang });

    const dict = I18N[lang];
    if (!dict || Object.keys(dict).length === 0) {
        // English or unknown → just restore originals
        applyLanguage._restore();
        return;
    }

    // Build a flat map sorted longest-first so "Faulty Sensors" matches before "Faulty Sensor"
    const entries = Object.entries(dict).sort((a,b) => b[0].length - a[0].length);

    // First restore to English so we always translate from a clean base
    applyLanguage._restore();

    // Walk text nodes
    const walker = document.createTreeWalker(
        document.body,
        NodeFilter.SHOW_TEXT,
        {
            acceptNode: (node) => {
                // Skip script, style, noscript, code, pre
                const pTag = node.parentElement?.tagName;
                if (['SCRIPT','STYLE','NOSCRIPT','CODE','PRE','INPUT','TEXTAREA'].includes(pTag))
                    return NodeFilter.FILTER_REJECT;
                return NodeFilter.FILTER_ACCEPT;
            }
        }
    );

    const patches = [];
    let node;
    while ((node = walker.nextNode())) {
        const orig = node.textContent;
        let text = orig;
        for (const [src, tgt] of entries) {
            if (text.includes(src)) text = text.split(src).join(tgt);
        }
        if (text !== orig) patches.push({ node, orig, text });
    }

    // Also translate placeholder and aria-label attributes
    document.querySelectorAll('[placeholder]').forEach(el => {
        for (const [src, tgt] of entries) {
            if (el.placeholder.includes(src)) {
                if (!el._origPlaceholder) el._origPlaceholder = el.placeholder;
                el.placeholder = el.placeholder.split(src).join(tgt);
            }
        }
    });

    // Apply patches and store originals for restoration
    applyLanguage._originals = patches;
    patches.forEach(p => { p.node.textContent = p.text; });
}
applyLanguage._originals = [];
applyLanguage._restore   = function() {
    applyLanguage._originals.forEach(p => { try { p.node.textContent = p.orig; } catch(e){} });
    applyLanguage._originals = [];
    // Restore placeholders
    document.querySelectorAll('[_origPlaceholder]').forEach(el => {
        el.placeholder = el._origPlaceholder;
        delete el._origPlaceholder;
    });
};

/* Alias used by the select dropdown */
function setLanguage(lang) { applyLanguage(lang); }

/* ── Font Size ── */
function applyFontSize(val) {
    const size = parseInt(val);
    updateFontSizeLabel(size);
    document.documentElement.style.fontSize = size + '%';
    saveSettings({ fontSize: size });
}

function updateFontSizeLabel(val) {
    const label = document.getElementById('fontSizeLabel');
    if (label) label.textContent = val + '%';
    const preview = document.getElementById('fontPreviewText');
    if (preview) preview.style.fontSize = (val / 100 * 13) + 'px';
}

/* ── High Contrast ── */
function applyHighContrast(enabled) {
    document.body.classList.toggle('high-contrast', enabled);
    saveSettings({ highContrast: enabled });
}

/* ── Reduce Motion ── */
function applyReduceMotion(enabled) {
    document.body.classList.toggle('reduce-motion', enabled);
    saveSettings({ reduceMotion: enabled });
}

/* ── Screen Reader ── */
function applyScreenReader(mode) {
    saveSettings({ screenReader: mode });
    if (mode === 'simplified') {
        document.body.setAttribute('data-sr-mode', 'simplified');
    } else if (mode === 'aria') {
        document.body.setAttribute('data-sr-mode', 'aria');
        // Enhance focus visibility
        document.body.classList.add('enhanced-focus');
    } else {
        document.body.removeAttribute('data-sr-mode');
        document.body.classList.remove('enhanced-focus');
    }
}

/* ── Reset all ── */
function resetAllSettings() {
    localStorage.removeItem(SETTINGS_KEY);
    document.body.classList.remove('dark-mode','high-contrast','reduce-motion','enhanced-focus');
    document.body.removeAttribute('data-sr-mode');
    document.documentElement.style.fontSize = '';
    loadSettingsIntoUI();
}

/* ── Apply settings on page load ── */
(function applyStoredSettings() {
    const s = getSettings();
    if (s.theme)        applyTheme(s.theme);
    if (s.fontSize)     { document.documentElement.style.fontSize = s.fontSize + '%'; }
    if (s.highContrast) document.body.classList.add('high-contrast');
    if (s.reduceMotion) document.body.classList.add('reduce-motion');
    if (s.screenReader) applyScreenReader(s.screenReader);
    // Apply saved language after DOM is fully ready
    if (s.language && s.language !== 'en') {
        // Wait for the page's own scripts to finish rendering dynamic content
        window.addEventListener('DOMContentLoaded', () => setTimeout(() => applyLanguage(s.language), 400));
        // Also re-apply after a short delay for Firebase-injected content
        setTimeout(() => applyLanguage(s.language), 1500);
    }
    // Re-apply on system theme change
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const current = getSettings();
        if (current.theme === 'system') applyTheme('system');
    });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>