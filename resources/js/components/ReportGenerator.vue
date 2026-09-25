<script>
import { useReportStore } from '../stores/reportStore';
export default {
    name: "ReportGenerator",
    setup() {
        const reportStore = useReportStore();

        return  {
            reportStore
        }
    }
}
</script>

<template>
    <div class="font-instrument p-6 w-full max-w-4xl bg-white rounded-xl shadow-md  flex items-center flex-col justify-between">
        <h2 class="text-xl font-bold text-center w-full mb-4">Генератор отчетов</h2>
        <form @submit.prevent="reportStore.startGeneration" class="form flex-1 flex flex-col justify-center gap-3">
            <div class="form-group">
                <label>Дата начала:</label>
                <input type="date" v-model="reportStore.filters.start_date" required />
            </div>

            <div class="form-group">
                <label>Дата окончания:</label>
                <input type="date" v-model="reportStore.filters.end_date" required />
            </div>

            <div class="form-group">
                <label>Статус заказа: </label>
                <select v-model="reportStore.filters.status">
                    <option value="">Все статусы </option>
                    <option value="pending">Ожидает</option>
                    <option value="completed">Завершен</option>
                    <option value="cancelled">Отменен</option>
                </select>
            </div>

            <!-- Нижняя часть: Кнопка и статусы (уже отцентрированы) -->
            <div class="flex flex-col items-center w-full mt-auto">

                <button
                    type="submit"
                    @click.prevent="reportStore.startGeneration"
                    :disabled="reportStore.isLoading"
                    class="px-4 py-2 bg-blue-500 text-white rounded disabled:bg-gray-400 w-fit"
                >
                    {{ reportStore.isLoading ? 'Формирование...' : 'Скачать отчет (CSV)' }}
                </button>

                <div class="mt-2 text-sm font-medium text-center min-h-[20px]">
                    <p v-if="reportStore.fileGenerationStatus === 'processing'" class="text-yellow-600">
                        ⏳ Задача добавлена в очередь Laravel.
                    </p>
                    <p v-if="reportStore.fileGenerationStatus === 'completed'" class="text-green-600">
                        ✅ Отчет успешно сгенерирован воркером!
                    </p>
                    <p v-if="reportStore.fileGenerationStatus === 'error'" class="text-red-600">
                        ❌ Произошла ошибка.
                    </p>
                </div>

            </div>
        </form>
    </div>
</template>

<style scoped>

</style>
