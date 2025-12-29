// Funciones del Calendario de Lectura
let currentMonth = new Date().getMonth() + 1;
let currentYear = new Date().getFullYear();
let updateInterval;

// Cargar datos del calendario
function loadCalendarData(month = currentMonth, year = currentYear) {
    // Log removido
    
    // Obtener user_id de sessionStorage si está disponible (para pruebas)
    const basePath = (window.location.pathname || '').replace(/[^\/]+$/, '');
    let url = `${basePath}ajax_calendar_data.php?month=${month}&year=${year}&t=${Date.now()}`;
    if (typeof sessionStorage !== 'undefined' && sessionStorage.getItem('user_id')) {
        url += `&user_id=${sessionStorage.getItem('user_id')}`;
    }
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);
    fetch(url, { credentials: 'same-origin', cache: 'no-store', signal: controller.signal })
        .then(response => response.ok ? response.json() : Promise.reject(new Error('HTTP ' + response.status)))
        .then(data => {
            // Logs removidos
            if (data.success) {
                // Logs removidos
                updateCalendarDisplay(data);
            }
        })
        .catch(error => {
            // Error silencioso
        })
        .finally(() => clearTimeout(timeoutId));
}

// Actualizar la visualización del calendario
function updateCalendarDisplay(data) {
    // Actualizar título del mes
    const monthElement = document.querySelector('.current-month');
    if (monthElement) {
        monthElement.textContent = data.month_name;
    }
    
    // Actualizar estadísticas de lectura (solo en la pestaña de progreso)
    // Solo actualizar si estamos en la pestaña de progreso y hay contadores específicos de tiempo
    const readingTimeStats = document.querySelectorAll('.reading-time-stats .stat-number');
    if (readingTimeStats.length >= 2) {
        readingTimeStats[0].textContent = data.stats.total_time;
        readingTimeStats[1].textContent = data.stats.average_time;
    }
    
    // Actualizar días del calendario
    updateCalendarDays(data.calendar_data);
}

// Actualizar los días del calendario
function updateCalendarDays(calendarData) {
    const calendarGrid = document.querySelector('.calendar-grid');
    if (!calendarGrid) return;
    
    const dayHeaders = calendarGrid.querySelectorAll('.day-header');
    
    // Mantener los headers de días de la semana
    let newHTML = '';
    dayHeaders.forEach(header => {
        newHTML += header.outerHTML;
    });
    
    // Crear un calendario real con los días correctos
    const firstDayOfMonth = new Date(currentYear, currentMonth - 1, 1);
    const lastDayOfMonth = new Date(currentYear, currentMonth, 0);
    let firstDayWeekday = firstDayOfMonth.getDay(); // 0 = Domingo, 1 = Lunes, etc.
    
    // Mantener domingo como primer día (0) para que coincida con los headers
    // Los headers empiezan con DOM, así que mantenemos: 0=Domingo, 1=Lunes, 2=Martes...
    // No necesitamos ajustar firstDayWeekday
    
    // Debug logs removidos
    
    const daysInMonth = lastDayOfMonth.getDate();
    
    // Crear un mapa de datos por fecha para acceso rápido
    const dataMap = {};
    calendarData.forEach(day => {
        dataMap[day.date] = day;
    });
    
    // Agregar espacios vacíos para los días antes del primer día del mes
    // Log removido
    for (let i = 0; i < firstDayWeekday; i++) {
        newHTML += '<div class="calendar-day empty">-</div>';
    }
    
    // Agregar los días del mes en las posiciones correctas
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
        
        // Debug logs removidos
        
        newHTML += `
            <div class="calendar-day ${isToday ? 'today' : ''} ${hasActivity ? 'has-activity' : ''}" 
                 data-date="${dayData.date}" 
                 data-tooltip="${hasActivity ? 'Tiempo de lectura: ' + dayData.formatted_time : 'Sin actividad'}">
                <div class="day-number">${day}</div>
                <div class="reading-time ${timeClass}">${dayData.formatted_time}</div>
            </div>
        `;
    }
    
    calendarGrid.innerHTML = newHTML;
}

// Verificar si es el día actual
function isCurrentDay(dateString) {
    const today = new Date().toISOString().split('T')[0];
    return dateString === today;
}

// Navegación del calendario
function previousMonth() {
    currentMonth--;
    if (currentMonth < 1) {
        currentMonth = 12;
        currentYear--;
    }
    // Log removido
    loadCalendarData(currentMonth, currentYear);
}

function nextMonth() {
    currentMonth++;
    if (currentMonth > 12) {
        currentMonth = 1;
        currentYear++;
    }
    // Log removido
    loadCalendarData(currentMonth, currentYear);
}

// Actualizar calendario en tiempo real
function startRealTimeUpdates() {
    // Actualizar cada 30 segundos
    updateInterval = setInterval(() => {
        loadCalendarData(currentMonth, currentYear);
    }, 30000);
}

function stopRealTimeUpdates() {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
}

// Función para actualizar inmediatamente (llamada desde el sistema de lectura)
function updateCalendarNow() {
    // Log removido
    loadCalendarData(currentMonth, currentYear);
}

// Inicializar calendario
function initializeCalendar() {
    loadCalendarData();
    startRealTimeUpdates();
}

// Exponer funciones globalmente
window.updateCalendarNow = updateCalendarNow;
window.previousMonth = previousMonth;
window.nextMonth = nextMonth;
window.initializeCalendar = initializeCalendar;

// Logs removidos - sistema de logs eliminado 