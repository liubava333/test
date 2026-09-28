import { defineStore } from 'pinia';
import {onBeforeUnmount, onMounted, reactive, ref} from 'vue';
import axios from 'axios';

export const useReportStore = defineStore('report', () => {
    // -----------PREVIEW-----------------------
    const previewImages = ref([]); // Для превью до отправки
    const serverImages = ref([]); // Для картинок, которые уже на сервере
    const isUploading = ref(false);
    const isDragOver = ref(false); // Управляет подсветкой

    // 2. Создаем функцию загрузки картинок с сервера
    const fetchServerImages = async () => {
        try {
            const response = await axios.get('/api/images');

            // Заполняем массив серверных картинок данными из БД
            serverImages.value = response.data.images.map(img => ({
                ...img,
                isEditing: false // не забываем добавить флаг для редактирования
            }));
        } catch (error) {
            console.error('Не удалось загрузить картинки с сервера:', error);
        }
    };
    // Отправка изображений на Laravel-бэкенд (сервер)
    const uploadImages = async () => {
        if (previewImages.value.length === 0) return;
        isUploading.value = true;
        const formData = new FormData();
        previewImages.value.forEach(item => formData.append('images[]', item.file));
        try {
            const response = await axios.post('/api/upload-images', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            // Laravel должен вернуть массив объектов с id и url
            // Пример ответа: [{id: 1, url: '/storage/uploads/1.jpg'}, ...]
            serverImages.value.push(...response.data.images.map(img => ({
                ...img, // .map - робить новий изменнений масив
                isEditing: false // флаг для переключения режима редактирования
            }))); // без ...(spread operator) буде масив в масиві(вложенность,яка зламає верстку)
            // Очищаем локальное превью
            previewImages.value.forEach(item => URL.revokeObjectURL(item.preview));
            previewImages.value = [];
        } catch (error) {
            console.error(error);
            alert('Ошибка при сохранении файлов');
        } finally {
            isUploading.value = false;
        }
    };
    // Логика обработки файлов (Клик / Дроп)
    const processFiles = (filesList) => {
        Array.from(filesList).forEach((file) => {
            if (!file.type.startsWith('image/')) return;
            const previewUrl = URL.createObjectURL(file); // Blob URL (існує в цій вкладці  до тех пор, пока документ (страница)
            // blob:http://localhost/e732168e-8b34-4d77-b42d-10f59ae0e8b6
            // живёт в этой вкладке, либо пока вы вручную не удалите ссылку из памяти с помощью метода URL.revokeObjectURL(previewUrl))
            previewImages.value.push({ file, preview: previewUrl });
        });
    };
    // Обработка обычного выбора через клик и окно проводника
    const handleFileChange = (e) => { processFiles(e.target.files); e.target.value = ''; };
    // ОБРАБОТКА ДРОПА (ПЕРЕТАСКИВАНИЯ) МЫШЬЮ
    const handleDrop = (e) => {
        isDragOver.value = false; // гасим подсветку
        if (e.dataTransfer?.files.length > 0) {
            processFiles(e.dataTransfer.files);
        }
    };
    const removeLocalImage = (index) => {
        URL.revokeObjectURL(previewImages.value[index].preview);
        previewImages.value.splice(index, 1);
    };
    // УДАЛЕНИЕ С СЕРВЕРА
    const deleteFromServer = async (id, index) => {
        if (!confirm('Вы уверены, что хотите удалить это изображение с сервера?')) return;

        try {
            await axios.delete(`/api/images/${id}`);
            serverImages.value.splice(index, 1); // Удаляем из отображения
        } catch (error) {
            console.error(error);
            alert('Не удалось удалить картинку');
        }
    };

    // РЕДАКТИРОВАНИЕ (ЗАМЕНА) КАРТИНКИ НА СЕРВЕРЕ
    const saveEditedImage = async (event, index) => {
        const file = event.target.files[0];
        if (!file || !file.type.startsWith('image/')) return;

        const imageId = serverImages.value[index].id;

        const formData = Object.entries({ image: file, _method: 'PUT' })
            .reduce((fd, [k, v]) =>
                (fd.append(k, v), fd), new FormData());
        try {
            const response = await axios.put(`/api/images/${imageId}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            // Обновляем картинку на клиенте данными с сервера
            serverImages.value[index].url = response.data.url;
            serverImages.value[index].isEditing = false;
        } catch (error) {
            console.error(error);
            alert('Ошибка при обновлении изображения');
        }
    };
    // ---------- FILE GENERATION ----------------

    const fileGenerationStatus = ref('idle'); // idle, processing, completed, error
    const fileParsingStatus = ref('idle');
    const isLoading = ref(false);
    const errorMessage = ref('');
    // Инициализация фильтров (по умолчанию текущий месяц)
    const today = new Date().toISOString().split('T')[0];
    const filters = reactive({
        start_date: today,
        end_date: today,
        status: '',
        type: 'export'
    });

    const startGeneration = async () => {
        isLoading.value = true;
        errorMessage.value = '';
        fileGenerationStatus.value = 'pending';

        try {
            // 1. Создаем отчет и получаем его ID
            const response = await axios.post('/api/reports/generate', filters);
            const reportId = response.data.id;

            // 2. Запускаем опрос статуса по ID
            trackProgress(reportId);
        } catch (error) {
            isLoading.value = false;
            errorMessage.value = 'Не удалось запустить генерацию.';
        }
    };

    const trackProgress = (generateReportId) => {
        const interval = setInterval(async () => {
            try {
                const res = await axios.get(`/api/reports/${generateReportId}/status`);
                fileGenerationStatus.value = res.data.status;

                if (res.data.status === 'completed') {
                    clearInterval(interval);
                    isLoading.value = false;
                    // 3. Скачиваем готовый файл в браузер
                    window.location.href = `/api/reports/${generateReportId}/download`;
                }

                if (res.data.status === 'error') {
                    clearInterval(interval);
                    isLoading.value = false;
                    errorMessage.value = 'Произошла ошибка при сборке отчета на сервере.';
                }
            } catch (error) {
                clearInterval(interval);
                isLoading.value = false;
                errorMessage.value = 'Ошибка связи с сервером.';
            }
        }, 1500); // Опрос каждые 1.5 секунды
    };
//---------------- FILE PARSE --------------------

    const parseReportId = ref(null);
    const progress = ref(0);
    const parsedData = ref([]);
    let pollingTimeoutId = null; // Таймер теперь живет прямо внутри компонента!
    let isPollingActive = false; // Флаг, чтобы таймауты не плодились
    const fileExtension = ref(null);
//------------
    // 1. Только загружаем файл и сохраняем ID
    async function parseData(fileObject) {
        fileParsingStatus.value = 'processing';
        progress.value = 0;
        parsedData.value = [];
        parseReportId.value = null;

        // Запоминаем расширение (приводим к нижнему регистру: csv, json)
        fileExtension.value = fileObject.name.split('.').pop().toLowerCase();
        try {
            const formData = new FormData();
            formData.append('uploaded_file', fileObject);
            // запуститься job:ProcessReportJob
            const response = await axios.post('/api/parseData', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            }); // вертаєм report_id
            if (response.data && response.data.report_id) {
                parseReportId.value = response.data.report_id;
                return true; // Говорим компоненту, что загрузка успешна
            }

            fileParsingStatus.value = 'error';
            return false;
        } catch (error) {
            console.error('Ошибка бэкенда при загрузке:', error);
            fileParsingStatus.value = 'error';
            return false;
        }
    }
    function startPolling() {
        isPollingActive = true;
        // Вызываем функцию один раз, дальше она будет перезапускать себя сама
        runPollingTick();
    }

    async function runPollingTick() {
        // Если опрос был остановлен пользователем или компонентом — выходим
        if (!isPollingActive) return;

        try {
            const response = await axios.get(`/api/reports-parse/${parseReportId.value}`, {
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache'
                }
            });
            const currentStatus = response.data.status;
            // Обновляем прогресс-бар на фронтенде
            progress.value = response.data.progress;
            fileParsingStatus.value = currentStatus;

            // Внутри runPollingTick во Vue-компоненте:
            if (currentStatus === 'processing' || currentStatus === 'idle') {
                pollingTimeoutId = setTimeout(() => {
                    runPollingTick();
                }, 1500); // Проверяем базу каждые 0.4 секунды вместо 1.5

            } else if (currentStatus === 'completed') {
                console.log('🎉 УРА! База данных подтвердила завершение!');
                console.log(parsedData.value)
                parsedData.value = response.data.parsed_data || [];

                stopPolling(); // Останавливаем опрос, всё готово!
            } else {
                console.log('--> Пришел неизвестный или ошибочный статус:', currentStatus);
                stopPolling();
            }

        } catch (error) {
            console.error('Ошибка при опросе бэкенда:', error);
            // Если это временная ошибка 404 (запись еще не создалась), даем бэкенду шанс и пробуем снова
            if (error.response && error.response.status === 404) {
                pollingTimeoutId = setTimeout(() => {
                    runPollingTick();
                }, 1500);
            } else {
                fileParsingStatus.value = 'error';
                stopPolling();
            }
        }
    }

    function stopPolling() {
        isPollingActive = false;
        if (pollingTimeoutId) {
            clearTimeout(pollingTimeoutId);
            pollingTimeoutId = null;
        }
    }

    // Функция безопасного превращения JSON-строки в JS-объект
    function parseJsonData(stringData) {
        try {
            // Если это уже объект (мало ли), просто возвращаем его
            if (typeof stringData === 'object') return stringData;

            // Иначе парсим строку
            return JSON.parse(stringData);
        } catch (e) {
            console.error("Ошибка парсинга строки данных:", e);
            return {"Ошибка": "Не удалось прочитать данные строки"};
        }
    }

    // Безопасность: гасим любые таймауты при уходе со страницы
    onBeforeUnmount(() => {
        stopPolling();
    });
    onMounted(() => {
        fetchServerImages();
    });
    return { fileParsingStatus, progress, parsedData, parseReportId, parseData, filters, fileGenerationStatus,
        startGeneration, isLoading, stopPolling, startPolling, parseJsonData, fileExtension,
        uploadImages, isUploading, removeLocalImage, handleFileChange, handleDrop, previewImages, serverImages, isDragOver,
        saveEditedImage, deleteFromServer
    };
});
