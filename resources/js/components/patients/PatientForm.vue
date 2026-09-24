<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import ArgonAlert from '../argon/ArgonAlert.vue';
import ArgonButton from '../argon/ArgonButton.vue';
import FormInput from '../form/FormInput.vue';
import FormSelect from '../form/FormSelect.vue';
import FormTextarea from '../form/FormTextarea.vue';
import { format as formatRut, isValid as isValidRut } from '../../utils/rut';

const props = defineProps({
    // create | edit
    mode: { type: String, required: true },
    action: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    genders: { type: Object, required: true },
    // PatientResource (edit mode)
    patient: { type: Object, default: null },
});

const isEdit = props.mode === 'edit';
const localDate = (date) => new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
const today = localDate(new Date());
const yesterday = localDate(new Date(Date.now() - 86400000));

const form = reactive({
    first_name: props.patient?.first_name ?? '',
    last_name: props.patient?.last_name ?? '',
    rut: props.patient?.rut ? formatRut(props.patient.rut) : '',
    email: props.patient?.email ?? '',
    address: props.patient?.address ?? '',
    birthdate: props.patient?.birthdate ?? '',
    gender: props.patient?.gender ?? '',
    comment: props.patient?.comment ?? '',
});

const initial = JSON.stringify(form);
const errors = ref({});
const touched = reactive({});
const saving = ref(false);
const generalError = ref('');

const isDirty = computed(() => JSON.stringify(form) !== initial);

const age = computed(() => {
    if (!form.birthdate) {
        return null;
    }
    const birth = new Date(`${form.birthdate}T00:00:00`);
    const now = new Date();
    let years = now.getFullYear() - birth.getFullYear();
    if (now < new Date(now.getFullYear(), birth.getMonth(), birth.getDate())) {
        years--;
    }
    return years >= 0 ? years : null;
});

// Client-side checks mirror PatientRequest for instant feedback; the server still validates.
const rules = {
    first_name: (v) => (!v.trim() ? 'El nombre es obligatorio.' : v.trim().length < 3 ? 'Debe tener al menos 3 caracteres.' : ''),
    last_name: (v) => (!v.trim() ? 'El apellido es obligatorio.' : v.trim().length < 3 ? 'Debe tener al menos 3 caracteres.' : ''),
    rut: (v) => (isEdit ? '' : !v.trim() ? 'El RUT es obligatorio.' : !isValidRut(v) ? 'El RUT no es válido (revise el dígito verificador).' : ''),
    email: (v) => (v.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()) ? 'Ingrese un correo válido.' : ''),
    address: (v) => (!v.trim() ? 'La dirección es obligatoria.' : v.trim().length < 3 ? 'Debe tener al menos 3 caracteres.' : ''),
    birthdate: (v) => (!v ? 'La fecha de nacimiento es obligatoria.' : v >= today ? 'Debe ser anterior a hoy.' : ''),
    gender: (v) => (!v ? 'Seleccione el sexo.' : ''),
    comment: () => '',
};

function validate(field) {
    const message = rules[field](form[field] ?? '');
    errors.value = { ...errors.value, [field]: message };
    return !message;
}

function blur(field) {
    touched[field] = true;
    if (field === 'rut' && form.rut) {
        form.rut = formatRut(form.rut);
    }
    validate(field);
}

function error(field) {
    return touched[field] ? errors.value[field] ?? '' : '';
}

