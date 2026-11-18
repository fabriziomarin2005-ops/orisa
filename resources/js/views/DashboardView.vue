<template>
    <v-container fluid class="pa-6">
        <!-- Barra Superior de Estado en Tiempo Real y Exportación -->
        <div class="d-flex flex-wrap justify-space-between align-center mb-4 ga-3">
            <div>
                <h1 class="text-h5 font-weight-bold font-mono">
                    CENTRO DE MONITOREO & AUDITORÍA HSE
                </h1>
                <div class="text-caption text-medium-emphasis font-mono">
                    RECEPCIÓN AUTOMÁTICA DE REPORTES GEMINI VISION + ESP32-CAM
                </div>
            </div>

            <div class="d-flex align-center ga-3">
                <v-switch
                    v-model="modoEnVivo"
                    hide-details
                    density="compact"
                    color="primary"
                    inset
                    class="font-mono mr-2"
                >
                    <template #label>
                        <span class="text-caption font-mono font-weight-bold">
                            {{ modoEnVivo ? 'MODO EN VIVO (5s)' : 'MONITOREO EN PAUSA' }}
                        </span>
                    </template>
                </v-switch>

                <v-btn
                    variant="outlined"
                    prepend-icon="mdi-file-delimited-outline"
                    :href="urlExportarCsv"
                    target="_blank"
                    class="font-mono"
                >
                    Exportar Bitácora (CSV)
                </v-btn>
            </div>
        </div>

        <!-- Tarjetas de KPIs -->
        <v-row class="mb-4" dense>
            <v-col cols="12" sm="6" md="3">
                <v-card variant="outlined" class="pa-4 border-industrial">
                    <div class="text-caption font-mono text-medium-emphasis">INCIDENTES TOTALES</div>
                    <div class="text-h4 font-weight-bold font-mono mt-1">
                        {{ metricas.total_incidentes }}
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" md="3">
                <v-card variant="outlined" class="pa-4 border-industrial">
                    <div class="text-caption font-mono text-medium-emphasis">CRÍTICOS / ALTOS SIN ATENDER</div>
                    <div class="text-h4 font-weight-bold font-mono mt-1">
                        {{ metricas.criticos_pendientes }}
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" md="3">
                <v-card variant="outlined" class="pa-4 border-industrial">
                    <div class="text-caption font-mono text-medium-emphasis">PENDIENTES DE REVISIÓN</div>
                    <div class="text-h4 font-weight-bold font-mono mt-1">
                        {{ metricas.pendientes }}
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" md="3">
                <v-card variant="outlined" class="pa-4 border-industrial">
                    <div class="text-caption font-mono text-medium-emphasis">ZONA DE MAYOR INCIDENCIA</div>
                    <div class="text-subtitle-1 font-weight-bold font-mono mt-2 text-truncate">
                        {{ metricas.zona_mas_afectada }}
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- Módulo de Gráficas Analíticas HSE -->
        <AnaliticaHseCharts
            :por-zona="metricas.por_zona"
            :por-riesgo="metricas.por_riesgo"
        />

        <!-- Barra de Filtros Operativos -->
        <v-card variant="outlined" class="pa-4 mb-4 border-industrial">
            <v-row dense align="center">
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="filtros.search"
                        label="Buscar por folio, título, encargado..."
                        prepend-inner-icon="mdi-magnify"
                        variant="outlined"
                        density="compact"
                        hide-details
                        clearable
                        @update:model-value="cargarReportes(false)"
                    ></v-text-field>
                </v-col>

                <v-col cols="12" sm="4" md="3">
                    <v-select
                        v-model="filtros.lugar"
                        :items="opcionesZonas"
                        label="Filtrar por Zona / Lugar"
                        variant="outlined"
                        density="compact"
                        hide-details
                        clearable
                        @update:model-value="cargarReportes(false)"
                    ></v-select>
                </v-col>

                <v-col cols="12" sm="4" md="2">
                    <v-select
                        v-model="filtros.nivel_riesgo"
                        :items="['bajo', 'medio', 'alto', 'critico']"
                        label="Nivel de Riesgo"
                        variant="outlined"
                        density="compact"
                        hide-details
                        clearable
                        @update:model-value="cargarReportes(false)"
                    ></v-select>
                </v-col>

                <v-col cols="12" sm="4" md="2">
                    <v-select
                        v-model="filtros.estado"
                        :items="[
                            { title: 'Pendiente', value: 'pendiente' },
                            { title: 'En Revisión', value: 'en_revision' },
                            { title: 'Atendido', value: 'atendido' },
                        ]"
                        label="Estado"
                        variant="outlined"
                        density="compact"
                        hide-details
                        clearable
                        @update:model-value="cargarReportes(false)"
                    ></v-select>
                </v-col>

                <v-col cols="12" md="1" class="text-right">
                    <v-btn
                        icon="mdi-refresh"
                        variant="outlined"
                        size="small"
                        title="Actualizar tabla"
                        @click="recargarTodo(false)"
                    ></v-btn>
                </v-col>
            </v-row>
        </v-card>

        <!-- Tabla Técnica de Incidentes -->
        <v-card variant="outlined" class="border-industrial">
            <v-data-table
                :headers="headers"
                :items="reportes"
                :loading="cargando"
                item-value="id"
                density="comfortable"
                class="font-mono"
            >
                <template #item.codigo_incidente="{ item }">
                    <span class="font-weight-bold">{{ item.codigo_incidente }}</span>
                </template>

                <template #item.nivel_riesgo="{ item }">
                    <v-chip
                        size="x-small"
                        :variant="item.nivel_riesgo === 'critico' || item.nivel_riesgo === 'alto' ? 'flat' : 'outlined'"
                        color="primary"
                        class="font-mono font-weight-bold text-uppercase"
                    >
                        {{ item.nivel_riesgo }}
                    </v-chip>
                </template>

                <template #item.estado="{ item }">
                    <v-chip size="x-small" variant="tonal" class="font-mono text-uppercase">
                        {{ item.estado.replace('_', ' ') }}
                    </v-chip>
                </template>

                <template #item.fecha="{ item }">
                    <span class="text-caption font-mono">{{ formatearFecha(item.fecha) }}</span>
                </template>

                <template #item.acciones="{ item }">
                    <v-btn
                        size="small"
                        variant="outlined"
                        prepend-icon="mdi-eye-outline"
                        @click="abrirDetalle(item)"
                    >
                        Ficha
                    </v-btn>
                </template>
            </v-data-table>
        </v-card>

        <!-- Modal de Ficha Técnica con Línea de Tiempo y PDF -->
        <DetalleIncidenteDialog
            v-model="dialogDetalle"
            :incidente="incidenteSeleccionado"
            @estado-actualizado="alActualizarIncidente"
        />

        <!-- Notificación Toast para Nuevos Incidentes en Tiempo Real -->
        <v-snackbar
            v-model="snackbarNuevo.visible"
            :timeout="6000"
            location="bottom right"
            color="primary"
        >
            <div class="d-flex align-center ga-2 font-mono text-caption">
                <v-icon icon="mdi-bell-ring-outline" size="18"></v-icon>
                <span>{{ snackbarNuevo.mensaje }}</span>
            </div>
            <template #actions>
                <v-btn variant="text" size="small" @click="snackbarNuevo.visible = false">
                    Cerrar
                </v-btn>
            </template>
        </v-snackbar>
    </v-container>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import AnaliticaHseCharts from '../components/AnaliticaHseCharts.vue';
