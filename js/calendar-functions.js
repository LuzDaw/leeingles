/**
 * Funciones del Calendario de Actividad (Lectura + Práctica)
 */
let currentMonth = new Date().getMonth() + 1;
let currentYear = new Date().getFullYear();
let updateInterval;

/**
 * Carga los datos de actividad desde el servidor
 */
function loadCalendarData(month = currentMonth, year = currentYear) {
    const basePath = (window.location.pathname || '').replace(/[^\/]+$/, '');
    let url = `${basePath}ajax_calendar_data.php?month=${month}&year=${year}&t=${Date.now()}`;
    
    // Soporte para user_id en sesión de pruebas
    if (typeof sessionStorage !== 'undefined' && sessionStorage.getItem('user_id')) {
        url += `&user_id=${sessionStorage.getItem('user_id')}`;
    }
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);

    fetch(url, { credentials: 'same-origin', cache: 'no-store', signal: controller.signal })
        .then(response => response.ok ? response.json() : Promise.reject(new Error('HTTP ' + response.status)))
        .then(data => {
            if (data.success) {
                updateCalendarDisplay(data);
            }
        })
        .catch(() => {
            // Error silencioso para no interrumpir la experiencia del usuario
        })
        .finally(() => clearTimeout(timeoutId));
}

/**
 * Actualiza los elementos visuales del calendario
 */
function updateCalendarDisplay(data) {
    const monthElement = document.querySelector('.current-month');
    if (monthElement) {
        monthElement.textContent = data.month_name;
    }
    
    // Actualizar estadísticas globales si existen en el DOM
    const activityStats = document.querySelectorAll('.activity-time-stats .stat-number');
    if (activityStats.length >= 2) {
        activityStats[0].textContent = data.stats.total_time;
        activityStats[1].textContent = data.stats.average_time;
    }
    
    updateCalendarDays(data.calendar_data);
}

/**
 * Renderiza los días en la cuadrícula del calendario
 */
function updateCalendarDays(calendarData) {
    const calendarGrid = document.querySelector('.calendar-grid');
    if (!calendarGrid) return;
    
    const dayHeaders = calendarGrid.querySelectorAll('.day-header');
    let newHTML = '';
    dayHeaders.forEach(header => {
        newHTML += header.outerHTML;
    });
    
    const firstDayOfMonth = new Date(currentYear, currentMonth - 1, 1);
    const lastDayOfMonth = new Date(currentYear, currentMonth, 0);
    const firstDayWeekday = firstDayOfMonth.getDay(); // 0 = Domingo
    const daysInMonth = lastDayOfMonth.getDate();
    
    const dataMap = {};
    calendarData.forEach(day => {
        dataMap[day.date] = day;
    });
    
    // Espacios vacíos para el inicio del mes
    for (let i = 0; i < firstDayWeekday; i++) {
        newHTML += '<div class="calendar-day empty">-</div>';
    }
    
    // Renderizado de cada día
    for (let day = 1; day <= daysInMonth; day++) {
        const dateString = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayData = dataMap[dateString] || {
            date: dateString,
            day: day,
            seconds: 0,
            formatted_time: '0 min',
            has_activity: false
        };
        
        const isToday = isCurrentDay(dayData.date);
        const hasActivity = dayData.has_activity;
        const timeClass = dayData.seconds === 0 ? 'zero' : '';
        
        // Construcción del tooltip con desglose de actividad
        let tooltipText = 'Sin actividad';
        if (hasActivity) {
            tooltipText = `Total: ${dayData.formatted_time}`;
            if (dayData.reading_seconds > 0 || dayData.practice_seconds > 0) {
                tooltipText += ` (Lectura: ${dayData.formatted_reading}, Práctica: ${dayData.formatted_practice})`;
            }
        }
        
        newHTML += `
            <div class="calendar-day ${isToday ? 'today' : ''} ${hasActivity ? 'has-activity' : ''}" 
                 data-date="${dayData.date}" 
                 data-tooltip="${tooltipText}">
                <div class="day-number">${day}</div>
                <div class="activity-time ${timeClass}">${dayData.formatted_time}</div>
            </div>
        `;
    }
    
    calendarGrid.innerHTML = newHTML;
}

function isCurrentDay(dateString) {
    const today = new Date().toISOString().split('T')[0];
    return dateString === today;
}

function previousMonth() {
    currentMonth--;
    if (currentMonth < 1) {
        currentMonth = 12;
        currentYear--;
    }
    loadCalendarData(currentMonth, currentYear);
}

function nextMonth() {
    currentMonth++;
    if (currentMonth > 12) {
        currentMonth = 1;
        currentYear++;
    }
    loadCalendarData(currentMonth, currentYear);
}

function startRealTimeUpdates() {
    updateInterval = setInterval(() => {
        loadCalendarData(currentMonth, currentYear);
    }, 10000);
}

function stopRealTimeUpdates() {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
}

function updateCalendarNow() {
    loadCalendarData(currentMonth, currentYear);
}

function initializeCalendar() {
    loadCalendarData();
    startRealTimeUpdates();
}

// Exposición global de funciones necesarias
window.updateCalendarNow = updateCalendarNow;
window.previousMonth = previousMonth;
window.nextMonth = nextMonth;
window.initializeCalendar = initializeCalendar;
