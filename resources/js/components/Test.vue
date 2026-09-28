<script>
import {useReportStore} from "../stores/reportStore.js";
import {onMounted, ref} from 'vue';
export default {
    name: "Test",
    setup() {
        const reportStore = useReportStore();
        const isDragOver = ref(false);
        const previewImages = ref([]);
        const isUploading = ref(false);
        const serverImages = ref([]);

        const handleFileChange = (e) => {
            processFiles(e.target.files); e.target.value = ''}
        const handleDrop = (e) => {
            if (e.dataTransfer.files.length > 0) {
                processFiles(e.dataTransfer.files);
                isDragOver.value = true;
            }
        }
        const processFiles = (filesList) => {
            Array.from(filesList).forEach((file) => {
                const PreviewUrl = URL.createObjectURL(file);
                previewImages.value.push({file, preview: PreviewUrl})
            })
        }
        const removeLocalImage = (index) => {
            URL.revokeObjectURL(previewImages.value[index].preview)
            previewImages.value.splice(index,1)
        }
        const uploadImages = async() => {
            if(previewImages.value.length === 0 )return;
            isUploading.value = true;
            const formData = new FormData();
            previewImages.value.forEach((img) => {
                formData.append('images[]', img.file)
            })
            try{
                const response = await axios.post(`api/upload-images`, formData, {
                    headers: {'Content-Type': 'multipart/form-data'}
                })
                serverImages.value.push(...response.data.images.map(img => ({
                    ...img, isEditing:false
                })));
                previewImages.value.forEach((img) => URL.revokeObjectURL(img.preview));
                previewImages.value = []
            } catch(e) {
                console.log(e)
            } finally{
                isUploading.value = false;
            }

        }
        const saveEditedImage = async(e, index) => {
            const file = e.target.files[0];
            if(!file || !file.type.startsWith('image/')) return;
            const imageId = serverImages.value[index].id;
            const formData = Object.entries({image:file, _method: 'PUT'} )
                .reduce((fd,[k,v]) => (fd.append(k,v), fd), new FormData)
            const response = await axios.put(`api/images/${imageId}`, formData,
                {headers: { 'Content-Type': 'multipart/form-data'}
                })
            serverImages.value[index].url = response.data.url
            serverImages.value[index].isEditing = false;
        }
        const deleteFromServer = (id, index) =>{
            if(!confirm('Видалити,')) return;
            axios.delete(`api/images/${id}`)
            serverImages.value.splice(index,1)
        }
        const fetchServerImages = async() => {
            const response = await axios.get('api/images');
            serverImages.value = response.data.images
        }
        onMounted (() => {
            fetchServerImages()
        });
        return {
            isDragOver,handleFileChange,handleDrop,previewImages,removeLocalImage,uploadImages,serverImages,isUploading,saveEditedImage,deleteFromServer,fetchServerImages,
        }
    }
}
</script>

<template>
    <label
    :class="[
        isDragOver
      ? 'border-blue-500 bg-blue-50 text-blue-600'
      : 'border-gray-300 bg-gray-50 hover:bg-gray-100 text-gray-500'
    ]"
    @dragover.prevent = "isDragOver = true"
    @dragenter.prevent = "isDragOver = true"
    @dragleave.prevent = "isDragOver.false"
    @drop.prevent = "handleDrop"
    >
        <span>Загрузіть чи перетащіть файл</span>
        <div>
            <input type="file" multiple accept="image" @change="handleFileChange">
        </div>
    </label>
<!--    Preview-->
    <div v-if="previewImages.length > 0">
        <h3 class="text-sm font-semibold text-gray-600">Новые файлы для отправки:</h3>
        <div class="grid grid-cols-3 gap-4">
        <div v-for="(img, index) in previewImages " :key="index" class="relative group border rounded-lg overflow-hidden h-24 bg-gray-100">
            <img :src="img.preview" class="w-full h-full object-cover" alt="Превью">
            <button
                @click="removeLocalImage(index)"
                class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                type="button"
            >✕</button>
        </div>
        </div>
    </div>
    <button @click="uploadImages"> {{ isUploading === true ? 'Зберігається' : 'Зберегти' }}</button>
<!--    Server imgs-->
    <div v-if="serverImages.length > 0"  class="border-t pt-4 space-y-2">
        <h3 class="text-sm font-semibold text-gray-700">Уже загружены на сервер (можно удалять/редактировать):</h3>
        <div class="grid grid-cols-3 gap-4">
            <div v-for="(img, index) in serverImages" :key="img.id"  class="relative group border rounded-lg overflow-hidden h-24 bg-gray-50 flex flex-col justify-between">
                <div v-if="img.isEditing" class="p-1 text-center flex flex-col items-center justify-center h-full">
                    <span class="text-[10px] text-gray-500 mb-1">Выберите замену:</span>
                    <input type="file" @change="saveEditedImage($event, index)" class="text-[10px] w-full">
                </div>
                <div v-else>
                    <img :src="img.url" class="w-full h-full object-cover" alt="Серверное фото" />
                    <!-- Панель действий при наведении -->
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                    <button @click="img.isEditing=true" class="bg-yellow-500 text-white rounded px-2.5 py-1 text-s hover:bg-yellow-600">✎ </button>
                    <button @click="deleteFromServer(img.id, index)" class="bg-red-500 text-white rounded px-2.5 py-1 text-s hover:bg-red-600">🗑</button>
                    </div>
                </div>
            </div>
        </div>
    </div>



</template>

<style scoped>

</style>
