<template>
    <!-- 对话框表单 -->
    <!-- 建议使用 Prettier 格式化代码 -->
    <!-- el-form 内可以混用 el-form-item、FormItem、ba-input 等输入组件 -->
    <el-dialog
        class="ba-operate-dialog"
        :close-on-click-modal="false"
        :model-value="['Add', 'Edit'].includes(baTable.form.operate!)"
        @close="baTable.toggleForm"
        width="50%"
    >
        <template #header>
            <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']" v-zoom="'.ba-operate-dialog'">
                {{ baTable.form.operate ? t(baTable.form.operate) : '' }}
            </div>
        </template>
        <el-scrollbar v-loading="baTable.form.loading" class="ba-table-form-scrollbar">
            <div
                class="ba-operate-form"
                :class="'ba-' + baTable.form.operate + '-form'"
                :style="config.layout.shrink ? '':'width: calc(100% - ' + baTable.form.labelWidth! / 2 + 'px)'"
            >
                <el-form
                    v-if="!baTable.form.loading"
                    ref="formRef"
                    @submit.prevent=""
                    @keyup.enter="baTable.onSubmit(formRef)"
                    :model="baTable.form.items"
                    :label-position="config.layout.shrink ? 'top' : 'right'"
                    :label-width="baTable.form.labelWidth + 'px'"
                    :rules="rules"
                >
                    <FormItem :label="t('withdraw.user_id')" type="remoteSelect" v-model="baTable.form.items!.user_id" prop="user_id" :input-attr="{ pk: 'user.id', field: 'username', remoteUrl: '/admin/user.User/index' }" :placeholder="t('Please select field', { field: t('withdraw.user_id') })" />
                    <FormItem :label="t('withdraw.money')" type="number" v-model="baTable.form.items!.money" prop="money" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('withdraw.money') })" />
                    <FormItem :label="t('withdraw.withdar_type')" type="select" v-model="baTable.form.items!.withdar_type" prop="withdar_type" :input-attr="{ content: { '0': t('withdraw.withdar_type 0'), '1': t('withdraw.withdar_type 1'), '2': t('withdraw.withdar_type 2'), '3': t('withdraw.withdar_type 3') } }" :placeholder="t('Please select field', { field: t('withdraw.withdar_type') })" />
                    <FormItem :label="t('withdraw.payee')" type="string" v-model="baTable.form.items!.payee" prop="payee" :placeholder="t('Please input field', { field: t('withdraw.payee') })" />
                    <FormItem :label="t('withdraw.payee_acount')" type="string" v-model="baTable.form.items!.payee_acount" prop="payee_acount" :placeholder="t('Please input field', { field: t('withdraw.payee_acount') })" />
                    <FormItem :label="t('withdraw.qrcode_image')" type="image" v-model="baTable.form.items!.qrcode_image" prop="qrcode_image" />
                    <FormItem :label="t('withdraw.trx_account')" type="string" v-model="baTable.form.items!.trx_account" prop="trx_account" :placeholder="t('Please input field', { field: t('withdraw.trx_account') })" />
                    <FormItem :label="t('withdraw.status')" type="radio" v-model="baTable.form.items!.status" prop="status" :input-attr="{ content: { '0': t('withdraw.status 0'), '1': t('withdraw.status 1'), '2': t('withdraw.status 2') } }" :placeholder="t('Please select field', { field: t('withdraw.status') })" />
                    <FormItem :label="t('withdraw.handle_time')" type="time" v-model="baTable.form.items!.handle_time" prop="handle_time" :placeholder="t('Please select field', { field: t('withdraw.handle_time') })" />
                </el-form>
            </div>
        </el-scrollbar>
        <template #footer>
            <div :style="'width: calc(100% - ' + baTable.form.labelWidth! / 1.8 + 'px)'">
                <el-button @click="baTable.toggleForm()">{{ t('Cancel') }}</el-button>
                <el-button v-blur :loading="baTable.form.submitLoading" @click="baTable.onSubmit(formRef)" type="primary">
                    {{ baTable.form.operateIds && baTable.form.operateIds.length > 1 ? t('Save and edit next item') : t('Save') }}
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import type { FormInstance, FormItemRule } from 'element-plus'
import { inject, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import FormItem from '/@/components/formItem/index.vue'
import { useConfig } from '/@/stores/config'
import type baTableClass from '/@/utils/baTable'
import { buildValidatorData } from '/@/utils/validate'

const config = useConfig()
const formRef = ref<FormInstance>()
const baTable = inject('baTable') as baTableClass

const { t } = useI18n()

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
    money: [buildValidatorData({ name: 'number', title: t('withdraw.money') })],
    create_time: [buildValidatorData({ name: 'date', title: t('withdraw.create_time') })],
    update_time: [buildValidatorData({ name: 'date', title: t('withdraw.update_time') })],
})
</script>

<style scoped lang="scss"></style>
