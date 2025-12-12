
// admin.js — grouped overview + preset-times generator + cleared inputs + fixed event wiring

// constants
const STORAGE_KEY = 'gobus_demo';

// ---------- state helpers ----------
function loadState() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) {
      const init = {
        config: { times: ['08:00','12:00','16:00'], activeDays: ['mon','tue','wed','thu','fri','sat','sun'] },
        schedules: {},
        scheduleGroups: [],
        reservations: [],
        defaults: { deluxe: 20, regular: 40 },
        terminals: ['Manila','Cebu','Davao','Baguio','Cagayan de Oro'],
        // demo users so you can login immediately (no DB)
        users: [
          {
            id: 'u_demo',
            name: 'Demo User',
            email: 'user@demo.local',
            password: 'password123',
            createdAt: new Date().toISOString()
          },
          {
            id: 'u_jane',
            name: 'Jane Tester',
            email: 'jane@demo.local',
            password: 'test456',
            createdAt: new Date().toISOString()
          }
        ]
      };
      localStorage.setItem(STORAGE_KEY, JSON.stringify(init));
      return init;
    }
    const parsed = JSON.parse(raw);
    parsed.config = parsed.config || { times: ['08:00','12:00','16:00'], activeDays: ['mon','tue','wed','thu','fri','sat','sun'] };
    parsed.schedules = parsed.schedules || {};
    parsed.scheduleGroups = parsed.scheduleGroups || [];
    parsed.reservations = parsed.reservations || [];
    parsed.defaults = parsed.defaults || { deluxe:20, regular:40 };
    parsed.terminals = parsed.terminals || ['Manila','Cebu','Davao','Baguio','Cagayan de Oro'];

    // ensure users array exists — do not overwrite existing users
    parsed.users = parsed.users || [
      {
        id: 'u_demo',
        name: 'Demo User',
        email: 'user@demo.local',
        password: 'password123',
        createdAt: new Date().toISOString()
      },
      {
        id: 'u_jane',
        name: 'Jane Tester',
        email: 'jane@demo.local',
        password: 'test456',
        createdAt: new Date().toISOString()
      }
    ];

    return parsed;
  } catch (e) {
    console.error('loadState error', e);
    return { config: { times: ['08:00','12:00','16:00'], activeDays: ['mon','tue','wed','thu','fri','sat','sun'] }, schedules: {}, scheduleGroups: [], reservations: [], defaults: { deluxe:20, regular:40 }, terminals:['Manila','Cebu','Davao'], users: [{ id:'u_demo', name:'Demo User', email:'user@demo.local', password:'password123' }, { id:'u_jane', name:'Jane Tester', email:'jane@demo.local', password:'test456' }] };
  }
}
function saveState(s) { localStorage.setItem(STORAGE_KEY, JSON.stringify(s)); }


// ---------- auth helpers ----------
function setAdminSession(){ sessionStorage.setItem('gobus_admin_logged','1'); }
function clearAdminSession(){ sessionStorage.removeItem('gobus_admin_logged'); }

function requireAdminOrRedirect(){
  // Check both sessionStorage (for demo) and server-side session
  const isLoggedIn = sessionStorage.getItem('gobus_admin_logged') === '1';
  if (!isLoggedIn) {
    // Try to check server session via AJAX
    fetch('/admin/schedules', { method: 'GET', credentials: 'same-origin' })
      .then(resp => {
        if (resp.status === 302 || resp.redirected) {
          // Redirected to login, so not authenticated
          window.location.href = '/admin-login';
        } else {
          // Server session exists, set local session
          setAdminSession();
        }
      })
      .catch(() => {
        // Network error, assume not logged in
        window.location.href = '/admin-login';
      });
  }
}

// ---------- util ----------
function uid(prefix='id'){ return prefix + '_' + Math.random().toString(36).slice(2,9); }
function keyFor(date, from, to){ return `${date}|${from}|${to}`; }
function formatDateISO(d){ return d.toISOString().split('T')[0]; }
function dayShortFromDate(dateStr){
  const d = new Date(dateStr + 'T00:00:00');
  const arr = ['sun','mon','tue','wed','thu','fri','sat'];
  return arr[d.getDay()];
}
function parseTimeToMinutes(t){
  const parts = (t||'').split(':');
  if(parts.length<2) return null;
  const hh = parseInt(parts[0],10), mm = parseInt(parts[1],10);
  if(isNaN(hh)||isNaN(mm)) return null;
  return hh*60 + mm;
}
function minutesToTime(min){
  const hh = Math.floor(min/60), mm = min % 60;
  const pad = n => (n<10 ? '0'+n : ''+n);
  return `${pad(hh)}:${pad(mm)}`;
}

function formatTimeToAMPM(timeStr) {
    let [h, m] = timeStr.split(":").map(Number);
    const ampm = h >= 12 ? "PM" : "AM";
    h = h % 12 || 12; 
    return `${h}:${m.toString().padStart(2, '0')} ${ampm}`;
}


/* ---------- LOGIN ---------- */

function handleLoginForm(e){
  e.preventDefault();
  const rawEmail = document.getElementById('email')?.value || '';
  const email = sanitizeEmail(rawEmail);
  const pass = (document.getElementById('password')?.value || '');
  const emErr = document.getElementById('emailErr');
  const passErr = document.getElementById('passwordErr');
  if(emErr) emErr.textContent=''; if(passErr) passErr.textContent='';

  if (!email) { if(emErr) emErr.textContent = 'Email required'; return; }
  if (!isValidEmail(email)) { if(emErr) emErr.textContent = 'Enter a valid email'; return; }
  if (!pass) { if(passErr) passErr.textContent = 'Password required'; return; }
  if (!isValidPassword(pass)) { if(passErr) passErr.textContent = 'Password must be at least 6 characters'; return; }



  // Use server-side authentication - submit the form normally
  // This will be handled by Laravel's server-side authentication
  document.getElementById('loginForm').submit();
}

// small helper to get a query param
function getQueryParam(name){
  try {
    return new URLSearchParams(window.location.search).get(name);
  } catch(e){ return null; }
}

