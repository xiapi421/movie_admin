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
                    <FormItem :label="t('pay.name')" type="string" v-model="baTable.form.items!.name" prop="name" :placeholder="t('Please input field', { field: t('pay.name') })" />
                    <FormItem :label="t('pay.select')" type="select" v-model="baTable.form.items!.select" prop="select" :input-attr="{ content: { alipay: t('pay.select alipay'), wechat: t('pay.select wechat') } }" :placeholder="t('Please select field', { field: t('pay.select') })" />
                    <FormItem :label="t('pay.weigh')" type="number" v-model="baTable.form.items!.weigh" prop="weigh" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('pay.weigh') })" />
                    <!-- <FormItem :label="t('pay.total_money')" type="number" v-model="baTable.form.items!.total_money" prop="total_money" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('pay.total_money') })" />
                    <FormItem :label="t('pay.total_order')" type="number" v-model="baTable.form.items!.total_order" prop="total_order" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('pay.total_order') })" />
                    <FormItem :label="t('pay.today_money')" type="number" v-model="baTable.form.items!.today_money" prop="today_money" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('pay.today_money') })" />
                    <FormItem :label="t('pay.today_order')" type="number" v-model="baTable.form.items!.today_order" prop="today_order" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('pay.today_order') })" />
                    <FormItem :label="t('pay.lastday_money')" type="number" v-model="baTable.form.items!.lastday_money" prop="lastday_money" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('pay.lastday_money') })" />
                    <FormItem :label="t('pay.lastday_order')" type="number" v-model="baTable.form.items!.lastday_order" prop="lastday_order" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('pay.lastday_order') })" /> -->
                    <FormItem :label="t('pay.remark')" type="string" v-model="baTable.form.items!.remark" prop="remark" :placeholder="t('Please input field', { field: t('pay.remark') })" />
                    <FormItem :label="t('pay.status')" type="switch" v-model="baTable.form.items!.status" prop="status" :input-attr="{ content: { '0': t('pay.status 0'), '1': t('pay.status 1') } }" />
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
    // total_money: [buildValidatorData({ name: 'float', title: t('pay.total_money') })],
    // total_order: [buildValidatorData({ name: 'number', title: t('pay.total_order') })],
    // today_money: [buildValidatorData({ name: 'float', title: t('pay.today_money') })],
    // today_order: [buildValidatorData({ name: 'number', title: t('pay.today_order') })],
    // lastday_money: [buildValidatorData({ name: 'float', title: t('pay.lastday_money') })],
    // lastday_order: [buildValidatorData({ name: 'number', title: t('pay.lastday_order') })],
    // create_time: [buildValidatorData({ name: 'date', title: t('pay.create_time') })],
    // update_time: [buildValidatorData({ name: 'date', title: t('pay.update_time') })],
})
</script>

<style scoped lang="scss"></style>
