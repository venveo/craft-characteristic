/* global Craft */
/* global Garnish */

import Vue from 'vue'
import VueEvents from 'vue-events'
import CharacteristicLinkBlock from './CharacteristicLinkBlock'
import CharacteristicControls from './CharacteristicControls'
import CharacteristicInput from './CharacteristicInput'
import {t} from '../../../../../../vendor/craftcms/cms/src/web/assets/pluginstore/src/js/filters/craft'

Vue.filter('t', t)
Vue.use(VueEvents)

Vue.config.devtools = true;

Craft.CharacteristicsField = Garnish.Base.extend({
        vm: null,
        init: function (settings) {
            this.setSettings(settings, Craft.CharacteristicsField.defaults);

            const props = this.settings;

            this.vm = new Vue({
                el: this.settings.container,
                components: {
                    CharacteristicLinkBlock,
                    CharacteristicControls,
                    CharacteristicInput
                },
                delimiters: ['${', '}'],
                data() {
                    return {
                        characteristics: props.characteristics,
                        usedCharacteristics: {},
                        blocks: props.blocks,
                        totalNewBlocks: 0,
                        name: props.name
                    };
                },
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
            container: null,
            name: null,
            blocks: []
        }
    });