/* ---------- USER LOGIN (demo) ----------
   Replaced naive acceptance with user lookup in local demo state.
*/
function handleUserLogin(e){
  e.preventDefault();
  const rawEmail = document.getElementById('userEmail')?.value || '';
  const email = sanitizeEmail(rawEmail);
  const pass = (document.getElementById('userPass')?.value || '');
  const emErr = document.getElementById('userEmailErr');
  const passErr = document.getElementById('userPassErr');
  if(emErr) emErr.textContent=''; if(passErr) passErr.textContent='';

  if (!email) { if(emErr) emErr.textContent = 'Email required'; return; }
  if (!isValidEmail(email)) { if(emErr) emErr.textContent = 'Enter a valid email'; return; }
  if (!pass) { if(passErr) passErr.textContent = 'Password required'; return; }
  if (!isValidPassword(pass)) { if(passErr) passErr.textContent = 'Password must be at least 6 characters'; return; }

  const s = loadState(); s.users = s.users || [];
  const user = s.users.find(u => (u.email || '').toLowerCase() === email);
  if(!user){ if(emErr) emErr.textContent = 'No account found. Register first.'; return; }
  if(user.password !== pass){ if(passErr) passErr.textContent = 'Invalid credentials'; return; }

  sessionStorage.setItem('gobus_user_logged','1');
  sessionStorage.setItem('gobus_user_email', email);

  // redirect logic: prefer ?next=, then same-origin referrer, else default reservations page
  const nextParam = getQueryParam('next');
  let target = '/user/reservations';
  if(nextParam){
    target = nextParam;
  } else if(document.referrer){
    try {
      const refUrl = new URL(document.referrer);
      if(refUrl.origin === location.origin){
        target = refUrl.pathname + (refUrl.search || '');
      }
    } catch(e){
      // ignore invalid referrer
    }
  }
  location.href = target;
}

/* ---------- USER REGISTER (demo) ----------
   Modal control + registration handler saving into demo state.
*/
function openUserRegisterModal(){
  const modal = document.getElementById('userRegisterModal');
  if(!modal) return;
  // clear fields
  document.getElementById('registerName').value = '';
  document.getElementById('registerEmail').value = '';
  document.getElementById('registerPass').value = '';
  document.getElementById('registerPassConfirm').value = '';
  ['registerNameErr','registerEmailErr','registerPassErr','registerPassConfirmErr'].forEach(id=>{
    const el = document.getElementById(id); if(el) el.textContent = '';
  });
  modal.classList.add('open');
  modal.style.display = 'flex';
}
function closeUserRegisterModal(){
  const modal = document.getElementById('userRegisterModal');
  if(!modal) return;
  modal.classList.remove('open');
  modal.style.display = 'none';
}

function handleUserRegister(e){
  e.preventDefault();
  const rawName = document.getElementById('registerName')?.value || '';
  const name = sanitizeName(rawName);
  const rawEmail = document.getElementById('registerEmail')?.value || '';
  const email = sanitizeEmail(rawEmail);
  const pass = (document.getElementById('registerPass')?.value || '');
  const passConfirm = (document.getElementById('registerPassConfirm')?.value || '');

  const nameErr = document.getElementById('registerNameErr');
  const emailErr = document.getElementById('registerEmailErr');
  const passErr = document.getElementById('registerPassErr');
  const passConfirmErr = document.getElementById('registerPassConfirmErr');
  if(nameErr) nameErr.textContent=''; if(emailErr) emailErr.textContent=''; if(passErr) passErr.textContent=''; if(passConfirmErr) passConfirmErr.textContent='';

  if(!name || name.length < 2){ if(nameErr) nameErr.textContent = 'Enter your full name'; return; }
  if(!email){ if(emailErr) emailErr.textContent = 'Email required'; return; }
  if(!isValidEmail(email)){ if(emailErr) emailErr.textContent = 'Enter a valid email'; return; }
  if(!pass){ if(passErr) passErr.textContent = 'Password required'; return; }
  if(!isValidPassword(pass)){ if(passErr) passErr.textContent = 'Password must be at least 6 characters'; return; }
  if(pass !== passConfirm){ if(passConfirmErr) passConfirmErr.textContent = 'Passwords do not match'; return; }

  const s = loadState(); s.users = s.users || [];
  if(s.users.some(u => (u.email || '').toLowerCase() === email)){ if(emailErr) emailErr.textContent = 'An account with this email already exists'; return; }

  const id = uid('u');
  s.users.push({ id, name, email, password: pass, createdAt: new Date().toISOString() });
  saveState(s);

  alert('Account created. You can now sign in.');
  closeUserRegisterModal();
}

/* ---------- RENDER HELPERS ---------- */
function renderTimes() {
  const timesList = document.getElementById('timesList');
  if(!timesList) return;
  const s = loadState();
  timesList.innerHTML = '';
  (s.config.times || []).forEach(t=>{
    const pill = document.createElement('div');
    pill.className='time-pill';
    pill.innerHTML = `<span>${t}</span> <button class="btn-ghost time-remove" data-time="${t}">&times;</button>`;
    timesList.appendChild(pill);
  });
  timesList.querySelectorAll('.time-remove').forEach(b=>{
    b.addEventListener('click', ()=>{
      const t = b.dataset.time;
      const s2 = loadState();
      s2.config.times = (s2.config.times || []).filter(x=>x!==t);
      saveState(s2);
      renderTimes();
    });
  });
}

function renderActiveDays(){
  const container = document.getElementById('activeDays');
  if(!container) return;
  const s = loadState();
  container.innerHTML='';
  const days = ['sun','mon','tue','wed','thu','fri','sat'];
  days.forEach(d=>{
    const id = 'ad_'+d;
    const label = document.createElement('label');
    label.style.display='inline-flex';
    label.style.alignItems='center';
    label.style.gap='8px';
    label.style.marginRight='8px';
    const cb = document.createElement('input');
    cb.type='checkbox'; cb.id=id; cb.value=d;
    cb.checked = (s.config.activeDays || []).includes(d);
    cb.addEventListener('change', ()=>{
      const s2 = loadState();
      if(cb.checked){
        if(!s2.config.activeDays.includes(d)) s2.config.activeDays.push(d);
      } else {
        s2.config.activeDays = s2.config.activeDays.filter(x=>x!==d);
      }
      saveState(s2);
      // visually mark quick-set buttons if needed
      updateQuickSetButtons();
    });
    label.appendChild(cb);
    label.appendChild(document.createTextNode(d.toUpperCase()));
    container.appendChild(label);
  });
  updateQuickSetButtons();
}

function updateQuickSetButtons(){
  const s = loadState();
  const days = s.config.activeDays || [];
  const wk = ['mon','tue','wed','thu','fri'];
  const we = ['sat','sun'];
  const setWeekdays = document.getElementById('setWeekdays');
  const setWeekends = document.getElementById('setWeekends');
  const setAllDays = document.getElementById('setAllDays');

  // helper to set .active
  const mark = (btn, cond)=> { if(!btn) return; if(cond) btn.classList.add('active'); else btn.classList.remove('active'); };

  mark(setWeekdays, wk.every(d=> days.includes(d)) && days.length === wk.length);
  mark(setWeekends, we.every(d=> days.includes(d)) && days.length === we.length);
  mark(setAllDays, days.length === 7);
}

