/* reservations.js – USER VERSION MATCHING ADMIN TABLE */

const STORAGE_KEY = "gobus_demo";

function loadState() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) || "{}");
  } catch (e) {
    return { schedules: {}, reservations: [] };
  }
}
function saveState(s) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(s));
}

const container = document.getElementById("reservationsContainer");

function renderReservations() {
  const state = loadState();
  const res = state.reservations || [];
  container.innerHTML = "";

  if (!res.length) {
    container.innerHTML = `
      <div class="card" style="padding:20px;border-radius:12px;border:1px solid #eef2f7;">
        No reservations yet.
      </div>`;
    return;
  }

  const tableWrapper = document.createElement("div");
  tableWrapper.className = "card";
  tableWrapper.style.padding = "0";

  const table = document.createElement("table");
  table.className = "table";

  table.innerHTML = `
  <thead>
    <tr>
      <th>Trip</th>
      <th>Trip Type</th>
      <th>Date</th>
      <th>Time</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
`;

  const tbody = document.createElement("tbody");

  res.forEach((r) => {
    const stateNow = loadState();
    const parts = r.key.split("|");

    const date = parts[0];
    const trip = `${parts[1]} → ${parts[2]}`;

    let schList = stateNow.schedules[r.key] || [];
    const sch = schList.find((s) => s.id === r.scheduleId);
    const time = sch ? sch.time : "-";
    const price = sch ? sch.price : 0;
    const total = price * r.qty;

    const tripType = r.tripType || "One Way";
    const passengers = `${r.adults} Adult(s)${
      r.children ? `, ${r.children} Child` : ""
    }`;

    let statusClass = "status-txt-pending";
    if (r.status === "confirmed") statusClass = "status-txt-confirmed";
    if (r.status === "cancelled") statusClass = "status-txt-cancelled";

    let actionBtn = "";
    if (r.status === "pending") {
      actionBtn = `<button class="cancel-btn" data-id="${r.id}">Cancel</button>`;
    } else {
      actionBtn = `<span class="no-action">—</span>`;
    }

    const tr = document.createElement("tr");
    tr.classList.add("res-row");
    tr.dataset.id = r.id;

   tr.innerHTML = `
  <td>${trip}</td>
  <td>${tripType}</td>
  <td>${date}</td>
  <td>${time}</td>
  <td><span class="${statusClass}">${r.status.toUpperCase()}</span></td>
  <td style="text-align:center;">${actionBtn}</td>
`;

    tbody.appendChild(tr);
  });

  table.appendChild(tbody);
  tableWrapper.appendChild(table);
  container.appendChild(tableWrapper);

  /* CANCEL BUTTONS */
  document.querySelectorAll(".cancel-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const id = btn.dataset.id;
      const s = loadState();
      const idx = s.reservations.findIndex((x) => x.id === id);
      if (idx === -1) return;
      s.reservations[idx].status = "cancelled";
      saveState(s);
      renderReservations();
    });
  });

  /* CLICK ROW TO VIEW DETAILS */
  document.querySelectorAll(".res-row").forEach((row) => {
    row.addEventListener("click", () => {
      openReservationModal(row.dataset.id);
    });
  });
}

function openReservationModal(id) {
  const state = loadState();
  const r = state.reservations.find((x) => x.id === id);
  if (!r) return;

  const parts = r.key.split("|");
  const date = parts[0];
  const route = `${parts[1]} → ${parts[2]}`;

  const sch = (state.schedules[r.key] || []).find((s) => s.id === r.scheduleId);
  const time = sch ? sch.time : "-";
  const price = sch ? sch.price : 0;
  const total = price * r.qty;

  const tripType = r.tripType || "One Way";

  document.getElementById("userDetailBody").innerHTML = `
    <p><strong>Trip Type:</strong> ${tripType}</p>
    <p><strong>Route:</strong> ${route}</p>
    <p><strong>Date:</strong> ${date}</p>
    <p><strong>Time:</strong> ${time}</p>
    <p><strong>Bus Type:</strong> ${r.busType}</p>
    <p><strong>Passengers:</strong> ${r.qty}</p>
    <p><strong>Seats:</strong> ${r.seats.join(", ")}</p>
    <p><strong>Price per ticket:</strong> ₱${price}</p>
    <h3><strong>Total Price:</strong> ₱${total}</h3>
  `;

  document.getElementById("userReservationModal").classList.add("open");
}

document.addEventListener("DOMContentLoaded", renderReservations);
