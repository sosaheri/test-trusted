<!--
  LegacyProductRow.vue — Options API (legacy).

  REFACTOR OBLIGATORIO (§3.4): migrar a Composition API con `<script setup>`
  SIN alterar el contrato de props/emits documentado abajo. Cualquier
  consumidor externo de este componente (por ejemplo tu tabla de catálogo)
  debe seguir funcionando exactamente igual después del refactor.

  Contrato público (no cambiar):
    props:
      - product: Object, required. Forma: { id, name, sku, price, stock }
      - selected: Boolean, default false
      - readonly: Boolean, default false
    emits:
      - 'select'          -> (productId: number)              al togglear el checkbox
      - 'quantity-change'  -> (payload: { id, stock: number })  al confirmar edición de stock
-->
<script>
export default {
    name: 'LegacyProductRow',
    props: {
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
    },
    emits: ['select', 'quantity-change'],
    data() {
        return {
            draftStock: this.product.stock,
            editingStock: false,
        };
    },
    computed: {
        formattedPrice() {
            // Presentación únicamente. La precisión real (DECIMAL(18,4))
            // se preserva en backend; aquí solo formateamos para mostrar.
            return new Intl.NumberFormat('es-VE', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2,
            }).format(Number(this.product.price));
        },
        stockLooksLow() {
            return Number(this.product.stock) <= 0;
        },
    },
    watch: {
        'product.stock'(newStock) {
            this.draftStock = newStock;
        },
    },
    methods: {
        onToggleSelect() {
            this.$emit('select', this.product.id);
        },
        startEditingStock() {
            if (this.readonly) return;
            this.editingStock = true;
        },
        confirmStock() {
            this.editingStock = false;
            const parsed = Number(this.draftStock);
            if (Number.isNaN(parsed)) {
                this.draftStock = this.product.stock;
                return;
            }
            this.$emit('quantity-change', { id: this.product.id, stock: parsed });
        },
        cancelEditingStock() {
            this.draftStock = this.product.stock;
            this.editingStock = false;
        },
    },
};
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
