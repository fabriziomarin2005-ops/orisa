<template>
    <v-dialog
        :model-value="modelValue"
        @update:model-value="$emit('update:modelValue', $event)"
        max-width="960"
        scrollable
    >
        <v-card v-if="incidente" class="border-industrial">
            <!-- Encabezado de la Ficha -->
            <v-card-title class="d-flex justify-space-between align-center py-4 px-6 border-b">
                <div>
                    <div class="d-flex align-center ga-2">
                        <span class="font-mono text-subtitle-2 text-medium-emphasis">
                            {{ incidente.codigo_incidente }}
                        </span>
                        <v-chip size="x-small" variant="outlined" class="font-mono">
                            RIESGO {{ incidente.nivel_riesgo?.toUpperCase() }}
                        </v-chip>
                    </div>
                    <div class="text-h6 font-weight-bold mt-1">
                        {{ incidente.titulo }}
                    </div>
                </div>
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    @click="$emit('update:modelValue', false)"
                ></v-btn>
            </v-card-title>

            <v-card-text class="pa-6">
                <!-- Metadatos Superiores -->
                <v-row class="mb-4" dense>
                    <v-col cols="12" sm="4">
                        <div class="pa-3 rounded border-industrial bg-surface-variant">
                            <div class="text-caption text-medium-emphasis">ZONA / UBICACIÓN</div>
                            <div class="font-weight-bold d-flex align-center mt-1">
                                <v-icon icon="mdi-map-marker" size="16" class="mr-1"></v-icon>
                                {{ incidente.lugar }}
                            </div>
                        </div>
                    </v-col>
                    <v-col cols="12" sm="4">
                        <div class="pa-3 rounded border-industrial bg-surface-variant">
                            <div class="text-caption text-medium-emphasis">ENCARGADO DE ÁREA</div>
                            <div class="font-weight-bold d-flex align-center mt-1">
                                <v-icon icon="mdi-account-hard-hat" size="16" class="mr-1"></v-icon>
                                {{ incidente.encargado }}
                            </div>
                        </div>
                    </v-col>
                    <v-col cols="12" sm="4">
                        <div class="pa-3 rounded border-industrial bg-surface-variant">
                            <div class="text-caption text-medium-emphasis">TIMESTAMP HARDWARE</div>
                            <div class="font-weight-bold font-mono d-flex align-center mt-1">
                                <v-icon icon="mdi-clock-outline" size="16" class="mr-1"></v-icon>
                                {{ formatearFecha(incidente.fecha) }}
                            </div>
                        </div>
                    </v-col>
                </v-row>

                <v-row>
                    <!-- Columna Izquierda: Análisis Gemini y Evidencia Óptica -->
                    <v-col cols="12" md="6">
                        <div class="mb-4">
                            <div class="text-overline font-weight-bold mb-1">
                                <v-icon icon="mdi-eye-outline" size="16" class="mr-1"></v-icon>
                                DESCRIPCIÓN VISUAL (GEMINI VISION)
                            </div>
                            <v-sheet class="pa-4 rounded border-industrial text-body-2">
                                {{ incidente.descripcion }}
                            </v-sheet>
                        </div>

                        <div class="mb-4">
                            <div class="text-overline font-weight-bold mb-1">
                                <v-icon icon="mdi-magnify-scan" size="16" class="mr-1"></v-icon>
                                CAUSAS IDENTIFICADAS
                            </div>
                            <v-sheet class="pa-4 rounded border-industrial text-body-2">
                                {{ incidente.causas }}
                            </v-sheet>
                        </div>

                        <!-- Visor de Captura ESP32-CAM -->
                        <div class="mb-4">
                            <div class="text-overline font-weight-bold mb-1">
                                <v-icon icon="mdi-camera-iris" size="16" class="mr-1"></v-icon>
                                CAPTURA ÓPTICA (ESP32-CAM OV2640)
                            </div>
                            <v-card v-if="incidente.foto_url" class="border-industrial overflow-hidden">
                                <v-img
                                    :src="incidente.foto_url"
                                    max-height="260"
                                    cover
                                    class="bg-black"
                                ></v-img>
                            </v-card>
                            <v-sheet
                                v-else
                                class="pa-8 rounded border-industrial d-flex flex-column align-center justify-center text-center"
                                style="min-height: 180px;"
                            >
                                <v-icon icon="mdi-image-off-outline" size="36" class="text-medium-emphasis mb-2"></v-icon>
                                <div class="text-caption font-mono text-medium-emphasis">
                                    REPORTE RECIBIDO VÍA PAYLOAD JSON PURO
                                </div>
                                <div class="text-caption text-disabled">
                                    Sin binario de imagen adjunto en la transacción HTTP
                                </div>
                            </v-sheet>
                        </div>
                    </v-col>

                    <!-- Columna Derecha: Soluciones, Gestión de Estado y Línea de Tiempo -->
                    <v-col cols="12" md="6">
                        <div class="mb-5">
                            <div class="d-flex justify-space-between align-center mb-1">
                                <span class="text-overline font-weight-bold">
                                    <v-icon icon="mdi-clipboard-check-outline" size="16" class="mr-1"></v-icon>
                                    PROTOCOLO DE SOLUCIONES RECOMENDADAS
                                </span>
                                <span class="text-caption font-mono">
                                    {{ tareasCompletadas }}/{{ listaSoluciones.length }} COMPLETADAS
                                </span>
                            </div>

                            <v-sheet class="pa-2 rounded border-industrial">
                                <v-list density="compact" bg-color="transparent">
                                    <v-list-item
                                        v-for="(solucion, index) in listaSoluciones"
                                        :key="index"
                                        class="px-2"
                                    >
                                        <template #prepend>
                                            <v-checkbox-btn
                                                v-model="checklist[index]"
                                                color="primary"
                                            ></v-checkbox-btn>
                                        </template>
                                        <v-list-item-title
                                            class="text-body-2 text-wrap"
                                            :class="{ 'text-decoration-line-through text-medium-emphasis': checklist[index] }"
                                        >
                                            {{ solucion }}
                                        </v-list-item-title>
                                    </v-list-item>
                                </v-list>
                            </v-sheet>
                        </div>

                        <!-- Línea de Tiempo de Trazabilidad HSE -->
                        <div class="mb-5">
                            <div class="text-overline font-weight-bold mb-2">
                                <v-icon icon="mdi-timeline-clock-outline" size="16" class="mr-1"></v-icon>
                                TRAZABILIDAD Y AUDITORÍA DEL INCIDENTE
                            </div>
                            <v-sheet class="pa-3 rounded border-industrial">
                                <v-timeline density="compact" side="end" align="start" truncate-line="both">
                                    <v-timeline-item dot-color="primary" size="x-small">
                                        <div class="text-caption font-mono font-weight-bold">
                                            1. CAPTURA EN CAMPO (ESP32-S3)
                                        </div>
                                        <div class="text-caption text-medium-emphasis font-mono">
                                            {{ formatearFecha(incidente.fecha) }} — {{ incidente.encargado }}
                                        </div>
                                    </v-timeline-item>

                                    <v-timeline-item
                                        :dot-color="incidente.revisado_en ? 'primary' : 'grey'"
                                        size="x-small"
                                    >
                                        <div class="text-caption font-mono font-weight-bold">
                                            2. INICIO DE REVISIÓN TÉCNICA
                                        </div>
                                        <div class="text-caption text-medium-emphasis font-mono">
                                            {{
                                                incidente.revisado_en
                                                    ? formatearFecha(incidente.revisado_en)
                                                    : 'En espera de intervención'
                                            }}
                                        </div>
                                    </v-timeline-item>

                                    <v-timeline-item
                                        :dot-color="incidente.atendido_en ? 'primary' : 'grey'"
                                        size="x-small"
                                    >
                                        <div class="text-caption font-mono font-weight-bold">
                                            3. CIERRE Y RESOLUCIÓN HSE
                                        </div>
                                        <div class="text-caption text-medium-emphasis font-mono">
                                            {{
                                                incidente.atendido_en
                                                    ? formatearFecha(incidente.atendido_en)
                                                    : 'Pendiente de cierre operativo'
                                            }}
                                        </div>
                                        <div
                                            v-if="incidente.observaciones_cierre"
                                            class="mt-1 pa-2 rounded bg-surface-variant text-caption"
                                        >
                                            <strong>Nota de cierre:</strong> {{ incidente.observaciones_cierre }}
                                        </div>
                                    </v-timeline-item>
                                </v-timeline>
                            </v-sheet>
                        </div>

                        <v-divider class="my-4"></v-divider>

                        <!-- Actualización de Estado Operativo + Observaciones -->
                        <div>
                            <div class="text-overline font-weight-bold mb-2">
                                <v-icon icon="mdi-state-machine" size="16" class="mr-1"></v-icon>
                                GESTIÓN DE ESTADO OPERATIVO & NOTAS DE CIERRE
                            </div>
                            <v-select
                                v-model="estadoTemporal"
                                :items="estadosDisponibles"
                                item-title="label"
                                item-value="value"
                                variant="outlined"
                                density="comfortable"
                                hide-details
                                class="mb-3"
                            ></v-select>

                            <v-textarea
                                v-model="observacionesTemporal"
                                label="Observaciones técnicas / Notas de resolución"
                                placeholder="Describe las acciones correctivas aplicadas, repuestos utilizados o verificación de seguridad..."
                                variant="outlined"
                                rows="2"
                                auto-grow
                                hide-details
                                class="mb-3"
                            ></v-textarea>

                            <v-btn
                                block
                                color="primary"
                                variant="flat"
                                :loading="guardandoEstado"
                                prepend-icon="mdi-content-save-check-outline"
                                @click="actualizarEstado"
                            >
                                Guardar Estado y Trazabilidad
                            </v-btn>
                        </div>
                    </v-col>
                </v-row>
            </v-card-text>

            <v-card-actions class="px-6 py-3 border-t justify-space-between">
                <span class="text-caption font-mono text-medium-emphasis">
                    ID INTERNO: #{{ incidente.id }}
                </span>
                <div class="d-flex ga-2">
                    <v-btn
                        color="primary"
                        variant="flat"
                        prepend-icon="mdi-file-pdf-box"
                        :href="`/api/reportes/${incidente.id}/pdf`"
                        target="_blank"
                    >
                        Descargar PDF
                    </v-btn>
                    <v-btn variant="outlined" @click="$emit('update:modelValue', false)">
                        Cerrar Ficha
                    </v-btn>
                </div>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    incidente: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue', 'estado-actualizado']);

