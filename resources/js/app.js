import './bootstrap';

// Bootstrap JS is loaded as a synchronous CDN <script> in the layout
// so window.bootstrap is available to all @push('scripts') blocks.
// Do NOT import it here — Vite generates type="module" which is deferred
// and would cause window.bootstrap to be undefined when page scripts run.

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
