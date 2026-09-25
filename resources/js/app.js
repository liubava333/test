import './bootstrap';
// 1. Импортируем ваши Vue-компоненты
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import AppMain from "./components/AppMain.vue";



// 2. Создаем экземпляр приложения Vue
const app = createApp({});
const pinia = createPinia();
app.use(pinia);
// 3. Регистрируем компоненты глобально
app.component('appmain', AppMain);

// 4. Монтируем приложение к HTML-элементу с id="app"
app.mount('#app');

