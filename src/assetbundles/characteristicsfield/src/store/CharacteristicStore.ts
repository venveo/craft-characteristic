/*
 *  @link      https://www.venveo.com
 *  @copyright Copyright (c) 2021 Venveo
 */

import {InjectionKey, provide, inject, reactive} from 'vue'
import Characteristic = characteristic.Characteristic;
import CharacteristicLinkBlock = characteristic.CharacteristicLinkBlock;
import Id = characteristic.Id;

import {Store} from "./store"

interface State extends Object {
    characteristics: Characteristic[],
    blocks: CharacteristicLinkBlock[],
    fieldName: string
}

function initialState(): State {
    return {
        characteristics: [],
        blocks: [],
        fieldName: ''
    }
}

class CharacteristicStore extends Store<State> {
    protected data(): State {
        return initialState()
    }

    public setCharacteristics(characteristics: Characteristic[]) {
        this.state.characteristics = characteristics
    }

    public setBlocks(blocks: CharacteristicLinkBlock[]) {
        this.state.blocks = blocks
    }

    public setFieldName(name: string) {
        this.state.fieldName = name
    }

    public addBlock(block: CharacteristicLinkBlock) {
        if (block.id === null) {
            block.id = 'new' + block.characteristicId
        }
        this.state.blocks.push(block)
    }

    public deleteBlock(blockToDelete: CharacteristicLinkBlock) {
        this.state.blocks = this.getState().blocks.filter((block) => block.id !== blockToDelete.id);
    }

    public updateBlockValue(block: CharacteristicLinkBlock, value: Id[]) {
        const blockIndex = this.state.blocks.findIndex((v) => {
            return v.id === block.id
        })
        this.state.blocks[blockIndex] = Object.assign({}, this.state.blocks[blockIndex], {values: value})
    }

    public getCharacteristicById(id: Id): Characteristic|null {
        console.log(this.getState().characteristics)
        return this.getState().characteristics.find(o => o.id === id) ?? null;
    }
}

export const characteristicStore: CharacteristicStore = new CharacteristicStore()
//
//
// export class Store {
//     protected state: State
//
//     constructor(init: State = initialState()) {
//         this.state = reactive(init)
//     }
//
//     public getState(): State {
//         return this.state
//     }
//
//     public setCharacteristics(characteristics: Characteristic[]) {
//         this.state.characteristics = characteristics
//     }
//
//     public setBlocks(blocks: CharacteristicLinkBlock[]) {
//         this.state.blocks = blocks
//     }
//
//     public setFieldName(name: string) {
//         this.state.fieldName = name
//     }
//
//     public getFieldName(): string {
//         return this.state.fieldName
//     }
//
//     public getBlocks(): CharacteristicLinkBlock[] {
//         return this.state.blocks
//     }
//
//     public getCharacteristics(): Characteristic[] {
//         return this.state.characteristics
//     }
//
//     public addBlock(block: CharacteristicLinkBlock) {
//         if (block.id === null) {
//             block.id = 'new' + block.characteristicId
//         }
//         this.state.blocks.push(block)
//     }
//
//     public deleteBlock(blockToDelete: CharacteristicLinkBlock) {
//         this.state.blocks = this.state.blocks.filter((block) => block.id !== blockToDelete.id);
//     }
//
//     public getCharacteristicById(id: Id): Characteristic|null {
//         return this.state.characteristics.find(o => o.id === id) ?? null;
//     }
// }
//
// export const store = new Store()
// // @ts-ignore
// export const useStore = (): Store => inject('store')