/* global Craft */
/* global Garnish */
import {createApp} from 'vue'

import App from './App.vue'
import '@/css/app.pcss';
import {useMainStore} from "./store/main";
import FieldSettings = characteristic.FieldSettings;
import { createPinia } from 'pinia'


// @ts-ignore
Craft.CharacteristicsField = Garnish.Base.extend({
        init: function (settings: FieldSettings) {
            // @ts-ignore
            this.setSettings(settings, Craft.CharacteristicsField.defaults);
            const props = this.settings;
            const app = createApp(App)
            app.use(createPinia())

            const store = useMainStore();
            store.$state = {
                characteristics: props.characteristics,
                blocks: props.blocks,
                fieldName: props.name
            }

            app.mount(this.settings.mountPoint)
            document.querySelector(this.settings.defaultsContainer).remove();
        }
    },
    {
        defaults: {
            mountPoint: null,
            defaultsContainer: null,
            name: null,
            blocks: []
        }
    });