async function submit() {
    Object.keys(rules).forEach((field) => { touched[field] = true; });
    const valid = Object.keys(rules).map(validate).every(Boolean);
    generalError.value = '';

    if (!valid) {
        document.querySelector('.is-invalid')?.focus();
        return;
    }

    saving.value = true;
    const payload = { ...form };
    if (isEdit) {
        delete payload.rut; // the RUT cannot be changed
    }

    try {
        const response = await window.axios({
            method: isEdit ? 'put' : 'post',
            url: props.action,
            data: payload,
            headers: { Accept: 'application/json' },
        });
        window.removeEventListener('beforeunload', warnUnsaved);
        window.location.href = response.data.meta.redirect;
    } catch (e) {
        saving.value = false;
        if (e.response?.status === 422) {
            errors.value = Object.fromEntries(
                Object.entries(e.response.data.errors).map(([field, messages]) => [field, messages[0]]),
            );
            generalError.value = 'Revise los campos marcados.';
        } else if (e.response?.status === 403) {
            generalError.value = 'No tiene permiso para realizar esta acción.';
        } else if (e.response?.status === 419) {
            generalError.value = 'La sesión expiró. Recargue la página e intente de nuevo.';
        } else {
            generalError.value = 'No se pudo guardar. Intente nuevamente.';
        }
    }
}

function warnUnsaved(event) {
    if (isDirty.value && !saving.value) {
        event.preventDefault();
        event.returnValue = '';
    }
}

onMounted(() => window.addEventListener('beforeunload', warnUnsaved));
onBeforeUnmount(() => window.removeEventListener('beforeunload', warnUnsaved));
</script>

<template>
    <form novalidate autocomplete="off" @submit.prevent="submit">
        <ArgonAlert v-if="generalError" color="danger" icon="ni ni-support-16">
            {{ generalError }}
        </ArgonAlert>

        <p class="text-uppercase text-sm text-secondary font-weight-bold">Datos personales</p>
        <div class="row">
            <div class="col-md-6">
                <FormInput id="first_name" v-model="form.first_name" label="Nombre" required maxlength="100"
                    autocomplete="given-name" :error="error('first_name')" @blur="blur('first_name')" />
            </div>
            <div class="col-md-6">
                <FormInput id="last_name" v-model="form.last_name" label="Apellido" required maxlength="100"
                    autocomplete="family-name" :error="error('last_name')" @blur="blur('last_name')" />
            </div>
            <div class="col-md-4">
                <FormInput id="rut" v-model="form.rut" label="RUT" :required="!isEdit" :readonly="isEdit"
                    maxlength="12" placeholder="12.345.678-5"
                    :hint="isEdit ? 'El RUT no se puede modificar.' : 'Con dígito verificador.'"
                    :error="error('rut')" @blur="blur('rut')" />
            </div>
            <div class="col-md-4">
                <FormInput id="birthdate" v-model="form.birthdate" type="date" label="Fecha de nacimiento" required
                    :max="yesterday" :hint="age !== null ? `${age} años` : ''"
                    :error="error('birthdate')" @blur="blur('birthdate')" />
            </div>
            <div class="col-md-4">
                <FormSelect id="gender" v-model="form.gender" label="Sexo" :options="genders" required
                    :error="error('gender')" @blur="blur('gender')" @change="blur('gender')" />
            </div>
        </div>

        <hr class="horizontal dark">
        <p class="text-uppercase text-sm text-secondary font-weight-bold">Contacto</p>
        <div class="row">
            <div class="col-md-6">
                <FormInput id="email" v-model="form.email" type="email" label="Correo" maxlength="191"
                    placeholder="paciente@correo.cl" autocomplete="email" hint="Opcional."
                    :error="error('email')" @blur="blur('email')" />
            </div>
            <div class="col-md-6">
                <FormInput id="address" v-model="form.address" label="Dirección" required maxlength="255"
                    autocomplete="street-address" :error="error('address')" @blur="blur('address')" />
            </div>
            <div class="col-12">
                <FormTextarea id="comment" v-model="form.comment" label="Comentario (opcional)" :rows="2"
                    :error="error('comment')" />
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">
            <a :href="cancelUrl" class="btn btn-outline-secondary mb-0">Cancelar</a>
            <ArgonButton type="submit" color="primary" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                {{ saving ? 'Guardando…' : isEdit ? 'Guardar cambios' : 'Registrar paciente' }}
            </ArgonButton>
        </div>
    </form>
</template>
