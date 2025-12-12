/* book.js — final fixed version
   - Login only when user clicks the FINAL BOOK NOW (finalBookBtn)
   - Seat confirm (bookNowBtn) moves to confirmation WITHOUT login
   - Uses loginSubmit button (loginEmail/loginPass IDs) from your HTML
*/

const STORAGE_KEY = 'gobus_demo';

/* ---------- Utility: load/save state ---------- */
function loadState() {
  const raw = localStorage.getItem(STORAGE_KEY);
  if (!raw) {
    const initial = {
      config:{ times:['08:00','12:00','16:00'], activeDays:['mon','tue','wed','thu','fri','sat','sun']},
      schedules:{},
      reservations:[],
      terminals:['Manila','Cebu','Davao','Baguio','Cagayan de Oro']
    };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(initial));
    return initial;
  }
  try { return JSON.parse(raw); } catch(e){ console.error('state parse error', e); return {config:{times:['08:00','12:00','16:00'],activeDays:['mon','tue','wed','thu','fri','sat','sun']},schedules:{},reservations:[],terminals:['Manila','Cebu','Davao']} }
}
function saveState(s){ localStorage.setItem(STORAGE_KEY, JSON.stringify(s)); }

/* ---------- Elements ---------- */
const fromSelect = document.getElementById('fromSelect');
const toSelect = document.getElementById('toSelect');
const departDate = document.getElementById('departDate');
const returnDate = document.getElementById('returnDate');
const adults = document.getElementById('adults');
const children = document.getElementById('children');
const busType = document.getElementById('busType');
const searchBtn = document.getElementById('searchSchedules');
const onewayBtn = document.getElementById('onewayBtn');
const roundBtn = document.getElementById('roundBtn');
roundBtn.addEventListener("click", () => {
    document.querySelector(".form-grid").classList.add("roundtrip-active");
});

onewayBtn.addEventListener("click", () => {
    document.querySelector(".form-grid").classList.remove("roundtrip-active");
});

const roundOnlyFields = document.querySelectorAll('.round-only');

/* modals */
const scheduleList = document.getElementById('scheduleList');
const seatMapEl = document.getElementById('seatMap');
const bookNowBtn = document.getElementById('bookNowBtn');
const finalBookBtn = document.getElementById("finalBookBtn");

/* close modal handlers (defensive) */
document.querySelectorAll('.close-modal').forEach(btn=>{
  btn.addEventListener('click', ()=> closeModal(btn.dataset.close));
});

/* state */
let state = loadState();
let currentSearch = null; // {from,to,date,passengers,preferredBus}
let currentSchedule = null; // chosen schedule object {id,time,busType,price,capacity}
let chosenSeats = [];
function isLoggedIn() { return !!localStorage.getItem("gobus_user"); }

/* Utility to require login before continuing.
   nextAction values:
     - 'seat_selection'  : return to seat modal so user can confirm and press BOOK NOW
     - 'confirm_booking' : continue and run final booking
*/
function requireLogin(nextAction){
  if(isLoggedIn()) return false; // no login required
  if(nextAction) sessionStorage.setItem('gobus_pending', nextAction);
  showModal('loginModal');
  return true;
}

/* small init date */
(function initDates(){
  const d=new Date(); d.setDate(d.getDate()+1);
  if(departDate) departDate.value = d.toISOString().split('T')[0];
})();

/* tab toggle */
/* tab toggle */
if(onewayBtn && roundBtn){
  onewayBtn.addEventListener('click', ()=>{ 
    onewayBtn.classList.add('active'); 
    roundBtn.classList.remove('active'); 
    
    // hide return date
    document.querySelectorAll(".round-only").forEach(el => el.classList.add("hidden"));

    // REMOVE round-trip layout class
    document.querySelector(".form-grid").classList.remove("round-trip-active");
  });

  roundBtn.addEventListener("click", () => {
    roundBtn.classList.add("active");
    onewayBtn.classList.remove("active");

    // show return date
    document.querySelectorAll(".round-only").forEach(el => el.classList.remove("hidden"));

    // ENABLE round-trip layout class
    document.querySelector(".form-grid").classList.add("round-trip-active");
  });
}


