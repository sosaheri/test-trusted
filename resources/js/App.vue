<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import LegacyProductRow from './components/LegacyProductRow.vue';

const STUB_LOGIN = {
    email: 'ana@empresa-uno.test',
    password: 'password',
};

const products = ref([]);
const totalProducts = ref(0);
const loading = ref(false);
const importRun = ref(null);
const uploadFile = ref(null);
const drawerOpen = ref(false);
const isPolling = ref(false);
const pollingTimer = ref(null);
const selectedProductIds = ref([]);
const searchQuery = ref('');
const searchTimer = ref(null);
const authReady = ref(false);
const authError = ref('');

const pagination = ref({
    page: 1,
    itemsPerPage: 15,
    sortBy: 'id',
    sortDir: 'desc',
});

const currentStatus = computed(() => importRun.value?.status ?? 'idle');

const statusMeta = {
    idle: { color: 'secondary', label: 'Sin corrida activa' },
    pending: { color: 'warning', label: 'En cola de validación' },
    processing: { color: 'info', label: 'Procesando CSV' },
    validated: { color: 'success', label: 'Validado' },
    applied: { color: 'success', label: 'Aplicado al catálogo' },
    failed: { color: 'error', label: 'Requiere revisión' },
};

const statusColor = computed(() => statusMeta[currentStatus.value]?.color ?? 'secondary');
const statusLabel = computed(() => statusMeta[currentStatus.value]?.label ?? 'Sin corrida activa');
const summaryLabel = computed(() => {
    if (!importRun.value) {
        return 'Todavía no hay una corrida de importación.';
    }

    return `Filas totales: ${importRun.value.total_rows ?? 0} · Válidas: ${importRun.value.valid_rows ?? 0} · Rechazadas: ${importRun.value.rejected_rows ?? 0}`;
});

function setAuthToken(token) {
    if (token) {
        axios.defaults.headers.common.Authorization = `Bearer ${token}`;
        return;
    }

    delete axios.defaults.headers.common.Authorization;
}

async function ensureAuthenticated() {
    try {
        const response = await axios.post('/login', STUB_LOGIN);
        setAuthToken(response.data.token);
        authReady.value = true;
        authError.value = '';
    } catch (error) {
        authReady.value = false;
        authError.value = 'El stub de autenticación no respondió correctamente. Verifica el entorno de la API.';
        console.error('No se pudo autenticar con el stub del frontend.', error);
        throw error;
    }
}

const uploadMessage = computed(() => {
    if (!importRun.value) {
        return 'Sube un CSV para iniciar la validación del catálogo.';
    }

    if (currentStatus.value === 'failed') {
        return 'La importación falló. Revisa el archivo y vuelve a intentarlo.';
    }

    if ((importRun.value.rejected_rows ?? 0) > 0) {
        return `La importación terminó con ${importRun.value.rejected_rows} filas rechazadas. Revisa el detalle y corrige el archivo antes de aplicar.`;
    }

    if (currentStatus.value === 'validated') {
        return 'La importación quedó validada y lista para aplicarse al catálogo del tenant.';
    }

    if (currentStatus.value === 'pending' || currentStatus.value === 'processing') {
        return 'El archivo está siendo validado en segundo plano. Espera unos segundos y la vista actualizará el estado.';
    }

    return 'La importación se está ejecutando.';
});
const uploadAlertType = computed(() => {
    if (!importRun.value) {
        return 'info';
    }

    if (currentStatus.value === 'failed') {
        return 'error';
    }

    if ((importRun.value.rejected_rows ?? 0) > 0) {
        return 'warning';
    }

    return 'success';
});

function stopPolling() {
    if (pollingTimer.value) {
        clearTimeout(pollingTimer.value);
        pollingTimer.value = null;
    }

    isPolling.value = false;
}

async function fetchProducts() {
    if (!authReady.value) {
        await ensureAuthenticated();
    }

    loading.value = true;

    try {
        const response = await axios.get('/products', {
            params: {
                page: pagination.value.page,
                per_page: pagination.value.itemsPerPage,
                sort_by: pagination.value.sortBy,
                sort_dir: pagination.value.sortDir,
                search: searchQuery.value,
            },
        });

        products.value = response.data.data;
        totalProducts.value = response.data.total;
    } finally {
        loading.value = false;
    }
}

