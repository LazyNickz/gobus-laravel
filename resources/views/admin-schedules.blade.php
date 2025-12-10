<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin — Schedules | GoBus</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="{{url('frontend/admin.css')}}">
  <script defer src="{{ asset('frontend/admin.js') }}"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body onload="initSchedulesPage(); setupSidebar();">
  <header class="header">
    <div class="brand"><span class="logo" style="color:#2b9cff">GoBus admin</span></div>
  </header>

  <div class="admin-wrap">
    <aside class="sidebar">
  <div class="item" data-target="/admin/schedules" onclick="location.href='/admin/schedules'"><i class="fa fa-calendar"></i> Schedules</div>
  <div class="item" data-target="/admin/reservations" onclick="location.href='/admin/reservations'"><i class="fa fa-list"></i> Reservations</div>
      <div style="flex:1"></div>
      <div class="item" onclick="logoutAdmin()"><i class="fa fa-sign-out-alt"></i> Logout</div>
    </aside>

    <main class="content">
<div class="card">
  <div style="
      display:flex;
      flex-wrap:nowrap;
      gap:90px;
      align-items:flex-end;
      overflow:hidden;
  ">
    
    <div style="width:150px">
      <label>Start Date</label>
      <input type="date" id="schedDate" />
    </div>

    <div style="width:150px">
      <label>End Date</label>
      <input type="date" id="schedEndDate" />
    </div>

    <div style="width:160px">
      <label>Terminal - From</label>
      <input type="text" id="schedFrom" placeholder="e.g. Manila" />
    </div>

    <div style="width:160px">
      <label>Terminal - To</label>
      <input type="text" id="schedTo" placeholder="e.g. Cebu" />
    </div>

    <div style="width:140px">
      <label>Trip Type</label>
  <select id="tripType">
    <option value="single">One-way</option>
    <option value="round">Round-trip</option>
</select>
  </div>
  </div>
