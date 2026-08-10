import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import axios from 'axios';

// TODO (candidato): reemplazar por la pantalla maestra real (§3.4).
// Este App.vue es un placeholder deliberadamente vacío: la SPA de una sola
// pantalla (carga de CSV, tabla server-side, drawer de resultados) es parte
// de tu entrega, no del starter-kit.
import App from './App.vue';

axios.defaults.baseURL = '/api';

const vuetify = createVuetify({ components, directives });

createApp(App)
    .use(createPinia())
    .use(vuetify)
    .mount('#app');
