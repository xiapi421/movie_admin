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
                    <FormItem :label="t('video.video_category_id')" type="remoteSelect" v-model="baTable.form.items!.video_category_id" prop="video_category_id" :input-attr="{ pk: 'category.id', field: 'name', remoteUrl: '/admin/video.Category/index' }" :placeholder="t('Please select field', { field: t('video.video_category_id') })" />
                    <FormItem :label="t('video.name')" type="string" v-model="baTable.form.items!.name" prop="name" :placeholder="t('Please input field', { field: t('video.name') })" />
                    <FormItem :label="t('video.image')" type="image" v-model="baTable.form.items!.image" prop="image" />
                    <FormItem :label="t('video.url')" type="string" v-model="baTable.form.items!.url" prop="url" :placeholder="t('Please input field', { field: t('video.url') })" />
                    <FormItem :label="t('video.duration')" type="string" v-model="baTable.form.items!.duration" prop="duration" :placeholder="t('Please input field', { field: t('video.duration') })" />
                    <!-- <FormItem :label="t('video.total_views')" type="number" v-model="baTable.form.items!.total_views" prop="total_views" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('video.total_views') })" />
                    <FormItem :label="t('video.total_clicks')" type="number" v-model="baTable.form.items!.total_clicks" prop="total_clicks" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('video.total_clicks') })" />
                    <FormItem :label="t('video.total_purchases')" type="number" v-model="baTable.form.items!.total_purchases" prop="total_purchases" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('video.total_purchases') })" />
                    <FormItem :label="t('video.total_conversion_rate')" type="number" v-model="baTable.form.items!.total_conversion_rate" prop="total_conversion_rate" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('video.total_conversion_rate') })" />
                    <FormItem :label="t('video.today_views')" type="number" v-model="baTable.form.items!.today_views" prop="today_views" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('video.today_views') })" />
                    <FormItem :label="t('video.today_clicks')" type="number" v-model="baTable.form.items!.today_clicks" prop="today_clicks" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('video.today_clicks') })" />
                    <FormItem :label="t('video.today_purchases')" type="number" v-model="baTable.form.items!.today_purchases" prop="today_purchases" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('video.today_purchases') })" />
                    <FormItem :label="t('video.today_conversion_rate')" type="number" v-model="baTable.form.items!.today_conversion_rate" prop="today_conversion_rate" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('video.today_conversion_rate') })" /> -->
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
    // total_views: [buildValidatorData({ name: 'number', title: t('video.total_views') })],
    // total_clicks: [buildValidatorData({ name: 'number', title: t('video.total_clicks') })],
    // total_purchases: [buildValidatorData({ name: 'number', title: t('video.total_purchases') })],
    // total_conversion_rate: [buildValidatorData({ name: 'float', title: t('video.total_conversion_rate') })],
    // today_views: [buildValidatorData({ name: 'number', title: t('video.today_views') })],
    // today_clicks: [buildValidatorData({ name: 'number', title: t('video.today_clicks') })],
    // today_purchases: [buildValidatorData({ name: 'number', title: t('video.today_purchases') })],
    // today_conversion_rate: [buildValidatorData({ name: 'number', title: t('video.today_conversion_rate') })],
    // create_time: [buildValidatorData({ name: 'date', title: t('video.create_time') })],
    // update_time: [buildValidatorData({ name: 'date', title: t('video.update_time') })],
})
</script>

<style scoped lang="scss"></style>
