import { createApp } from 'vue';
import LabResultForm from './components/lab-results/LabResultForm.vue';

// Mounted by resources/views/lab_results/form.blade.php
const el = document.getElementById('lab-result-form');

if (el) {
    createApp(LabResultForm, JSON.parse(el.dataset.props)).mount(el);
}
