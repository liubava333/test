<script>
import {useReportStore} from "../stores/reportStore.js";
import {ref} from "vue";

export default {
    name: "ParsingFile",
    setup() {
        const reportStore = useReportStore();
        const fileInput = ref(null); // щоб очищать поле после успешной загрузки, чтобы пользователь мог загрузить новый файл
        const selectedFile = ref(null);

        // Перехватываем выбор файла
        function handleFileChange(event) {
            const file = event.target.files[0];
            if (file) {
                selectedFile.value = file;
            }
        }

        async function submitFile() {
            if (!selectedFile.value) {
                console.log('Файл не выбран, отмена');
                return;
            }
            // Блокируем старый опрос перед новым запуском
            reportStore.stopPolling();

            const isUploaded = await reportStore.parseData(selectedFile.value);
            if (isUploaded) {
                reportStore.startPolling();
            } else {
                console.log('Ошибка загрузки файла на сервер.');
            }
            // Очищаем локальное состояние Vue
            selectedFile.value = null;

            // Очищаем сам HTML-инпут на странице через ref
            if (fileInput.value) {
                fileInput.value.value = ''; // Сбрасывает выбранный файл в браузере
            }
        }

        return  {
            reportStore, handleFileChange, submitFile, selectedFile,
            fileInput
        }
    }
}
</script>

<template>
    <div class="p-6 w-full max-w-4xl bg-white rounded-xl shadow-md  flex flex-col justify-between space-y-4 min-h-[340px]">
        <h2 class="text-xl font-bold text-center">Загрузка и парсинг локального файла</h2>
        <div class="form flex-1 flex flex-col justify-center gap-3  w-full max-w-md mx-auto ">
            <div class="flex flex-col space-y-2">
                <label class="text-sm font-medium text-gray-700">Выберите файл с компьютера (XLSX, CSV, JSON):</label>
                <input
                    type="file"
                    ref="fileInput"
                    @change="handleFileChange"
                    accept=".csv, .json, .xlsx, .xls"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                />
            </div>

            <div v-if="selectedFile" class="text-sm text-green-600 font-medium">
                Выбран файл: {{ selectedFile.name }} ({{ (selectedFile.size / 1024).toFixed(2) }} KB)
            </div>
            <button
                type="button"
                @click="submitFile"
                :disabled="reportStore.fileParsingStatus === 'processing' || !selectedFile "
                class="w-full px-4 py-2 bg-blue-600 text-white rounded disabled:bg-gray-400 font-medium"
            >
                {{ reportStore.fileParsingStatus === 'processing'
                ? 'Парсинг файла...'
                : 'Загрузить и распарсить'
                }}
            </button>
        </div>
        <!-- ПРОГРЕСС БАР -->
        <div class="w-full max-w-md mx-auto mt-6">
            <div class="flex justify-between text-sm font-medium">
                <span>Статус: {{ reportStore.fileParsingStatus === 'completed' ? 'Готово!' : 'Обработка строк...' }}</span>
                <span>{{ reportStore.progress }}%</span>
            </div>
            <!-- Полоса прогресса -->
            <div class="w-full max-w-md mx-auto bg-gray-200 rounded-full h-4 overflow-hidden">
                <div
                    class="bg-green-500 h-full transition-all duration-300 ease-out"
                    :style="{ width: reportStore.progress + '%' }"
                ></div>
            </div>
        </div>

        <!-- ВЫВОД РАСПАРСЕННЫХ ДАННЫХ -->
        <div v-if="reportStore.fileParsingStatus === 'completed' && reportStore.parsedData.length" class="mt-6">
            <h3 class="font-semibold mb-2">Результат парсинга (Первые 10 строк):</h3>
            <div class="border rounded overflow-hidden overflow-x-auto">

                <!-- ================= ТАБЛИЦА ДЛЯ CSV ================= -->
                <table v-if="reportStore.fileExtension === 'csv'" class="w-full text-left text-sm min-w-[500px]">
                    <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 border-b font-semibold w-16">Строка</th>
                        <th class="p-2 border-b font-semibold">Данные CSV (Строка целиком)</th>
                        <th class="p-2 border-b font-semibold w-24">Время</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="item in reportStore.parsedData" :key="item.row" class="border-t hover:bg-gray-50">
                        <td class="p-2 font-bold text-gray-500">{{ item.row }}</td>
                        <!-- Выводим как обычный текст или массив через запятую -->
                        <td class="p-2 text-gray-700">
                            {{ typeof reportStore.parseJsonData(item.data) === 'object' ? Object.values(reportStore.parseJsonData(item.data)).join(' | ') : item.data }}
                        </td>
                        <td class="p-2 text-gray-400 text-xs">{{ item.parsed_at }}</td>
                    </tr>
                    </tbody>
                </table>

                <!-- ================= ТАБЛИЦА ДЛЯ JSON ================= -->
                <table v-else-if="reportStore.fileExtension === 'json'" class="w-full text-left text-sm min-w-[600px]">
                    <thead class="bg-gray-100">
                    <tr>
                        <th>Ключ</th>
                        <th>Значение</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="item in reportStore.parsedData" :key="item.row" class="border-t hover:bg-gray-50">
                        <td> {{ item.row }}</td>
                        <td>
                            <div v-if="typeof reportStore.parseJsonData(item.data) === 'object' && reportStore.parseJsonData(item.data) !== null">
                                <ul class="list-none pl-5 space-y-1 text-xs">
                                    <li v-for="(value, key) in reportStore.parseJsonData(item.data)" :key="key">
                                        <span class="font-semibold text-gray-600">{{ key }}:</span>
                                        {{ typeof value === 'object' ? JSON.stringify(value) : value }}
                                    </li>
                                </ul>
                            </div>
                            <div v-else class="text-sm font-medium text-gray-800">
                                {{ reportStore.parseJsonData(item.data) }}
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