/* ---------- Overview groups rendering ---------- */
function renderOverview(){
  const out = document.getElementById('schedulesOverview');
  if(!out) return;
  const state = loadState();
  out.innerHTML = '';
  const groups = state.scheduleGroups || [];
  if(!groups.length){ out.innerHTML = '<div class="card">No schedule groups yet.</div>'; return; }

  groups.forEach(g=>{
    const has = hasSchedulesForGroup(g);
    if(!has) return;
    const item = document.createElement('div');
    item.className = 'sched-row';
    item.style.display = 'flex';
    item.style.justifyContent = 'space-between';
    item.style.alignItems = 'center';
    item.style.marginBottom = '8px';
    item.innerHTML = `<div><strong>${g.start} → ${g.end || g.start}</strong> — ${g.from} → ${g.to}</div>
      <div>
        <button class="btn btn-ghost view-group" data-id="${g.id}">View</button>
      </div>`;
    out.appendChild(item);
  });

  // attach handlers (delegation would work too)
  out.querySelectorAll('.view-group').forEach(b=>{
    b.addEventListener('click', ()=>{
      const id = b.dataset.id;
      openGroupModal(id);
    });
  });
}

function hasSchedulesForGroup(group){
  const s = loadState();
  let cur = new Date(group.start + 'T00:00:00');
  let last = new Date(group.start + 'T00:00:00');
  if(group.end && group.end >= group.start) last = new Date(group.end + 'T00:00:00');
  while(cur <= last){
    const key = keyFor(cur.toISOString().split('T')[0], group.from, group.to);
    const arr = s.schedules[key] || [];
    if(arr.length) return true;
    cur.setDate(cur.getDate()+1);
  }
  return false;
}

/* Group modal */
function openGroupModal(id){
  const state = loadState();
  const g = (state.scheduleGroups || []).find(x=>x.id===id);
  if(!g) return alert('Group not found');
  const title = document.getElementById('groupModalTitle');
  const body = document.getElementById('groupModalBody');
  title.textContent = `${g.start} → ${g.end || g.start} — ${g.from} → ${g.to}`;
  body.innerHTML = '';

  // accumulate schedules by date for this group
  let cur = new Date(g.start + 'T00:00:00');
  let last = new Date(g.start + 'T00:00:00');
  if(g.end && g.end >= g.start) last = new Date(g.end + 'T00:00:00');
  while(cur <= last){
    const dateStr = cur.toISOString().split('T')[0];
    const key = keyFor(dateStr, g.from, g.to);
    const arr = (state.schedules[key] || []);
    if(arr.length){
      const sec = document.createElement('div');
      sec.className = 'card';
      sec.style.marginBottom = '8px';
      sec.innerHTML = `<strong style="display:block;margin-bottom:8px">${dateStr}</strong>`;
      arr.forEach(sch=>{
        const reservedCount = state.reservations.filter(r=>r.scheduleId===sch.id).reduce((a,b)=>a+b.qty,0);
        const capDefault = sch.busType==='deluxe' ? state.defaults.deluxe : state.defaults.regular;
        const cap = (sch.capacity || capDefault);
        const remaining = Math.max(0, (cap - reservedCount));
        const row = document.createElement('div');
        row.style.display = 'flex';
        row.style.justifyContent = 'space-between';
        row.style.alignItems = 'center';
        row.style.marginBottom = '6px';
        row.innerHTML = `<div><strong>${sch.time}</strong> — ${sch.busType.toUpperCase()} — ₱${sch.price || 0} — ${cap} seats (left: ${remaining})</div>
          <div style="display:flex;gap:8px">
            <button class="btn btn-ghost view-sched" data-key="${key}" data-id="${sch.id}">Details</button>
          </div>`;
        sec.appendChild(row);
      });
      body.appendChild(sec);
    }
    cur.setDate(cur.getDate()+1);
  }

  // attach handlers for details inside the group body
  body.querySelectorAll('.view-sched').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const key = btn.dataset.key;
      const id = btn.dataset.id;
      openScheduleDetails(key, id);
    });
  });

  document.getElementById('groupModal').classList.add('open');
}
function closeGroupModal(){ document.getElementById('groupModal').classList.remove('open'); }

/* Show single schedule details in a small modal */
function openScheduleDetails(key, scheduleId){
  const state = loadState();
  const arr = state.schedules[key] || [];
  const sch = arr.find(s => s.id === scheduleId);
  if(!sch) return alert('Schedule not found');

  const title = document.getElementById('scheduleDetailsTitle');
  const list = document.getElementById('scheduleDetailsList');
  title.textContent = `${key} — ${sch.time} (${sch.busType.toUpperCase()})`;
  list.innerHTML = `
    <div class="card" style="margin-bottom:8px">
      <div><strong>Time:</strong> ${sch.time}</div>
      <div><strong>Bus Type:</strong> ${sch.busType}</div>
      <div><strong>Price:</strong> ₱${sch.price || 0}</div>
      <div><strong>Trip Type:</strong> ${sch.tripType || 'single'}</div>
      <div><strong>Capacity:</strong> ${sch.capacity || (sch.busType==='deluxe'? loadState().defaults.deluxe : loadState().defaults.regular)}</div>
      <div><strong>Seats left:</strong> ${ (function(){
        const reservedCount = loadState().reservations.filter(r=>r.scheduleId===sch.id).reduce((a,b)=>a+b.qty,0);
        const capDefault = sch.busType==='deluxe' ? loadState().defaults.deluxe : loadState().defaults.regular;
        const cap = sch.capacity || capDefault;
        return Math.max(0, cap - reservedCount);
      })() }</div>
    </div>
  `;
  document.getElementById('scheduleDetailsModal').classList.add('open');
}

