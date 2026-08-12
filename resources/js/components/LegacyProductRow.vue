<!--
  LegacyProductRow.vue — Composition API refactor.

  Este componente conserva exactamente el contrato público del legacy:
    props:
      - product: Object, required
      - selected: Boolean, default false
      - readonly: Boolean, default false
    emits:
      - 'select'
      - 'quantity-change'
-->
<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    product: {
        type: Object,
        required: true,
        validator(value) {
            return ['id', 'name', 'sku', 'price', 'stock'].every((key) => key in value);
        },
    },
    selected: {
        type: Boolean,
        default: false,
    },
    readonly: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select', 'quantity-change']);

const draftStock = ref(props.product.stock);
const editingStock = ref(false);

const formattedPrice = computed(() => {
    return new Intl.NumberFormat('es-VE', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
    }).format(Number(props.product.price));
});

const stockLooksLow = computed(() => Number(props.product.stock) <= 0);

watch(
    () => props.product.stock,
    (newStock) => {
        draftStock.value = newStock;
    },
);

function onToggleSelect() {
    emit('select', props.product.id);
}

function startEditingStock() {
    if (props.readonly) return;
    editingStock.value = true;
}

function confirmStock() {
    editingStock.value = false;
    const parsed = Number(draftStock.value);

    if (Number.isNaN(parsed)) {
        draftStock.value = props.product.stock;
        return;
    }

    emit('quantity-change', { id: props.product.id, stock: parsed });
}

function cancelEditingStock() {
    draftStock.value = props.product.stock;
    editingStock.value = false;
}
</script>

<template>
    <tr class="legacy-product-row" :class="{ 'legacy-product-row--low': stockLooksLow }">
        <td>
            <input
                type="checkbox"
                :checked="selected"
                :disabled="readonly"
                @change="onToggleSelect"
            >
        </td>
        <td>{{ product.name }}</td>
        <td><code>{{ product.sku }}</code></td>
        <td>{{ formattedPrice }}</td>
        <td>
            <span v-if="!editingStock" @dblclick="startEditingStock">
                {{ product.stock }}
            </span>
            <span v-else>
                <input
                    v-model="draftStock"
                    type="number"
                    step="0.000001"
                    @keyup.enter="confirmStock"
                    @keyup.esc="cancelEditingStock"
                    @blur="confirmStock"
                >
            </span>
        </td>
    </tr>
</template>

<style scoped>
.legacy-product-row--low {
    background-color: rgba(255, 0, 0, 0.08);
}
</style>