async function pollImportRun(runId) {
    if (!runId) {
        return;
    }

    try {
        const response = await axios.get(`/import-runs/${runId}`);
        importRun.value = {
            ...(importRun.value ?? {}),
            ...response.data,
        };

        const activeStates = ['pending', 'processing', 'validated'];

        if (activeStates.includes(response.data.status)) {
            isPolling.value = true;
            pollingTimer.value = setTimeout(() => {
                pollImportRun(runId);
            }, 2000);
            return;
        }

        if ((response.data.rejected_rows ?? 0) > 0 && response.data.status === 'validated') {
            drawerOpen.value = true;
        }

        isPolling.value = false;
        await fetchProducts();
    } catch (error) {
        console.error('No se pudo consultar el estado del import run.', error);
        isPolling.value = false;
    }
}

async function uploadCsv() {
    if (!authReady.value) {
        await ensureAuthenticated();
    }

    if (!uploadFile.value) {
        return;
    }

    const formData = new FormData();
    formData.append('file', uploadFile.value);

    loading.value = true;

    try {
        const response = await axios.post('/import-runs', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        importRun.value = {
            ...(response.data.import_run ?? {}),
            status: response.data.import_run?.status ?? 'pending',
        };

        stopPolling();
        await pollImportRun(response.data.import_run.id);
    } catch (error) {
        console.error('Error al subir el CSV.', error);
    } finally {
        loading.value = false;
    }
}

async function applyImport() {
    if (!authReady.value) {
        await ensureAuthenticated();
    }

    if (!importRun.value?.id) {
        return;
    }

    try {
        const response = await axios.post(`/import-runs/${importRun.value.id}/apply`);
        importRun.value = {
            ...(importRun.value ?? {}),
            ...response.data.import_run,
        };
        drawerOpen.value = true;
        await fetchProducts();
    } catch (error) {
        console.error('Error al aplicar la importación.', error);
    }
}

function toggleSelect(productId) {
    const index = selectedProductIds.value.indexOf(productId);

    if (index >= 0) {
        selectedProductIds.value.splice(index, 1);
        return;
    }

    selectedProductIds.value.push(productId);
}

function handleQuantityChange(payload) {
    const target = products.value.find((product) => product.id === payload.id);

    if (!target) {
        return;
    }

    target.stock = payload.stock;
}

watch(
    () => [pagination.value.page, pagination.value.itemsPerPage, pagination.value.sortBy, pagination.value.sortDir],
    () => {
        fetchProducts();
    },
    { flush: 'post' },
);

watch(searchQuery, () => {
    if (searchTimer.value) {
        clearTimeout(searchTimer.value);
    }

    searchTimer.value = setTimeout(() => {
        pagination.value.page = 1;
        fetchProducts();
    }, 400);
});

onMounted(async () => {
    try {
        await ensureAuthenticated();
        await fetchProducts();
    } catch (error) {
        console.error('No se pudo inicializar la sesión del catálogo.', error);
    }
});

onBeforeUnmount(() => {
    stopPolling();
    if (searchTimer.value) {
        clearTimeout(searchTimer.value);
    }
});
</script>

<template>
    <v-app>
        <v-main>
            <v-container class="py-8">
                <v-alert
                    v-if="authError"
                    type="error"
                    variant="tonal"
                    class="mb-4"
                    border="start"
                    prominent
                >
                    {{ authError }}
                </v-alert>
                <v-row>
                    <v-col cols="12" class="d-flex justify-space-between align-center">
                        <div>
                            <h2 class="text-h4 font-weight-bold mb-1">Catálogo empresarial</h2>
                            <p class="text-body-2 text-medium-emphasis">
                                Carga de CSV, validación, apply y catálogo del tenant actual.
                            </p>
                        </div>
                    </v-col>
                </v-row>

                <v-card class="mb-6" elevation="2">
                    <v-card-title>Importador de catálogo</v-card-title>
                    <v-card-text>
                        <v-row align="center">
                            <v-col cols="12" md="8">
                                <v-file-input
                                    v-model="uploadFile"
                                    accept=".csv,text/csv"
                                    label="Selecciona un CSV"
                                    prepend-icon="mdi-file-upload"
                                    show-size
                                    chips
                                />
                            </v-col>
                            <v-col cols="12" md="4" class="d-flex align-center justify-end">
                                <v-btn
                                    color="primary"
                                    size="large"
                                    :loading="loading"
                                    :disabled="!uploadFile"
                                    @click="uploadCsv"
                                >
                                    Procesar archivo
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <v-alert
                    v-if="importRun"
                    :type="statusColor"
                    variant="tonal"
                    class="mb-4"
                    border="start"
                    prominent
                >
                    <div class="d-flex align-center justify-space-between flex-wrap gap-2">
                        <strong>{{ statusLabel }}</strong>
                        <span>{{ summaryLabel }}</span>
                    </div>
                </v-alert>

                <v-alert
                    :type="uploadAlertType"
                    variant="tonal"
                    class="mb-4"
                    border="start"
                    prominent
                >
                    {{ uploadMessage }}
                </v-alert>

                <v-progress-linear
                    v-if="currentStatus === 'pending' || currentStatus === 'processing' || currentStatus === 'validated' || isPolling"
                    :model-value="currentStatus === 'validated' ? 100 : undefined"
                    :indeterminate="currentStatus !== 'validated'"
                    color="primary"
                    class="mb-4"
                />

                <v-row class="mb-4">
                    <v-col cols="12" md="4">
                        <v-card elevation="1">
                            <v-card-text>
                                <div class="text-caption text-medium-emphasis">Total de filas</div>
                                <div class="text-h5 font-weight-bold">{{ importRun?.total_rows ?? 0 }}</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-card elevation="1">
                            <v-card-text>
                                <div class="text-caption text-medium-emphasis">Válidas</div>
                                <div class="text-h5 font-weight-bold text-success">{{ importRun?.valid_rows ?? 0 }}</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-card elevation="1">
                            <v-card-text>
                                <div class="text-caption text-medium-emphasis">Rechazadas</div>
                                <div class="text-h5 font-weight-bold text-error">{{ importRun?.rejected_rows ?? 0 }}</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <v-card elevation="2">
                    <v-card-title class="d-flex align-center justify-space-between flex-wrap">
                        <span>Catálogo del tenant</span>
                        <v-btn
                            v-if="importRun?.id"
                            color="success"
                            variant="flat"
                            @click="applyImport"
                            :disabled="currentStatus !== 'validated'"
                        >
                            Aplicar importación
                        </v-btn>
                    </v-card-title>

                    <v-card-text>
                        <v-text-field
                            v-model="searchQuery"
                            clearable
                            prepend-inner-icon="mdi-magnify"
                            label="Buscar por nombre o SKU"
                            density="comfortable"
                            class="mb-4"
                        />

                        <v-table density="comfortable">
                            <thead>
                                <tr>
                                    <th class="text-left">Seleccionar</th>
                                    <th class="text-left">Nombre</th>
                                    <th class="text-left">SKU</th>
                                    <th class="text-left">Precio</th>
                                    <th class="text-left">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <LegacyProductRow
                                    v-for="product in products"
                                    :key="product.id"
                                    :product="product"
                                    :selected="selectedProductIds.includes(product.id)"
                                    :readonly="false"
                                    @select="toggleSelect"
                                    @quantity-change="handleQuantityChange"
                                />
                            </tbody>
                        </v-table>

                        <div v-if="!products.length && !loading" class="text-body-2 text-medium-emphasis py-4">
                            No hay productos para mostrar en este tenant.
                        </div>

                        <v-pagination
                            v-model="pagination.page"
                            :length="Math.max(1, Math.ceil(totalProducts / pagination.itemsPerPage))"
                            :total-visible="5"
                            class="mt-4"
                        />
                    </v-card-text>
                </v-card>

                <v-navigation-drawer
                    v-model="drawerOpen"
                    location="right"
                    temporary
                    width="420"
                >
                    <v-toolbar density="comfortable" flat>
                        <v-toolbar-title>Detalle de importación</v-toolbar-title>
                        <v-btn icon="mdi-close" variant="text" @click="drawerOpen = false" />
                    </v-toolbar>

                    <v-card flat class="pa-4">
                        <v-list density="comfortable">
                            <v-list-item>
                                <v-list-item-title>ID de la corrida</v-list-item-title>
                                <v-list-item-subtitle>{{ importRun?.id ?? '—' }}</v-list-item-subtitle>
                            </v-list-item>
                            <v-list-item>
                                <v-list-item-title>Estado</v-list-item-title>
                                <v-list-item-subtitle>{{ statusLabel }}</v-list-item-subtitle>
                            </v-list-item>
                            <v-list-item>
                                <v-list-item-title>Filas válidas</v-list-item-title>
                                <v-list-item-subtitle>{{ importRun?.valid_rows ?? 0 }}</v-list-item-subtitle>
                            </v-list-item>
                            <v-list-item>
                                <v-list-item-title>Filas rechazadas</v-list-item-title>
                                <v-list-item-subtitle>{{ importRun?.rejected_rows ?? 0 }}</v-list-item-subtitle>
                            </v-list-item>
                        </v-list>
                    </v-card>
                </v-navigation-drawer>
            </v-container>
        </v-main>
    </v-app>
</template>
