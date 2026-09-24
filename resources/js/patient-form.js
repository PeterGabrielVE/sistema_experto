import { createApp } from 'vue';
import PatientForm from './components/patients/PatientForm.vue';

// Mounted by resources/views/patients/{create,edit}.blade.php
const el = document.getElementById('patient-form');

if (el) {
    createApp(PatientForm, JSON.parse(el.dataset.props)).mount(el);
}