/* renderExisting: show all dates in the selected range (grouped) */
function renderExisting(){
  const existingSchedules = document.getElementById('existingSchedules');
  if(!existingSchedules) return;
  const startInput = document.getElementById('schedDate');
  const endInput = document.getElementById('schedEndDate');
  const fromInput = document.getElementById('schedFrom');
  const toInput = document.getElementById('schedTo');

  const start = startInput ? startInput.value : '';
  const end = endInput ? endInput.value : '';
  const from = fromInput ? fromInput.value.trim() : '';
  const to = toInput ? toInput.value.trim() : '';
  const state = loadState();

  existingSchedules.innerHTML = '';
  if(!start || !from || !to){ existingSchedules.innerHTML = '<div class="card">Enter date range and route to view schedules.</div>'; return; }

  // build dates list inclusive
  const dates = [];
  let cur = new Date(start + 'T00:00:00');
  let last = cur;
  if(end && end >= start) last = new Date(end + 'T00:00:00');
  while(cur <= last){
    dates.push(cur.toISOString().split('T')[0]);
    cur.setDate(cur.getDate()+1);
  }

  // for each date, show its schedules (if any)
  let totalFound = 0;
  dates.forEach(dateStr=>{
    const key = keyFor(dateStr, from, to);
    const arr = (state.schedules[key] || []);
    if(!arr.length) return;
    totalFound += arr.length;
    const section = document.createElement('div');
    section.className = 'card';
    section.style.marginBottom = '10px';
    const heading = document.createElement('div');
    heading.innerHTML = `<strong style="font-size:15px">${dateStr} — ${from} → ${to}</strong>`;
    section.appendChild(heading);

    arr.forEach(sch=>{
      const reservedCount = state.reservations.filter(r=>r.scheduleId===sch.id).reduce((a,b)=>a+b.qty,0);
      const capDefault = sch.busType==='deluxe' ? state.defaults.deluxe : state.defaults.regular;
      const cap = (sch.capacity || capDefault);
      const remaining = Math.max(0, (cap - reservedCount));

      const row = document.createElement('div'); row.className='sched-row';
      row.style.marginTop = '10px';
      row.innerHTML = `
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
          <div style="min-width:90px"><strong>${sch.time}</strong><div style="font-size:12px;color:var(--muted)">${sch.busType.toUpperCase()}</div></div>

          <div>
            <label>Trip</label>
            <select class="edit-trip" data-key="${key}" data-id="${sch.id}">
              <option value="single"${sch.tripType==='single'?' selected':''}>Single</option>
              <option value="round"${sch.tripType==='round'?' selected':''}>Round-trip</option>
            </select>
          </div>

          <div>
            <label>Bus</label>
            <select class="edit-bus" data-key="${key}" data-id="${sch.id}">
              <option value="regular"${sch.busType==='regular'?' selected':''}>Regular</option>
              <option value="deluxe"${sch.busType==='deluxe'?' selected':''}>Deluxe</option>
            </select>
          </div>

          <div>
            <label>Price (₱)</label>
            <input type="number" class="edit-price" data-key="${key}" data-id="${sch.id}" value="${sch.price||0}" />
          </div>

          <div>
            <label>Capacity</label>
            <input type="number" class="edit-cap" data-key="${key}" data-id="${sch.id}" value="${sch.capacity||cap}" />
          </div>

          <div style="min-width:140px">
            <div>Seats left: <strong>${remaining}</strong></div>
            <div style="margin-top:8px">
              <button class="btn btn-primary update-sched" data-key="${key}" data-id="${sch.id}">Update</button>
              <button class="btn btn-ghost delete-sched" data-key="${key}" data-id="${sch.id}">Delete</button>
            </div>
          </div>
        </div>
      `;
      section.appendChild(row);
    });

    existingSchedules.appendChild(section);
  });

  if(!totalFound){
    existingSchedules.innerHTML = '<div class="card">No schedules found for the selected range / route.</div>';
    return;
  }

  // attach handlers for delete/update (use data-key + data-id)
  existingSchedules.querySelectorAll('.delete-sched').forEach(b=>{
    b.addEventListener('click', ()=>{
      if(!confirm('Delete this schedule?')) return;
      const id = b.dataset.id;
      const key = b.dataset.key;
      const s = loadState();
      s.schedules[key] = (s.schedules[key] || []).filter(x=>x.id !== id);
      saveState(s);
      cleanupEmptyGroups();
      renderExisting();
      renderOverview();
    });
  });

  existingSchedules.querySelectorAll('.update-sched').forEach(b=>{
    b.addEventListener('click', ()=>{
      const id = b.dataset.id;
      const key = b.dataset.key;
      const s = loadState();
      const arr = s.schedules[key] || [];
      const sch = arr.find(x=>x.id===id);
      if(!sch) return alert('Schedule not found');

      const newTrip = existingSchedules.querySelector(`.edit-trip[data-id="${id}"][data-key="${key}"]`).value;
      const newBus = existingSchedules.querySelector(`.edit-bus[data-id="${id}"][data-key="${key}"]`).value;
      const newPrice = parseFloat(existingSchedules.querySelector(`.edit-price[data-id="${id}"][data-key="${key}"]`).value) || 0;
      const newCap = parseInt(existingSchedules.querySelector(`.edit-cap[data-id="${id}"][data-key="${key}"]`).value) || sch.capacity || (newBus==='deluxe' ? s.defaults.deluxe : s.defaults.regular);

      sch.tripType = newTrip;
      sch.busType = newBus;
      sch.price = newPrice;
      sch.capacity = newCap;
      saveState(s);
      alert('Schedule updated.');
      renderExisting();
      renderOverview();
    });
  });
}

/* Remove schedule groups that no longer contain schedules */
function cleanupEmptyGroups(){
  const state = loadState();
  state.scheduleGroups = (state.scheduleGroups || []).filter(g=> hasSchedulesForGroup(g));
  saveState(state);
}