import DetalleIncidenteDialog from '../components/DetalleIncidenteDialog.vue';

const reportes = ref([]);
const zonas = ref([]);
const cargando = ref(false);
const modoEnVivo = ref(true);
const ultimoIdConocido = ref(null);

const dialogDetalle = ref(false);
const incidenteSeleccionado = ref(null);

const snackbarNuevo = ref({
    visible: false,
    mensaje: '',
});

const metricas = ref({
    total_incidentes: 0,
    pendientes: 0,
    en_revision: 0,
    atendidos: 0,
    criticos_pendientes: 0,
    zona_mas_afectada: '-',
    por_riesgo: { bajo: 0, medio: 0, alto: 0, critico: 0 },
    por_zona: [],
});

const filtros = ref({
    search: '',
    lugar: null,
    nivel_riesgo: null,
    estado: null,
});

const headers = [
    { title: 'FOLIO', key: 'codigo_incidente', sortable: true },
    { title: 'INCIDENTE', key: 'titulo', sortable: true },
    { title: 'ZONA / LUGAR', key: 'lugar', sortable: true },
    { title: 'RIESGO', key: 'nivel_riesgo', sortable: true },
    { title: 'ENCARGADO', key: 'encargado', sortable: true },
    { title: 'FECHA CAPTURA', key: 'fecha', sortable: true },
    { title: 'ESTADO', key: 'estado', sortable: true },
    { title: 'ACCIÓN', key: 'acciones', sortable: false, align: 'end' },
];

