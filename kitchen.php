<?php
// kitchen.php - Kitchen Display System (KDS): live tickets for the kitchen screen
header('Content-Type: text/html; charset=UTF-8');
require_once 'auth.php';
requireAuth();
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="my">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>မီးဖိုချောင် - SmartOrder</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+JP:wght@400;500;700;900&family=Noto+Sans+Myanmar:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/modern-admin.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body.page-kds{min-height:100vh}
.kds-wrap{max-width:1600px;margin:0 auto;padding:0 24px 32px}
.kds-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px}
.kds-head h1{font-size:1.75rem;font-weight:800;letter-spacing:-.035em}
.kds-head p{color:var(--m-text3);font-size:.9rem;margin-top:4px}
.kds-tools{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.kds-clock{font-size:1.5rem;font-weight:800;letter-spacing:-.03em;font-variant-numeric:tabular-nums;margin-right:6px}
.kds-status{display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border-radius:999px;font-size:.78rem;font-weight:600;background:var(--m-green-soft);color:var(--m-green)}
.kds-status .dot{width:7px;height:7px;border-radius:50%;background:currentColor;animation:kdsPulse 2s infinite}
.kds-status.err{background:var(--m-red-soft);color:var(--m-red)}
@keyframes kdsPulse{50%{opacity:.35}}
.kds-btn{padding:8px 14px;border:1px solid var(--m-line);border-radius:10px;background:var(--m-surface);color:var(--m-ink);font:inherit;font-size:.82rem;font-weight:600;cursor:pointer}
.kds-btn:hover{border-color:var(--m-ink)}
.kds-btn.on{background:var(--m-ink);color:#fff;border-color:var(--m-ink)}

.kds-board{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;align-items:start}
.kds-col{background:var(--m-muted);border-radius:20px;padding:12px;min-height:60vh}
.kds-col-head{display:flex;align-items:center;justify-content:space-between;padding:6px 8px 12px}
.kds-col-title{display:flex;align-items:center;gap:8px;font-weight:800;font-size:1.05rem;letter-spacing:-.02em}
.kds-col-title i{width:10px;height:10px;border-radius:50%;display:inline-block}
.col-new i{background:var(--m-accent)}
.col-cooking i{background:#e5a50a}
.col-ready i{background:var(--m-green)}
.kds-count{min-width:30px;height:30px;padding:0 9px;border-radius:999px;background:var(--m-surface);font-weight:800;display:flex;align-items:center;justify-content:center;font-variant-numeric:tabular-nums}
.kds-list{display:flex;flex-direction:column;gap:12px}
.kds-empty{text-align:center;color:var(--m-text3);font-size:.88rem;padding:40px 10px}

.ticket{background:var(--m-surface);border-radius:16px;border:1px solid var(--m-line);overflow:hidden;animation:tIn .3s ease}
@keyframes tIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
.ticket.fresh{box-shadow:0 0 0 3px var(--m-accent);animation:tIn .3s ease,tFlash 1.2s ease 3}
@keyframes tFlash{50%{box-shadow:0 0 0 6px rgba(255,90,54,.25)}}
.t-top{display:flex;align-items:center;justify-content:space-between;padding:14px 16px 10px}
.t-table{font-size:1.5rem;font-weight:900;letter-spacing:-.03em}
.t-table small{font-size:.75rem;font-weight:600;color:var(--m-text3);margin-left:6px;letter-spacing:0}
.t-time{font-size:1.15rem;font-weight:800;font-variant-numeric:tabular-nums;padding:4px 10px;border-radius:9px;background:var(--m-muted)}
.t-time.warn{background:#fdf3dc;color:#a86b00}
.t-time.late{background:var(--m-red);color:#fff}
.t-items{list-style:none;padding:4px 16px 12px;border-top:1px dashed var(--m-line)}
.t-item{display:flex;gap:10px;align-items:flex-start;padding:9px 0;border-bottom:1px solid var(--m-bg)}
.t-item:last-child{border-bottom:none}
.t-qty{min-width:34px;height:30px;border-radius:8px;background:var(--m-ink);color:#fff;font-weight:800;display:flex;align-items:center;justify-content:center;font-variant-numeric:tabular-nums}
.t-name{font-size:1.05rem;font-weight:700;line-height:1.35;padding-top:3px}
.t-icon{margin-right:4px}
.t-note{display:inline-block;margin-top:5px;font-size:.85rem;font-weight:700;color:var(--m-accent-dark,#c8401f);background:var(--m-accent-soft);padding:3px 8px;border-radius:7px}
.t-actions{display:flex;gap:8px;padding:0 12px 12px}
.t-next{flex:1;padding:13px;border:none;border-radius:12px;font:inherit;font-size:1rem;font-weight:800;cursor:pointer;color:#fff;background:var(--m-ink)}
.col-new .t-next{background:var(--m-accent)}
.col-ready .t-next{background:var(--m-green)}
.t-next:active{transform:scale(.98)}
.t-back{padding:13px 14px;border:1px solid var(--m-line);border-radius:12px;background:var(--m-surface);font:inherit;font-weight:700;color:var(--m-text2);cursor:pointer}
.t-back:hover{border-color:var(--m-ink);color:var(--m-ink)}
.t-next:disabled,.t-back:disabled{opacity:.5;cursor:wait}

.kds-toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);background:var(--m-ink);color:#fff;padding:11px 18px;border-radius:999px;font-weight:600;font-size:.9rem;box-shadow:var(--m-shadow-lg);opacity:0;transition:opacity .2s;pointer-events:none;z-index:900}
.kds-toast.show{opacity:1}

/* Fullscreen kitchen-TV mode: hide the admin bar, bigger type */
body.tv .m-topbar{display:none}
body.tv .kds-wrap{padding-top:20px;max-width:none}
body.tv .t-name{font-size:1.2rem}

@media(max-width:1000px){.kds-board{grid-template-columns:1fr}.kds-col{min-height:0}}
@media(max-width:720px){.kds-wrap{padding:0 14px 24px}.kds-head h1{font-size:1.4rem}.kds-clock{font-size:1.2rem}}
</style>
</head>
<body class="page-mgmt page-kds">
<header class="m-topbar"><div class="m-topbar-inner">
  <a class="m-brand" href="management.php"><span class="m-logo">S</span><span><div class="m-brand-name">SmartOrder</div><div class="m-brand-sub">စီမံခန့်ခွဲမှု</div></span></a>
  <nav class="m-nav"><a href="management.php">အော်ဒါ စီမံရန်</a><a href="kitchen.php" class="active">မီးဖိုချောင်</a><a href="admin.php">မီနူး စီမံရန်</a></nav>
  <div class="m-actions"><a class="m-btn-ghost" href="index.php?tableNo=1" target="_blank">ဖောက်သည် စခရင် ↗</a><a class="m-btn-ghost" href="logout.php">ထွက်ရန်</a></div>
</div></header>

<div class="kds-wrap">
  <div class="kds-head">
    <div><h1>မီးဖိုချောင် မော်နီတာ</h1><p>အော်ဒါအသစ်တွေကို အချိန်နဲ့တပြေးညီ ပြပါတယ်</p></div>
    <div class="kds-tools">
      <span class="kds-clock" id="kdsClock">--:--</span>
      <span class="kds-status" id="kdsStatus"><span class="dot"></span><span id="kdsStatusText">ချိတ်ဆက်နေသည်…</span></span>
      <button class="kds-btn" id="soundBtn" type="button" onclick="toggleSound()">🔕 အသံ OFF</button>
      <button class="kds-btn" type="button" onclick="toggleTV()">⛶ မျက်နှာပြင်အပြည့်</button>
    </div>
  </div>

  <div class="kds-board">
    <section class="kds-col col-new">
      <div class="kds-col-head"><div class="kds-col-title"><i></i>အသစ်</div><div class="kds-count" id="count0">0</div></div>
      <div class="kds-list" id="list0"></div>
    </section>
    <section class="kds-col col-cooking">
      <div class="kds-col-head"><div class="kds-col-title"><i></i>ချက်နေဆဲ</div><div class="kds-count" id="count1">0</div></div>
      <div class="kds-list" id="list1"></div>
    </section>
    <section class="kds-col col-ready">
      <div class="kds-col-head"><div class="kds-col-title"><i></i>ပြီးပြီ / ချပေးရန်</div><div class="kds-count" id="count2">0</div></div>
      <div class="kds-list" id="list2"></div>
    </section>
  </div>
</div>
<div class="kds-toast" id="kdsToast"></div>

<script>
const CSRF = <?php echo json_encode($csrfToken); ?>;
const POLL_MS = 3000;
const NEXT_LABEL = ['ချက်မယ် →', 'ပြီးပြီ ✓', 'ချပေးပြီး ✓'];
const EMPTY_LABEL = ['အော်ဒါအသစ် မရှိပါ', 'ချက်နေတဲ့ အော်ဒါ မရှိပါ', 'ချပေးရန် မရှိပါ'];
let tickets = [];          // latest tickets from the server
let fetchedAt = Date.now(); // when "elapsed" values were measured
let known = null;          // orderNos already seen (null until first load)
let fresh = new Set();     // orderNos to highlight as new
let soundOn = false, audioCtx = null, busy = new Set();

const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fmt = sec => { sec = Math.max(0, sec|0); const m = Math.floor(sec/60), s = sec%60; return (m<10?'0':'')+m+':'+(s<10?'0':'')+s; };

function render(){
  const lists = [[],[],[]];
  tickets.forEach(t => lists[Math.min(2, Math.max(0, t.state))].push(t));
  lists.forEach((arr, col) => {
    document.getElementById('count'+col).textContent = arr.length;
    const el = document.getElementById('list'+col);
    if (!arr.length) { el.innerHTML = '<div class="kds-empty">'+EMPTY_LABEL[col]+'</div>'; return; }
    el.innerHTML = arr.map(t => {
      const items = t.items.map(i =>
        '<li class="t-item"><span class="t-qty">'+i.qty+'</span><div><div class="t-name"><span class="t-icon">'+esc(i.icon)+'</span>'+esc(i.name)+'</div>'
        + (i.notes ? '<span class="t-note">📝 '+esc(i.notes)+'</span>' : '') + '</div></li>').join('');
      const dis = busy.has(t.orderNo) ? ' disabled' : '';
      return '<article class="ticket'+(fresh.has(t.orderNo)?' fresh':'')+'" data-order="'+esc(t.orderNo)+'">'
        + '<div class="t-top"><div class="t-table">Table '+t.tableNo+'<small>#'+esc(String(t.orderNo).slice(-4))+'</small></div>'
        + '<div class="t-time" data-elapsed="'+t.elapsed+'">'+fmt(t.elapsed)+'</div></div>'
        + '<ul class="t-items">'+items+'</ul>'
        + '<div class="t-actions">'
        + (t.state > 0 ? '<button class="t-back" type="button" title="တစ်ဆင့် နောက်ပြန်ရန်" onclick="setState(\''+esc(t.orderNo)+'\','+(t.state-1)+')"'+dis+'>↩</button>' : '')
        + '<button class="t-next" type="button" onclick="setState(\''+esc(t.orderNo)+'\','+(t.state+1)+')"'+dis+'>'+NEXT_LABEL[t.state]+'</button>'
        + '</div></article>';
    }).join('');
  });
  tick();
}

// Update timers every second without re-rendering the whole board
function tick(){
  const since = (Date.now() - fetchedAt) / 1000;
  document.querySelectorAll('.t-time').forEach(el => {
    const sec = +el.dataset.elapsed + since;
    el.textContent = fmt(sec);
    el.classList.toggle('warn', sec >= 600 && sec < 1200);
    el.classList.toggle('late', sec >= 1200);
  });
  const d = new Date();
  document.getElementById('kdsClock').textContent = d.toLocaleTimeString('ja-JP', {hour:'2-digit', minute:'2-digit'});
}

function setStatus(ok, text){
  const s = document.getElementById('kdsStatus');
  s.classList.toggle('err', !ok);
  document.getElementById('kdsStatusText').textContent = text;
}

async function load(){
  try {
    const res = await fetch('kitchen_api.php', {cache:'no-store', credentials:'same-origin'});
    if (res.status === 403) { setStatus(false, 'Login သက်တမ်း ကုန်သွားပါပြီ'); setTimeout(() => location.href = 'login.php', 1500); return; }
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'error');
    const incoming = data.tickets || [];
    if (known) {
      const added = incoming.filter(t => !known.has(t.orderNo));
      if (added.length) {
        added.forEach(t => fresh.add(t.orderNo));
        setTimeout(() => { added.forEach(t => fresh.delete(t.orderNo)); render(); }, 8000);
        chime();
        toast('🔔 အော်ဒါအသစ်: ' + added.map(t => 'Table ' + t.tableNo).join(', '));
      }
    }
    known = new Set(incoming.map(t => t.orderNo));
    tickets = incoming; fetchedAt = Date.now();
    render();
    setStatus(true, 'အချိန်နဲ့တပြေးညီ ပြောင်းလဲနေသည်');
  } catch (e) {
    setStatus(false, 'ချိတ်ဆက်မှု အမှား - ထပ်ကြိုးစားနေသည်');
  }
}

async function setState(orderNo, state){
  if (busy.has(orderNo)) return;
  busy.add(orderNo);
  const t = tickets.find(x => x.orderNo === orderNo);
  const prev = t ? t.state : null;
  // optimistic update
  if (t) { if (state >= 3) tickets = tickets.filter(x => x.orderNo !== orderNo); else t.state = state; }
  fresh.delete(orderNo);
  render();
  try {
    const body = new FormData();
    body.append('orderNo', orderNo); body.append('kitchen_state', state); body.append('csrf_token', CSRF);
    const res = await fetch('api_kitchen_update.php', {method:'POST', body, credentials:'same-origin', headers:{'Accept':'application/json'}});
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'failed');
    if (state >= 3) toast('✓ Table ' + (t ? t.tableNo : '') + ' ချပေးပြီးပါပြီ');
  } catch (e) {
    toast('ပြောင်းလို့ မရပါ။ ထပ်ကြိုးစားပါ');
    if (t && prev !== null) { t.state = prev; if (!tickets.includes(t)) tickets.push(t); }
  } finally {
    busy.delete(orderNo);
    render();
    load();
  }
}

function toast(msg){
  const el = document.getElementById('kdsToast');
  el.textContent = msg; el.classList.add('show');
  clearTimeout(toast._t); toast._t = setTimeout(() => el.classList.remove('show'), 2600);
}

// Sound needs a click first (browser autoplay rules)
function toggleSound(){
  soundOn = !soundOn;
  if (soundOn && !audioCtx) { try { audioCtx = new (window.AudioContext || window.webkitAudioContext)(); } catch(e) {} }
  const b = document.getElementById('soundBtn');
  b.textContent = soundOn ? '🔔 အသံ ON' : '🔕 အသံ OFF';
  b.classList.toggle('on', soundOn);
  try { localStorage.setItem('kds_sound', soundOn ? '1' : '0'); } catch(e) {}
  if (soundOn) chime();
}
function chime(){
  if (!soundOn || !audioCtx) return;
  const now = audioCtx.currentTime;
  [[880, 0], [1320, .18]].forEach(([f, d]) => {
    const o = audioCtx.createOscillator(), g = audioCtx.createGain();
    o.type = 'sine'; o.frequency.value = f;
    g.gain.setValueAtTime(0.0001, now + d);
    g.gain.exponentialRampToValueAtTime(0.35, now + d + 0.02);
    g.gain.exponentialRampToValueAtTime(0.0001, now + d + 0.35);
    o.connect(g).connect(audioCtx.destination); o.start(now + d); o.stop(now + d + 0.4);
  });
}

function toggleTV(){
  const on = !document.body.classList.contains('tv');
  document.body.classList.toggle('tv', on);
  try {
    if (on && document.documentElement.requestFullscreen) document.documentElement.requestFullscreen();
    else if (!on && document.fullscreenElement) document.exitFullscreen();
  } catch(e) {}
}
document.addEventListener('fullscreenchange', () => { if (!document.fullscreenElement) document.body.classList.remove('tv'); });

setInterval(tick, 1000);
setInterval(load, POLL_MS);
load();
</script>
</body>
</html>