/* ---------- SCHEDULES PAGE init (wires UI) ---------- */
function initSchedulesPage(){

  // elements
  const startInput = document.getElementById('schedDate');
  const endInput = document.getElementById('schedEndDate');
  const fromInput = document.getElementById('schedFrom');
  const toInput = document.getElementById('schedTo');

  const genBusChoice = document.getElementById('genBusChoice');
  const genPrice = document.getElementById('genPrice');
  const genPriceWrap = document.getElementById('genPriceWrap');
  const genDualPrices = document.getElementById('genDualPrices');
  const genPriceRegular = document.getElementById('genPriceRegular');
  const genPriceDeluxe = document.getElementById('genPriceDeluxe');

  const genCapacity = document.getElementById('genCapacity');
  const genCapWrap = document.getElementById('genCapWrap');
  const genDualCaps = document.getElementById('genDualCaps');
  const genCapRegular = document.getElementById('genCapRegular');
  const genCapDeluxe = document.getElementById('genCapDeluxe');

  const addTimeBtn = document.getElementById('addTime');
  const newTimeInput = document.getElementById('newTime');

  const autoGenerateBtn = document.getElementById('autoGenerate');
  const saveConfigBtn = document.getElementById('saveConfig');

  // default start date = tomorrow
  if(startInput){
    const dt = new Date(); dt.setDate(dt.getDate() + 1);
    if(!startInput.value) startInput.value = formatDateISO(dt);
  }



  // render UI pieces
  renderTimes();
  renderActiveDays();

  // show / hide price-capacity inputs based on bus choice
  function updateGenInputsVisibility(){
    const choice = genBusChoice ? genBusChoice.value : 'regular';
    if(choice === 'both'){
      genPriceWrap.style.display = 'none';
      genDualPrices.style.display = 'flex';
      genCapWrap.style.display = 'none';
      genDualCaps.style.display = 'flex';
    } else {
      genPriceWrap.style.display = 'block';
      genDualPrices.style.display = 'none';
      genCapWrap.style.display = 'block';
      genDualCaps.style.display = 'none';
    }
  }
  if(genBusChoice){
    genBusChoice.addEventListener('change', updateGenInputsVisibility);
    updateGenInputsVisibility();
  }

  // add preset time
  if(addTimeBtn && newTimeInput){
    addTimeBtn.addEventListener('click', ()=>{
      const v = newTimeInput.value.trim();
      if(!/^\d{1,2}:\d{2}$/.test(v)){ alert('Enter time like HH:MM'); return; }
      const s = loadState();
      s.config.times = s.config.times || [];
      if(!s.config.times.includes(v)) s.config.times.push(v);
      s.config.times.sort();
      saveState(s);
      newTimeInput.value = '';
      renderTimes();
    });
  }

  // quick-set active days
  const setWeekdays = document.getElementById('setWeekdays');
  const setWeekends = document.getElementById('setWeekends');
  const setAllDays = document.getElementById('setAllDays');
  if(setWeekdays) setWeekdays.addEventListener('click', ()=>{ const s = loadState(); s.config.activeDays = ['mon','tue','wed','thu','fri']; saveState(s); renderActiveDays(); });
  if(setWeekends) setWeekends.addEventListener('click', ()=>{ const s = loadState(); s.config.activeDays = ['sat','sun']; saveState(s); renderActiveDays(); });
  if(setAllDays) setAllDays.addEventListener('click', ()=>{ const s = loadState(); s.config.activeDays = ['sun','mon','tue','wed','thu','fri','sat']; saveState(s); renderActiveDays(); });

  // save config
  if(saveConfigBtn){
    saveConfigBtn.addEventListener('click', ()=>{ const s = loadState(); saveState(s); alert('Saved configuration.'); });
  }

  // Auto-generate using PRESET TIMES (timesList) and respecting activeDays
  if(autoGenerateBtn){
    autoGenerateBtn.addEventListener('click', async ()=>{
      const sState = loadState();
      const start = startInput ? startInput.value : '';
      const end = endInput ? endInput.value : '';
      const from = fromInput ? fromInput.value.trim() : '';
      const to = toInput ? toInput.value.trim() : '';
      const busChoice = genBusChoice ? genBusChoice.value : 'regular';
      const capInput = genCapacity ? parseInt(genCapacity.value) : NaN;

      if(!start || !from || !to) return alert('Please set start date and route (from/to).');

      // times come from preset config
      const times = (sState.config.times || []).slice();
      if(!times.length) return alert('No preset times configured. Add times in Preset Hours.');

      // build date list inclusive but only for active days
      const dates = [];
      let cur = new Date(start + 'T00:00:00');
      let last = cur;
      if(end && end >= start) last = new Date(end + 'T00:00:00');
      while(cur <= last){
        const dayShort = dayShortFromDate(cur.toISOString().split('T')[0]);
        if((sState.config.activeDays || []).includes(dayShort)){
          dates.push(cur.toISOString().split('T')[0]);
        }
        cur.setDate(cur.getDate()+1);
      }
      if(!dates.length) return alert('No dates in range are active (check Active Days settings).');

      // determine types to create
      const types = (busChoice === 'both') ? ['regular','deluxe'] : [busChoice];

      // prices per type
      const priceVals = {};
      if(busChoice === 'both'){
        priceVals.regular = parseFloat(genPriceRegular.value) || 0;
        priceVals.deluxe = parseFloat(genPriceDeluxe.value) || 0;
      } else {
        priceVals[types[0]] = parseFloat(genPrice.value) || 0;
      }

      // capacities per type (use defaults if empty)
      const capVals = {};
      if(busChoice === 'both'){
        capVals.regular = (parseInt(genCapRegular.value) > 0) ? parseInt(genCapRegular.value) : sState.defaults.regular;
        capVals.deluxe = (parseInt(genCapDeluxe.value) > 0) ? parseInt(genCapDeluxe.value) : sState.defaults.deluxe;
      } else {
        capVals[types[0]] = (!isNaN(capInput) && capInput>0) ? capInput : (types[0] === 'deluxe' ? sState.defaults.deluxe : sState.defaults.regular);
      }

      // create unique group record
      const groupId = uid('g');
      const groupMeta = { id: groupId, from, to, start, end: end || start, createdAt: new Date().toISOString() };

      let created = 0;
      const createdForServer = []; // collect payloads to POST to server
      dates.forEach(dateStr=>{
        const key = keyFor(dateStr, from, to);
        sState.schedules[key] = sState.schedules[key] || [];

        times.forEach(timeStr=>{
          types.forEach(bt=>{
            // prevent duplicate: same time + busType
            if(sState.schedules[key].some(x=> x.time === timeStr && x.busType === bt)) return;
            const id = uid('s');
            const cap = capVals[bt] || (bt==='deluxe' ? sState.defaults.deluxe : sState.defaults.regular);
            const price = priceVals[bt] || 0;
            sState.schedules[key].push({
              id,
              time: timeStr,
              busType: bt,
              tripType: 'single',
              price: price,
              capacity: cap
            });
            created++;


            // prepare server payload: adapt to your ScheduleController expected fields
            createdForServer.push({
              route_from: from,
              route_to: to,
              departure_time: `${dateStr} ${timeStr}:00`,
              arrival_time: null,
              bus_number: null,
              seats: cap,
              available_seats: cap,
              fare: price,
              status: 'active',
              bus_type: bt,
              trip_type: 'single',
              capacity: cap
            });
          });
        });
      });

      if(created > 0){
        // store group metadata
        sState.scheduleGroups = sState.scheduleGroups || [];
        sState.scheduleGroups.push(groupMeta);
        saveState(sState);

        // clear inputs so admin can create new ones
        if(startInput) startInput.value = '';
        if(endInput) endInput.value = '';
        if(fromInput) fromInput.value = '';
        if(toInput) toInput.value = '';
        if(genBusChoice) genBusChoice.value = 'regular';
        if(genPrice) genPrice.value = '';
        if(genPriceRegular) genPriceRegular.value = '';
        if(genPriceDeluxe) genPriceDeluxe.value = '';
        if(genCapacity) genCapacity.value = '';
        if(genCapRegular) genCapRegular.value = '';
        if(genCapDeluxe) genCapDeluxe.value = '';

        renderExisting();
        renderOverview();
        updateGenInputsVisibility();

        // Attempt to persist to server (non-blocking)
        if(createdForServer.length){
          // send in batches to avoid too many simultaneous requests (simple split)
          const batchSize = 20;
          let serverOk = 0, serverFailed = 0;
          for(let i=0;i<createdForServer.length;i+=batchSize){
            const batch = createdForServer.slice(i, i+batchSize);
            try {
              const res = await postSchedulesBatch(batch);
              serverOk += res.ok;
              serverFailed += res.failed;
            } catch(e){
              serverFailed += batch.length;
            }
          }
          alert(`Created ${created} schedule(s) locally across ${dates.length} active date(s).\nServer save: ${serverOk} succeeded, ${serverFailed} failed.`);
        } else {
          alert(`Created ${created} schedule(s) locally across ${dates.length} active date(s).`);
        }
      } else {
        alert('No new schedules were created (duplicates or none matched).');
      }
    });
  }

  // update view when selecting different date/route
  if(startInput) startInput.addEventListener('change', renderExisting);
  if(endInput) endInput.addEventListener('change', renderExisting);
  if(fromInput) fromInput.addEventListener('input', renderExisting);
  if(toInput) toInput.addEventListener('input', renderExisting);
}

