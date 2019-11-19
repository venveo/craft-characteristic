/* global Craft */
/* global Garnish */

import Vue from 'vue'
import CharacteristicBuilder from './CharacteristicBuilder'

Craft.VueCharacteristicsField = Garnish.Base.extend(
    {
        settings: {
            container: '.vue-characteristics-input',
            options: {
            },
        },

        init: function(settings) {

            if (settings.options) {
                settings.options = {...this.settings.options, ...settings.options};
            } else {
                settings.options = this.settings.options;
            }

            this.setSettings(settings, Craft.VueCharacteristicsField.defaults);

            const dataSettings = this.settings;
            return new Vue({
                components: {
                    CharacteristicBuilder
                },
                data() {
                    return {};
                },
                render: (h) => {
                    return h(CharacteristicBuilder, {
                        props: {
                            settings: dataSettings
                        }
                    })
                },
            }).$mount(this.settings.container);
        },

    },
    {
        defaults: {
            test: 'one'
        }
    }
);
