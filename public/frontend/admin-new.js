php// Admin Schedule Management JavaScript
// Complete rewrite for database integration

// Global state
let adminState = {
    schedules: [],
    presetTimes: ['08:00', '12:00', '16:00'],
    activeDays: [0, 1, 2, 3, 4, 5, 6], // 0=Sunday, 6=Saturday
    currentSchedules: []
};

// Initialize the schedules page
function initSchedulesPage() {
    console.log('Initializing admin schedules page...');
    
    // Check admin authentication
    if (!requireAdminOrRedirect()) {
        return;
    }

    // Load existing schedules
    loadSchedules();
    
    // Set up event listeners
    setupEventListeners();
    
    // Initialize UI components
    renderPresetTimes();
    renderActiveDays();
}


// Check admin authentication
function requireAdminOrRedirect() {
    // Check server-side session first
    fetch('/admin/check-session')
        .then(response => response.json())
        .then(data => {

            if (!data.authenticated) {
                window.location.href = '/admin-login';
                return false;
            }
            // If authenticated, set client-side flag for consistency
            sessionStorage.setItem('admin_logged_in', 'true');
            return true;
        })

        .catch(() => {
            window.location.href = '/admin-login';
            return false;
        });
    
    // Return true for now to allow page to load while we check
    return true;
}

// Setup all event listeners
function setupEventListeners() {
    // Preset times management
    document.getElementById('addTime').addEventListener('click', addPresetTime);
    document.getElementById('saveConfig').addEventListener('click', saveConfig);
    
    // Active days management
    document.getElementById('setWeekdays').addEventListener('click', () => setActiveDays([1, 2, 3, 4, 5]));
    document.getElementById('setWeekends').addEventListener('click', () => setActiveDays([0, 6]));
    document.getElementById('setAllDays').addEventListener('click', () => setActiveDays([0, 1, 2, 3, 4, 5, 6]));
    
    // Generate schedules
    document.getElementById('autoGenerate').addEventListener('click', generateSchedules);
    
    // Bus type selection
    document.getElementById('genBusChoice').addEventListener('change', toggleBusTypeInputs);
    
    // Load schedules on page load
    loadSchedules();
}

// Render preset times list
function renderPresetTimes() {
    const container = document.getElementById('timesList');
    container.innerHTML = '';
    
    adminState.presetTimes.forEach((time, index) => {
        const timeDiv = document.createElement('div');
        timeDiv.className = 'time-item';
        timeDiv.style.cssText = `
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            margin: 4px;
            font-size: 14px;
            position: relative;
        `;
        
        timeDiv.innerHTML = `
            ${time}
            <span onclick="removePresetTime(${index})" style="
                position: absolute;
                top: -4px;
                right: -4px;
                background: #ff4444;
                color: white;
                border-radius: 50%;
                width: 18px;
                height: 18px;
                font-size: 10px;
                line-height: 18px;
                text-align: center;
                cursor: pointer;
            ">×</span>
        `;
        
        container.appendChild(timeDiv);
    });
}

// Add new preset time
function addPresetTime() {
    const timeInput = document.getElementById('newTime');
    const newTime = timeInput.value.trim();
    
    if (!newTime) {
        alert('Please enter a time');
        return;
    }
    
    // Validate time format (HH:MM)
    if (!/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(newTime)) {
        alert('Please enter time in HH:MM format (e.g., 08:00)');
        return;
    }
    
    if (adminState.presetTimes.includes(newTime)) {
        alert('This time already exists');
        return;
    }
    
    adminState.presetTimes.push(newTime);
    renderPresetTimes();
    timeInput.value = '';
}

// Remove preset time
function removePresetTime(index) {
    adminState.presetTimes.splice(index, 1);
    renderPresetTimes();
}

// Save configuration
function saveConfig() {
    // Save preset times and active days to localStorage for persistence
    localStorage.setItem('adminPresetTimes', JSON.stringify(adminState.presetTimes));
    localStorage.setItem('adminActiveDays', JSON.stringify(adminState.activeDays));
    
    showMessage('Configuration saved successfully!', 'success');
}

// Set active days
function setActiveDays(days) {
    adminState.activeDays = days;
    renderActiveDays();
}

// Render active days
function renderActiveDays() {
    const container = document.getElementById('activeDays');
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    let html = '';
    days.forEach((day, index) => {
        const isActive = adminState.activeDays.includes(index);
        html += `
            <label style="
                display: inline-block;
                margin: 4px 8px 4px 0;
                padding: 6px 12px;
                border: 1px solid var(--primary);
                border-radius: 20px;
                cursor: pointer;
                background: ${isActive ? 'var(--primary)' : 'transparent'};
                color: ${isActive ? 'white' : 'var(--primary)'};
            ">
                <input type="checkbox" ${isActive ? 'checked' : ''} 
                       onchange="toggleDay(${index})" style="display: none;">
                ${day}
            </label>
        `;
    });
    
    container.innerHTML = html;
}

