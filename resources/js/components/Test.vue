<script>
import {useReportStore} from "../stores/reportStore.js";
import { ref } from 'vue';

export default {
    name: "Test",
    setup() {
        const reportStore = useReportStore();
        const localImages = ref([]);
        const isDragOver = ref(false);
        const isUploading = ref(false);
        const serverImages = ref([]);

        const handleDrop = (e) => {
           isDragOver.value = true;
           if(e.dataTransfer?.files.length > 0) {
               processFiles(e.dataTransfer.files);
           }
       }
        const processFiles = (filesList) => {
           Array.from(filesList).forEach((file) => {
                if(!file.type.startsWith('image/')) return;
                const preview = URL.createObjectURL(file);

               localImages.value.push({file, preview: preview})
           })
       }
        const uploadImages = async () => {
            if(localImages.value.lemgth === 0 )return;
            const formData = new FormData()
            localImages.value.forEach(img => formData.append('images[]', img.file))
            try {
                const response = await axios.post('api/upload-images', formData, {
                    headers: {'Content-Type': 'multipart/form-data'}
                })
                serverImages.value.push(...response.data.images.map(img => ({
                    ...img,
                    isEditing:false
                })))
                localImages.value.forEach(img => URL.revokeObjectURL(img.preview))
                localImages.value = []
            } catch( e) {
                console.log(e)
            } finally {
                isUploading.value = false;
            }
        }

    }
}
</script>

<template>
    <div class="p-6 max-w-xl mx-auto bg-white rounded-xl shadow-md space-y-6">
        <h2 class="text-xl font-bold text-gray-900">Управление изображениями</h2>

        <!-- ЗОНА ЗАГРУЗКИ С ДИНАМИЧЕСКИМ КЛАССОМ ПОДСВЕТКИ -->
        <div class="flex items-center justify-center w-full">
            <label
                class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer transition-colors"
                :class="[
          reportStore.isDragOver
            ? 'border-blue-500 bg-blue-50 text-blue-600'
            : 'border-gray-300 bg-gray-50 hover:bg-gray-100 text-gray-500'
        ]"
                @dragover.prevent="reportStore.isDragOver = true"
                @dragenter.prevent="reportStore.isDragOver = true"
                @dragleave.prevent="reportStore.isDragOver = false"
                @drop.prevent="reportStore.handleDrop"
            >
                <div class="flex flex-col items-center justify-center pt-5 pb-6 pointer-events-none">
                    <p class="mb-2 text-sm"><span class="font-semibold">Перетащите файлы сюда</span> или нажмите для выбора</p>
                    <p class="text-xs">PNG, JPG или WEBP</p>
                </div>
                <input type="file" multiple accept="image/*" class="hidden" @change="reportStore.handleFileChange" />
            </label>
        </div>

        <!-- БЛОК 1: ВРЕМЕННОЕ ПРЕВЬЮ (До отправки) -->
        <div v-if="reportStore.localImages.length > 0" class="space-y-2">
            <h3 class="text-sm font-semibold text-gray-600">Новые файлы для отправки:</h3>
            <div class="grid grid-cols-3 gap-4">
                <div v-for="(image, index) in reportStore.localImages" :key="index" class="relative group border rounded-lg overflow-hidden h-24 bg-gray-100">
                    <img :src="image.preview" class="w-full h-full object-cover" alt="Превью" />
                    <button
                        @click="reportStore.removeLocalImage(index)"
                        class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                        type="button"
                    >✕</button>
                </div>
            </div>
        </div>

        <!-- КНОПКА ОТПРАВКИ -->
        <button
            @click="reportStore.uploadImages"
            :disabled="reportStore.localImages.length === 0 || reportStore.isUploading"
            class="w-full py-2 px-4 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
        >
            {{ reportStore.isUploading ? 'Загрузка...' : 'Загрузить на сервер' }}
        </button>

        <!-- БЛОК 2: СЕРВЕРНЫЕ КАРТИНКИ (Уже сохраненные) -->
        <div v-if="reportStore.serverImages.length > 0" class="border-t pt-4 space-y-2">
            <h3 class="text-sm font-semibold text-gray-700">Уже загружены на сервер (можно удалять/редактировать):</h3>
            <div class="grid grid-cols-3 gap-4">
                <div v-for="(image, index) in reportStore.serverImages" :key="image.id" class="relative group border rounded-lg overflow-hidden h-24 bg-gray-50 flex flex-col justify-between">

                    <!-- Если картинка в режиме редактирования (замены) -->
                    <div v-if="image.isEditing" class="p-1 text-center flex flex-col items-center justify-center h-full">
                        <span class="text-[10px] text-gray-500 mb-1">Выберите замену:</span>
                        <input type="file" accept="image/*" @change="reportStore.saveEditedImage($event, index)" class="text-[10px] w-full" />
                        <button @click="image.isEditing = false" class="text-[10px] text-red-500 mt-1 underline">Отмена</button>
                    </div>

                    <!-- Обычный показ картинки с сервера -->
                    <template v-else>
                        <img :src="image.url" class="w-full h-full object-cover" alt="Серверное фото" />

                        <!-- Панель действий при наведении -->
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <button
                                @click="image.isEditing = true"
                                class="bg-yellow-500 text-white rounded px-2.5 py-1 text-s hover:bg-yellow-600"
                                title="Редактировать"
                            > ✎ </button>
                            <button
                                @click="reportStore.deleteFromServer(image.id, index)"
                                class="bg-red-500 text-white rounded px-2.5 py-1 text-s hover:bg-red-600"
                                title="Удалить"
                            > 🗑 </button>
                        </div>
                    </template>

                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