const opcionesZonas = computed(() => zonas.value.map((z) => z.nombre));

const urlExportarCsv = computed(() => {
    const params = new URLSearchParams();
    if (filtros.value.search) params.append('search', filtros.value.search);
    if (filtros.value.lugar) params.append('lugar', filtros.value.lugar);
    if (filtros.value.nivel_riesgo) params.append('nivel_riesgo', filtros.value.nivel_riesgo);
    if (filtros.value.estado) params.append('estado', filtros.value.estado);
    const qs = params.toString();
    return qs ? `/api/reportes/exportar/csv?${qs}` : '/api/reportes/exportar/csv';
});

function formatearFecha(fechaIso) {
    if (!fechaIso) return '-';
    return fechaIso.replace('T', ' ').substring(0, 19);
}

async function cargarMetricas() {
    try {
        const { data } = await axios.get('/api/metricas');
        metricas.value = data;
    } catch (e) {
        console.error('Error al cargar métricas:', e);
    }
}

async function cargarZonas() {
    try {
        const { data } = await axios.get('/api/zonas');
        zonas.value = Array.isArray(data) ? data : data.data || [];
    } catch (e) {
        console.error('Error al cargar zonas:', e);
    }
}

async function cargarReportes(silencioso = false) {
    if (!silencioso) cargando.value = true;
    try {
        const { data } = await axios.get('/api/reportes', {
            params: {
                search: filtros.value.search || undefined,
                lugar: filtros.value.lugar || undefined,
                nivel_riesgo: filtros.value.nivel_riesgo || undefined,
                estado: filtros.value.estado || undefined,
                per_page: 25,
            },
        });

        const lista = data.data || [];

        if (lista.length > 0) {
            const idMasReciente = Math.max(...lista.map((r) => r.id));
            if (ultimoIdConocido.value !== null && idMasReciente > ultimoIdConocido.value) {
                const nuevo = lista.find((r) => r.id === idMasReciente) || lista[0];
                snackbarNuevo.value = {
                    visible: true,
                    mensaje: `Nuevo incidente registrado en ${nuevo.lugar}: ${nuevo.titulo}`,
                };
                await cargarMetricas();
            }
            if (ultimoIdConocido.value === null || idMasReciente > ultimoIdConocido.value) {
                ultimoIdConocido.value = idMasReciente;
            }
        }

        reportes.value = lista;
    } catch (e) {
        console.error('Error al cargar reportes:', e);
    } finally {
        if (!silencioso) cargando.value = false;
    }
}

async function recargarTodo(silencioso = false) {
    await Promise.all([cargarReportes(silencioso), cargarMetricas(), cargarZonas()]);
}

function abrirDetalle(item) {
    incidenteSeleccionado.value = item;
    dialogDetalle.value = true;
}

function alActualizarIncidente(incidenteActualizado) {
    incidenteSeleccionado.value = incidenteActualizado;
    recargarTodo(true);
}

let pollingTimer = null;

function manejarSimulacionExterna() {
    recargarTodo(true);
}

onMounted(() => {
    recargarTodo(false);
    window.addEventListener('orisa-iot-simulated', manejarSimulacionExterna);
    pollingTimer = setInterval(() => {
        if (modoEnVivo.value) {
            cargarReportes(true);
        }
    }, 5000);
});

onUnmounted(() => {
    window.removeEventListener('orisa-iot-simulated', manejarSimulacionExterna);
    if (pollingTimer) clearInterval(pollingTimer);
});
</script>