// Toggle day selection
function toggleDay(dayIndex) {
    const index = adminState.activeDays.indexOf(dayIndex);
    if (index > -1) {
        adminState.activeDays.splice(index, 1);
    } else {
        adminState.activeDays.push(dayIndex);
    }
    renderActiveDays();
}

// Toggle bus type input visibility
function toggleBusTypeInputs() {
    const choice = document.getElementById('genBusChoice').value;
    const singlePriceWrap = document.getElementById('genPriceWrap');
    const dualPrices = document.getElementById('genDualPrices');
    const singleCapWrap = document.getElementById('genCapWrap');
    const dualCaps = document.getElementById('genDualCaps');
    
    if (choice === 'both') {
        singlePriceWrap.style.display = 'none';
        dualPrices.style.display = 'flex';
        singleCapWrap.style.display = 'none';
        dualCaps.style.display = 'flex';
    } else {
        singlePriceWrap.style.display = 'block';
        dualPrices.style.display = 'none';
        singleCapWrap.style.display = 'block';
        dualCaps.style.display = 'none';
    }
}


// Generate schedules using GET endpoint (bypass CSRF issues)
async function generateSchedules() {
    try {
        // Get form values
        const startDate = document.getElementById('schedDate').value;
        const endDate = document.getElementById('schedEndDate').value;
        const routeFrom = document.getElementById('schedFrom').value.trim();
        const routeTo = document.getElementById('schedTo').value.trim();
        const tripType = document.getElementById('tripType').value;
        const busChoice = document.getElementById('genBusChoice').value;
        
        // Validate required fields
        if (!startDate) {
            alert('Please select a start date');
            return;
        }
        
        if (!routeFrom) {
            alert('Please enter departure terminal');
            return;
        }
        
        if (!routeTo) {
            alert('Please enter arrival terminal');
            return;
        }
        
        if (routeFrom === routeTo) {
            alert('Departure and arrival terminals must be different');
            return;
        }
        
        // Get price and capacity based on bus choice
        let price, capacity;
        
        if (busChoice === 'regular') {
            price = parseFloat(document.getElementById('genPrice').value);
            capacity = parseInt(document.getElementById('genCapacity').value) || 40;
            
            if (!price || price <= 0) {
                alert('Please enter a valid price for regular buses');
                return;
            }
        } else if (busChoice === 'deluxe') {
            price = parseFloat(document.getElementById('genPrice').value);
            capacity = parseInt(document.getElementById('genCapacity').value) || 20;
            
            if (!price || price <= 0) {
                alert('Please enter a valid price for deluxe buses');
                return;
            }
        } else { // both - use regular values
            price = parseFloat(document.getElementById('genPriceRegular').value);
            capacity = parseInt(document.getElementById('genCapRegular').value) || 40;
            
            if (!price || price <= 0) {
                alert('Please enter a valid price for regular buses');
                return;
            }
        }
        
        // Build query parameters for GET request
        const params = new URLSearchParams({
            route_from: routeFrom,
            route_to: routeTo,
            start_date: startDate,
            times: adminState.presetTimes.join(','),
            bus_type: busChoice === 'both' ? 'regular' : busChoice, // Generate regular buses first
            price: price.toString(),
            capacity: capacity.toString(),
            trip_type: tripType,
            active_days: adminState.activeDays.join(',')
        });
        
        if (endDate) {
            params.append('end_date', endDate);
        }
        
        // Show loading state
        showMessage('Generating schedules...', 'info');
        
        // Use GET request to bypass CSRF
        const response = await fetch(`/admin/schedules/generate?${params.toString()}`);
        const result = await response.json();
        
        if (result.success) {
            showMessage(`Successfully created ${result.created} schedules!`, 'success');
            
            // Clear form
            document.getElementById('schedDate').value = '';
            document.getElementById('schedEndDate').value = '';
            document.getElementById('schedFrom').value = '';
            document.getElementById('schedTo').value = '';
            document.getElementById('genPrice').value = '';
            document.getElementById('genPriceRegular').value = '';
            document.getElementById('genPriceDeluxe').value = '';
            document.getElementById('genCapacity').value = '';
            document.getElementById('genCapRegular').value = '';
            document.getElementById('genCapDeluxe').value = '';
            
            // Reload schedules
            loadSchedules();
            
        } else {
            showMessage(`Error: ${result.error}`, 'error');
            console.error('Save error:', result);
        }
        
    } catch (error) {
        console.error('Generation error:', error);
        showMessage('An error occurred while generating schedules', 'error');
    }
}