const checklist = ref([]);
const estadoTemporal = ref('pendiente');
const observacionesTemporal = ref('');
const guardandoEstado = ref(false);

const estadosDisponibles = [
    { label: 'PENDIENTE — En espera de asignación', value: 'pendiente' },
    { label: 'EN REVISIÓN — Personal técnico en sitio', value: 'en_revision' },
    { label: 'ATENDIDO — Incidente resuelto y verificado', value: 'atendido' },
];

const listaSoluciones = computed(() => {
    if (!props.incidente || !Array.isArray(props.incidente.soluciones)) {
        return [];
    }
    return props.incidente.soluciones;
});

const tareasCompletadas = computed(() => {
    return checklist.value.filter(Boolean).length;
});

function formatearFecha(valor) {
    if (!valor) return '-';
    return String(valor).replace('T', ' ').substring(0, 19);
}

watch(
    () => props.incidente,
    (nuevoIncidente) => {
        if (nuevoIncidente) {
            estadoTemporal.value = nuevoIncidente.estado || 'pendiente';
            observacionesTemporal.value = nuevoIncidente.observaciones_cierre || '';
            const totalSoluciones = Array.isArray(nuevoIncidente.soluciones)
                ? nuevoIncidente.soluciones.length
                : 0;
            checklist.value = new Array(totalSoluciones).fill(nuevoIncidente.estado === 'atendido');
        }
    },
    { immediate: true }
);

async function actualizarEstado() {
    if (!props.incidente) return;
    guardandoEstado.value = true;
    try {
        const { data } = await axios.patch(`/api/reportes/${props.incidente.id}/estado`, {
            estado: estadoTemporal.value,
            observaciones_cierre: observacionesTemporal.value,
        });
        emit('estado-actualizado', data.data);
    } catch (error) {
        console.error('Error al actualizar el estado del incidente:', error);
    } finally {
        guardandoEstado.value = false;
    }
}
</script>
