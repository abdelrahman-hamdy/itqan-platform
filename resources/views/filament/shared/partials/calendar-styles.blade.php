{{--
    Shared Calendar Styles

    Common CSS for calendar widgets used in:
    - Teacher Calendar (UnifiedTeacherCalendar)
    - Supervisor Calendar (SupervisorCalendar)
--}}
<style>
    /* Calendar event styling */
    .fc-event {
        border-radius: 6px;
        border-width: 2px;
        font-size: 12px;
        padding: 2px 6px;
        cursor: pointer;
    }

    .fc-event:hover {
        opacity: 0.85;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .fc-daygrid-event {
        margin-bottom: 2px;
    }

    .fc-event-title {
        font-weight: 500;
    }

    /* Arabic RTL support */
    .fc-direction-rtl {
        direction: rtl;
    }

    /* Passed events styling */
    .event-passed {
        opacity: 0.6;
        text-decoration: line-through;
    }

    .event-passed .fc-event-title {
        text-decoration: line-through;
    }

    /* Ongoing events animation */
    .event-ongoing {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.7;
        }
    }

    /* Dark mode adjustments */
    .dark .fc-theme-standard td,
    .dark .fc-theme-standard th {
        border-color: rgb(55 65 81);
    }

    .dark .fc-day-today {
        background-color: rgba(59, 130, 246, 0.1) !important;
    }

    /* Item selection card styles */
    .item-card {
        transition: all 0.3s ease !important;
        position: relative;
    }

    .item-card .fi-card {
        transition: all 0.3s ease !important;
    }

    .item-selected .fi-card {
        border-width: 2px !important;
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.25) !important;
    }

    .item-card.item-selected .fi-section-content {
        border: solid 1px #60a5fa !important;
        border-radius: 10px;
        background-color: #3d485b24 !important;
    }

    /* Custom button styling */
    .fc-customButton-button {
        background-color: #6366f1;
        border-color: #6366f1;
        color: white;
    }

    .fc-customButton-button:hover {
        background-color: #4f46e5;
        border-color: #4f46e5;
    }
</style>
