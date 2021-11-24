/* global Craft */
/* global Garnish */
import {createApp} from 'vue'

import App from './App.vue'
import '@/css/app.pcss';
import {characteristicStore} from "./store/CharacteristicStore";
import FieldSettings = characteristic.FieldSettings;

// @ts-ignore
Craft.CharacteristicsField = Garnish.Base.extend({
        init: function (settings: FieldSettings) {
            // @ts-ignore
            this.setSettings(settings, Craft.CharacteristicsField.defaults);
            const props = this.settings;

            characteristicStore.setCharacteristics(props.characteristics)
            characteristicStore.setBlocks(props.blocks)
            characteristicStore.setFieldName(props.name)
            const app = createApp(App)
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
