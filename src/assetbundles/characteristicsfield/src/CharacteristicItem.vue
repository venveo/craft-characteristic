<template>
    <div class="characteristic-item matrixblock">
        <input type="hidden" :name="name + '[attribute]'" :value="attribute"/>
        <input type="hidden" :name="name + '[value]'" :value="value"/>
        <div class="titlebar">
            <div class="blocktype">Characteristic</div>
        </div>
        <div class="actions">
            <a class="error icon delete" @click="$emit('delete')" title="Delete" role="button" data-icon="remove"></a>
        </div>
        <div class="fields flex">
            <div class="input ltr">
                <div class="select">
                <select v-model="attribute">
                    <option v-for="option in options" :key="option.id" :value="option.handle"
                            :disabled="option.disabled">{{option.title}}
                    </option>
                </select>
                </div>
            </div>
            <div class="input ltr">
                <input class="text" type="text" v-model="value">
            </div>
        </div>
    </div>
</template>
<script>
    /* eslint-disable */
    /* global Craft */
    export default {
        components: {},
        props: {
            options: Array,
            name: String,
            data: Object
        },
        data() {
            return {
                attribute: '',
                value: ''
            }
        },
        methods: {},
        watch: {
            attribute: function (newVal) {
                this.$emit('change', newVal);
            }
        },
        mounted() {
            if (this.data.hasOwnProperty('characteristic')) {
                this.attribute = this.data.characteristic.handle;
            }
            if (this.data.hasOwnProperty('value')) {
                this.value = this.data.value.text;
            }
        }
    }
</script>

<style lang="scss" scoped>
    .characteristic-item.matrixblock {
        .actions > .delete {
            margin-right: 10px;
            font-size: 20px;
        }

        .titlebar {
            padding-left: 15px;
        }
    }
</style>