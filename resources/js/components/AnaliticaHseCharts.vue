<template>
    <v-row class="mb-4" dense>
        <!-- Gráfica 1: Incidencias por Zona Industrial -->
        <v-col cols="12" md="7">
            <v-card variant="outlined" class="pa-4 border-industrial fill-height">
                <div class="d-flex justify-space-between align-center mb-3">
                    <span class="text-overline font-weight-bold font-mono">
                        <v-icon icon="mdi-factory" size="16" class="mr-1" color="#E86C1F"></v-icon>
                        INCIDENCIAS POR ZONA INDUSTRIAL
                    </span>
                    <v-chip size="x-small" variant="tonal" color="#E86C1F" class="font-mono font-weight-bold">
                        DISTRIBUCIÓN EN PLANTA
                    </v-chip>
                </div>
                <div style="height: 230px; position: relative;">
                    <Bar v-if="tieneDatosZona" :data="barData" :options="barOptions" />
                    <div
                        v-else
                        class="fill-height d-flex align-center justify-center text-caption font-mono text-medium-emphasis"
                    >
                        SIN DATOS DE ZONAS PARA GRAFICAR
                    </div>
                </div>
            </v-card>
        </v-col>

        <!-- Gráfica 2: Distribución por Nivel de Riesgo -->
        <v-col cols="12" md="5">
            <v-card variant="outlined" class="pa-4 border-industrial fill-height">
                <div class="d-flex justify-space-between align-center mb-3">
                    <span class="text-overline font-weight-bold font-mono">
                        <v-icon icon="mdi-chart-donut" size="16" class="mr-1" color="#E86C1F"></v-icon>
                        SEVERIDAD DE RIESGO HSE
                    </span>
                    <v-chip size="x-small" variant="tonal" color="#38BDF8" class="font-mono font-weight-bold">
                        PROPORCIÓN ACTUAL
                    </v-chip>
                </div>
                <div style="height: 230px; position: relative;">
                    <Doughnut v-if="tieneDatosRiesgo" :data="doughnutData" :options="doughnutOptions" />
                    <div
                        v-else
                        class="fill-height d-flex align-center justify-center text-caption font-mono text-medium-emphasis"
                    >
                        SIN REGISTROS DE CRITICIDAD
                    </div>
                </div>
            </v-card>
        </v-col>
    </v-row>
</template>

<script setup>
import { computed } from 'vue';
import { useTheme } from 'vuetify';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    ArcElement,
} from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const props = defineProps({
    porZona: {
        type: Array,
        default: () => [],
    },
    porRiesgo: {
        type: Object,
        default: () => ({ bajo: 0, medio: 0, alto: 0, critico: 0 }),
    },
});

const theme = useTheme();
const isDark = computed(() => theme.global.current.value.dark);

const tieneDatosZona = computed(() => props.porZona && props.porZona.length > 0);
const tieneDatosRiesgo = computed(() => {
    const r = props.porRiesgo || {};
    return (r.bajo || 0) + (r.medio || 0) + (r.alto || 0) + (r.critico || 0) > 0;
});

// Paleta vibrante inspirada en la identidad de ORISA (Naranja, Cian Eléctrico, Azul Marino, Esmeralda, Ámbar)
const paletaZonas = [
    '#E86C1F', // Naranja ORISA
    '#0EA5E9', // Azul Cian Industrial
    '#10B981', // Verde Esmeralda
    '#F59E0B', // Ámbar Seguridad
    '#6366F1', // Índigo Técnico
    '#EC4899', // Magenta Alerta
    '#14B8A6', // Turquesa
    '#8B5CF6', // Violeta
];

const barData = computed(() => ({
    labels: props.porZona.map((item) => item.lugar),
    datasets: [
        {
            label: 'Reportes Registrados',
            data: props.porZona.map((item) => item.total),
            backgroundColor: props.porZona.map((_, idx) => paletaZonas[idx % paletaZonas.length]),
            hoverBackgroundColor: props.porZona.map((_, idx) => paletaZonas[idx % paletaZonas.length]),
            borderRadius: 6,
            borderSkipped: false,
            barThickness: 32,
        },
    ],
}));

const barOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: isDark.value ? '#0F172A' : '#1E293B',
            titleFont: { family: 'monospace', size: 12 },
            bodyFont: { family: 'monospace', size: 12 },
            padding: 10,
            cornerRadius: 6,
        },
    },
    scales: {
        x: {
            ticks: {
                color: isDark.value ? '#E2E8F0' : '#334155',
                font: { family: 'monospace', size: 11, weight: 'bold' },
            },
            grid: {
                display: false,
            },
        },
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1,
                color: isDark.value ? '#94A3B8' : '#475569',
                font: { family: 'monospace', size: 11 },
            },
            grid: {
                color: isDark.value ? 'rgba(148, 163, 184, 0.12)' : 'rgba(15, 23, 42, 0.08)',
            },
        },
    },
}));

// Colores semánticos de seguridad industrial (HSE)
const doughnutData = computed(() => ({
    labels: ['BAJO', 'MEDIO', 'ALTO', 'CRÍTICO'],
    datasets: [
        {
            data: [
                props.porRiesgo?.bajo || 0,
                props.porRiesgo?.medio || 0,
                props.porRiesgo?.alto || 0,
                props.porRiesgo?.critico || 0,
            ],
            backgroundColor: [
                '#10B981', // Bajo -> Verde Esmeralda
                '#F59E0B', // Medio -> Ámbar / Amarillo Industrial
                '#E86C1F', // Alto -> Naranja ORISA
                '#EF4444', // Crítico -> Rojo Alerta
            ],
            hoverOffset: 8,
            borderColor: isDark.value ? '#121212' : '#FFFFFF',
            borderWidth: 3,
        },
    ],
}));

const doughnutOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '62%',
    plugins: {
        legend: {
            position: 'right',
            labels: {
                color: isDark.value ? '#F1F5F9' : '#1E293B',
                font: { family: 'monospace', size: 11, weight: 'bold' },
                boxWidth: 14,
                padding: 14,
                usePointStyle: true,
                pointStyle: 'rectRounded',
            },
        },
        tooltip: {
            backgroundColor: isDark.value ? '#0F172A' : '#1E293B',
            titleFont: { family: 'monospace', size: 12 },
            bodyFont: { family: 'monospace', size: 12 },
            padding: 10,
            cornerRadius: 6,
        },
    },
}));
</script>