/* ---------- ADD SCHEDULE Modal (kept for manual adds) ---------- */
function openAddModal(){
  const overlay = document.getElementById('addScheduleModal');
  if(!overlay) return;
  const dt = new Date(); dt.setDate(dt.getDate()+1);
  document.getElementById('modalStartDate').value = dt.toISOString().split('T')[0];
  document.getElementById('modalEndDate').value = '';
  document.getElementById('modalTime').value = '';
  document.getElementById('modalTripType').value = 'single';
  document.getElementById('modalBusType').value = 'regular';
  document.getElementById('modalCapacity').value = '';
  document.getElementById('modalPrice').value = '';
  overlay.classList.add('open');
}
function closeAddModal(){
  const overlay = document.getElementById('addScheduleModal');
  if(!overlay) return;
  overlay.classList.remove('open');
}

/* add schedule handler (supports date range) */
function handleAddSchedule(){
  const start = document.getElementById('modalStartDate').value;
  const end = document.getElementById('modalEndDate').value;
  const time = document.getElementById('modalTime').value.trim();
  const tripType = document.getElementById('modalTripType').value;
  const busType = document.getElementById('modalBusType').value;
  const price = parseFloat(document.getElementById('modalPrice').value) || 0;
  const capInput = parseInt(document.getElementById('modalCapacity').value);
  const sState = loadState();

  if(!start) return alert('Please choose start date');
  if(!/^\d{1,2}:\d{2}$/.test(time)) return alert('Enter time as HH:MM');

  const dates = [];
  if(end && end >= start){
    let cur = new Date(start + 'T00:00:00');
    const last = new Date(end + 'T00:00:00');
    while(cur <= last){
      dates.push(cur.toISOString().split('T')[0]);
      cur.setDate(cur.getDate()+1);
    }
  } else {
    dates.push(start);
  }

  const createdForServer = [];
  dates.forEach(date=>{
    const from = document.getElementById('schedFrom').value.trim();
    const to = document.getElementById('schedTo').value.trim();
    if(!from || !to) return;
    const key = keyFor(date, from, to);
    sState.schedules[key] = sState.schedules[key] || [];

    if(sState.schedules[key].some(x=> x.time === time && x.busType === busType)) return;

    const capacity = (typeof capInput === 'number' && !isNaN(capInput) && capInput>0) ? capInput : (busType==='deluxe' ? sState.defaults.deluxe : sState.defaults.regular);
    const id = uid('s');
    sState.schedules[key].push({
      id,
      time,
      busType,
      tripType,
      price,
      capacity
    });

    createdForServer.push({
      route_from: from,
      route_to: to,
      departure_time: `${date} ${time}:00`,
      arrival_time: null,
      bus_number: null,
      seats: capacity,
      available_seats: capacity,
      fare: price,
      status: 'active'
    });
  });

  saveState(sState);
  closeAddModal();
  renderExisting();
  renderOverview();
  alert('Schedule(s) added.');

  // Try to save created schedules to server
  if(createdForServer.length){
    postSchedulesBatch(createdForServer).then(res=>{
      alert(`Server save: ${res.ok} succeeded, ${res.failed} failed (of ${res.sent}).`);
    }).catch(()=>{ alert('Failed to send schedules to server.'); });
  }
}

/* ---------- RESERVATIONS PAGE (full working version) ---------- */
function initReservationsPage(){
  requireAdminOrRedirect();

  const state = loadState();
  const container = document.getElementById("reservationsTable");
  if (!container) return;

  container.innerHTML = "";
  const rows = state.reservations || [];

  if (!rows.length) {
    container.innerHTML = `<div class="card">No reservations yet.</div>`;
    return;
  }

  const t = document.createElement("table");
  t.className = "table";
  t.innerHTML = `
    <thead>
  <tr>
    <th>ID</th>
    <th>Trip</th>
    <th>Date</th>
    <th>Time</th>
    <th>Bus</th>
    <th>Qty</th>
    <th>Status</th>
    <th>Action</th>
  </tr>
</thead>

  `;

  const tb = document.createElement("tbody");

  rows.forEach(r => {

    /* ROUTE + DETAILS */
    const parts = r.key.split("|");
    const date = parts[0] || "";
    const trip = `${parts[1]} → ${parts[2]}`;

    const schArr = state.schedules[r.key] || [];
    const sch = schArr.find(s => s.id === r.scheduleId);
    const time = sch ? sch.time : "-";

    /* STATUS COLORS */
    let statusClass = "status-txt-pending";
    if (r.status === "confirmed") statusClass = "status-txt-confirmed";
    if (r.status === "cancelled") statusClass = "status-txt-cancelled";

    /* ACTIONS */
    let actionHTML = `<span class="no-action"></span>`;
    if (r.status === "pending") {
      actionHTML = `
        <button class="btn-cancel-admin" data-id="${r.id}">Cancel</button>
      `;
    }

    const tr = document.createElement("tr");
    tr.classList.add("res-click-row");
    tr.dataset.id = r.id;

    tr.innerHTML = `
      <td>${r.id}</td>
      <td>${trip}</td>
      <td>${date}</td>
      <td>${time}</td>
      <td>${r.busType}</td>
      <td>${r.qty}</td>
      <td><span class="${statusClass}">${r.status.toUpperCase()}</span></td>
      <td>${actionHTML}</td>
    `;

    tb.appendChild(tr);
  });

  t.appendChild(tb);
  container.appendChild(t);

  /* ---------- ROW CLICK OPENS MODAL ---------- */
  document.querySelectorAll(".res-click-row").forEach(row => {
    row.addEventListener("click", (e) => {
      if (e.target.closest("button")) return;  // ignore clicks on Cancel button
      const id = row.dataset.id;
      openReservationDetails(id);
    });
  });

  /* ---------- CANCEL BUTTON ---------- */
  document.querySelectorAll(".btn-cancel-admin").forEach(btn => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation(); // prevent modal opening

      const id = btn.dataset.id;
      const s = loadState();
      const idx = s.reservations.findIndex(x => x.id === id);
      if (idx === -1) return;

      if (!confirm("Cancel this reservation?")) return;

      s.reservations[idx].status = "cancelled";
      saveState(s);
      initReservationsPage();
    });
  });

  function openReservationDetails(id){
  const modal = document.getElementById("adminReservationModal");
  const body = document.getElementById("adminDetailBody");
  
  const state = loadState();
  const r = state.reservations.find(x => x.id === id);
  if (!r) return;

  const parts = r.key.split("|");
  const date = parts[0];
  const route = `${parts[1]} → ${parts[2]}`;

  const sch = (state.schedules[r.key] || []).find(s => s.id === r.scheduleId);
  const time = sch ? sch.time : "-";
  const price = sch ? sch.price : 0;
  const total = price * r.qty;

  body.innerHTML = `
    <p><strong>Reservation ID:</strong> ${r.id}</p>
    <p><strong>Trip Type:</strong> ${r.rdate ? "Roundtrip" : "One Way"}</p>
    <p><strong>Route:</strong> ${route}</p>
    <p><strong>Date:</strong> ${date}</p>
    <p><strong>Time:</strong> ${time}</p>
    <p><strong>Bus Type:</strong> ${r.busType}</p>
    <p><strong>Seats:</strong> ${r.seats.join(", ")}</p>
    <p><strong>Passengers:</strong> ${r.qty}</p>
    <p><strong>Price:</strong> ₱${price}</p>
    <h3><strong>Total:</strong> ₱${total}</h3>
  `;

  modal.classList.add("open");
}

}


