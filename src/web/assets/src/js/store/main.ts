import { defineStore } from 'pinia'
import { TemporaryId, Id, RootState, Characteristic, CharacteristicLinkBlock, CharacteristicValueValue } from '@/js/types/characteristics';

export const useMainStore = defineStore('main', {
    state: () => ({
        characteristics: [],
        blocks: [],
        fieldName: ''
    } as RootState),
    getters: {
        unusedCharacteristics: (state) => {
            const activeCharacteristicIds = state.blocks.map((linkBlock) => linkBlock.characteristicId);
            return state.characteristics.filter(characteristic => {
                return !activeCharacteristicIds.includes(characteristic.id)
            })
        },
        getCharacteristicById: (state) => {
            return (characteristicId: Id|TemporaryId) => state.characteristics.find((characteristic) => characteristic.id === characteristicId)
        }
    },
    actions: {
        findCharacteristicIndexById(id: Id|TemporaryId) {
            return this.characteristics.findIndex((item) => item.id === id);
        },

        findBlockIndexById(id: Id|TemporaryId) {
            return this.blocks.findIndex((item) => item.id === id);
        },

        addBlock(block: CharacteristicLinkBlock) {
            if (block.id === null) {
                block.id = 'new' + block.characteristicId
            }
            this.blocks.push(block)
        },

        deleteBlockById(id: Id|TemporaryId) {
            const index = this.findBlockIndexById(id);
            if (index === -1) return;
            this.blocks.splice(index, 1);
        },

        updateBlock(blockId: Id|TemporaryId, payload: CharacteristicLinkBlock) {
            if (!blockId || !payload) return;

            const index = this.findBlockIndexById(blockId);

            if (index !== -1) {
                this.blocks[index] = payload;
            }
        },

        updateBlockValue(blockId: Id|TemporaryId, value: CharacteristicValueValue) {
            const blockIndex = this.findBlockIndexById(blockId)
            const block = Object.assign({}, this.blocks[blockIndex], {values: value})
            this.updateBlock(blockId, block)
        }
    },
})