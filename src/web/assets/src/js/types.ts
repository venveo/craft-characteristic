/*
 *  @link      https://www.venveo.com
 *  @copyright Copyright (c) 2021 Venveo
 */

namespace characteristic {
    export type Id = number;
    export type TemporaryId = string;
    export type NullableId = Id | null;
    export type CharacteristicValueValue = string | null;

    export interface Element {
        id: NullableId|TemporaryId
    }

    export type FieldSettings = {
        mountPoint: string,
        defaultsContainer: string,
        name: string,
        blocks: CharacteristicLinkBlock[]
    }

    export interface Characteristic extends Element {
        allowCustomOptions: boolean,
        cpEditUrl: string | null,
        handle: string,
        maxValues: number,
        required: boolean,
        title: string,
        values: CharacteristicValue[]
    }

    export interface CharacteristicValue extends Element {
        idempotent: boolean,
        value: CharacteristicValueValue
    }

    export interface CharacteristicLinkBlock extends Element {
        characteristicId: Id,
        valueIds: Id[]
    }

    export interface HydratedCharacteristicLinkBlock extends CharacteristicLinkBlock {
        values: CharacteristicValue[]
    }
}