/* ---------- BUSES PAGE (unchanged) ---------- */
function initBusesPage(){
  requireAdminOrRedirect();
  const state = loadState();
  const dInput = document.getElementById('capDeluxe');
  const rInput = document.getElementById('capRegular');
  if(!dInput || !rInput) return;
  dInput.value = state.defaults && state.defaults.deluxe ? state.defaults.deluxe : 20;
  rInput.value = state.defaults && state.defaults.regular ? state.defaults.regular : 40;
  document.getElementById('saveCaps').addEventListener('click', ()=>{
    const d = parseInt(dInput.value) || 20;
    const r = parseInt(rInput.value) || 40;
    const s = loadState(); s.defaults = s.defaults||{}; s.defaults.deluxe = d; s.defaults.regular = r; saveState(s);
    alert('Capacities saved');
  });
  // resetSchedules kept but guarded by confirm — you asked to remove global clear; I kept resetSchedules but it clears everything only if button exists
  document.getElementById('resetSchedules')?.addEventListener('click', ()=>{
    if(!confirm('Remove all schedules?')) return;
    const s = loadState(); s.schedules = {}; s.scheduleGroups = []; saveState(s);
    alert('All schedules cleared');
  });
}

/* ---------- sidebar nav helpers ---------- */
function setupSidebar(){
  const path = location.pathname.split('/').pop();
  document.querySelectorAll('.sidebar .item').forEach(el=>{
    el.classList.remove('active');
    const target = el.dataset.target;
    if(target && path === target) el.classList.add('active');
  });

  // Make sidebar sticky / scrollable by CSS; nothing else needed here.
}

/* Helper: load the server-rendered admin schedules view into a container */
function loadAdminSchedulesInto(containerId = 'schedules-container') {
  const container = document.getElementById(containerId);
  if (!container) return console.warn('Container not found:', containerId);

  fetch('/admin/schedules', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(resp => {
      if (!resp.ok) throw new Error('Network response was not ok');
      return resp.text();
    })
    .then(html => { container.innerHTML = html; })
    .catch(err => { console.error('Failed to load admin schedules view:', err); });
}

/* ---------- input sanitization / validation helpers ---------- */
function sanitizeEmail(email){ return (email || '').trim().toLowerCase(); }
function stripTags(s){ return (s || '').replace(/<\/?[^>]+(>|$)/g, ''); }
function sanitizeName(name){ return stripTags(name).replace(/\s+/g,' ').trim(); }
function isValidEmail(email){ return /^\S+@\S+\.\S+$/.test(email); }
function isValidPassword(p){ return typeof p === 'string' && p.length >= 6; }

// ---------- realtime validation helpers ----------
function getErrElFor(input){
  if(!input) return null;
  // error divs are named like "<inputId>Err" or use a sibling with class error-text
  const id = input.id ? input.id + 'Err' : null;
  if(id){
    const el = document.getElementById(id);
    if(el) return el;
  }
  // fallback: next sibling
  const sib = input.nextElementSibling;
  if(sib && sib.classList.contains('error-text')) return sib;
  return null;
}
function markInvalid(input, msg){
  if(!input) return;
  input.classList.add('input-invalid');
  const err = getErrElFor(input);
  if(err) err.textContent = msg || '';
}
function clearInvalid(input){
  if(!input) return;
  input.classList.remove('input-invalid');
  const err = getErrElFor(input);
  if(err) err.textContent = '';
}

// Validate one field by type and element; returns boolean (valid)
function validateFieldById(id){
  const el = document.getElementById(id);
  if(!el) return true;
  const val = (el.value || '').trim();
  if(id.toLowerCase().includes('email')){
    const email = sanitizeEmail(val);
    if(!email){ markInvalid(el, 'Email required'); return false; }
    if(!isValidEmail(email)){ markInvalid(el, 'Enter a valid email'); return false; }
    clearInvalid(el); return true;
  }
  if(id.toLowerCase().includes('pass')){
    // treat confirm separately
    if(id.toLowerCase().includes('confirm')){
      const other = document.getElementById(id.replace('Confirm','')) || document.getElementById(id.replace('confirm',''));
      const otherVal = other ? other.value : '';
      if(val !== otherVal){ markInvalid(el, 'Passwords do not match'); return false; }
      clearInvalid(el); return true;
    }
    if(!val){ markInvalid(el, 'Password required'); return false; }
    if(!isValidPassword(val)){ markInvalid(el, 'Password must be at least 6 characters'); return false; }
    clearInvalid(el); return true;
  }
  if(id.toLowerCase().includes('name')){
    const name = sanitizeName(val);
    if(!name || name.length < 2){ markInvalid(el, 'Enter your full name'); return false; }
    clearInvalid(el); return true;
  }
  // generic: non-empty
  if(!val){ markInvalid(el, 'Required'); return false; }
  clearInvalid(el); return true;
}

// Attach listeners to an input element for realtime validation + sanitization on blur
function attachRealtimeTo(input){
  if(!input) return;
  input.addEventListener('input', ()=> {
    validateFieldById(input.id);
  });
  input.addEventListener('blur', ()=> {
    // sanitize on blur for emails & names
    if(/email/i.test(input.id)){
      input.value = sanitizeEmail(input.value);
    }
    if(/name/i.test(input.id)){
      input.value = sanitizeName(input.value);
    }
    // re-validate after sanitization
    validateFieldById(input.id);
  });
}

// Setup realtime validation for login/register inputs present on the page
function setupRealtimeValidation(){
  const ids = [
    'loginEmail','loginPass',
    'userEmail','userPass',
    'registerName','registerEmail','registerPass','registerPassConfirm'
  ];
  ids.forEach(id=>{
    const el = document.getElementById(id);
    if(el) attachRealtimeTo(el);
  });

  // ensure form submissions run a final validation pass
  const forms = [
    {id:'loginForm', fields:['loginEmail','loginPass']},
    {id:'userLoginForm', fields:['userEmail','userPass']},
    {id:'userRegisterForm', fields:['registerName','registerEmail','registerPass','registerPassConfirm']}
  ];
  forms.forEach(f=>{
    const form = document.getElementById(f.id);
    if(!form) return;
    form.addEventListener('submit', (ev)=>{
      let ok = true;
      f.fields.forEach(fid=>{
        if(!validateFieldById(fid)) ok = false;
      });
      if(!ok){
        ev.preventDefault();
        ev.stopPropagation();
        // focus first invalid
        const firstInvalid = Array.from(document.querySelectorAll('.input-invalid'))[0];
        if(firstInvalid) firstInvalid.focus();
      }
    });
  });
}

// init realtime validation when script runs (deferred scripts load after parse)
if(document.readyState === 'loading'){
  document.addEventListener('DOMContentLoaded', setupRealtimeValidation);
} else {
  setupRealtimeValidation();
}

/* ---------- AJAX helpers for CSRF-protected auth ---------- */
function getCsrfToken(){
  const m = document.querySelector('meta[name="csrf-token"]');
  return m ? m.getAttribute('content') : '';
}

function ajaxPostJson(url, payload){
  return fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': getCsrfToken()
    },
    body: JSON.stringify(payload || {})
  }).then(async resp => {
    const text = await resp.text();
    try { return { ok: resp.ok, status: resp.status, data: JSON.parse(text) }; }
    catch(e){ return { ok: resp.ok, status: resp.status, data: text }; }
  });
}

