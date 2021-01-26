/* global Craft */
/* global Garnish */
import 'vite/dynamic-import-polyfill'
import {createApp, ref} from 'vue'

import App from './App.vue'
import FieldSettings = characteristic.FieldSettings;
// import {store} from "./store";
import './css/main.css';
import {characteristicStore} from "./store/CharacteristicStore";

// @ts-ignore
Craft.CharacteristicsField = Garnish.Base.extend({
        vm: null,
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

            return;
            this.vm = new Vue({
                mounted() {
                    // We're going to remove any Twig rendered values to avoid confusing the Draft editor
                    Garnish.$doc.ready($.proxy(this, 'clearDefaultValues'));
                    this.recomputeUsedCharacteristics();
                },
                methods: {
                    clearDefaultValues() {
                        let defaultsContainer = document.querySelector(props.container + ' ' + '.defaults');
                        defaultsContainer.remove();
                    },
                    addBlock(characteristic) {
                        let id = 'new' + this.totalNewBlocks;
                        const block = {
                            id: id,
                            characteristic: characteristic.id,
                            values: [],
                            isNew: true
                        };
                        this.blocks.push(block)
                        this.totalNewBlocks++;
                        this.recomputeUsedCharacteristics();
                    },
                    deleteBlock(blockId) {
                        this.blocks = this.blocks.filter((block) => block.id !== blockId);
                        this.recomputeUsedCharacteristics();
                    },
                    addCharacteristicValue(characteristic, value) {
                        return {
                            id: 'todo',
                            value: 'todo'
                        }
                    },
                    recomputeUsedCharacteristics() {
                        this.usedCharacteristics = {};
                        this.blocks.forEach((block) => {
                            this.usedCharacteristics[block.characteristic] = true;
                        });
                    }
                }
            });
            return this.vm;
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
