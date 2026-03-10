{{-- Shared FullCalendar CSS overrides (RTL, theme, status colors, responsive) --}}
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<style>
    /* FullCalendar RTL & theme overrides */
    .fc {
        direction: rtl;
        font-family: 'Tajawal', sans-serif;
    }
    .fc .fc-toolbar-title {
        font-size: 1.25rem;
        font-weight: 700;
    }
    .fc .fc-button {
        background-color: #f3f4f6;
        border: 1px solid #e5e7eb;
        color: #374151;
        font-size: 0.875rem;
        font-weight: 500;
        padding: 0.375rem 0.75rem;
        border-radius: 0.5rem;
        transition: all 0.15s;
    }
    .fc .fc-button:hover {
        background-color: #e5e7eb;
        border-color: #d1d5db;
    }
    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }
    .fc .fc-today-button {
        background-color: #eff6ff;
        border-color: #bfdbfe;
        color: #2563eb;
    }
    .fc .fc-today-button:hover {
        background-color: #dbeafe;
    }
    .fc .fc-day-today {
        background-color: #eff6ff !important;
    }
    .fc .fc-daygrid-day-number {
        padding: 6px 8px;
        font-weight: 600;
        font-size: 0.875rem;
    }
    .fc .fc-col-header-cell-cushion {
        font-weight: 700;
        font-size: 0.8rem;
        color: #6b7280;
        padding: 8px 4px;
    }
    .fc .fc-event {
        border-radius: 0.375rem;
        padding: 2px 6px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        border: none;
        margin-bottom: 1px;
    }
    .fc .fc-event:hover {
        filter: brightness(0.9);
    }
    .fc .fc-daygrid-event-dot {
        display: none;
    }
    .fc .fc-event-time {
        font-weight: 600;
        font-size: 0.7rem;
    }
    .fc .fc-event-title {
        font-weight: 500;
    }
    /* Status colors */
    .fc-event-scheduled { background-color: #3b82f6 !important; color: #fff !important; }
    .fc-event-ready { background-color: #6366f1 !important; color: #fff !important; }
    .fc-event-ongoing { background-color: #f59e0b !important; color: #fff !important; }
    .fc-event-live { background-color: #f59e0b !important; color: #fff !important; }
    .fc-event-completed { background-color: #10b981 !important; color: #fff !important; }
    .fc-event-cancelled { background-color: #ef4444 !important; color: #fff !important; }
    .fc-event-absent { background-color: #f97316 !important; color: #fff !important; }
    /* Drag & drop visual feedback */
    .fc-event-dragging {
        opacity: 0.8;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .fc .fc-highlight {
        background-color: rgba(59, 130, 246, 0.1) !important;
    }
    /* Responsive — ensure calendar fills all available width */
    .fc {
        width: 100% !important;
        max-width: 100% !important;
    }
    .fc .fc-view-harness {
        width: 100% !important;
    }
    .fc table {
        width: 100% !important;
        table-layout: fixed !important;
    }
    .fc .fc-scrollgrid {
        width: 100% !important;
    }
    .fc .fc-daygrid-body {
        width: 100% !important;
    }
    .fc .fc-daygrid-body table {
        width: 100% !important;
    }
    .fc td, .fc th {
        max-width: none !important;
    }
    @media (max-width: 640px) {
        .fc .fc-toolbar {
            flex-direction: column;
            gap: 0.5rem;
        }
        .fc .fc-toolbar-chunk {
            display: flex;
            justify-content: center;
        }
    }
</style>