</div>
        
      <div class="card">
        <h3>Preset Hours</h3>
        <div id="timesList" class="times-list" style="margin-bottom:10px"></div>
        <div style="display:flex;gap:8px;align-items:center">
          <input id="newTime" placeholder="08:00" style="width:120px" />
          <button id="addTime" class="btn btn-ghost">Add time</button>
          <button id="saveConfig" class="btn btn-ghost">Save config</button>
        </div>
        <div style="margin-top:12px;color:var(--muted)">Default preset times are 08:00, 12:00, 16:00. Admin can add/remove times.</div>
      </div>

      <div class="card">
        <h3>Active Days</h3>
        <div id="activeDays"></div>
        <div style="margin-top:8px">
          <button id="setWeekdays" class="btn btn-ghost">Set: Weekdays</button>
          <button id="setWeekends" class="btn btn-ghost">Set: Weekends</button>
          <button id="setAllDays" class="btn btn-ghost">Set: All days</button>
        </div>
      </div>

      <div class="card">
        <h3>Generate / Manage</h3>

        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:10px">
          <!-- NOTE: generator will use PRESET HOURS (timesList) as requested -->
          <div style="min-width:220px">
            <label>Bus Types to generate</label>
            <select id="genBusChoice">
              <option value="regular">Regular only</option>
              <option value="deluxe">Deluxe only</option>
              <option value="both">Both Regular + Deluxe</option>
            </select>
          </div>

          <!-- Single price input (used when selecting single type) -->
          <div id="genPriceWrap" style="min-width:140px">
            <label>Price (₱)</label>
            <input id="genPrice" type="number" placeholder="e.g. 650" />
          </div>

          <!-- When BOTH selected, show separate price inputs -->
          <div id="genDualPrices" style="display:none;gap:8px;align-items:center">
            <div style="min-width:140px">
              <label>Price Regular (₱)</label>
              <input id="genPriceRegular" type="number" placeholder="e.g. 600" />
            </div>
            <div style="min-width:140px">
              <label>Price Deluxe (₱)</label>
              <input id="genPriceDeluxe" type="number" placeholder="e.g. 900" />
            </div>
          </div>

          <!-- Capacities -->
          <div id="genCapWrap" style="min-width:140px">
            <label>Capacity</label>
            <input id="genCapacity" type="number" placeholder="Leave empty for default" />
          </div>

          <div id="genDualCaps" style="display:none;gap:8px;align-items:center">
            <div style="min-width:140px">
              <label>Cap Regular</label>
              <input id="genCapRegular" type="number" placeholder="e.g. 40" />
            </div>
            <div style="min-width:140px">
              <label>Cap Deluxe</label>
              <input id="genCapDeluxe" type="number" placeholder="e.g. 20" />
            </div>
          </div>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <button id="autoGenerate" class="btn btn-primary">Generate schedules (preset hours)</button>
        </div>
      </div>

      <div class="card">
        <h3>Schedules Overview</h3>
        <div id="schedulesOverview">
          <!-- grouped schedule entries will appear here -->
        </div>
      </div>

      <div class="legend">
        <div><span class="box available"></span> Available</div>
        <div><span class="box booked"></span> Booked</div>
        <div><span class="box selected"></span> Selected</div>
      </div>
    </main>
  </div>

  <!-- Add Schedule modal (kept for manual single adds if needed) -->
  <div id="addScheduleModal" class="modal-overlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-label="Add schedule">
      <div class="modal-header">
        <h3>Add Schedule</h3>
        <button class="close" onclick="closeAddModal()">×</button>
      </div>

      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <div style="flex:1;min-width:200px">
          <label>Start Date</label>
          <input id="modalStartDate" type="date" />
        </div>

        <div style="flex:1;min-width:200px">
          <label>End Date (optional)</label>
          <input id="modalEndDate" type="date" />
          <small style="display:block;color:var(--muted)">Leave empty to add only the Start Date</small>
        </div>

        <div style="flex:1;min-width:160px">
          <label>Time (HH:MM)</label>
          <input id="modalTime" type="text" placeholder="08:00" />
        </div>

        <div style="flex:1;min-width:160px">
          <label>Trip Type</label>
          <select id="modalTripType">
            <option value="single">Single</option>
            <option value="round">Round-trip</option>
          </select>
        </div>

        <div style="flex:1;min-width:160px">
          <label>Bus Type</label>
          <select id="modalBusType">
            <option value="regular">Regular (40)</option>
            <option value="deluxe">Deluxe (20)</option>
          </select>
        </div>

        <div style="flex:1;min-width:160px">
          <label>Capacity (optional)</label>
          <input id="modalCapacity" type="number" placeholder="e.g. 40" />
        </div>

        <div style="flex:1;min-width:160px">
          <label>Price (₱)</label>
          <input id="modalPrice" type="number" placeholder="e.g. 650" />
        </div>
      </div>

      <div style="display:flex;justify-content:flex-end;margin-top:12px;gap:8px">
        <button class="btn btn-ghost" onclick="closeAddModal()">Cancel</button>
        <button class="btn btn-primary" onclick="handleAddSchedule()">Add Schedule</button>
      </div>
    </div>
  </div>

  <!-- Group modal to show full list for a grouped schedule -->
  <div id="groupModal" class="modal-overlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-label="Schedule group details">
      <div class="modal-header">
        <h3 id="groupModalTitle">Group schedules</h3>
        <button class="close" onclick="closeGroupModal()">×</button>
      </div>
      <div id="groupModalBody" style="max-height:60vh; overflow:auto;"></div>
      <div style="display:flex;justify-content:flex-end;margin-top:12px;gap:8px">
        <button class="btn btn-ghost" onclick="closeGroupModal()">Close</button>
      </div>
    </div>
  </div>

  <!-- Schedule details modal (per-schedule detail opened from group modal) -->
  <div id="scheduleDetailsModal" class="modal-overlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-label="Schedule details">
      <div class="modal-header">
        <h3 id="scheduleDetailsTitle">Schedule details</h3>
        <button class="close" onclick="document.getElementById('scheduleDetailsModal').classList.remove('open')">×</button>
      </div>
      <div id="scheduleDetailsList" style="max-height:50vh; overflow:auto;"></div>
      <div style="display:flex;justify-content:flex-end;margin-top:12px;gap:8px">
        <button class="btn btn-ghost" onclick="document.getElementById('scheduleDetailsModal').classList.remove('open')">Close</button>
      </div>
    </div>
  </div>

</body>
</html>