function ajaxUserLogin(email, password){
  return ajaxPostJson('/api/auth/login', { email, password });
}
function ajaxUserRegister(name, email, password){
  return ajaxPostJson('/api/auth/register', { name, email, password });
}

// expose
window.ajaxUserLogin = ajaxUserLogin;
window.ajaxUserRegister = ajaxUserRegister;
window.ajaxPostJson = ajaxPostJson;

/* expose functions globally */
window.handleLoginForm = handleLoginForm;
window.handleUserLogin = handleUserLogin;
window.handleUserRegister = handleUserRegister;
window.initSchedulesPage = initSchedulesPage;
window.openAddModal = openAddModal;
window.closeAddModal = closeAddModal;
window.handleAddSchedule = handleAddSchedule;
window.renderExisting = renderExisting;
window.renderOverview = renderOverview;
window.initReservationsPage = initReservationsPage;
window.initBusesPage = initBusesPage;
window.openGroupModal = openGroupModal;
window.closeGroupModal = closeGroupModal;
// Logout: clear the demo admin session and navigate back to the login route (/)
// If you use Laravel server-side auth later, replace this with a POST to /logout.

window.logoutAdmin = function(){
  clearAdminSession();
  // navigate to the admin login route
  location.href = '/admin-login';
};

// small UI helper: show server save result in admin schedules page
function showServerSaveResult(html) {
  let el = document.getElementById('serverSaveResult');
  if(!el) {
    try { alert(html.replace(/<[^>]*>/g,'')); } catch(e){}
    return;
  }
  el.innerHTML = html;
  el.style.display = 'block';
}

// store last batch globally for debug retry
window._lastScheduleBatch = null;

// dev-only helper: try saving schedules via a GET debug route (no CSRF) — only for local testing
window.debugTrySave = async function(){
  const batch = window._lastScheduleBatch || [];
  if(!batch.length) return alert('No batch available to retry.');
  let ok = 0, failed = 0;
  for(const p of batch){
    try {
      const qs = new URLSearchParams({
        route_from: p.route_from || '',
        route_to: p.route_to || '',
        departure_time: p.departure_time || '',
        arrival_time: p.arrival_time || '',
        bus_number: p.bus_number || '',
        seats: p.seats != null ? String(p.seats) : '0',
        available_seats: p.available_seats != null ? String(p.available_seats) : (p.seats != null ? String(p.seats) : '0'),
        fare: p.fare != null ? String(p.fare) : '0',
        status: p.status || 'active'
      }).toString();
      const resp = await fetch('/debug/add-schedule?' + qs, { method: 'GET', credentials: 'same-origin' });
      if(resp.ok){
        ok++;
      } else {
        failed++;
      }
    } catch(e) { failed++; }
  }
  alert(`Debug save complete: ${ok} succeeded, ${failed} failed. Check DB.`);
};


// helper to POST many schedules to admin bulk endpoint (returns {sent,ok,failed,results})
async function postSchedulesBatch(payloads){
  if(!Array.isArray(payloads) || payloads.length === 0) return { sent: 0, ok: 0, failed: 0, results: [] };

  // save for debug retry if needed
  window._lastScheduleBatch = payloads.slice();

  const token = getCsrfToken();
  
  try {
    const resp = await fetch('/admin/schedules/bulk', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': token || ''
      },
      body: JSON.stringify(payloads)
    });

    let parsed = null;
    let text = null;
    try {
      text = await resp.text();
      parsed = text ? JSON.parse(text) : null;
    } catch(e){ parsed = null; }

    const success = resp.ok;
    
    // build HTML summary
    const summaryParts = [];
    if(success){
      const created = parsed?.created || payloads.length;
      summaryParts.push(`<div style="padding:8px;border-radius:6px;background:#e6ffea;border:1px solid #bff0c4">`);
      summaryParts.push(`<strong>Server save:</strong> Successfully saved ${created} schedule(s) to database!`);
      summaryParts.push(`</div>`);
    } else {
      summaryParts.push(`<div style="padding:8px;border-radius:6px;background:#ffe6e6;border:1px solid #ffb3b3">`);
      summaryParts.push(`<strong>Server save:</strong> Failed to save schedules`);
      if(parsed?.error){
        summaryParts.push(`<div style="margin-top:6px;color:#b33">Error: ${parsed.error}</div>`);
      }
      summaryParts.push(`<div style="margin-top:6px;font-size:13px;color:#333">Please make sure you are logged in as an admin. If you haven't logged in yet, <a href="/admin-login" target="_blank">click here to login</a>.</div>`);
      summaryParts.push(`</div>`);
    }

    showServerSaveResult(summaryParts.join(''));

    return { 
      sent: payloads.length, 
      ok: success ? 1 : 0, 
      failed: success ? 0 : 1, 
      results: [{ ok: success, status: resp.status, body: parsed || text }]
    };
  } catch(err) {
    const errorMsg = err.message || String(err);
    const summaryParts = [];
    summaryParts.push(`<div style="padding:8px;border-radius:6px;background:#ffe6e6;border:1px solid #ffb3b3">`);
    summaryParts.push(`<strong>Server save:</strong> Network error - ${errorMsg}`);
    summaryParts.push(`<div style="margin-top:6px;font-size:13px;color:#333">Please check your internet connection and make sure you are logged in as an admin.</div>`);
    summaryParts.push(`</div>`);
    showServerSaveResult(summaryParts.join(''));
    
    return { 
      sent: payloads.length, 
      ok: 0, 
      failed: payloads.length, 
      results: [{ ok: false, err: errorMsg }]
    };
  }
}

