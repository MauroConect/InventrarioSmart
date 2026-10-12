<div class="rounded-md border border-slate-200 bg-slate-50 p-3 space-y-3">
    <div class="flex items-center justify-between gap-2">
        <p class="text-sm font-semibold text-gray-800">Campos extra</p>
        <button
            type="button"
            x-show="customCanManage"
            @click="showAddCustomField = !showAddCustomField"
            class="rounded-md bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700"
        >
            Agregar
        </button>
    </div>
    <p class="text-xs text-gray-500">Estos campos quedan en este comercio y se piden en cada alta.</p>

    <div x-show="showAddCustomField" x-cloak class="space-y-2 rounded-md border bg-white p-3">
        <input x-model="newCustomField.label" placeholder="Nombre del campo (ej. Edad, N° de ficha)" class="w-full rounded-md border px-3 py-2 text-sm">
        <div class="flex flex-wrap gap-2">
            <select x-model="newCustomField.type" class="flex-1 rounded-md border px-3 py-2 text-sm">
                <template x-for="(label, type) in customTypes" :key="type">
                    <option :value="type" x-text="label"></option>
                </template>
            </select>
            <label class="flex items-center gap-2 text-xs text-gray-600">
                <input type="checkbox" x-model="newCustomField.required">
                Obligatorio
            </label>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" @click="showAddCustomField = false" class="rounded-md border px-3 py-1.5 text-xs">Cancelar</button>
            <button type="button" @click="addCustomField()" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white">Crear campo</button>
        </div>
    </div>

    <template x-if="customFields.length === 0">
        <p class="text-xs text-gray-400">Todavía no hay campos extra. Usá Agregar para crear uno numérico, de texto, fecha o sí/no.</p>
    </template>

    <template x-for="field in customFields" :key="field.id">
        <div class="space-y-1">
            <div class="flex items-center justify-between gap-2">
                <label class="text-xs font-medium text-gray-700">
                    <span x-text="field.label"></span>
                    <span x-show="field.required" class="text-red-600">*</span>
                </label>
                <button type="button" x-show="customCanManage" @click.stop.prevent="removeCustomField(field.id)" class="text-[11px] text-red-600 hover:underline">Quitar</button>
            </div>
            <template x-if="field.type === 'text'">
                <input type="text" x-model="customValues[field.id]" class="w-full rounded-md border px-3 py-2 text-sm" :required="field.required">
            </template>
            <template x-if="field.type === 'number'">
                <input type="number" step="any" x-model="customValues[field.id]" class="w-full rounded-md border px-3 py-2 text-sm" :required="field.required">
            </template>
            <template x-if="field.type === 'date'">
                <input type="date" x-model="customValues[field.id]" class="w-full rounded-md border px-3 py-2 text-sm" :required="field.required">
            </template>
            <template x-if="field.type === 'textarea'">
                <textarea rows="2" x-model="customValues[field.id]" class="w-full rounded-md border px-3 py-2 text-sm" :required="field.required"></textarea>
            </template>
            <label x-show="field.type === 'checkbox'" class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" x-model="customValues[field.id]">
                <span>Sí</span>
            </label>
        </div>
    </template>
</div>
