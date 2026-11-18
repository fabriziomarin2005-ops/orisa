<template>
    <div>
        <div class="d-flex flex-wrap justify-space-between align-center mb-6 ga-4">
            <div>
                <h1 class="text-h5 font-weight-bold">Catálogo de Zonas Industriales</h1>
                <p class="text-body-2 text-medium-emphasis">
                    Áreas monitoreadas por nodos ORISA (se vinculan automáticamente al recibir reportes de campo)
                </p>
            </div>
        </div>

        <v-row>
            <!-- Formulario de Alta Manual -->
            <v-col cols="12" md="4">
                <v-card class="pa-5 border-industrial">
                    <div class="text-subtitle-1 font-weight-bold mb-4">
                        Registrar Nueva Zona
                    </div>

                    <v-form @submit.prevent="guardarZona">
                        <v-text-field
                            v-model="form.nombre"
                            label="Nombre de la Zona (ej. Expo Umma)"
                            variant="outlined"
                            density="comfortable"
                            required
                            class="mb-3"
                        ></v-text-field>

                        <v-text-field
                            v-model="form.codigo"
                            label="Código Nomenclatura (ej. ZN-EXPO)"
                            variant="outlined"
                            density="comfortable"
                            class="mb-3 font-mono"
                        ></v-text-field>

                        <v-textarea
                            v-model="form.descripcion"
                            label="Descripción operativa del área"
                            variant="outlined"
                            rows="3"
                            class="mb-4"
                        ></v-textarea>

                        <v-alert
                            v-if="errorMsg"
                            type="error"
                            variant="tonal"
                            density="compact"
                            class="mb-3 text-caption"
                        >
                            {{ errorMsg }}
                        </v-alert>

                        <v-btn
                            type="submit"
                            block
                            color="primary"
                            variant="flat"
                            :loading="guardando"
                            prepend-icon="mdi-plus-box-outline"
                        >
                            Dar de Alta Zona
                        </v-btn>
                    </v-form>
                </v-card>
            </v-col>

            <!-- Tabla de Zonas Registradas -->
            <v-col cols="12" md="8">
                <v-card class="border-industrial">
                    <v-data-table
                        :headers="headers"
                        :items="zonas"
                        :loading="cargando"
                        class="text-body-2"
                        no-data-text="No hay zonas registradas todavía."
                    >
                        <template #item.codigo="{ item }">
                            <span class="font-mono font-weight-bold">
                                {{ item.codigo || 'S/C' }}
                            </span>
                        </template>

                        <template #item.reportes_count="{ item }">
                            <v-chip size="small" variant="outlined" class="font-mono">
                                {{ item.reportes_count }} incidentes
                            </v-chip>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const zonas = ref([]);
const cargando = ref(false);
const guardando = ref(false);
const errorMsg = ref('');

const form = ref({
    nombre: '',
    codigo: '',
    descripcion: '',
});

const headers = [
    { title: 'CÓDIGO', key: 'codigo', width: '130px' },
    { title: 'NOMBRE DE ZONA / LUGAR', key: 'nombre' },
    { title: 'DESCRIPCIÓN', key: 'descripcion' },
    { title: 'HISTÓRICO', key: 'reportes_count', align: 'center', width: '150px' },
];

async function cargarZonas() {
    cargando.value = true;
    try {
        const { data } = await axios.get('/api/zonas');
        zonas.value = data;
    } catch (error) {
        console.error('Error al listar zonas:', error);
    } finally {
        cargando.value = false;
    }
}

async function guardarZona() {
    if (!form.value.nombre.trim()) return;
    guardando.value = true;
    errorMsg.value = '';
    try {
        await axios.post('/api/zonas', form.value);
        form.value = { nombre: '', codigo: '', descripcion: '' };
        await cargarZonas();
    } catch (error) {
        errorMsg.value = error.response?.data?.message || 'Error al registrar la zona.';
    } finally {
        guardando.value = false;
    }
}

onMounted(() => {
    cargarZonas();
});
</script>
