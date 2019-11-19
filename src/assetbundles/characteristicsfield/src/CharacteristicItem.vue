<template>
    <div class="characteristic-item matrixblock">
        <div class="titlebar"><div class="blocktype">Restaurant Criteria</div></div>
        <div class="actions">
            <a class="error icon delete" title="Delete" role="button" data-icon="remove"></a>
            <a class="move icon" title="Reorder" role="button"></a>
        </div>
        <div class="fields">
            <select>
                <option>Option 1</option>
                <option>Option 2</option>
                <option>Option 3</option>
            </select>
        </div>
    </div>
</template>
<script>
    /* eslint-disable */
    /* global Craft */
    export default {
        components: {
        },
        props: {
        },
        data() {
            return {
                characteristics: []
            }
        },
        methods: {

        },
        computed: {
        },
        watch: {
        },
        mounted() {
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