/* search -> show schedules modal */
if(searchBtn){
  searchBtn.addEventListener('click', async ()=> {
    const from = fromSelect ? fromSelect.value : '';
    const to = toSelect ? toSelect.value : '';
    const date = departDate ? departDate.value : '';
    const rdate = returnDate ? returnDate.value : '';
    const a = adults ? (parseInt(adults.value)||1) : 1;
    const c = children ? (parseInt(children.value)||0) : 0;
    const pax = a + c;
    const prefBus = busType ? busType.value : '';

    if(!date){ alert('Please select departure date'); return; }
    if(from === to){ alert('From and To cannot be the same'); return; }

    currentSearch = { from, to, date, rdate, adults:a, children:c, pax, prefBus };
    
    // Show loading state while getting dynamic pricing
    showDynamicPricingLoading();
    
    try {
      // Get dynamic pricing for 7 days
      const pricingData = await getDynamicPricing(from, to, date, prefBus);
      
      // Update current search with pricing data
      currentSearch.dynamicPricing = pricingData;
      
      // Open schedules with dynamic pricing
      openSchedulesForSearch(currentSearch);
      
    } catch (error) {
      console.error('Dynamic pricing error:', error);
      alert('Warning: Could not load dynamic pricing. Using standard rates.');
      
      // Continue without dynamic pricing
      openSchedulesForSearch(currentSearch);
    }
  });
}

// helper for building keys (must match admin.keyFor)
function keyFor(date, from, to){
  return `${date}|${(from || '').trim().toLowerCase()}|${(to || '').trim().toLowerCase()}`;
}

/* open schedules: read admin-managed schedules from state */
function openSchedulesForSearch(search){
  if(!scheduleList) return;
  scheduleList.innerHTML = '';
  const key = keyFor(search.date, search.from, search.to);
  let routeSchedules = state.schedules[key];

  if(!routeSchedules){
    for(const savedKey in state.schedules){
      if(savedKey.toLowerCase() === key.toLowerCase()){
        routeSchedules = state.schedules[savedKey];
        break;
      }
    }
  }

  routeSchedules = routeSchedules || [];

  if(routeSchedules.length === 0){
    const noCard = document.createElement('div');
    noCard.className = 'schedule-card';
    noCard.innerHTML = `<div class="left"><strong>No schedules available</strong><div class="muted">There are no schedules for ${search.from} → ${search.to} on ${search.date}.</div></div>`;
    scheduleList.appendChild(noCard);
    showModal('modalSchedules');
    return;
  }


  routeSchedules.forEach(sch=>{
    const reservedCount = state.reservations.filter(r=>r.scheduleId === sch.id).reduce((a,b)=>a+b.qty,0);
    const capacity = (typeof sch.capacity === 'number') ? sch.capacity : (sch.busType === 'deluxe' ? 20 : 40);
    const remaining = Math.max(0, capacity - reservedCount);

    // Apply dynamic pricing if available
    let scheduleWithPricing = {...sch};
    if (search.dynamicPricing && search.dynamicPricing.length > 0) {
      scheduleWithPricing = applyDynamicPricingToSchedule(scheduleWithPricing, search.dynamicPricing);
    }

    const card = document.createElement('div');
    card.className = 'schedule-card';

    if (remaining <= 0) {
      card.classList.add('disabled-card');
      const priceDisplay = search.dynamicPricing ? getDynamicPriceDisplay(scheduleWithPricing) : `₱${sch.price ?? '—'}`;
      card.innerHTML = `
        <div class="left">
          <strong>${sch.time}</strong>
          <div class="muted">${search.date} • ${sch.busType === 'deluxe' ? 'Deluxe' : 'Regular'} • ${priceDisplay}</div>
        </div>
        <div class="mid">Seats left: <strong>0</strong></div>
        <div class="right fully-booked-text">Fully Booked</div>
      `;
    } else {
      const priceDisplay = search.dynamicPricing ? getDynamicPriceDisplay(scheduleWithPricing) : `₱${sch.price ?? '—'}`;
      card.innerHTML = `
        <div class="left">
          <strong>${sch.time}</strong>
          <div class="muted">${search.date} • ${sch.busType === 'deluxe' ? 'Deluxe' : 'Regular'} • ${priceDisplay}</div>
        </div>
        <div class="mid">Seats left: <strong>${remaining}</strong></div>
        <div class="right"><button class="btn-select" data-sid="${sch.id}" data-key="${key}">Select Seat</button></div>
      `;
    }

    scheduleList.appendChild(card);
  });

  // attach handlers (delegation safe)
  scheduleList.querySelectorAll('.btn-select').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const sid = btn.dataset.sid;
      const routeKey = btn.dataset.key;
      let routeSchedules = state.schedules[routeKey];

      if(!routeSchedules){
        for(const k in state.schedules){
          if(k.toLowerCase() === routeKey.toLowerCase()){
            routeSchedules = state.schedules[k];
            break;
          }
        }
      }
      routeSchedules = routeSchedules || [];

      const scheduleObj = routeSchedules.find(x=>x.id === sid);
      if(!scheduleObj){ alert('Schedule not found'); return; }
      const reservedCount = state.reservations.filter(r=>r.scheduleId === scheduleObj.id).reduce((a,b)=>a+b.qty,0);
      const capacity = (typeof scheduleObj.capacity === 'number') ? scheduleObj.capacity : (scheduleObj.busType === 'deluxe' ? 20 : 40);
      const remaining = Math.max(0, capacity - reservedCount);
      if(remaining <= 0){ alert('Sorry, this schedule is fully booked'); openSchedulesForSearch(currentSearch); return; }

      // Important: DO NOT require login here. Only open seat modal.
      currentSchedule = { ...scheduleObj, routeKey, date: currentSearch.date, from: currentSearch.from, to: currentSearch.to };
      openSeatModal(currentSchedule, remaining);
    });
  });

  showModal('modalSchedules');
}