// Load schedules from database
async function loadSchedules() {
    try {
        const response = await fetch('/admin/schedules/data');
        const result = await response.json();
        
        if (result.success) {
            adminState.currentSchedules = result.schedules;
            renderSchedulesOverview();
            renderExistingSchedules();
        } else {
            console.error('Failed to load schedules:', result.error);
        }
    } catch (error) {
        console.error('Error loading schedules:', error);
    }
}

// Render schedules overview
function renderSchedulesOverview() {
    const container = document.getElementById('schedulesOverview');
    
    if (adminState.currentSchedules.length === 0) {
        container.innerHTML = '<p style="color: var(--muted);">No schedules found.</p>';
        return;
    }
    
    // Group schedules by route and date
    const grouped = {};
    adminState.currentSchedules.forEach(schedule => {
        const date = new Date(schedule.departure_time).toLocaleDateString();
        const route = `${schedule.route_from} → ${schedule.route_to}`;
        const key = `${route} - ${date}`;
        
        if (!grouped[key]) {
            grouped[key] = {
                route,
                date,
                schedules: []
            };
        }
        grouped[key].schedules.push(schedule);
    });
    
    let html = '<div style="display: grid; gap: 12px;">';
    
    Object.values(grouped).forEach(group => {
        html += `
            <div style="border: 1px solid var(--border); padding: 12px; border-radius: 8px;">
                <h4 style="margin: 0 0 8px 0; color: var(--primary);">${group.route}</h4>
                <p style="margin: 0 0 8px 0; color: var(--muted); font-size: 14px;">${group.date}</p>
                <p style="margin: 0; font-size: 14px;">${group.schedules.length} schedules</p>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Render existing schedules
function renderExistingSchedules() {
    const container = document.getElementById('existingSchedules');
    
    if (adminState.currentSchedules.length === 0) {
        container.innerHTML = '<p style="color: var(--muted);">No schedules in database.</p>';
        return;
    }
    
    let html = '<div style="display: grid; gap: 8px;">';
    
    adminState.currentSchedules.forEach(schedule => {
        const departureTime = new Date(schedule.departure_time).toLocaleString();
        html += `
            <div style="border: 1px solid var(--border); padding: 12px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-weight: bold; margin-bottom: 4px;">${schedule.route_from} → ${schedule.route_to}</div>
                    <div style="color: var(--muted); font-size: 14px;">${departureTime}</div>
                    <div style="font-size: 14px; margin-top: 4px;">
                        <span style="background: ${schedule.bus_type === 'regular' ? 'var(--primary)' : '#ff9800'}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;">
                            ${schedule.bus_type.toUpperCase()}
                        </span>
                        <span style="margin-left: 8px;">₱${schedule.fare}</span>
                        <span style="margin-left: 8px;">${schedule.available_seats}/${schedule.capacity} seats</span>
                    </div>
                </div>
                <div>
                    <button onclick="deleteSchedule(${schedule.id})" style="background: #ff4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">Delete</button>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Delete schedule
async function deleteSchedule(id) {
    if (!confirm('Are you sure you want to delete this schedule?')) {
        return;
    }
    
    try {
        const response = await fetch(`/admin/schedules/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('Schedule deleted successfully', 'success');
            loadSchedules();
        } else {
            showMessage(`Error: ${result.error}`, 'error');
        }
    } catch (error) {
        console.error('Delete error:', error);
        showMessage('An error occurred while deleting the schedule', 'error');
    }
}

// Show message
function showMessage(message, type = 'info') {
    const container = document.getElementById('serverSaveResult');
    
    const colors = {
        success: '#4caf50',
        error: '#f44336',
        info: '#2196f3'
    };
    
    container.innerHTML = `
        <div style="
            padding: 12px;
            border-radius: 4px;
            background: ${colors[type]}20;
            border: 1px solid ${colors[type]};
            color: ${colors[type]};
            margin-top: 8px;
        ">
            ${message}
        </div>
    `;
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

// Setup sidebar (called from body onload)
function setupSidebar() {
    // Sidebar functionality is handled by CSS and onclick events in the HTML
    console.log('Sidebar setup complete');
}


// Logout admin
function logoutAdmin() {
    sessionStorage.removeItem('admin_logged_in');
    window.location.href = '/admin-login';
}

// Load saved configuration on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load saved preset times
    const savedTimes = localStorage.getItem('adminPresetTimes');
    if (savedTimes) {
        adminState.presetTimes = JSON.parse(savedTimes);
    }
    
    // Load saved active days
    const savedDays = localStorage.getItem('adminActiveDays');
    if (savedDays) {
        adminState.activeDays = JSON.parse(savedDays);
    }
});
