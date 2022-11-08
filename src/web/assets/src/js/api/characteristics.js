/* global Craft */

import axios from 'axios'

export default {
    getCharacteristicsForSource(sourceKey) {
        return axios.get(Craft.getActionUrl('characteristic/field/get-characteristics-for-source'), {
            params: {
                sourceKey: sourceKey,
            },
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },
}