/* open seat selection modal */
function openSeatModal(scheduleObj, remainingSeats){
  if(!seatMapEl) return;
  seatMapEl.innerHTML = '';
  // preserve chosenSeats only if same schedule (so user coming back retains selections)
  if(!currentSchedule || currentSchedule.id !== scheduleObj.id) chosenSeats = [];

  const busTypeLocal = scheduleObj.busType;
  const rows = (busTypeLocal === 'deluxe') ? 5 : 10;
  const cols = 4;
  const rowLabels = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');

  const booked = new Set();
  state.reservations.filter(r=>r.scheduleId === scheduleObj.id).forEach(r=>{ (r.seats || []).forEach(s=> booked.add(s)); });

  for(let r=0;r<rows;r++){
    for(let c=1;c<=cols;c++){
      const code = `${rowLabels[r]}${c}`;
      const seatEl = document.createElement('div');
      seatEl.className = 'seat';
      seatEl.dataset.code = code;
      seatEl.textContent = code;
      if(booked.has(code)){
        seatEl.classList.add('booked');
      } else {
        seatEl.classList.add('available');
        seatEl.addEventListener('click', ()=> toggleSeatSelection(seatEl, scheduleObj));
      }
      seatMapEl.appendChild(seatEl);
    }
  }

  // Show seat modal. NOTE: no login check here.
  showModal('modalSeats');
}

/* toggle seat selection */
function toggleSeatSelection(el, scheduleObj){
  if(el.classList.contains('booked')) return;
  const code = el.dataset.code;
  const idx = chosenSeats.indexOf(code);
  const pax = currentSearch ? (currentSearch.pax || 1) : 1;
  if(idx >=0){
    chosenSeats.splice(idx,1);
    el.classList.remove('chosen');
    el.classList.add('available');
  } else {
    if(chosenSeats.length >= pax){
      alert(`You can only select up to ${pax} seats (adults + children).`);
      return;
    }
    chosenSeats.push(code);
    el.classList.remove('available');
    el.classList.add('chosen');
  }
}

