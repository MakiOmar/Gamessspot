import './bootstrap';

// Expose Bootstrap 5 globally for data-bs-toggle and `new bootstrap.Modal(...)`
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;
