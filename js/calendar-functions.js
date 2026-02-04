/**
 * Funciones del Calendario de Actividad (Lectura + Práctica)
 */
let currentMonth = new Date().getMonth() + 1;
let currentYear = new Date().getFullYear();
let updateInterval;

/**
 * Carga los datos de actividad (lectura y práctica) del usuario para un mes y año específicos desde el servidor.
 *
 * Realiza una petición AJAX a `ajax_calendar_data.php` para obtener la información.
 * Incluye un mecanismo de timeout para la petición.
 *
 * @param {number} [month=currentMonth] - El mes para el que se cargarán los datos (1-12).
 * @param {number} [year=currentYear] - El año para el que se cargarán los datos.
 */
function loadCalendarData(month = currentMonth, year = currentYear) {
    const basePath = (window.location.pathname || '').replace(/[^\/]+$/, '');
    let url = `${basePath}ajax/ajax_calendar_data.php?month=${month}&year=${year}&t=${Date.now()}`;
    
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
 * Actualiza la interfaz de usuario del calendario con los datos recibidos del servidor.
 *
 * Actualiza el nombre del mes y año, así como las estadísticas globales de actividad.
 * Luego, llama a `updateCalendarDays` para renderizar los días individuales.
 *
 * @param {object} data - Un objeto que contiene los datos del calendario y las estadísticas.
 * @param {string} data.month_name - El nombre del mes y año formateado.
 * @param {object} data.stats - Estadísticas de actividad del mes.
 * @param {Array<object>} data.calendar_data - Datos de actividad por día.
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
 * Renderiza los días individuales en la cuadrícula del calendario.
 *
 * Calcula los días vacíos al inicio del mes y luego itera sobre cada día
 * para mostrar su número, tiempo de actividad y un tooltip detallado.
 *
 * @param {Array<object>} calendarData - Un array de objetos, donde cada objeto representa un día
 *                                        y contiene su fecha, segundos de actividad, etc.
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

/**
 * Comprueba si una fecha dada es el día actual.
 *
 * @param {string} dateString - La fecha en formato 'YYYY-MM-DD'.
 * @returns {boolean} `true` si la fecha es hoy, `false` en caso contrario.
 */
function isCurrentDay(dateString) {
    const today = new Date().toISOString().split('T')[0];
    return dateString === today;
}

/**
 * Cambia el calendario al mes anterior y recarga los datos.
 */
function previousMonth() {
    currentMonth--;
    if (currentMonth < 1) {
        currentMonth = 12;
        currentYear--;
    }
    loadCalendarData(currentMonth, currentYear);
}

/**
 * Cambia el calendario al mes siguiente y recarga los datos.
 */
function nextMonth() {
    currentMonth++;
    if (currentMonth > 12) {
        currentMonth = 1;
        currentYear++;
    }
    loadCalendarData(currentMonth, currentYear);
}

/**
 * Inicia la actualización en tiempo real de los datos del calendario.
 *
 * Configura un intervalo para recargar los datos del calendario cada 10 segundos.
 */
function startRealTimeUpdates() {
    updateInterval = setInterval(() => {
        loadCalendarData(currentMonth, currentYear);
    }, 10000);
}

/**
 * Detiene la actualización en tiempo real de los datos del calendario.
 *
 * Limpia el intervalo establecido por `startRealTimeUpdates`.
 */
function stopRealTimeUpdates() {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
}

/**
 * Fuerza una actualización inmediata de los datos del calendario.
 */
function updateCalendarNow() {
    loadCalendarData(currentMonth, currentYear);
}

/**
 * Inicializa el calendario al cargar los datos del mes actual y comenzar las actualizaciones en tiempo real.
 */
function initializeCalendar() {
    loadCalendarData();
    startRealTimeUpdates();
}

// Exposición global de funciones necesarias
window.updateCalendarNow = updateCalendarNow;
window.previousMonth = previousMonth;
window.nextMonth = nextMonth;
window.initializeCalendar = initializeCalendar;
