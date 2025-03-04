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
                    <FormItem :label="t('order.order_sn')" type="string" v-model="baTable.form.items!.order_sn" prop="order_sn" :placeholder="t('Please input field', { field: t('order.order_sn') })" />
                    <FormItem :label="t('order.out_order_sn')" type="string" v-model="baTable.form.items!.out_order_sn" prop="out_order_sn" :placeholder="t('Please input field', { field: t('order.out_order_sn') })" />
                    <FormItem :label="t('order.ip')" type="string" v-model="baTable.form.items!.ip" prop="ip" :placeholder="t('Please input field', { field: t('order.ip') })" />
                    <FormItem :label="t('order.user_id')" type="remoteSelect" v-model="baTable.form.items!.user_id" prop="user_id" :input-attr="{ pk: 'user.id', field: 'username', remoteUrl: '/admin/user.User/index' }" :placeholder="t('Please select field', { field: t('order.user_id') })" />
                    <FormItem :label="t('order.video_id')" type="number" v-model="baTable.form.items!.video_id" prop="video_id" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('order.video_id') })" />
                    <FormItem :label="t('order.money')" type="number" v-model="baTable.form.items!.money" prop="money" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('order.money') })" />
                    <FormItem :label="t('order.subscribe_type')" type="select" v-model="baTable.form.items!.subscribe_type" prop="subscribe_type" :input-attr="{ content: { single: t('order.subscribe_type single'), day: t('order.subscribe_type day'), week: t('order.subscribe_type week'), month: t('order.subscribe_type month') } }" :placeholder="t('Please select field', { field: t('order.subscribe_type') })" />
                    <FormItem :label="t('order.pay_id')" type="number" v-model="baTable.form.items!.pay_id" prop="pay_id" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('order.pay_id') })" />
                    <FormItem :label="t('order.status')" type="switch" v-model="baTable.form.items!.status" prop="status" :input-attr="{ content: { '0': t('order.status 0'), '1': t('order.status 1'), '2': t('order.status 2') } }" />
                    <FormItem :label="t('order.notify_time')" type="time" v-model="baTable.form.items!.notify_time" prop="notify_time" :placeholder="t('Please select field', { field: t('order.notify_time') })" />
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
    video_id: [buildValidatorData({ name: 'number', title: t('order.video_id') })],
    money: [buildValidatorData({ name: 'number', title: t('order.money') })],
    pay_id: [buildValidatorData({ name: 'number', title: t('order.pay_id') })],
    create_time: [buildValidatorData({ name: 'date', title: t('order.create_time') })],
    update_time: [buildValidatorData({ name: 'date', title: t('order.update_time') })],
})
</script>

<style scoped lang="scss"></style>
