/**
 * Chart.js Initialization
 *
 * This module imports Chart.js from npm and registers all chart components.
 * Chart is exposed globally as window.Chart for use in blade templates.
 */

import { Chart, registerables } from 'chart.js';

// Register all chart types and components
Chart.register(...registerables);

// Expose Chart globally for blade templates
window.Chart = Chart;

export default Chart;