/* BOOK NOW (seat confirm) — NO LOGIN HERE */
if(bookNowBtn){
  bookNowBtn.addEventListener("click", (e) => {
    e.preventDefault();

    if (!currentSchedule) {
      alert("No schedule selected");
      return;
    }

    const pax = currentSearch ? (currentSearch.pax || 1) : 1;
    if (chosenSeats.length !== pax) {
      alert(`Please select exactly ${pax} seat(s).`);
      return;
    }

    // Populate confirmation details (no login)
    const total = (currentSchedule.price || 0) * pax;
    const detailsEl = document.getElementById("confirmDetails");
    if(detailsEl){
      detailsEl.innerHTML = `
        <p><strong>Trip Type:</strong> ${currentSearch && currentSearch.rdate ? "Roundtrip" : "One Way"}</p>
        <p><strong>Route:</strong> ${currentSchedule.from} → ${currentSchedule.to}</p>
        <p><strong>Date:</strong> ${currentSchedule.date}</p>
        <p><strong>Time:</strong> ${currentSchedule.time}</p>
        <p><strong>Bus Type:</strong> ${currentSchedule.busType}</p>
        <p><strong>Seats:</strong> ${chosenSeats.join(", ")}</p>
        <p><strong>Price:</strong> ₱${currentSchedule.price} × ${pax}</p>
        <h3><strong>Total:</strong> ₱${total}</h3>
      `;
    }

    closeModal("modalSeats");
    showModal("modalConfirm");
  });
}

/* submitBooking function that finalizes reservation */
function submitBooking(){
  // basic safety checks
  if(!currentSchedule){ alert('No schedule selected.'); return; }
  const pax = currentSearch ? (currentSearch.pax || 1) : 1;
  if(chosenSeats.length !== pax){ alert(`Please select ${pax} seats.`); return; }

  const res = {
    id: "r_" + Math.random().toString(36).slice(2, 9),
    scheduleId: currentSchedule.id,
    key: `${currentSchedule.date}|${(currentSchedule.from||'').toLowerCase()}|${(currentSchedule.to||'').toLowerCase()}`,
    busType: currentSchedule.busType,
    seats: chosenSeats.slice(),
    qty: pax,
    adults: currentSearch ? currentSearch.adults : 0,
    children: currentSearch ? currentSearch.children : 0,
    tripType: currentSearch && currentSearch.rdate ? "Roundtrip" : "One Way",
    total: (currentSchedule.price || 0) * pax,
    status: "pending",
    createdAt: new Date().toISOString(),
  };

  state.reservations.push(res);
  saveState(state);

  closeModal("modalConfirm");
  closeModal("modalSchedules");

  alert(`Booking Successful! Your reservation ID is ${res.id}.`);
  window.location.href = "my-reservations.html";
}

/* FINAL BOOK button — login check happens HERE */
if(finalBookBtn){
  finalBookBtn.addEventListener("click", () => {
    // if not logged in, prompt login and mark pending action
    if(requireLogin('confirm_booking')){
      // close confirm modal while user logs in
      closeModal('modalConfirm');
      return;
    }
    // already logged in
    submitBooking();
  });
}

/* modal helpers */
function showModal(id){
  const el = document.getElementById(id);
  if(!el) return;
  el.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}
function closeModal(id){
  const el = document.getElementById(id);
  if(!el) return;
  el.classList.add('hidden');
  document.body.style.overflow = '';
}

/* -------------------------
   Custom popup calendar (B-1)
   ------------------------- */
function normalizeKeyPart(s){ return (s||'').toString().trim().toLowerCase(); }
function getAvailableDatesForRoute(from, to){
  const dates = new Set();
  if(!state || !state.schedules) return dates;
  const wantFrom = normalizeKeyPart(from);
  const wantTo = normalizeKeyPart(to);
  for(const key in state.schedules){
    if(!state.schedules.hasOwnProperty(key)) continue;
    const parts = key.split('|');
    if(parts.length < 3) continue;
    const datePart = parts[0];
    const f = normalizeKeyPart(parts[1]);
    const t = normalizeKeyPart(parts[2]);
    if(f === wantFrom && t === wantTo){ dates.add(datePart); }
  }
  return dates;
}
const MONTH_NAMES = ['January','February','March','April','May','June','July','August','September','October','November','December'];
function prettyMonthYear(year, monthIndex){ return `${MONTH_NAMES[monthIndex]} ${year}`; }

