<template>
    <v-container class="fill-height d-flex align-center justify-center">
        <v-card max-width="440" width="100%" variant="outlined" class="pa-6 border-industrial">
            <div class="text-center mb-6">
                <v-img
                    src="/images/logo-orisa.jpg"
                    max-height="96"
                    contain
                    class="mx-auto mb-3"
                ></v-img>
                <div class="text-h5 font-weight-bold font-mono tracking-wide">
                    ORISA PLATFORM
                </div>
                <div class="text-caption font-mono text-medium-emphasis">
                    INTELLIGENT HSE INSPECTOR — ACCESO SUPERVISOR
                </div>
            </div>

            <v-alert
                v-if="errorMsg"
                type="error"
                variant="tonal"
                density="compact"
                class="mb-4 text-caption font-mono"
            >
                {{ errorMsg }}
            </v-alert>

            <v-form @submit.prevent="iniciarSesion">
                <v-text-field
                    v-model="email"
                    label="Correo Corporativo HSE"
                    prepend-inner-icon="mdi-shield-account-outline"
                    variant="outlined"
                    density="comfortable"
                    type="email"
                    required
                    class="mb-3"
                ></v-text-field>

                <v-text-field
                    v-model="password"
                    label="Código de Acceso"
                    prepend-inner-icon="mdi-lock-outline"
                    :append-inner-icon="mostrarPass ? 'mdi-eye-off' : 'mdi-eye'"
                    :type="mostrarPass ? 'text' : 'password'"
                    variant="outlined"
                    density="comfortable"
                    required
                    class="mb-5"
                    @click:append-inner="mostrarPass = !mostrarPass"
                ></v-text-field>

                <v-btn
                    type="submit"
                    color="primary"
                    variant="flat"
                    block
                    size="large"
                    :loading="cargando"
                    prepend-icon="mdi-login-variant"
                    class="font-mono"
                >
                    AUTENTICAR TERMINAL
                </v-btn>
            </v-form>

            <v-divider class="my-5"></v-divider>

            <div class="d-flex justify-space-between align-center text-caption font-mono text-medium-emphasis">
                <span>CREDENCIAL DEMO:</span>
                <span>admin@orisa.local / orisa2026</span>
            </div>
        </v-card>
    </v-container>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const email = ref('admin@orisa.local');
const password = ref('orisa2026');
const mostrarPass = ref(false);
const cargando = ref(false);
const errorMsg = ref('');

async function iniciarSesion() {
    cargando.value = true;
    errorMsg.value = '';
    try {
        const { data } = await axios.post('/api/auth/login', {
            email: email.value,
            password: password.value,
        });
        localStorage.setItem('orisa_auth_token', data.token);
        localStorage.setItem('orisa_auth_user', JSON.stringify(data.user));
        window.dispatchEvent(new Event('orisa-auth-changed'));
        router.push({ name: 'dashboard' });
    } catch (err) {
        errorMsg.value =
            err.response?.data?.errors?.email?.[0] ||
            err.response?.data?.message ||
            'Error al verificar credenciales en el servidor.';
    } finally {
        cargando.value = false;
    }
}
</script>
