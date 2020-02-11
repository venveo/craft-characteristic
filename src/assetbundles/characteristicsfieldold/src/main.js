/* global Craft */
/* global Garnish */

import Vue from 'vue'
import CharacteristicBuilder from './CharacteristicBuilder'
import {t} from '../../../../../../vendor/craftcms/cms/src/web/assets/pluginstore/src/js/filters/craft'

Vue.filter('t', t)

Vue.config.devtools = true;

Craft.VueCharacteristicsField = Garnish.Base.extend({
        init: function (settings) {
            this.setSettings(settings, Craft.VueCharacteristicsField.defaults);

            const props = this.settings;

            return new Vue({
                components: {
                    CharacteristicBuilder
                },
                data() {
                    return {};
                },
                render: (h) => {
                    return h(CharacteristicBuilder, {
                        props: props
                    })
                },
            }).$mount(this.settings.container);
        }
},
{
    defaults: {
        container: null,
        name: null,
        source: null,
        value: null
    }
});