function renderCalendarInto(popupEl, year, monthIndex, availableDatesSet, inputEl){
  popupEl.innerHTML = '';
  const header = document.createElement('div'); header.className='calendar-header';
  const label = document.createElement('div'); label.className='month-label'; label.textContent = prettyMonthYear(year, monthIndex);
  const nav = document.createElement('div'); nav.className='calendar-nav';
  const prevBtn = document.createElement('button'); prevBtn.innerHTML = '&#x25C0;'; prevBtn.title='Previous month';
  const nextBtn = document.createElement('button'); nextBtn.innerHTML = '&#x25B6;'; nextBtn.title='Next month';
  nav.appendChild(prevBtn); nav.appendChild(nextBtn);
  header.appendChild(label); header.appendChild(nav);
  popupEl.appendChild(header);

  const grid = document.createElement('div'); grid.className='calendar-grid';
  ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d=>{ const wd = document.createElement('div'); wd.className='weekday'; wd.textContent = d; grid.appendChild(wd); });

  const firstOfMonth = new Date(year, monthIndex, 1);
  const startDay = firstOfMonth.getDay();
  const daysInMonth = new Date(year, monthIndex+1, 0).getDate();

  for(let i=0;i<startDay;i++){ const blank = document.createElement('div'); blank.className='calendar-day disabled'; blank.textContent = ''; grid.appendChild(blank); }

  for(let d=1; d<=daysInMonth; d++){
    const dateISO = `${year}-${String(monthIndex+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    const cell = document.createElement('div');
    cell.className = 'calendar-day';
    cell.textContent = d;

    if(availableDatesSet.has(dateISO)){
      cell.classList.add('available');
      cell.tabIndex = 0;
      cell.addEventListener('click', ()=>{
        inputEl.value = dateISO;
        if(inputEl.id === 'departDate'){
          if(returnDate && returnDate.value){ const ret = new Date(returnDate.value); const dep = new Date(inputEl.value); if(ret < dep) returnDate.value = ''; }
        }
        closeCalendarPopupFor(inputEl);
      });
      cell.addEventListener('keydown', (e)=>{ if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); cell.click(); } });
    } else {
      cell.classList.add('disabled');
    }
    grid.appendChild(cell);
  }

  popupEl.appendChild(grid);
  const footer = document.createElement('div'); footer.className='calendar-footer'; footer.textContent = 'Only highlighted dates have schedules'; popupEl.appendChild(footer);

  prevBtn.addEventListener('click', ()=>{ let ny = year, nm = monthIndex - 1; if(nm < 0){ ny = year - 1; nm = 11; } renderCalendarInto(popupEl, ny, nm, availableDatesSet, inputEl); });
  nextBtn.addEventListener('click', ()=>{ let ny = year, nm = monthIndex + 1; if(nm > 11){ ny = year + 1; nm = 0; } renderCalendarInto(popupEl, ny, nm, availableDatesSet, inputEl); });
}

function openCalendarPopupFor(inputEl){
  state = loadState();
  const from = (fromSelect && fromSelect.value) ? fromSelect.value : '';
  const to = (toSelect && toSelect.value) ? toSelect.value : '';
  if(!from || !to || from === to){ alert('Please select From and To terminals first (and make sure they are different).'); return; }
  const availableSet = getAvailableDatesForRoute(from, to);
  const popupEl = document.getElementById(inputEl.id === 'returnDate' ? 'calendarPopupReturn' : 'calendarPopupDepart');
  if(!availableSet || availableSet.size === 0){ popupEl.innerHTML = `<div style="padding:14px">No schedules found for ${from} → ${to}. Ask admin or choose another route/date.</div>`; popupEl.classList.remove('hidden'); popupEl.style.display = 'block'; return; }

  const sorted = Array.from(availableSet).sort();
  const initialISO = sorted[0];
  const [iy, im] = initialISO.split('-').map(Number);
  const year = iy; const monthIndex = im - 1;
  popupEl.classList.remove('hidden');
  renderCalendarInto(popupEl, year, monthIndex, availableSet, inputEl);
  popupEl.scrollTop = 0;
}
function closeCalendarPopupFor(inputEl){ const popupEl = document.getElementById(inputEl.id === 'returnDate' ? 'calendarPopupReturn' : 'calendarPopupDepart'); if(popupEl) popupEl.classList.add('hidden'); inputEl.focus(); }

(function attachCalendarHandlers(){
  const departInput = document.getElementById('departDate');
  const returnInput = document.getElementById('returnDate');
  if(departInput){ departInput.addEventListener('click', (e)=> { e.stopPropagation(); openCalendarPopupFor(departInput); }); }
  if(returnInput){ returnInput.addEventListener('click', (e)=> { e.stopPropagation(); openCalendarPopupFor(returnInput); }); }
  document.addEventListener('click', (e)=>{
    const popD = document.getElementById('calendarPopupDepart');
    const popR = document.getElementById('calendarPopupReturn');
    const path = e.composedPath ? e.composedPath() : (e.path || []);
    const clickedInsideDepart = path.includes(popD) || (path.includes(departInput));
    const clickedInsideReturn = path.includes(popR) || (path.includes(returnInput));
    if(!clickedInsideDepart && popD && !popD.classList.contains('hidden')) popD.classList.add('hidden');
    if(!clickedInsideReturn && popR && !popR.classList.contains('hidden')) popR.classList.add('hidden');
  });
})();

/* small init to reflect state */
(function boot(){ state = loadState(); })();

/* UI helpers */
const swapBtn = document.querySelector('.swap-icon');

if(swapBtn){
  swapBtn.addEventListener('click', () => {
    let from = document.getElementById("fromSelect");
    let to = document.getElementById("toSelect");
    let temp = from.value;
    from.value = to.value;
    to.value = temp;
  });
}

const backToSchedulesBtns = document.querySelectorAll('#backToSchedules');
backToSchedulesBtns.forEach(btn => btn.addEventListener('click', () => { closeModal('modalSeats'); showModal('modalSchedules'); }));

const navReservations = document.getElementById("navReservations");
if(navReservations){ navReservations.addEventListener("click", () => { if (!isLoggedIn()) { showModal('loginModal'); return; } window.location.href = "my-reservations.html"; }); }

/* LOGIN handling (use the IDs in your HTML: loginEmail, loginPass, loginSubmit) */
const loginSubmitBtn = document.getElementById("loginSubmit");
if(loginSubmitBtn){
  loginSubmitBtn.addEventListener("click", function (e) {
    e.preventDefault();
    const emailEl = document.getElementById("loginEmail");
    const passEl = document.getElementById("loginPass");

    const email = emailEl ? emailEl.value.trim() : '';
    const pass = passEl ? passEl.value : '';

    // simple inline validation
    if(!email){
      alert('Email is required.');
      return;
    }
    if(!email.includes('@')){
      alert('Enter a valid email.');
      return;
    }
    if(!pass || pass.length < 6){
      alert('Password must be at least 6 characters.');
      return;
    }

    // mark as logged in (demo)
    localStorage.setItem("gobus_user", "active");
    closeModal('loginModal');
    alert('Login success!');

    const pending = sessionStorage.getItem('gobus_pending');
    sessionStorage.removeItem('gobus_pending');

    if(pending === 'confirm_booking'){
      // continue with final booking
      submitBooking();
      return;
    }
    if(pending === 'seat_selection'){
      // reopen the seat modal so user can confirm and press BOOK NOW
      if(currentSchedule) openSeatModal(currentSchedule, 999);
      return;
    }
    // no pending -> do nothing
  });
}

// Close login modal handlers (if you add elements with .close-login)
const closeLoginBtns = document.querySelectorAll('.close-login');
closeLoginBtns.forEach(b=> b.addEventListener('click', ()=> closeModal('loginModal') ));

// OPEN REGISTER from login
document.getElementById("openRegister").addEventListener("click", () => {
  closeModal("loginModal");
  showModal("registerModal");
});

// OPEN LOGIN from register
document.getElementById("openLogin").addEventListener("click", () => {
  closeModal("registerModal");
  showModal("loginModal");
});

// CLOSE REGISTER MODAL
document.querySelector(".close-register").addEventListener("click", () => {
  closeModal("registerModal");
});



/* Dynamic Pricing Functions */
async function getDynamicPricing(from, to, startDate, busType) {
  try {
    // Calculate distance based on route (you can customize this)
    const distanceMap = {
      'Manila-Cebu': 937,
      'Manila-Davao': 1452,
      'Cebu-Davao': 518,
      'Manila-Baguio': 250,
      'Baguio-Cebu': 687,
      'Cagayan de Oro-Manila': 905
    };
    
    const routeKey = `${from}-${to}`;
    const reverseRouteKey = `${to}-${from}`;
    const distance = distanceMap[routeKey] || distanceMap[reverseRouteKey] || 300; // Default distance
    
    const params = new URLSearchParams({
      from: from,
      to: to,
      start_date: startDate,
      bus_type: busType || 'regular',
      distance_km: distance.toString()
    });
    
    const response = await fetch(`/api/dynamic-pricing?${params}`);
    const data = await response.json();
    
    if (data.success) {
      return data.pricing;
    } else {
      throw new Error(data.error || 'Failed to get dynamic pricing');
    }
  } catch (error) {
    console.error('Dynamic pricing error:', error);
    throw error;
  }
}

function showDynamicPricingLoading() {
  if (!scheduleList) return;
  scheduleList.innerHTML = `
    <div class="schedule-card">
      <div class="left">
        <strong>Loading Dynamic Pricing...</strong>
        <div class="muted">Calculating 7-day demand-based pricing for your route</div>
      </div>
      <div class="mid">Please wait</div>
      <div class="right">
        <div class="loading-spinner"></div>
      </div>
    </div>
  `;
  showModal('modalSchedules');
}

function applyDynamicPricingToSchedule(schedule, dynamicPricing) {
  // Find pricing for the specific date
  const datePricing = dynamicPricing.find(p => p.date === schedule.date);
  
  if (datePricing) {
    // Apply dynamic price and add pricing info
    schedule.originalPrice = schedule.price;
    schedule.price = datePricing.final_price;
    schedule.dynamicPricingInfo = {
      basePrice: datePricing.base_price,
      demandScore: datePricing.demand_prediction,
      isWeekend: datePricing.is_weekend,
      isHoliday: datePricing.is_holiday,
      priceBreakdown: datePricing.price_breakdown,
      dayOfWeek: datePricing.day_of_week
    };
    
    // Add price change indicator
    if (schedule.originalPrice !== schedule.price) {
      const changePercent = ((schedule.price - schedule.originalPrice) / schedule.originalPrice * 100).toFixed(1);
      schedule.priceChange = changePercent > 0 ? `+${changePercent}%` : `${changePercent}%`;
    }
  }
  
  return schedule;
}

function getDynamicPriceDisplay(schedule) {
  if (!schedule.dynamicPricingInfo) {
    return `₱${schedule.price ?? '—'}`;
  }
  
  const { demandScore, isWeekend, isHoliday, dayOfWeek } = schedule.dynamicPricingInfo;
  
  // Create price display with dynamic info
  let priceHtml = `<span class="dynamic-price">₱${schedule.price}</span>`;
  
  // Add price change indicator
  if (schedule.priceChange) {
    const changeClass = schedule.priceChange.startsWith('+') ? 'price-up' : 'price-down';
    priceHtml += ` <span class="price-change ${changeClass}">${schedule.priceChange}</span>`;
  }
  
  // Add demand indicator
  const demandClass = demandScore > 0.7 ? 'high-demand' : demandScore > 0.4 ? 'medium-demand' : 'low-demand';
  priceHtml += ` <span class="demand-indicator ${demandClass}" title="Demand Level: ${(demandScore * 100).toFixed(0)}%">${isWeekend ? 'Weekend' : isHoliday ? 'Holiday' : dayOfWeek}</span>`;
  
  return priceHtml;
}

/* END of book.js */
