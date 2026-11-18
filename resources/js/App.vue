<template>
    <v-app>
        <v-app-bar flat class="border-b px-4">
            <div class="d-flex align-center ga-3">
                <v-avatar size="36" rounded="0">
                    <v-img src="/images/logo-orisa.jpg" alt="ORISA"></v-img>
                </v-avatar>
                <div>
                    <div class="text-subtitle-1 font-weight-bold font-mono leading-tight">
                        ORISA
                    </div>
                    <div class="text-caption font-mono text-medium-emphasis d-none d-sm-block" style="font-size: 10px !important;">
                        INTELLIGENT HSE INSPECTOR
                    </div>
                </div>
            </div>

            <!-- Navegación principal -->
            <div v-if="autenticado" class="ml-6 d-none d-md-flex ga-1">
                <v-btn
                    :to="{ name: 'dashboard' }"
                    variant="text"
                    size="small"
                    prepend-icon="mdi-view-dashboard-outline"
                    class="font-mono"
                >
                    BITÁCORA HSE
                </v-btn>
                <v-btn
                    :to="{ name: 'zonas' }"
                    variant="text"
                    size="small"
                    prepend-icon="mdi-map-marker-radius-outline"
                    class="font-mono"
                >
                    ZONAS DE PLANTA
                </v-btn>
            </div>

            <v-spacer></v-spacer>

            <!-- Badge de Telemetría del Nodo ESP32-S3 -->
            <v-chip
                v-if="autenticado"
                size="small"
                variant="outlined"
                class="font-mono mr-3 d-none d-lg-inline-flex"
            >
                <v-icon
                    icon="mdi-circle"
                    size="10"
                    class="mr-2"
                    :class="iotStatus.online ? 'text-white' : 'text-disabled'"
                ></v-icon>
                {{ iotStatus.texto_telemetria }}
            </v-chip>

            <!-- Botón Simulador de Paquete IoT (Modo Demo / Emergencia) -->
            <v-btn
                v-if="autenticado"
                variant="outlined"
                size="small"
                prepend-icon="mdi-access-point-network"
                :loading="simulando"
                class="font-mono mr-2"
                @click="dialogSimular = true"
            >
                Simular Paquete IoT
            </v-btn>

            <!-- Alternar Tema Oscuro / Claro -->
            <v-btn
                :icon="isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'"
                variant="text"
                size="small"
                title="Alternar Modo Oscuro / Claro"
                @click="toggleTheme"
            ></v-btn>

            <!-- Cierre de sesión -->
            <v-btn
                v-if="autenticado"
                icon="mdi-logout-variant"
                variant="text"
                size="small"
                title="Cerrar Sesión"
                @click="cerrarSesion"
            ></v-btn>
        </v-app-bar>

        <v-main>
            <router-view />
        </v-main>

        <!-- Diálogo de Confirmación para Simular Incidente IoT -->
        <v-dialog v-model="dialogSimular" max-width="460">
            <v-card class="border-industrial pa-2">
                <v-card-title class="text-subtitle-1 font-weight-bold font-mono">
                    <v-icon icon="mdi-chip" class="mr-2"></v-icon>
                    SIMULADOR DE NODO ESP32-S3
                </v-card-title>
                <v-card-text class="text-body-2">
                    Esta acción inyectará un paquete de incidente industrial realista directamente en la base de datos y actualizará el latido de telemetría para demostrar la recepción en vivo.
                </v-card-text>
                <v-card-actions class="justify-end">
                    <v-btn variant="text" @click="dialogSimular = false">Cancelar</v-btn>
                    <v-btn
                        color="primary"
                        variant="flat"
                        :loading="simulando"
                        prepend-icon="mdi-send-check-outline"
                        @click="ejecutarSimulacion"
                    >
                        Disparar Paquete
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useTheme } from 'vuetify';
import { useRouter } from 'vue-router';
import axios from 'axios';

const theme = useTheme();
const router = useRouter();

const autenticado = ref(!!localStorage.getItem('orisa_auth_token'));
const simulando = ref(false);
const dialogSimular = ref(false);
const iotStatus = ref({
    online: false,
    texto_telemetria: 'NODO ESP32-S3: VERIFICANDO ENLACE...',
});

const isDark = computed(() => theme.global.current.value.dark);

function toggleTheme() {
    const nextTheme = isDark.value ? 'light' : 'dark';
    theme.global.name.value = nextTheme;
    localStorage.setItem('orisa_theme', nextTheme);
}

async function consultarTelemetria() {
    if (!autenticado.value) return;
    try {
        const { data } = await axios.get('/api/iot/status');
        iotStatus.value = data;
    } catch (e) {
        // Silencioso en caso de desconexión temporal
    }
}

async function ejecutarSimulacion() {
    simulando.value = true;
    try {
        await axios.post('/api/iot/simular');
        dialogSimular.value = false;
        await consultarTelemetria();
        window.dispatchEvent(new CustomEvent('orisa-iot-simulated'));
    } catch (error) {
        console.error('Error al simular paquete IoT:', error);
    } finally {
        simulando.value = false;
    }
}

async function cerrarSesion() {
    try {
        await axios.post('/api/auth/logout');
    } catch (e) {
        // Ignorar error si el token ya expiró
    } finally {
        localStorage.removeItem('orisa_auth_token');
        localStorage.removeItem('orisa_auth_user');
        autenticado.value = false;
        router.push({ name: 'login' });
    }
}

function sincronizarEstadoAuth() {
    autenticado.value = !!localStorage.getItem('orisa_auth_token');
    if (autenticado.value) {
        consultarTelemetria();
    }
}

let timerTelemetria = null;

onMounted(() => {
    const savedTheme = localStorage.getItem('orisa_theme');
    if (savedTheme) {
        theme.global.name.value = savedTheme;
    }
    window.addEventListener('orisa-auth-changed', sincronizarEstadoAuth);
    consultarTelemetria();
    timerTelemetria = setInterval(consultarTelemetria, 10000);
});

onUnmounted(() => {
    window.removeEventListener('orisa-auth-changed', sincronizarEstadoAuth);
    if (timerTelemetria) clearInterval(timerTelemetria);
});
</script>
