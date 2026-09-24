import './bootstrap';
import '../css/nucleo-icons.css';

// Bootstrap 5. jQuery is loaded before this module by the layout, so Bootstrap
// also exposes its jQuery plugins ($('#modal').modal('show')) used by older views.
import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

// Mobile: open/close the Argon sidenav.
document.addEventListener('DOMContentLoaded', () => {
    const toggler = document.getElementById('iconNavbarSidenav');
    const close = document.getElementById('iconSidenav');
    const body = document.body;

    const toggle = (event) => {
        event.preventDefault();
        body.classList.toggle('g-sidenav-pinned');
        body.classList.toggle('g-sidenav-hidden');
    };

    toggler?.addEventListener('click', toggle);
    close?.addEventListener('click', toggle);
});
