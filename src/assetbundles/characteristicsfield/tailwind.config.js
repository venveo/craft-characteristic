/*
 *  @link      https://www.venveo.com
 *  @copyright Copyright (c) 2021 Venveo
 */

// module exports
module.exports = {
    important: true,
    purge: {
        content: [
            '../../../templates/**/*.{twig,html}',
            './**/*.{vue,html}',
        ],
        layers: [
            'base',
            'components',
            'utilities',
        ],
        mode: 'layers',
        options: {
            whitelist: [],
        }
    },
    theme: {
        colors: {
            grey: {

            }
        }
    },
    corePlugins: {},
    plugins: [],
};